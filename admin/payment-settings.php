<?php
/**
 * GrowthEngineAI LMS - Payment Settings
 * Admin page to manage Paystack configuration
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
    $publicKey = isset($_POST['public_key']) ? trim($_POST['public_key']) : '';
    $secretKey = isset($_POST['secret_key']) ? trim($_POST['secret_key']) : '';
    $mode = isset($_POST['mode']) ? trim($_POST['mode']) : 'test';
    $currency = isset($_POST['currency']) ? trim($_POST['currency']) : 'NGN';
    
    if ($publicKey && $secretKey) {
        $settings->set('paystack_public_key', $publicKey, 'text', 'payment', 'Paystack Public Key', false);
        $settings->set('paystack_secret_key', $secretKey, 'text', 'payment', 'Paystack Secret Key', false);
        $settings->set('paystack_mode', $mode, 'text', 'payment', 'Paystack Mode (test/live)', false);
        $settings->set('paystack_currency', $currency, 'text', 'payment', 'Payment Currency', false);
        
        $message = 'Payment settings updated successfully!';
        $messageType = 'success';
    } else {
        $message = 'Please fill in all required fields.';
        $messageType = 'danger';
    }
}

// Load current settings
$publicKey = $settings->get('paystack_public_key', '');
$secretKey = $settings->get('paystack_secret_key', '');
$mode = $settings->get('paystack_mode', 'test');
$currency = $settings->get('paystack_currency', 'NGN');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Settings - GrowthEngineAI Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-shield-lock me-2"></i>GrowthEngineAI Admin
            </a>
            <div>
                <a href="oauth-settings.php" class="btn btn-outline-light btn-sm me-2">
                    <i class="bi bi-google"></i> OAuth Settings
                </a>
                <a href="<?php echo Url::dashboard(); ?>" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="bi bi-credit-card me-2"></i>Payment Settings</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Paystack Mode</label>
                                <select name="mode" class="form-select" required>
                                    <option value="test" <?php echo $mode === 'test' ? 'selected' : ''; ?>>Test Mode</option>
                                    <option value="live" <?php echo $mode === 'live' ? 'selected' : ''; ?>>Live Mode</option>
                                </select>
                                <small class="form-text text-muted">Use Test mode for development and Live mode for production</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Public Key</label>
                                <input type="text" name="public_key" class="form-control" value="<?php echo htmlspecialchars($publicKey); ?>" required placeholder="pk_test_...">
                                <small class="form-text text-muted">Your Paystack public key (starts with pk_test_ or pk_live_)</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Secret Key</label>
                                <input type="password" name="secret_key" class="form-control" value="<?php echo htmlspecialchars($secretKey); ?>" required placeholder="sk_test_...">
                                <small class="form-text text-muted">Your Paystack secret key (starts with sk_test_ or sk_live_)</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Currency</label>
                                <select name="currency" class="form-select" required>
                                    <option value="NGN" <?php echo $currency === 'NGN' ? 'selected' : ''; ?>>NGN - Nigerian Naira</option>
                                    <option value="GHS" <?php echo $currency === 'GHS' ? 'selected' : ''; ?>>GHS - Ghanaian Cedi</option>
                                    <option value="ZAR" <?php echo $currency === 'ZAR' ? 'selected' : ''; ?>>ZAR - South African Rand</option>
                                    <option value="USD" <?php echo $currency === 'USD' ? 'selected' : ''; ?>>USD - US Dollar</option>
                                </select>
                            </div>

                            <div class="alert alert-info">
                                <strong><i class="bi bi-info-circle me-2"></i>How to get your API keys:</strong>
                                <ol class="mb-0 mt-2">
                                    <li>Go to <a href="https://dashboard.paystack.com/#/settings/developer" target="_blank">Paystack Dashboard</a></li>
                                    <li>Navigate to Settings → API Keys & Webhooks</li>
                                    <li>Copy your Public Key and Secret Key</li>
                                    <li>Use Test keys for development, Live keys for production</li>
                                </ol>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-save me-2"></i>Save Settings
                                </button>
                                <a href="<?php echo Url::base(); ?>/admin/dashboard.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card shadow mt-4">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="bi bi-test-tube me-2"></i>Test Card Details</h5>
                    </div>
                    <div class="card-body">
                        <p>Use these test card details in Test Mode:</p>
                        <table class="table table-sm">
                            <tr>
                                <td><strong>Card Number:</strong></td>
                                <td><code>4084 0840 8408 4081</code></td>
                            </tr>
                            <tr>
                                <td><strong>CVV:</strong></td>
                                <td><code>408</code></td>
                            </tr>
                            <tr>
                                <td><strong>Expiry:</strong></td>
                                <td>Any future date</td>
                            </tr>
                            <tr>
                                <td><strong>PIN:</strong></td>
                                <td><code>0000</code></td>
                            </tr>
                            <tr>
                                <td><strong>OTP:</strong></td>
                                <td><code>123456</code></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
