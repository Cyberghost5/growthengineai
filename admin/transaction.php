<?php
/**
 * GrowthEngineAI LMS - Admin Transaction Detail
 */

require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Paystack.php';

$auth = new Auth();
$auth->requireRole('admin');

$ref = trim($_GET['ref'] ?? '');
if ($ref === '') {
    header('Location: transactions.php');
    exit;
}

$paystack = new Paystack();
$tx = $paystack->getTransactionByReference($ref);
if (!$tx) {
    header('Location: transactions.php?error=not_found');
    exit;
}

function formatDateTime($value) {
    if (!$value) return '—';
    $ts = strtotime($value);
    if (!$ts) return '—';
    return date('M j, Y g:i A', $ts);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Transaction <?php echo htmlspecialchars($tx['reference']); ?> - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { font-family: Roboto, sans-serif; background: #f1f5f9; }
        .card { border: none; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.06); }
        pre { background: #0f172a; color: #e6eef8; padding: 12px; border-radius: 8px; overflow:auto; }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="card p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h3 class="mb-0">Transaction Details</h3>
                    <small class="text-muted">Reference: <?php echo htmlspecialchars($tx['reference']); ?></small>
                </div>
                <div>
                    <a href="transactions.php" class="btn btn-outline-secondary">Back to Transactions</a>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <div class="border rounded-3 p-3 h-100">
                        <h6>Payment</h6>
                        <div><strong>Amount:</strong> <?php echo htmlspecialchars((float)$tx['amount']) . ' ' . htmlspecialchars($tx['currency'] ?? ''); ?></div>
                        <div><strong>Status:</strong> <?php echo htmlspecialchars($tx['status']); ?></div>
                        <div><strong>Method:</strong> <?php echo htmlspecialchars($tx['payment_method'] ?? '—'); ?></div>
                        <div><strong>Created:</strong> <?php echo formatDateTime($tx['created_at']); ?></div>
                        <div><strong>Paid At:</strong> <?php echo formatDateTime($tx['paid_at'] ?? null); ?></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="border rounded-3 p-3 h-100">
                        <h6>User & Course</h6>
                        <div><strong>User:</strong> <?php echo htmlspecialchars($tx['user_name'] ?? '—'); ?></div>
                        <div><strong>Email:</strong> <?php echo htmlspecialchars($tx['email'] ?? $tx['user_email'] ?? '—'); ?></div>
                        <div><strong>Course:</strong> <?php echo htmlspecialchars($tx['course_title'] ?? '—'); ?></div>
                        <div><strong>Reference:</strong> <?php echo htmlspecialchars($tx['reference']); ?></div>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <h6>Gateway Response</h6>
                <?php if (!empty($tx['paystack_response'])): ?>
                    <?php $resp = json_decode($tx['paystack_response'], true); ?>
                    <pre><?php echo htmlspecialchars(json_encode($resp, JSON_PRETTY_PRINT)); ?></pre>
                <?php else: ?>
                    <div class="text-muted">No gateway response saved.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
