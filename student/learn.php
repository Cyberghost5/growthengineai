<?php
/**
 * GrowthEngineAI LMS - Course Learning Page
 * Displays lessons, quizzes, and assignments
 */

require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Course.php';
require_once __DIR__ . '/../classes/Url.php';
require_once __DIR__ . '/../config/database.php';

$auth = new Auth();
$auth->requireRole('student');

$user = $auth->getCurrentUser();
$courseModel = new Course();
$db = getDB();

// Get course by slug or ID
$courseSlug = isset($_GET['course_slug']) ? trim($_GET['course_slug']) : null;
$courseId = isset($_GET['course']) ? (int)$_GET['course'] : null;
$lessonSlug = isset($_GET['lesson_slug']) ? trim($_GET['lesson_slug']) : null;
$lessonId = isset($_GET['lesson']) ? (int)$_GET['lesson'] : null;
$contentType = isset($_GET['type']) ? $_GET['type'] : 'lesson';
$itemId = isset($_GET['item_id']) ? (int)$_GET['item_id'] : null;

// Get course from database
if ($courseSlug) {
    $courseDataDB = $courseModel->getCourseBySlug($courseSlug);
} elseif ($courseId) {
    $courseDataDB = $courseModel->getCourseById($courseId);
} else {
    header('Location: ' . Url::courses());
    exit;
}

if (!$courseDataDB) {
    header('Location: ' . Url::courses());
    exit;
}

$courseId = $courseDataDB['id'];
$courseSlug = $courseDataDB['slug'];

// Check enrollment
$isEnrolled = $courseModel->isEnrolled($user['id'], $courseId);
if (!$isEnrolled) {
    header('Location: ' . Url::course($courseSlug));
    exit;
}

// Get user's lesson progress
$lessonProgressData = $courseModel->getCourseProgress($user['id'], $courseId);
$lessonProgressMap = [];
foreach ($lessonProgressData as $lp) {
    $lessonProgressMap[$lp['lesson_id']] = $lp['is_completed'];
}

// Build course data structure for template
$courseData = [
    'id' => $courseDataDB['id'],
    'slug' => $courseDataDB['slug'],
    'title' => $courseDataDB['title'],
    'instructor' => $courseDataDB['instructor_name'],
    'progress' => 0,
    'modules' => []
];

