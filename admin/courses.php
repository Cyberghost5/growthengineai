<?php
/**
 * GrowthEngineAI LMS - Admin Course Management
 * Create and view courses
 */

require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Url.php';
require_once __DIR__ . '/../config/database.php';

$auth = new Auth();
$auth->requireRole('admin');

$db = getDB();
$errors = [];
$success = '';

function normalizeLinesToJson($input) {
    $lines = preg_split("/\r\n|\n|\r/", trim($input));
    $items = array_values(array_filter(array_map('trim', $lines), function ($value) {
        return $value !== '';
    }));
    return json_encode($items);
}

// Load categories and instructors for the form
$categories = $db->query("SELECT id, name, slug FROM categories WHERE is_active = 1 ORDER BY name ASC")->fetchAll();
$instructors = $db->query("
    SELECT i.id, u.first_name, u.last_name, u.email
    FROM instructors i
    JOIN users u ON i.user_id = u.id
    ORDER BY u.first_name, u.last_name
")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $subtitle = trim($_POST['subtitle'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $instructorId = (int)($_POST['instructor_id'] ?? 0);
    $level = trim($_POST['level'] ?? 'beginner');
    $language = trim($_POST['language'] ?? 'English');
    $durationHours = trim($_POST['duration_hours'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $salePrice = trim($_POST['sale_price'] ?? '');
    $thumbnail = trim($_POST['thumbnail'] ?? '');
    $previewVideo = trim($_POST['preview_video'] ?? '');
    $requirementsRaw = trim($_POST['requirements'] ?? '');
    $whatYouLearnRaw = trim($_POST['what_you_learn'] ?? '');
    $targetAudienceRaw = trim($_POST['target_audience'] ?? '');
    $isFree = isset($_POST['is_free']) ? 1 : 0;
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    $status = trim($_POST['status'] ?? 'draft');

    if ($title === '') {
        $errors['title'] = 'Course title is required.';
    }

    if ($slug === '') {
        $slug = Url::slugify($title);
    }

    if ($slug === '') {
        $errors['slug'] = 'Course slug is required.';
    }

    if ($categoryId <= 0) {
        $errors['category_id'] = 'Please select a category.';
    }

    if ($instructorId <= 0) {
        $errors['instructor_id'] = 'Please select an instructor.';
    }

    $validLevels = ['beginner', 'intermediate', 'advanced', 'all_levels'];
    if (!in_array($level, $validLevels, true)) {
        $errors['level'] = 'Invalid level selected.';
    }

    $validStatuses = ['draft', 'pending_review', 'published', 'archived'];
    if (!in_array($status, $validStatuses, true)) {
        $status = 'draft';
    }

    $durationValue = 0.0;
    if ($durationHours !== '') {
        if (!is_numeric($durationHours) || (float)$durationHours < 0) {
            $errors['duration_hours'] = 'Duration must be a positive number.';
        } else {
            $durationValue = (float)$durationHours;
        }
    }

    if ($isFree) {
        $priceValue = 0.0;
        $salePriceValue = null;
    } else {
        if ($price === '' || !is_numeric($price) || (float)$price < 0) {
            $errors['price'] = 'Price is required for paid courses.';
        }
        $priceValue = (float)$price;
        $salePriceValue = null;
        if ($salePrice !== '') {
            if (!is_numeric($salePrice) || (float)$salePrice < 0) {
                $errors['sale_price'] = 'Sale price must be a positive number.';
            } else {
                $salePriceValue = (float)$salePrice;
            }
        }
    }

    // Ensure slug uniqueness
    if (empty($errors['slug'])) {
        $stmt = $db->prepare("SELECT id FROM courses WHERE slug = ?");
        $stmt->execute([$slug]);
        if ($stmt->fetch()) {
            $errors['slug'] = 'This slug is already in use.';
        }
    }

    if (empty($errors)) {
        $requirementsJson = $requirementsRaw !== '' ? normalizeLinesToJson($requirementsRaw) : json_encode([]);
        $whatYouLearnJson = $whatYouLearnRaw !== '' ? normalizeLinesToJson($whatYouLearnRaw) : json_encode([]);
        $targetAudienceJson = $targetAudienceRaw !== '' ? normalizeLinesToJson($targetAudienceRaw) : json_encode([]);
        $isPublished = $status === 'published' ? 1 : 0;
        $publishedAt = $status === 'published' ? date('Y-m-d H:i:s') : null;

        $stmt = $db->prepare("
            INSERT INTO courses (
                instructor_id, category_id, title, slug, subtitle, description,
                requirements, what_you_learn, target_audience, thumbnail, preview_video,
                level, language, duration_hours, price, sale_price, is_free, is_featured,
                is_published, status, published_at, created_at, updated_at
            ) VALUES (
                :instructor_id, :category_id, :title, :slug, :subtitle, :description,
                :requirements, :what_you_learn, :target_audience, :thumbnail, :preview_video,
                :level, :language, :duration_hours, :price, :sale_price, :is_free, :is_featured,
                :is_published, :status, :published_at, NOW(), NOW()
            )
        ");

        $saved = $stmt->execute([
            ':instructor_id' => $instructorId,
            ':category_id' => $categoryId,
            ':title' => $title,
            ':slug' => $slug,
            ':subtitle' => $subtitle ?: null,
            ':description' => $description ?: null,
            ':requirements' => $requirementsJson,
            ':what_you_learn' => $whatYouLearnJson,
            ':target_audience' => $targetAudienceJson,
            ':thumbnail' => $thumbnail ?: null,
            ':preview_video' => $previewVideo ?: null,
            ':level' => $level,
            ':language' => $language ?: 'English',
            ':duration_hours' => $durationValue,
            ':price' => $priceValue,
            ':sale_price' => $salePriceValue,
            ':is_free' => $isFree,
            ':is_featured' => $isFeatured,
            ':is_published' => $isPublished,
            ':status' => $status,
            ':published_at' => $publishedAt
        ]);

        if ($saved) {
            $db->prepare("UPDATE instructors SET total_courses = total_courses + 1 WHERE id = ?")->execute([$instructorId]);
            $success = 'Course created successfully.';
            $_POST = [];
        } else {
            $errors['general'] = 'Failed to create course.';
        }
    }
}

$recentCourses = $db->query("
    SELECT c.id, c.title, c.slug, c.status, c.is_free, c.price, c.created_at,
           cat.name AS category_name,
           CONCAT(u.first_name, ' ', u.last_name) AS instructor_name
    FROM courses c
    JOIN categories cat ON c.category_id = cat.id
    JOIN instructors i ON c.instructor_id = i.id
    JOIN users u ON i.user_id = u.id
    ORDER BY c.id DESC
    LIMIT 20
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Courses - GrowthEngineAI Admin</title>
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
        .form-label {
            font-weight: 600;
            color: #0f172a;
        }
        .helper-text {
            font-size: 12px;
            color: #64748b;
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
                    <p class="mb-0">Create and manage courses</p>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="badge bg-light px-3 py-2" style="color: #000016 !important;">
                        <i class="bi bi-person-badge me-1"></i> Admin
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid px-4">
        <div class="row g-4">
            <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
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
                        <a class="nav-link" href="categories.php">
                            <i class="bi bi-folder"></i> Manage Categories
                        </a>
                        <a class="nav-link active" href="courses.php">
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

            <div class="col-lg-11 mx-auto">
                <div class="card p-4 mb-4">
                    <h2 class="mb-1">Create New Course</h2>
                    <p class="text-muted mb-4">Fill in the course details and publish when ready.</p>

                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>
                    <?php if (isset($errors['general'])): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($errors['general']); ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="title" class="form-label">Course Title</label>
                                <input type="text" class="form-control <?php echo isset($errors['title']) ? 'is-invalid' : ''; ?>" id="title" name="title" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required>
                                <?php if (isset($errors['title'])): ?>
                                    <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['title']); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label for="slug" class="form-label">Course Slug</label>
                                <input type="text" class="form-control <?php echo isset($errors['slug']) ? 'is-invalid' : ''; ?>" id="slug" name="slug" value="<?php echo htmlspecialchars($_POST['slug'] ?? ''); ?>">
                                <div class="helper-text">Leave blank to auto-generate from the title.</div>
                                <?php if (isset($errors['slug'])): ?>
                                    <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['slug']); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label for="subtitle" class="form-label">Subtitle</label>
                                <input type="text" class="form-control" id="subtitle" name="subtitle" value="<?php echo htmlspecialchars($_POST['subtitle'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="language" class="form-label">Language</label>
                                <input type="text" class="form-control" id="language" name="language" value="<?php echo htmlspecialchars($_POST['language'] ?? 'English'); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="category_id" class="form-label">Category</label>
                                <select class="form-select <?php echo isset($errors['category_id']) ? 'is-invalid' : ''; ?>" id="category_id" name="category_id" required>
                                    <option value="">Select category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo (int)$category['id']; ?>" <?php echo ((int)($category['id']) === (int)($_POST['category_id'] ?? 0)) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['category_id'])): ?>
                                    <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['category_id']); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label for="instructor_id" class="form-label">Instructor</label>
                                <select class="form-select <?php echo isset($errors['instructor_id']) ? 'is-invalid' : ''; ?>" id="instructor_id" name="instructor_id" required>
                                    <option value="">Select instructor</option>
                                    <?php foreach ($instructors as $instructor): ?>
                                        <option value="<?php echo (int)$instructor['id']; ?>" <?php echo ((int)($instructor['id']) === (int)($_POST['instructor_id'] ?? 0)) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($instructor['first_name'] . ' ' . $instructor['last_name'] . ' (' . $instructor['email'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['instructor_id'])): ?>
                                    <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['instructor_id']); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4">
                                <label for="level" class="form-label">Level</label>
                                <select class="form-select <?php echo isset($errors['level']) ? 'is-invalid' : ''; ?>" id="level" name="level">
                                    <option value="beginner" <?php echo (($_POST['level'] ?? '') === 'beginner') ? 'selected' : ''; ?>>Beginner</option>
                                    <option value="intermediate" <?php echo (($_POST['level'] ?? '') === 'intermediate') ? 'selected' : ''; ?>>Intermediate</option>
                                    <option value="advanced" <?php echo (($_POST['level'] ?? '') === 'advanced') ? 'selected' : ''; ?>>Advanced</option>
                                    <option value="all_levels" <?php echo (($_POST['level'] ?? '') === 'all_levels') ? 'selected' : ''; ?>>All Levels</option>
                                </select>
                                <?php if (isset($errors['level'])): ?>
                                    <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['level']); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4">
                                <label for="duration_hours" class="form-label">Duration (hours)</label>
                                <input type="number" step="0.1" class="form-control <?php echo isset($errors['duration_hours']) ? 'is-invalid' : ''; ?>" id="duration_hours" name="duration_hours" value="<?php echo htmlspecialchars($_POST['duration_hours'] ?? ''); ?>">
                                <?php if (isset($errors['duration_hours'])): ?>
                                    <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['duration_hours']); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="draft" <?php echo (($_POST['status'] ?? '') === 'draft') ? 'selected' : ''; ?>>Draft</option>
                                    <option value="pending_review" <?php echo (($_POST['status'] ?? '') === 'pending_review') ? 'selected' : ''; ?>>Pending Review</option>
                                    <option value="published" <?php echo (($_POST['status'] ?? '') === 'published') ? 'selected' : ''; ?>>Published</option>
                                    <option value="archived" <?php echo (($_POST['status'] ?? '') === 'archived') ? 'selected' : ''; ?>>Archived</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="price" class="form-label">Price</label>
                                <input type="number" step="0.01" class="form-control <?php echo isset($errors['price']) ? 'is-invalid' : ''; ?>" id="price" name="price" value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>">
                                <?php if (isset($errors['price'])): ?>
                                    <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['price']); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4">
                                <label for="sale_price" class="form-label">Sale Price</label>
                                <input type="number" step="0.01" class="form-control <?php echo isset($errors['sale_price']) ? 'is-invalid' : ''; ?>" id="sale_price" name="sale_price" value="<?php echo htmlspecialchars($_POST['sale_price'] ?? ''); ?>">
                                <?php if (isset($errors['sale_price'])): ?>
                                    <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['sale_price']); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4 d-flex align-items-center gap-4">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" id="is_free" name="is_free" <?php echo isset($_POST['is_free']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_free">Free Course</label>
                                </div>
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" <?php echo isset($_POST['is_featured']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_featured">Featured</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="thumbnail" class="form-label">Thumbnail URL</label>
                                <input type="url" class="form-control" id="thumbnail" name="thumbnail" value="<?php echo htmlspecialchars($_POST['thumbnail'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="preview_video" class="form-label">Preview Video URL</label>
                                <input type="url" class="form-control" id="preview_video" name="preview_video" value="<?php echo htmlspecialchars($_POST['preview_video'] ?? ''); ?>">
                            </div>
                            <div class="col-12">
                                <label for="description" class="form-label">Course Description</label>
                                <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                            </div>
                            <div class="col-md-4">
                                <label for="requirements" class="form-label">Requirements</label>
                                <textarea class="form-control" id="requirements" name="requirements" rows="4"><?php echo htmlspecialchars($_POST['requirements'] ?? ''); ?></textarea>
                                <div class="helper-text">One requirement per line.</div>
                            </div>
                            <div class="col-md-4">
                                <label for="what_you_learn" class="form-label">What You'll Learn</label>
                                <textarea class="form-control" id="what_you_learn" name="what_you_learn" rows="4"><?php echo htmlspecialchars($_POST['what_you_learn'] ?? ''); ?></textarea>
                                <div class="helper-text">One item per line.</div>
                            </div>
                            <div class="col-md-4">
                                <label for="target_audience" class="form-label">Target Audience</label>
                                <textarea class="form-control" id="target_audience" name="target_audience" rows="4"><?php echo htmlspecialchars($_POST['target_audience'] ?? ''); ?></textarea>
                                <div class="helper-text">One audience type per line.</div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-plus-circle me-1"></i> Create Course
                            </button>
                        </div>
                    </form>
                </div>

                <div class="card p-4">
                    <h3 class="mb-3">Recent Courses</h3>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>Instructor</th>
                                    <th>Status</th>
                                    <th>Price</th>
                                    <th>Actions</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentCourses as $course): ?>
                                    <tr>
                                        <td><?php echo (int)$course['id']; ?></td>
                                        <td><?php echo htmlspecialchars($course['title']); ?></td>
                                        <td><?php echo htmlspecialchars($course['category_name']); ?></td>
                                        <td><?php echo htmlspecialchars($course['instructor_name']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $course['status'] === 'published' ? 'success' : ($course['status'] === 'draft' ? 'secondary' : 'warning'); ?>">
                                                <?php echo htmlspecialchars(str_replace('_', ' ', $course['status'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo $course['is_free'] ? 'Free' : number_format((float)$course['price'], 2); ?>
                                        </td>
                                        <td>
                                            <a class="btn btn-sm btn-outline-primary" href="course-content.php?course_id=<?php echo (int)$course['id']; ?>">
                                                Manage Content
                                            </a>
                                        </td>
                                        <td><?php echo htmlspecialchars($course['created_at']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!$recentCourses): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">No courses found yet.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
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
