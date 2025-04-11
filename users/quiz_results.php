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

$attempt_id = $_GET['attempt_id'] ?? null;
if (!$attempt_id) {
    die("Invalid attempt ID.");
}

// ✅ FIXED: Correct WHERE clause for fetching all answers in this attempt
$answer_stmt = $pdo->prepare("
    SELECT qa.*, q.quiz_id, q.question_text, q.correct_answer, q.explanation
    FROM quiz_answers qa
    JOIN questions q ON qa.question_id = q.id
    WHERE qa.attempt_id = ?
");
$answer_stmt->execute([$attempt_id]);
$answers = $answer_stmt->fetchAll(PDO::FETCH_ASSOC);

// Count correct answers
$total_correct = 0;
foreach ($answers as $a) {
    if ($a['is_correct']) {
        $total_correct++;
    }
}

// ✅ FIXED: This query was fine
$quiz_stmt = $pdo->prepare("
    SELECT q.title, a.total_questions
    FROM quiz_attempts a
    JOIN quizzes q ON q.id = a.quiz_id
    WHERE a.id = ?
");
$quiz_stmt->execute([$attempt_id]);
$quiz_meta = $quiz_stmt->fetch(PDO::FETCH_ASSOC);

$page_title = "Quiz Results: " . htmlspecialchars($quiz_meta['title']);
require_once '../templates/header.php';
require_once '../templates/sidebar.php'; // Sidebar for student
?>

<div class="container py-4">
    <h2 class="mb-3">🎉 Quiz Results: <?= htmlspecialchars($quiz_meta['title']) ?></h2>
    <div class="alert alert-info">
        You scored <strong><?= $total_correct ?></strong> out of <strong><?= $quiz_meta['total_questions'] ?? count($answers) ?></strong>
    </div>

    <?php foreach ($answers as $index => $a): ?>
        <div class="card mb-3">
            <div class="card-body">
                <h5>Q<?= $index + 1 ?>: <?= htmlspecialchars($a['question_text']) ?></h5>
                <p>
                    Your Answer:
                    <strong class="<?= $a['is_correct'] ? 'text-success' : 'text-danger' ?>">
                        <?= htmlspecialchars($a['selected_answer']) ?> <?= $a['is_correct'] ? '✔️' : '❌' ?>
                    </strong>
                </p>
                <p>Correct Answer: <strong><?= htmlspecialchars($a['correct_answer']) ?></strong></p>
                <?php if (!empty($a['explanation'])): ?>
                    <div class="alert alert-secondary">
                        <strong>Explanation:</strong> <?= htmlspecialchars($a['explanation']) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <a href="dashboard.php" class="btn btn-primary">🔙 Back to Dashboard</a>
</div>

<?php require_once '../templates/footer.php'; ?>
