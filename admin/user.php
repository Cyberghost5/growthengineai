<?php
/**
 * GrowthEngineAI LMS - Admin User Detail
 */

require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/User.php';

$auth = new Auth();
$auth->requireRole('admin');

$currentAdmin = $auth->getCurrentUser();
$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($userId <= 0) {
    header('Location: users.php');
    exit;
}

$userModel = new User();
$detail = $userModel->getUserDetail($userId);

if (!$detail) {
    header('Location: users.php?error=not_found');
    exit;
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ((int)$currentAdmin['id'] === $userId && $action === 'update_user') {
        $errors[] = 'You cannot change your own role or status.';
    } else {
        if ($action === 'update_user') {
            $newRole = trim($_POST['role'] ?? '');
            $newStatus = trim($_POST['status'] ?? '');

            $update = $userModel->updateRoleStatus($userId, $newRole, $newStatus);
            if ($update['success']) {
                $userModel->logActivity(
                    $currentAdmin['id'],
                    'admin_user_update',
                    'Updated user ' . $detail['user']['email'] . ' role/status.'
                );
                $success = 'User updated successfully.';
            } else {
                $errors[] = $update['message'] ?? 'Failed to update user.';
            }
        }

        if ($action === 'reset_password') {
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if ($newPassword === '' || $confirmPassword === '') {
                $errors[] = 'Password fields are required.';
            } elseif ($newPassword !== $confirmPassword) {
                $errors[] = 'Passwords do not match.';
            } else {
                $reset = $userModel->setPassword($userId, $newPassword);
                if ($reset['success']) {
                    $userModel->logActivity(
                        $currentAdmin['id'],
                        'admin_password_reset',
                        'Reset password for user ' . $detail['user']['email'] . '.'
                    );
                    $success = 'Password reset successfully.';
                } else {
                    $errors[] = $reset['message'] ?? 'Failed to reset password.';
                }
            }
        }
    }

    $detail = $userModel->getUserDetail($userId);
    $user = $detail['user'];
    $latestSession = $detail['latest_session'];
    $enrollments = $detail['enrollments'];
    $activityLogs = $detail['activity_logs'];
}

$user = $detail['user'];
$latestSession = $detail['latest_session'];
$enrollments = $detail['enrollments'];
$activityLogs = $detail['activity_logs'];

