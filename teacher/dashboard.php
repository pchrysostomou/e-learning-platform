<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/session.php';

redirect_if_not_logged_in();

if ($_SESSION['user_role'] !== 'teacher') {
    header("Location: " . base_url("users/dashboard.php"));
    exit;
}

$teacher_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM courses WHERE teacher_id = ?");
$stmt->execute([$teacher_id]);
$courses = $stmt->fetchAll();

$page_title = "Teacher Dashboard";
require_once '../templates/header.php';
?>

<h2 class="mb-4">🎓 Teacher Dashboard</h2>

<a href="<?= base_url('teacher/create_course.php') ?>" class="btn btn-success mb-3">➕ Create New Course</a>

<table class="table table-bordered table-striped">
    <thead>
        <tr>
            <th>Title</th>
            <th>Description</th>
            <th style="width: 150px;">Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($courses)): ?>
            <?php foreach ($courses as $course): ?>
                <tr>
                    <td><?= htmlspecialchars($course['title']) ?></td>
                    <td><?= htmlspecialchars($course['description']) ?></td>
                    <td>
                        <a href="<?= base_url('teacher/edit_course.php?id=' . $course['id']) ?>" class="btn btn-sm btn-warning">Edit</a>
                        <a href="<?= base_url('teacher/delete_course.php?id=' . $course['id']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this course?')">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="3" class="text-center text-muted">No courses created yet.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<?php require_once '../templates/footer.php'; ?>
