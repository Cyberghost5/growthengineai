<?php
/**
 * GrowthEngineAI LMS - Contact Form Handler
 * 
 * Processes contact form submissions and sends emails
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Mailer.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method not allowed';
    exit;
}

// Get form data
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$subject = trim($_POST['subject'] ?? $_POST['ubject'] ?? ''); // Handle typo in form
$message = trim($_POST['message'] ?? '');

// Validation
$errors = [];

if (empty($name)) {
    $errors[] = 'Please enter your name.';
} elseif (strlen($name) < 2) {
    $errors[] = 'Name must be at least 2 characters.';
} elseif (strlen($name) > 100) {
    $errors[] = 'Name must be less than 100 characters.';
}

if (empty($email)) {
    $errors[] = 'Please enter your email address.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please enter a valid email address.';
}

if (empty($subject)) {
    $errors[] = 'Please enter a subject.';
} elseif (strlen($subject) < 3) {
    $errors[] = 'Subject must be at least 3 characters.';
} elseif (strlen($subject) > 200) {
    $errors[] = 'Subject must be less than 200 characters.';
}

if (empty($message)) {
    $errors[] = 'Please enter your message.';
} elseif (strlen($message) < 10) {
    $errors[] = 'Message must be at least 10 characters.';
} elseif (strlen($message) > 5000) {
    $errors[] = 'Message must be less than 5000 characters.';
}

// Check for spam (honeypot and basic checks)
if (!empty($_POST['website'])) {
    // Honeypot field filled - likely spam
    echo 'OK';
    exit;
}

// Rate limiting (simple implementation using session)
session_start();
$now = time();
$cooldown = 60; // 1 minute between submissions

if (isset($_SESSION['last_contact_submission'])) {
    $timeSince = $now - $_SESSION['last_contact_submission'];
    if ($timeSince < $cooldown) {
        $waitTime = $cooldown - $timeSince;
        echo "Please wait {$waitTime} seconds before sending another message.";
        exit;
    }
}

// If there are validation errors, return them
if (!empty($errors)) {
    echo implode(' ', $errors);
    exit;
}

// Sanitize input for email
$name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$subject = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
$message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

// Build the email content
$htmlBody = buildContactEmailTemplate([
    'name' => $name,
    'email' => $email,
    'subject' => $subject,
    'message' => $message,
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
    'date' => date('F j, Y \a\t g:i A')
]);

// Send email to admin
$mailer = new Mailer();
$result = $mailer->send(
    SITE_EMAIL,
    'New Contact Form Submission: ' . $subject,
    $htmlBody,
    null,
    ['reply_to' => $email]
);

if ($result['success']) {
    // Update rate limiting
    $_SESSION['last_contact_submission'] = $now;
    
    // Optionally save to database
    saveContactSubmission($name, $email, $subject, $message);
    
    // Send auto-reply to user
    sendAutoReply($email, $name);
    
    // Return OK for validate.js
    echo 'OK';
} else {
    error_log("Contact form email failed: " . $result['message']);
    echo 'Sorry, there was an error sending your message. Please try again later.';
}

/**
 * Build styled email template for contact form
 */
function buildContactEmailTemplate($data) {
    $accentColor = '#2563eb';
    $headingColor = '#1a1a2e';
    $textColor = '#444444';
    
    return '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
    </head>
    <body style="margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; background-color: #f8f9fa;">
        <table role="presentation" style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 40px 20px;">
                    <table role="presentation" style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08); overflow: hidden;">
                        <!-- Header -->
                        <tr>
                            <td style="background-color: ' . $accentColor . '; padding: 30px 40px; text-align: center;">
                                <h1 style="margin: 0; color: #ffffff; font-size: 24px; font-weight: 700;">New Contact Form Submission</h1>
                            </td>
                        </tr>
                        <!-- Content -->
                        <tr>
                            <td style="padding: 40px;">
                                <h2 style="margin: 0 0 20px; color: ' . $headingColor . '; font-size: 20px;">Message Details</h2>
                                
                                <table style="width: 100%; border-collapse: collapse; margin-bottom: 30px;">
                                    <tr>
                                        <td style="padding: 12px 0; border-bottom: 1px solid #e5e7eb; color: #888; font-size: 14px; width: 120px;">From:</td>
                                        <td style="padding: 12px 0; border-bottom: 1px solid #e5e7eb; color: ' . $textColor . '; font-size: 14px; font-weight: 600;">' . $data['name'] . '</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 12px 0; border-bottom: 1px solid #e5e7eb; color: #888; font-size: 14px;">Email:</td>
                                        <td style="padding: 12px 0; border-bottom: 1px solid #e5e7eb; color: ' . $textColor . '; font-size: 14px;">
                                            <a href="mailto:' . $data['email'] . '" style="color: ' . $accentColor . '; text-decoration: none;">' . $data['email'] . '</a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 12px 0; border-bottom: 1px solid #e5e7eb; color: #888; font-size: 14px;">Subject:</td>
                                        <td style="padding: 12px 0; border-bottom: 1px solid #e5e7eb; color: ' . $textColor . '; font-size: 14px; font-weight: 600;">' . $data['subject'] . '</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 12px 0; border-bottom: 1px solid #e5e7eb; color: #888; font-size: 14px;">Date:</td>
                                        <td style="padding: 12px 0; border-bottom: 1px solid #e5e7eb; color: ' . $textColor . '; font-size: 14px;">' . $data['date'] . '</td>
                                    </tr>
                                </table>
                                
                                <h3 style="margin: 0 0 15px; color: ' . $headingColor . '; font-size: 16px;">Message:</h3>
                                <div style="background-color: #f8f9fa; border-radius: 8px; padding: 20px; color: ' . $textColor . '; font-size: 15px; line-height: 1.6;">
                                    ' . nl2br($data['message']) . '
                                </div>
                                
                                <p style="margin: 30px 0 0; text-align: center;">
                                    <a href="mailto:' . $data['email'] . '" style="display: inline-block; padding: 14px 32px; background-color: ' . $accentColor . '; color: #ffffff; text-decoration: none; font-size: 16px; font-weight: 600; border-radius: 8px;">Reply to ' . $data['name'] . '</a>
                                </p>
                                
                                <p style="margin: 30px 0 0; padding-top: 20px; border-top: 1px solid #e5e7eb; font-size: 12px; color: #888888;">
                                    <strong>Technical Info:</strong><br>
                                    IP Address: ' . $data['ip'] . '<br>
                                    User Agent: ' . substr($data['user_agent'], 0, 100) . '
                                </p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
    </html>';
}

