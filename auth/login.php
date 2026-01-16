<?php
/**
 * GrowthEngineAI LMS - Login Page
 */

require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/GoogleAuth.php';

$auth = new Auth();
$googleAuth = new GoogleAuth();
$googleAuthUrl = GoogleAuth::isConfigured() ? $googleAuth->getAuthUrl() : null;

// Handle AJAX request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && 
    isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    
    header('Content-Type: application/json');
    
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    $result = $auth->login($email, $password, $remember);

    echo json_encode([
        'success' => $result['success'],
        'redirect' => $result['redirect'] ?? null,
        'errors' => $result['errors'] ?? []
    ]);
    exit;
}

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    $user = $auth->getCurrentUser();
    header('Location: ' . $auth->getRedirectUrl($user['role'] ?? 'student'));
    exit;
}

$errors = [];
$success = '';
$email = '';

// Handle regular form submission (fallback)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    $result = $auth->login($email, $password, $remember);

    if ($result['success']) {
        header('Location: ' . $result['redirect']);
        exit;
    } else {
        $errors = $result['errors'];
    }
}

// Check for messages from other pages
if (isset($_GET['verified'])) {
    $success = 'Email verified successfully! You can now login.';
}
if (isset($_GET['registered'])) {
    $success = 'Registration successful! Please check your email to verify your account.';
}
if (isset($_GET['reset'])) {
    $success = 'Password reset successful! You can now login with your new password.';
}
if (isset($_GET['error'])) {
    $errors['general'] = $_GET['error'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - GrowthEngineAI</title>
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
                    <h1>Welcome Back!</h1>
                    <p>Login to access your courses, track your progress, and connect with our community of learners.</p>
                    <div class="auth-features">
                        <div class="auth-feature">
                            <i class="bi bi-book"></i>
                            <span>Access Premium Courses</span>
                        </div>
                        <div class="auth-feature">
                            <i class="bi bi-slack"></i>
                            <span>Join Slack Community</span>
                        </div>
                        <div class="auth-feature">
                            <i class="bi bi-graph-up"></i>
                            <span>Track Your Progress</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="auth-right">
                <div class="auth-form-container">
                    <div class="auth-form-header">
                        <h2>Sign In</h2>
                        <p>Enter your credentials to access your account</p>
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
                                       placeholder="Enter your email"
                                       value="<?php echo htmlspecialchars($email); ?>"
                                       required>
                            </div>
                            <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['email']); ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="password">Password</label>
                            <div class="input-wrapper">
                                <i class="bi bi-lock"></i>
                                <input type="password" 
                                       id="password" 
                                       name="password" 
                                       class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" 
                                       placeholder="Enter your password"
                                       required>
                                <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <?php if (isset($errors['password'])): ?>
                                <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['password']); ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-options">
                            <label class="remember-me">
                                <input type="checkbox" name="remember">
                                <span>Remember me</span>
                            </label>
                            <a href="forgot-password" class="forgot-link">Forgot Password?</a>
                        </div>

                        <button type="submit" class="btn btn-primary btn-auth">
                            <span>Sign In</span>
                            <i class="bi bi-arrow-right"></i>
                        </button>
                    </form>

                    <div class="auth-divider">
                        <span>OR</span>
                    </div>

                    <?php if ($googleAuthUrl): ?>
                    <a href="<?php echo htmlspecialchars($googleAuthUrl); ?>" class="btn btn-google btn-auth">
                        <svg width="18" height="18" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg">
                            <path d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844c-.209 1.125-.843 2.078-1.796 2.717v2.258h2.908c1.702-1.567 2.684-3.874 2.684-6.615z" fill="#4285F4"/>
                            <path d="M9.003 18c2.43 0 4.467-.806 5.956-2.18l-2.909-2.26c-.806.54-1.836.86-3.047.86-2.344 0-4.328-1.584-5.036-3.711H.957v2.332A8.997 8.997 0 0 0 9.003 18z" fill="#34A853"/>
                            <path d="M3.964 10.712A5.41 5.41 0 0 1 3.682 9c0-.593.102-1.17.282-1.71V4.958H.957A8.996 8.996 0 0 0 0 9c0 1.452.348 2.827.957 4.042l3.007-2.33z" fill="#FBBC05"/>
                            <path d="M9.003 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.464.891 11.428 0 9.002 0A8.997 8.997 0 0 0 .957 4.958L3.964 7.29c.708-2.127 2.692-3.71 5.036-3.71z" fill="#EA4335"/>
                        </svg>
                        <span>Continue with Google</span>
                    </a>
                    <?php endif; ?>

                    <div class="auth-alt-actions">
                        <p>Don't have an account?</p>
                        <a href="register" class="btn btn-outline-primary btn-auth">
                            <span>Create Account</span>
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
            document.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
            
            // Show loading state
            submitBtn.disabled = true;
            btnText.textContent = 'Signing in...';
            submitBtn.querySelector('i').classList.add('bi-arrow-repeat', 'spin');
            submitBtn.querySelector('i').classList.remove('bi-arrow-right');
            
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
                    showAlert('success', 'Login successful! Redirecting...');
                    
                    // Redirect after brief delay
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 500);
                } else {
                    // Show errors
                    if (data.errors.general) {
                        showAlert('danger', data.errors.general);
                    }
                    if (data.errors.email) {
                        showFieldError('email', data.errors.email);
                    }
                    if (data.errors.password) {
                        showFieldError('password', data.errors.password);
                    }
                    
                    // Reset button
                    submitBtn.disabled = false;
                    btnText.textContent = originalText;
                    submitBtn.querySelector('i').classList.remove('bi-arrow-repeat', 'spin');
                    submitBtn.querySelector('i').classList.add('bi-arrow-right');
                }
            } catch (error) {
                console.error('Login error:', error);
                showAlert('danger', 'An error occurred. Please try again.');
                
                // Reset button
                submitBtn.disabled = false;
                btnText.textContent = originalText;
                submitBtn.querySelector('i').classList.remove('bi-arrow-repeat', 'spin');
                submitBtn.querySelector('i').classList.add('bi-arrow-right');
            }
        });
        
        function showAlert(type, message) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type}`;
            alertDiv.innerHTML = `<i class="bi bi-${type === 'success' ? 'check' : 'exclamation'}-circle"></i> ${message}`;
            
            const formHeader = document.querySelector('.auth-form-header');
            formHeader.insertAdjacentElement('afterend', alertDiv);
        }
        
        function showFieldError(fieldId, message) {
            const input = document.getElementById(fieldId);
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
