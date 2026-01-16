<?php
/**
 * GrowthEngineAI LMS - Reset Password Page
 */

require_once __DIR__ . '/../classes/Auth.php';

$auth = new Auth();

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    header('Location: ../index.html');
    exit;
}

$errors = [];
$success = '';
$token = $_GET['token'] ?? '';
$validToken = !empty($token);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($password)) {
        $errors['password'] = 'Please enter a new password.';
    }

    if (empty($confirmPassword)) {
        $errors['confirm_password'] = 'Please confirm your password.';
    }

    if (empty($errors)) {
        $result = $auth->resetPassword($token, $password, $confirmPassword);

        if ($result['success']) {
            header('Location: login.php?reset=1');
            exit;
        } else {
            $errors = $result['errors'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - GrowthEngineAI</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../images/favicon.png" rel="icon">
    <link href="../css/auth.css" rel="stylesheet">
</head>
<body>
    <div class="auth-container">
        <div class="auth-wrapper">
            <div class="auth-left">
                <div class="auth-left-content">
                    <a href="../index.html" class="auth-logo">
                        <img src="../images/logo-dark.png" alt="" width="250px">
                        <!-- <span>GrowthEngineAI</span> -->
                    </a>
                    <h1>Create New Password</h1>
                    <p>Your new password must be different from previously used passwords and meet our security requirements.</p>
                    <div class="auth-features">
                        <div class="auth-feature">
                            <i class="bi bi-check2-circle"></i>
                            <span>At least 8 characters</span>
                        </div>
                        <div class="auth-feature">
                            <i class="bi bi-check2-circle"></i>
                            <span>One uppercase letter</span>
                        </div>
                        <div class="auth-feature">
                            <i class="bi bi-check2-circle"></i>
                            <span>One number required</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="auth-right">
                <div class="auth-form-container">
                    <div class="auth-form-header">
                        <div class="auth-icon-circle">
                            <i class="bi bi-shield-lock"></i>
                        </div>
                        <h2>Set New Password</h2>
                        <p>Create a strong password for your account</p>
                    </div>

                    <?php if (!$validToken && empty($_POST)): ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-circle"></i>
                            Invalid or missing reset token. Please request a new password reset link.
                        </div>
                        <div class="auth-alt-actions mt-4">
                            <a href="forgot-password.php" class="btn btn-primary btn-auth">
                                <span>Request New Link</span>
                            </a>
                        </div>
                    <?php else: ?>

                        <?php if (!empty($errors['general'])): ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-circle"></i>
                                <?php echo htmlspecialchars($errors['general']); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle"></i>
                                <?php echo htmlspecialchars($success); ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="" class="auth-form">
                            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                            <div class="form-group">
                                <label for="password">New Password</label>
                                <div class="input-wrapper">
                                    <i class="bi bi-lock"></i>
                                    <input type="password" 
                                           id="password" 
                                           name="password" 
                                           class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" 
                                           placeholder="Enter new password"
                                           required>
                                    <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <small class="form-text text-muted">Min 8 characters with uppercase, lowercase, and number</small>
                                <?php if (isset($errors['password'])): ?>
                                    <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['password']); ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password</label>
                                <div class="input-wrapper">
                                    <i class="bi bi-lock-fill"></i>
                                    <input type="password" 
                                           id="confirm_password" 
                                           name="confirm_password" 
                                           class="form-control <?php echo isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>" 
                                           placeholder="Confirm new password"
                                           required>
                                    <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <?php if (isset($errors['confirm_password'])): ?>
                                    <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['confirm_password']); ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Password strength indicator -->
                            <div class="password-strength">
                                <div class="strength-bar">
                                    <div class="strength-fill" id="strengthFill"></div>
                                </div>
                                <span class="strength-text" id="strengthText">Password Strength</span>
                            </div>

                            <button type="submit" class="btn btn-primary btn-auth">
                                <span>Reset Password</span>
                                <i class="bi bi-check2"></i>
                            </button>
                        </form>
                    <?php endif; ?>

                    <div class="auth-footer">
                        <a href="login.php"><i class="bi bi-arrow-left"></i> Back to Sign In</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = input.nextElementSibling.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        }

        // Password strength checker
        const passwordInput = document.getElementById('password');
        const strengthFill = document.getElementById('strengthFill');
        const strengthText = document.getElementById('strengthText');

        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;
                let text = 'Weak';
                let color = '#dc3545';

                if (password.length >= 8) strength += 25;
                if (password.match(/[a-z]/)) strength += 25;
                if (password.match(/[A-Z]/)) strength += 25;
                if (password.match(/[0-9]/)) strength += 25;

                if (strength <= 25) {
                    text = 'Weak';
                    color = '#dc3545';
                } else if (strength <= 50) {
                    text = 'Fair';
                    color = '#fd7e14';
                } else if (strength <= 75) {
                    text = 'Good';
                    color = '#ffc107';
                } else {
                    text = 'Strong';
                    color = '#28a745';
                }

                strengthFill.style.width = strength + '%';
                strengthFill.style.backgroundColor = color;
                strengthText.textContent = text;
                strengthText.style.color = color;
            });
        }
    </script>
</body>
</html>
