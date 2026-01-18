<?php
/**
 * GrowthEngineAI LMS - Course Model
 * Handles all course-related database operations
 */

require_once __DIR__ . '/../config/database.php';

class Course {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Get all published courses with category and instructor info
     */
    public function getAllCourses($filters = []) {
        $sql = "SELECT 
                    c.id, c.title, c.slug, c.subtitle, c.description, c.thumbnail,
                    c.level, c.duration_hours, c.total_lessons, c.price, c.sale_price,
                    c.is_free, c.average_rating, c.total_enrollments, c.total_reviews,
                    cat.name as category_name, cat.slug as category_slug,
                    CONCAT(u.first_name, ' ', u.last_name) as instructor_name,
                    u.profile_image as instructor_image,
                    i.bio as instructor_bio
                FROM courses c
                JOIN categories cat ON c.category_id = cat.id
                JOIN instructors i ON c.instructor_id = i.id
                JOIN users u ON i.user_id = u.id
                WHERE c.is_published = 1 AND c.status = 'published'";
        
        $params = [];
        
        if (!empty($filters['category'])) {
            $sql .= " AND cat.slug = ?";
            $params[] = $filters['category'];
        }
        
        if (!empty($filters['level'])) {
            $sql .= " AND c.level = ?";
            $params[] = $filters['level'];
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (c.title LIKE ? OR c.description LIKE ?)";
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }
        
        $sql .= " ORDER BY c.is_featured DESC, c.total_enrollments DESC";
        
        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . (int)$filters['limit'];
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get a single course by ID with full details
     */
    public function getCourseById($courseId) {
        $sql = "SELECT 
                    c.*,
                    cat.name as category_name, cat.slug as category_slug,
                    CONCAT(u.first_name, ' ', u.last_name) as instructor_name,
                    u.profile_image as instructor_image,
                    u.email as instructor_email,
                    i.bio as instructor_bio,
                    i.expertise as instructor_expertise,
                    i.website as instructor_website,
                    i.linkedin as instructor_linkedin,
                    i.total_students as instructor_total_students,
                    i.total_courses as instructor_total_courses,
                    i.average_rating as instructor_rating
                FROM courses c
                JOIN categories cat ON c.category_id = cat.id
                JOIN instructors i ON c.instructor_id = i.id
                JOIN users u ON i.user_id = u.id
                WHERE c.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$courseId]);
        $course = $stmt->fetch();
        
        if ($course) {
            // Decode JSON fields
            $course['what_you_learn'] = json_decode($course['what_you_learn'] ?? '[]', true) ?: [];
            $course['requirements'] = json_decode($course['requirements'] ?? '[]', true) ?: [];
            $course['target_audience'] = json_decode($course['target_audience'] ?? '[]', true) ?: [];
            
            // Get modules with lessons
            $course['modules'] = $this->getCourseModules($courseId);
        }
        
