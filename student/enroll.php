<?php
/**
 * GrowthEngineAI LMS - Course Enrollment Handler
 * Handles AJAX enrollment requests
 */

require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Course.php';
require_once __DIR__ . '/../classes/Url.php';

header('Content-Type: application/json');

$auth = new Auth();

// Check if user is logged in and is a student
if (!$auth->isLoggedIn() || $auth->getCurrentUser()['role'] !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Please log in as a student to enroll']);
    exit;
}

$user = $auth->getCurrentUser();
$courseModel = new Course();

// Get course by slug or ID from POST request
$courseSlug = isset($_POST['course_slug']) ? trim($_POST['course_slug']) : null;
$courseId = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;

// Check if course exists
$course = null;
if ($courseSlug) {
    $course = $courseModel->getCourseBySlug($courseSlug);
} elseif ($courseId) {
    $course = $courseModel->getCourseById($courseId);
}

if (!$course) {
    echo json_encode(['success' => false, 'message' => 'Course not found']);
    exit;
}

$courseId = $course['id'];
$courseSlug = $course['slug'];

// Check if already enrolled
if ($courseModel->isEnrolled($user['id'], $courseId)) {
    echo json_encode(['success' => false, 'message' => 'You are already enrolled in this course']);
    exit;
}

// Enroll the user
$pricePaid = $course['is_free'] ? 0 : ($course['sale_price'] ?? $course['price']);
$result = $courseModel->enrollUser($user['id'], $courseId, $pricePaid);

if ($result['success']) {
    echo json_encode([
        'success' => true, 
        'message' => 'Successfully enrolled in ' . $course['title'] . '!',
        'redirect' => Url::learn($courseSlug)
    ]);
} else {
    echo json_encode(['success' => false, 'message' => $result['message']]);
}
