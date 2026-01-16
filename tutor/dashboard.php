<?php
/**
 * GrowthEngineAI LMS - Tutor Dashboard
 */

require_once __DIR__ . '/../classes/Auth.php';

$auth = new Auth();
$auth->requireRole('tutor');

$user = $auth->getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tutor Dashboard - GrowthEngineAI</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: #f1f5f9;
        }
        .dashboard-header {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 30px;
            margin-bottom: 30px;
        }
        .welcome-text h1 {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
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
        .stat-card .stat-icon.bg-primary { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); }
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
    </style>
</head>
<body>
    <div class="dashboard-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div class="welcome-text">
                    <h1>Tutor Dashboard</h1>
                    <p class="mb-0">Welcome back, <?php echo htmlspecialchars($user['first_name']); ?>!</p>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="badge bg-light text-success px-3 py-2">
                        <i class="bi bi-person-video3 me-1"></i> Tutor
                    </span>
                    <a href="../auth/logout.php" class="btn btn-outline-light">
                        <i class="bi bi-box-arrow-right me-1"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon bg-primary">
                            <i class="bi bi-book"></i>
                        </div>
                        <div>
                            <div class="stat-number">0</div>
                            <div class="stat-label">My Courses</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon bg-success">
                            <i class="bi bi-people"></i>
                        </div>
                        <div>
                            <div class="stat-number">0</div>
                            <div class="stat-label">Total Students</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon bg-warning">
                            <i class="bi bi-star"></i>
                        </div>
                        <div>
                            <div class="stat-number">0.0</div>
                            <div class="stat-label">Average Rating</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon bg-info">
                            <i class="bi bi-currency-dollar"></i>
                        </div>
                        <div>
                            <div class="stat-number">₦0</div>
                            <div class="stat-label">Total Earnings</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-8">
                <div class="card p-4">
                    <h5 class="mb-3"><i class="bi bi-collection-play me-2"></i>My Courses</h5>
                    <p class="text-muted text-center py-5">You haven't created any courses yet. <br>Click the button below to get started!</p>
                    <div class="text-center">
                        <a href="#" class="btn btn-success">
                            <i class="bi bi-plus-circle me-1"></i> Create Your First Course
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-4">
                    <h5 class="mb-3"><i class="bi bi-lightning me-2"></i>Quick Actions</h5>
                    <div class="d-grid gap-2">
                        <a href="#" class="btn btn-outline-success">
                            <i class="bi bi-plus-circle me-1"></i> Create New Course
                        </a>
                        <a href="#" class="btn btn-outline-success">
                            <i class="bi bi-chat-dots me-1"></i> View Messages
                        </a>
                        <a href="#" class="btn btn-outline-success">
                            <i class="bi bi-person-circle me-1"></i> Edit Profile
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
