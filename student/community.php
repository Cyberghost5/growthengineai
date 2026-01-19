<?php
/**
 * GrowthEngineAI LMS - Student Community (Slack Integration)
 */

require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Url.php';

$auth = new Auth();
$auth->requireRole('student');

$user = $auth->getCurrentUser();

// Slack workspace configuration
$slackWorkspaceUrl = 'https://join.slack.com/t/growthengineaigroup/shared_invite/zt-3mde1tz0h-xtBvxaUbVwIYCipII1rnfA'; // Replace with your Slack invite link
$slackWorkspaceName = 'GrowthEngineAI Community';

// Community channels
$channels = [
    [
        'name' => '#general',
        'description' => 'General discussions, introductions, and community announcements',
        'icon' => 'bi-chat-dots',
        'members' => 1250
    ],
    [
        'name' => '#ai-discussion',
        'description' => 'Deep dive into AI concepts, news, and developments',
        'icon' => 'bi-robot',
        'members' => 890
    ],
    [
        'name' => '#machine-learning',
        'description' => 'ML algorithms, models, and implementation discussions',
        'icon' => 'bi-cpu',
        'members' => 720
    ],
    [
        'name' => '#python-help',
        'description' => 'Get help with Python programming and data science',
        'icon' => 'bi-code-slash',
        'members' => 950
    ],
    [
        'name' => '#career-advice',
        'description' => 'Job opportunities, resume tips, and career guidance',
        'icon' => 'bi-briefcase',
        'members' => 680
    ],
    [
        'name' => '#project-showcase',
        'description' => 'Share your projects and get feedback from the community',
        'icon' => 'bi-lightbulb',
        'members' => 540
    ]
];

