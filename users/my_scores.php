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
$page_title = "My Quiz Scores";

// ✅ Fetch all quiz attempts for this student using actual structure
$stmt = $pdo->prepare("
    SELECT qa.id, qa.quiz_id, qa.user_id, qa.score, qa.attempted_at,
           q.title AS quiz_title, q.week, c.title AS course_title
    FROM quiz_attempts qa
    JOIN quizzes q ON qa.quiz_id = q.id
    JOIN courses c ON q.course_id = c.id
    WHERE qa.user_id = ?
    ORDER BY qa.attempted_at DESC
");
$stmt->execute([$student_id]);
$attempts = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../templates/header.php';
?>

<div class="container py-4">
    <h2 class="mb-4">📊 My Quiz Scores</h2>

    <?php if (empty($attempts)): ?>
        <div class="alert alert-info">You haven’t taken any quizzes yet.</div>
    <?php else: ?>
        <table class="table table-bordered table-striped">
            <thead class="table-light">
                <tr>
                    <th>Course</th>
                    <th>Quiz</th>
                    <th>Week</th>
                    <th>Score</th>
                    <th>Attempted At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($attempts as $a): ?>
                    <tr>
                        <td><?= htmlspecialchars($a['course_title']) ?></td>
                        <td><?= htmlspecialchars($a['quiz_title']) ?></td>
                        <td>Week <?= htmlspecialchars($a['week']) ?></td>
                        <td><?= $a['score'] ?>%</td>
                        <td><?= date('d-m-Y H:i', strtotime($a['attempted_at'])) ?></td>
                        <td>
                            <a href="view_score.php?quiz_id=<?= $a['quiz_id'] ?>" class="btn btn-sm btn-outline-primary">📄 View</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once '../templates/footer.php'; ?>
