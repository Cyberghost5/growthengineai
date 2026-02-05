<?php
/**
 * GrowthEngineAI LMS - Admin Users
 * View users with enrollment summary and login info.
 */

require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/User.php';

$auth = new Auth();
$auth->requireRole('admin');

$userModel = new User();

$search = trim($_GET['search'] ?? '');
$role = trim($_GET['role'] ?? '');
$status = trim($_GET['status'] ?? '');
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = $page > 0 ? $page : 1;
$perPage = 20;

$result = $userModel->getUsers([
    'search' => $search,
    'role' => $role,
    'status' => $status,
    'page' => $page,
    'per_page' => $perPage
]);

$users = $result['users'];
$total = $result['total'];
$totalPages = $result['total_pages'];

// Export CSV when requested
if (isset($_GET['export']) && $_GET['export']) {
    $exportResult = $userModel->getUsers([
        'search' => $search,
        'role' => $role,
        'status' => $status,
        'page' => 1,
        'per_page' => 10000
    ]);
    $exportUsers = $exportResult['users'];

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=users_export_' . date('Ymd_His') . '.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID','First Name','Last Name','Email','Phone','Role','Status','Registered','Last Login','Enrollments','Latest Course','Last Enrolled']);
    foreach ($exportUsers as $row) {
        fputcsv($out, [
            $row['id'],
            $row['first_name'] ?? '',
            $row['last_name'] ?? '',
            $row['email'] ?? '',
            $row['phone'] ?? '',
            $row['role'] ?? '',
            $row['status'] ?? '',
            $row['created_at'] ? date('Y-m-d H:i:s', strtotime($row['created_at'])) : '',
            $row['last_login'] ? date('Y-m-d H:i:s', strtotime($row['last_login'])) : '',
            (int)($row['enrollments_count'] ?? 0),
            $row['latest_course_title'] ?? '',
            $row['last_enrolled_at'] ? date('Y-m-d H:i:s', strtotime($row['last_enrolled_at'])) : ''
        ]);
    }
    fclose($out);
    exit;
}

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

function buildQuery(array $base, array $overrides = []) {
    $params = array_merge($base, $overrides);
    $params = array_filter($params, function ($value) {
        return $value !== '' && $value !== null;
    });
    return http_build_query($params);
}

$baseQuery = [
    'search' => $search,
    'role' => $role,
    'status' => $status
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - GrowthEngineAI Admin</title>
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
        .table thead th {
            background: #f8fafc;
            color: #475569;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
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
                    <p class="mb-0">Manage all users and enrollments</p>
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
                    <div class="d-flex flex-wrap gap-3 align-items-center justify-content-between mb-3">
                        <div>
                            <h2 class="mb-1">Users</h2>
                            <p class="text-muted mb-0">Total users: <?php echo number_format($total); ?></p>
                        </div>
                        <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
                            <input type="text" name="search" class="form-control" placeholder="Search name or email" value="<?php echo htmlspecialchars($search); ?>">
                            <select name="role" class="form-select">
                                <option value="">All roles</option>
                                <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                <option value="student" <?php echo $role === 'student' ? 'selected' : ''; ?>>Student</option>
                                <option value="tutor" <?php echo $role === 'tutor' ? 'selected' : ''; ?>>Tutor</option>
                            </select>
                            <select name="status" class="form-select">
                                <option value="">All statuses</option>
                                <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                <option value="suspended" <?php echo $status === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                            </select>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search me-1"></i> Filter
                            </button>
                            <a href="users.php" class="btn btn-outline-secondary">Reset</a>
                            <a href="users.php?<?php echo buildQuery($baseQuery, ['export' => '1']); ?>" class="btn btn-success">
                                <i class="bi bi-download me-1"></i> Export
                            </a>
                        </form>
                    </div>

                    <?php if (empty($users)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-people fs-1 d-block mb-3"></i>
                            No users found.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Registered</th>
                                        <th>Last Login</th>
                                        <th>Enrollments</th>
                                        <th>Latest Course</th>
                                        <th>Last Enrolled</th>
                                        <th>Phone</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($users as $row): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?php echo htmlspecialchars(trim($row['first_name'] . ' ' . $row['last_name'])); ?></div>
                                            <div class="text-muted small"><?php echo htmlspecialchars($row['email']); ?></div>
                                        </td>
                                        <td>
                                            <span class="badge badge-role"><?php echo htmlspecialchars($row['role']); ?></span>
                                        </td>
                                        <td>
                                            <span class="badge badge-status <?php echo htmlspecialchars($row['status']); ?>">
                                                <?php echo htmlspecialchars($row['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo formatDateTime($row['created_at']); ?></td>
                                        <td><?php echo formatDateTime($row['last_login']); ?></td>
                                        <td><?php echo (int)$row['enrollments_count']; ?></td>
                                        <td><?php echo $row['latest_course_title'] ? htmlspecialchars($row['latest_course_title']) : '—'; ?></td>
                                        <td><?php echo formatDateTime($row['last_enrolled_at']); ?></td>
                                        <td><?php echo !empty($row['phone']) ? htmlspecialchars($row['phone']) : '—'; ?></td>
                                        <td>
                                            <a class="btn btn-sm btn-outline-primary" href="user.php?id=<?php echo (int)$row['id']; ?>">
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if ($totalPages > 1): ?>
                            <nav class="d-flex justify-content-between align-items-center mt-3">
                                <span class="text-muted small">
                                    Page <?php echo $page; ?> of <?php echo $totalPages; ?>
                                </span>
                                <div class="btn-group">
                                    <a class="btn btn-outline-secondary <?php echo $page <= 1 ? 'disabled' : ''; ?>"
                                       href="users.php?<?php echo buildQuery($baseQuery, ['page' => max(1, $page - 1)]); ?>">
                                        <i class="bi bi-chevron-left"></i> Prev
                                    </a>
                                    <a class="btn btn-outline-secondary <?php echo $page >= $totalPages ? 'disabled' : ''; ?>"
                                       href="users.php?<?php echo buildQuery($baseQuery, ['page' => min($totalPages, $page + 1)]); ?>">
                                        Next <i class="bi bi-chevron-right"></i>
                                    </a>
                                </div>
                            </nav>
                        <?php endif; ?>
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
