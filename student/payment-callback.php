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

// Log the callback request for debugging
erro_log("Payment callback initiated - Reference: {$reference}, Course ID: {$courseId}, User ID: {$user['id']}");

if (empty($reference) || !$courseId) {
    error_log("Payment callback failed - Invalid parameters");
    echo json_encode(['success' => false, 'message' => 'Invalid payment reference']);
    exit;
}

// Verify the payment with Paystack
$verification = $paystack->verifyPayment($reference);

// Log verification result
error_log("Payment verification result: " . json_encode($verification));

if ($verification['success']) {
    $transaction = $verification['data'];
    $amount = $transaction['amount'] / 100; // Convert from kobo to naira
    
    error_log("Payment verified successfully - Amount: {$amount}");
    
    // Check if transaction was updated in database
    $dbTransaction = $paystack->getTransactionByReference($reference);
    error_log("Transaction from DB: " . json_encode($dbTransaction));
    
    if (!$dbTransaction) {
        error_log("ERROR: Transaction not found in database after verification!");
    }
    
    // Enroll the user in the course
    $enrollResult = $courseModel->enrollUser($user['id'], $courseId, $amount);
    
    // Log enrollment result
    error_log("Enrollment result: " . json_encode($enrollResult));
    
    if (!$enrollResult['success']) {
        error_log("ERROR: Enrollment failed for user {$user['id']} in course {$courseId}: {$enrollResult['message']}");
    } else {
        error_log("SUCCESS: User {$user['id']} enrolled in course {$courseId}");
    }
    
    // Get course details for redirect
    $course = $courseModel->getCourseById($courseId);
    
    if (!$course) {
        error_log("ERROR: Failed to get course details for course ID: {$courseId}");
        header('Location: courses.php?payment=error&message=' . urlencode('Course not found'));
        exit;
    }
    
    // Redirect to the course with success message
    error_log("Redirecting to learn.php with slug: {$course['slug']}");
    header('Location: learn.php?slug=' . $course['slug'] . '&payment=success&message=' . urlencode('Payment successful! Welcome to the course!'));
    exit;
} else {
    error_log("ERROR: Payment verification failed: " . json_encode($verification));
    // Redirect back to courses with error message
    header('Location: courses.php?payment=error&message=' . urlencode('Payment verification failed. Please contact support.'));
    exit;
}