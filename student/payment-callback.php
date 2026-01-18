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
    
    // Transaction is already saved and updated by verifyPayment()
    // Just enroll the user in the course
    $enrollResult = $courseModel->enrollUser($user['id'], $courseId, $amount);
    
    // Log enrollment result for debugging
    if (!$enrollResult['success']) {
        error_log("Enrollment failed for user {$user['id']} in course {$courseId}: {$enrollResult['message']}");
    }
    
    // Get course details for redirect
    $course = $courseModel->getCourseById($courseId);
    
    // Redirect to the course with success message
    header('Location: learn.php?slug=' . $course['slug'] . '&payment=success&message=' . urlencode('Payment successful! Welcome to the course!'));
    exit;
} else {
    // Redirect back to courses with error message
    header('Location: courses.php?payment=error&message=' . urlencode('Payment verification failed. Please contact support.'));
    exit;
}