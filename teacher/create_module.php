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
$title = '';
$content = '';
$errors = [];

if (!$course_id) {
    $_SESSION['error'] = "Course not specified.";
    header("Location: " . base_url("teacher/my_courses.php"));
    exit;
}

// ✅ Verify ownership of course
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ? AND teacher_id = ?");
$stmt->execute([$course_id, $teacher_id]);
$course = $stmt->fetch();

if (!$course) {
    $_SESSION['error'] = "You don't own this course.";
    header("Location: " . base_url("teacher/my_courses.php"));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);

    if (empty($title)) $errors[] = "Title is required.";

    if (empty($errors)) {
        // ✅ Insert module record
        $stmt = $pdo->prepare("INSERT INTO modules (course_id, title, content) VALUES (?, ?, ?)");
        $stmt->execute([$course_id, $title, $content]);
        $module_id = $pdo->lastInsertId();

        // ✅ Handle multiple file uploads
        if (!empty($_FILES['materials']['name'][0])) {
            $allowed = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'gif'];
            $upload_dir = __DIR__ . '/../uploads/materials/';

            foreach ($_FILES['materials']['name'] as $index => $name) {
                $tmp = $_FILES['materials']['tmp_name'][$index];
                $error = $_FILES['materials']['error'][$index];
                $size = $_FILES['materials']['size'][$index];
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

                if ($error === 0 && $size <= 10 * 1024 * 1024 && in_array($ext, $allowed)) {
                    $filename = uniqid('material_') . '.' . $ext;
                    $destination = $upload_dir . $filename;

                    if (move_uploaded_file($tmp, $destination)) {
                        // ✅ Save all file details
                        $stmt = $pdo->prepare("INSERT INTO module_materials (module_id, file_name, original_name, file_type, size) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([$module_id, $filename, $name, $ext, $size]);
                    } else {
                        $errors[] = "Failed to upload file: " . htmlspecialchars($name);
                    }
                } else {
                    $errors[] = "Invalid file or too large: " . htmlspecialchars($name);
                }
            }
        }

        if (empty($errors)) {
            log_activity($teacher_id, "Created new module '$title' for course '{$course['title']}'");
            $_SESSION['success'] = "✅ Module created.";
            header("Location: " . base_url("teacher/modules.php?course_id=$course_id"));
            exit;
        }
    }
}

$page_title = "Create Module";
include '../templates/header.php';
?>

<h2 class="mb-4">➕ Create Module for <?= htmlspecialchars($course['title']) ?></h2>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" class="card card-body shadow-sm" style="max-width: 700px;">
    <div class="mb-3">
        <label class="form-label">Module Title</label>
        <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($title) ?>" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Content / Description</label>
        <textarea name="content" rows="5" class="form-control"><?= htmlspecialchars($content) ?></textarea>
    </div>

    <div class="mb-3">
        <label class="form-label">Upload Materials (multiple files allowed)</label>
        <input type="file" name="materials[]" class="form-control" accept=".pdf,.doc,.docx,.ppt,.pptx,.jpg,.jpeg,.png,.gif" multiple>
        <div class="form-text">Max 10MB per file. Allowed: PDF, Word, PPT, Images.</div>
    </div>

    <button type="submit" class="btn btn-success">💾 Save Module</button>
    <a href="<?= base_url("teacher/modules.php?course_id=$course_id") ?>" class="btn btn-secondary">Cancel</a>
</form>

<?php include '../templates/footer.php'; ?>
