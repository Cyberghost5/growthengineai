<?php
/**
 * Debug script to check transaction and enrollment status
 * DELETE THIS FILE AFTER DEBUGGING
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/debug_payment.log');

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

echo "<!DOCTYPE html><html><head><title>Payment Debug</title>";
echo "<style>body{font-family:Arial,sans-serif;padding:20px;max-width:1200px;margin:0 auto;}
table{border-collapse:collapse;width:100%;margin:20px 0;}
th,td{border:1px solid #ddd;padding:10px;text-align:left;}
th{background:#f5f5f5;}
.success{background:#d1fae5;color:#065f46;}
.pending{background:#fef3c7;color:#92400e;}
.failed{background:#fee2e2;color:#991b1b;}
pre{background:#f5f5f5;padding:15px;overflow-x:auto;border-radius:5px;}
.btn{display:inline-block;padding:8px 16px;background:#000016;color:#fff;text-decoration:none;border-radius:5px;margin:5px;}
.btn:hover{background:#00212d;}
h2{border-bottom:2px solid #000016;padding-bottom:10px;}</style></head><body>";

echo "<h1>🔧 Payment Debug Tool</h1>";
echo "<p><strong>Current User:</strong> " . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . " (ID: {$user['id']}, Email: {$user['email']})</p>";
echo "<p><strong>Site URL:</strong> " . SITE_URL . "</p>";

// Check Paystack API connection
echo "<h2>1. Paystack API Connection Test</h2>";
$testResponse = @file_get_contents('https://api.paystack.co/transaction/verify/test', false, stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => 'Authorization: Bearer ' . $paystack->getPublicKey() . "\r\n",
        'timeout' => 10
    ],
    'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
]));
if ($testResponse === false) {
    echo "<p style='color:orange;'>⚠️ Could not connect to Paystack API (this may be normal for the test endpoint)</p>";
} else {
    echo "<p style='color:green;'>✅ Can reach Paystack API</p>";
}

echo "<h2>2. Recent Transactions (Last 10)</h2>";
$stmt = $db->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$user['id']]);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($transactions)) {
    echo "<p>No transactions found for your account</p>";
} else {
    echo "<table>";
    echo "<tr><th>ID</th><th>Reference</th><th>Course ID</th><th>Amount</th><th>Status</th><th>Created</th><th>Paid At</th><th>Action</th></tr>";
    foreach ($transactions as $t) {
        $statusClass = $t['status'] === 'completed' ? 'success' : ($t['status'] === 'pending' ? 'pending' : 'failed');
        echo "<tr>";
        echo "<td>{$t['id']}</td>";
        echo "<td style='font-family:monospace;font-size:12px;'>" . htmlspecialchars($t['reference']) . "</td>";
        echo "<td>{$t['course_id']}</td>";
        echo "<td>{$t['currency']} " . number_format($t['amount'], 2) . "</td>";
        echo "<td class='{$statusClass}'><strong>{$t['status']}</strong></td>";
        echo "<td>" . date('M j, Y H:i', strtotime($t['created_at'])) . "</td>";
        echo "<td>" . ($t['paid_at'] ? date('M j, Y H:i', strtotime($t['paid_at'])) : '-') . "</td>";
        echo "<td><a href='?verify=" . urlencode($t['reference']) . "' class='btn'>🔍 Verify</a></td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<h2>3. Your Enrollments</h2>";
$stmt = $db->prepare("SELECT e.*, c.title as course_title FROM enrollments e JOIN courses c ON e.course_id = c.id WHERE e.user_id = ? ORDER BY e.enrolled_at DESC");
$stmt->execute([$user['id']]);
$enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($enrollments)) {
    echo "<p>❌ No enrollments found for your account</p>";
} else {
    echo "<table>";
    echo "<tr><th>ID</th><th>Course</th><th>Course ID</th><th>Amount Paid</th><th>Enrolled At</th></tr>";
    foreach ($enrollments as $e) {
        echo "<tr>";
        echo "<td>{$e['id']}</td>";
        echo "<td>" . htmlspecialchars($e['course_title']) . "</td>";
        echo "<td>{$e['course_id']}</td>";
        echo "<td>₦" . number_format($e['amount_paid'], 2) . "</td>";
        echo "<td>" . date('M j, Y H:i', strtotime($e['enrolled_at'])) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Verify a specific transaction
if (isset($_GET['verify'])) {
    $reference = trim($_GET['verify']);
    echo "<h2>🔍 Verifying Transaction: <code>" . htmlspecialchars($reference) . "</code></h2>";
    
    echo "<div style='background:#f0f9ff;border:1px solid #0ea5e9;padding:15px;border-radius:5px;margin:15px 0;'>";
    
    // Get transaction from DB
    $transaction = $paystack->getTransactionByReference($reference);
    
    if (!$transaction) {
        echo "<p class='failed'>❌ Transaction not found in database</p>";
    } else {
        echo "<p><strong>Step 1 - Transaction in DB:</strong></p>";
        echo "<ul>";
        echo "<li>ID: {$transaction['id']}</li>";
        echo "<li>Course ID: {$transaction['course_id']}</li>";
        echo "<li>User ID: {$transaction['user_id']}</li>";
        echo "<li>Current Status: <strong>{$transaction['status']}</strong></li>";
        echo "<li>Amount: {$transaction['currency']} " . number_format($transaction['amount'], 2) . "</li>";
        echo "</ul>";
        
        // Verify with Paystack
        echo "<p><strong>Step 2 - Calling Paystack API...</strong></p>";
        $verification = $paystack->verifyPayment($reference);
        
        echo "<p><strong>Paystack Response:</strong></p>";
        echo "<pre>" . htmlspecialchars(json_encode($verification, JSON_PRETTY_PRINT)) . "</pre>";
        
        if ($verification['success']) {
            echo "<p class='success'>✅ Payment verified successfully with Paystack!</p>";
            
            // Re-fetch transaction to see updated status
            $updatedTransaction = $paystack->getTransactionByReference($reference);
            echo "<p><strong>Step 3 - Updated Transaction Status:</strong> {$updatedTransaction['status']}</p>";
            
            // Check enrollment using the transaction's user_id (not logged-in user)
            $txnUserId = (int)$transaction['user_id'];
            $txnCourseId = (int)$transaction['course_id'];
            
            $isEnrolled = $courseModel->isEnrolled($txnUserId, $txnCourseId);
            echo "<p><strong>Step 4 - Enrollment status for User {$txnUserId} in Course {$txnCourseId}:</strong> " . ($isEnrolled ? '<span class="success">✅ ENROLLED</span>' : '<span class="failed">❌ NOT ENROLLED</span>') . "</p>";
            
            if (!$isEnrolled) {
                echo "<p><strong>Step 5 - Attempting to enroll user...</strong></p>";
                $amount = isset($verification['data']['amount']) ? $verification['data']['amount'] / 100 : $transaction['amount'];
                $enrollResult = $courseModel->enrollUser($txnUserId, $txnCourseId, $amount);
                
                echo "<p>Enrollment result:</p>";
                echo "<pre>" . htmlspecialchars(json_encode($enrollResult, JSON_PRETTY_PRINT)) . "</pre>";
                
                if ($enrollResult['success']) {
                    echo "<p class='success'>✅ User enrolled successfully!</p>";
                } else {
                    echo "<p class='failed'>❌ Enrollment failed: " . htmlspecialchars($enrollResult['message']) . "</p>";
                }
            }
        } else {
            echo "<p class='failed'>❌ Payment verification failed: " . htmlspecialchars($verification['message'] ?? 'Unknown error') . "</p>";
            if (isset($verification['debug_response'])) {
                echo "<p><strong>Debug info:</strong></p>";
                echo "<pre>" . htmlspecialchars(json_encode($verification['debug_response'], JSON_PRETTY_PRINT)) . "</pre>";
            }
        }
    }
    
    echo "</div>";
}

// Check database structure
echo "<h2>4. Database Structure Check</h2>";

echo "<details><summary>Click to view table structures</summary>";

// Check transactions table columns
$stmt = $db->query("DESCRIBE transactions");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<h3>Transactions Table:</h3>";
echo "<table>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
foreach ($columns as $col) {
    echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td><td>{$col['Default']}</td></tr>";
}
echo "</table>";

// Check enrollments table columns
$stmt = $db->query("DESCRIBE enrollments");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<h3>Enrollments Table:</h3>";
echo "<table>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
foreach ($columns as $col) {
    echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td><td>{$col['Default']}</td></tr>";
}
echo "</table>";

echo "</details>";

// Check error logs
echo "<h2>5. Recent Error Logs</h2>";
$logFiles = [
    'Payment Callback Log' => __DIR__ . '/logs/payment_callback.log',
    'Checkout Errors' => __DIR__ . '/logs/checkout_errors.log',
    'Debug Payment Log' => __DIR__ . '/logs/debug_payment.log',
    'Paystack Webhooks' => __DIR__ . '/logs/paystack_webhooks.log'
];

foreach ($logFiles as $name => $path) {
    echo "<h3>{$name}</h3>";
    if (file_exists($path)) {
        $content = file_get_contents($path);
        $lines = array_slice(explode("\n", $content), -30); // Last 30 lines
        echo "<pre style='max-height:200px;overflow-y:auto;font-size:11px;'>" . htmlspecialchars(implode("\n", $lines)) . "</pre>";
    } else {
        echo "<p style='color:gray;'>Log file not found: " . basename($path) . "</p>";
    }
}

echo "<br><br>";
echo "<div style='background:#fef3c7;border:1px solid #f59e0b;padding:15px;border-radius:5px;'>";
echo "<strong>⚠️ SECURITY WARNING:</strong> Delete this file (debug-payment.php) after debugging is complete!";
echo "</div>";

echo "</body></html>";
