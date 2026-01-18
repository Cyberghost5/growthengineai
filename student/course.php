<?php
/**
 * GrowthEngineAI LMS - Single Course Page
 */

require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Course.php';
require_once __DIR__ . '/../classes/Paystack.php';
require_once __DIR__ . '/../classes/Url.php';

$auth = new Auth();
$auth->requireRole('student');

$user = $auth->getCurrentUser();
$courseModel = new Course();
$paystack = new Paystack();
$paystackPublicKey = $paystack->getPublicKey();

// Get course by slug or ID from URL
$courseSlug = isset($_GET['slug']) ? trim($_GET['slug']) : null;
$courseId = isset($_GET['id']) ? (int)$_GET['id'] : null;

// Get course from database
if ($courseSlug) {
    $courseData = $courseModel->getCourseBySlug($courseSlug);
} elseif ($courseId) {
    $courseData = $courseModel->getCourseById($courseId);
} else {
    header('Location: ' . Url::courses());
    exit;
}

// Handle course not found
if (!$courseData) {
    header('Location: ' . Url::courses());
    exit;
}

$courseId = $courseData['id'];

// Check if user is enrolled
$isEnrolled = $courseModel->isEnrolled($user['id'], $courseId);
$enrollment = $isEnrolled ? $courseModel->getEnrollment($user['id'], $courseId) : null;

// Get user's lesson progress for this course
$lessonProgress = [];
if ($isEnrolled) {
    $progressData = $courseModel->getCourseProgress($user['id'], $courseId);
    foreach ($progressData as $lp) {
        $lessonProgress[$lp['lesson_id']] = $lp['is_completed'];
    }
}

// Build course array for template compatibility
$course = [
    'id' => $courseData['id'],
    'slug' => $courseData['slug'],
    'category_slug' => $courseData['category_slug'] ?? null,
    'title' => $courseData['title'],
    'instructor' => $courseData['instructor_name'],
    'instructor_image' => $courseData['instructor_image'] ?: 'https://ui-avatars.com/api/?name=' . urlencode($courseData['instructor_name']) . '&size=100&background=8b5cf6&color=fff',
    'instructor_bio' => $courseData['instructor_bio'] ?? '',
    'description' => $courseData['subtitle'] ?? '',
    'long_description' => $courseData['description'] ?? '',
    'image' => $courseData['thumbnail'] ?: 'https://images.unsplash.com/photo-1677442136019-21780ecad995?w=800&h=400&fit=crop',
    'duration' => $courseData['duration_hours'] . ' hours',
    'lessons' => $courseData['total_lessons'] ?? 0,
    'level' => ucfirst($courseData['level']),
    'category' => $courseData['category_name'],
    'rating' => $courseData['average_rating'] ?? 0,
    'reviews' => $courseData['total_reviews'] ?? 0,
    'students' => $courseData['total_enrollments'] ?? 0,
    'language' => $courseData['language'] ?? 'English',
    'last_updated' => $courseData['updated_at'] ? date('Y-m-d', strtotime($courseData['updated_at'])) : date('Y-m-d'),
    'enrolled' => $isEnrolled,
    'progress' => $enrollment['progress_percent'] ?? 0,
    'is_free' => $courseData['is_free'],
    'price' => $courseData['is_free'] ? 0 : ($courseData['sale_price'] > 0 ? $courseData['sale_price'] : $courseData['price']),
    'price_display' => $courseData['is_free'] ? 'Free' : '₦' . number_format($courseData['is_free'] ? 0 : ($courseData['sale_price'] > 0 ? $courseData['sale_price'] : $courseData['price']), 2),
    'original_price' => $courseData['price'],
    'sale_price' => $courseData['sale_price'],
    'modules' => [],
    'what_you_learn' => $courseData['what_you_learn'] ?? [],
    'requirements' => $courseData['requirements'] ?? []
];

