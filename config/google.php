<?php
/**
 * GrowthEngineAI LMS - Google OAuth Configuration
 * 
 * Configure your Google OAuth credentials here.
 * 
 * To get your credentials:
 * 1. Go to https://console.cloud.google.com/
 * 2. Create a new project or select existing one
 * 3. Go to APIs & Services > Credentials
 * 4. Click "Create Credentials" > "OAuth client ID"
 * 5. Select "Web application"
 * 6. Add authorized redirect URI: http://localhost/growthengine1/auth/google-callback.php
 * 7. Copy the Client ID and Client Secret below
 */

// Google OAuth Credentials
define('GOOGLE_CLIENT_ID', '');     // Your Google Client ID
define('GOOGLE_CLIENT_SECRET', ''); // Your Google Client Secret
define('GOOGLE_REDIRECT_URI', SITE_URL . '/auth/google-callback.php');

// Google OAuth URLs
define('GOOGLE_AUTH_URL', 'https://accounts.google.com/o/oauth2/v2/auth');
define('GOOGLE_TOKEN_URL', 'https://oauth2.googleapis.com/token');
define('GOOGLE_USERINFO_URL', 'https://www.googleapis.com/oauth2/v2/userinfo');

// Scopes
define('GOOGLE_SCOPES', 'email profile');
