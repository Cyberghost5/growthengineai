<?php
/**
 * GrowthEngineAI LMS - Student Transactions
 */

require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Paystack.php';
require_once __DIR__ . '/../classes/Url.php';

$auth = new Auth();
$auth->requireRole('student');

$user = $auth->getCurrentUser();
$paystack = new Paystack();

// Get all transactions for the user
$transactions = $paystack->getUserTransactions($user['id']);
$stats = $paystack->getUserTransactionStats($user['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Transactions - GrowthEngineAI</title>
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
            margin-bottom: 20px;
        }
        .stats-card {
            text-align: center;
            padding: 24px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        .stats-card .stats-icon {
            font-size: 40px;
            margin-bottom: 12px;
        }
        .stats-card.success .stats-icon {
            color: #10b981;
        }
        .stats-card.warning .stats-icon {
            color: #f59e0b;
        }
        .stats-card.danger .stats-icon {
            color: #ef4444;
        }
        .stats-card.primary .stats-icon {
            color: #8b5cf6;
        }
        .stats-card h3 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 4px;
            color: #1e293b;
        }
        .stats-card p {
            color: #64748b;
            margin: 0;
        }
        .transaction-table {
            margin: 0;
        }
        .transaction-table thead th {
            background: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
            color: #64748b;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 16px;
            white-space: nowrap;
        }
        .transaction-table tbody td {
            padding: 16px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
        }
        .transaction-table tbody tr:hover {
            background: #f8fafc;
        }
        .transaction-table .course-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .transaction-table .course-thumb {
            width: 60px;
            height: 45px;
            object-fit: cover;
            border-radius: 6px;
            flex-shrink: 0;
        }
        .transaction-table .course-title {
            font-weight: 600;
            color: #1e293b;
            font-size: 14px;
            margin-bottom: 2px;
        }
        .transaction-table .course-ref {
            font-size: 11px;
            color: #94a3b8;
            font-family: monospace;
        }
        .transaction-table .amount {
            font-weight: 700;
            color: #1e293b;
            font-size: 15px;
        }
        .transaction-table .date {
            color: #64748b;
            font-size: 13px;
        }
        .transaction-status {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .status-completed {
            background: #d1fae5;
            color: #065f46;
        }
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        .status-failed {
            background: #fee2e2;
            color: #991b1b;
        }
        /* Mobile card view */
        .transaction-card-mobile {
            display: none;
        }
        @media (max-width: 768px) {
            .transaction-table-wrapper {
                display: none;
            }
            .transaction-card-mobile {
                display: block;
            }
            .mobile-transaction-card {
                background: white;
                border-radius: 12px;
                padding: 16px;
                margin-bottom: 12px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            }
            .mobile-transaction-card .card-header {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                margin-bottom: 12px;
            }
            .mobile-transaction-card .course-info {
                display: flex;
                gap: 12px;
                align-items: center;
            }
            .mobile-transaction-card .course-thumb {
                width: 50px;
                height: 38px;
                object-fit: cover;
                border-radius: 6px;
            }
            .mobile-transaction-card .course-title {
                font-weight: 600;
                font-size: 14px;
                color: #1e293b;
                margin-bottom: 2px;
            }
            .mobile-transaction-card .course-ref {
                font-size: 10px;
                color: #94a3b8;
                font-family: monospace;
            }
            .mobile-transaction-card .card-details {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding-top: 12px;
                border-top: 1px solid #f1f5f9;
            }
            .mobile-transaction-card .amount {
                font-weight: 700;
                font-size: 16px;
                color: #1e293b;
            }
            .mobile-transaction-card .date {
                font-size: 12px;
                color: #94a3b8;
            }
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
                    <h1><i class="bi bi-receipt me-2"></i>My Transactions</h1>
                    <p class="mb-0">View your payment history and course purchases</p>
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
                        <a class="nav-link active" href="<?php echo Url::transactions(); ?>">
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
                <!-- Statistics Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <div class="stats-card primary">
                            <div class="stats-icon"><i class="bi bi-receipt-cutoff"></i></div>
                            <h3><?php echo number_format($stats['total_transactions']); ?></h3>
                            <p>Total Transactions</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card success">
                            <div class="stats-icon"><i class="bi bi-check-circle"></i></div>
                            <h3><?php echo number_format($stats['successful_transactions']); ?></h3>
                            <p>Successful Payments</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card warning">
                            <div class="stats-icon"><i class="bi bi-clock-history"></i></div>
                            <h3><?php echo number_format($stats['pending_transactions']); ?></h3>
                            <p>Pending Transactions</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card primary">
                            <div class="stats-icon"><i class="bi bi-cash-stack"></i></div>
                            <h3>₦<?php echo number_format($stats['total_spent'], 2); ?></h3>
                            <p>Total Spent</p>
                        </div>
                    </div>
                </div>

                <!-- Transaction History -->
                <div class="card">
                    <div class="card-body p-0">
                        <div class="p-4 border-bottom">
                            <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Transaction History</h5>
                        </div>
                        
                        <?php if (empty($transactions)): ?>
                        <div class="empty-state">
                            <i class="bi bi-receipt"></i>
                            <h5>No Transactions Yet</h5>
                            <p>Your payment history will appear here once you make a purchase</p>
                            <a href="<?php echo Url::courses(); ?>" class="btn btn-primary mt-3">
                                <i class="bi bi-book me-2"></i>Browse Courses
                            </a>
                        </div>
                        <?php else: ?>
                        
                        <!-- Desktop Table View -->
                        <div class="transaction-table-wrapper">
                            <div class="table-responsive">
                                <table class="table transaction-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Course</th>
                                            <th>Amount</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($transactions as $transaction): ?>
                                        <tr>
                                            <td>
                                                <div class="course-cell">
                                                    <img src="<?php echo htmlspecialchars($transaction['course_image'] ?: 'https://via.placeholder.com/60x45'); ?>" 
                                                         alt="<?php echo htmlspecialchars($transaction['course_title']); ?>" 
                                                         class="course-thumb">
                                                    <div>
                                                        <div class="course-title"><?php echo htmlspecialchars($transaction['course_title']); ?></div>
                                                        <div class="course-ref">Ref: <?php echo htmlspecialchars($transaction['reference']); ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="amount"><?php echo $transaction['currency']; ?> <?php echo number_format($transaction['amount'], 2); ?></span>
                                            </td>
                                            <td>
                                                <span class="date"><?php echo date('M d, Y', strtotime($transaction['created_at'])); ?></span>
                                                <br>
                                                <small class="text-muted"><?php echo date('g:i A', strtotime($transaction['created_at'])); ?></small>
                                            </td>
                                            <td>
                                                <?php 
                                                $statusIcons = [
                                                    'completed' => 'check-circle-fill',
                                                    'pending' => 'clock-fill',
                                                    'failed' => 'x-circle-fill',
                                                    'cancelled' => 'x-circle-fill'
                                                ];
                                                ?>
                                                <span class="transaction-status status-<?php echo strtolower($transaction['status']); ?>">
                                                    <i class="bi bi-<?php echo $statusIcons[$transaction['status']]; ?>"></i>
                                                    <?php echo ucfirst($transaction['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Mobile Card View -->
                        <div class="transaction-card-mobile p-3">
                            <?php foreach ($transactions as $transaction): ?>
                            <div class="mobile-transaction-card">
                                <div class="card-header">
                                    <div class="course-info">
                                        <img src="<?php echo htmlspecialchars($transaction['course_image'] ?: 'https://via.placeholder.com/50x38'); ?>" 
                                             alt="<?php echo htmlspecialchars($transaction['course_title']); ?>" 
                                             class="course-thumb">
                                        <div>
                                            <div class="course-title"><?php echo htmlspecialchars($transaction['course_title']); ?></div>
                                            <div class="course-ref">Ref: <?php echo htmlspecialchars(substr($transaction['reference'], 0, 20)); ?>...</div>
                                        </div>
                                    </div>
                                    <?php 
                                    $statusIcons = [
                                        'completed' => 'check-circle-fill',
                                        'pending' => 'clock-fill',
                                        'failed' => 'x-circle-fill',
                                        'cancelled' => 'x-circle-fill'
                                    ];
                                    ?>
                                    <span class="transaction-status status-<?php echo strtolower($transaction['status']); ?>">
                                        <i class="bi bi-<?php echo $statusIcons[$transaction['status']]; ?>"></i>
                                        <?php echo ucfirst($transaction['status']); ?>
                                    </span>
                                </div>
                                <div class="card-details">
                                    <div>
                                        <div class="amount"><?php echo $transaction['currency']; ?> <?php echo number_format($transaction['amount'], 2); ?></div>
                                        <div class="date"><?php echo date('M d, Y - g:i A', strtotime($transaction['created_at'])); ?></div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php endif; ?>
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

        // Show success/error messages from URL
        const urlParams = new URLSearchParams(window.location.search);
        const payment = urlParams.get('payment');
        const message = urlParams.get('message');

        if (payment && message) {
            const alertClass = payment === 'success' ? 'alert-success' : 'alert-danger';
            const alertHtml = `
                <div class="alert ${alertClass} alert-dismissible fade show mb-4" role="alert">
                    <i class="bi bi-${payment === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                    ${decodeURIComponent(message)}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            document.querySelector('.col-lg-12').insertAdjacentHTML('afterbegin', alertHtml);
            
            // Remove query params
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    </script>
</body>
</html>
