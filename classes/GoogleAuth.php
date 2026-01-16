<?php
/**
 * GrowthEngineAI LMS - Google OAuth Handler
 * 
 * Handles Google Sign-In authentication
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/google.php';

class GoogleAuth {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Generate the Google OAuth URL
     */
    public function getAuthUrl($state = null) {
        // Generate state for CSRF protection
        if ($state === null) {
            $state = bin2hex(random_bytes(16));
        }
        $_SESSION['google_oauth_state'] = $state;
        
        $params = [
            'client_id' => GOOGLE_CLIENT_ID,
            'redirect_uri' => GOOGLE_REDIRECT_URI,
            'response_type' => 'code',
            'scope' => GOOGLE_SCOPES,
            'state' => $state,
            'access_type' => 'online',
            'prompt' => 'select_account'
        ];
        
        return GOOGLE_AUTH_URL . '?' . http_build_query($params);
    }
    
    /**
     * Handle the OAuth callback
     */
    public function handleCallback($code, $state) {
        // Verify state for CSRF protection
        if (!isset($_SESSION['google_oauth_state']) || $state !== $_SESSION['google_oauth_state']) {
            return ['success' => false, 'error' => 'Invalid state parameter. Please try again.'];
        }
        
        // Clear the state
        unset($_SESSION['google_oauth_state']);
        
        // Exchange code for access token
        $tokenData = $this->getAccessToken($code);
        
        if (!$tokenData || isset($tokenData['error'])) {
            return ['success' => false, 'error' => $tokenData['error_description'] ?? 'Failed to get access token.'];
        }
        
        // Get user info from Google
        $userInfo = $this->getUserInfo($tokenData['access_token']);
        
        if (!$userInfo || isset($userInfo['error'])) {
            return ['success' => false, 'error' => 'Failed to get user information from Google.'];
        }
        
        // Find or create user
        return $this->findOrCreateUser($userInfo);
    }
    
    /**
     * Exchange authorization code for access token
     */
    private function getAccessToken($code) {
        $params = [
            'client_id' => GOOGLE_CLIENT_ID,
            'client_secret' => GOOGLE_CLIENT_SECRET,
            'redirect_uri' => GOOGLE_REDIRECT_URI,
            'code' => $code,
            'grant_type' => 'authorization_code'
        ];
        
        $ch = curl_init(GOOGLE_TOKEN_URL);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            error_log("Google OAuth token error: " . $error);
            return null;
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Get user info from Google
     */
    private function getUserInfo($accessToken) {
        $ch = curl_init(GOOGLE_USERINFO_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            error_log("Google OAuth userinfo error: " . $error);
            return null;
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Find existing user or create new one
     */
    private function findOrCreateUser($googleUser) {
        try {
            $email = $googleUser['email'];
            $googleId = $googleUser['id'];
            
            // Check if user exists by Google ID
            $stmt = $this->db->prepare("SELECT * FROM users WHERE google_id = :google_id");
            $stmt->execute([':google_id' => $googleId]);
            $user = $stmt->fetch();
            
            if ($user) {
                // User exists with Google ID, log them in
                return $this->loginUser($user);
            }
            
            // Check if user exists by email
            $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Link Google account to existing user
                $stmt = $this->db->prepare("UPDATE users SET google_id = :google_id, profile_image = COALESCE(profile_image, :picture) WHERE id = :id");
                $stmt->execute([
                    ':google_id' => $googleId,
                    ':picture' => $googleUser['picture'] ?? null,
                    ':id' => $user['id']
                ]);
                
                // Refresh user data
                $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id");
                $stmt->execute([':id' => $user['id']]);
                $user = $stmt->fetch();
                
                return $this->loginUser($user);
            }
            
            // Create new user
            $firstName = $googleUser['given_name'] ?? explode(' ', $googleUser['name'] ?? 'User')[0];
            $lastName = $googleUser['family_name'] ?? (isset(explode(' ', $googleUser['name'] ?? '')[1]) ? explode(' ', $googleUser['name'])[1] : '');
            
            $stmt = $this->db->prepare("
                INSERT INTO users (first_name, last_name, email, google_id, profile_image, role, status, email_verified_at, created_at) 
                VALUES (:first_name, :last_name, :email, :google_id, :picture, 'student', 'active', NOW(), NOW())
            ");
            
            $stmt->execute([
                ':first_name' => $firstName,
                ':last_name' => $lastName,
                ':email' => $email,
                ':google_id' => $googleId,
                ':picture' => $googleUser['picture'] ?? null
            ]);
            
            $userId = $this->db->lastInsertId();
            
            // Get the new user
            $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id");
            $stmt->execute([':id' => $userId]);
            $user = $stmt->fetch();
            
            // Log activity
            $this->logActivity($userId, 'google_registration', 'User registered via Google');
            
            return $this->loginUser($user, true);
            
        } catch (PDOException $e) {
            error_log("Google OAuth database error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Database error. Please try again.'];
        }
    }
    
    /**
     * Create session and log in user
     */
    private function loginUser($user, $isNewUser = false) {
        // Check if account is suspended
        if ($user['status'] === 'suspended') {
            return ['success' => false, 'error' => 'Your account has been suspended. Please contact support.'];
        }
        
        if ($user['status'] === 'inactive') {
            return ['success' => false, 'error' => 'Your account is inactive. Please contact support.'];
        }
        
        // Create session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        
        // Update last login
        $stmt = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
        $stmt->execute([':id' => $user['id']]);
        
        // Store session in database
        $sessionToken = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);
        
        $stmt = $this->db->prepare("INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, expires_at) 
                VALUES (:user_id, :token, :ip, :user_agent, :expires_at)");
        $stmt->execute([
            ':user_id' => $user['id'],
            ':token' => $sessionToken,
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            ':expires_at' => $expiresAt
        ]);
        
        // Log activity
        $this->logActivity($user['id'], 'google_login', 'User logged in via Google');
        
        // Get redirect URL
        $redirect = $this->getRedirectUrl($user['role']);
        
        return [
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'name' => $user['first_name'] . ' ' . $user['last_name'],
                'email' => $user['email'],
                'role' => $user['role']
            ],
            'redirect' => $redirect,
            'is_new_user' => $isNewUser
        ];
    }
    
    /**
     * Log activity
     */
    private function logActivity($userId, $action, $description) {
        try {
            $stmt = $this->db->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent) 
                    VALUES (:user_id, :action, :description, :ip, :user_agent)");
            $stmt->execute([
                ':user_id' => $userId,
                ':action' => $action,
                ':description' => $description,
                ':ip' => $_SERVER['REMOTE_ADDR'] ?? '',
                ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
        } catch (PDOException $e) {
            error_log("Failed to log activity: " . $e->getMessage());
        }
    }
    
    /**
     * Get redirect URL based on role
     */
    private function getRedirectUrl($role) {
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
    
    /**
     * Check if Google OAuth is configured
     */
    public static function isConfigured() {
        return !empty(GOOGLE_CLIENT_ID) && !empty(GOOGLE_CLIENT_SECRET);
    }
}
