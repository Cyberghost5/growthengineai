<?php
/**
 * GrowthEngineAI LMS - URL Helper
 * Generates clean, SEO-friendly URLs
 */

class Url {
    private static $baseUrl = null;
    
    /**
     * Get the base URL of the site
     */
    public static function base() {
        if (self::$baseUrl === null) {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            self::$baseUrl = $protocol . '://' . $host . '/growthengine1';
        }
        return self::$baseUrl;
    }
    
    /**
     * Generate a slug from a string
     */
    public static function slugify($text) {
        // Replace non-alphanumeric characters with hyphens
        $text = preg_replace('/[^a-z0-9]+/i', '-', strtolower(trim($text)));
        // Remove leading/trailing hyphens
        return trim($text, '-');
    }
    
    /**
     * Student section URLs
     */
    public static function student($page = 'dashboard') {
        return self::base() . '/student/' . $page;
    }
    
    /**
     * Dashboard URL
     */
    public static function dashboard() {
        return self::student('dashboard');
    }
    
    /**
     * Courses list URL
     */
    public static function courses() {
        return self::student('courses');
    }
    
    /**
     * Community URL
     */
    public static function community() {
        return self::student('community');
    }
    
    /**
     * Settings URL
     */
    public static function settings() {
        return self::student('settings');
    }
    
    /**
     * Single course URL
     * @param string $courseSlug The course slug
     * @param string $categorySlug Optional category slug for full path
     */
    public static function course($courseSlug, $categorySlug = null) {
        if ($categorySlug) {
            return self::student('course/' . $categorySlug . '/' . $courseSlug);
        }
        return self::student('course/' . $courseSlug);
    }
    
    /**
     * Learn/Lesson URL
     * @param string $courseSlug The course slug
     * @param string $lessonSlug Optional lesson slug
     */
    public static function learn($courseSlug, $lessonSlug = null) {
        $url = self::student('learn/' . $courseSlug);
        if ($lessonSlug) {
            $url .= '/lesson/' . $lessonSlug;
        }
        return $url;
    }
    
    /**
     * Quiz URL within a course
     */
    public static function quiz($courseSlug, $quizId) {
        return self::student('learn/' . $courseSlug . '/quiz/' . $quizId);
    }
    
    /**
     * Assignment URL within a course
     */
    public static function assignment($courseSlug, $assignmentId) {
        return self::student('learn/' . $courseSlug . '/assignment/' . $assignmentId);
    }
    
    /**
     * Enrollment URL
     */
    public static function enroll() {
        return self::student('enroll');
    }
    
    /**
     * Auth URLs
     */
    public static function login() {
        return self::base() . '/auth/login';
    }
    
    public static function register() {
        return self::base() . '/auth/register';
    }
    
    public static function logout() {
        return self::base() . '/auth/logout';
    }
    
    public static function forgotPassword() {
        return self::base() . '/auth/forgot-password';
    }
    
    /**
     * Generate a relative URL (for use within student pages)
     */
    public static function relative($path) {
        return $path;
    }
    
    /**
     * Get course URL from course data array
     */
    public static function courseFromData($course) {
        $slug = $course['slug'] ?? self::slugify($course['title'] ?? 'course');
        $categorySlug = $course['category_slug'] ?? null;
        return self::course($slug, $categorySlug);
    }
    
    /**
     * Get learn URL from course data array
     */
    public static function learnFromData($course, $lesson = null) {
        $courseSlug = $course['slug'] ?? self::slugify($course['title'] ?? 'course');
        $lessonSlug = null;
        
        if ($lesson) {
            $lessonSlug = $lesson['slug'] ?? self::slugify($lesson['title'] ?? 'lesson');
        }
        
        return self::learn($courseSlug, $lessonSlug);
    }
}
