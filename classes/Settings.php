<?php
/**
 * GrowthEngineAI LMS - Settings Manager
 * Handles system settings storage and retrieval
 */

require_once __DIR__ . '/../config/database.php';

class Settings {
    private $db;
    private static $cache = [];
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Get a setting value
     */
    public function get($key, $default = null) {
        // Check cache first
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }
        
        try {
            $sql = "SELECT setting_value, setting_type, is_encrypted FROM settings WHERE setting_key = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$key]);
            $result = $stmt->fetch();
            
            if (!$result) {
                return $default;
            }
            
            $value = $result['setting_value'];
            
            // Decrypt if needed (simple base64 for now, you can enhance this)
            if ($result['is_encrypted']) {
                $value = base64_decode($value);
            }
            
            // Convert type
            switch ($result['setting_type']) {
                case 'number':
                    $value = (float)$value;
                    break;
                case 'boolean':
                    $value = (bool)$value;
                    break;
                case 'json':
                    $value = json_decode($value, true);
                    break;
            }
            
            // Cache the value
            self::$cache[$key] = $value;
            
            return $value;
        } catch (PDOException $e) {
            error_log("Settings get error: " . $e->getMessage());
            return $default;
        }
    }
    
    /**
     * Set a setting value
     */
    public function set($key, $value, $type = 'text', $category = 'general', $description = '', $isEncrypted = false) {
        try {
            // Convert value based on type
            switch ($type) {
                case 'json':
                    $value = json_encode($value);
                    break;
                case 'boolean':
                    $value = $value ? '1' : '0';
                    break;
                default:
                    $value = (string)$value;
            }
            
            // Encrypt if needed
            if ($isEncrypted) {
                $value = base64_encode($value);
            }
            
            $sql = "INSERT INTO settings (setting_key, setting_value, setting_type, category, description, is_encrypted)
                    VALUES (?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                        setting_value = VALUES(setting_value),
                        setting_type = VALUES(setting_type),
                        category = VALUES(category),
                        description = VALUES(description),
                        is_encrypted = VALUES(is_encrypted)";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$key, $value, $type, $category, $description, $isEncrypted ? 1 : 0]);
            
            // Clear cache
            unset(self::$cache[$key]);
            
            return $result;
        } catch (PDOException $e) {
            error_log("Settings set error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all settings by category
     */
    public function getByCategory($category) {
        try {
            $sql = "SELECT setting_key, setting_value, setting_type, description, is_encrypted 
                    FROM settings 
                    WHERE category = ?
                    ORDER BY setting_key";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$category]);
            
            $settings = [];
            while ($row = $stmt->fetch()) {
                $value = $row['setting_value'];
                
                if ($row['is_encrypted']) {
                    $value = '***ENCRYPTED***'; // Don't expose encrypted values
                }
                
                $settings[$row['setting_key']] = [
                    'value' => $value,
                    'type' => $row['setting_type'],
                    'description' => $row['description'],
                    'is_encrypted' => $row['is_encrypted']
                ];
            }
            
            return $settings;
        } catch (PDOException $e) {
            error_log("Settings getByCategory error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Delete a setting
     */
    public function delete($key) {
        try {
            $sql = "DELETE FROM settings WHERE setting_key = ?";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$key]);
            
            // Clear cache
            unset(self::$cache[$key]);
            
            return $result;
        } catch (PDOException $e) {
            error_log("Settings delete error: " . $e->getMessage());
            return false;
        }
    }
}