// Build modules array with lessons, quizzes, and assignments
foreach ($courseData['modules'] as $module) {
    $moduleLessons = [];
    
    // Add regular lessons
    foreach ($module['lessons'] as $lesson) {
        $moduleLessons[] = [
            'id' => $lesson['id'],
            'slug' => $lesson['slug'] ?? Url::slugify($lesson['title']),
            'title' => $lesson['title'],
            'duration' => $lesson['duration_minutes'] . ' min',
            'type' => $lesson['content_type'],
            'completed' => isset($lessonProgress[$lesson['id']]) && $lessonProgress[$lesson['id']],
            'preview' => $lesson['is_free_preview'] ?? false
        ];
    }
    
    // Add quiz if exists
    if ($module['quiz']) {
        $moduleLessons[] = [
            'id' => 'quiz_' . $module['quiz']['id'],
            'quiz_id' => $module['quiz']['id'],
            'title' => $module['quiz']['title'],
            'duration' => $module['quiz']['total_questions'] . ' questions',
            'type' => 'quiz',
            'completed' => false,
            'preview' => false
        ];
    }
    
    // Add assignment if exists
    if ($module['assignment']) {
        $moduleLessons[] = [
            'id' => 'assignment_' . $module['assignment']['id'],
            'assignment_id' => $module['assignment']['id'],
            'title' => $module['assignment']['title'],
            'duration' => $module['assignment']['due_days'] . ' days',
            'type' => 'assignment',
            'completed' => false,
            'preview' => false
        ];
    }
    
    $course['modules'][] = [
        'id' => $module['id'],
        'title' => $module['title'],
        'lessons' => $moduleLessons
    ];
}

