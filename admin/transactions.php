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
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Transactions - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { font-family: Roboto, sans-serif; background: #f1f5f9; }
        .card { border: none; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.06); }
        .table thead th { background: #f8fafc; }
        .badge-status { text-transform: capitalize; }
        .badge-status.completed { background: #dcfce7; color: #166534; }
        .badge-status.pending { background: #fef3c7; color: #92400e; }
        .badge-status.failed { background: #fee2e2; color: #b91c1c; }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="card p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h3 class="mb-0">Transactions</h3>
                    <small class="text-muted">Total: <?php echo number_format($total); ?></small>
                </div>
                <a href="users.php" class="btn btn-outline-secondary">Back</a>
            </div>

            <form method="GET" class="row g-2 align-items-center mb-3">
                <div class="col-auto">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" class="form-control" placeholder="Search reference, user, email, course">
                </div>
                <div class="col-auto">
                    <select name="status" class="form-select">
                        <option value="">All statuses</option>
                        <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="failed" <?php echo $status === 'failed' ? 'selected' : ''; ?>>Failed</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i>Filter</button>
                    <a href="transactions.php" class="btn btn-outline-secondary">Reset</a>
                </div>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
