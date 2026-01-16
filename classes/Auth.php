<?php
/**
 * GrowthEngineAI LMS - User Authentication Class
 * 
 * Handles user registration, login, logout, and password management
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Mailer.php';

class Auth {
    private $db;
    private $maxLoginAttempts = 10;
    private $lockoutTime = 900; // 15 minutes in seconds

    public function __construct() {
        $this->db = getDB();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Register a new user
     */
    public function register($data) {
        try {
            // Validate input
            $errors = $this->validateRegistration($data);
            if (!empty($errors)) {
                return ['success' => false, 'errors' => $errors];
            }

            // Check if email already exists
            if ($this->emailExists($data['email'])) {
                return ['success' => false, 'errors' => ['email' => 'This email is already registered.']];
            }

            // Hash password
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

            // Generate verification token
            $verificationToken = bin2hex(random_bytes(32));

            // Insert user
            $sql = "INSERT INTO users (first_name, last_name, email, password, phone, role, status, email_verification_token) 
                    VALUES (:first_name, :last_name, :email, :password, :phone, :role, 'pending', :token)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':first_name' => $this->sanitize($data['first_name']),
                ':last_name' => $this->sanitize($data['last_name']),
                ':email' => $this->sanitize($data['email']),
                ':password' => $hashedPassword,
                ':phone' => $this->sanitize($data['phone'] ?? ''),
                ':role' => $data['role'] ?? 'student',
                ':token' => $verificationToken
            ]);

            $userId = $this->db->lastInsertId();

            // Log activity
            $this->logActivity($userId, 'registration', 'New user registered');

            // Send verification email (implement your email sending logic)
            $this->sendVerificationEmail($data['email'], $verificationToken);

            return [
                'success' => true, 
                'message' => 'Registration successful! Please check your email to verify your account.',
                'user_id' => $userId
            ];

        } catch (PDOException $e) {
            return ['success' => false, 'errors' => ['general' => 'Registration failed. Please try again.']];
        }
    }

    /**
     * Login user
     */
    public function login($email, $password, $remember = false) {
        try {
            // Check for too many login attempts
            if ($this->isLockedOut($email)) {
                $remainingTime = $this->getRemainingLockoutTime($email);
                return [
                    'success' => false, 
                    'errors' => ['general' => "Too many login attempts. Please try again in " . ceil($remainingTime / 60) . " minutes."]
                ];
            }

            // Get user by email
            $user = $this->getUserByEmail($email);

            if (!$user || !password_verify($password, $user['password'])) {
                $this->recordLoginAttempt($email, false);
                return ['success' => false, 'errors' => ['general' => 'Invalid email or password.']];
            }

            // Check if account is active
            if ($user['status'] === 'pending') {
                return ['success' => false, 'errors' => ['general' => 'Please verify your email address before logging in.']];
            }

            if ($user['status'] === 'suspended') {
                return ['success' => false, 'errors' => ['general' => 'Your account has been suspended. Please contact support.']];
            }

            if ($user['status'] === 'inactive') {
                return ['success' => false, 'errors' => ['general' => 'Your account is inactive. Please contact support.']];
            }

            // Record successful login
            $this->recordLoginAttempt($email, true);

            // Update last login
            $this->updateLastLogin($user['id']);

            // Create session
            $this->createSession($user, $remember);

            // Log activity
            $this->logActivity($user['id'], 'login', 'User logged in');

            return [
                'success' => true, 
                'message' => 'Login successful!',
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['first_name'] . ' ' . $user['last_name'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ],
                'redirect' => $this->getRedirectUrl($user['role'])
            ];

        } catch (PDOException $e) {
            return ['success' => false, 'errors' => ['general' => 'Login failed. Please try again.']];
        }
    }

    /**
     * Logout user
     */
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            $this->logActivity($_SESSION['user_id'], 'logout', 'User logged out');
            
            // Delete session from database
            $this->deleteUserSession($_SESSION['user_id']);
        }

        // Clear session
        $_SESSION = [];
        
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        // Clear remember me cookie
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/');
        }
        
        session_destroy();

        return ['success' => true, 'message' => 'Logged out successfully.'];
    }

    /**
     * Request password reset
     */
    public function forgotPassword($email) {
        try {
            $user = $this->getUserByEmail($email);

            // Don't reveal if email exists or not for security
            if (!$user) {
                return [
                    'success' => true, 
                    'message' => 'If an account with that email exists, a password reset link has been sent.'
                ];
            }

            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+2 hours'));

            // Invalidate any existing reset tokens
            $this->invalidateResetTokens($user['id']);

            // Store new token
            $sql = "INSERT INTO password_resets (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':user_id' => $user['id'],
                ':token' => hash('sha256', $token),
                ':expires_at' => $expiresAt
            ]);

            // Send reset email
            $this->sendPasswordResetEmail($email, $token);

            // Log activity
            $this->logActivity($user['id'], 'password_reset_request', 'Password reset requested');

            return [
                'success' => true, 
                'message' => 'If an account with that email exists, a password reset link has been sent.'
            ];

        } catch (PDOException $e) {
            return ['success' => false, 'errors' => ['general' => 'Password reset request failed. Please try again.']];
        }
    }

    /**
     * Reset password with token
     */
    public function resetPassword($token, $newPassword, $confirmPassword) {
        try {
            // Validate passwords
            if ($newPassword !== $confirmPassword) {
                return ['success' => false, 'errors' => ['password' => 'Passwords do not match.']];
            }

            if (strlen($newPassword) < 8) {
                return ['success' => false, 'errors' => ['password' => 'Password must be at least 8 characters long.']];
            }

            // Find valid token
            $sql = "SELECT pr.*, u.email FROM password_resets pr 
                    JOIN users u ON pr.user_id = u.id 
                    WHERE pr.token = :token AND pr.expires_at > NOW() AND pr.used = 0";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':token' => hash('sha256', $token)]);
            $reset = $stmt->fetch();

            if (!$reset) {
                return ['success' => false, 'errors' => ['general' => 'Invalid or expired reset token.']];
            }

            // Update password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET password = :password WHERE id = :user_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':password' => $hashedPassword,
                ':user_id' => $reset['user_id']
            ]);

            // Mark token as used
            $sql = "UPDATE password_resets SET used = 1 WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $reset['id']]);

            // Log activity
            $this->logActivity($reset['user_id'], 'password_reset', 'Password was reset');

            return ['success' => true, 'message' => 'Password has been reset successfully. You can now login with your new password.'];

        } catch (PDOException $e) {
            return ['success' => false, 'errors' => ['general' => 'Password reset failed. Please try again.']];
        }
    }

    /**
     * Verify email address
     */
    public function verifyEmail($token) {
        try {
            $sql = "SELECT id, email FROM users WHERE email_verification_token = :token AND email_verified_at IS NULL";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':token' => $token]);
            $user = $stmt->fetch();

            if (!$user) {
                return ['success' => false, 'errors' => ['general' => 'Invalid or already used verification token.']];
            }

            // Verify email and activate account
            $sql = "UPDATE users SET email_verified_at = NOW(), email_verification_token = NULL, status = 'active' WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $user['id']]);

            // Log activity
            $this->logActivity($user['id'], 'email_verified', 'Email address verified');

            return ['success' => true, 'message' => 'Email verified successfully! You can now login.'];

        } catch (PDOException $e) {
            return ['success' => false, 'errors' => ['general' => 'Email verification failed. Please try again.']];
        }
    }

    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    /**
     * Get current logged in user
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }

        $sql = "SELECT id, first_name, last_name, email, phone, role, profile_image, bio, status, created_at, last_login 
                FROM users WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $_SESSION['user_id']]);
        return $stmt->fetch();
    }

    /**
     * Check if user has specific role
     */
    public function hasRole($role) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        return $_SESSION['user_role'] === $role;
    }

    /**
     * Check if user has any of the specified roles
     */
    public function hasAnyRole($roles) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        return in_array($_SESSION['user_role'], $roles);
    }

    /**
     * Require authentication - redirect if not logged in
     */
    public function requireAuth() {
        if (!$this->isLoggedIn()) {
            header('Location: ' . SITE_URL . '/auth/login.php');
            exit;
        }
    }

    /**
     * Require specific role
     */
    public function requireRole($role) {
        $this->requireAuth();
        if (!$this->hasRole($role)) {
            header('Location: ' . SITE_URL . '/auth/unauthorized.php');
            exit;
        }
    }

    // ==================== Private Helper Methods ====================

    private function validateRegistration($data) {
        $errors = [];

        if (empty($data['first_name'])) {
            $errors['first_name'] = 'First name is required.';
        }

        if (empty($data['last_name'])) {
            $errors['last_name'] = 'Last name is required.';
        }

        if (empty($data['email'])) {
            $errors['email'] = 'Email is required.';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address.';
        }

        if (empty($data['password'])) {
            $errors['password'] = 'Password is required.';
        } elseif (strlen($data['password']) < 8) {
            $errors['password'] = 'Password must be at least 8 characters long.';
        } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $data['password'])) {
            $errors['password'] = 'Password must contain at least one uppercase letter, one lowercase letter, and one number.';
        }

        if (empty($data['confirm_password'])) {
            $errors['confirm_password'] = 'Please confirm your password.';
        } elseif ($data['password'] !== $data['confirm_password']) {
            $errors['confirm_password'] = 'Passwords do not match.';
        }

        if (!empty($data['role']) && !in_array($data['role'], ['student', 'tutor'])) {
            $errors['role'] = 'Invalid role selected.';
        }

        return $errors;
    }

    private function emailExists($email) {
        $sql = "SELECT id FROM users WHERE email = :email";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
        return $stmt->fetch() !== false;
    }

    private function getUserByEmail($email) {
        $sql = "SELECT * FROM users WHERE email = :email";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
        return $stmt->fetch();
    }

    private function createSession($user, $remember = false) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();

        // Generate session token
        $sessionToken = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);

        // Store session in database
        $sql = "INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, expires_at) 
                VALUES (:user_id, :token, :ip, :user_agent, :expires_at)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id' => $user['id'],
            ':token' => $sessionToken,
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            ':expires_at' => $expiresAt
        ]);

        if ($remember) {
            $rememberToken = bin2hex(random_bytes(32));
            setcookie('remember_token', $rememberToken, time() + (86400 * 30), '/', '', false, true);
        }
    }

    private function deleteUserSession($userId) {
        $sql = "DELETE FROM user_sessions WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
    }

    private function updateLastLogin($userId) {
        $sql = "UPDATE users SET last_login = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $userId]);
    }

    private function isLockedOut($email) {
        $sql = "SELECT COUNT(*) as attempts FROM login_attempts 
                WHERE email = :email AND success = 0 AND attempted_at > DATE_SUB(NOW(), INTERVAL :lockout SECOND)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email, ':lockout' => $this->lockoutTime]);
        $result = $stmt->fetch();
        return $result['attempts'] >= $this->maxLoginAttempts;
    }

    private function getRemainingLockoutTime($email) {
        $sql = "SELECT attempted_at FROM login_attempts 
                WHERE email = :email AND success = 0 
                ORDER BY attempted_at DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
        $result = $stmt->fetch();
        
        if ($result) {
            $lastAttempt = strtotime($result['attempted_at']);
            $unlockTime = $lastAttempt + $this->lockoutTime;
            return max(0, $unlockTime - time());
        }
        return 0;
    }

    private function recordLoginAttempt($email, $success) {
        $sql = "INSERT INTO login_attempts (email, ip_address, success) VALUES (:email, :ip, :success)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':email' => $email,
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            ':success' => $success ? 1 : 0
        ]);
    }

    private function invalidateResetTokens($userId) {
        $sql = "UPDATE password_resets SET used = 1 WHERE user_id = :user_id AND used = 0";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
    }

    private function logActivity($userId, $action, $description) {
        $sql = "INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent) 
                VALUES (:user_id, :action, :description, :ip, :user_agent)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':action' => $action,
            ':description' => $description,
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    }

    public function getRedirectUrl($role) {
        switch ($role) {
            case 'admin':
                return SITE_URL . '/admin/dashboard.php';
            case 'tutor':
                return SITE_URL . '/tutor/dashboard.php';
            case 'student':
            default:
                return SITE_URL . '/student/dashboard.php';
        }
    }

    private function sanitize($input) {
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }

    private function sendVerificationEmail($email, $token) {
        $verificationLink = SITE_URL . '/auth/verify-email.php?token=' . $token;
        
        $subject = 'Verify Your Email - ' . SITE_NAME;
        $htmlBody = $this->getEmailTemplate('verification', [
            'verification_link' => $verificationLink,
            'site_name' => SITE_NAME,
            'site_url' => SITE_URL,
            'logo' => SITE_LOGO_DARK
        ]);

        $mailer = new Mailer();
        $result = $mailer->send($email, $subject, $htmlBody);
        
        if (!$result['success']) {
            error_log("Failed to send verification email to {$email}: " . $result['message']);
        }
        
        return $result;
    }

    private function sendPasswordResetEmail($email, $token) {
        $resetLink = SITE_URL . '/auth/reset-password.php?token=' . $token;
        
        $subject = 'Reset Your Password - ' . SITE_NAME;
        $htmlBody = $this->getEmailTemplate('password_reset', [
            'reset_link' => $resetLink,
            'site_name' => SITE_NAME,
            'site_url' => SITE_URL,
            'logo' => SITE_LOGO_DARK
        ]);

        $mailer = new Mailer();
        $result = $mailer->send($email, $subject, $htmlBody);
        
        if (!$result['success']) {
            error_log("Failed to send password reset email to {$email}: " . $result['message']);
        }
        
        return $result;
    }
    
    /**
     * Get styled email template
     */
    private function getEmailTemplate($type, $data) {
        $accentColor = '#000016';
        $headingColor = '#1a1a2e';
        $textColor = '#444444';
        
        $header = '
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
        </head>
        <body style="margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; background-color: #f8f9fa;">
            <table role="presentation" style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 40px 20px;">
                        <table role="presentation" style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08); overflow: hidden;">
                            <!-- Header -->
                            <tr>
                                <td style="background-color: ' . $accentColor . '; padding: 30px 40px; text-align: center;">
                                    <center>
                                    <img src="' . htmlspecialchars($data['logo'] ?? '') . '" alt="' . htmlspecialchars($data['site_name']) . ' Logo" style="max-height: 50px; margin-bottom: 10px;">
                                    </center>
                                </td>
                            </tr>
                            <!-- Content -->
                            <tr>
                                <td style="padding: 40px;">';
        
        $footer = '
                                    <p style="margin: 30px 0 0; padding-top: 20px; border-top: 1px solid #e5e7eb; font-size: 13px; color: #888888; text-align: center;">
                                        &copy; ' . date('Y') . ' ' . htmlspecialchars($data['site_name']) . '. All rights reserved.<br>
                                        <a href="' . htmlspecialchars($data['site_url']) . '" style="color: ' . $accentColor . '; text-decoration: none;">' . htmlspecialchars($data['site_url']) . '</a>
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>';
        
        switch ($type) {
            case 'verification':
                $content = '
                    <h2 style="margin: 0 0 20px; color: ' . $headingColor . '; font-size: 24px;">Welcome to ' . htmlspecialchars($data['site_name']) . '!</h2>
                    <p style="margin: 0 0 20px; color: ' . $textColor . '; font-size: 16px; line-height: 1.6;">Thank you for registering. Please verify your email address by clicking the button below:</p>
                    <p style="margin: 30px 0; text-align: center;">
                        <a href="' . htmlspecialchars($data['verification_link']) . '" style="display: inline-block; padding: 14px 32px; background-color: ' . $accentColor . '; color: #ffffff; text-decoration: none; font-size: 16px; font-weight: 600; border-radius: 8px;">Verify Email Address</a>
                    </p>
                    <p style="margin: 0 0 10px; color: ' . $textColor . '; font-size: 14px; line-height: 1.6;">Or copy and paste this link into your browser:</p>
                    <p style="margin: 0 0 20px; word-break: break-all;">
                        <a href="' . htmlspecialchars($data['verification_link']) . '" style="color: ' . $accentColor . '; font-size: 14px;">' . htmlspecialchars($data['verification_link']) . '</a>
                    </p>
                    <p style="margin: 0; color: #888888; font-size: 14px;">This link will expire in 24 hours.</p>
                    <p style="margin: 20px 0 0; color: #888888; font-size: 14px;">If you did not create an account, please ignore this email.</p>';
                break;
                
            case 'password_reset':
                $content = '
                    <h2 style="margin: 0 0 20px; color: ' . $headingColor . '; font-size: 24px;">Password Reset Request</h2>
                    <p style="margin: 0 0 20px; color: ' . $textColor . '; font-size: 16px; line-height: 1.6;">You have requested to reset your password. Click the button below to proceed:</p>
                    <p style="margin: 30px 0; text-align: center;">
                        <a href="' . htmlspecialchars($data['reset_link']) . '" style="display: inline-block; padding: 14px 32px; background-color: ' . $accentColor . '; color: #ffffff; text-decoration: none; font-size: 16px; font-weight: 600; border-radius: 8px;">Reset Password</a>
                    </p>
                    <p style="margin: 0 0 10px; color: ' . $textColor . '; font-size: 14px; line-height: 1.6;">Or copy and paste this link into your browser:</p>
                    <p style="margin: 0 0 20px; word-break: break-all;">
                        <a href="' . htmlspecialchars($data['reset_link']) . '" style="color: ' . $accentColor . '; font-size: 14px;">' . htmlspecialchars($data['reset_link']) . '</a>
                    </p>
                    <p style="margin: 0; color: #888888; font-size: 14px;">This link will expire in 1 hour.</p>
                    <p style="margin: 20px 0 0; color: #888888; font-size: 14px;">If you did not request a password reset, please ignore this email and your password will remain unchanged.</p>';
                break;
                
            default:
                $content = '';
        }
        
        return $header . $content . $footer;
    }
}
