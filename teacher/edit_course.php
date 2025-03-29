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
$course_id = $_GET['id'] ?? null;

if (!$course_id) {
    $_SESSION['error'] = "Missing course ID.";
    header("Location: " . base_url("teacher/my_courses.php"));
    exit;
}

// Ensure course belongs to teacher
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ? AND teacher_id = ?");
$stmt->execute([$course_id, $teacher_id]);
$course = $stmt->fetch();

if (!$course) {
    $_SESSION['error'] = "❌ Course not found or access denied.";
    header("Location: " . base_url("teacher/my_courses.php"));
    exit;
}

$title = $course['title'];
$description = $course['description'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);

    if (empty($title)) {
        $errors[] = "Course title is required.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE courses SET title = ?, description = ? WHERE id = ? AND teacher_id = ?");
        $stmt->execute([$title, $description, $course_id, $teacher_id]);

        $_SESSION['success'] = "✅ Course updated successfully.";
        header("Location: " . base_url("teacher/my_courses.php"));
        exit;
    }
}

$page_title = "Edit Course";
include '../templates/header.php';
?>

<h2 class="mb-4">✏️ Edit Course</h2>

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
        <label>Course Title</label>
        <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($title) ?>" required>
    </div>

    <div class="mb-3">
        <label>Description</label>
        <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($description) ?></textarea>
    </div>

    <button type="submit" class="btn btn-primary">Save Changes</button>
    <a href="<?= base_url('teacher/my_courses.php') ?>" class="btn btn-secondary">Cancel</a>
</form>

<?php include '../templates/footer.php'; ?>
