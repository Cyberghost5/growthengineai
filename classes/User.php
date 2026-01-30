<?php
/**
 * GrowthEngineAI LMS - User Model
 * Provides admin-friendly user queries with enrollment stats.
 */

require_once __DIR__ . '/../config/database.php';

class User {
    private $db;

    public function __construct() {
        $this->db = getDB();
    }

    /**
     * Fetch paginated users with enrollment summary data.
     *
     * Supported filters:
     * - search (name/email)
     * - role (admin|student|tutor)
     * - status (active|inactive|suspended|pending)
     * - page (1-based)
     * - per_page (max 100)
     */
    public function getUsers(array $filters = []) {
        $page = isset($filters['page']) ? (int)$filters['page'] : 1;
        $page = $page > 0 ? $page : 1;

        $perPage = isset($filters['per_page']) ? (int)$filters['per_page'] : 20;
        $perPage = $perPage > 0 ? $perPage : 20;
        $perPage = min($perPage, 100);

        $search = trim($filters['search'] ?? '');
        $role = trim($filters['role'] ?? '');
        $status = trim($filters['status'] ?? '');

        $where = [];
        $params = [];

        if ($search !== '') {
            $where[] = "(u.first_name LIKE :search OR u.last_name LIKE :search OR u.email LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        if ($role !== '') {
            $where[] = "u.role = :role";
            $params[':role'] = $role;
        }

        if ($status !== '') {
            $where[] = "u.status = :status";
            $params[':status'] = $status;
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $countSql = "SELECT COUNT(*) FROM users u {$whereSql}";
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $offset = ($page - 1) * $perPage;

        $sql = "
            SELECT
                u.id,
                u.first_name,
                u.last_name,
                u.email,
                u.role,
                u.status,
                u.created_at,
                u.last_login,
                (
                    SELECT COUNT(*)
                    FROM enrollments e
                    WHERE e.user_id = u.id
                ) AS enrollments_count,
                (
                    SELECT MAX(e.enrolled_at)
                    FROM enrollments e
                    WHERE e.user_id = u.id
                ) AS last_enrolled_at,
                (
                    SELECT c.title
                    FROM enrollments e2
                    JOIN courses c ON e2.course_id = c.id
                    WHERE e2.user_id = u.id
                    ORDER BY e2.enrolled_at DESC
                    LIMIT 1
                ) AS latest_course_title
            FROM users u
            {$whereSql}
            ORDER BY u.created_at DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'users' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $perPage > 0 ? (int)ceil($total / $perPage) : 0
        ];
    }

    /**
     * Get a single user with latest session and enrollments.
     */
    public function getUserDetail($userId) {
        $stmt = $this->db->prepare("
            SELECT id, first_name, last_name, email, phone, role, status, profile_image,
                   bio, created_at, last_login
            FROM users
            WHERE id = ?
        ");
        $stmt->execute([(int)$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            return null;
        }

        $sessionStmt = $this->db->prepare("
            SELECT ip_address, user_agent, created_at, expires_at
            FROM user_sessions
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT 1
        ");
        $sessionStmt->execute([(int)$userId]);
        $latestSession = $sessionStmt->fetch();

        $enrollStmt = $this->db->prepare("
            SELECT
                e.id,
                e.enrolled_at,
                e.status,
                e.progress_percent,
                e.amount_paid,
                c.id AS course_id,
                c.title AS course_title,
                c.slug AS course_slug
            FROM enrollments e
            JOIN courses c ON e.course_id = c.id
            WHERE e.user_id = ?
            ORDER BY e.enrolled_at DESC
        ");
        $enrollStmt->execute([(int)$userId]);
        $enrollments = $enrollStmt->fetchAll();

        $logStmt = $this->db->prepare("
            SELECT action, description, ip_address, user_agent, created_at
            FROM activity_logs
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT 50
        ");
        $logStmt->execute([(int)$userId]);
        $activityLogs = $logStmt->fetchAll();

        return [
            'user' => $user,
            'latest_session' => $latestSession,
            'enrollments' => $enrollments,
            'activity_logs' => $activityLogs
        ];
    }

    /**
     * Update a user's role and status.
     */
    public function updateRoleStatus($userId, $role, $status) {
        $validRoles = ['admin', 'student', 'tutor'];
        $validStatuses = ['active', 'inactive', 'suspended', 'pending'];

        if (!in_array($role, $validRoles, true)) {
            return ['success' => false, 'message' => 'Invalid role selected.'];
        }

        if (!in_array($status, $validStatuses, true)) {
            return ['success' => false, 'message' => 'Invalid status selected.'];
        }

        $stmt = $this->db->prepare("UPDATE users SET role = :role, status = :status WHERE id = :id");
        $success = $stmt->execute([
            ':role' => $role,
            ':status' => $status,
            ':id' => (int)$userId
        ]);

        return ['success' => $success];
    }

    /**
     * Set a new password for a user.
     */
    public function setPassword($userId, $password) {
        if (strlen($password) < 8) {
            return ['success' => false, 'message' => 'Password must be at least 8 characters long.'];
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("UPDATE users SET password = :password WHERE id = :id");
        $success = $stmt->execute([
            ':password' => $hashedPassword,
            ':id' => (int)$userId
        ]);

        return ['success' => $success];
    }

    /**
     * Log admin activity.
     */
    public function logActivity($userId, $action, $description) {
        $stmt = $this->db->prepare("
            INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent)
            VALUES (:user_id, :action, :description, :ip, :user_agent)
        ");
        $stmt->execute([
            ':user_id' => $userId,
            ':action' => $action,
            ':description' => $description,
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    }
}
