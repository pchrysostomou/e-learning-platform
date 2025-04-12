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

$page_title = "Available Quizzes";

// Fetch quizzes from enrolled courses
$stmt = $pdo->prepare("
    SELECT q.*, c.title AS course_title, m.title AS module_title,
           (SELECT COUNT(*) FROM quiz_attempts qa WHERE qa.quiz_id = q.id AND qa.user_id = ?) AS attempted
    FROM quizzes q
    JOIN courses c ON q.course_id = c.id
    LEFT JOIN modules m ON q.module_id = m.id
    WHERE q.course_id IN (
        SELECT course_id FROM enrollments WHERE student_id = ?
    )
    ORDER BY q.created_at DESC
");
$stmt->execute([$student_id, $student_id]);
$quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../templates/header.php';
?>

<div class="container py-4">
    <h2 class="mb-4">📝 Available Quizzes</h2>

    <?php if (empty($quizzes)): ?>
        <div class="alert alert-info">You have no available quizzes at the moment.</div>
    <?php else: ?>
        <table class="table table-bordered table-responsive">
            <thead class="table-light">
                <tr>
                    <th>Course</th>
                    <th>Module</th>
                    <th>Quiz Title</th>
                    <th>Week</th>
                    <th>Status</ths=>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($quizzes as $quiz): ?>
                    <tr>
                        <td class="text-wrap"><?= htmlspecialchars($quiz['course_title']) ?></td>
                        <td class="text-wrap"><?= htmlspecialchars($quiz['module_title'] ?? '-') ?></td>
                        <td class="text-wrap"><?= htmlspecialchars($quiz['title']) ?></td>
                        <td class="text-wrap">Week <?= htmlspecialchars($quiz['week']) ?></td>
                        <td class="text-wrap">
                            <?php if ($quiz['attempted'] > 0): ?>
                                <span class="badge bg-secondary">Attempted</span>
                            <?php else: ?>
                                <span class="badge bg-success">Not Attempted</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-wrap">
                            <?php if ($quiz['attempted'] == 0): ?>
                                <a href="<?= base_url("users/take_quiz.php?quiz_id=" . $quiz['id']) ?>" class="btn btn-sm btn-primary">Take Quiz</a>
                            <?php else: ?>
                                <a href="<?= base_url("users/view_score.php?quiz_id=" . $quiz['id']) ?>" class="btn btn-sm btn-outline-info">View Score</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once '../templates/footer.php'; ?>
