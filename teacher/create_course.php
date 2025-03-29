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

$title = '';
$description = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);

    if (empty($title)) {
        $errors[] = "Course title is required.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO courses (title, description, teacher_id) VALUES (?, ?, ?)");
        $stmt->execute([$title, $description, $teacher_id]);

        $_SESSION['success'] = "✅ Course created successfully.";
        header("Location: " . base_url("teacher/my_courses.php"));
        exit;
    }
}

$page_title = "Create Course";
include '../templates/header.php';
?>

<h2 class="mb-4">➕ Create Course</h2>

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
        <label>Description (optional)</label>
        <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($description) ?></textarea>
    </div>

    <button type="submit" class="btn btn-primary">Create Course</button>
    <a href="<?= base_url('teacher/my_courses.php') ?>" class="btn btn-secondary">Cancel</a>
</form>

<?php include '../templates/footer.php'; ?>
