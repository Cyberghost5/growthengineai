<?php
/**
 * GrowthEngineAI LMS - OAuth Settings
 * Admin page to manage Google OAuth configuration
 */

require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Settings.php';
require_once __DIR__ . '/../classes/Url.php';

$auth = new Auth();
$auth->requireRole('admin');

$settings = new Settings();
$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clientId = isset($_POST['client_id']) ? trim($_POST['client_id']) : '';
    $clientSecret = isset($_POST['client_secret']) ? trim($_POST['client_secret']) : '';
    $enabled = isset($_POST['enabled']) ? '1' : '0';
    
    if ($clientId && $clientSecret) {
        $settings->set('google_client_id', $clientId, 'text', 'oauth', 'Google OAuth Client ID', false);
        $settings->set('google_client_secret', $clientSecret, 'text', 'oauth', 'Google OAuth Client Secret', false);
        $settings->set('google_oauth_enabled', $enabled, 'boolean', 'oauth', 'Enable Google OAuth Login', false);
        
        $message = 'OAuth settings updated successfully!';
        $messageType = 'success';
    } else {
        $message = 'Please fill in all required fields.';
        $messageType = 'danger';
    }
}

// Load current settings
$clientId = $settings->get('google_client_id', '');
$clientSecret = $settings->get('google_client_secret', '');
$enabled = $settings->get('google_oauth_enabled', true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OAuth Settings - GrowthEngineAI Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .settings-card {
            max-width: 800px;
            margin: 0 auto;
        }
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #0d6efd;
            padding: 15px;
            margin-bottom: 20px;
        }
        .step-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            background: #0d6efd;
            color: white;
            border-radius: 50%;
            font-weight: bold;
            font-size: 14px;
            margin-right: 10px;
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-shield-lock me-2"></i>GrowthEngineAI Admin
            </a>
            <div>
                <a href="payment-settings.php" class="btn btn-outline-light btn-sm me-2">
                    <i class="bi bi-credit-card"></i> Payment Settings
                </a>
                <a href="<?php echo Url::dashboard(); ?>" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="settings-card">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="bi bi-google me-2"></i>Google OAuth Settings</h4>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <div class="info-box">
                        <h6 class="mb-3"><i class="bi bi-info-circle me-2"></i>How to Get Google OAuth Credentials</h6>
                        <ol class="mb-0 ps-3">
                            <li class="mb-2">
                                <span class="step-number">1</span>
                                Go to <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a>
                            </li>
                            <li class="mb-2">
                                <span class="step-number">2</span>
                                Create a new project or select an existing one
                            </li>
                            <li class="mb-2">
                                <span class="step-number">3</span>
                                Navigate to <strong>APIs & Services → Credentials</strong>
                            </li>
                            <li class="mb-2">
                                <span class="step-number">4</span>
                                Click <strong>Create Credentials → OAuth client ID</strong>
                            </li>
                            <li class="mb-2">
                                <span class="step-number">5</span>
                                Select <strong>Web application</strong>
                            </li>
                            <li class="mb-2">
                                <span class="step-number">6</span>
                                Add authorized redirect URI: 
                                <code class="bg-white px-2 py-1 rounded"><?php echo SITE_URL; ?>/auth/google-callback</code>
                                <button class="btn btn-sm btn-outline-secondary ms-2" onclick="copyToClipboard('<?php echo SITE_URL; ?>/auth/google-callback')">
                                    <i class="bi bi-clipboard"></i> Copy
                                </button>
                            </li>
                            <li>
                                <span class="step-number">7</span>
                                Copy the Client ID and Client Secret and paste below
                            </li>
                        </ol>
                    </div>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="bi bi-toggle-on me-1"></i>Enable Google OAuth Login
                            </label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="enabled" id="oauthEnabled" <?php echo $enabled ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="oauthEnabled">
                                    Allow users to login with their Google account
                                </label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                <i class="bi bi-key me-1"></i>Google Client ID
                            </label>
                            <input type="text" name="client_id" class="form-control font-monospace" 
                                   value="<?php echo htmlspecialchars($clientId); ?>" 
                                   required 
                                   placeholder="123456789-abcdefghijklmnop.apps.googleusercontent.com">
                            <small class="form-text text-muted">
                                Your Google OAuth Client ID (ends with .apps.googleusercontent.com)
                            </small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                <i class="bi bi-shield-lock me-1"></i>Google Client Secret
                            </label>
                            <input type="password" name="client_secret" class="form-control font-monospace" 
                                   value="<?php echo htmlspecialchars($clientSecret); ?>" 
                                   required 
                                   placeholder="GOCSPX-xxxxxxxxxxxxxxxxxxxxx">
                            <small class="form-text text-muted">
                                Your Google OAuth Client Secret (starts with GOCSPX-)
                            </small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                <i class="bi bi-link-45deg me-1"></i>Redirect URI (Read Only)
                            </label>
                            <div class="input-group">
                                <input type="text" class="form-control font-monospace" 
                                       value="<?php echo SITE_URL; ?>/auth/google-callback" 
                                       readonly>
                                <button class="btn btn-outline-secondary" type="button" 
                                        onclick="copyToClipboard('<?php echo SITE_URL; ?>/auth/google-callback')">
                                    <i class="bi bi-clipboard"></i> Copy
                                </button>
                            </div>
                            <small class="form-text text-muted">
                                Use this URL as the authorized redirect URI in Google Cloud Console
                            </small>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-check-circle me-2"></i>Update OAuth Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <?php if ($clientId && $clientSecret): ?>
            <div class="card shadow mt-4">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0"><i class="bi bi-check-circle me-2"></i>Current Configuration</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr>
                            <td width="200"><strong>Status:</strong></td>
                            <td>
                                <?php if ($enabled): ?>
                                    <span class="badge bg-success">Enabled</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Disabled</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Client ID:</strong></td>
                            <td><code><?php echo htmlspecialchars(substr($clientId, 0, 20)) . '...' . substr($clientId, -10); ?></code></td>
                        </tr>
                        <tr>
                            <td><strong>Client Secret:</strong></td>
                            <td><code><?php echo str_repeat('•', 20) . substr($clientSecret, -4); ?></code></td>
                        </tr>
                        <tr>
                            <td><strong>Redirect URI:</strong></td>
                            <td><code><?php echo SITE_URL; ?>/auth/google-callback</code></td>
                        </tr>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                // Show success feedback
                const btn = event.target.closest('button');
                const originalHTML = btn.innerHTML;
                btn.innerHTML = '<i class="bi bi-check"></i> Copied!';
                btn.classList.add('btn-success');
                btn.classList.remove('btn-outline-secondary');
                
                setTimeout(() => {
                    btn.innerHTML = originalHTML;
                    btn.classList.remove('btn-success');
                    btn.classList.add('btn-outline-secondary');
                }, 2000);
            });
        }
    </script>
</body>
</html>
