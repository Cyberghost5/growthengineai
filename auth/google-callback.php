<?php
/**
 * GrowthEngineAI LMS - Google OAuth Callback
 * 
 * Handles the callback from Google after authentication
 */

require_once __DIR__ . '/../classes/GoogleAuth.php';

$googleAuth = new GoogleAuth();

// Check for errors from Google
if (isset($_GET['error'])) {
    $error = $_GET['error_description'] ?? $_GET['error'];
    header('Location: login.php?error=' . urlencode($error));
    exit;
}

// Check for authorization code
if (!isset($_GET['code']) || !isset($_GET['state'])) {
    header('Location: login.php?error=' . urlencode('Invalid callback parameters.'));
    exit;
}

// Handle the callback
$result = $googleAuth->handleCallback($_GET['code'], $_GET['state']);

if ($result['success']) {
    // Redirect to dashboard
    header('Location: ' . $result['redirect']);
    exit;
} else {
    // Redirect to login with error
    header('Location: login.php?error=' . urlencode($result['error']));
    exit;
}
