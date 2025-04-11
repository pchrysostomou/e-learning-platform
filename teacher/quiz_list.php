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

// Fetch quizzes created by this teacher
$quiz_stmt = $pdo->prepare("SELECT q.id, q.title, q.duration_minutes, q.created_at, c.title AS course_title, m.title AS module_title FROM quizzes q JOIN courses c ON q.course_id = c.id LEFT JOIN modules m ON q.module_id = m.id WHERE c.teacher_id = ? ORDER BY q.created_at DESC");
$quiz_stmt->execute([$teacher_id]);
$quizzes = $quiz_stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "My Quizzes";
require_once '../templates/header.php';
require_once '../templates/sidebar.php';
?>

<div class="container py-4">
    <h2 class="mb-4">📋 My Quizzes</h2>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?= $_SESSION['success'] ?>
            <?php unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($quizzes)): ?>
        <div class="alert alert-info">You have not created any quizzes yet.</div>
    <?php else: ?>
        <table class="table table-bordered table-hover">
            <thead class="table-light">
                <tr>
                    <th>Title</th>
                    <th>Course</th>
                    <th>Module</th>
                    <th>Duration</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($quizzes as $quiz): ?>
                    <tr>
                        <td><?= htmlspecialchars($quiz['title']) ?></td>
                        <td><?= htmlspecialchars($quiz['course_title']) ?></td>
                        <td><?= htmlspecialchars($quiz['module_title'] ?? '-') ?></td>
                        <td><?= $quiz['duration_minutes'] ?> min</td>
                        <td><?= date('Y-m-d', strtotime($quiz['created_at'])) ?></td>
                        <td>
                            <a href="add_question.php?quiz_id=<?= $quiz['id'] ?>" class="btn btn-sm btn-success">➕ Add Question</a>
                            <a href="view_questions.php?quiz_id=<?= $quiz['id'] ?>" class="btn btn-sm btn-info">🔍 View Questions</a>
                            <a href="delete_quiz.php?id=<?= $quiz['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this quiz?')">🗑 Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once '../templates/footer.php'; ?>
