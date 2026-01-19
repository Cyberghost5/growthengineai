<?php
/**
 * Debug script to check transaction and enrollment status
 * DELETE THIS FILE AFTER DEBUGGING
 */

require_once __DIR__ . '/classes/Auth.php';
require_once __DIR__ . '/classes/Course.php';
require_once __DIR__ . '/classes/Paystack.php';
require_once __DIR__ . '/config/database.php';

header('Content-Type: text/html; charset=utf-8');

$auth = new Auth();

// Only allow admin or logged in users
if (!$auth->isLoggedIn()) {
    die('Please log in first');
}

$user = $auth->getCurrentUser();
$db = getDB();
$paystack = new Paystack();
$courseModel = new Course();

echo "<h1>Payment Debug Tool</h1>";
echo "<p>Current User: " . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . " (ID: {$user['id']})</p>";

// Check recent transactions
echo "<h2>Recent Transactions (Last 10)</h2>";
$stmt = $db->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$user['id']]);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($transactions)) {
    echo "<p>No transactions found</p>";
} else {
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Reference</th><th>Course ID</th><th>Amount</th><th>Status</th><th>Created</th><th>Paid At</th><th>Action</th></tr>";
    foreach ($transactions as $t) {
        echo "<tr>";
        echo "<td>{$t['id']}</td>";
        echo "<td>" . htmlspecialchars($t['reference']) . "</td>";
        echo "<td>{$t['course_id']}</td>";
        echo "<td>{$t['currency']} {$t['amount']}</td>";
        echo "<td style='background:" . ($t['status'] === 'completed' ? '#d1fae5' : ($t['status'] === 'pending' ? '#fef3c7' : '#fee2e2')) . "'>{$t['status']}</td>";
        echo "<td>{$t['created_at']}</td>";
        echo "<td>{$t['paid_at']}</td>";
        echo "<td>";
        if ($t['status'] === 'pending') {
            echo "<a href='?verify=" . urlencode($t['reference']) . "'>Verify Now</a>";
        }
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Check enrollments
echo "<h2>Your Enrollments</h2>";
$stmt = $db->prepare("SELECT e.*, c.title as course_title FROM enrollments e JOIN courses c ON e.course_id = c.id WHERE e.user_id = ? ORDER BY e.enrolled_at DESC");
$stmt->execute([$user['id']]);
$enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($enrollments)) {
    echo "<p>No enrollments found</p>";
} else {
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Course</th><th>Amount Paid</th><th>Enrolled At</th></tr>";
    foreach ($enrollments as $e) {
        echo "<tr>";
        echo "<td>{$e['id']}</td>";
        echo "<td>" . htmlspecialchars($e['course_title']) . "</td>";
        echo "<td>{$e['amount_paid']}</td>";
        echo "<td>{$e['enrolled_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Verify a specific transaction
if (isset($_GET['verify'])) {
    $reference = trim($_GET['verify']);
    echo "<h2>Verifying Transaction: " . htmlspecialchars($reference) . "</h2>";
    
    // Get transaction from DB
    $transaction = $paystack->getTransactionByReference($reference);
    
    if (!$transaction) {
        echo "<p style='color:red;'>Transaction not found in database</p>";
    } else {
        echo "<p>Transaction found in DB - Course ID: {$transaction['course_id']}, Status: {$transaction['status']}</p>";
        
        // Verify with Paystack
        echo "<h3>Verifying with Paystack API...</h3>";
        $verification = $paystack->verifyPayment($reference);
        
        echo "<pre>" . htmlspecialchars(json_encode($verification, JSON_PRETTY_PRINT)) . "</pre>";
        
        if ($verification['success']) {
            echo "<p style='color:green;'>✅ Payment verified successfully with Paystack!</p>";
            
            // Check enrollment
            $isEnrolled = $courseModel->isEnrolled($user['id'], $transaction['course_id']);
            echo "<p>Enrollment status: " . ($isEnrolled ? '✅ Enrolled' : '❌ Not enrolled') . "</p>";
            
            if (!$isEnrolled) {
                echo "<h3>Attempting to enroll user...</h3>";
                $amount = $verification['data']['amount'] / 100;
                $enrollResult = $courseModel->enrollUser($user['id'], $transaction['course_id'], $amount);
                echo "<pre>" . htmlspecialchars(json_encode($enrollResult, JSON_PRETTY_PRINT)) . "</pre>";
                
                if ($enrollResult['success']) {
                    echo "<p style='color:green;'>✅ User enrolled successfully!</p>";
                } else {
                    echo "<p style='color:red;'>❌ Enrollment failed: {$enrollResult['message']}</p>";
                }
            }
        } else {
            echo "<p style='color:red;'>❌ Payment verification failed: " . htmlspecialchars($verification['message'] ?? 'Unknown error') . "</p>";
        }
    }
}

// Check database connection and table structure
echo "<h2>Database Check</h2>";

// Check transactions table columns
$stmt = $db->query("DESCRIBE transactions");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<h3>Transactions Table Structure:</h3>";
echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
foreach ($columns as $col) {
    echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td><td>{$col['Default']}</td></tr>";
}
echo "</table>";

// Check enrollments table columns
$stmt = $db->query("DESCRIBE enrollments");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<h3>Enrollments Table Structure:</h3>";
echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
foreach ($columns as $col) {
    echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td><td>{$col['Default']}</td></tr>";
}
echo "</table>";

echo "<br><br><p><strong>⚠️ DELETE THIS FILE (debug-payment.php) AFTER DEBUGGING!</strong></p>";
