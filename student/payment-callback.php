<?php
/**
 * GrowthEngineAI LMS - Paystack Payment Callback
 * Handles payment verification after Paystack redirect
 */

require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Course.php';
require_once __DIR__ . '/../classes/Paystack.php';
require_once __DIR__ . '/../classes/Url.php';

$auth = new Auth();
$auth->requireRole('student');

$user = $auth->getCurrentUser();
$paystack = new Paystack();
$courseModel = new Course();

// Get payment reference from URL
$reference = isset($_GET['reference']) ? trim($_GET['reference']) : '';

if (empty($reference)) {
    header('Location: ' . Url::courses() . '?payment=error&message=' . urlencode('Invalid payment reference'));
    exit;
}

// Verify the payment
$verification = $paystack->verifyPayment($reference);

if ($verification['success']) {
    $transaction = $verification['transaction'];
    $courseId = $transaction['course_id'];
    $amount = $transaction['amount'];
    
    // Enroll the user in the course
    $enrollResult = $courseModel->enrollUser($user['id'], $courseId, $amount);
    
    if ($enrollResult['success']) {
        // Get course details for redirect
        $course = $courseModel->getCourseById($courseId);
        
        header('Location: ' . Url::learn($course['slug']) . '?payment=success&message=' . urlencode('Payment successful! Welcome to the course.'));
        exit;
    } else {
        header('Location: ' . Url::courses() . '?payment=error&message=' . urlencode('Payment verified but enrollment failed. Please contact support.'));
        exit;
    }
} else {
    header('Location: ' . Url::courses() . '?payment=failed&message=' . urlencode('Payment verification failed. Please try again or contact support.'));
    exit;
}
