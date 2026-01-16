<?php
/**
 * GrowthEngineAI LMS - Forgot Password Page
 */

require_once __DIR__ . '/../classes/Auth.php';

$auth = new Auth();

// Handle AJAX request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && 
    isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    
    header('Content-Type: application/json');
    
    $email = $_POST['email'] ?? '';
    $errors = [];
    
    if (empty($email)) {
        $errors['email'] = 'Please enter your email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }
    
    if (empty($errors)) {
        $result = $auth->forgotPassword($email);
        
        echo json_encode([
            'success' => $result['success'],
            'message' => $result['message'] ?? null,
            'errors' => $result['errors'] ?? []
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'errors' => $errors
        ]);
    }
    exit;
}

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    header('Location: ../index.html');
    exit;
}

$errors = [];
$success = '';
$email = '';

// Handle regular form submission (fallback)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';

    if (empty($email)) {
        $errors['email'] = 'Please enter your email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }

    if (empty($errors)) {
        $result = $auth->forgotPassword($email);

        if ($result['success']) {
            $success = $result['message'];
            $email = ''; // Clear the email field
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
    <title>Forgot Password - GrowthEngineAI</title>
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
                    <h1>Forgot Your Password?</h1>
                    <p>Don't worry, it happens to the best of us. Enter your email address and we'll send you a link to reset your password.</p>
                    <div class="auth-features">
                        <div class="auth-feature">
                            <i class="bi bi-envelope-check"></i>
                            <span>Check Your Email</span>
                        </div>
                        <div class="auth-feature">
                            <i class="bi bi-link-45deg"></i>
                            <span>Click Reset Link</span>
                        </div>
                        <div class="auth-feature">
                            <i class="bi bi-key"></i>
                            <span>Create New Password</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="auth-right">
                <div class="auth-form-container">
                    <div class="auth-form-header">
                        <div class="auth-icon-circle">
                            <i class="bi bi-key"></i>
                        </div>
                        <h2>Reset Password</h2>
                        <p>Enter your email to receive a password reset link</p>
                    </div>

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
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <div class="input-wrapper">
                                <i class="bi bi-envelope"></i>
                                <input type="email" 
                                       id="email" 
                                       name="email" 
                                       class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" 
                                       placeholder="Enter your registered email"
                                       value="<?php echo htmlspecialchars($email); ?>"
                                       required>
                            </div>
                            <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['email']); ?></div>
                            <?php endif; ?>
                        </div>

                        <button type="submit" class="btn btn-primary btn-auth">
                            <span>Send Reset Link</span>
                            <i class="bi bi-send"></i>
                        </button>
                    </form>

                    <div class="auth-divider">
                        <span>OR</span>
                    </div>

                    <div class="auth-alt-actions">
                        <p>Remember your password?</p>
                        <a href="login" class="btn btn-outline-primary btn-auth">
                            <span>Back to Sign In</span>
                        </a>
                    </div>

                    <div class="auth-footer">
                        <a href="../index.html"><i class="bi bi-arrow-left"></i> Back to Home</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // AJAX Form Submission
        document.querySelector('.auth-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const form = this;
            const submitBtn = form.querySelector('button[type="submit"]');
            const btnText = submitBtn.querySelector('span');
            const originalText = btnText.textContent;
            
            // Clear previous errors
            document.querySelectorAll('.alert').forEach(el => el.remove());
            document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            document.querySelectorAll('.invalid-feedback.d-block').forEach(el => el.remove());
            
            // Show loading state
            submitBtn.disabled = true;
            btnText.textContent = 'Sending...';
            submitBtn.querySelector('i').classList.add('bi-arrow-repeat', 'spin');
            submitBtn.querySelector('i').classList.remove('bi-send');
            
            try {
                const formData = new FormData(form);
                
                const response = await fetch(form.action || window.location.href, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Show success message
                    showAlert('success', data.message || 'Password reset link sent! Check your email.');
                    
                    // Clear the form
                    form.reset();
                    
                    // Reset button
                    resetButton(submitBtn, btnText, originalText);
                } else {
                    // Show errors
                    if (data.errors.general) {
                        showAlert('danger', data.errors.general);
                    }
                    if (data.errors.email) {
                        showFieldError('email', data.errors.email);
                    }
                    
                    // Reset button
                    resetButton(submitBtn, btnText, originalText);
                }
            } catch (error) {
                console.error('Forgot password error:', error);
                showAlert('danger', 'An error occurred. Please try again.');
                resetButton(submitBtn, btnText, originalText);
            }
        });
        
        function resetButton(btn, btnText, originalText) {
            btn.disabled = false;
            btnText.textContent = originalText;
            btn.querySelector('i').classList.remove('bi-arrow-repeat', 'spin');
            btn.querySelector('i').classList.add('bi-send');
        }
        
        function showAlert(type, message) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type}`;
            alertDiv.innerHTML = `<i class="bi bi-${type === 'success' ? 'check' : 'exclamation'}-circle"></i> ${message}`;
            
            const formHeader = document.querySelector('.auth-form-header');
            formHeader.insertAdjacentElement('afterend', alertDiv);
        }
        
        function showFieldError(fieldId, message) {
            const input = document.getElementById(fieldId);
            if (!input) return;
            
            input.classList.add('is-invalid');
            
            const errorDiv = document.createElement('div');
            errorDiv.className = 'invalid-feedback d-block';
            errorDiv.textContent = message;
            
            input.closest('.form-group').appendChild(errorDiv);
        }
    </script>
    <style>
        .spin {
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    </style>
</body>
</html>
