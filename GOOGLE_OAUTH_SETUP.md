# Google OAuth Database Setup

Google OAuth credentials are now stored in the database instead of config files.

## Quick Setup

### 1. Run the SQL migration
```sql
mysql -u root growthen_lms < database/google_oauth_settings.sql
```

Or manually run in phpMyAdmin/MySQL:
```sql
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
```

### 2. Configure OAuth credentials
Go to: http://localhost/growthengine/admin/oauth-settings.php

### 3. Get Google OAuth Credentials
1. Visit https://console.cloud.google.com/
2. Create/select a project
3. Go to APIs & Services → Credentials
4. Create OAuth client ID (Web application)
5. Add redirect URI: `http://localhost/growthengine/auth/google-callback`
6. Copy Client ID and Secret to admin panel

## File Changes
- ✅ `config/google.php` - Now loads from database
- ✅ `admin/oauth-settings.php` - New admin interface
- ✅ `database/google_oauth_settings.sql` - Database migration

## Benefits
- ✅ No sensitive data in config files
- ✅ Easy to update via admin panel
- ✅ Consistent with Paystack implementation
- ✅ GitHub-safe (no secrets in commits)
