<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/session.php';

redirect_if_not_logged_in();
redirect_if_not_admin();

$title = '';
$description = '';
$teacher_id = '';
$errors = [];

// Get list of teachers
$stmt = $pdo->prepare("SELECT id, name FROM users WHERE role = 'teacher'");
$stmt->execute();
$teachers = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $teacher_id = $_POST['teacher_id'] ?? null;

    if (empty($title)) {
        $errors[] = "Course title is required.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO courses (title, description, teacher_id) VALUES (?, ?, ?)");
        $stmt->execute([$title, $description, $teacher_id ?: null]);

        $_SESSION['success'] = "✅ Course created successfully.";
        header("Location: " . base_url("admin/courses.php"));
        exit;
    }
}

$page_title = "Add New Course";
include '../templates/header.php';
?>

<h2 class="mb-4">➕ Add New Course</h2>

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

    <div class="mb-3">
        <label>Assign Teacher (optional)</label>
        <select name="teacher_id" class="form-select">
            <option value="">-- None --</option>
            <?php foreach ($teachers as $teacher): ?>
                <option value="<?= $teacher['id'] ?>" <?= $teacher_id == $teacher['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($teacher['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <button type="submit" class="btn btn-primary">Create Course</button>
    <a href="<?= base_url('admin/courses.php') ?>" class="btn btn-secondary">Cancel</a>
</form>

<?php include '../templates/footer.php'; ?>