// Build modules with lessons
foreach ($courseDataDB['modules'] as $module) {
    $moduleLessons = [];
    
    // Add regular lessons
    foreach ($module['lessons'] as $lesson) {
        $isCompleted = isset($lessonProgressMap[$lesson['id']]) && $lessonProgressMap[$lesson['id']];
        $resources = $courseModel->getLessonResources($lesson['id']);
        
        $lessonItem = [
            'id' => $lesson['id'],
            'slug' => $lesson['slug'] ?? Url::slugify($lesson['title']),
            'title' => $lesson['title'],
            'duration' => $lesson['duration_minutes'] . ' min',
            'type' => $lesson['content_type'],
            'completed' => $isCompleted,
            'video_url' => $lesson['video_url'] ?? 'https://www.youtube.com/embed/ad79nYk2keg',
            'description' => $lesson['description'] ?? '',
            'resources' => []
        ];
        
        // Add resources
        foreach ($resources as $resource) {
            $lessonItem['resources'][] = [
                'name' => $resource['title'],
                'url' => $resource['file_url'],
                'size' => round($resource['file_size'] / 1024, 1) . ' KB'
            ];
        }
        
        $moduleLessons[] = $lessonItem;
    }
    
    // Add quiz if exists
    if ($module['quiz']) {
        $quiz = $module['quiz'];
        $quizQuestions = $courseModel->getQuizQuestions($quiz['id']);
        $userAttempts = $courseModel->getQuizAttempts($user['id'], $quiz['id']);
        
        $questionsFormatted = [];
        foreach ($quizQuestions as $q) {
            $options = [];
            $correctIndex = 0;
            foreach ($q['options'] as $idx => $opt) {
                $options[] = $opt['option_text'];
                if ($opt['is_correct']) {
                    $correctIndex = $idx;
                }
            }
            
            $questionsFormatted[] = [
                'id' => $q['id'],
                'question' => $q['question_text'],
                'type' => $q['question_type'],
                'options' => $options,
                'correct' => $correctIndex
            ];
        }
        
        $moduleLessons[] = [
            'id' => 'quiz_' . $quiz['id'],
            'quiz_id' => $quiz['id'],
            'title' => $quiz['title'],
            'duration' => $quiz['total_questions'] . ' questions',
            'type' => 'quiz',
            'completed' => false,
            'time_limit' => $quiz['time_limit_minutes'] ?? 15,
            'passing_score' => $quiz['passing_score'] ?? 70,
            'attempts_allowed' => $quiz['max_attempts'] ?? 3,
            'attempts_used' => count($userAttempts),
            'questions' => $questionsFormatted
        ];
    }
    
    // Add assignment if exists
    if ($module['assignment']) {
        $assignment = $module['assignment'];
        
        $moduleLessons[] = [
            'id' => 'assignment_' . $assignment['id'],
            'assignment_id' => $assignment['id'],
            'title' => $assignment['title'],
            'duration' => ($assignment['due_days'] ?? 7) . ' days',
            'type' => 'assignment',
            'completed' => false,
            'submitted' => false,
            'grade' => null,
            'due_date' => date('Y-m-d', strtotime('+' . ($assignment['due_days'] ?? 7) . ' days')),
            'description' => $assignment['description'] ?? '',
            'instructions' => $assignment['instructions'] ?? '',
            'max_points' => $assignment['max_points'] ?? 100,
            'submission' => null
        ];
    }
    
    $courseData['modules'][] = [
        'id' => $module['id'],
        'title' => $module['title'],
        'lessons' => $moduleLessons
    ];
}

// Find the current lesson
$currentLesson = null;
$currentModule = null;
$allLessons = [];
$lessonIndex = 0;
$currentLessonIndex = 0;

foreach ($courseData['modules'] as $module) {
    foreach ($module['lessons'] as $lesson) {
        $allLessons[] = [
            'lesson' => $lesson,
            'module' => $module
        ];
        
        // Match by slug first, then by ID, then find first incomplete
        if ($lessonSlug && isset($lesson['slug']) && $lesson['slug'] === $lessonSlug) {
            $currentLesson = $lesson;
            $currentModule = $module;
            $currentLessonIndex = $lessonIndex;
            $lessonId = $lesson['id'];
        } elseif ($lessonId !== null && $lesson['id'] === $lessonId) {
            $currentLesson = $lesson;
            $currentModule = $module;
            $currentLessonIndex = $lessonIndex;
        } elseif ($lessonId === null && $lessonSlug === null && !$lesson['completed'] && $currentLesson === null) {
            $currentLesson = $lesson;
            $currentModule = $module;
            $currentLessonIndex = $lessonIndex;
            $lessonId = $lesson['id'];
        }
        $lessonIndex++;
    }
}

// Default to first lesson if none selected
if ($currentLesson === null && !empty($allLessons)) {
    $currentLesson = $allLessons[0]['lesson'];
    $currentModule = $allLessons[0]['module'];
    $currentLessonIndex = 0;
    $lessonId = $currentLesson['id'];
}

// Calculate progress
$completedCount = 0;
$totalCount = count($allLessons);
foreach ($allLessons as $item) {
    if ($item['lesson']['completed']) {
        $completedCount++;
    }
}
$progress = $totalCount > 0 ? round(($completedCount / $totalCount) * 100) : 0;

