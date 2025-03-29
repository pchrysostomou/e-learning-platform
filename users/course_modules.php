<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/session.php';

redirect_if_not_logged_in();

if ($_SESSION['user_role'] !== 'student') {
    header("Location: " . base_url("index.php"));
    exit;
}

$student_id = $_SESSION['user_id'];
$course_id = $_GET['course_id'] ?? null;

if (!$course_id) {
    $_SESSION['error'] = "Invalid course.";
    header("Location: " . base_url("users/dashboard.php"));
    exit;
}

// Verify student is enrolled
$stmt = $pdo->prepare("SELECT c.* FROM enrollments e JOIN courses c ON c.id = e.course_id WHERE e.course_id = ? AND e.student_id = ?");
$stmt->execute([$course_id, $student_id]);
$course = $stmt->fetch();

if (!$course) {
    $_SESSION['error'] = "You are not enrolled in this course.";
    header("Location: " . base_url("users/dashboard.php"));
    exit;
}

// Get modules for this course
$stmt = $pdo->prepare("SELECT * FROM modules WHERE course_id = ? ORDER BY created_at ASC");
$stmt->execute([$course_id]);
$modules = $stmt->fetchAll();

// Get completed module IDs
$stmt = $pdo->prepare("SELECT module_id FROM module_progress WHERE student_id = ?");
$stmt->execute([$student_id]);
$completed_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

$page_title = "Course Modules";
include '../templates/header.php';
?>

<h2 class="mb-3"><?= htmlspecialchars($course['title']) ?></h2>
<p class="text-muted"><?= nl2br(htmlspecialchars($course['description'])) ?></p>

<h4 class="mt-4 mb-3">📘 Modules</h4>

<?php if (!empty($modules)): ?>
    <ul class="list-group">
        <?php foreach ($modules as $i => $m): ?>
            <li class="list-group-item d-flex justify-content-between align-items-start <?= in_array($m['id'], $completed_ids) ? 'bg-success bg-opacity-10' : '' ?>">
                <div>
                    <h6 class="mb-1"><?= ($i + 1) . '. ' . htmlspecialchars($m['title']) ?></h6>
                    <p class="mb-1"><?= nl2br(htmlspecialchars($m['content'])) ?></p>
                </div>
                <div class="form-check ms-3">
                    <input type="checkbox"
                           class="form-check-input progress-check"
                           data-module-id="<?= $m['id'] ?>"
                           <?= in_array($m['id'], $completed_ids) ? 'checked' : '' ?>>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
<?php else: ?>
    <div class="alert alert-info">No modules available for this course.</div>
<?php endif; ?>

<script>
document.querySelectorAll('.progress-check').forEach(box => {
    box.addEventListener('change', () => {
        const moduleId = box.dataset.moduleId;
        const checked = box.checked ? 1 : 0;

        fetch("<?= base_url('users/update_progress.php') ?>", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `module_id=${moduleId}&completed=${checked}`
        }).then(() => {
            box.closest('li').classList.toggle('bg-success', checked);
            box.closest('li').classList.toggle('bg-opacity-10', checked);
        });
    });
});
</script>

<?php include '../templates/footer.php'; ?>
