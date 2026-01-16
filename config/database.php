<?php
/**
 * GrowthEngineAI LMS - Database Configuration
 * 
 * This file contains the database connection settings.
 * Update these values according to your environment.
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'growthengine_lms');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Site configuration
define('SITE_NAME', 'GrowthEngineAI');
define('SITE_URL', 'https://growthengineai.org');
define('SITE_EMAIL', 'info@growthengineai.org');

// Define logo links
define('SITE_LOGO_LIGHT', SITE_URL . '/images/logo_ge.png');
define('SITE_LOGO_DARK', SITE_URL . '/images/logo-dark.png');

// Session configuration
define('SESSION_LIFETIME', 3600); // 1 hour

// Create database connection
class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    // Prevent cloning
    private function __clone() {}

    // Prevent unserialization
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

// Helper function to get database connection
function getDB() {
    return Database::getInstance()->getConnection();
}
