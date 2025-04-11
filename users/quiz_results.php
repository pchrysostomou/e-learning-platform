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

// Fetch quiz attempt
$stmt = $conn->prepare("
    SELECT qa.*, q.quiz_id, q.question_text, q.correct_answer, q.explanation
    FROM quiz_answers qa
    JOIN questions q ON qa.question_id = q.id
    WHERE qa.attempt_id = ?
");
$stmt->bind_param("i", $attempt_id);
$stmt->execute();
$result = $stmt->get_result();

$answers = [];
$quiz_title = "";
$total_correct = 0;

while ($row = $result->fetch_assoc()) {
    $answers[] = $row;
    if ($row['is_correct']) {
        $total_correct++;
    }
}
$stmt->close();

// Fetch quiz metadata
$quiz_stmt = $conn->prepare("
    SELECT q.title, a.total_questions
    FROM quiz_attempts a
    JOIN quizzes q ON q.id = a.quiz_id
    WHERE a.id = ?
");
$quiz_stmt->bind_param("i", $attempt_id);
$quiz_stmt->execute();
$quiz_meta = $quiz_stmt->get_result()->fetch_assoc();
$quiz_stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Quiz Results - <?= htmlspecialchars($quiz_meta['title']) ?></title>
    <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
</head>
<body class="container mt-5">
    <h2>🎉 Quiz Results: <?= htmlspecialchars($quiz_meta['title']) ?></h2>
    <div class="alert alert-info">
        You scored <strong><?= $total_correct ?></strong> out of <strong><?= $quiz_meta['total_questions'] ?></strong>
    </div>

    <?php foreach ($answers as $index => $a): ?>
        <div class="card mb-3">
            <div class="card-body">
                <h5>Q<?= $index + 1 ?>: <?= htmlspecialchars($a['question_text']) ?></h5>
                <p>Your Answer: 
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

    <a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a>
    <!-- You can link to leaderboard here if implemented -->
</body>
</html>
