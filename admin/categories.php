<?php
/**
 * Admin - Category Management
 * Allows admin to create and view categories
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Auth.php';

$auth = new Auth();
$auth->requireRole('admin');

// Handle category creation
$errors = [];
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($name === '') {
        $errors['name'] = 'Category name is required.';
    }

    if (empty($errors)) {
        $db = getDB();
        $stmt = $db->prepare('INSERT INTO categories (name, description, created_at) VALUES (?, ?, NOW())');
        if ($stmt->execute([$name, $description])) {
            $success = 'Category created successfully!';
        } else {
            $errors['general'] = 'Failed to create category.';
        }
    }
}

// Fetch categories
$db = getDB();
$categories = $db->query('SELECT * FROM categories ORDER BY id DESC')->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manage Categories</title>
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
                    <h1>Admin Dashboard</h1>
                    <p class="mb-0">Manage categories and more</p>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="badge bg-light text-purple px-3 py-2" style="color: #000016 !important;">
                        <i class="bi bi-person-badge me-1"></i> Admin
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
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-grid-1x2"></i> Dashboard
                        </a>
                        <a class="nav-link active" href="categories.php">
                            <i class="bi bi-folder"></i> Manage Categories
                        </a>
                        <a class="nav-link" href="courses.php">
                            <i class="bi bi-book"></i> Manage Courses
                        </a>
                        <a class="nav-link" href="users.php">
                            <i class="bi bi-people"></i> Users
                        </a>
                        <a class="nav-link" href="settings.php">
                            <i class="bi bi-gear"></i> Settings
                        </a>
                    </nav>
                    <div class="sidebar-footer">
                        <nav class="nav flex-column">
                            <a class="nav-link" href="../auth/logout.php">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a>
                        </nav>
                    </div>
                </div>
            </div>
            <!-- Main Content -->
            <div class="col-lg-10 mx-auto">
                <div class="card p-4 mb-4">
                    <h2 class="mb-4">Manage Categories</h2>
                    <?php if ($success): ?>
                        <div class="alert alert-success"> <?= htmlspecialchars($success) ?> </div>
                    <?php endif; ?>
                    <?php if (isset($errors['general'])): ?>
                        <div class="alert alert-danger"> <?= htmlspecialchars($errors['general']) ?> </div>
                    <?php endif; ?>
                    <form method="POST" class="mb-4">
                        <div class="mb-3">
                            <label for="name" class="form-label">Category Name</label>
                            <input type="text" name="name" id="name" class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                            <?php if (isset($errors['name'])): ?>
                                <div class="invalid-feedback d-block"> <?= htmlspecialchars($errors['name']) ?> </div>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea name="description" id="description" class="form-control" rows="2"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Create Category</button>
                    </form>
                    <h3 class="mb-3">Existing Categories</h3>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Created At</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td><?= $cat['id'] ?></td>
                                <td><?= htmlspecialchars($cat['name']) ?></td>
                                <td><?= htmlspecialchars($cat['description']) ?></td>
                                <td><?= $cat['created_at'] ?? '' ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('sidebarOverlay').classList.toggle('active');
            document.body.style.overflow = document.getElementById('sidebar').classList.contains('active') ? 'hidden' : '';
        }
    </script>
</body>
</html>