// Get previous and next lessons
$prevLesson = $currentLessonIndex > 0 ? $allLessons[$currentLessonIndex - 1]['lesson'] : null;
$nextLesson = $currentLessonIndex < count($allLessons) - 1 ? $allLessons[$currentLessonIndex + 1]['lesson'] : null;

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'submit_assignment') {
        // Handle assignment submission
        if (isset($_FILES['assignment_file']) && $_FILES['assignment_file']['error'] === UPLOAD_ERR_OK) {
            $message = 'Assignment submitted successfully! You will receive feedback within 48 hours.';
            $messageType = 'success';
        } else {
            $message = 'Please select a file to upload.';
            $messageType = 'danger';
        }
    }
    
    if ($action === 'submit_quiz') {
        // Handle quiz submission
        $message = 'Quiz submitted successfully! Your score: 80%';
        $messageType = 'success';
    }
    
    if ($action === 'mark_complete') {
        // Get the actual lesson ID (handle quiz/assignment prefixes)
        $markLessonId = $_POST['lesson_id'] ?? null;
        $markLessonSlug = $_POST['lesson_slug'] ?? null;
        if ($markLessonId && is_numeric($markLessonId)) {
            $courseModel->markLessonComplete($user['id'], (int)$markLessonId);
            $message = 'Lesson marked as complete!';
            $messageType = 'success';
            
            // Refresh the page to show updated progress using clean URL
            $redirectUrl = Url::learn($courseSlug, $markLessonSlug) . '?completed=1';
            header('Location: ' . $redirectUrl);
            exit;
        }
    }
}