function formatDateTime($value) {
    if (!$value) {
        return '—';
    }
    $timestamp = strtotime($value);
    if (!$timestamp) {
        return '—';
    }
    return date('M j, Y g:i A', $timestamp);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Details - GrowthEngineAI Admin</title>
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
        .badge-role {
            background: #e2e8f0;
            color: #334155;
        }
        .badge-status {
            text-transform: capitalize;
        }
        .badge-status.active { background: #dcfce7; color: #166534; }
        .badge-status.pending { background: #fef3c7; color: #92400e; }
        .badge-status.inactive { background: #e2e8f0; color: #475569; }
        .badge-status.suspended { background: #fee2e2; color: #b91c1c; }
        .meta-label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #94a3b8;
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
                    <h1>Admin Dashboard</h1>
                    <p class="mb-0">User details</p>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="badge bg-light px-3 py-2" style="color: #000016 !important;">
                        <i class="bi bi-person-badge me-1"></i> Admin
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid px-4">
        <div class="row g-4">
            <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
            <div class="sidebar" id="sidebar">
                <div class="sidebar-header">
                    <span class="sidebar-logo"><img src="../images/logo_ge.png" alt="" width="150px"></span>
                    <button class="sidebar-close" onclick="toggleSidebar()">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
                <div class="sidebar-nav">
                    <nav class="nav flex-column">
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-grid-1x2"></i> Dashboard
                        </a>
                        <a class="nav-link" href="categories.php">
                            <i class="bi bi-folder"></i> Manage Categories
                        </a>
                        <a class="nav-link" href="courses.php">
                            <i class="bi bi-book"></i> Manage Courses
                        </a>
                        <a class="nav-link active" href="users.php">
                            <i class="bi bi-people"></i> Users
                        </a>
                        <a class="nav-link" href="transactions.php">
                            <i class="bi bi-receipt"></i> Transactions
                        </a>
                        <a class="nav-link" href="settings.php">
                            <i class="bi bi-gear"></i> Settings
                        </a>
                    </nav>
                    <div class="sidebar-footer">
                        <nav class="nav flex-column">
                            <a class="nav-link" href="../auth/logout.php">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a>
                        </nav>
                    </div>
                </div>
            </div>

            <div class="col-lg-11 mx-auto">
                <div class="card p-4 mb-4">
                    <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
                        <div>
                            <h2 class="mb-1"><?php echo htmlspecialchars(trim($user['first_name'] . ' ' . $user['last_name'])); ?></h2>
                            <div class="text-muted"><?php echo htmlspecialchars($user['email']); ?></div>
                            <div class="text-muted mt-1"><?php echo $user['phone'] ? htmlspecialchars($user['phone']) : '—'; ?></div>
                        </div>
                        <a href="users.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Back to Users
                        </a>
                    </div>

                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $error): ?>
                                <div><?php echo htmlspecialchars($error); ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="meta-label mb-1">Role</div>
                                <span class="badge badge-role"><?php echo htmlspecialchars($user['role']); ?></span>
                                <div class="meta-label mt-3 mb-1">Status</div>
                                <span class="badge badge-status <?php echo htmlspecialchars($user['status']); ?>">
                                    <?php echo htmlspecialchars($user['status']); ?>
                                </span>
                                <div class="meta-label mt-3 mb-1">Registered</div>
                                <div><?php echo formatDateTime($user['created_at']); ?></div>
                                <div class="meta-label mt-3 mb-1">Last Login</div>
                                <div><?php echo formatDateTime($user['last_login']); ?></div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="meta-label mb-1">Latest Session</div>
                                <?php if ($latestSession): ?>
                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <div class="text-muted small">IP Address</div>
                                            <div><?php echo htmlspecialchars($latestSession['ip_address'] ?? '—'); ?></div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="text-muted small">Session Started</div>
                                            <div><?php echo formatDateTime($latestSession['created_at'] ?? null); ?></div>
                                        </div>
                                        <div class="col-12 mt-2">
                                            <div class="text-muted small">User Agent</div>
                                            <div class="small"><?php echo htmlspecialchars($latestSession['user_agent'] ?? '—'); ?></div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="text-muted">No sessions recorded.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card p-4 mb-4">
                    <h4 class="mb-3">Admin Actions</h4>
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <form method="POST" class="border rounded-3 p-3 h-100">
                                <input type="hidden" name="action" value="update_user">
                                <h6 class="mb-3">Update Role & Status</h6>
                                <div class="mb-3">
                                    <label class="form-label">Role</label>
                                    <select name="role" class="form-select" required>
                                        <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                        <option value="student" <?php echo $user['role'] === 'student' ? 'selected' : ''; ?>>Student</option>
                                        <option value="tutor" <?php echo $user['role'] === 'tutor' ? 'selected' : ''; ?>>Tutor</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-select" required>
                                        <option value="active" <?php echo $user['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="pending" <?php echo $user['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="inactive" <?php echo $user['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        <option value="suspended" <?php echo $user['status'] === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle me-1"></i> Save Changes
                                </button>
                                <?php if ((int)$currentAdmin['id'] === $userId): ?>
                                    <div class="text-muted small mt-2">You cannot change your own role or status.</div>
                                <?php endif; ?>
                            </form>
                        </div>
                        <div class="col-lg-6">
                            <form method="POST" class="border rounded-3 p-3 h-100">
                                <input type="hidden" name="action" value="reset_password">
                                <h6 class="mb-3">Reset Password</h6>
                                <div class="mb-3">
                                    <label class="form-label">New Password</label>
                                    <input type="password" name="new_password" class="form-control" minlength="8" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Confirm Password</label>
                                    <input type="password" name="confirm_password" class="form-control" minlength="8" required>
                                </div>
                                <button type="submit" class="btn btn-outline-primary">
                                    <i class="bi bi-key me-1"></i> Reset Password
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="card p-4">
                    <h4 class="mb-3">Enrollments</h4>
                    <?php if (empty($enrollments)): ?>
                        <div class="text-muted text-center py-4">No enrollments found.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Course</th>
                                        <th>Enrolled At</th>
                                        <th>Status</th>
                                        <th>Progress</th>
                                        <th>Amount Paid</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($enrollments as $enrollment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($enrollment['course_title']); ?></td>
                                        <td><?php echo formatDateTime($enrollment['enrolled_at']); ?></td>
                                        <td class="text-capitalize"><?php echo htmlspecialchars($enrollment['status']); ?></td>
                                        <td><?php echo number_format((float)$enrollment['progress_percent'], 2); ?>%</td>
                                        <td><?php echo number_format((float)$enrollment['amount_paid'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="card p-4 mt-4">
                    <h4 class="mb-3">Activity Logs</h4>
                    <?php if (empty($activityLogs)): ?>
                        <div class="text-muted text-center py-4">No activity logs found.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Action</th>
                                        <th>Description</th>
                                        <th>IP Address</th>
                                        <th>When</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($activityLogs as $log): ?>
                                    <tr>
                                        <td class="text-capitalize"><?php echo htmlspecialchars(str_replace('_', ' ', $log['action'])); ?></td>
                                        <td><?php echo $log['description'] ? htmlspecialchars($log['description']) : '—'; ?></td>
                                        <td><?php echo $log['ip_address'] ? htmlspecialchars($log['ip_address']) : '—'; ?></td>
                                        <td><?php echo formatDateTime($log['created_at']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
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