/**
 * Send auto-reply to the user
 */
function sendAutoReply($email, $name) {
    $accentColor = '#2563eb';
    $headingColor = '#1a1a2e';
    $textColor = '#444444';
    
    $htmlBody = '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
    </head>
    <body style="margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; background-color: #f8f9fa;">
        <table role="presentation" style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 40px 20px;">
                    <table role="presentation" style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08); overflow: hidden;">
                        <!-- Header -->
                        <tr>
                            <td style="background-color: ' . $accentColor . '; padding: 30px 40px; text-align: center;">
                                <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 700;">' . SITE_NAME . '</h1>
                            </td>
                        </tr>
                        <!-- Content -->
                        <tr>
                            <td style="padding: 40px;">
                                <h2 style="margin: 0 0 20px; color: ' . $headingColor . '; font-size: 24px;">Thank you for contacting us!</h2>
                                
                                <p style="margin: 0 0 20px; color: ' . $textColor . '; font-size: 16px; line-height: 1.6;">
                                    Hi ' . htmlspecialchars($name) . ',
                                </p>
                                
                                <p style="margin: 0 0 20px; color: ' . $textColor . '; font-size: 16px; line-height: 1.6;">
                                    Thank you for reaching out to GrowthEngineAI! We\'ve received your message and our team will get back to you within 24 hours.
                                </p>
                                
                                <p style="margin: 0 0 20px; color: ' . $textColor . '; font-size: 16px; line-height: 1.6;">
                                    In the meantime, feel free to explore our courses and join our community:
                                </p>
                                
                                <p style="margin: 30px 0; text-align: center;">
                                    <a href="' . SITE_URL . '" style="display: inline-block; padding: 14px 32px; background-color: ' . $accentColor . '; color: #ffffff; text-decoration: none; font-size: 16px; font-weight: 600; border-radius: 8px;">Explore Courses</a>
                                </p>
                                
                                <p style="margin: 0 0 20px; color: ' . $textColor . '; font-size: 16px; line-height: 1.6;">
                                    Best regards,<br>
                                    <strong>The GrowthEngineAI Team</strong>
                                </p>
                                
                                <p style="margin: 30px 0 0; padding-top: 20px; border-top: 1px solid #e5e7eb; font-size: 13px; color: #888888; text-align: center;">
                                    &copy; ' . date('Y') . ' ' . SITE_NAME . '. All rights reserved.<br>
                                    <a href="' . SITE_URL . '" style="color: ' . $accentColor . '; text-decoration: none;">' . SITE_URL . '</a>
                                </p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
    </html>';
    
    $mailer = new Mailer();
    $mailer->send($email, 'Thank you for contacting ' . SITE_NAME, $htmlBody);
}

/**
 * Save contact submission to database (optional)
 */
function saveContactSubmission($name, $email, $subject, $message) {
    try {
        $db = Database::getInstance()->getConnection();
        
        // Check if table exists, if not create it
        $db->exec("CREATE TABLE IF NOT EXISTS contact_submissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(255) NOT NULL,
            subject VARCHAR(200) NOT NULL,
            message TEXT NOT NULL,
            ip_address VARCHAR(45),
            user_agent TEXT,
            status ENUM('new', 'read', 'replied', 'archived') DEFAULT 'new',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_email (email),
            INDEX idx_status (status),
            INDEX idx_created (created_at)
        )");
        
        $stmt = $db->prepare("INSERT INTO contact_submissions (name, email, subject, message, ip_address, user_agent) 
                              VALUES (:name, :email, :subject, :message, :ip, :ua)");
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':subject' => $subject,
            ':message' => $message,
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            ':ua' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
        
        return true;
    } catch (Exception $e) {
        error_log("Failed to save contact submission: " . $e->getMessage());
        return false;
    }
}
