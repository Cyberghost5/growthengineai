<?php
/**
 * GrowthEngineAI LMS - Unauthorized Access Page
 */

require_once __DIR__ . '/../classes/Auth.php';

$auth = new Auth();
$user = $auth->getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied - GrowthEngineAI</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../images/favicon.png" rel="icon">
    <link href="../css/auth.css" rel="stylesheet">
</head>
<body>
    <div class="auth-container">
        <div class="auth-wrapper auth-wrapper-centered">
            <div class="auth-form-container text-center">
                <div class="auth-icon-circle error">
                    <i class="bi bi-shield-exclamation"></i>
                </div>
                <h2>Access Denied</h2>
                <p class="text-muted mb-4">You don't have permission to access this page. Please contact an administrator if you believe this is an error.</p>

                <div class="auth-alt-actions">
                    <?php if ($user): ?>
                        <a href="<?php echo $auth->getRedirectUrl($user['role']); ?>" class="btn btn-primary btn-auth mb-3">
                            <span>Go to Dashboard</span>
                            <i class="bi bi-arrow-right"></i>
                        </a>
                    <?php else: ?>
                        <a href="login" class="btn btn-primary btn-auth mb-3">
                            <span>Sign In</span>
                            <i class="bi bi-arrow-right"></i>
                        </a>
                    <?php endif; ?>
                </div>

                <div class="auth-footer mt-4">
                    <a href="../"><i class="bi bi-arrow-left"></i> Back to Home</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
