<?php
/**
 * GrowthEngineAI LMS - Student Courses
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

// Get enrolled courses from database
$enrolledCoursesRaw = $courseModel->getEnrolledCourses($user['id']);
$enrolledCourses = [];
foreach ($enrolledCoursesRaw as $course) {
    $enrolledCourses[] = [
        'id' => $course['id'],
        'slug' => $course['slug'] ?? Url::slugify($course['title']),
        'title' => $course['title'],
        'instructor' => $course['instructor_name'],
        'progress' => $course['progress'] ?? 0,
        'image' => $course['thumbnail'] ?: 'https://images.unsplash.com/photo-1677442136019-21780ecad995?w=400&h=250&fit=crop',
        'lessons_completed' => $course['completed_lessons'] ?? 0,
        'total_lessons' => $course['total_lessons'] ?? 0,
        'category' => $course['category_name'],
        'next_lesson' => $course['next_lesson'] ?? 'Start learning'
    ];
}

// Get enrolled course IDs for filtering
$enrolledCourseIds = array_column($enrolledCoursesRaw, 'id');

// Get available courses from database (excluding enrolled ones)
$availableCoursesRaw = $courseModel->getAllCourses();
$availableCourses = [];
foreach ($availableCoursesRaw as $course) {
    // Skip courses that user is already enrolled in
    if (in_array($course['id'], $enrolledCourseIds)) {
        continue;
    }
    
    $price = $course['is_free'] ? 0 : ($course['sale_price'] > 0 ? $course['sale_price'] : $course['price']);
    
    $availableCourses[] = [
        'id' => $course['id'],
        'slug' => $course['slug'] ?? Url::slugify($course['title']),
        'category_slug' => $course['category_slug'] ?? Url::slugify($course['category_name']),
        'title' => $course['title'],
        'instructor' => $course['instructor_name'],
        'description' => $course['description'] ? substr($course['description'], 0, 150) . '...' : '',
        'image' => $course['thumbnail'] ?: 'https://images.unsplash.com/photo-1677442136019-21780ecad995?w=400&h=250&fit=crop',
        'duration' => $course['duration_hours'] . ' hours',
        'lessons' => $course['total_lessons'] ?? 0,
        'level' => ucfirst($course['level']),
        'category' => $course['category_name'],
        'rating' => $course['average_rating'] ?? 0,
        'students' => $course['total_enrollments'] ?? 0,
        'is_free' => $course['is_free'],
        'price' => $price,
        'original_price' => $course['price'],
        'sale_price' => $course['sale_price']
    ];
}

// Get categories from database
$categoriesRaw = $courseModel->getCategories();
$categories = ['All'];
foreach ($categoriesRaw as $cat) {
    $categories[] = $cat['name'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses - GrowthEngineAI</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../images/favicon.png" rel="icon">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: #f1f5f9;
        }
        .dashboard-header {
            background: linear-gradient(135deg, #000016 0%, #00212d 100%);
            color: white;
            padding: 30px;
            margin-bottom: 30px;
        }
        .welcome-text h1 {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
        }
        /* Sidebar Toggle Button */
        .sidebar-toggle-btn {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1001;
            width: 45px;
            height: 45px;
            border-radius: 10px;
            background: white;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.15);
            color: #000016;
            font-size: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .sidebar-toggle-btn:hover {
            background: linear-gradient(135deg, #000016 0%, #00212d 100%);
            color: white;
        }
        /* Sidebar Overlay */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1002;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        .sidebar-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: -280px;
            width: 280px;
            height: 100vh;
            background: white;
            box-shadow: 2px 0 20px rgba(0,0,0,0.1);
            padding: 20px;
            z-index: 1003;
            transition: left 0.3s ease;
            overflow-y: auto;
        }
        .sidebar.active {
            left: 0;
        }
        .sidebar-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 20px;
            margin-bottom: 20px;
            border-bottom: 1px solid #e2e8f0;
        }
        .sidebar-logo {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            font-size: 18px;
            color: #000016;
        }
        .sidebar-close {
            background: none;
            border: none;
            font-size: 24px;
            color: #64748b;
            cursor: pointer;
            padding: 0;
            line-height: 1;
        }
        .sidebar-close:hover {
            color: #000016;
        }
        .sidebar .nav-link {
            color: #64748b;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 4px;
            transition: all 0.3s ease;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: linear-gradient(135deg, #000016 0%, #00212d 100%);
            color: white;
        }
        .sidebar .nav-link i {
            width: 24px;
        }
        .sidebar-footer {
            margin-top: auto;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }
        .sidebar-footer .nav-link {
            color: #ef4444;
        }
        .sidebar-footer .nav-link:hover {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }
        .sidebar-nav {
            display: flex;
            flex-direction: column;
            height: calc(100% - 80px);
        }
        .sidebar-nav .nav {
            flex: 1;
        }
        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
            background: white;
        }
        .card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        .course-card {
            position: relative;
        }
        .course-card .image-wrapper {
            position: relative;
            overflow: hidden;
        }
        .course-card .course-image {
            height: 200px;
            object-fit: cover;
            width: 100%;
            transition: transform 0.5s ease;
        }
        .course-card:hover .course-image {
            transform: scale(1.08);
        }
        .course-card .image-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, transparent 40%, rgba(0,0,0,0.7) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .course-card:hover .image-overlay {
            opacity: 1;
        }
        .course-card .course-badges {
            position: absolute;
            top: 12px;
            left: 12px;
            right: 12px;
            display: flex;
            justify-content: space-between;
            z-index: 2;
        }
        .course-card .course-category {
            background: linear-gradient(135deg, #000016 0%, #00212d 100%);
            color: white;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        .course-card .course-level {
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        .course-card .course-level.beginner {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }
        .course-card .course-level.intermediate {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
        }
        .course-card .course-level.advanced {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }
        .course-card .course-level.all_levels {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            color: white;
        }
        .course-card .progress-badge {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        .course-card .card-body {
            padding: 24px;
        }
        .course-card .course-title {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            color: #1e293b;
            font-size: 17px;
            margin-bottom: 10px;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 48px;
        }
        .course-card .course-instructor {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #64748b;
            font-size: 13px;
            margin-bottom: 14px;
        }
        .course-card .course-instructor i {
            font-size: 16px;
            color: #000016;
        }
        .course-card .course-description {
            color: #64748b;
            font-size: 13px;
            line-height: 1.6;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            margin-bottom: 16px;
            min-height: 42px;
        }
        .course-card .course-meta {
            display: flex;
            gap: 20px;
            color: #64748b;
            font-size: 12px;
            margin-bottom: 16px;
            padding-bottom: 16px;
            border-bottom: 1px solid #f1f5f9;
        }
        .course-card .course-meta span {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .course-card .course-meta i {
            font-size: 14px;
            color: #94a3b8;
        }
        .course-card .course-rating {
            color: #f59e0b !important;
        }
        .course-card .course-rating i {
            color: #f59e0b !important;
        }
        .course-card .course-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .course-card .course-price {
            display: flex;
            flex-direction: column;
        }
        .course-card .price-current {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            font-size: 20px;
            color: #000016;
        }
        .course-card .price-original {
            font-size: 13px;
            color: #94a3b8;
            text-decoration: line-through;
        }
        .course-card .price-free {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            font-size: 18px;
            color: #10b981;
        }
        .course-card .students-count {
            font-size: 12px;
            color: #94a3b8;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .btn-enroll {
            background: linear-gradient(135deg, #000016 0%, #00212d 100%);
            border: none;
            color: white;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 15px rgba(0, 0, 22, 0.3);
        }
        .btn-enroll:hover {
            background: linear-gradient(135deg, #00212d 0%, #003344 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 22, 0.4);
        }
        .btn-enroll:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        .btn-outline-secondary {
            border-color: #cbd5e1;
            color: #64748b;
        }
        .btn-outline-secondary:hover {
            background: #f1f5f9;
            border-color: #94a3b8;
            color: #475569;
        }
        .btn-continue {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border: none;
            color: white;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }
        .btn-continue:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
        }
        .filter-tabs {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 24px;
        }
        .filter-tab {
            padding: 8px 20px;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            background: white;
            color: #64748b;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .filter-tab:hover,
        .filter-tab.active {
            background: linear-gradient(135deg, #000016 0%, #00212d 100%);
            color: white;
            border-color: transparent;
        }
        .search-box {
            position: relative;
        }
        .search-box input {
            padding-left: 45px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            height: 48px;
        }
        .search-box input:focus {
            border-color: #000016;
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
        }
        .search-box i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }
        .progress {
            height: 6px;
            border-radius: 3px;
            background: #e2e8f0;
        }
        .progress-bar {
            background: linear-gradient(135deg, #000016 0%, #00212d 100%);
        }
        .enrolled-card {
            display: flex;
            gap: 20px;
            padding: 20px;
        }
        .enrolled-card .course-thumb {
            width: 200px;
            height: 130px;
            object-fit: cover;
            border-radius: 10px;
            flex-shrink: 0;
        }
        .enrolled-card .course-info {
            flex: 1;
        }
        .section-title {
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 20px;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        .empty-state i {
            font-size: 64px;
            color: #cbd5e1;
            margin-bottom: 20px;
        }
        .empty-state h5 {
            color: #64748b;
            margin-bottom: 10px;
        }
        .empty-state p {
            color: #94a3b8;
        }
        /* Toggle Switch Styles */
        .view-toggle {
            display: inline-flex;
            background: white;
            border-radius: 50px;
            padding: 5px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }
        .view-toggle .toggle-btn {
            padding: 12px 28px;
            border-radius: 50px;
            border: none;
            background: transparent;
            color: #64748b;
            font-weight: 500;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .view-toggle .toggle-btn.active {
            background: linear-gradient(135deg, #000016 0%, #00212d 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(139, 92, 246, 0.4);
        }
        .view-toggle .toggle-btn:hover:not(.active) {
            color: #000016;
        }
        .course-section {
            display: none;
        }
        .course-section.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="dashboard-header">
        <div class="container-fluid px-4">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <button class="sidebar-toggle-btn" onclick="toggleSidebar()">
                        <i class="bi bi-list"></i>
                    </button>
                </div>
                <div class="welcome-text">
                    <h1><i class="bi bi-book me-2"></i>Courses</h1>
                    <p class="mb-0">Explore our courses and continue your learning journey</p>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="badge bg-light text-purple px-3 py-2" style="color: #000016 !important;">
                        <i class="bi bi-mortarboard me-1"></i> Student
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid px-4">
        <!-- Alert Container -->
        <div id="alertContainer"></div>
        
        <div class="row g-4">
            <!-- Sidebar Overlay -->
            <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
            
            <!-- Sidebar -->
            <div class="sidebar" id="sidebar">
                <div class="sidebar-header">
                    <span class="sidebar-logo"><img src="../images/logo_ge.png" alt="" width="150px"></span>
                    <button class="sidebar-close" onclick="toggleSidebar()">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
                <div class="sidebar-nav">
                    <nav class="nav flex-column">
                        <a class="nav-link" href="<?php echo Url::dashboard(); ?>">
                            <i class="bi bi-grid-1x2"></i> Dashboard
                        </a>
                        <a class="nav-link active" href="<?php echo Url::courses(); ?>">
                            <i class="bi bi-book"></i> My Courses
                        </a>
                        <a class="nav-link" href="<?php echo Url::transactions(); ?>">
                            <i class="bi bi-receipt"></i> Transactions
                        </a>
                        <a class="nav-link" href="<?php echo Url::community(); ?>">
                            <i class="bi bi-people"></i> Community
                        </a>
                        <a class="nav-link" href="<?php echo Url::settings(); ?>">
                            <i class="bi bi-gear"></i> Settings
                        </a>
                    </nav>
                    <div class="sidebar-footer">
                        <nav class="nav flex-column">
                            <a class="nav-link" href="<?php echo Url::logout(); ?>">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a>
                        </nav>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-lg-12">
                <!-- Toggle Switch -->
                <div class="text-center">
                    <div class="view-toggle">
                        <button class="toggle-btn active" data-target="enrolled-section">
                            <i class="bi bi-collection-play me-2"></i>My Learning
                        </button>
                        <button class="toggle-btn" data-target="available-section">
                            <i class="bi bi-grid me-2"></i>Browse Courses
                        </button>
                    </div>
                </div>

                <!-- My Enrolled Courses -->
                <div class="course-section active" id="enrolled-section">
                    <h4 class="section-title"><i class="bi bi-collection-play me-2"></i>My Enrolled Courses</h4>
                    
                    <?php if (empty($enrolledCourses)): ?>
                    <div class="card">
                        <div class="empty-state">
                            <i class="bi bi-journal-bookmark"></i>
                            <h5>No Enrolled Courses Yet</h5>
                            <p>Browse the available courses below and start your learning journey!</p>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="row g-4">
                        <?php foreach ($enrolledCourses as $course): ?>
                        <div class="col-xl-4 col-lg-6">
                            <div class="card course-card h-100">
                                <div class="image-wrapper">
                                    <img src="<?php echo htmlspecialchars($course['image']); ?>" alt="<?php echo htmlspecialchars($course['title']); ?>" class="course-image">
                                    <div class="image-overlay"></div>
                                    <div class="course-badges">
                                        <span class="course-category"><?php echo htmlspecialchars($course['category']); ?></span>
                                        <span class="progress-badge">
                                            <i class="bi bi-pie-chart-fill me-1"></i><?php echo $course['progress']; ?>%
                                        </span>
                                    </div>
                                </div>
                                <div class="card-body d-flex flex-column">
                                    <h6 class="course-title"><?php echo htmlspecialchars($course['title']); ?></h6>
                                    <div class="course-instructor">
                                        <i class="bi bi-person-circle"></i>
                                        <span><?php echo htmlspecialchars($course['instructor']); ?></span>
                                    </div>
                                    <div class="course-meta">
                                        <span><i class="bi bi-collection-play"></i><?php echo $course['lessons_completed']; ?>/<?php echo $course['total_lessons']; ?> lessons</span>
                                    </div>
                                    <div class="mb-3 flex-grow-1">
                                        <div class="d-flex justify-content-between mb-2">
                                            <small class="text-muted fw-medium">Progress</small>
                                            <small class="fw-bold" style="color: #10b981;"><?php echo $course['progress']; ?>%</small>
                                        </div>
                                        <div class="progress" style="height: 8px; border-radius: 4px;">
                                            <div class="progress-bar" style="width: <?php echo $course['progress']; ?>%; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 4px;"></div>
                                        </div>
                                        <p class="text-muted small mt-2 mb-0">
                                            <i class="bi bi-arrow-right-circle me-1"></i>Next: <?php echo htmlspecialchars($course['next_lesson']); ?>
                                        </p>
                                    </div>
                                    <a href="<?php echo Url::learn($course['slug']); ?>" class="btn btn-continue w-100">
                                        <i class="bi bi-play-fill me-1"></i>Continue Learning
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Available Courses -->
                <div class="course-section" id="available-section">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="section-title mb-0"><i class="bi bi-grid me-2"></i>Browse Available Courses</h4>
                        <div class="search-box" style="width: 300px;">
                            <i class="bi bi-search"></i>
                            <input type="text" class="form-control" placeholder="Search courses..." id="courseSearch">
                        </div>
                    </div>

                    <!-- Filter Tabs -->
                    <div class="filter-tabs">
                        <?php foreach ($categories as $category): ?>
                        <button class="filter-tab <?php echo $category === 'All' ? 'active' : ''; ?>" data-category="<?php echo $category; ?>">
                            <?php echo $category; ?>
                        </button>
                        <?php endforeach; ?>
                    </div>

                    <!-- Course Grid -->
                    <div class="row g-4" id="courseGrid">
                        <?php foreach ($availableCourses as $course): ?>
                        <div class="col-xl-4 col-lg-6" data-category="<?php echo htmlspecialchars($course['category']); ?>">
                            <div class="card course-card h-100">
                                <div class="image-wrapper">
                                    <img src="<?php echo htmlspecialchars($course['image']); ?>" alt="<?php echo htmlspecialchars($course['title']); ?>" class="course-image">
                                    <div class="image-overlay"></div>
                                    <div class="course-badges">
                                        <span class="course-category"><?php echo htmlspecialchars($course['category']); ?></span>
                                        <span class="course-level <?php echo strtolower(str_replace(' ', '_', $course['level'])); ?>"><?php echo htmlspecialchars($course['level']); ?></span>
                                    </div>
                                </div>
                                <div class="card-body d-flex flex-column">
                                    <h6 class="course-title"><?php echo htmlspecialchars($course['title']); ?></h6>
                                    <div class="course-instructor">
                                        <i class="bi bi-person-circle"></i>
                                        <span><?php echo htmlspecialchars($course['instructor']); ?></span>
                                    </div>
                                    <p class="course-description"><?php echo htmlspecialchars($course['description']); ?></p>
                                    <div class="course-meta">
                                        <!-- <span><i class="bi bi-clock"></i><?php echo htmlspecialchars($course['duration']); ?></span> -->
                                        <span><i class="bi bi-collection-play"></i><?php echo $course['lessons']; ?> lessons</span>
                                        <span class="course-rating"><i class="bi bi-star-fill"></i><?php echo number_format($course['rating'], 1); ?></span>
                                    </div>
                                    <div class="course-footer mt-auto">
                                        <div class="course-price">
                                            <?php if ($course['is_free']): ?>
                                                <span class="price-free">FREE</span>
                                            <?php else: ?>
                                                <span class="price-current">₦<?php echo number_format($course['price'], 2); ?></span>
                                                <?php if ($course['sale_price'] > 0 && $course['sale_price'] < $course['original_price']): ?>
                                                    <span class="price-original">₦<?php echo number_format($course['original_price'], 2); ?></span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                            <span class="students-count"><i class="bi bi-people-fill"></i><?php echo $course['students']; ?> enrolled</span>
                                        </div>
                                        <a href="<?php echo Url::course($course['slug'], $course['category_slug']); ?>" class="btn btn-enroll"> 
                                            View Course
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show payment notification alerts
        const urlParams = new URLSearchParams(window.location.search);
        const payment = urlParams.get('payment');
        const message = urlParams.get('message');

        if (payment && message) {
            showAlert(decodeURIComponent(message), payment === 'success' ? 'success' : 'danger');
            // Remove query params
            window.history.replaceState({}, document.title, window.location.pathname);
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

        
        // Filter functionality
        document.querySelectorAll('.filter-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                // Update active state
                document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                const category = this.dataset.category;
                const courses = document.querySelectorAll('#courseGrid > div');
                
                courses.forEach(course => {
                    if (category === 'All' || course.dataset.category === category) {
                        course.style.display = '';
                    } else {
                        course.style.display = 'none';
                    }
                });
            });
        });

        // Search functionality
        document.getElementById('courseSearch').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const courses = document.querySelectorAll('#courseGrid > div');
            
            courses.forEach(course => {
                const title = course.querySelector('.course-title').textContent.toLowerCase();
                const instructor = course.querySelector('.course-instructor').textContent.toLowerCase();
                const category = course.dataset.category.toLowerCase();
                
                if (title.includes(searchTerm) || instructor.includes(searchTerm) || category.includes(searchTerm)) {
                    course.style.display = '';
                } else {
                    course.style.display = 'none';
                }
            });
        });

        // Toggle switch functionality
        document.querySelectorAll('.view-toggle .toggle-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                // Update active button
                document.querySelectorAll('.view-toggle .toggle-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                // Show/hide sections
                const targetId = this.dataset.target;
                document.querySelectorAll('.course-section').forEach(section => {
                    section.classList.remove('active');
                });
                document.getElementById(targetId).classList.add('active');
            });
        });

        // Sidebar toggle functionality
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('sidebarOverlay').classList.toggle('active');
            document.body.style.overflow = document.getElementById('sidebar').classList.contains('active') ? 'hidden' : '';
        }
    </script>
</body>
</html>
