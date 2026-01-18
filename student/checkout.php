<?php
/**
 * GrowthEngineAI LMS - Course Checkout
 * Handles payment initialization for course enrollment
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in output
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/checkout_errors.log');

// Prevent any output before JSON
ob_start();

try {
    require_once __DIR__ . '/../classes/Auth.php';
    require_once __DIR__ . '/../classes/Course.php';
    require_once __DIR__ . '/../classes/Paystack.php';
    require_once __DIR__ . '/../classes/Url.php';

    // Clear any output buffer and set JSON header
    ob_end_clean();
    header('Content-Type: application/json');

    $auth = new Auth();

// Check if user is logged in and is a student
if (!$auth->isLoggedIn() || $auth->getCurrentUser()['role'] !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Please log in as a student to enroll']);
    exit;
}

$user = $auth->getCurrentUser();
$courseModel = new Course();
$paystack = new Paystack();

// Get course ID from POST request
$courseId = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;

// Debug: Log what we received
error_log('Checkout - POST data: ' . print_r($_POST, true));
error_log('Checkout - course_id: ' . $courseId);

if (!$courseId) {
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid course',
        'debug' => [
            'post_data' => $_POST,
            'course_id' => $courseId,
            'raw_input' => file_get_contents('php://input')
        ]
    ]);
    exit;
}

// Get course details
$course = $courseModel->getCourseById($courseId);

if (!$course) {
    echo json_encode(['success' => false, 'message' => 'Course not found']);
    exit;
}

// Check if already enrolled
if ($courseModel->isEnrolled($user['id'], $courseId)) {
    echo json_encode(['success' => false, 'message' => 'You are already enrolled in this course']);
    exit;
}

// Determine the price
$price = 0;
if (!$course['is_free']) {
    $price = $course['sale_price'] > 0 ? $course['sale_price'] : $course['price'];
}

// If free course, enroll directly
if ($price == 0) {
    $result = $courseModel->enrollUser($user['id'], $courseId, 0);
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'free_course' => true,
            'message' => 'Successfully enrolled in ' . $course['title'] . '!',
            'redirect' => Url::learn($course['slug'])
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => $result['message']]);
    }
    exit;
}

// Initialize Paystack payment
$paymentResult = $paystack->initializePayment(
    $user['email'],
    $price,
    $courseId,
    $user['id'],
    [
        'course_title' => $course['title'],
        'student_name' => $user['first_name'] . ' ' . $user['last_name']
    ]
);

if ($paymentResult['success']) {
    echo json_encode([
        'success' => true,
        'payment_required' => true,
        'authorization_url' => $paymentResult['authorization_url'],
        'reference' => $paymentResult['reference']
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => $paymentResult['message']
    ]);
}

} catch (Exception $e) {
    // Catch any errors and return JSON
    ob_end_clean();
    header('Content-Type: application/json');
    
    // Log the full error
    error_log("Checkout Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage(),
        'error_type' => get_class($e),
        'error_file' => $e->getFile(),
        'error_line' => $e->getLine(),
        'trace' => $e->getTrace()
    ]);
}
