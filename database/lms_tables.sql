-- =============================================
-- GrowthEngineAI LMS Database Schema
-- Created: January 16, 2026
-- Description: Complete LMS database structure
-- =============================================

-- Use the existing database
USE growthengine_lms;

-- =============================================
-- COURSE CATEGORIES
-- =============================================
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(50) DEFAULT 'bi-folder',
    color VARCHAR(7) DEFAULT '#000016',
    parent_id INT DEFAULT NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_slug (slug),
    INDEX idx_parent (parent_id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- INSTRUCTORS (extends users table)
-- =============================================
CREATE TABLE IF NOT EXISTS instructors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    bio TEXT,
    expertise TEXT,
    website VARCHAR(255),
    linkedin VARCHAR(255),
    twitter VARCHAR(255),
    youtube VARCHAR(255),
    total_students INT DEFAULT 0,
    total_courses INT DEFAULT 0,
    average_rating DECIMAL(3,2) DEFAULT 0.00,
    is_verified TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_verified (is_verified),
    INDEX idx_rating (average_rating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- COURSES
-- =============================================
CREATE TABLE IF NOT EXISTS courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instructor_id INT NOT NULL,
    category_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    subtitle VARCHAR(255),
    description TEXT,
    requirements TEXT,
    what_you_learn TEXT,
    target_audience TEXT,
    thumbnail VARCHAR(255),
    preview_video VARCHAR(255),
    level ENUM('beginner', 'intermediate', 'advanced', 'all_levels') DEFAULT 'beginner',
    language VARCHAR(50) DEFAULT 'English',
    duration_hours DECIMAL(5,1) DEFAULT 0.0,
    total_lessons INT DEFAULT 0,
    total_quizzes INT DEFAULT 0,
    total_assignments INT DEFAULT 0,
    price DECIMAL(10,2) DEFAULT 0.00,
    sale_price DECIMAL(10,2) DEFAULT NULL,
    is_free TINYINT(1) DEFAULT 0,
    is_featured TINYINT(1) DEFAULT 0,
    is_published TINYINT(1) DEFAULT 0,
    status ENUM('draft', 'pending_review', 'published', 'archived') DEFAULT 'draft',
    total_enrollments INT DEFAULT 0,
    average_rating DECIMAL(3,2) DEFAULT 0.00,
    total_reviews INT DEFAULT 0,
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (instructor_id) REFERENCES instructors(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
    INDEX idx_slug (slug),
    INDEX idx_instructor (instructor_id),
    INDEX idx_category (category_id),
    INDEX idx_status (status),
    INDEX idx_published (is_published),
    INDEX idx_featured (is_featured),
    INDEX idx_level (level),
    FULLTEXT idx_search (title, subtitle, description)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- COURSE TAGS
-- =============================================
CREATE TABLE IF NOT EXISTS tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    slug VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS course_tags (
    course_id INT NOT NULL,
    tag_id INT NOT NULL,
    PRIMARY KEY (course_id, tag_id),
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- COURSE MODULES (Sections)
-- =============================================
CREATE TABLE IF NOT EXISTS modules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    sort_order INT DEFAULT 0,
    duration_minutes INT DEFAULT 0,
    is_published TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    INDEX idx_course (course_id),
    INDEX idx_order (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- LESSONS
-- =============================================
CREATE TABLE IF NOT EXISTS lessons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    module_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    description TEXT,
    content_type ENUM('video', 'text', 'pdf', 'audio', 'embed', 'live') DEFAULT 'video',
    
    -- Video specific fields
    video_url VARCHAR(500),
    video_provider ENUM('youtube', 'vimeo', 'wistia', 'bunny', 'self_hosted', 'other') DEFAULT 'youtube',
    video_duration_seconds INT DEFAULT 0,
    
    -- Text/Article content
    text_content LONGTEXT,
    
    -- File attachments
    attachment_url VARCHAR(500),
    attachment_name VARCHAR(255),
    
    -- Lesson settings
    duration_minutes INT DEFAULT 0,
    sort_order INT DEFAULT 0,
    is_free_preview TINYINT(1) DEFAULT 0,
    is_published TINYINT(1) DEFAULT 1,
    is_downloadable TINYINT(1) DEFAULT 0,
    
    -- Completion requirements
    requires_completion TINYINT(1) DEFAULT 1,
    minimum_watch_percent INT DEFAULT 80,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
    INDEX idx_module (module_id),
    INDEX idx_order (sort_order),
    INDEX idx_type (content_type),
    INDEX idx_free (is_free_preview)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- LESSON RESOURCES (Downloadable files)
-- =============================================
CREATE TABLE IF NOT EXISTS lesson_resources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lesson_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    file_url VARCHAR(500) NOT NULL,
    file_type VARCHAR(50),
    file_size INT DEFAULT 0,
    download_count INT DEFAULT 0,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
    INDEX idx_lesson (lesson_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- QUIZZES
-- =============================================
CREATE TABLE IF NOT EXISTS quizzes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    module_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    instructions TEXT,
    
    -- Quiz settings
    time_limit_minutes INT DEFAULT NULL,
    passing_score INT DEFAULT 70,
    max_attempts INT DEFAULT 3,
    shuffle_questions TINYINT(1) DEFAULT 0,
    shuffle_answers TINYINT(1) DEFAULT 0,
    show_correct_answers TINYINT(1) DEFAULT 1,
    show_correct_answers_after ENUM('immediately', 'after_submission', 'after_passing', 'never') DEFAULT 'after_submission',
    
    -- Points and grading
    total_points INT DEFAULT 0,
    total_questions INT DEFAULT 0,
    
    -- Display settings
    questions_per_page INT DEFAULT NULL,
    sort_order INT DEFAULT 0,
    is_published TINYINT(1) DEFAULT 1,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
    INDEX idx_module (module_id),
    INDEX idx_order (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- QUIZ QUESTIONS
-- =============================================
CREATE TABLE IF NOT EXISTS quiz_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT NOT NULL,
    question_text TEXT NOT NULL,
    question_type ENUM('multiple_choice', 'multiple_answer', 'true_false', 'short_answer', 'essay', 'matching', 'fill_blank') DEFAULT 'multiple_choice',
    explanation TEXT,
    hint TEXT,
    points INT DEFAULT 1,
    sort_order INT DEFAULT 0,
    is_required TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
    INDEX idx_quiz (quiz_id),
    INDEX idx_order (sort_order),
    INDEX idx_type (question_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- QUIZ QUESTION OPTIONS (Answers)
-- =============================================
CREATE TABLE IF NOT EXISTS quiz_options (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT NOT NULL,
    option_text TEXT NOT NULL,
    is_correct TINYINT(1) DEFAULT 0,
    feedback TEXT,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (question_id) REFERENCES quiz_questions(id) ON DELETE CASCADE,
    INDEX idx_question (question_id),
    INDEX idx_correct (is_correct)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- ASSIGNMENTS
-- =============================================
CREATE TABLE IF NOT EXISTS assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    module_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    instructions LONGTEXT,
    
    -- Assignment settings
    due_days INT DEFAULT NULL,
    max_points INT DEFAULT 100,
    passing_points INT DEFAULT 60,
    max_file_size_mb INT DEFAULT 25,
    allowed_file_types VARCHAR(255) DEFAULT 'pdf,doc,docx,zip,py,ipynb,txt',
    max_submissions INT DEFAULT NULL,
    
    -- Grading
    grading_type ENUM('points', 'percentage', 'pass_fail', 'rubric') DEFAULT 'points',
    
    -- Display settings
    sort_order INT DEFAULT 0,
    is_published TINYINT(1) DEFAULT 1,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
    INDEX idx_module (module_id),
    INDEX idx_order (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- ASSIGNMENT RUBRICS
-- =============================================
CREATE TABLE IF NOT EXISTS assignment_rubrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NOT NULL,
    criteria VARCHAR(255) NOT NULL,
    description TEXT,
    max_points INT DEFAULT 10,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
    INDEX idx_assignment (assignment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- ENROLLMENTS
-- =============================================
CREATE TABLE IF NOT EXISTS enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    course_id INT NOT NULL,
    
    -- Enrollment details
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    
    -- Progress tracking
    progress_percent DECIMAL(5,2) DEFAULT 0.00,
    completed_lessons INT DEFAULT 0,
    completed_quizzes INT DEFAULT 0,
    completed_assignments INT DEFAULT 0,
    
    -- Completion
    is_completed TINYINT(1) DEFAULT 0,
    completed_at TIMESTAMP NULL,
    
    -- Certificate
    certificate_issued TINYINT(1) DEFAULT 0,
    certificate_id VARCHAR(100) UNIQUE,
    certificate_issued_at TIMESTAMP NULL,
    
    -- Status
    status ENUM('active', 'expired', 'suspended', 'refunded') DEFAULT 'active',
    
    -- Last activity
    last_accessed_at TIMESTAMP NULL,
    last_lesson_id INT NULL,
    
    -- Payment
    payment_id VARCHAR(100),
    amount_paid DECIMAL(10,2) DEFAULT 0.00,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_enrollment (user_id, course_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (last_lesson_id) REFERENCES lessons(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_course (course_id),
    INDEX idx_status (status),
    INDEX idx_completed (is_completed),
    INDEX idx_progress (progress_percent)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- LESSON PROGRESS
-- =============================================
CREATE TABLE IF NOT EXISTS lesson_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    lesson_id INT NOT NULL,
    enrollment_id INT NOT NULL,
    
    -- Progress tracking
    is_completed TINYINT(1) DEFAULT 0,
    completed_at TIMESTAMP NULL,
    
    -- Video progress
    watch_time_seconds INT DEFAULT 0,
    last_position_seconds INT DEFAULT 0,
    watch_percent DECIMAL(5,2) DEFAULT 0.00,
    
    -- Engagement
    notes TEXT,
    bookmarked TINYINT(1) DEFAULT 0,
    
    -- Timestamps
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_watched_at TIMESTAMP NULL,
    
    UNIQUE KEY unique_progress (user_id, lesson_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
    FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_lesson (lesson_id),
    INDEX idx_enrollment (enrollment_id),
    INDEX idx_completed (is_completed)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- QUIZ ATTEMPTS
-- =============================================
CREATE TABLE IF NOT EXISTS quiz_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    quiz_id INT NOT NULL,
    enrollment_id INT NOT NULL,
    
    -- Attempt details
    attempt_number INT DEFAULT 1,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    submitted_at TIMESTAMP NULL,
    time_spent_seconds INT DEFAULT 0,
    
    -- Scoring
    score DECIMAL(5,2) DEFAULT 0.00,
    points_earned INT DEFAULT 0,
    points_possible INT DEFAULT 0,
    is_passed TINYINT(1) DEFAULT 0,
    
    -- Status
    status ENUM('in_progress', 'submitted', 'graded', 'expired') DEFAULT 'in_progress',
    
    -- Answers stored as JSON
    answers JSON,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
    FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_quiz (quiz_id),
    INDEX idx_enrollment (enrollment_id),
    INDEX idx_status (status),
    INDEX idx_passed (is_passed)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- QUIZ ATTEMPT ANSWERS (Individual answers)
-- =============================================
CREATE TABLE IF NOT EXISTS quiz_attempt_answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    attempt_id INT NOT NULL,
    question_id INT NOT NULL,
    selected_option_ids JSON,
    text_answer TEXT,
    is_correct TINYINT(1) DEFAULT NULL,
    points_earned DECIMAL(5,2) DEFAULT 0.00,
    feedback TEXT,
    answered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (attempt_id) REFERENCES quiz_attempts(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES quiz_questions(id) ON DELETE CASCADE,
    INDEX idx_attempt (attempt_id),
    INDEX idx_question (question_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- ASSIGNMENT SUBMISSIONS
-- =============================================
CREATE TABLE IF NOT EXISTS assignment_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    assignment_id INT NOT NULL,
    enrollment_id INT NOT NULL,
    
    -- Submission details
    submission_number INT DEFAULT 1,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Files
    file_url VARCHAR(500),
    file_name VARCHAR(255),
    file_size INT DEFAULT 0,
    
    -- Text submission
    text_content LONGTEXT,
    
    -- Additional URLs (GitHub, etc.)
    submission_url VARCHAR(500),
    
    -- Grading
    status ENUM('submitted', 'under_review', 'graded', 'returned', 'resubmit_requested') DEFAULT 'submitted',
    points_earned DECIMAL(5,2) DEFAULT NULL,
    grade_percent DECIMAL(5,2) DEFAULT NULL,
    is_passed TINYINT(1) DEFAULT NULL,
    
    -- Feedback
    instructor_feedback LONGTEXT,
    graded_by INT NULL,
    graded_at TIMESTAMP NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE,
    FOREIGN KEY (graded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_assignment (assignment_id),
    INDEX idx_enrollment (enrollment_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- COURSE REVIEWS
-- =============================================
CREATE TABLE IF NOT EXISTS course_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    course_id INT NOT NULL,
    enrollment_id INT NOT NULL,
    
    rating TINYINT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    title VARCHAR(255),
    review_text TEXT,
    
    -- Helpfulness
    helpful_count INT DEFAULT 0,
    
    -- Status
    is_approved TINYINT(1) DEFAULT 1,
    is_featured TINYINT(1) DEFAULT 0,
    
    -- Instructor response
    instructor_response TEXT,
    responded_at TIMESTAMP NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_review (user_id, course_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE,
    INDEX idx_course (course_id),
    INDEX idx_rating (rating),
    INDEX idx_approved (is_approved)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- COURSE ANNOUNCEMENTS
-- =============================================
CREATE TABLE IF NOT EXISTS course_announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    instructor_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    is_pinned TINYINT(1) DEFAULT 0,
    send_email TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (instructor_id) REFERENCES instructors(id) ON DELETE CASCADE,
    INDEX idx_course (course_id),
    INDEX idx_pinned (is_pinned)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- DISCUSSION FORUMS
-- =============================================
CREATE TABLE IF NOT EXISTS discussions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    lesson_id INT NULL,
    user_id INT NOT NULL,
    
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    
    -- Status
    is_pinned TINYINT(1) DEFAULT 0,
    is_answered TINYINT(1) DEFAULT 0,
    is_closed TINYINT(1) DEFAULT 0,
    
    -- Stats
    reply_count INT DEFAULT 0,
    view_count INT DEFAULT 0,
    upvote_count INT DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_course (course_id),
    INDEX idx_lesson (lesson_id),
    INDEX idx_pinned (is_pinned),
    FULLTEXT idx_search (title, content)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- DISCUSSION REPLIES
-- =============================================
CREATE TABLE IF NOT EXISTS discussion_replies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    discussion_id INT NOT NULL,
    user_id INT NOT NULL,
    parent_reply_id INT NULL,
    
    content TEXT NOT NULL,
    
    is_answer TINYINT(1) DEFAULT 0,
    upvote_count INT DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (discussion_id) REFERENCES discussions(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_reply_id) REFERENCES discussion_replies(id) ON DELETE CASCADE,
    INDEX idx_discussion (discussion_id),
    INDEX idx_parent (parent_reply_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- CERTIFICATES
-- =============================================
CREATE TABLE IF NOT EXISTS certificates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    certificate_number VARCHAR(50) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    course_id INT NOT NULL,
    enrollment_id INT NOT NULL,
    
    -- Certificate details
    title VARCHAR(255) NOT NULL,
    student_name VARCHAR(255) NOT NULL,
    course_name VARCHAR(255) NOT NULL,
    instructor_name VARCHAR(255) NOT NULL,
    completion_date DATE NOT NULL,
    
    -- Certificate file
    pdf_url VARCHAR(500),
    image_url VARCHAR(500),
    
    -- Verification
    verification_url VARCHAR(500),
    is_valid TINYINT(1) DEFAULT 1,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_certificate (user_id, course_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE,
    INDEX idx_certificate_number (certificate_number),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- WISHLISTS
-- =============================================
CREATE TABLE IF NOT EXISTS wishlists (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    course_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_wishlist (user_id, course_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- COUPONS
-- =============================================
CREATE TABLE IF NOT EXISTS coupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255),
    
    discount_type ENUM('percentage', 'fixed') DEFAULT 'percentage',
    discount_value DECIMAL(10,2) NOT NULL,
    
    min_purchase DECIMAL(10,2) DEFAULT 0.00,
    max_discount DECIMAL(10,2) DEFAULT NULL,
    
    usage_limit INT DEFAULT NULL,
    usage_count INT DEFAULT 0,
    per_user_limit INT DEFAULT 1,
    
    -- Restrictions
    course_id INT NULL,
    category_id INT NULL,
    
    starts_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    is_active TINYINT(1) DEFAULT 1,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_code (code),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- NOTIFICATIONS
-- =============================================
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT,
    data JSON,
    
    is_read TINYINT(1) DEFAULT 0,
    read_at TIMESTAMP NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_read (is_read),
    INDEX idx_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- ACTIVITY LOG
-- =============================================
CREATE TABLE IF NOT EXISTS activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    activity_type VARCHAR(50) NOT NULL,
    description VARCHAR(255),
    entity_type VARCHAR(50),
    entity_id INT,
    metadata JSON,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_type (activity_type),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- INSERT SAMPLE CATEGORIES
-- =============================================
INSERT INTO categories (name, slug, description, icon, color, sort_order) VALUES
('Artificial Intelligence', 'artificial-intelligence', 'Learn AI concepts, machine learning, and neural networks', 'bi-robot', '#000016', 1),
('Machine Learning', 'machine-learning', 'Master ML algorithms and practical implementations', 'bi-cpu', '#6366f1', 2),
('Data Science', 'data-science', 'Data analysis, visualization, and statistical modeling', 'bi-graph-up', '#06b6d4', 3),
('Deep Learning', 'deep-learning', 'Neural networks, TensorFlow, PyTorch, and more', 'bi-diagram-3', '#ec4899', 4),
('Business Intelligence', 'business-intelligence', 'BI tools, dashboards, and data-driven decisions', 'bi-bar-chart-line', '#f59e0b', 5),
('Natural Language Processing', 'nlp', 'Text processing, sentiment analysis, and chatbots', 'bi-chat-dots', '#10b981', 6),
('Computer Vision', 'computer-vision', 'Image recognition, object detection, and video analysis', 'bi-eye', '#ef4444', 7),
('Python Programming', 'python-programming', 'Python for data science, automation, and web development', 'bi-code-slash', '#3b82f6', 8);

-- =============================================
-- INSERT SAMPLE TAGS
-- =============================================
INSERT INTO tags (name, slug) VALUES
('Python', 'python'),
('TensorFlow', 'tensorflow'),
('PyTorch', 'pytorch'),
('Pandas', 'pandas'),
('NumPy', 'numpy'),
('Scikit-learn', 'scikit-learn'),
('Keras', 'keras'),
('OpenCV', 'opencv'),
('NLP', 'nlp'),
('CNN', 'cnn'),
('RNN', 'rnn'),
('Transformers', 'transformers'),
('GPT', 'gpt'),
('BERT', 'bert'),
('SQL', 'sql'),
('Tableau', 'tableau'),
('Power BI', 'power-bi'),
('Statistics', 'statistics'),
('Mathematics', 'mathematics'),
('Beginner Friendly', 'beginner-friendly'),
('Advanced', 'advanced'),
('Hands-On Projects', 'hands-on-projects'),
('Certification', 'certification');

-- =============================================
-- VIEWS FOR COMMON QUERIES
-- =============================================

-- View: Course with full details
CREATE OR REPLACE VIEW v_course_details AS
SELECT 
    c.*,
    cat.name as category_name,
    cat.slug as category_slug,
    CONCAT(u.first_name, ' ', u.last_name) as instructor_name,
    u.profile_image as instructor_image,
    i.bio as instructor_bio,
    i.average_rating as instructor_rating,
    (SELECT COUNT(*) FROM modules m WHERE m.course_id = c.id) as module_count,
    (SELECT COUNT(*) FROM lessons l JOIN modules m ON l.module_id = m.id WHERE m.course_id = c.id) as lesson_count
FROM courses c
JOIN categories cat ON c.category_id = cat.id
JOIN instructors i ON c.instructor_id = i.id
JOIN users u ON i.user_id = u.id;

-- View: Student enrollment with progress
CREATE OR REPLACE VIEW v_student_enrollments AS
SELECT 
    e.*,
    c.title as course_title,
    c.slug as course_slug,
    c.thumbnail as course_thumbnail,
    c.total_lessons,
    cat.name as category_name,
    CONCAT(u.first_name, ' ', u.last_name) as instructor_name,
    (SELECT l.title FROM lessons l 
     JOIN modules m ON l.module_id = m.id 
     WHERE m.course_id = c.id 
     AND l.id NOT IN (SELECT lp.lesson_id FROM lesson_progress lp WHERE lp.user_id = e.user_id AND lp.is_completed = 1)
     ORDER BY m.sort_order, l.sort_order LIMIT 1) as next_lesson_title
FROM enrollments e
JOIN courses c ON e.course_id = c.id
JOIN categories cat ON c.category_id = cat.id
JOIN instructors i ON c.instructor_id = i.id
JOIN users u ON i.user_id = u.id;

-- =============================================
-- TRIGGERS FOR AUTOMATIC UPDATES
-- =============================================

-- Trigger: Update course stats when lesson is added
DELIMITER //
CREATE TRIGGER after_lesson_insert
AFTER INSERT ON lessons
FOR EACH ROW
BEGIN
    UPDATE courses c
    SET total_lessons = (
        SELECT COUNT(*) FROM lessons l 
        JOIN modules m ON l.module_id = m.id 
        WHERE m.course_id = c.id
    ),
    duration_hours = (
        SELECT COALESCE(SUM(l.duration_minutes), 0) / 60 
        FROM lessons l 
        JOIN modules m ON l.module_id = m.id 
        WHERE m.course_id = c.id
    )
    WHERE c.id = (SELECT course_id FROM modules WHERE id = NEW.module_id);
END//

-- Trigger: Update enrollment progress when lesson is completed
CREATE TRIGGER after_lesson_progress_update
AFTER UPDATE ON lesson_progress
FOR EACH ROW
BEGIN
    IF NEW.is_completed = 1 AND OLD.is_completed = 0 THEN
        UPDATE enrollments e
        SET 
            completed_lessons = (
                SELECT COUNT(*) FROM lesson_progress lp 
                WHERE lp.enrollment_id = NEW.enrollment_id AND lp.is_completed = 1
            ),
            progress_percent = (
                SELECT (COUNT(CASE WHEN lp.is_completed = 1 THEN 1 END) * 100.0 / COUNT(*))
                FROM lessons l
                JOIN modules m ON l.module_id = m.id
                JOIN enrollments en ON m.course_id = en.course_id
                LEFT JOIN lesson_progress lp ON lp.lesson_id = l.id AND lp.user_id = en.user_id
                WHERE en.id = NEW.enrollment_id
            ),
            last_accessed_at = NOW(),
            last_lesson_id = NEW.lesson_id
        WHERE e.id = NEW.enrollment_id;
    END IF;
END//

-- Trigger: Update course enrollment count
CREATE TRIGGER after_enrollment_insert
AFTER INSERT ON enrollments
FOR EACH ROW
BEGIN
    UPDATE courses 
    SET total_enrollments = total_enrollments + 1
    WHERE id = NEW.course_id;
    
    UPDATE instructors i
    SET total_students = (
        SELECT COUNT(DISTINCT e.user_id) 
        FROM enrollments e 
        JOIN courses c ON e.course_id = c.id 
        WHERE c.instructor_id = i.id
    )
    WHERE i.id = (SELECT instructor_id FROM courses WHERE id = NEW.course_id);
END//

-- Trigger: Update course rating when review is added
CREATE TRIGGER after_review_insert
AFTER INSERT ON course_reviews
FOR EACH ROW
BEGIN
    UPDATE courses 
    SET 
        average_rating = (SELECT AVG(rating) FROM course_reviews WHERE course_id = NEW.course_id AND is_approved = 1),
        total_reviews = (SELECT COUNT(*) FROM course_reviews WHERE course_id = NEW.course_id AND is_approved = 1)
    WHERE id = NEW.course_id;
END//

DELIMITER ;

-- =============================================
-- SUCCESS MESSAGE
-- =============================================
SELECT 'GrowthEngineAI LMS database tables created successfully!' AS status;
