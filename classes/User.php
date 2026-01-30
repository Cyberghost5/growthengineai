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
}
