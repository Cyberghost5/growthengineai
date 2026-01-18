<?php
/**
 * GrowthEngineAI LMS - Paystack Payment Callback
 * Handles payment verification after Paystack redirect
 */

require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Course.php';
require_once __DIR__ . '/../classes/Paystack.php';

$auth = new Auth();
$auth->requireRole('student');

$user = $auth->getCurrentUser();
$paystack = new Paystack();
$courseModel = new Course();

// Get payment reference and course ID from URL
$reference = isset($_GET['reference']) ? trim($_GET['reference']) : '';
$courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

if (empty($reference) || !$courseId) {
    echo json_encode(['success' => false, 'message' => 'Invalid payment reference']);
    exit;
}

// Verify the payment with Paystack
$verification = $paystack->verifyPayment($reference);

if ($verification['success']) {
    $transaction = $verification['data'];
    $amount = $transaction['amount'] / 100; // Convert from kobo to naira
    
    // Save transaction to database
    $paystack->saveTransaction([
        'reference' => $reference,
        'user_id' => $user['id'],
        'course_id' => $courseId,
        'amount' => $amount,
        'status' => 'completed'
    ]);
    
    // Enroll the user in the course
    $courseModel->enrollUser($user['id'], $courseId, $amount);
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Payment verification failed']);
}
