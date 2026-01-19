<?php
/**
 * GrowthEngineAI LMS - Verify Pending Transaction
 * 
 * This endpoint allows students to manually verify and complete a pending transaction.
 * It re-checks with Paystack and updates the transaction/enrollment if successful.
 */

require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Course.php';
require_once __DIR__ . '/../classes/Paystack.php';

header('Content-Type: application/json');

$auth = new Auth();

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please log in']);
    exit;
}

$user = $auth->getCurrentUser();
$paystack = new Paystack();
$courseModel = new Course();

// Get reference from request
$reference = isset($_POST['reference']) ? trim($_POST['reference']) : '';

if (empty($reference)) {
    echo json_encode(['success' => false, 'message' => 'Transaction reference is required']);
    exit;
}

// Get the transaction from database
$transaction = $paystack->getTransactionByReference($reference);

if (!$transaction) {
    echo json_encode(['success' => false, 'message' => 'Transaction not found']);
    exit;
}

// Verify the transaction belongs to this user
if ($transaction['user_id'] != $user['id']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// If already completed, check enrollment
if ($transaction['status'] === 'completed') {
    $isEnrolled = $courseModel->isEnrolled($user['id'], $transaction['course_id']);
    
    if ($isEnrolled) {
        echo json_encode([
            'success' => true, 
            'message' => 'Transaction already completed and enrolled',
            'status' => 'completed',
            'enrolled' => true
        ]);
    } else {
        // Transaction completed but not enrolled - fix this
        $enrollResult = $courseModel->enrollUser($user['id'], $transaction['course_id'], $transaction['amount']);
        
        echo json_encode([
            'success' => $enrollResult['success'], 
            'message' => $enrollResult['success'] ? 'Enrollment completed successfully' : $enrollResult['message'],
            'status' => 'completed',
            'enrolled' => $enrollResult['success']
        ]);
    }
    exit;
}

// Verify with Paystack
$verification = $paystack->verifyPayment($reference);

if ($verification['success']) {
    $paystackData = $verification['data'];
    $amount = $paystackData['amount'] / 100;
    
    // Enroll the user
    $enrollResult = $courseModel->enrollUser($user['id'], $transaction['course_id'], $amount);
    
    // Get course details
    $course = $courseModel->getCourseById($transaction['course_id']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Payment verified and enrollment completed!',
        'status' => 'completed',
        'enrolled' => $enrollResult['success'],
        'course_slug' => $course['slug'] ?? null,
        'redirect' => $course ? 'learn.php?slug=' . $course['slug'] : 'courses.php'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Payment verification failed: ' . ($verification['message'] ?? 'Unknown error'),
        'status' => $transaction['status']
    ]);
}