// Calculate total lessons and progress
$totalLessons = 0;
$completedLessons = 0;
foreach ($course['modules'] as $module) {
    $totalLessons += count($module['lessons']);
    foreach ($module['lessons'] as $lesson) {
        if ($lesson['completed']) {
            $completedLessons++;
        }
    }
}
$progress = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($course['title']); ?> - GrowthEngineAI</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../../images/favicon.png" rel="icon">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: #f1f5f9;
        }
        .course-hero {
            background: linear-gradient(135deg, #000016 0%, #00212d 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
        }
        .course-hero h1 {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            font-size: 2rem;
            margin-bottom: 15px;
        }
        .course-hero .breadcrumb {
            background: none;
            padding: 0;
            margin-bottom: 20px;
        }
        .course-hero .breadcrumb a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
        }
        .course-hero .breadcrumb a:hover {
            color: white;
        }
        .course-hero .breadcrumb-item.active {
            color: white;
        }
        .course-hero .breadcrumb-item + .breadcrumb-item::before {
            color: rgba(255,255,255,0.5);
        }
        .course-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 20px;
        }
        .course-meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }
        .course-meta-item i {
            opacity: 0.8;
        }
        .rating-stars {
            color: #fbbf24;
        }
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        .course-sidebar {
            position: sticky;
            top: 20px;
        }
        .course-image {
            width: 100%;
            border-radius: 12px 12px 0 0;
        }
        .course-price-card {
            padding: 24px;
        }
        .course-price {
            font-size: 32px;
            font-weight: 700;
            color: #1e293b;
        }
        .course-price .original {
            font-size: 18px;
            color: #94a3b8;
            text-decoration: line-through;
            margin-left: 10px;
            font-weight: 400;
        }
        .btn-enroll {
            background: linear-gradient(135deg, #000016 0%, #00212d 100%);
            border: none;
            color: white;
            padding: 14px 24px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 16px;
            width: 100%;
            transition: all 0.3s ease;
        }
        .btn-enroll:hover {
            background: linear-gradient(135deg, #000016 0%, #00212d 100%);
            color: white;
            transform: translateY(-2px);
        }
        .btn-continue {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        .btn-continue:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
        }
        .course-features {
            padding: 20px 24px;
            border-top: 1px solid #e2e8f0;
        }
        .course-feature {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 0;
            color: #64748b;
            font-size: 14px;
        }
        .course-feature i {
            color: #000016;
            width: 20px;
        }
        .section-title {
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 20px;
            font-size: 1.25rem;
        }
        .learn-list {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }
        .learn-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 14px;
            color: #475569;
        }
        .learn-item i {
            color: #10b981;
            margin-top: 3px;
            flex-shrink: 0;
        }
        .module-accordion .accordion-item {
            border: 1px solid #e2e8f0;
            border-radius: 10px !important;
            margin-bottom: 12px;
            overflow: hidden;
        }
        .module-accordion .accordion-button {
            background: #f8fafc;
            font-weight: 600;
            color: #1e293b;
            padding: 16px 20px;
        }
        .module-accordion .accordion-button:not(.collapsed) {
            background: linear-gradient(135deg, #000016 0%, #00212d 100%);
            color: white;
        }
        .module-accordion .accordion-button::after {
            filter: brightness(0) saturate(100%);
        }
        .module-accordion .accordion-button:not(.collapsed)::after {
            filter: brightness(0) invert(1);
        }
        .module-info {
            font-size: 13px;
            font-weight: 400;
            opacity: 0.8;
            margin-left: auto;
            padding-right: 10px;
        }
        .lesson-item {
            display: flex;
            align-items: center;
            padding: 14px 20px;
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.2s ease;
        }
        .lesson-item:last-child {
            border-bottom: none;
        }
        .lesson-item:hover {
            background: #f8fafc;
        }
        .lesson-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 14px;
            color: #000016;
            flex-shrink: 0;
        }
        .lesson-icon.completed {
            background: #10b981;
            color: white;
        }
        .lesson-info {
            flex: 1;
        }
        .lesson-title {
            font-weight: 500;
            color: #1e293b;
            font-size: 14px;
            margin-bottom: 2px;
        }
        .lesson-meta {
            font-size: 12px;
            color: #94a3b8;
        }
        .lesson-duration {
            color: #64748b;
            font-size: 13px;
            margin-left: auto;
        }
        .lesson-preview {
            background: #dbeafe;
            color: #2563eb;
            font-size: 11px;
            padding: 3px 8px;
            border-radius: 4px;
            margin-left: 10px;
        }
        .lesson-lock {
            color: #94a3b8;
            margin-left: 10px;
        }
        .instructor-card {
            display: flex;
            gap: 20px;
            padding: 24px;
        }
        .instructor-image {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
        }
        .instructor-info h5 {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 4px;
        }
        .instructor-info .title {
            color: #000016;
            font-size: 14px;
            margin-bottom: 10px;
        }
        .instructor-info p {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 0;
        }
        .requirements-list {
            padding-left: 0;
            list-style: none;
        }
        .requirements-list li {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 8px 0;
            color: #475569;
            font-size: 14px;
        }
        .requirements-list li i {
            color: #000016;
            margin-top: 3px;
        }
        .nav-tabs-custom {
            border-bottom: 2px solid #e2e8f0;
            margin-bottom: 24px;
        }
        .nav-tabs-custom .nav-link {
            border: none;
            color: #64748b;
            font-weight: 500;
            padding: 12px 24px;
            position: relative;
        }
        .nav-tabs-custom .nav-link.active {
            color: #000016;
            background: none;
        }
        .nav-tabs-custom .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 2px;
            background: #000016;
        }
        .progress {
            height: 8px;
            border-radius: 4px;
            background: #e2e8f0;
        }
        .progress-bar {
            background: linear-gradient(135deg, #000016 0%, #00212d 100%);
        }
        .back-link {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 15px;
            transition: color 0.2s ease;
        }
        .back-link:hover {
            color: white;
        }
    </style>
</head>
<body>
    <!-- Course Hero -->
    <div class="course-hero">
        <div class="container">
            <a href="<?php echo Url::courses(); ?>" class="back-link">
                <i class="bi bi-arrow-left"></i> Back to Courses
            </a>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo Url::dashboard(); ?>">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo Url::courses(); ?>">Courses</a></li>
                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($course['title']); ?></li>
                </ol>
            </nav>
            <div class="row">
                <div class="col-lg-8">
                    <span class="badge bg-light text-purple mb-3" style="color: #000016 !important;">
                        <?php echo htmlspecialchars($course['category']); ?>
                    </span>
                    <h1><?php echo htmlspecialchars($course['title']); ?></h1>
                    <p class="lead mb-0"><?php echo htmlspecialchars($course['description']); ?></p>
                    
                    <div class="course-meta">
                        <div class="course-meta-item">
                            <span class="rating-stars">
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-half"></i>
                            </span>
                            <span><?php echo $course['rating']; ?> (<?php echo number_format($course['reviews']); ?> reviews)</span>
                        </div>
                        <div class="course-meta-item">
                            <i class="bi bi-people"></i>
                            <span><?php echo number_format($course['students']); ?> students</span>
                        </div>
                        <div class="course-meta-item">
                            <i class="bi bi-person"></i>
                            <span>Created by <?php echo htmlspecialchars($course['instructor']); ?></span>
                        </div>
                        <div class="course-meta-item">
                            <i class="bi bi-calendar3"></i>
                            <span>Last updated <?php echo date('M Y', strtotime($course['last_updated'])); ?></span>
                        </div>
                        <div class="course-meta-item">
                            <i class="bi bi-globe"></i>
                            <span><?php echo htmlspecialchars($course['language']); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Alert Container -->
        <div id="alertContainer" class="mb-3"></div>
        
        <div class="row g-4">
            <!-- Main Content -->
            <div class="col-lg-8">
                <!-- Navigation Tabs -->
                <ul class="nav nav-tabs-custom" id="courseTabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#overview">Overview</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#curriculum">Curriculum</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#instructor">Instructor</button>
                    </li>
                </ul>

                <div class="tab-content">
                    <!-- Overview Tab -->
                    <div class="tab-pane fade show active" id="overview">
                        <!-- What You'll Learn -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="section-title"><i class="bi bi-check2-circle me-2"></i>What You'll Learn</h5>
                                <div class="learn-list">
                                    <?php foreach ($course['what_you_learn'] as $item): ?>
                                    <div class="learn-item">
                                        <i class="bi bi-check-lg"></i>
                                        <span><?php echo htmlspecialchars($item); ?></span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="section-title"><i class="bi bi-file-text me-2"></i>Description</h5>
                                <p class="text-muted"><?php echo htmlspecialchars($course['long_description']); ?></p>
                            </div>
                        </div>

                        <!-- Requirements -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="section-title"><i class="bi bi-list-check me-2"></i>Requirements</h5>
                                <ul class="requirements-list">
                                    <?php foreach ($course['requirements'] as $req): ?>
                                    <li>
                                        <i class="bi bi-dot"></i>
                                        <span><?php echo htmlspecialchars($req); ?></span>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Curriculum Tab -->
                    <div class="tab-pane fade" id="curriculum">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="section-title mb-0"><i class="bi bi-collection me-2"></i>Course Content</h5>
                            <span class="text-muted"><?php echo count($course['modules']); ?> modules • <?php echo $totalLessons; ?> lessons</span>
                        </div>

                        <div class="accordion module-accordion" id="courseModules">
                            <?php foreach ($course['modules'] as $index => $module): ?>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button <?php echo $index > 0 ? 'collapsed' : ''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#module<?php echo $module['id']; ?>">
                                        <span>Module <?php echo $index + 1; ?>: <?php echo htmlspecialchars($module['title']); ?></span>
                                        <span class="module-info"><?php echo count($module['lessons']); ?> lessons</span>
                                    </button>
                                </h2>
                                <div id="module<?php echo $module['id']; ?>" class="accordion-collapse collapse <?php echo $index === 0 ? 'show' : ''; ?>" data-bs-parent="#courseModules">
                                    <div class="accordion-body p-0">
                                        <?php foreach ($module['lessons'] as $lesson): ?>
                                        <div class="lesson-item">
                                            <div class="lesson-icon <?php echo $lesson['completed'] ? 'completed' : ''; ?>">
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
                                                <div class="lesson-title"><?php echo htmlspecialchars($lesson['title']); ?></div>
                                                <div class="lesson-meta">
                                                    <?php echo ucfirst($lesson['type']); ?>
                                                </div>
                                            </div>
                                            <span class="lesson-duration"><?php echo $lesson['duration']; ?></span>
                                            <?php if ($lesson['preview']): ?>
                                                <span class="lesson-preview">Preview</span>
                                            <?php elseif (!$course['enrolled']): ?>
                                                <i class="bi bi-lock lesson-lock"></i>
                                            <?php endif; ?>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Instructor Tab -->
                    <div class="tab-pane fade" id="instructor">
                        <div class="card">
                            <div class="instructor-card">
                                <img src="<?php echo htmlspecialchars($course['instructor_image']); ?>" alt="<?php echo htmlspecialchars($course['instructor']); ?>" class="instructor-image">
                                <div class="instructor-info">
                                    <h5><?php echo htmlspecialchars($course['instructor']); ?></h5>
                                    <div class="title">Lead Instructor</div>
                                    <p><?php echo htmlspecialchars($course['instructor_bio']); ?></p>
                                    <div class="d-flex gap-4 mt-3">
                                        <div class="text-center">
                                            <div class="fw-bold text-dark">15+</div>
                                            <small class="text-muted">Courses</small>
                                        </div>
                                        <div class="text-center">
                                            <div class="fw-bold text-dark">50,000+</div>
                                            <small class="text-muted">Students</small>
                                        </div>
                                        <div class="text-center">
                                            <div class="fw-bold text-dark">4.9</div>
                                            <small class="text-muted">Rating</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <div class="card course-sidebar">
                    <img src="<?php echo htmlspecialchars($course['image']); ?>" alt="<?php echo htmlspecialchars($course['title']); ?>" class="course-image">
                    
                    <div class="course-price-card">
                        <?php if ($course['enrolled']): ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted">Your Progress</span>
                                    <span class="fw-bold"><?php echo $progress; ?>%</span>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar" style="width: <?php echo $progress; ?>%"></div>
                                </div>
                            </div>
                            <a href="<?php echo Url::learn($course['slug']); ?>" class="btn-enroll btn-continue mb-3">
                                <i class="bi bi-play-fill me-2"></i>Continue Learning
                            </a>
                        <?php else: ?>
                            <div class="course-price mb-3">
                                <?php echo $course['price_display']; ?>
                                <?php if (!$course['is_free'] && $course['sale_price'] > 0 && $course['sale_price'] < $course['original_price']): ?>
                                <span class="original">₦<?php echo number_format($course['original_price'], 2); ?></span>
                                <?php endif; ?>
                            </div>
                            <button class="btn-enroll mb-3" id="enrollBtn" data-course-id="<?php echo $course['id']; ?>" data-course-slug="<?php echo $course['slug']; ?>" data-price="<?php echo $course['price']; ?>" data-is-free="<?php echo $course['is_free'] ? '1' : '0'; ?>">
                                <i class="bi bi-cart-plus me-2"></i>Enroll Now
                            </button>
                        <?php endif; ?>
                        
                        <p class="text-center text-muted mb-0" style="font-size: 13px;">
                            <i class="bi bi-shield-check me-1"></i>30-Day Money-Back Guarantee
                        </p>
                    </div>

                    <div class="course-features">
                        <h6 class="mb-3">This course includes:</h6>
                        <div class="course-feature">
                            <i class="bi bi-play-circle"></i>
                            <span><?php echo $course['lessons']; ?> video lessons</span>
                        </div>
                        <div class="course-feature">
                            <i class="bi bi-clock"></i>
                            <span><?php echo $course['duration']; ?> of content</span>
                        </div>
                        <div class="course-feature">
                            <i class="bi bi-file-earmark-text"></i>
                            <span>Downloadable resources</span>
                        </div>
                        <div class="course-feature">
                            <i class="bi bi-phone"></i>
                            <span>Access on mobile and TV</span>
                        </div>
                        <div class="course-feature">
                            <i class="bi bi-infinity"></i>
                            <span>Full lifetime access</span>
                        </div>
                        <div class="course-feature">
                            <i class="bi bi-trophy"></i>
                            <span>Certificate of completion</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div style="height: 60px;"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://js.paystack.co/v1/inline.js"></script>
    <script>
        // Show payment notification alerts
        const urlParams = new URLSearchParams(window.location.search);
        const payment = urlParams.get('payment');
        const message = urlParams.get('message');

        if (payment && message) {
            showAlert(decodeURIComponent(message), payment === 'success' ? 'success' : 'danger');
            // Remove query params
            window.history.replaceState({}, document.title, window.location.pathname + window.location.search.replace(/[?&](payment|message)=[^&]*/g, '').replace(/^&/, '?'));
        }

        function showAlert(message, type = 'info') {
            const alertHtml = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            document.getElementById('alertContainer').insertAdjacentHTML('beforeend', alertHtml);
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                const alert = document.querySelector('.alert');
                if (alert) {
                    alert.remove();
                }
            }, 5000);
        }

        // Handle course enrollment with Paystack
        const enrollBtn = document.getElementById('enrollBtn');
        if (enrollBtn) {
            enrollBtn.addEventListener('click', function(e) {
                e.preventDefault();
                
                const courseId = this.getAttribute('data-course-id');
                const courseSlug = this.getAttribute('data-course-slug');
                const price = parseFloat(this.getAttribute('data-price'));
                const isFree = this.getAttribute('data-is-free') === '1';
                
                console.log('Enrolling in course:', { courseId, courseSlug, price, isFree });
                
                // Disable button
                this.disabled = true;
                this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
                
                // Make checkout request
                fetch('<?php echo Url::base(); ?>/student/checkout', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `course_id=${courseId}`
                })
                .then(response => {
                    // Check if response is ok
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    // Check if response is JSON
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        throw new Error('Response is not JSON');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        if (data.free_course) {
                            // Free course - redirect directly
                            showAlert(data.message, 'success');
                            setTimeout(() => {
                                window.location.href = data.redirect;
                            }, 1000);
                        } else if (data.payment_required) {
                            // Paid course - initialize Paystack
                            const handler = PaystackPop.setup({
                                key: '<?php echo $paystackPublicKey; ?>',
                                email: '<?php echo $user['email']; ?>',
                                amount: price * 100, // Convert to kobo
                                currency: 'NGN',
                                ref: data.reference,
                                callback: function(response) {
                                    // Payment successful
                                    showAlert('Payment successful! Redirecting...', 'success');
                                    window.location.href = 'payment-callback.php?reference=' + response.reference;
                                },
                                onClose: function() {
                                    // User closed payment modal
                                    showAlert('Payment cancelled', 'warning');
                                    // Re-enable button
                                    enrollBtn.disabled = false;
                                    enrollBtn.innerHTML = '<i class="bi bi-cart-plus me-2"></i>Enroll Now';
                                }
                            });
                            handler.openIframe();
                        }
                    } else {
                        showAlert(data.message, 'danger');
                        // Re-enable button
                        this.disabled = false;
                        this.innerHTML = '<i class="bi bi-cart-plus me-2"></i>Enroll Now';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('An error occurred. Please try again.', 'danger');
                    // Re-enable button
                    this.disabled = false;
                    this.innerHTML = '<i class="bi bi-cart-plus me-2"></i>Enroll Now';
                });
            });
        }
    </script>
</body>
</html>
