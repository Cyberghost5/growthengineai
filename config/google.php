<?php
/**
 * GrowthEngineAI LMS - Google OAuth Configuration
 * 
 * Google OAuth credentials are now stored in the database.
 * Configure them via the admin panel at: /admin/oauth-settings.php
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../classes/Settings.php';

$settings = new Settings();

// Google OAuth Credentials (from database)
define('GOOGLE_CLIENT_ID', $settings->get('google_client_id', ''));
define('GOOGLE_CLIENT_SECRET', $settings->get('google_client_secret', ''));
define('GOOGLE_REDIRECT_URI', SITE_URL . '/auth/google-callback');

// Google OAuth URLs
define('GOOGLE_AUTH_URL', 'https://accounts.google.com/o/oauth2/v2/auth');
define('GOOGLE_TOKEN_URL', 'https://oauth2.googleapis.com/token');
define('GOOGLE_USERINFO_URL', 'https://www.googleapis.com/oauth2/v2/userinfo');

// Scopes
define('GOOGLE_SCOPES', 'email profile');
