<?php
/**
 * GrowthEngineAI LMS - Student Dashboard
 */

require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Course.php';
require_once __DIR__ . '/../classes/Url.php';

$auth = new Auth();
$auth->requireRole('student');

$user = $auth->getCurrentUser();
$courseModel = new Course();

// Get user statistics
$stats = $courseModel->getUserCourseStats($user['id']);

// Get enrolled courses for continue learning section
$enrolledCourses = $courseModel->getEnrolledCourses($user['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - GrowthEngineAI</title>
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
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        .stat-card {
            padding: 24px;
        }
        .stat-card .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }
        .stat-card .stat-icon.bg-primary { background: linear-gradient(135deg, #000016 0%, #00212d 100%); }
        .stat-card .stat-icon.bg-success { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        .stat-card .stat-icon.bg-warning { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
        .stat-card .stat-icon.bg-info { background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%); }
        .stat-number {
            font-size: 32px;
            font-weight: 700;
            color: #1e293b;
        }
        .stat-label {
            color: #64748b;
            font-size: 14px;
        }
        .progress {
            height: 8px;
            border-radius: 4px;
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
                    <h1>Student Dashboard</h1>
                    <p class="mb-0">Welcome back, <?php echo htmlspecialchars($user['first_name']); ?>! Ready to learn something new?</p>
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
                        <a class="nav-link active" href="<?php echo Url::dashboard(); ?>">
                            <i class="bi bi-grid-1x2"></i> Dashboard
                        </a>
                        <a class="nav-link" href="<?php echo Url::courses(); ?>">
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
                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="d-flex align-items-center gap-3">
                                <div class="stat-icon bg-primary">
                                    <i class="bi bi-book"></i>
                                </div>
                                <div>
                                    <div class="stat-number"><?php echo $stats['enrolled_courses']; ?></div>
                                    <div class="stat-label">Enrolled Courses</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="d-flex align-items-center gap-3">
                                <div class="stat-icon bg-success">
                                    <i class="bi bi-check-circle"></i>
                                </div>
                                <div>
                                    <div class="stat-number"><?php echo $stats['completed_lessons']; ?></div>
                                    <div class="stat-label">Lessons Completed</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="d-flex align-items-center gap-3">
                                <div class="stat-icon bg-warning">
                                    <i class="bi bi-trophy"></i>
                                </div>
                                <div>
                                    <div class="stat-number"><?php echo $stats['certificates']; ?></div>
                                    <div class="stat-label">Certificates</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="d-flex align-items-center gap-3">
                                <div class="stat-icon bg-info">
                                    <i class="bi bi-clock"></i>
                                </div>
                                <div>
                                    <div class="stat-number"><?php echo $stats['completed_courses']; ?></div>
                                    <div class="stat-label">Courses Completed</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-md-8">
                        <div class="card p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0"><i class="bi bi-play-circle me-2"></i>Continue Learning</h5>
                                <a href="<?php echo Url::courses(); ?>" class="btn btn-sm btn-outline-primary">View All</a>
                            </div>
                            <?php if (empty($enrolledCourses)): ?>
                            <p class="text-muted text-center py-5">You haven't enrolled in any courses yet. <br>Browse our catalog to find courses that interest you!</p>
                            <div class="text-center">
                                <a href="<?php echo Url::courses(); ?>" class="btn btn-primary" style="background: linear-gradient(135deg, #000016 0%, #00212d 100%); border: none;">
                                    <i class="bi bi-search me-1"></i> Browse Courses
                                </a>
                            </div>
                            <?php else: ?>
                            <div class="continue-learning-list">
                                <?php foreach (array_slice($enrolledCourses, 0, 3) as $course): ?>
                                <?php $courseLearnUrl = Url::learn($course['slug'] ?? Url::slugify($course['title'])); ?>
                                <div class="continue-course-item d-flex gap-3 p-3 mb-2 rounded" style="background: #f8fafc;">
                                    <img src="<?php echo htmlspecialchars($course['thumbnail'] ?: 'https://images.unsplash.com/photo-1677442136019-21780ecad995?w=100&h=60&fit=crop'); ?>" 
                                         alt="<?php echo htmlspecialchars($course['title']); ?>" 
                                         style="width: 100px; height: 60px; object-fit: cover; border-radius: 8px;">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($course['title']); ?></h6>
                                        <div class="d-flex align-items-center gap-3 mb-2">
                                            <small class="text-muted"><?php echo htmlspecialchars($course['instructor_name']); ?></small>
                                            <small class="text-muted"><?php echo $course['completed_lessons'] ?? 0; ?>/<?php echo $course['total_lessons']; ?> lessons</small>
                                        </div>
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar" style="width: <?php echo $course['progress'] ?? 0; ?>%; background: linear-gradient(135deg, #000016 0%, #00212d 100%);"></div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <a href="<?php echo $courseLearnUrl; ?>" class="btn btn-sm btn-primary" style="background: linear-gradient(135deg, #000016 0%, #00212d 100%); border: none;">
                                            <i class="bi bi-play-fill"></i> Continue
                                        </a>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card p-4 mb-4">
                            <h5 class="mb-3"><i class="bi bi-lightning me-2"></i>Quick Actions</h5>
                            <div class="d-grid gap-2">
                                <a href="<?php echo Url::courses(); ?>" class="btn btn-outline-primary">
                                    <i class="bi bi-search me-1"></i> Browse Courses
                                </a>
                                <a href="<?php echo Url::community(); ?>" class="btn btn-outline-primary">
                                    <i class="bi bi-slack me-1"></i> Join Slack Community
                                </a>
                                <a href="<?php echo Url::settings(); ?>" class="btn btn-outline-primary">
                                    <i class="bi bi-person-circle me-1"></i> Edit Profile
                                </a>
                            </div>
                        </div>
                        <div class="card p-4">
                            <h5 class="mb-3"><i class="bi bi-graph-up me-2"></i>Your Progress</h5>
                            <div class="text-center py-3">
                                <p class="text-muted mb-0">Start a course to track your progress</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('sidebarOverlay').classList.toggle('active');
            document.body.style.overflow = document.getElementById('sidebar').classList.contains('active') ? 'hidden' : '';
        }
    </script>
</body>
</html>
