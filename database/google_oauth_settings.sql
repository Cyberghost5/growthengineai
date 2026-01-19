-- Add Google OAuth settings to the settings table
-- Run this file to add default Google OAuth configuration

INSERT INTO settings (setting_key, setting_value, setting_type, category, description, is_encrypted, created_at, updated_at) 
VALUES 
    ('google_client_id', '', 'text', 'oauth', 'Google OAuth Client ID', 0, NOW(), NOW()),
    ('google_client_secret', '', 'text', 'oauth', 'Google OAuth Client Secret', 0, NOW(), NOW()),
    ('google_oauth_enabled', '1', 'boolean', 'oauth', 'Enable Google OAuth Login', 0, NOW(), NOW())
ON DUPLICATE KEY UPDATE 
    setting_type = VALUES(setting_type),
    category = VALUES(category),
    description = VALUES(description),
    updated_at = NOW();
