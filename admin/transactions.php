<?php
/**
 * GrowthEngineAI LMS - Admin Transactions
 * List all transactions with filters and pagination
 */

require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../config/database.php';

$auth = new Auth();
$auth->requireRole('admin');

$db = getDB();

$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = $page > 0 ? $page : 1;
$perPage = 20;

// Build WHERE clause
$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(t.reference LIKE :search OR u.email LIKE :search OR CONCAT(u.first_name, ' ', u.last_name) LIKE :search OR c.title LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if ($status !== '') {
    $where[] = "t.status = :status";
    $params[':status'] = $status;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countSql = "SELECT COUNT(*) FROM transactions t LEFT JOIN users u ON t.user_id = u.id LEFT JOIN courses c ON t.course_id = c.id {$whereSql}";
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$offset = ($page - 1) * $perPage;

$sql = "SELECT t.*, CONCAT(u.first_name, ' ', u.last_name) AS user_name, u.email AS user_email, c.title AS course_title
        FROM transactions t
        LEFT JOIN users u ON t.user_id = u.id
        LEFT JOIN courses c ON t.course_id = c.id
        {$whereSql}
        ORDER BY t.created_at DESC
        LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$transactions = $stmt->fetchAll();
$totalPages = $perPage > 0 ? (int)ceil($total / $perPage) : 0;

function formatDateTime($value) {
    if (!$value) return '—';
    $ts = strtotime($value);
    if (!$ts) return '—';
    return date('M j, Y g:i A', $ts);
}

function buildQuery(array $base, array $overrides = []) {
    $params = array_merge($base, $overrides);
    $params = array_filter($params, function ($v) { return $v !== '' && $v !== null; });
    return http_build_query($params);
}

$baseQuery = [
    'search' => $search,
    'status' => $status
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions - GrowthEngineAI Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../images/favicon.png" rel="icon">
    <style>
        body { font-family: 'Roboto', sans-serif; background: #f1f5f9; }
        .dashboard-header { background: linear-gradient(135deg, #000016 0%, #00212d 100%); color: white; padding: 30px; margin-bottom: 30px; }
        .sidebar-toggle-btn { position: fixed; top: 20px; left: 20px; z-index: 1001; width: 45px; height: 45px; border-radius: 10px; background: white; border: none; box-shadow: 0 2px 10px rgba(0,0,0,0.15); color: #000016; font-size: 20px; cursor: pointer; transition: all 0.3s ease; }
        .sidebar-toggle-btn:hover { background: linear-gradient(135deg, #000016 0%, #00212d 100%); color: white; }
        .sidebar-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1002; opacity: 0; visibility: hidden; transition: all 0.3s ease; }
        .sidebar-overlay.active { opacity: 1; visibility: visible; }
        .sidebar { position: fixed; top: 0; left: -280px; width: 280px; height: 100vh; background: white; box-shadow: 2px 0 20px rgba(0,0,0,0.1); padding: 20px; z-index: 1003; transition: left 0.3s ease; overflow-y: auto; }
        .sidebar.active { left: 0; }
        .sidebar-header { display:flex; align-items:center; justify-content:space-between; padding-bottom:20px; margin-bottom:20px; border-bottom:1px solid #e2e8f0; }
        .sidebar-logo { font-family: 'Montserrat', sans-serif; font-weight:700; font-size:18px; color:#000016; }
        .card { border: none; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
        .table thead th { background: #f8fafc; }
        .badge-status { text-transform: capitalize; }
        .badge-status.completed { background: #dcfce7; color: #166534; }
        .badge-status.pending { background: #fef3c7; color: #92400e; }
        .badge-status.failed { background: #fee2e2; color: #b91c1c; }
    </style>
</head>
<body>
    <div class="dashboard-header">
        <div class="container-fluid px-4">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <button class="sidebar-toggle-btn" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
                </div>
                <div class="welcome-text">
                    <h1>Admin Dashboard</h1>
                    <p class="mb-0">Manage transactions</p>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="badge bg-light px-3 py-2" style="color: #000016 !important;"><i class="bi bi-person-badge me-1"></i> Admin</span>
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
                    <button class="sidebar-close" onclick="toggleSidebar()"><i class="bi bi-x"></i></button>
                </div>
                <div class="sidebar-nav">
                    <nav class="nav flex-column">
                        <a class="nav-link" href="dashboard.php"><i class="bi bi-grid-1x2"></i> Dashboard</a>
                        <a class="nav-link" href="categories.php"><i class="bi bi-folder"></i> Manage Categories</a>
                        <a class="nav-link" href="courses.php"><i class="bi bi-book"></i> Manage Courses</a>
                        <a class="nav-link" href="users.php"><i class="bi bi-people"></i> Users</a>
                        <a class="nav-link active" href="transactions.php"><i class="bi bi-receipt"></i> Transactions</a>
                        <a class="nav-link" href="settings.php"><i class="bi bi-gear"></i> Settings</a>
                    </nav>
                    <div class="sidebar-footer">
                        <nav class="nav flex-column">
                            <a class="nav-link" href="../auth/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                        </nav>
                    </div>
                </div>
            </div>

            <div class="col-lg-11 mx-auto">
                <div class="card p-4 mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h2 class="mb-0">Transactions</h2>
                            <p class="text-muted mb-0">Total: <?php echo number_format($total); ?></p>
                        </div>
                        <div>
                            <a href="users.php" class="btn btn-outline-secondary">Back</a>
                        </div>
                    </div>

                    <form method="GET" class="d-flex flex-wrap gap-2 align-items-center mb-3">
                        <input type="text" name="search" class="form-control" placeholder="Search reference, user, email, course" value="<?php echo htmlspecialchars($search); ?>">
                        <select name="status" class="form-select">
                            <option value="">All statuses</option>
                            <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="failed" <?php echo $status === 'failed' ? 'selected' : ''; ?>>Failed</option>
                        </select>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i> Filter</button>
                        <a href="transactions.php" class="btn btn-outline-secondary">Reset</a>
                    </form>

                    <?php if (empty($transactions)): ?>
                        <div class="text-center py-5 text-muted">No transactions found.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Reference</th>
                                        <th>User</th>
                                        <th>Email</th>
                                        <th>Course</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($transactions as $t): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($t['reference']); ?></td>
                                        <td><?php echo htmlspecialchars($t['user_name'] ?? '—'); ?></td>
                                        <td><?php echo htmlspecialchars($t['user_email'] ?? '—'); ?></td>
                                        <td><?php echo htmlspecialchars($t['course_title'] ?? '—'); ?></td>
                                        <td><?php echo htmlspecialchars((float)$t['amount']) . ' ' . htmlspecialchars($t['currency'] ?? ''); ?></td>
                                        <td>
                                            <span class="badge badge-status <?php echo htmlspecialchars($t['status']); ?>">
                                                <?php echo htmlspecialchars($t['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo formatDateTime($t['created_at']); ?></td>
                                        <td>
                                            <a class="btn btn-sm btn-outline-primary" href="transaction.php?ref=<?php echo urlencode($t['reference']); ?>">View</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if ($totalPages > 1): ?>
                            <nav class="d-flex justify-content-between align-items-center mt-3">
                                <span class="text-muted small">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
                                <div class="btn-group">
                                    <a class="btn btn-outline-secondary <?php echo $page <= 1 ? 'disabled' : ''; ?>" href="transactions.php?<?php echo buildQuery($baseQuery, ['page' => max(1, $page - 1)]); ?>">
                                        <i class="bi bi-chevron-left"></i> Prev
                                    </a>
                                    <a class="btn btn-outline-secondary <?php echo $page >= $totalPages ? 'disabled' : ''; ?>" href="transactions.php?<?php echo buildQuery($baseQuery, ['page' => min($totalPages, $page + 1)]); ?>">
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
