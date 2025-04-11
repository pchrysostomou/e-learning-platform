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

// Fetch quiz and course info
$quiz_stmt = $pdo->prepare("
    SELECT q.title, q.duration_minutes, c.name AS course_name
    FROM quizzes q
    LEFT JOIN courses c ON q.course_id = c.id
    WHERE q.id = ?
");
$quiz_stmt->execute([$quiz_id]);
$quiz_result = $quiz_stmt->fetch(PDO::FETCH_ASSOC);

$quiz_title = $quiz_result['title'] ?? "Unknown Quiz";
$course_name = $quiz_result['course_name'] ?? "N/A";
$duration = $quiz_result['duration_minutes'] ?? 0;

// Fetch leaderboard data
$stmt = $pdo->prepare("
    SELECT u.name, a.score, a.total_questions, a.time_taken_seconds, a.attempted_at
    FROM quiz_attempts a
    JOIN users u ON a.user_id = u.id
    WHERE a.quiz_id = ? AND u.role = 'student'
    ORDER BY a.score DESC, a.time_taken_seconds ASC
");
$stmt->execute([$quiz_id]);
$leaderboard = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Stats
$attempt_count = count($leaderboard);
$avg_score = 0;
if ($attempt_count > 0) {
    $total_scores = array_sum(array_column($leaderboard, 'score'));
    $avg_score = round($total_scores / $attempt_count, 2);
}

$page_title = "Leaderboard – $quiz_title";
include '../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-2 bg-light">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        <div class="col-md-10 py-4">
            <h2>🏆 Leaderboard for: <?= htmlspecialchars($quiz_title) ?></h2>
            <p><strong>Course:</strong> <?= htmlspecialchars($course_name) ?> | 
               <strong>Duration:</strong> <?= $duration ?> minutes</p>

            <?php if ($attempt_count === 0): ?>
                <div class="alert alert-warning">No attempts yet for this quiz.</div>
            <?php else: ?>
                <div class="alert alert-info mb-3">
                    <strong>Total Attempts:</strong> <?= $attempt_count ?> |
                    <strong>Average Score:</strong> <?= $avg_score ?> / <?= $leaderboard[0]['total_questions'] ?>
                </div>

                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Rank</th>
                            <th>Student</th>
                            <th>Score</th>
                            <th>Time Taken</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($leaderboard as $i => $row): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= htmlspecialchars($row['name']) ?></td>
                                <td><?= $row['score'] ?> / <?= $row['total_questions'] ?></td>
                                <td><?= $row['time_taken_seconds'] ?>s</td>
                                <td><?= date("Y-m-d H:i", strtotime($row['attempted_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

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
        </div>
    </div>
</div>

<?php include '../templates/footer.php'; ?>
