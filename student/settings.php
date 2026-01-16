<?php
/**
 * GrowthEngineAI LMS - Student Settings / Profile Update
 */

require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Url.php';
require_once __DIR__ . '/../config/database.php';

$auth = new Auth();
$auth->requireRole('student');

$user = $auth->getCurrentUser();
$db = getDB();

$success = '';
$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        // Update profile information
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        
        // Validate
        if (empty($firstName)) {
            $errors[] = 'First name is required.';
        }
        if (empty($lastName)) {
            $errors[] = 'Last name is required.';
        }
        
        if (empty($errors)) {
            try {
                $sql = "UPDATE users SET first_name = :first_name, last_name = :last_name, phone = :phone, bio = :bio WHERE id = :id";
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    ':first_name' => $firstName,
                    ':last_name' => $lastName,
                    ':phone' => $phone,
                    ':bio' => $bio,
                    ':id' => $user['id']
                ]);
                
                $success = 'Profile updated successfully!';
                
                // Refresh user data
                $user = $auth->getCurrentUser();
            } catch (PDOException $e) {
                $errors[] = 'Failed to update profile. Please try again.';
            }
        }
    }
    
    if ($action === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validate
        if (empty($currentPassword)) {
            $errors[] = 'Current password is required.';
        }
        if (empty($newPassword)) {
            $errors[] = 'New password is required.';
        } elseif (strlen($newPassword) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        }
        if ($newPassword !== $confirmPassword) {
            $errors[] = 'New passwords do not match.';
        }
        
        // Verify current password
        if (empty($errors)) {
            if (!password_verify($currentPassword, $user['password'])) {
                $errors[] = 'Current password is incorrect.';
            }
        }
        
        if (empty($errors)) {
            try {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET password = :password WHERE id = :id";
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    ':password' => $hashedPassword,
                    ':id' => $user['id']
                ]);
                
                $success = 'Password changed successfully!';
            } catch (PDOException $e) {
                $errors[] = 'Failed to change password. Please try again.';
            }
        }
    }
    
    if ($action === 'upload_photo') {
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['profile_photo'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $maxSize = 5 * 1024 * 1024; // 5MB
            
            if (!in_array($file['type'], $allowedTypes)) {
                $errors[] = 'Invalid file type. Please upload a JPG, PNG, GIF, or WebP image.';
            } elseif ($file['size'] > $maxSize) {
                $errors[] = 'File is too large. Maximum size is 5MB.';
            } else {
                // Create uploads directory if it doesn't exist
                $uploadDir = __DIR__ . '/../uploads/profiles/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                // Generate unique filename
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'user_' . $user['id'] . '_' . time() . '.' . $extension;
                $filepath = $uploadDir . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $filepath)) {
                    // Delete old profile image if exists
                    if (!empty($user['profile_image'])) {
                        $oldFile = $uploadDir . $user['profile_image'];
                        if (file_exists($oldFile)) {
                            unlink($oldFile);
                        }
                    }
                    
                    // Update database
                    $sql = "UPDATE users SET profile_image = :image WHERE id = :id";
                    $stmt = $db->prepare($sql);
                    $stmt->execute([
                        ':image' => $filename,
                        ':id' => $user['id']
                    ]);
                    
                    $success = 'Profile photo updated successfully!';
                    $user = $auth->getCurrentUser();
                } else {
                    $errors[] = 'Failed to upload file. Please try again.';
                }
            }
        } else {
            $errors[] = 'Please select a file to upload.';
        }
    }
}

