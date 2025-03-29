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
$module_id = $_GET['id'] ?? null;
$course_id = $_GET['course_id'] ?? null;

if (!$module_id || !$course_id) {
    $_SESSION['error'] = "Module or course not specified.";
    header("Location: " . base_url("teacher/my_courses.php"));
    exit;
}

// Validate ownership
$stmt = $pdo->prepare("
    SELECT m.*, c.teacher_id, c.title AS course_title
    FROM modules m
    JOIN courses c ON c.id = m.course_id
    WHERE m.id = ? AND c.id = ? AND c.teacher_id = ?
");
$stmt->execute([$module_id, $course_id, $teacher_id]);
$module = $stmt->fetch();

if (!$module) {
    $_SESSION['error'] = "Access denied or module not found.";
    header("Location: " . base_url("teacher/modules.php?course_id=$course_id"));
    exit;
}

$title = $module['title'];
$content = $module['content'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);

    if (empty($title)) {
        $errors[] = "Module title is required.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE modules SET title = ?, content = ? WHERE id = ? AND course_id = ?");
        $stmt->execute([$title, $content, $module_id, $course_id]);

        $_SESSION['success'] = "✅ Module updated.";
        header("Location: " . base_url("teacher/modules.php?course_id=$course_id"));
        exit;
    }
}

$page_title = "Edit Module";
include '../templates/header.php';
?>

<h2 class="mb-4">✏️ Edit Module - <?= htmlspecialchars($module['course_title']) ?></h2>

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

    <button type="submit" class="btn btn-primary">Save Changes</button>
    <a href="<?= base_url("teacher/modules.php?course_id=$course_id") ?>" class="btn btn-secondary">Cancel</a>
</form>

<?php include '../templates/footer.php'; ?>
