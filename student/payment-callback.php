<?php
/**
 * GrowthEngineAI LMS - Paystack Payment Callback
 * Handles payment verification after Paystack redirect
 */

// Start output buffering to catch any errors
ob_start();

// Set up error logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/payment_callback.log');

error_log("\n\n========== PAYMENT CALLBACK STARTED ==========");
error_log("Timestamp: " . date('Y-m-d H:i:s'));
error_log("GET params: " . json_encode($_GET));

require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Course.php';
require_once __DIR__ . '/../classes/Paystack.php';

$auth = new Auth();
$paystack = new Paystack();
$courseModel = new Course();

// Get payment reference and course ID from URL FIRST (before auth check)
// Paystack can send either 'reference' or 'trxref' as the parameter
$reference = isset($_GET['reference']) ? trim($_GET['reference']) : '';
if (empty($reference) && isset($_GET['trxref'])) {
    $reference = trim($_GET['trxref']);
}
$courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

error_log("Extracted - Reference: {$reference}, Course ID from URL: {$courseId}");

// If no reference, redirect with error
if (empty($reference)) {
    error_log("ERROR: No payment reference found in URL");
    header('Location: courses.php?payment=error&message=' . urlencode('Invalid payment reference'));
    exit;
}

// Get transaction from database to find user_id and course_id
$existingTransaction = $paystack->getTransactionByReference($reference);

if (!$existingTransaction) {
    error_log("ERROR: Transaction not found in database for reference: {$reference}");
    header('Location: courses.php?payment=error&message=' . urlencode('Transaction not found. Please contact support.'));
    exit;
}

error_log("Transaction found in DB - ID: {$existingTransaction['id']}, User: {$existingTransaction['user_id']}, Course: {$existingTransaction['course_id']}, Status: {$existingTransaction['status']}");

// Get user_id and course_id from transaction record
$userId = (int)$existingTransaction['user_id'];
$courseId = (int)$existingTransaction['course_id'];

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    error_log("User not logged in during callback. Attempting to verify payment anyway for user_id: {$userId}");
    $user = null;
} else {
    $user = $auth->getCurrentUser();
    error_log("Logged in user: {$user['id']} ({$user['email']})");
    
    // Verify the transaction belongs to this user
    if ((int)$user['id'] !== $userId) {
        error_log("WARNING: Logged in user ({$user['id']}) doesn't match transaction user ({$userId})");
        // Still proceed with verification using the transaction's user_id
    }
}

if (empty($reference) || !$courseId) {
    error_log("Payment callback failed - Invalid parameters (reference: {$reference}, courseId: {$courseId})");
    header('Location: courses.php?payment=error&message=' . urlencode('Invalid payment parameters'));
    exit;
}

// Verify the payment with Paystack
error_log("Calling verifyPayment for reference: {$reference}");
$verification = $paystack->verifyPayment($reference);

// Log verification result
error_log("Payment verification result: " . json_encode($verification));

if ($verification['success']) {
    $transaction = $verification['data'] ?? [];
    
    // Get amount - try from Paystack response first, fallback to database transaction
    $amount = 0;
    if (isset($transaction['amount'])) {
        $amount = $transaction['amount'] / 100; // Convert from kobo to naira
    } elseif (isset($existingTransaction['amount'])) {
        $amount = (float)$existingTransaction['amount'];
        error_log("Using amount from DB transaction: {$amount}");
    }
    
    error_log("Payment verified successfully - Amount: {$amount}, Cached: " . (isset($verification['cached']) ? 'YES' : 'NO'));
    
    // Check if already enrolled (avoid duplicate enrollment)
    $alreadyEnrolled = $courseModel->isEnrolled($userId, $courseId);
    error_log("Already enrolled check: " . ($alreadyEnrolled ? 'YES' : 'NO'));
    
    if (!$alreadyEnrolled) {
        // Enroll the user in the course using userId from transaction
        error_log("Attempting enrollment - User: {$userId}, Course: {$courseId}, Amount: {$amount}");
        $enrollResult = $courseModel->enrollUser($userId, $courseId, $amount);
        
        // Log enrollment result
        error_log("Enrollment result: " . json_encode($enrollResult));
        
        if (!$enrollResult['success']) {
            error_log("ERROR: Enrollment failed for user {$userId} in course {$courseId}: {$enrollResult['message']}");
        } else {
            error_log("SUCCESS: User {$userId} enrolled in course {$courseId}");
        }
    } else {
        error_log("User already enrolled, skipping enrollment");
    }
    
    // Get course details for redirect
    $course = $courseModel->getCourseById($courseId);
    
    if (!$course) {
        error_log("ERROR: Failed to get course details for course ID: {$courseId}");
        header('Location: courses.php?payment=success&message=' . urlencode('Payment successful! Enrollment complete.'));
        exit;
    }
    
    // Redirect to the course with success message
    error_log("========== PAYMENT CALLBACK SUCCESS ==========");
    error_log("Redirecting to learn.php with slug: {$course['slug']}");
    header('Location: learn.php?slug=' . $course['slug'] . '&payment=success&message=' . urlencode('Payment successful! Welcome to the course!'));
    exit;
} else {
    $errorMsg = $verification['message'] ?? 'Payment verification failed';
    error_log("========== PAYMENT CALLBACK FAILED ==========");
    error_log("ERROR: Payment verification failed: " . json_encode($verification));
    // Redirect back to courses with error message
    header('Location: courses.php?payment=error&message=' . urlencode($errorMsg . '. Please contact support if you were charged.'));
    exit;
}