// Get profile image URL
$profileImage = !empty($user['profile_image']) 
    ? '../uploads/profiles/' . $user['profile_image'] 
    : 'https://ui-avatars.com/api/?name=' . urlencode($user['first_name'] . '+' . $user['last_name']) . '&size=150&background=8b5cf6&color=fff';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - GrowthEngineAI</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../images/favicon.png" rel="icon">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: #f1f5f9;
        }
        .dashboard-header {
            background: linear-gradient(135deg, #000016 0%, #00212d 100%);
            color: white;
            padding: 30px;
            margin-bottom: 30px;
        }
        .welcome-text h1 {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
        }
        /* Sidebar Toggle Button */
        .sidebar-toggle-btn {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1001;
            width: 45px;
            height: 45px;
            border-radius: 10px;
            background: white;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.15);
            color: #000016;
            font-size: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .sidebar-toggle-btn:hover {
            background: linear-gradient(135deg, #000016 0%, #00212d 100%);
            color: white;
        }
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1002;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        .sidebar-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        .sidebar {
            position: fixed;
            top: 0;
            left: -280px;
            width: 280px;
            height: 100vh;
            background: white;
            box-shadow: 2px 0 20px rgba(0,0,0,0.1);
            padding: 20px;
            z-index: 1003;
            transition: left 0.3s ease;
            overflow-y: auto;
        }
        .sidebar.active {
            left: 0;
        }
        .sidebar-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 20px;
            margin-bottom: 20px;
            border-bottom: 1px solid #e2e8f0;
        }
        .sidebar-logo {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            font-size: 18px;
            color: #000016;
        }
        .sidebar-close {
            background: none;
            border: none;
            font-size: 24px;
            color: #64748b;
            cursor: pointer;
            padding: 0;
            line-height: 1;
        }
        .sidebar-close:hover {
            color: #000016;
        }
        .sidebar .nav-link {
            color: #64748b;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 4px;
            transition: all 0.3s ease;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: linear-gradient(135deg, #000016 0%, #00212d 100%);
            color: white;
        }
        .sidebar .nav-link i {
            width: 24px;
        }
        .sidebar-footer {
            margin-top: auto;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }
        .sidebar-footer .nav-link {
            color: #ef4444;
        }
        .sidebar-footer .nav-link:hover {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }
        .sidebar-nav {
            display: flex;
            flex-direction: column;
            height: calc(100% - 80px);
        }
        .sidebar-nav .nav {
            flex: 1;
        }
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        .card-header {
            background: white;
            border-bottom: 1px solid #e2e8f0;
            padding: 20px 24px;
            font-weight: 600;
            font-size: 18px;
            color: #1e293b;
        }
        .card-body {
            padding: 24px;
        }
        .profile-photo-container {
            text-align: center;
            padding: 30px;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-radius: 12px;
            margin-bottom: 24px;
        }
        .profile-photo {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 16px;
        }
        .profile-name {
            font-size: 20px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 4px;
        }
        .profile-email {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 16px;
        }
        .btn-upload {
            background: linear-gradient(135deg, #000016 0%, #00212d 100%);
            border: none;
            color: white;
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-upload:hover {
            background: linear-gradient(135deg, #000016 0%, #00212d 100%);
            color: white;
            transform: translateY(-2px);
        }
        .form-label {
            font-weight: 500;
            color: #374151;
            margin-bottom: 8px;
        }
        .form-control {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px 16px;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #000016;
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
        }
        .btn-save {
            background: linear-gradient(135deg, #000016 0%, #00212d 100%);
            border: none;
            color: white;
            padding: 12px 32px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-save:hover {
            background: linear-gradient(135deg, #000016 0%, #00212d 100%);
            color: white;
            transform: translateY(-2px);
        }
        .settings-nav {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }
        .settings-nav .nav-btn {
            padding: 10px 20px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            background: white;
            color: #64748b;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .settings-nav .nav-btn:hover,
        .settings-nav .nav-btn.active {
            background: linear-gradient(135deg, #000016 0%, #00212d 100%);
            color: white;
            border-color: transparent;
        }
        .section-title {
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 20px;
        }
        .alert {
            border-radius: 8px;
            padding: 16px 20px;
        }
        .account-info {
            background: #f8fafc;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 16px;
        }
        .account-info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .account-info-row:last-child {
            border-bottom: none;
        }
        .account-info-label {
            color: #64748b;
            font-size: 14px;
        }
        .account-info-value {
            color: #1e293b;
            font-weight: 500;
            font-size: 14px;
        }
        .password-requirements {
            background: #fef3c7;
            border-radius: 8px;
            padding: 16px;
            margin-top: 16px;
        }
        .password-requirements h6 {
            color: #92400e;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .password-requirements ul {
            margin: 0;
            padding-left: 20px;
        }
        .password-requirements li {
            color: #92400e;
            font-size: 13px;
            margin-bottom: 4px;
        }
    </style>
</head>
<body>
    <div class="dashboard-header">
        <div class="container-fluid px-4">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <button class="sidebar-toggle-btn" onclick="toggleSidebar()">
                        <i class="bi bi-list"></i>
                    </button>
                </div>
                <div class="welcome-text">
                    <h1><i class="bi bi-gear me-2"></i>Settings</h1>
                    <p class="mb-0">Manage your account and profile settings</p>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="badge bg-light text-purple px-3 py-2" style="color: #000016 !important;">
                        <i class="bi bi-mortarboard me-1"></i> Student
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid px-4">
        <div class="row g-4">
            <!-- Sidebar Overlay -->
            <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
            
            <!-- Sidebar -->
            <div class="sidebar" id="sidebar">
                <div class="sidebar-header">
                    <span class="sidebar-logo"><img src="../images/logo_ge.png" alt="" width="150px"></span>
                    <button class="sidebar-close" onclick="toggleSidebar()">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
                <div class="sidebar-nav">
                    <nav class="nav flex-column">
                        <a class="nav-link" href="<?php echo Url::dashboard(); ?>">
                            <i class="bi bi-grid-1x2"></i> Dashboard
                        </a>
                        <a class="nav-link" href="<?php echo Url::courses(); ?>">
                            <i class="bi bi-book"></i> My Courses
                        </a>
                        <a class="nav-link" href="<?php echo Url::community(); ?>">
                            <i class="bi bi-people"></i> Community
                        </a>
                        <a class="nav-link active" href="<?php echo Url::settings(); ?>">
                            <i class="bi bi-gear"></i> Settings
                        </a>
                    </nav>
                    <div class="sidebar-footer">
                        <nav class="nav flex-column">
                            <a class="nav-link" href="<?php echo Url::logout(); ?>">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a>
                        </nav>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-lg-12">
                <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-circle me-2"></i>
                    <?php foreach ($errors as $error): ?>
                        <?php echo htmlspecialchars($error); ?><br>
                    <?php endforeach; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <div class="row g-4">
                    <!-- Profile Photo & Info -->
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-body">
                                <div class="profile-photo-container">
                                    <img src="<?php echo htmlspecialchars($profileImage); ?>" alt="Profile Photo" class="profile-photo" id="profilePreview">
                                    <div class="profile-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                                    <div class="profile-email"><?php echo htmlspecialchars($user['email']); ?></div>
                                    <form method="POST" enctype="multipart/form-data" id="photoForm">
                                        <input type="hidden" name="action" value="upload_photo">
                                        <input type="file" name="profile_photo" id="profilePhotoInput" accept="image/*" style="display: none;">
                                        <button type="button" class="btn-upload" onclick="document.getElementById('profilePhotoInput').click()">
                                            <i class="bi bi-camera me-2"></i>Change Photo
                                        </button>
                                    </form>
                                </div>

                                <div class="account-info">
                                    <h6 class="mb-3"><i class="bi bi-info-circle me-2"></i>Account Info</h6>
                                    <div class="account-info-row">
                                        <span class="account-info-label">Role</span>
                                        <span class="account-info-value text-capitalize"><?php echo htmlspecialchars($user['role']); ?></span>
                                    </div>
                                    <div class="account-info-row">
                                        <span class="account-info-label">Status</span>
                                        <span class="account-info-value">
                                            <?php if ($user['status'] === 'active'): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning"><?php echo ucfirst($user['status']); ?></span>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    <div class="account-info-row">
                                        <span class="account-info-label">Member Since</span>
                                        <span class="account-info-value"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></span>
                                    </div>
                                    <div class="account-info-row">
                                        <span class="account-info-label">Last Login</span>
                                        <span class="account-info-value"><?php echo $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : 'N/A'; ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Settings Forms -->
                    <div class="col-lg-8">
                        <!-- Settings Navigation -->
                        <div class="settings-nav">
                            <button class="nav-btn active" data-target="profile-section">
                                <i class="bi bi-person me-2"></i>Profile
                            </button>
                            <button class="nav-btn" data-target="password-section">
                                <i class="bi bi-shield-lock me-2"></i>Password
                            </button>
                        </div>

                        <!-- Profile Section -->
                        <div class="card settings-section" id="profile-section">
                            <div class="card-header">
                                <i class="bi bi-person me-2"></i>Profile Information
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="update_profile">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">First Name <span class="text-danger">*</span></label>
                                            <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Last Name <span class="text-danger">*</span></label>
                                            <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Email Address</label>
                                            <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                                            <small class="text-muted">Email cannot be changed</small>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Phone Number</label>
                                            <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="+1 (555) 000-0000">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Bio</label>
                                            <textarea name="bio" class="form-control" rows="4" placeholder="Tell us about yourself..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                                        </div>
                                        <div class="col-12">
                                            <button type="submit" class="btn-save">
                                                <i class="bi bi-check-lg me-2"></i>Save Changes
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Password Section -->
                        <div class="card settings-section" id="password-section" style="display: none;">
                            <div class="card-header">
                                <i class="bi bi-shield-lock me-2"></i>Change Password
                            </div>
                            <div class="card-body">
                                <?php if (!empty($user['google_id'])): ?>
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-2"></i>
                                    You signed up using Google. To set a password, use the "Forgot Password" feature on the login page.
                                </div>
                                <?php else: ?>
                                <form method="POST">
                                    <input type="hidden" name="action" value="change_password">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label class="form-label">Current Password <span class="text-danger">*</span></label>
                                            <input type="password" name="current_password" class="form-control" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">New Password <span class="text-danger">*</span></label>
                                            <input type="password" name="new_password" class="form-control" id="newPassword" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                                            <input type="password" name="confirm_password" class="form-control" id="confirmPassword" required>
                                        </div>
                                        <div class="col-12">
                                            <div class="password-requirements">
                                                <h6><i class="bi bi-shield-check me-2"></i>Password Requirements</h6>
                                                <ul>
                                                    <li>At least 8 characters long</li>
                                                    <li>Include uppercase and lowercase letters</li>
                                                    <li>Include at least one number</li>
                                                    <li>Include at least one special character</li>
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <button type="submit" class="btn-save">
                                                <i class="bi bi-check-lg me-2"></i>Update Password
                                            </button>
                                        </div>
                                    </div>
                                </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Settings navigation
        document.querySelectorAll('.settings-nav .nav-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                // Update active state
                document.querySelectorAll('.settings-nav .nav-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                // Show/hide sections
                const target = this.dataset.target;
                document.querySelectorAll('.settings-section').forEach(section => {
                    section.style.display = section.id === target ? 'block' : 'none';
                });
            });
        });

        // Photo upload preview and auto-submit
        document.getElementById('profilePhotoInput').addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profilePreview').src = e.target.result;
                };
                reader.readAsDataURL(this.files[0]);
                
                // Auto submit form
                document.getElementById('photoForm').submit();
            }
        });

        // Password match validation
        document.getElementById('confirmPassword')?.addEventListener('input', function() {
            const newPassword = document.getElementById('newPassword').value;
            if (this.value !== newPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });

        // Sidebar toggle functionality
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('sidebarOverlay').classList.toggle('active');
            document.body.style.overflow = document.getElementById('sidebar').classList.contains('active') ? 'hidden' : '';
        }
    </script>
</body>
</html>