// Community benefits
$benefits = [
    [
        'icon' => 'bi-people-fill',
        'title' => 'Connect with Peers',
        'description' => 'Network with fellow students and AI enthusiasts from around the world'
    ],
    [
        'icon' => 'bi-question-circle-fill',
        'title' => 'Get Help Fast',
        'description' => 'Ask questions and get answers from experienced community members'
    ],
    [
        'icon' => 'bi-calendar-event-fill',
        'title' => 'Exclusive Events',
        'description' => 'Access to webinars, AMAs, and virtual meetups with industry experts'
    ],
    [
        'icon' => 'bi-trophy-fill',
        'title' => 'Challenges & Competitions',
        'description' => 'Participate in coding challenges and win prizes'
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community - GrowthEngineAI</title>
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
        .slack-hero {
            background: linear-gradient(135deg, #4A154B 0%, #611f69 100%);
            color: white;
            border-radius: 16px;
            padding: 40px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }
        .slack-hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 400px;
            height: 400px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
        }
        .slack-hero::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -10%;
            width: 300px;
            height: 300px;
            background: rgba(255,255,255,0.03);
            border-radius: 50%;
        }
        .slack-hero h2 {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            font-size: 2rem;
            margin-bottom: 15px;
        }
        .slack-logo {
            width: 60px;
            height: 60px;
            margin-bottom: 20px;
        }
        .btn-slack {
            background: white;
            color: #4A154B;
            border: none;
            padding: 14px 32px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 16px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
        }
        .btn-slack:hover {
            background: #f8f9fa;
            color: #4A154B;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }
        .btn-slack svg {
            width: 24px;
            height: 24px;
        }
        .community-stats {
            display: flex;
            gap: 40px;
            margin-top: 30px;
        }
        .stat-item {
            text-align: center;
        }
        .stat-item .number {
            font-size: 28px;
            font-weight: 700;
        }
        .stat-item .label {
            font-size: 14px;
            opacity: 0.9;
        }
        .benefit-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            height: 100%;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .benefit-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }
        .benefit-card .icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            background: linear-gradient(135deg, #000016 0%, #00212d 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin: 0 auto 16px;
        }
        .benefit-card h5 {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 10px;
        }
        .benefit-card p {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 0;
        }
        .channel-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            transition: all 0.3s ease;
            cursor: pointer;
            border: 2px solid transparent;
        }
        .channel-card:hover {
            border-color: #000016;
            transform: translateX(5px);
        }
        .channel-card .channel-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #000016;
            flex-shrink: 0;
        }
        .channel-card .channel-info {
            flex: 1;
        }
        .channel-card .channel-name {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 4px;
        }
        .channel-card .channel-desc {
            color: #64748b;
            font-size: 13px;
            margin-bottom: 0;
        }
        .channel-card .channel-members {
            color: #94a3b8;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .section-title {
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 20px;
        }
        .activity-feed {
            background: white;
            border-radius: 12px;
            padding: 24px;
        }
        .activity-item {
            display: flex;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .activity-item:last-child {
            border-bottom: none;
        }
        .activity-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #000016 0%, #00212d 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
            flex-shrink: 0;
        }
        .activity-content {
            flex: 1;
        }
        .activity-content strong {
            color: #1e293b;
        }
        .activity-content p {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 4px;
        }
        .activity-time {
            color: #94a3b8;
            font-size: 12px;
        }
        .guidelines-card {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border-radius: 12px;
            padding: 24px;
            border-left: 4px solid #10b981;
        }
        .guidelines-card h5 {
            color: #166534;
            font-weight: 600;
            margin-bottom: 15px;
        }
        .guidelines-card ul {
            margin-bottom: 0;
            padding-left: 20px;
        }
        .guidelines-card li {
            color: #166534;
            margin-bottom: 8px;
            font-size: 14px;
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
                    <h1><i class="bi bi-people me-2"></i>Community</h1>
                    <p class="mb-0">Connect, collaborate, and grow with fellow learners</p>
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
                        <a class="nav-link" href="<?php echo Url::dashboard(); ?>">
                            <i class="bi bi-grid-1x2"></i> Dashboard
                        </a>
                        <a class="nav-link" href="<?php echo Url::courses(); ?>">
                            <i class="bi bi-book"></i> My Courses
                        </a>
                        <a class="nav-link active" href="<?php echo Url::community(); ?>">
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
                <!-- Slack Hero Section -->
                <div class="slack-hero">
                    <div class="row align-items-center">
                        <div class="col-lg-8">
                            <!-- Slack Logo SVG -->
                            <svg class="slack-logo" viewBox="0 0 127 127" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M27.2 80c0 7.3-5.9 13.2-13.2 13.2C6.7 93.2.8 87.3.8 80c0-7.3 5.9-13.2 13.2-13.2h13.2V80zm6.6 0c0-7.3 5.9-13.2 13.2-13.2 7.3 0 13.2 5.9 13.2 13.2v33c0 7.3-5.9 13.2-13.2 13.2-7.3 0-13.2-5.9-13.2-13.2V80z" fill="#E01E5A"/>
                                <path d="M47 27c-7.3 0-13.2-5.9-13.2-13.2C33.8 6.5 39.7.6 47 .6c7.3 0 13.2 5.9 13.2 13.2V27H47zm0 6.7c7.3 0 13.2 5.9 13.2 13.2 0 7.3-5.9 13.2-13.2 13.2H13.9C6.6 60.1.7 54.2.7 46.9c0-7.3 5.9-13.2 13.2-13.2H47z" fill="#36C5F0"/>
                                <path d="M99.9 46.9c0-7.3 5.9-13.2 13.2-13.2 7.3 0 13.2 5.9 13.2 13.2 0 7.3-5.9 13.2-13.2 13.2H99.9V46.9zm-6.6 0c0 7.3-5.9 13.2-13.2 13.2-7.3 0-13.2-5.9-13.2-13.2V13.8C66.9 6.5 72.8.6 80.1.6c7.3 0 13.2 5.9 13.2 13.2v33.1z" fill="#2EB67D"/>
                                <path d="M80.1 99.8c7.3 0 13.2 5.9 13.2 13.2 0 7.3-5.9 13.2-13.2 13.2-7.3 0-13.2-5.9-13.2-13.2V99.8h13.2zm0-6.6c-7.3 0-13.2-5.9-13.2-13.2 0-7.3 5.9-13.2 13.2-13.2h33.1c7.3 0 13.2 5.9 13.2 13.2 0 7.3-5.9 13.2-13.2 13.2H80.1z" fill="#ECB22E"/>
                            </svg>
                            <h2>Join Our Slack Community</h2>
                            <p class="mb-4" style="font-size: 18px; opacity: 0.9;">Connect with 2,500+ students, mentors, and AI professionals. Get help, share projects, and accelerate your learning journey.</p>
                            <a href="<?php echo htmlspecialchars($slackWorkspaceUrl); ?>" target="_blank" class="btn-slack" style="text-decoration: none;">
                                <svg viewBox="0 0 127 127" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M27.2 80c0 7.3-5.9 13.2-13.2 13.2C6.7 93.2.8 87.3.8 80c0-7.3 5.9-13.2 13.2-13.2h13.2V80zm6.6 0c0-7.3 5.9-13.2 13.2-13.2 7.3 0 13.2 5.9 13.2 13.2v33c0 7.3-5.9 13.2-13.2 13.2-7.3 0-13.2-5.9-13.2-13.2V80z" fill="#E01E5A"/>
                                    <path d="M47 27c-7.3 0-13.2-5.9-13.2-13.2C33.8 6.5 39.7.6 47 .6c7.3 0 13.2 5.9 13.2 13.2V27H47zm0 6.7c7.3 0 13.2 5.9 13.2 13.2 0 7.3-5.9 13.2-13.2 13.2H13.9C6.6 60.1.7 54.2.7 46.9c0-7.3 5.9-13.2 13.2-13.2H47z" fill="#36C5F0"/>
                                    <path d="M99.9 46.9c0-7.3 5.9-13.2 13.2-13.2 7.3 0 13.2 5.9 13.2 13.2 0 7.3-5.9 13.2-13.2 13.2H99.9V46.9zm-6.6 0c0 7.3-5.9 13.2-13.2 13.2-7.3 0-13.2-5.9-13.2-13.2V13.8C66.9 6.5 72.8.6 80.1.6c7.3 0 13.2 5.9 13.2 13.2v33.1z" fill="#2EB67D"/>
                                    <path d="M80.1 99.8c7.3 0 13.2 5.9 13.2 13.2 0 7.3-5.9 13.2-13.2 13.2-7.3 0-13.2-5.9-13.2-13.2V99.8h13.2zm0-6.6c-7.3 0-13.2-5.9-13.2-13.2 0-7.3 5.9-13.2 13.2-13.2h33.1c7.3 0 13.2 5.9 13.2 13.2 0 7.3-5.9 13.2-13.2 13.2H80.1z" fill="#ECB22E"/>
                                </svg>
                                Join Slack Workspace
                            </a>
                            <div class="community-stats">
                                <div class="stat-item">
                                    <div class="number">2,500+</div>
                                    <div class="label">Members</div>
                                </div>
                                <div class="stat-item">
                                    <div class="number">15+</div>
                                    <div class="label">Channels</div>
                                </div>
                                <div class="stat-item">
                                    <div class="number">24/7</div>
                                    <div class="label">Active</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 d-none d-lg-block text-center">
                            <img src="https://a.slack-edge.com/80588/marketing/img/icons/icon_slack_hash_colored.png" alt="Slack" style="width: 200px; opacity: 0.3;">
                        </div>
                    </div>
                </div>

                <!-- Benefits Section -->
                <h4 class="section-title"><i class="bi bi-stars me-2"></i>Why Join Our Community?</h4>
                <div class="row g-4 mb-4">
                    <?php foreach ($benefits as $benefit): ?>
                    <div class="col-md-6 col-lg-3">
                        <div class="benefit-card">
                            <div class="icon">
                                <i class="bi <?php echo $benefit['icon']; ?>"></i>
                            </div>
                            <h5><?php echo htmlspecialchars($benefit['title']); ?></h5>
                            <p><?php echo htmlspecialchars($benefit['description']); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
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
