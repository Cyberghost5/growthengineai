<?php
/**
 * Debug script to test Paystack API connection
 * DELETE THIS FILE AFTER DEBUGGING
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/classes/Auth.php';
require_once __DIR__ . '/classes/Settings.php';
require_once __DIR__ . '/config/database.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    die('Please log in first');
}

$settings = new Settings();
$db = getDB();

echo "<h1>Paystack API Debug</h1>";

// 1. Check if settings table has Paystack keys
echo "<h2>1. Settings Check</h2>";

$stmt = $db->query("SELECT setting_key, setting_value, is_encrypted FROM settings WHERE setting_key LIKE 'paystack%'");
$paystackSettings = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($paystackSettings)) {
    echo "<p style='color:red;'>❌ No Paystack settings found in database!</p>";
} else {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Key</th><th>Value (masked)</th><th>Encrypted</th></tr>";
    foreach ($paystackSettings as $s) {
        $maskedValue = substr($s['setting_value'], 0, 15) . '...' . substr($s['setting_value'], -4);
        echo "<tr><td>{$s['setting_key']}</td><td>{$maskedValue}</td><td>{$s['is_encrypted']}</td></tr>";
    }
    echo "</table>";
}

// 2. Load keys via Settings class
echo "<h2>2. Keys Loaded via Settings Class</h2>";
$publicKey = $settings->get('paystack_public_key', 'NOT_FOUND');
$secretKey = $settings->get('paystack_secret_key', 'NOT_FOUND');

echo "<p><strong>Public Key:</strong> " . substr($publicKey, 0, 15) . '...' . substr($publicKey, -4) . "</p>";
echo "<p><strong>Secret Key:</strong> " . substr($secretKey, 0, 15) . '...' . substr($secretKey, -4) . "</p>";

if ($secretKey === 'NOT_FOUND' || $secretKey === 'YOUR_PAYSTACK_SECRET_KEY') {
    echo "<p style='color:red;'>❌ Secret key is not properly configured!</p>";
} else {
    echo "<p style='color:green;'>✅ Secret key appears to be configured</p>";
}

// 3. Test API connection
echo "<h2>3. Paystack API Connection Test</h2>";

if ($secretKey !== 'NOT_FOUND' && $secretKey !== 'YOUR_PAYSTACK_SECRET_KEY') {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.paystack.co/balance');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $secretKey,
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    echo "<p><strong>HTTP Code:</strong> {$httpCode}</p>";
    
    if ($curlError) {
        echo "<p style='color:red;'>❌ cURL Error: {$curlError}</p>";
    } else {
        $decoded = json_decode($response, true);
        if ($httpCode === 200 && isset($decoded['status']) && $decoded['status'] === true) {
            echo "<p style='color:green;'>✅ API connection successful!</p>";
            echo "<pre>" . htmlspecialchars(json_encode($decoded, JSON_PRETTY_PRINT)) . "</pre>";
        } else {
            echo "<p style='color:red;'>❌ API returned error:</p>";
            echo "<pre>" . htmlspecialchars($response) . "</pre>";
        }
    }
}

// 4. Test verify endpoint with a specific reference
echo "<h2>4. Test Verify Endpoint</h2>";

$testRef = isset($_GET['ref']) ? trim($_GET['ref']) : '';
if ($testRef) {
    echo "<p>Testing reference: <code>{$testRef}</code></p>";
    
    // Check transaction in DB first
    $stmt = $db->prepare("SELECT * FROM transactions WHERE reference = ?");
    $stmt->execute([$testRef]);
    $txn = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($txn) {
        echo "<p><strong>DB Transaction:</strong></p>";
        echo "<pre>" . htmlspecialchars(json_encode($txn, JSON_PRETTY_PRINT)) . "</pre>";
    } else {
        echo "<p style='color:red;'>Transaction not found in DB</p>";
    }
    
    // Call Paystack API
    $endpoint = 'https://api.paystack.co/transaction/verify/' . urlencode($testRef);
    echo "<p><strong>Calling:</strong> {$endpoint}</p>";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $secretKey,
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    echo "<p><strong>HTTP Code:</strong> {$httpCode}</p>";
    
    if ($curlError) {
        echo "<p style='color:red;'>❌ cURL Error: {$curlError}</p>";
    } else {
        $decoded = json_decode($response, true);
        echo "<p><strong>Paystack Response:</strong></p>";
        echo "<pre>" . htmlspecialchars(json_encode($decoded, JSON_PRETTY_PRINT)) . "</pre>";
        
        if ($decoded && isset($decoded['data']['status'])) {
            $status = $decoded['data']['status'];
            if ($status === 'success') {
                echo "<p style='color:green;'>✅ Payment status: SUCCESS</p>";
            } else {
                echo "<p style='color:orange;'>⚠️ Payment status: {$status}</p>";
            }
        }
    }
} else {
    // Show form to enter reference
    echo "<form method='get'>";
    echo "<label>Enter transaction reference: </label>";
    echo "<input type='text' name='ref' placeholder='GE_1768815403_696dfb2b7e745' style='width:300px;'>";
    echo "<button type='submit'>Test</button>";
    echo "</form>";
    
    // Show recent transactions
    $user = $auth->getCurrentUser();
    $stmt = $db->prepare("SELECT reference, status, amount, created_at FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$user['id']]);
    $recent = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($recent) {
        echo "<p><strong>Recent transactions (click to test):</strong></p>";
        echo "<ul>";
        foreach ($recent as $r) {
            echo "<li><a href='?ref=" . urlencode($r['reference']) . "'>{$r['reference']}</a> - {$r['status']} - ₦{$r['amount']} - {$r['created_at']}</li>";
        }
        echo "</ul>";
    }
}

echo "<br><br><p style='color:red;'><strong>⚠️ DELETE THIS FILE AFTER DEBUGGING!</strong></p>";