        return $course;
    }
    
    /**
     * Get course by slug
     */
    public function getCourseBySlug($slug) {
        $sql = "SELECT id FROM courses WHERE slug = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$slug]);
        $result = $stmt->fetch();
        
        if ($result) {
            return $this->getCourseById($result['id']);
        }
        return null;
    }
    
    /**
     * Get all modules for a course
     */
    public function getCourseModules($courseId) {
        $sql = "SELECT * FROM modules 
                WHERE course_id = ? AND is_published = 1 
                ORDER BY sort_order";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$courseId]);
        $modules = $stmt->fetchAll();
        
        foreach ($modules as &$module) {
            $module['lessons'] = $this->getModuleLessons($module['id']);
            $module['quiz'] = $this->getModuleQuiz($module['id']);
            $module['assignment'] = $this->getModuleAssignment($module['id']);
        }
        
        return $modules;
    }
    
    /**
     * Get lessons for a module
     */
    public function getModuleLessons($moduleId) {
        $sql = "SELECT 
                    l.*,
                    (SELECT COUNT(*) FROM lesson_resources WHERE lesson_id = l.id) as resource_count
                FROM lessons l
                WHERE l.module_id = ? AND l.is_published = 1
                ORDER BY l.sort_order";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$moduleId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get a single lesson by ID
     */
    public function getLessonById($lessonId) {
        $sql = "SELECT 
                    l.*,
                    m.course_id,
                    m.title as module_title,
                    c.title as course_title
                FROM lessons l
                JOIN modules m ON l.module_id = m.id
                JOIN courses c ON m.course_id = c.id
                WHERE l.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$lessonId]);
        $lesson = $stmt->fetch();
        
        if ($lesson) {
            $lesson['resources'] = $this->getLessonResources($lessonId);
        }
        
        return $lesson;
    }
    
    /**
     * Get lesson resources
     */
    public function getLessonResources($lessonId) {
        $sql = "SELECT * FROM lesson_resources 
                WHERE lesson_id = ? 
                ORDER BY sort_order";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$lessonId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get quiz for a module
     */
    public function getModuleQuiz($moduleId) {
        $sql = "SELECT * FROM quizzes 
                WHERE module_id = ? AND is_published = 1 
                ORDER BY sort_order 
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$moduleId]);
        return $stmt->fetch();
    }
    
    /**
     * Get quiz by ID with questions and options
     */
    public function getQuizById($quizId) {
        $sql = "SELECT 
                    q.*,
                    m.course_id,
                    m.title as module_title,
                    c.title as course_title
                FROM quizzes q
                JOIN modules m ON q.module_id = m.id
                JOIN courses c ON m.course_id = c.id
                WHERE q.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$quizId]);
        $quiz = $stmt->fetch();
        
        if ($quiz) {
            $quiz['questions'] = $this->getQuizQuestions($quizId);
        }
        
        return $quiz;
    }
    
    /**
     * Get quiz questions with options
     */
    public function getQuizQuestions($quizId) {
        $sql = "SELECT * FROM quiz_questions 
                WHERE quiz_id = ? 
                ORDER BY sort_order";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$quizId]);
        $questions = $stmt->fetchAll();
        
        foreach ($questions as &$question) {
            $question['options'] = $this->getQuestionOptions($question['id']);
        }
        
        return $questions;
    }
    
    /**
     * Get options for a question
     */
    public function getQuestionOptions($questionId) {
        $sql = "SELECT * FROM quiz_options 
                WHERE question_id = ? 
                ORDER BY sort_order";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$questionId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get assignment for a module
     */
    public function getModuleAssignment($moduleId) {
        $sql = "SELECT * FROM assignments 
                WHERE module_id = ? AND is_published = 1 
                ORDER BY sort_order 
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$moduleId]);
        return $stmt->fetch();
    }
    
    /**
     * Get assignment by ID
     */
    public function getAssignmentById($assignmentId) {
        $sql = "SELECT 
                    a.*,
                    m.course_id,
                    m.title as module_title,
                    c.title as course_title
                FROM assignments a
                JOIN modules m ON a.module_id = m.id
                JOIN courses c ON m.course_id = c.id
                WHERE a.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$assignmentId]);
        return $stmt->fetch();
    }
    
    /**
     * Get enrolled courses for a user
     */
    public function getEnrolledCourses($userId) {
        $sql = "SELECT 
                    c.id, c.title, c.slug, c.thumbnail, c.total_lessons,
                    cat.name as category_name,
                    CONCAT(u.first_name, ' ', u.last_name) as instructor_name,
                    e.progress_percent as progress,
                    e.completed_lessons,
                    e.enrolled_at,
                    e.last_accessed_at,
                    (SELECT l.title FROM lessons l 
                     JOIN modules m ON l.module_id = m.id 
                     LEFT JOIN lesson_progress lp ON l.id = lp.lesson_id AND lp.user_id = e.user_id
                     WHERE m.course_id = c.id AND (lp.is_completed IS NULL OR lp.is_completed = 0)
                     ORDER BY m.sort_order, l.sort_order LIMIT 1) as next_lesson
                FROM enrollments e
                JOIN courses c ON e.course_id = c.id
                JOIN categories cat ON c.category_id = cat.id
                JOIN instructors i ON c.instructor_id = i.id
                JOIN users u ON i.user_id = u.id
                WHERE e.user_id = ? AND e.status = 'active'
                ORDER BY e.last_accessed_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Check if user is enrolled in a course
     */
    public function isEnrolled($userId, $courseId) {
        $sql = "SELECT id FROM enrollments 
                WHERE user_id = ? AND course_id = ? AND status = 'active'";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $courseId]);
        return $stmt->fetch() !== false;
    }
    
    /**
     * Get enrollment details
     */
    public function getEnrollment($userId, $courseId) {
        $sql = "SELECT * FROM enrollments 
                WHERE user_id = ? AND course_id = ? AND status = 'active'";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $courseId]);
        return $stmt->fetch();
    }
    
    /**
     * Enroll a user in a course
     */
    public function enrollUser($userId, $courseId, $pricePaid = 0) {
        // Check if already enrolled
        if ($this->isEnrolled($userId, $courseId)) {
            return ['success' => false, 'message' => 'Already enrolled'];
        }
        
        $sql = "INSERT INTO enrollments (user_id, course_id, amount_paid, enrolled_at) 
                VALUES (?, ?, ?, NOW())";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $courseId, $pricePaid]);
        
        // Update course enrollment count
        $this->db->query("UPDATE courses SET total_enrollments = total_enrollments + 1 WHERE id = " . (int)$courseId);
        
        return ['success' => true, 'enrollment_id' => $this->db->lastInsertId()];
    }
    
    /**
     * Get lesson progress for a user
     */
    public function getLessonProgress($userId, $lessonId) {
        $sql = "SELECT * FROM lesson_progress 
                WHERE user_id = ? AND lesson_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $lessonId]);
        return $stmt->fetch();
    }
    
    /**
     * Get all lesson progress for a course
     */
    public function getCourseProgress($userId, $courseId) {
        $sql = "SELECT lp.* 
                FROM lesson_progress lp
                JOIN lessons l ON lp.lesson_id = l.id
                JOIN modules m ON l.module_id = m.id
                WHERE lp.user_id = ? AND m.course_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $courseId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Mark lesson as completed
     */
    public function markLessonComplete($userId, $lessonId) {
        // Get course ID from lesson to find enrollment
        $sql = "SELECT m.course_id 
                FROM lessons l 
                JOIN modules m ON l.module_id = m.id 
                WHERE l.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$lessonId]);
        $result = $stmt->fetch();
        
        if (!$result) return false;
        
        $courseId = $result['course_id'];
        
        // Get enrollment ID
        $enrollment = $this->getEnrollment($userId, $courseId);
        if (!$enrollment) return false;
        
        $enrollmentId = $enrollment['id'];
        
        // Check if progress exists
        $existing = $this->getLessonProgress($userId, $lessonId);
        
        if ($existing) {
            $sql = "UPDATE lesson_progress 
                    SET is_completed = 1, completed_at = NOW(), watch_time_seconds = duration_seconds
                    WHERE user_id = ? AND lesson_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $lessonId]);
        } else {
            $sql = "INSERT INTO lesson_progress (user_id, lesson_id, enrollment_id, is_completed, completed_at) 
                    VALUES (?, ?, ?, 1, NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $lessonId, $enrollmentId]);
        }
        
        // Update enrollment progress
        $this->updateEnrollmentProgress($userId, $lessonId);
        
        return true;
    }
    
    /**
     * Update enrollment progress after lesson completion
     */
    private function updateEnrollmentProgress($userId, $lessonId) {
        // Get course ID from lesson
        $sql = "SELECT m.course_id 
                FROM lessons l 
                JOIN modules m ON l.module_id = m.id 
                WHERE l.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$lessonId]);
        $result = $stmt->fetch();
        
        if (!$result) return;
        
        $courseId = $result['course_id'];
        
        // Count completed lessons
        $sql = "SELECT COUNT(*) as completed 
                FROM lesson_progress lp
                JOIN lessons l ON lp.lesson_id = l.id
                JOIN modules m ON l.module_id = m.id
                WHERE lp.user_id = ? AND m.course_id = ? AND lp.is_completed = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $courseId]);
        $completed = $stmt->fetch()['completed'];
        
        // Get total lessons
        $sql = "SELECT total_lessons FROM courses WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$courseId]);
        $total = $stmt->fetch()['total_lessons'];
        
        // Calculate progress
        $progress = $total > 0 ? round(($completed / $total) * 100) : 0;
        
        // Update enrollment
        $sql = "UPDATE enrollments 
                SET completed_lessons = ?, progress_percent = ?, last_accessed_at = NOW()
                WHERE user_id = ? AND course_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$completed, $progress, $userId, $courseId]);
    }
    
    /**
     * Get user's quiz attempts for a quiz
     */
    public function getQuizAttempts($userId, $quizId) {
        $sql = "SELECT * FROM quiz_attempts 
                WHERE user_id = ? AND quiz_id = ? 
                ORDER BY started_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $quizId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Submit a quiz attempt
     */
    public function submitQuiz($userId, $quizId, $answers) {
        // Get the quiz details
        $quiz = $this->getQuizById($quizId);
        if (!$quiz) {
            return ['success' => false, 'message' => 'Quiz not found'];
        }
        
        // Get course ID from quiz's module
        $sql = "SELECT m.course_id FROM quizzes q 
                JOIN modules m ON q.module_id = m.id 
                WHERE q.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$quizId]);
        $result = $stmt->fetch();
        
        if (!$result) {
            return ['success' => false, 'message' => 'Quiz module not found'];
        }
        
        $courseId = $result['course_id'];
        
        // Get enrollment
        $enrollment = $this->getEnrollment($userId, $courseId);
        if (!$enrollment) {
            return ['success' => false, 'message' => 'Not enrolled in this course'];
        }
        
        // Count previous attempts
        $sql = "SELECT COUNT(*) as count FROM quiz_attempts WHERE user_id = ? AND quiz_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $quizId]);
        $attemptNumber = $stmt->fetch()['count'] + 1;
        
        // Calculate score
        $pointsEarned = 0;
        $pointsPossible = 0;
        $gradedAnswers = [];
        
        foreach ($quiz['questions'] as $question) {
            $pointsPossible += $question['points'];
            $questionId = $question['id'];
            $userAnswer = $answers[$questionId] ?? null;
            
            $isCorrect = false;
            if ($userAnswer !== null) {
                // Find correct option
                foreach ($question['options'] as $option) {
                    if ($option['is_correct'] && $option['id'] == $userAnswer) {
                        $isCorrect = true;
                        $pointsEarned += $question['points'];
                        break;
                    }
                }
            }
            
            $gradedAnswers[$questionId] = [
                'selected' => $userAnswer,
                'is_correct' => $isCorrect,
                'points' => $isCorrect ? $question['points'] : 0
            ];
        }
        
        $score = $pointsPossible > 0 ? round(($pointsEarned / $pointsPossible) * 100, 2) : 0;
        $isPassed = $score >= ($quiz['passing_score'] ?? 70);
        
        // Insert quiz attempt
        $sql = "INSERT INTO quiz_attempts 
                (user_id, quiz_id, enrollment_id, attempt_number, submitted_at, 
                 score, points_earned, points_possible, is_passed, status, answers) 
                VALUES (?, ?, ?, ?, NOW(), ?, ?, ?, ?, 'graded', ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $userId, 
            $quizId, 
            $enrollment['id'], 
            $attemptNumber,
            $score,
            $pointsEarned,
            $pointsPossible,
            $isPassed ? 1 : 0,
            json_encode($gradedAnswers)
        ]);
        
        $attemptId = $this->db->lastInsertId();
        
        // Update enrollment quiz completion count if passed
        if ($isPassed) {
            $sql = "UPDATE enrollments SET completed_quizzes = completed_quizzes + 1 
                    WHERE id = ? AND user_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$enrollment['id'], $userId]);
        }
        
        return [
            'success' => true, 
            'score' => $score, 
            'passed' => $isPassed,
            'points_earned' => $pointsEarned,
            'points_possible' => $pointsPossible,
            'attempt_id' => $attemptId
        ];
    }
    
    /**
     * Submit an assignment
     */
    public function submitAssignment($userId, $assignmentId, $data) {
        // Get assignment details
        $assignment = $this->getAssignmentById($assignmentId);
        if (!$assignment) {
            return ['success' => false, 'message' => 'Assignment not found'];
        }
        
        // Get course ID from assignment's module
        $sql = "SELECT m.course_id FROM assignments a 
                JOIN modules m ON a.module_id = m.id 
                WHERE a.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$assignmentId]);
        $result = $stmt->fetch();
        
        if (!$result) {
            return ['success' => false, 'message' => 'Assignment module not found'];
        }
        
        $courseId = $result['course_id'];
        
        // Get enrollment
        $enrollment = $this->getEnrollment($userId, $courseId);
        if (!$enrollment) {
            return ['success' => false, 'message' => 'Not enrolled in this course'];
        }
        
        // Count previous submissions
        $sql = "SELECT COUNT(*) as count FROM assignment_submissions WHERE user_id = ? AND assignment_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $assignmentId]);
        $submissionNumber = $stmt->fetch()['count'] + 1;
        
        // Insert assignment submission
        $sql = "INSERT INTO assignment_submissions 
                (user_id, assignment_id, enrollment_id, submission_number, 
                 file_url, file_name, file_size, text_content, submission_url, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'submitted')";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $userId,
            $assignmentId,
            $enrollment['id'],
            $submissionNumber,
            $data['file_url'] ?? null,
            $data['file_name'] ?? null,
            $data['file_size'] ?? 0,
            $data['text_content'] ?? null,
            $data['submission_url'] ?? null
        ]);
        
        $submissionId = $this->db->lastInsertId();
        
        // Update enrollment assignment completion count
        $sql = "UPDATE enrollments SET completed_assignments = completed_assignments + 1 
                WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$enrollment['id'], $userId]);
        
        return [
            'success' => true,
            'submission_id' => $submissionId,
            'message' => 'Assignment submitted successfully'
        ];
    }
    
    /**
     * Get user's assignment submissions
     */
    public function getAssignmentSubmissions($userId, $assignmentId) {
        $sql = "SELECT * FROM assignment_submissions 
                WHERE user_id = ? AND assignment_id = ? 
                ORDER BY submitted_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $assignmentId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get all categories
     */
    public function getCategories() {
        $sql = "SELECT c.*, 
                (SELECT COUNT(*) FROM courses WHERE category_id = c.id AND is_published = 1) as course_count
                FROM categories c 
                WHERE c.is_active = 1 
                ORDER BY c.sort_order, c.name";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * Get featured courses
     */
    public function getFeaturedCourses($limit = 6) {
        $sql = "SELECT 
                    c.id, c.title, c.slug, c.thumbnail, c.level, 
                    c.duration_hours, c.price, c.average_rating, c.total_enrollments,
                    cat.name as category_name,
                    CONCAT(u.first_name, ' ', u.last_name) as instructor_name
                FROM courses c
                JOIN categories cat ON c.category_id = cat.id
                JOIN instructors i ON c.instructor_id = i.id
                JOIN users u ON i.user_id = u.id
                WHERE c.is_published = 1 AND c.is_featured = 1
                ORDER BY c.total_enrollments DESC
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get course statistics for dashboard
     */
    public function getUserCourseStats($userId) {
        // Total enrolled courses
        $sql = "SELECT COUNT(*) as total FROM enrollments WHERE user_id = ? AND status = 'active'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $totalEnrolled = $stmt->fetch()['total'];
        
        // Completed courses
        $sql = "SELECT COUNT(*) as total FROM enrollments WHERE user_id = ? AND status = 'completed'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $completedCourses = $stmt->fetch()['total'];
        
        // Total completed lessons
        $sql = "SELECT COUNT(*) as total FROM lesson_progress WHERE user_id = ? AND is_completed = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $completedLessons = $stmt->fetch()['total'];
        
        // Total certificates
        $sql = "SELECT COUNT(*) as total FROM certificates WHERE user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $certificates = $stmt->fetch()['total'];
        
        return [
            'enrolled_courses' => $totalEnrolled,
            'completed_courses' => $completedCourses,
            'completed_lessons' => $completedLessons,
            'certificates' => $certificates
        ];
    }
    
    /**
     * Get next lesson to continue for a user's course
     */
    public function getNextLesson($userId, $courseId) {
        $sql = "SELECT l.*, m.title as module_title
                FROM lessons l
                JOIN modules m ON l.module_id = m.id
                LEFT JOIN lesson_progress lp ON l.id = lp.lesson_id AND lp.user_id = ?
                WHERE m.course_id = ? AND l.is_published = 1
                AND (lp.is_completed IS NULL OR lp.is_completed = 0)
                ORDER BY m.sort_order, l.sort_order
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $courseId]);
        return $stmt->fetch();
    }
    
    /**
     * Get content item (lesson, quiz, or assignment) for learn page
     */
    public function getContentItem($type, $itemId) {
        switch ($type) {
            case 'lesson':
                return $this->getLessonById($itemId);
            case 'quiz':
                return $this->getQuizById($itemId);
            case 'assignment':
                return $this->getAssignmentById($itemId);
            default:
                return null;
        }
    }
    
    /**
     * Get all content items for a course (lessons, quizzes, assignments) in order
     */
    public function getCourseContent($courseId) {
        $content = [];
        $modules = $this->getCourseModules($courseId);
        
        foreach ($modules as $module) {
            // Add lessons
            foreach ($module['lessons'] as $lesson) {
                $content[] = [
                    'type' => 'lesson',
                    'id' => $lesson['id'],
                    'title' => $lesson['title'],
                    'module_id' => $module['id'],
                    'module_title' => $module['title'],
                    'duration' => $lesson['duration_minutes'] . ' min',
                    'content_type' => $lesson['content_type']
                ];
            }
            
            // Add quiz if exists
            if ($module['quiz']) {
                $content[] = [
                    'type' => 'quiz',
                    'id' => $module['quiz']['id'],
                    'title' => $module['quiz']['title'],
                    'module_id' => $module['id'],
                    'module_title' => $module['title'],
                    'duration' => $module['quiz']['total_questions'] . ' questions',
                    'content_type' => 'quiz'
                ];
            }
            
            // Add assignment if exists
            if ($module['assignment']) {
                $content[] = [
                    'type' => 'assignment',
                    'id' => $module['assignment']['id'],
                    'title' => $module['assignment']['title'],
                    'module_id' => $module['id'],
                    'module_title' => $module['title'],
                    'duration' => $module['assignment']['due_days'] . ' days',
                    'content_type' => 'assignment'
                ];
            }
        }
        
        return $content;
    }
}
