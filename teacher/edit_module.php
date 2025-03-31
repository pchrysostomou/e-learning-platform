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
    $_SESSION['error'] = "Module or course ID missing.";
    header("Location: " . base_url("teacher/my_courses.php"));
    exit;
}

// ✅ Check course ownership
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ? AND teacher_id = ?");
$stmt->execute([$course_id, $teacher_id]);
$course = $stmt->fetch();

if (!$course) {
    $_SESSION['error'] = "Unauthorized access.";
    header("Location: " . base_url("teacher/my_courses.php"));
    exit;
}

// ✅ Get module
$stmt = $pdo->prepare("SELECT * FROM modules WHERE id = ? AND course_id = ?");
$stmt->execute([$module_id, $course_id]);
$module = $stmt->fetch();

if (!$module) {
    $_SESSION['error'] = "Module not found.";
    header("Location: " . base_url("teacher/modules.php?course_id=$course_id"));
    exit;
}

$title = $module['title'];
$content = $module['content'];
$errors = [];

// ✅ Fetch existing materials
$mat_stmt = $pdo->prepare("SELECT * FROM module_materials WHERE module_id = ?");
$mat_stmt->execute([$module_id]);
$existing_materials = $mat_stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);

    if (empty($title)) $errors[] = "Title is required.";

    // ✅ Handle new uploads
    if (!empty($_FILES['materials']['name'][0])) {
        $allowed = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'gif'];
        $upload_dir = __DIR__ . '/../uploads/materials/';

        foreach ($_FILES['materials']['name'] as $key => $name) {
            $tmp = $_FILES['materials']['tmp_name'][$key];
            $error = $_FILES['materials']['error'][$key];
            $size = $_FILES['materials']['size'][$key];

            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

            if (!in_array($ext, $allowed)) {
                $errors[] = "$name: Invalid file type.";
                continue;
            }
            if ($error !== 0 || $size > 10 * 1024 * 1024) {
                $errors[] = "$name: Upload error or too large.";
                continue;
            }

            $new_name = uniqid('material_') . '.' . $ext;
            $dest = $upload_dir . $new_name;

            if (move_uploaded_file($tmp, $dest)) {
                // Insert into module_materials
                $stmt = $pdo->prepare("INSERT INTO module_materials (module_id, file_name, original_name, file_type, size) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$module_id, $new_name, $name, $ext, $size]);
            } else {
                $errors[] = "$name: Failed to save.";
            }
        }
    }

    // ✅ Save title/content
    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE modules SET title = ?, content = ? WHERE id = ?");
        $stmt->execute([$title, $content, $module_id]);

        log_activity($teacher_id, "Updated module '$title'");
        $_SESSION['success'] = "✅ Module updated.";
        header("Location: " . base_url("teacher/modules.php?course_id=$course_id"));
        exit;
    }
}

$page_title = "Edit Module";
include '../templates/header.php';
?>

<h2 class="mb-4">✏️ Edit Module: <?= htmlspecialchars($course['title']) ?></h2>

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
        <label class="form-label">Content</label>
        <textarea name="content" rows="5" class="form-control"><?= htmlspecialchars($content) ?></textarea>
    </div>

    <!-- ✅ Show existing materials -->
    <?php if ($existing_materials): ?>
        <div class="mb-3">
            <label class="form-label">Existing Materials:</label>
            <ul class="list-group">
                <?php foreach ($existing_materials as $mat): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <a href="<?= base_url('uploads/materials/' . $mat['file_name']) ?>" target="_blank">
                            <?= htmlspecialchars($mat['original_name'] ?: $mat['file_name']) ?>
                        </a>
                        <a href="<?= base_url('teacher/delete_material.php?id=' . $mat['id'] . '&module_id=' . $module_id . '&course_id=' . $course_id) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this file?')">🗑</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="mb-3">
        <label class="form-label">Upload More Materials</label>
        <input type="file" name="materials[]" class="form-control" accept=".pdf,.doc,.docx,.ppt,.pptx,.jpg,.jpeg,.png,.gif" multiple>
        <div class="form-text">Max 10MB each. Allowed: PDF, Word, PPT, Images.</div>
    </div>

    <button type="submit" class="btn btn-primary">💾 Save Changes</button>
    <a href="<?= base_url("teacher/modules.php?course_id=$course_id") ?>" class="btn btn-secondary">Cancel</a>
</form>

<?php include '../templates/footer.php'; ?>
