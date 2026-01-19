<?php
/**
 * GrowthEngineAI LMS - Paystack Webhook Handler
 * 
 * This endpoint handles webhook events from Paystack for reliable payment verification.
 * Webhooks are server-to-server and more reliable than redirect callbacks.
 * 
 * Webhook URL: https://yourdomain.com/webhooks/paystack.php
 * 
 * To set up:
 * 1. Go to Paystack Dashboard > Settings > API Keys & Webhooks
 * 2. Add your webhook URL
 * 3. Copy the webhook secret and add it to your settings
 */

// Disable output buffering and set headers
ob_clean();
header('Content-Type: application/json');

// Log file for webhook debugging
$logFile = __DIR__ . '/../logs/paystack_webhooks.log';

function webhookLog($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[{$timestamp}] {$message}\n", FILE_APPEND | LOCK_EX);
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    webhookLog("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get the raw POST body
$input = file_get_contents('php://input');

if (empty($input)) {
    webhookLog("Empty webhook payload received");
    http_response_code(400);
    echo json_encode(['error' => 'Empty payload']);
    exit;
}

webhookLog("Webhook received - Raw payload: " . $input);

try {
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../classes/Settings.php';
    require_once __DIR__ . '/../classes/Course.php';
    
    $settings = new Settings();
    $db = getDB();
    
    // Get the Paystack secret key for signature verification
    $secretKey = $settings->get('paystack_secret_key', '');
    
    // Verify webhook signature
    $paystackSignature = $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ?? '';
    
    if (empty($paystackSignature)) {
        webhookLog("WARNING: No Paystack signature in request headers");
        // Continue processing but log the warning
    } else {
        // Validate signature
        $calculatedSignature = hash_hmac('sha512', $input, $secretKey);
        
        if ($paystackSignature !== $calculatedSignature) {
            webhookLog("ERROR: Invalid webhook signature");
            webhookLog("Expected: " . $calculatedSignature);
            webhookLog("Received: " . $paystackSignature);
            http_response_code(401);
            echo json_encode(['error' => 'Invalid signature']);
            exit;
        }
        
        webhookLog("Webhook signature verified successfully");
    }
    
    // Parse the event
    $event = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        webhookLog("ERROR: Failed to parse JSON - " . json_last_error_msg());
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
        exit;
    }
    
    $eventType = $event['event'] ?? '';
    $data = $event['data'] ?? [];
    
    webhookLog("Processing event: {$eventType}");
    webhookLog("Event data: " . json_encode($data));
    
    // Handle different event types
    switch ($eventType) {
        case 'charge.success':
            handleChargeSuccess($db, $data);
            break;
            
        case 'transfer.success':
            webhookLog("Transfer success event - not handled");
            break;
            
        case 'transfer.failed':
            webhookLog("Transfer failed event - not handled");
            break;
            
        default:
            webhookLog("Unhandled event type: {$eventType}");
    }
    
    // Always return 200 to acknowledge receipt
    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Webhook processed']);
    
} catch (Exception $e) {
    webhookLog("ERROR: Exception - " . $e->getMessage());
    webhookLog("Stack trace: " . $e->getTraceAsString());
    
    // Still return 200 to prevent Paystack from retrying
    http_response_code(200);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

/**
 * Handle successful charge event
 */
function handleChargeSuccess($db, $data) {
    webhookLog("Processing charge.success event");
    
    $reference = $data['reference'] ?? '';
    $status = $data['status'] ?? '';
    $amount = ($data['amount'] ?? 0) / 100; // Convert from kobo to naira
    $paidAt = $data['paid_at'] ?? date('Y-m-d H:i:s');
    $channel = $data['channel'] ?? 'unknown';
    $currency = $data['currency'] ?? 'NGN';
    
    // Get metadata
    $metadata = $data['metadata'] ?? [];
    $userId = $metadata['user_id'] ?? null;
    $courseId = $metadata['course_id'] ?? null;
    
    webhookLog("Reference: {$reference}, Status: {$status}, Amount: {$amount}, User: {$userId}, Course: {$courseId}");
    
    if (empty($reference)) {
        webhookLog("ERROR: No reference in webhook data");
        return;
    }
    
    if ($status !== 'success') {
        webhookLog("Payment status is not success: {$status}");
        return;
    }
    
    // Check if transaction exists in database
    $stmt = $db->prepare("SELECT * FROM transactions WHERE reference = ?");
    $stmt->execute([$reference]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$transaction) {
        webhookLog("Transaction not found in database for reference: {$reference}");
        
        // If we have user_id and course_id from metadata, create the transaction
        if ($userId && $courseId) {
            webhookLog("Creating transaction from webhook data");
            
            $email = $data['customer']['email'] ?? '';
            
            $insertStmt = $db->prepare("
                INSERT INTO transactions (reference, user_id, course_id, amount, currency, status, payment_method, email, paid_at, paystack_response, created_at)
                VALUES (?, ?, ?, ?, ?, 'completed', 'paystack', ?, ?, ?, NOW())
            ");
            
            $insertStmt->execute([
                $reference,
                $userId,
                $courseId,
                $amount,
                $currency,
                $email,
                $paidAt,
                json_encode($data)
            ]);
            
            webhookLog("Transaction created successfully");
        } else {
            webhookLog("Cannot create transaction - missing user_id or course_id in metadata");
            return;
        }
    } else {
        webhookLog("Found existing transaction ID: " . $transaction['id']);
        
        // Get user_id and course_id from existing transaction
        $userId = $transaction['user_id'];
        $courseId = $transaction['course_id'];
        
        // Check if already completed
        if ($transaction['status'] === 'completed') {
            webhookLog("Transaction already marked as completed, checking enrollment...");
        } else {
            // Update transaction status
            $updateStmt = $db->prepare("
                UPDATE transactions 
                SET status = 'completed', 
                    paid_at = ?, 
                    paystack_response = ?,
                    updated_at = NOW()
                WHERE reference = ?
            ");
            
            $updateStmt->execute([$paidAt, json_encode($data), $reference]);
            $rowCount = $updateStmt->rowCount();
            
            webhookLog("Transaction updated - Rows affected: {$rowCount}");
        }
    }
    
    // Enroll user in course
    if ($userId && $courseId) {
        enrollUserFromWebhook($db, $userId, $courseId, $amount, $reference);
    }
}

/**
 * Enroll user in course from webhook
 */
function enrollUserFromWebhook($db, $userId, $courseId, $amount, $reference) {
    webhookLog("Attempting to enroll user {$userId} in course {$courseId}");
    
    // Check if already enrolled
    $checkStmt = $db->prepare("SELECT id FROM enrollments WHERE user_id = ? AND course_id = ?");
    $checkStmt->execute([$userId, $courseId]);
    $existing = $checkStmt->fetch();
    
    if ($existing) {
        webhookLog("User already enrolled (enrollment ID: {$existing['id']})");
        return true;
    }
    
    try {
        // Create enrollment
        $enrollStmt = $db->prepare("
            INSERT INTO enrollments (user_id, course_id, amount_paid, enrolled_at)
            VALUES (?, ?, ?, NOW())
        ");
        
        $enrollStmt->execute([$userId, $courseId, $amount]);
        $enrollmentId = $db->lastInsertId();
        
        webhookLog("Enrollment created successfully - ID: {$enrollmentId}");
        
        // Update course enrollment count
        $db->exec("UPDATE courses SET total_enrollments = total_enrollments + 1 WHERE id = " . (int)$courseId);
        
        webhookLog("Course enrollment count updated");
        
        return true;
    } catch (PDOException $e) {
        webhookLog("ERROR: Enrollment failed - " . $e->getMessage());
        return false;
    }
}
