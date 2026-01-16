<?php
/**
 * GrowthEngineAI LMS - Email Configuration
 * 
 * Configure your SMTP settings here for sending emails.
 * Supports Gmail, Mailgun, SendGrid, Amazon SES, and other SMTP providers.
 */

// Email sending mode: 'smtp', 'mail', or 'log' (for development)
define('MAIL_DRIVER', 'smtp'); // Change to 'smtp' for production

// SMTP Configuration
define('SMTP_HOST', 's3.whitelabelclouds.com');      // SMTP server hostname
define('SMTP_PORT', 465);                    // SMTP port (587 for TLS, 465 for SSL, 25 for none)
define('SMTP_ENCRYPTION', 'ssl');            // 'tls', 'ssl', or '' for none
define('SMTP_USERNAME', 'info@growthengineai.org');                 // SMTP username (usually your email)
define('SMTP_PASSWORD', 'Growthengine@2025');                 // SMTP password or app password
define('SMTP_TIMEOUT', 30);                  // Connection timeout in seconds

// Sender Configuration
define('MAIL_FROM_ADDRESS', SITE_EMAIL);     // Default from email address
define('MAIL_FROM_NAME', SITE_NAME);         // Default from name

// Debug mode (set to true to see SMTP debug output)
define('MAIL_DEBUG', false);

/**
 * SMTP Provider Examples:
 * 
 * Gmail:
 *   - Host: smtp.gmail.com
 *   - Port: 587 (TLS) or 465 (SSL)
 *   - Encryption: tls or ssl
 *   - Username: your-email@gmail.com
 *   - Password: Use App Password (not your regular password)
 *   - Note: Enable "Less secure apps" or use App Passwords with 2FA
 *   - Generate App Password: Google Account > Security > 2-Step Verification > App Passwords
 * 
 * Mailgun:
 *   - Host: smtp.mailgun.org
 *   - Port: 587
 *   - Encryption: tls
 *   - Username: postmaster@your-domain.mailgun.org
 *   - Password: Your Mailgun SMTP password
 * 
 * SendGrid:
 *   - Host: smtp.sendgrid.net
 *   - Port: 587
 *   - Encryption: tls
 *   - Username: apikey
 *   - Password: Your SendGrid API key
 * 
 * Amazon SES:
 *   - Host: email-smtp.us-east-1.amazonaws.com (or your region)
 *   - Port: 587
 *   - Encryption: tls
 *   - Username: Your SMTP username from SES
 *   - Password: Your SMTP password from SES
 * 
 * Outlook/Office 365:
 *   - Host: smtp.office365.com
 *   - Port: 587
 *   - Encryption: tls
 *   - Username: your-email@outlook.com
 *   - Password: Your password
 */
