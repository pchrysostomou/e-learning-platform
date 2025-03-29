<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/session.php';

redirect_if_not_logged_in();
redirect_if_not_admin();

$course_id = isset($_GET['id']) ? (int) $_GET['id'] : null;
if (!$course_id) {
    header("Location: " . base_url("admin/courses.php"));
    exit;
}

// Fetch course
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
$stmt->execute([$course_id]);
$course = $stmt->fetch();

if (!$course) {
    $_SESSION['error'] = "❌ Course not found.";
    header("Location: " . base_url("admin/courses.php"));
    exit;
}

// Fetch all teachers
$stmt = $pdo->query("SELECT id, name FROM users WHERE role = 'teacher'");
$teachers = $stmt->fetchAll();

$title = $course['title'];
$description = $course['description'];
$teacher_id = $course['teacher_id'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $teacher_id = $_POST['teacher_id'] ?? null;

    if (empty($title)) {
        $errors[] = "Course title is required.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE courses SET title = ?, description = ?, teacher_id = ? WHERE id = ?");
        $stmt->execute([$title, $description, $teacher_id ?: null, $course_id]);

        $_SESSION['success'] = "✅ Course updated successfully.";
        header("Location: " . base_url("admin/courses.php"));
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

    <div class="mb-3">
        <label>Assign Teacher</label>
        <select name="teacher_id" class="form-select">
            <option value="">-- None --</option>
            <?php foreach ($teachers as $t): ?>
                <option value="<?= $t['id'] ?>" <?= $teacher_id == $t['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($t['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <button type="submit" class="btn btn-primary">Save Changes</button>
    <a href="<?= base_url('admin/courses.php') ?>" class="btn btn-secondary">Cancel</a>
</form>

<?php include '../templates/footer.php'; ?>
