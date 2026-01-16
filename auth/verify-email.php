<?php
/**
 * GrowthEngineAI LMS - Email Verification Page
 */

require_once __DIR__ . '/../classes/Auth.php';

$auth = new Auth();

$errors = [];
$success = '';
$token = $_GET['token'] ?? '';

if (!empty($token)) {
    $result = $auth->verifyEmail($token);

    if ($result['success']) {
        header('Location: login.php?verified=1');
        exit;
    } else {
        $errors = $result['errors'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - GrowthEngineAI</title>
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
                <?php if (empty($token)): ?>
                    <div class="auth-icon-circle error">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <h2>Invalid Link</h2>
                    <p class="text-muted">The verification link is invalid or missing.</p>
                <?php elseif (!empty($errors['general'])): ?>
                    <div class="auth-icon-circle error">
                        <i class="bi bi-x-circle"></i>
                    </div>
                    <h2>Verification Failed</h2>
                    <p class="text-muted"><?php echo htmlspecialchars($errors['general']); ?></p>
                <?php else: ?>
                    <div class="auth-icon-circle success">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <h2>Email Verified!</h2>
                    <p class="text-muted">Your email has been verified successfully.</p>
                <?php endif; ?>

                <div class="auth-alt-actions mt-4">
                    <a href="login" class="btn btn-primary btn-auth">
                        <span>Go to Login</span>
                        <i class="bi bi-arrow-right"></i>
                    </a>
                </div>

                <div class="auth-footer mt-4">
                    <a href="../index.html"><i class="bi bi-arrow-left"></i> Back to Home</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
