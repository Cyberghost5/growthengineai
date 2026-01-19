<?php
/**
 * GrowthEngineAI LMS - Admin Course Content
 * Manage modules, lessons, quizzes, and assignments for a course
 */

require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Url.php';
require_once __DIR__ . '/../config/database.php';

$auth = new Auth();
$auth->requireRole('admin');

$db = getDB();
$errors = [];
$success = '';

function nextSortOrder(PDO $db, $table, $column, $id) {
    $stmt = $db->prepare("SELECT COALESCE(MAX(sort_order), 0) + 1 AS next_sort FROM {$table} WHERE {$column} = ?");
    $stmt->execute([$id]);
    return (int)$stmt->fetchColumn();
}

$courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
$course = null;
if ($courseId > 0) {
    $stmt = $db->prepare("SELECT id, title FROM courses WHERE id = ?");
    $stmt->execute([$courseId]);
    $course = $stmt->fetch();
}

if (!$course) {
    http_response_code(404);
    $errors['general'] = 'Course not found.';
}

if (isset($_GET['saved']) && $course) {
    $savedType = trim($_GET['saved']);
    if ($savedType !== '') {
        $success = ucfirst($savedType) . ' saved successfully.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $course) {
    $action = trim($_POST['action'] ?? '');

    if ($action === 'add_module') {
        $title = trim($_POST['module_title'] ?? '');
        $description = trim($_POST['module_description'] ?? '');
        $sortOrder = trim($_POST['module_sort_order'] ?? '');
        $isPublished = isset($_POST['module_is_published']) ? 1 : 0;

        if ($title === '') {
            $errors['module_title'] = 'Module title is required.';
        }

        $sortValue = $sortOrder !== '' ? (int)$sortOrder : nextSortOrder($db, 'modules', 'course_id', $courseId);

        if (empty($errors)) {
            $stmt = $db->prepare("
                INSERT INTO modules (course_id, title, description, sort_order, is_published)
                VALUES (:course_id, :title, :description, :sort_order, :is_published)
            ");
            $saved = $stmt->execute([
                ':course_id' => $courseId,
                ':title' => $title,
                ':description' => $description ?: null,
                ':sort_order' => $sortValue,
                ':is_published' => $isPublished
            ]);
            if ($saved) {
                header('Location: course-content.php?course_id=' . $courseId . '&saved=module');
                exit;
            }
            $errors['general'] = 'Failed to create module.';
        }
    }

    if ($action === 'add_lesson') {
        $moduleId = (int)($_POST['lesson_module_id'] ?? 0);
        $title = trim($_POST['lesson_title'] ?? '');
        $slug = trim($_POST['lesson_slug'] ?? '');
        $description = trim($_POST['lesson_description'] ?? '');
        $contentType = trim($_POST['lesson_content_type'] ?? 'video');
        $videoUrl = trim($_POST['lesson_video_url'] ?? '');
        $durationMinutes = trim($_POST['lesson_duration_minutes'] ?? '');
        $sortOrder = trim($_POST['lesson_sort_order'] ?? '');
        $isFreePreview = isset($_POST['lesson_is_free_preview']) ? 1 : 0;
        $isPublished = isset($_POST['lesson_is_published']) ? 1 : 0;

        if ($moduleId <= 0) {
            $errors['lesson_module_id'] = 'Please select a module.';
        }
        if ($title === '') {
            $errors['lesson_title'] = 'Lesson title is required.';
        }

        if ($slug === '') {
            $slug = Url::slugify($title);
        }
        if ($slug === '') {
            $errors['lesson_slug'] = 'Lesson slug is required.';
        }

        $durationValue = 0;
        if ($durationMinutes !== '') {
            if (!is_numeric($durationMinutes) || (int)$durationMinutes < 0) {
                $errors['lesson_duration_minutes'] = 'Duration must be a positive number.';
            } else {
                $durationValue = (int)$durationMinutes;
            }
        }

        $sortValue = $sortOrder !== '' ? (int)$sortOrder : nextSortOrder($db, 'lessons', 'module_id', $moduleId);

        if (empty($errors)) {
            $stmt = $db->prepare("
                INSERT INTO lessons (
                    module_id, title, slug, description, content_type, video_url,
                    duration_minutes, sort_order, is_free_preview, is_published
                ) VALUES (
                    :module_id, :title, :slug, :description, :content_type, :video_url,
                    :duration_minutes, :sort_order, :is_free_preview, :is_published
                )
            ");
            $saved = $stmt->execute([
                ':module_id' => $moduleId,
                ':title' => $title,
                ':slug' => $slug,
                ':description' => $description ?: null,
                ':content_type' => $contentType,
                ':video_url' => $videoUrl ?: null,
                ':duration_minutes' => $durationValue,
                ':sort_order' => $sortValue,
                ':is_free_preview' => $isFreePreview,
                ':is_published' => $isPublished
            ]);
            if ($saved) {
                header('Location: course-content.php?course_id=' . $courseId . '&saved=lesson');
                exit;
            }
            $errors['general'] = 'Failed to create lesson.';
        }
    }

    if ($action === 'add_quiz') {
        $moduleId = (int)($_POST['quiz_module_id'] ?? 0);
        $title = trim($_POST['quiz_title'] ?? '');
        $description = trim($_POST['quiz_description'] ?? '');
        $passingScore = trim($_POST['quiz_passing_score'] ?? '');
        $totalQuestions = trim($_POST['quiz_total_questions'] ?? '');
        $sortOrder = trim($_POST['quiz_sort_order'] ?? '');
        $isPublished = isset($_POST['quiz_is_published']) ? 1 : 0;

        if ($moduleId <= 0) {
            $errors['quiz_module_id'] = 'Please select a module.';
        }
        if ($title === '') {
            $errors['quiz_title'] = 'Quiz title is required.';
        }

        $passingValue = $passingScore !== '' && is_numeric($passingScore) ? (int)$passingScore : 70;
        $questionsValue = $totalQuestions !== '' && is_numeric($totalQuestions) ? (int)$totalQuestions : 0;
        $sortValue = $sortOrder !== '' ? (int)$sortOrder : nextSortOrder($db, 'quizzes', 'module_id', $moduleId);

        if (empty($errors)) {
            $stmt = $db->prepare("
                INSERT INTO quizzes (
                    module_id, title, description, passing_score,
                    total_questions, sort_order, is_published
                ) VALUES (
                    :module_id, :title, :description, :passing_score,
                    :total_questions, :sort_order, :is_published
                )
            ");
            $saved = $stmt->execute([
                ':module_id' => $moduleId,
                ':title' => $title,
                ':description' => $description ?: null,
                ':passing_score' => $passingValue,
                ':total_questions' => $questionsValue,
                ':sort_order' => $sortValue,
                ':is_published' => $isPublished
            ]);
            if ($saved) {
                header('Location: course-content.php?course_id=' . $courseId . '&saved=quiz');
                exit;
            }
            $errors['general'] = 'Failed to create quiz.';
        }
    }

    if ($action === 'add_assignment') {
        $moduleId = (int)($_POST['assignment_module_id'] ?? 0);
        $title = trim($_POST['assignment_title'] ?? '');
        $description = trim($_POST['assignment_description'] ?? '');
        $dueDays = trim($_POST['assignment_due_days'] ?? '');
        $maxPoints = trim($_POST['assignment_max_points'] ?? '');
        $passingPoints = trim($_POST['assignment_passing_points'] ?? '');
        $sortOrder = trim($_POST['assignment_sort_order'] ?? '');
        $isPublished = isset($_POST['assignment_is_published']) ? 1 : 0;

        if ($moduleId <= 0) {
            $errors['assignment_module_id'] = 'Please select a module.';
        }
        if ($title === '') {
            $errors['assignment_title'] = 'Assignment title is required.';
        }

        $dueValue = $dueDays !== '' && is_numeric($dueDays) ? (int)$dueDays : null;
        $maxPointsValue = $maxPoints !== '' && is_numeric($maxPoints) ? (int)$maxPoints : 100;
        $passingPointsValue = $passingPoints !== '' && is_numeric($passingPoints) ? (int)$passingPoints : 60;
        $sortValue = $sortOrder !== '' ? (int)$sortOrder : nextSortOrder($db, 'assignments', 'module_id', $moduleId);

        if (empty($errors)) {
            $stmt = $db->prepare("
                INSERT INTO assignments (
                    module_id, title, description, due_days,
                    max_points, passing_points, sort_order, is_published
                ) VALUES (
                    :module_id, :title, :description, :due_days,
                    :max_points, :passing_points, :sort_order, :is_published
                )
            ");
            $saved = $stmt->execute([
                ':module_id' => $moduleId,
                ':title' => $title,
                ':description' => $description ?: null,
                ':due_days' => $dueValue,
                ':max_points' => $maxPointsValue,
                ':passing_points' => $passingPointsValue,
                ':sort_order' => $sortValue,
                ':is_published' => $isPublished
            ]);
            if ($saved) {
                header('Location: course-content.php?course_id=' . $courseId . '&saved=assignment');
                exit;
            }
            $errors['general'] = 'Failed to create assignment.';
        }
    }
}

$modules = [];
if ($course) {
    $stmt = $db->prepare("SELECT * FROM modules WHERE course_id = ? ORDER BY sort_order ASC, id ASC");
    $stmt->execute([$courseId]);
    $modules = $stmt->fetchAll();

    $moduleIds = array_column($modules, 'id');
    $lessonsByModule = [];
    $quizzesByModule = [];
    $assignmentsByModule = [];

    if ($moduleIds) {
        $placeholders = implode(',', array_fill(0, count($moduleIds), '?'));

        $stmt = $db->prepare("SELECT * FROM lessons WHERE module_id IN ({$placeholders}) ORDER BY sort_order ASC, id ASC");
        $stmt->execute($moduleIds);
        foreach ($stmt->fetchAll() as $lesson) {
            $lessonsByModule[$lesson['module_id']][] = $lesson;
        }

        $stmt = $db->prepare("SELECT * FROM quizzes WHERE module_id IN ({$placeholders}) ORDER BY sort_order ASC, id ASC");
        $stmt->execute($moduleIds);
        foreach ($stmt->fetchAll() as $quiz) {
            $quizzesByModule[$quiz['module_id']][] = $quiz;
        }

        $stmt = $db->prepare("SELECT * FROM assignments WHERE module_id IN ({$placeholders}) ORDER BY sort_order ASC, id ASC");
        $stmt->execute($moduleIds);
        foreach ($stmt->fetchAll() as $assignment) {
            $assignmentsByModule[$assignment['module_id']][] = $assignment;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Content - GrowthEngineAI Admin</title>
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
        .module-card {
            border-left: 4px solid #000016;
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
                    <h1>Course Content</h1>
                    <p class="mb-0"><?php echo $course ? htmlspecialchars($course['title']) : 'Course not found'; ?></p>
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
                <?php if (isset($errors['general'])): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($errors['general']); ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <?php if ($course): ?>
                    <div class="card p-4 mb-4">
                        <h2 class="mb-3">Add Module</h2>
                        <form method="POST">
                            <input type="hidden" name="action" value="add_module">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="module_title">Module Title</label>
                                    <input class="form-control <?php echo isset($errors['module_title']) ? 'is-invalid' : ''; ?>" id="module_title" name="module_title" value="<?php echo htmlspecialchars($_POST['module_title'] ?? ''); ?>" required>
                                    <?php if (isset($errors['module_title'])): ?>
                                        <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['module_title']); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="module_sort_order">Sort Order</label>
                                    <input type="number" class="form-control" id="module_sort_order" name="module_sort_order" value="<?php echo htmlspecialchars($_POST['module_sort_order'] ?? ''); ?>">
                                    <div class="helper-text">Leave blank for auto.</div>
                                </div>
                                <div class="col-md-3 d-flex align-items-center">
                                    <div class="form-check mt-4">
                                        <input class="form-check-input" type="checkbox" id="module_is_published" name="module_is_published" <?php echo isset($_POST['module_is_published']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="module_is_published">Published</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="module_description">Description</label>
                                    <textarea class="form-control" id="module_description" name="module_description" rows="2"><?php echo htmlspecialchars($_POST['module_description'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            <div class="text-end mt-3">
                                <button class="btn btn-primary" type="submit">
                                    <i class="bi bi-plus-circle me-1"></i> Add Module
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="card p-4 mb-4">
                        <h2 class="mb-3">Add Lesson</h2>
                        <form method="POST">
                            <input type="hidden" name="action" value="add_lesson">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label" for="lesson_module_id">Module</label>
                                    <select class="form-select <?php echo isset($errors['lesson_module_id']) ? 'is-invalid' : ''; ?>" id="lesson_module_id" name="lesson_module_id" required>
                                        <option value="">Select module</option>
                                        <?php foreach ($modules as $module): ?>
                                            <option value="<?php echo (int)$module['id']; ?>" <?php echo ((int)($module['id']) === (int)($_POST['lesson_module_id'] ?? 0)) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($module['title']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (isset($errors['lesson_module_id'])): ?>
                                        <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['lesson_module_id']); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="lesson_title">Lesson Title</label>
                                    <input class="form-control <?php echo isset($errors['lesson_title']) ? 'is-invalid' : ''; ?>" id="lesson_title" name="lesson_title" value="<?php echo htmlspecialchars($_POST['lesson_title'] ?? ''); ?>" required>
                                    <?php if (isset($errors['lesson_title'])): ?>
                                        <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['lesson_title']); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="lesson_slug">Lesson Slug</label>
                                    <input class="form-control <?php echo isset($errors['lesson_slug']) ? 'is-invalid' : ''; ?>" id="lesson_slug" name="lesson_slug" value="<?php echo htmlspecialchars($_POST['lesson_slug'] ?? ''); ?>">
                                    <div class="helper-text">Leave blank to auto-generate.</div>
                                    <?php if (isset($errors['lesson_slug'])): ?>
                                        <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['lesson_slug']); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="lesson_content_type">Content Type</label>
                                    <select class="form-select" id="lesson_content_type" name="lesson_content_type">
                                        <?php
                                        $contentTypes = ['video', 'text', 'pdf', 'audio', 'embed', 'live'];
                                        $selectedType = $_POST['lesson_content_type'] ?? 'video';
                                        foreach ($contentTypes as $type):
                                        ?>
                                            <option value="<?php echo $type; ?>" <?php echo $selectedType === $type ? 'selected' : ''; ?>>
                                                <?php echo ucfirst($type); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="lesson_video_url">Video URL</label>
                                    <input class="form-control" id="lesson_video_url" name="lesson_video_url" value="<?php echo htmlspecialchars($_POST['lesson_video_url'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="lesson_duration_minutes">Duration (min)</label>
                                    <input type="number" class="form-control <?php echo isset($errors['lesson_duration_minutes']) ? 'is-invalid' : ''; ?>" id="lesson_duration_minutes" name="lesson_duration_minutes" value="<?php echo htmlspecialchars($_POST['lesson_duration_minutes'] ?? ''); ?>">
                                    <?php if (isset($errors['lesson_duration_minutes'])): ?>
                                        <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['lesson_duration_minutes']); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="lesson_sort_order">Sort Order</label>
                                    <input type="number" class="form-control" id="lesson_sort_order" name="lesson_sort_order" value="<?php echo htmlspecialchars($_POST['lesson_sort_order'] ?? ''); ?>">
                                    <div class="helper-text">Leave blank for auto.</div>
                                </div>
                                <div class="col-md-8 d-flex flex-wrap align-items-center gap-4 mt-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="lesson_is_free_preview" name="lesson_is_free_preview" <?php echo isset($_POST['lesson_is_free_preview']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="lesson_is_free_preview">Free Preview</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="lesson_is_published" name="lesson_is_published" <?php echo isset($_POST['lesson_is_published']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="lesson_is_published">Published</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="lesson_description">Description</label>
                                    <textarea class="form-control" id="lesson_description" name="lesson_description" rows="2"><?php echo htmlspecialchars($_POST['lesson_description'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            <div class="text-end mt-3">
                                <button class="btn btn-primary" type="submit">
                                    <i class="bi bi-plus-circle me-1"></i> Add Lesson
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="card p-4 mb-4">
                        <h2 class="mb-3">Add Quiz</h2>
                        <form method="POST">
                            <input type="hidden" name="action" value="add_quiz">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label" for="quiz_module_id">Module</label>
                                    <select class="form-select <?php echo isset($errors['quiz_module_id']) ? 'is-invalid' : ''; ?>" id="quiz_module_id" name="quiz_module_id" required>
                                        <option value="">Select module</option>
                                        <?php foreach ($modules as $module): ?>
                                            <option value="<?php echo (int)$module['id']; ?>" <?php echo ((int)($module['id']) === (int)($_POST['quiz_module_id'] ?? 0)) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($module['title']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (isset($errors['quiz_module_id'])): ?>
                                        <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['quiz_module_id']); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="quiz_title">Quiz Title</label>
                                    <input class="form-control <?php echo isset($errors['quiz_title']) ? 'is-invalid' : ''; ?>" id="quiz_title" name="quiz_title" value="<?php echo htmlspecialchars($_POST['quiz_title'] ?? ''); ?>" required>
                                    <?php if (isset($errors['quiz_title'])): ?>
                                        <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['quiz_title']); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label" for="quiz_passing_score">Passing Score</label>
                                    <input type="number" class="form-control" id="quiz_passing_score" name="quiz_passing_score" value="<?php echo htmlspecialchars($_POST['quiz_passing_score'] ?? ''); ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label" for="quiz_total_questions">Questions</label>
                                    <input type="number" class="form-control" id="quiz_total_questions" name="quiz_total_questions" value="<?php echo htmlspecialchars($_POST['quiz_total_questions'] ?? ''); ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label" for="quiz_sort_order">Sort Order</label>
                                    <input type="number" class="form-control" id="quiz_sort_order" name="quiz_sort_order" value="<?php echo htmlspecialchars($_POST['quiz_sort_order'] ?? ''); ?>">
                                    <div class="helper-text">Leave blank for auto.</div>
                                </div>
                                <div class="col-md-10">
                                    <label class="form-label" for="quiz_description">Description</label>
                                    <textarea class="form-control" id="quiz_description" name="quiz_description" rows="2"><?php echo htmlspecialchars($_POST['quiz_description'] ?? ''); ?></textarea>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-check mt-4">
                                        <input class="form-check-input" type="checkbox" id="quiz_is_published" name="quiz_is_published" <?php echo isset($_POST['quiz_is_published']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="quiz_is_published">Published</label>
                                    </div>
                                </div>
                            </div>
                            <div class="text-end mt-3">
                                <button class="btn btn-primary" type="submit">
                                    <i class="bi bi-plus-circle me-1"></i> Add Quiz
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="card p-4 mb-4">
                        <h2 class="mb-3">Add Assignment</h2>
                        <form method="POST">
                            <input type="hidden" name="action" value="add_assignment">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label" for="assignment_module_id">Module</label>
                                    <select class="form-select <?php echo isset($errors['assignment_module_id']) ? 'is-invalid' : ''; ?>" id="assignment_module_id" name="assignment_module_id" required>
                                        <option value="">Select module</option>
                                        <?php foreach ($modules as $module): ?>
                                            <option value="<?php echo (int)$module['id']; ?>" <?php echo ((int)($module['id']) === (int)($_POST['assignment_module_id'] ?? 0)) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($module['title']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (isset($errors['assignment_module_id'])): ?>
                                        <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['assignment_module_id']); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="assignment_title">Assignment Title</label>
                                    <input class="form-control <?php echo isset($errors['assignment_title']) ? 'is-invalid' : ''; ?>" id="assignment_title" name="assignment_title" value="<?php echo htmlspecialchars($_POST['assignment_title'] ?? ''); ?>" required>
                                    <?php if (isset($errors['assignment_title'])): ?>
                                        <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['assignment_title']); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label" for="assignment_due_days">Due Days</label>
                                    <input type="number" class="form-control" id="assignment_due_days" name="assignment_due_days" value="<?php echo htmlspecialchars($_POST['assignment_due_days'] ?? ''); ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label" for="assignment_sort_order">Sort Order</label>
                                    <input type="number" class="form-control" id="assignment_sort_order" name="assignment_sort_order" value="<?php echo htmlspecialchars($_POST['assignment_sort_order'] ?? ''); ?>">
                                    <div class="helper-text">Leave blank for auto.</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="assignment_max_points">Max Points</label>
                                    <input type="number" class="form-control" id="assignment_max_points" name="assignment_max_points" value="<?php echo htmlspecialchars($_POST['assignment_max_points'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="assignment_passing_points">Passing Points</label>
                                    <input type="number" class="form-control" id="assignment_passing_points" name="assignment_passing_points" value="<?php echo htmlspecialchars($_POST['assignment_passing_points'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check mt-4">
                                        <input class="form-check-input" type="checkbox" id="assignment_is_published" name="assignment_is_published" <?php echo isset($_POST['assignment_is_published']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="assignment_is_published">Published</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="assignment_description">Description</label>
                                    <textarea class="form-control" id="assignment_description" name="assignment_description" rows="2"><?php echo htmlspecialchars($_POST['assignment_description'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            <div class="text-end mt-3">
                                <button class="btn btn-primary" type="submit">
                                    <i class="bi bi-plus-circle me-1"></i> Add Assignment
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="card p-4">
                        <h2 class="mb-3">Current Structure</h2>
                        <?php if (!$modules): ?>
                            <p class="text-muted mb-0">No modules yet. Add the first module above.</p>
                        <?php endif; ?>
                        <?php foreach ($modules as $module): ?>
                            <div class="border rounded-3 p-3 mb-3 module-card">
                                <h5 class="mb-1"><?php echo htmlspecialchars($module['title']); ?></h5>
                                <p class="text-muted mb-2"><?php echo htmlspecialchars($module['description'] ?? ''); ?></p>
                                <div class="small text-muted mb-2">
                                    Sort: <?php echo (int)$module['sort_order']; ?> |
                                    <?php echo $module['is_published'] ? 'Published' : 'Draft'; ?>
                                </div>
                                <div class="mb-2">
                                    <strong>Lessons</strong>
                                    <ul class="mt-2">
                                        <?php foreach ($lessonsByModule[$module['id']] ?? [] as $lesson): ?>
                                            <li>
                                                <?php echo htmlspecialchars($lesson['title']); ?>
                                                <span class="text-muted">(<?php echo htmlspecialchars($lesson['content_type']); ?>, <?php echo $lesson['is_published'] ? 'Published' : 'Draft'; ?>)</span>
                                            </li>
                                        <?php endforeach; ?>
                                        <?php if (empty($lessonsByModule[$module['id']])): ?>
                                            <li class="text-muted">No lessons yet.</li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                                <div class="mb-2">
                                    <strong>Quizzes</strong>
                                    <ul class="mt-2">
                                        <?php foreach ($quizzesByModule[$module['id']] ?? [] as $quiz): ?>
                                            <li>
                                                <?php echo htmlspecialchars($quiz['title']); ?>
                                                <span class="text-muted">(<?php echo $quiz['is_published'] ? 'Published' : 'Draft'; ?>)</span>
                                            </li>
                                        <?php endforeach; ?>
                                        <?php if (empty($quizzesByModule[$module['id']])): ?>
                                            <li class="text-muted">No quizzes yet.</li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                                <div>
                                    <strong>Assignments</strong>
                                    <ul class="mt-2">
                                        <?php foreach ($assignmentsByModule[$module['id']] ?? [] as $assignment): ?>
                                            <li>
                                                <?php echo htmlspecialchars($assignment['title']); ?>
                                                <span class="text-muted">(<?php echo $assignment['is_published'] ? 'Published' : 'Draft'; ?>)</span>
                                            </li>
                                        <?php endforeach; ?>
                                        <?php if (empty($assignmentsByModule[$module['id']])): ?>
                                            <li class="text-muted">No assignments yet.</li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
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
