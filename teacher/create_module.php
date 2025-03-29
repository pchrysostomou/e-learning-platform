<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/session.php';

redirect_if_not_logged_in();

if ($_SESSION['user_role'] !== 'teacher') {
    header("Location: " . base_url("index.php"));
    exit;
}

$teacher_id = $_SESSION['user_id'];
$course_id = $_GET['course_id'] ?? null;

if (!$course_id) {
    $_SESSION['error'] = "Course not specified.";
    header("Location: " . base_url("teacher/my_courses.php"));
    exit;
}

// Verify teacher owns course
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ? AND teacher_id = ?");
$stmt->execute([$course_id, $teacher_id]);
$course = $stmt->fetch();

if (!$course) {
    $_SESSION['error'] = "Access denied.";
    header("Location: " . base_url("teacher/my_courses.php"));
    exit;
}

$title = '';
$content = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);

    if (empty($title)) {
        $errors[] = "Module title is required.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO modules (course_id, title, content) VALUES (?, ?, ?)");
        $stmt->execute([$course_id, $title, $content]);

        $_SESSION['success'] = "✅ Module created.";
        header("Location: " . base_url("teacher/modules.php?course_id=" . $course_id));
        exit;
    }
}

$page_title = "Add Module";
include '../templates/header.php';
?>

<h2 class="mb-4">➕ Add Module to: <?= htmlspecialchars($course['title']) ?></h2>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST">
    <div class="mb-3">
        <label>Module Title</label>
        <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($title) ?>" required>
    </div>

    <div class="mb-3">
        <label>Module Content</label>
        <textarea name="content" class="form-control" rows="6"><?= htmlspecialchars($content) ?></textarea>
    </div>

    <button type="submit" class="btn btn-primary">Create Module</button>
    <a href="<?= base_url('teacher/modules.php?course_id=' . $course_id) ?>" class="btn btn-secondary">Cancel</a>
</form>

<?php include '../templates/footer.php'; ?>