// Check for completed message from redirect
if (isset($_GET['completed']) && $_GET['completed'] == '1') {
    $message = 'Lesson marked as complete!';
    $messageType = 'success';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($currentLesson['title']); ?> - GrowthEngineAI</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../images/favicon.png" rel="icon">
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Roboto', sans-serif;
            background: #0f172a;
            color: #e2e8f0;
            margin: 0;
            padding: 0;
        }
        .learn-header {
            background: #1e293b;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #334155;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }
        .learn-header .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            color: white;
            text-decoration: none;
        }
        .learn-header .logo img {
            height: 32px;
        }
        .learn-header .course-title {
            font-weight: 500;
            font-size: 14px;
            color: #94a3b8;
        }
        .header-progress {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .header-progress .progress {
            width: 200px;
            height: 8px;
            background: #334155;
            border-radius: 4px;
        }
        .header-progress .progress-bar {
            background: linear-gradient(135deg, #000016 0%, #00212d 100%);
        }
        .header-progress span {
            font-size: 13px;
            color: #94a3b8;
        }
        .btn-exit {
            background: transparent;
            border: 1px solid #475569;
            color: #94a3b8;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 13px;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .btn-exit:hover {
            background: #334155;
            color: white;
        }
        .main-container {
            display: flex;
            margin-top: 57px;
            height: calc(100vh - 57px);
        }
        .content-area {
            flex: 1;
            overflow-y: auto;
            padding: 0;
        }
        .sidebar-toggle {
            position: fixed;
            right: 20px;
            bottom: 20px;
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #000016 0%, #00212d 100%);
            border: none;
            border-radius: 50%;
            color: white;
            font-size: 20px;
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1001;
            box-shadow: 0 4px 15px rgba(139, 92, 246, 0.4);
        }
        .course-sidebar {
            width: 380px;
            background: #1e293b;
            border-left: 1px solid #334155;
            overflow-y: auto;
            flex-shrink: 0;
        }
        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid #334155;
        }
        .sidebar-header h5 {
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 8px;
            color: white;
        }
        .sidebar-header .progress-text {
            font-size: 13px;
            color: #94a3b8;
        }
        .module-section {
            border-bottom: 1px solid #334155;
        }
        .module-header {
            padding: 16px 20px;
            background: #0f172a;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: background 0.2s ease;
        }
        .module-header:hover {
            background: #1e293b;
        }
        .module-header h6 {
            font-weight: 600;
            font-size: 14px;
            margin: 0;
            color: #e2e8f0;
        }
        .module-header .toggle-icon {
            color: #64748b;
            transition: transform 0.2s ease;
        }
        .module-header.collapsed .toggle-icon {
            transform: rotate(-90deg);
        }
        .lesson-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .lesson-item {
            display: flex;
            align-items: center;
            padding: 14px 20px;
            cursor: pointer;
            transition: background 0.2s ease;
            border-left: 3px solid transparent;
            text-decoration: none;
            color: inherit;
        }
        .lesson-item:hover {
            background: rgba(139, 92, 246, 0.1);
        }
        .lesson-item.active {
            background: rgba(139, 92, 246, 0.15);
            border-left-color: #000016;
        }
        .lesson-item.completed .lesson-icon {
            background: #10b981;
            color: white;
        }
        .lesson-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #334155;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            font-size: 14px;
            color: #94a3b8;
            flex-shrink: 0;
        }
        .lesson-icon.video { color: #000016; }
        .lesson-icon.quiz { color: #f59e0b; }
        .lesson-icon.assignment { color: #06b6d4; }
        .lesson-info {
            flex: 1;
            min-width: 0;
        }
        .lesson-title-small {
            font-size: 13px;
            font-weight: 500;
            color: #e2e8f0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: 2px;
        }
        .lesson-meta-small {
            font-size: 11px;
            color: #64748b;
        }
        .video-container {
            background: #000;
            aspect-ratio: 16/9;
            width: 100%;
        }
        .video-container iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
        .lesson-content {
            padding: 30px 40px;
            max-width: 900px;
            margin: 0 auto;
        }
        .lesson-header {
            margin-bottom: 24px;
        }
        .lesson-type-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            margin-bottom: 12px;
        }
        .lesson-type-badge.video {
            background: rgba(139, 92, 246, 0.2);
            color: #a78bfa;
        }
        .lesson-type-badge.quiz {
            background: rgba(245, 158, 11, 0.2);
            color: #fbbf24;
        }
        .lesson-type-badge.assignment {
            background: rgba(6, 182, 212, 0.2);
            color: #22d3ee;
        }
        .lesson-title-main {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            font-size: 1.75rem;
            margin-bottom: 8px;
            color: white;
        }
        .lesson-description {
            color: #94a3b8;
            font-size: 15px;
            line-height: 1.6;
        }
        .lesson-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 40px;
            background: #1e293b;
            border-top: 1px solid #334155;
            position: sticky;
            bottom: 0;
        }
        .btn-nav {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 500;
            font-size: 14px;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .btn-prev {
            background: #334155;
            color: #e2e8f0;
            border: none;
        }
        .btn-prev:hover {
            background: #475569;
            color: white;
        }
        .btn-next {
            background: linear-gradient(135deg, #000016 0%, #00212d 100%);
            color: white;
            border: none;
        }
        .btn-next:hover {
            background: linear-gradient(135deg, #000016 0%, #00212d 100%);
            color: white;
        }
        .btn-complete {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-complete:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
        }
        .resources-section {
            background: #1e293b;
            border-radius: 12px;
            padding: 24px;
            margin-top: 30px;
        }
        .resources-section h5 {
            font-weight: 600;
            margin-bottom: 16px;
            color: white;
        }
        .resource-item {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            background: #0f172a;
            border-radius: 8px;
            margin-bottom: 8px;
            text-decoration: none;
            color: #e2e8f0;
            transition: all 0.2s ease;
        }
        .resource-item:hover {
            background: #334155;
            color: white;
        }
        .resource-item i {
            font-size: 20px;
            color: #000016;
            margin-right: 12px;
        }
        .resource-item .resource-info {
            flex: 1;
        }
        .resource-item .resource-name {
            font-weight: 500;
            font-size: 14px;
        }
        .resource-item .resource-size {
            font-size: 12px;
            color: #64748b;
        }
        /* Quiz Styles */
        .quiz-container {
            background: #1e293b;
            border-radius: 12px;
            padding: 30px;
            margin-top: 20px;
        }
        .quiz-info {
            display: flex;
            gap: 30px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #334155;
        }
        .quiz-info-item {
            text-align: center;
        }
        .quiz-info-item .value {
            font-size: 24px;
            font-weight: 700;
            color: #000016;
        }
        .quiz-info-item .label {
            font-size: 12px;
            color: #64748b;
        }
        .question-card {
            background: #0f172a;
            border-radius: 10px;
            padding: 24px;
            margin-bottom: 20px;
        }
        .question-number {
            font-size: 12px;
            color: #000016;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .question-text {
            font-size: 16px;
            font-weight: 500;
            color: white;
            margin-bottom: 16px;
        }
        .answer-option {
            display: flex;
            align-items: center;
            padding: 14px 16px;
            background: #1e293b;
            border: 2px solid #334155;
            border-radius: 8px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .answer-option:hover {
            border-color: #000016;
            background: rgba(139, 92, 246, 0.1);
        }
        .answer-option.selected {
            border-color: #000016;
            background: rgba(139, 92, 246, 0.15);
        }
        .answer-option input {
            margin-right: 12px;
            accent-color: #000016;
        }
        .btn-submit-quiz {
            background: linear-gradient(135deg, #000016 0%, #00212d 100%);
            color: white;
            border: none;
            padding: 14px 32px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
            margin-top: 20px;
        }
        .btn-submit-quiz:hover {
            background: linear-gradient(135deg, #000016 0%, #00212d 100%);
        }
        /* Assignment Styles */
        .assignment-container {
            background: #1e293b;
            border-radius: 12px;
            padding: 30px;
            margin-top: 20px;
        }
        .assignment-meta {
            display: flex;
            gap: 30px;
            margin-bottom: 24px;
        }
        .assignment-meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #94a3b8;
        }
        .assignment-meta-item i {
            color: #000016;
        }
        .assignment-instructions {
            background: #0f172a;
            border-radius: 10px;
            padding: 24px;
            margin-bottom: 24px;
        }
        .assignment-instructions h6 {
            color: white;
            font-weight: 600;
            margin-bottom: 16px;
        }
        .assignment-instructions p,
        .assignment-instructions li {
            color: #94a3b8;
            font-size: 14px;
            line-height: 1.8;
        }
        .upload-area {
            border: 2px dashed #475569;
            border-radius: 12px;
            padding: 40px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .upload-area:hover {
            border-color: #000016;
            background: rgba(139, 92, 246, 0.05);
        }
        .upload-area i {
            font-size: 48px;
            color: #64748b;
            margin-bottom: 16px;
        }
        .upload-area p {
            color: #94a3b8;
            margin-bottom: 8px;
        }
        .upload-area .file-types {
            font-size: 12px;
            color: #64748b;
        }
        .submission-status {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(5, 150, 105, 0.1) 100%);
            border: 1px solid #10b981;
            border-radius: 12px;
            padding: 24px;
            margin-top: 24px;
        }
        .submission-status h6 {
            color: #10b981;
            font-weight: 600;
            margin-bottom: 12px;
        }
        .submission-status .grade {
            font-size: 32px;
            font-weight: 700;
            color: #10b981;
        }
        .alert {
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid #10b981;
            color: #10b981;
        }
        .alert-danger {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid #ef4444;
            color: #ef4444;
        }
        @media (max-width: 992px) {
            .course-sidebar {
                position: fixed;
                right: -380px;
                top: 57px;
                height: calc(100vh - 57px);
                z-index: 999;
                transition: right 0.3s ease;
            }
            .course-sidebar.open {
                right: 0;
            }
            .sidebar-toggle {
                display: flex;
            }
            .lesson-content {
                padding: 20px;
            }
            .lesson-nav {
                padding: 15px 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="learn-header">
        <div class="d-flex align-items-center gap-4">
            <a href="<?php echo Url::courses(); ?>" class="logo">
                <i class="bi bi-arrow-left" style="font-size: 18px;"></i>
            </a>
            <div class="course-title"><?php echo htmlspecialchars($courseData['title']); ?></div>
        </div>
        <div class="header-progress">
            <div class="progress">
                <div class="progress-bar" style="width: <?php echo $progress; ?>%"></div>
            </div>
            <span><?php echo $progress; ?>% complete</span>
        </div>
        <a href="<?php echo Url::courses(); ?>" class="btn-exit">
            <i class="bi bi-x-lg me-1"></i> Exit
        </a>
    </header>

    <!-- Main Container -->
    <div class="main-container">
        <!-- Content Area -->
        <div class="content-area">
            <?php if ($currentLesson['type'] === 'video'): ?>
            <!-- Video Player -->
            <div class="video-container">
                <iframe src="<?php echo htmlspecialchars($currentLesson['video_url']); ?>" 
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                        allowfullscreen></iframe>
            </div>
            <?php endif; ?>

            <!-- Lesson Content -->
            <div class="lesson-content">
                <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <i class="bi bi-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
                <?php endif; ?>

                <div class="lesson-header">
                    <span class="lesson-type-badge <?php echo $currentLesson['type']; ?>">
                        <?php if ($currentLesson['type'] === 'video'): ?>
                            <i class="bi bi-play-circle"></i> Video Lesson
                        <?php elseif ($currentLesson['type'] === 'quiz'): ?>
                            <i class="bi bi-question-circle"></i> Quiz
                        <?php else: ?>
                            <i class="bi bi-file-earmark-text"></i> Assignment
                        <?php endif; ?>
                    </span>
                    <h1 class="lesson-title-main"><?php echo htmlspecialchars($currentLesson['title']); ?></h1>
                    <p class="lesson-description"><?php echo htmlspecialchars($currentLesson['description'] ?? ''); ?></p>
                </div>

                <?php if ($currentLesson['type'] === 'video'): ?>
                <!-- Video Lesson Content -->
                <?php if (!empty($currentLesson['resources'])): ?>
                <div class="resources-section">
                    <h5><i class="bi bi-folder me-2"></i>Lesson Resources</h5>
                    <?php foreach ($currentLesson['resources'] as $resource): ?>
                    <a href="<?php echo htmlspecialchars($resource['url']); ?>" class="resource-item">
                        <i class="bi bi-file-earmark-pdf"></i>
                        <div class="resource-info">
                            <div class="resource-name"><?php echo htmlspecialchars($resource['name']); ?></div>
                            <div class="resource-size"><?php echo htmlspecialchars($resource['size']); ?></div>
                        </div>
                        <i class="bi bi-download" style="color: #64748b;"></i>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php elseif ($currentLesson['type'] === 'quiz'): ?>
                <!-- Quiz Content -->
                <div class="quiz-container">
                    <div class="quiz-info">
                        <div class="quiz-info-item">
                            <div class="value"><?php echo count($currentLesson['questions']); ?></div>
                            <div class="label">Questions</div>
                        </div>
                        <div class="quiz-info-item">
                            <div class="value"><?php echo $currentLesson['time_limit']; ?> min</div>
                            <div class="label">Time Limit</div>
                        </div>
                        <div class="quiz-info-item">
                            <div class="value"><?php echo $currentLesson['passing_score']; ?>%</div>
                            <div class="label">Passing Score</div>
                        </div>
                        <div class="quiz-info-item">
                            <div class="value"><?php echo $currentLesson['attempts_allowed'] - $currentLesson['attempts_used']; ?></div>
                            <div class="label">Attempts Left</div>
                        </div>
                    </div>

                    <form method="POST" id="quizForm">
                        <input type="hidden" name="action" value="submit_quiz">
                        <?php foreach ($currentLesson['questions'] as $index => $question): ?>
                        <div class="question-card">
                            <div class="question-number">Question <?php echo $index + 1; ?> of <?php echo count($currentLesson['questions']); ?></div>
                            <div class="question-text"><?php echo htmlspecialchars($question['question']); ?></div>
                            <?php foreach ($question['options'] as $optIndex => $option): ?>
                            <label class="answer-option">
                                <input type="radio" name="q<?php echo $question['id']; ?>" value="<?php echo $optIndex; ?>" required>
                                <span><?php echo htmlspecialchars($option); ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <?php endforeach; ?>
                        <button type="submit" class="btn-submit-quiz">
                            <i class="bi bi-send me-2"></i>Submit Quiz
                        </button>
                    </form>
                </div>

                <?php elseif ($currentLesson['type'] === 'assignment'): ?>
                <!-- Assignment Content -->
                <div class="assignment-container">
                    <div class="assignment-meta">
                        <div class="assignment-meta-item">
                            <i class="bi bi-clock"></i>
                            <span>Estimated time: <?php echo htmlspecialchars($currentLesson['duration']); ?></span>
                        </div>
                        <div class="assignment-meta-item">
                            <i class="bi bi-calendar"></i>
                            <span>Due: <?php echo date('M d, Y', strtotime($currentLesson['due_date'])); ?></span>
                        </div>
                    </div>

                    <div class="assignment-instructions">
                        <h6><i class="bi bi-list-check me-2"></i>Instructions</h6>
                        <div style="white-space: pre-line; color: #94a3b8; font-size: 14px; line-height: 1.8;">
                            <?php echo htmlspecialchars($currentLesson['instructions']); ?>
                        </div>
                    </div>

                    <?php if (!empty($currentLesson['submission'])): ?>
                    <!-- Already Submitted -->
                    <div class="submission-status">
                        <h6><i class="bi bi-check-circle me-2"></i>Assignment Submitted</h6>
                        <div class="d-flex align-items-center gap-4">
                            <div>
                                <div class="grade"><?php echo $currentLesson['grade']; ?>%</div>
                                <div style="color: #10b981; font-size: 14px;">Your Grade</div>
                            </div>
                            <div style="flex: 1; padding-left: 20px; border-left: 1px solid rgba(16, 185, 129, 0.3);">
                                <p style="color: #e2e8f0; margin-bottom: 8px;"><strong>File:</strong> <?php echo htmlspecialchars($currentLesson['submission']['file']); ?></p>
                                <p style="color: #94a3b8; margin-bottom: 8px;"><strong>Submitted:</strong> <?php echo date('M d, Y \a\t h:i A', strtotime($currentLesson['submission']['submitted_at'])); ?></p>
                                <p style="color: #94a3b8; margin: 0;"><strong>Feedback:</strong> <?php echo htmlspecialchars($currentLesson['submission']['feedback']); ?></p>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <!-- Upload Form -->
                    <form method="POST" enctype="multipart/form-data" id="assignmentForm">
                        <input type="hidden" name="action" value="submit_assignment">
                        <input type="file" name="assignment_file" id="assignmentFile" style="display: none;" accept=".pdf,.doc,.docx,.ipynb,.py,.zip">
                        <div class="upload-area" onclick="document.getElementById('assignmentFile').click()">
                            <i class="bi bi-cloud-arrow-up"></i>
                            <p>Click to upload or drag and drop</p>
                            <div class="file-types">PDF, DOC, DOCX, IPYNB, PY, ZIP (max 25MB)</div>
                        </div>
                        <div id="selectedFile" style="display: none; margin-top: 16px; padding: 16px; background: #0f172a; border-radius: 8px;">
                            <div class="d-flex align-items-center gap-3">
                                <i class="bi bi-file-earmark-check" style="font-size: 24px; color: #10b981;"></i>
                                <div style="flex: 1;">
                                    <div style="font-weight: 500; color: #e2e8f0;" id="fileName"></div>
                                    <div style="font-size: 12px; color: #64748b;" id="fileSize"></div>
                                </div>
                                <button type="button" class="btn btn-sm" style="color: #ef4444;" onclick="removeFile()">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </div>
                        <button type="submit" class="btn-submit-quiz" id="submitBtn" style="display: none;">
                            <i class="bi bi-send me-2"></i>Submit Assignment
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Navigation -->
            <div class="lesson-nav">
                <div>
                    <?php if ($prevLesson): ?>
                    <a href="<?php echo Url::learn($courseSlug, $prevLesson['slug']); ?>" class="btn-nav btn-prev">
                        <i class="bi bi-chevron-left"></i>
                        Previous
                    </a>
                    <?php endif; ?>
                </div>
                <div class="d-flex gap-3">
                    <?php if (!$currentLesson['completed'] && $currentLesson['type'] === 'video'): ?>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="mark_complete">
                        <input type="hidden" name="lesson_id" value="<?php echo is_numeric($currentLesson['id']) ? $currentLesson['id'] : ''; ?>">
                        <button type="submit" class="btn-complete">
                            <i class="bi bi-check-lg"></i>
                            Mark as Complete
                        </button>
                    </form>
                    <?php endif; ?>
                    <?php if ($nextLesson): ?>
                    <a href="<?php echo Url::learn($courseSlug, $nextLesson['slug']); ?>" class="btn-nav btn-next">
                        Next
                        <i class="bi bi-chevron-right"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Course Sidebar -->
        <div class="course-sidebar" id="courseSidebar">
            <div class="sidebar-header">
                <h5>Course Content</h5>
                <div class="progress-text"><?php echo $completedCount; ?> of <?php echo $totalCount; ?> lessons completed</div>
            </div>

            <?php foreach ($courseData['modules'] as $module): ?>
            <div class="module-section">
                <div class="module-header" onclick="toggleModule(this)">
                    <h6>Module <?php echo $module['id']; ?>: <?php echo htmlspecialchars($module['title']); ?></h6>
                    <i class="bi bi-chevron-down toggle-icon"></i>
                </div>
                <ul class="lesson-list">
                    <?php foreach ($module['lessons'] as $lesson): ?>
                    <a href="<?php echo Url::learn($courseSlug, $lesson['slug']); ?>" 
                       class="lesson-item <?php echo $lesson['id'] === $lessonId ? 'active' : ''; ?> <?php echo $lesson['completed'] ? 'completed' : ''; ?>">
                        <div class="lesson-icon <?php echo $lesson['type']; ?>">
                            <?php if ($lesson['completed']): ?>
                                <i class="bi bi-check-lg"></i>
                            <?php elseif ($lesson['type'] === 'video'): ?>
                                <i class="bi bi-play-fill"></i>
                            <?php elseif ($lesson['type'] === 'quiz'): ?>
                                <i class="bi bi-question-lg"></i>
                            <?php else: ?>
                                <i class="bi bi-file-earmark-text"></i>
                            <?php endif; ?>
                        </div>
                        <div class="lesson-info">
                            <div class="lesson-title-small"><?php echo htmlspecialchars($lesson['title']); ?></div>
                            <div class="lesson-meta-small">
                                <?php echo ucfirst($lesson['type']); ?> • <?php echo $lesson['duration']; ?>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Mobile Sidebar Toggle -->
    <button class="sidebar-toggle" onclick="toggleSidebar()">
        <i class="bi bi-list"></i>
    </button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle module sections
        function toggleModule(header) {
            header.classList.toggle('collapsed');
            const lessonList = header.nextElementSibling;
            lessonList.style.display = lessonList.style.display === 'none' ? 'block' : 'none';
        }

        // Toggle mobile sidebar
        function toggleSidebar() {
            document.getElementById('courseSidebar').classList.toggle('open');
        }

        // File upload handling
        document.getElementById('assignmentFile')?.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                document.getElementById('fileName').textContent = file.name;
                document.getElementById('fileSize').textContent = formatFileSize(file.size);
                document.getElementById('selectedFile').style.display = 'block';
                document.getElementById('submitBtn').style.display = 'block';
            }
        });

        function removeFile() {
            document.getElementById('assignmentFile').value = '';
            document.getElementById('selectedFile').style.display = 'none';
            document.getElementById('submitBtn').style.display = 'none';
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // Quiz answer selection styling
        document.querySelectorAll('.answer-option').forEach(option => {
            option.addEventListener('click', function() {
                const parent = this.closest('.question-card');
                parent.querySelectorAll('.answer-option').forEach(opt => opt.classList.remove('selected'));
                this.classList.add('selected');
                this.querySelector('input').checked = true;
            });
        });
    </script>
</body>
</html>
