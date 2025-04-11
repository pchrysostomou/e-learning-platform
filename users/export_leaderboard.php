<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/session.php';

redirect_if_not_logged_in();

$quiz_id = $_GET['quiz_id'] ?? null;
if (!$quiz_id) {
    die("Quiz ID not provided.");
}

// Fetch quiz title
$quiz_stmt = $conn->prepare("SELECT title FROM quizzes WHERE id = ?");
$quiz_stmt->bind_param("i", $quiz_id);
$quiz_stmt->execute();
$quiz_result = $quiz_stmt->get_result()->fetch_assoc();
$quiz_stmt->close();
$quiz_title = $quiz_result['title'] ?? "Unknown Quiz";

// Fetch leaderboard entries
$stmt = $conn->prepare("
    SELECT u.name, a.score, a.total_questions, a.time_taken_seconds, a.attempted_at
    FROM quiz_attempts a
    JOIN users u ON a.user_id = u.id
    WHERE a.quiz_id = ? AND u.role = 'student'
    ORDER BY a.score DESC, a.time_taken_seconds ASC
    LIMIT 20
");
$stmt->bind_param("i", $quiz_id);
$stmt->execute();
$leaderboard = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Leaderboard – <?= htmlspecialchars($quiz_title) ?></title>
    <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
</head>
<body class="container mt-5">
    <h2>🏆 Leaderboard for: <?= htmlspecialchars($quiz_title) ?></h2>

    <?php if (count($leaderboard) === 0): ?>
        <div class="alert alert-warning">No attempts yet for this quiz.</div>
    <?php else: ?>
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th class="w-25 text-wrap">Rank</th>
                    <th class="w-25 text-wrap">Student</th>
                    <th class="w-25 text-wrap">Score</th>
                    <th class="w-25 text-wrap">Time Taken</th>
                    <th class="w-25 text-wrap">Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($leaderboard as $i => $row): ?>
                    <tr>
                        <td class="text-wrap"><?= $i + 1 ?></td>
                        <td class="text-wrap"><?= htmlspecialchars($row['name']) ?></td>
                        <td class="text-wrap"><?= $row['score'] ?> / <?= $row['total_questions'] ?></td>
                        <td class="text-wrap"><?= $row['time_taken_seconds'] ?>s</td>
                        <td class="text-wrap"><?= date("d-m-Y H:i", strtotime($row['attempted_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Export Buttons (Teachers Only) -->
        <?php if ($_SESSION['user_role'] === 'teacher' || $_SESSION['user_role'] === 'admin'): ?>
            <div class="mt-4">
                <a href="export_leaderboard.php?quiz_id=<?= $quiz_id ?>&format=pdf" class="btn btn-danger me-2">
                    📄 Export PDF
                </a>
                <a href="export_leaderboard.php?quiz_id=<?= $quiz_id ?>&format=excel" class="btn btn-success">
                    📊 Export Excel
                </a>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <a href="dashboard.php" class="btn btn-primary mt-4">⬅ Back to Dashboard</a>
</body>
</html>
