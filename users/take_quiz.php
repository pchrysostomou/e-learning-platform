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

$quiz_id = $_GET['id'] ?? null;
if (!$quiz_id) {
    die("Quiz ID is missing.");
}

// Fetch quiz
$stmt = $conn->prepare("SELECT * FROM quizzes WHERE id = ?");
$stmt->bind_param("i", $quiz_id);
$stmt->execute();
$quiz = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$quiz) {
    die("Quiz not found.");
}

// Fetch questions
$qstmt = $conn->prepare("SELECT * FROM questions WHERE quiz_id = ?");
$qstmt->bind_param("i", $quiz_id);
$qstmt->execute();
$result = $qstmt->get_result();
$questions = [];

while ($row = $result->fetch_assoc()) {
    $questions[] = $row;
}

shuffle($questions); // random order
?>

<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($quiz['title']) ?> - Quiz</title>
    <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
    <script>
        let timeLeft = <?= $quiz['duration_minutes'] * 60 ?>;

        function startTimer() {
            const timerDisplay = document.getElementById("timer");
            const interval = setInterval(() => {
                const minutes = Math.floor(timeLeft / 60);
                const seconds = timeLeft % 60;
                timerDisplay.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;

                if (timeLeft <= 0) {
                    clearInterval(interval);
                    alert("⏰ Time is up! Submitting quiz...");
                    document.getElementById("quizForm").submit();
                }

                timeLeft--;
            }, 1000);
        }

        function toggleHint(id) {
            const hintBox = document.getElementById("hint-" + id);
            hintBox.style.display = (hintBox.style.display === "none") ? "block" : "none";
        }

        window.onload = startTimer;
    </script>
</head>
<body class="container mt-4">
    <h2><?= htmlspecialchars($quiz['title']) ?></h2>
    <div class="alert alert-warning">⏱️ Time Left: <span id="timer"></span></div>

    <form method="POST" action="submit_quiz.php" id="quizForm">
        <input type="hidden" name="quiz_id" value="<?= $quiz_id ?>">

        <?php foreach ($questions as $index => $q): ?>
            <div class="card mb-3">
                <div class="card-body">
                    <h5>Q<?= $index + 1 ?>: <?= htmlspecialchars($q['question_text']) ?></h5>

                    <?php if ($q['type'] === 'mcq'): ?>
                        <?php foreach (['A', 'B', 'C', 'D'] as $opt): 
                            $key = 'option_' . strtolower($opt);
                            if (!empty($q[$key])): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="answers[<?= $q['id'] ?>]" value="<?= htmlspecialchars($q[$key]) ?>" required>
                                    <label class="form-check-label"><?= $opt ?>. <?= htmlspecialchars($q[$key]) ?></label>
                                </div>
                        <?php endif; endforeach; ?>

                    <?php elseif ($q['type'] === 'true_false'): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="answers[<?= $q['id'] ?>]" value="True" required>
                            <label class="form-check-label">True</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="answers[<?= $q['id'] ?>]" value="False" required>
                            <label class="form-check-label">False</label>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($q['hint'])): ?>
                        <button type="button" class="btn btn-sm btn-link" onclick="toggleHint(<?= $q['id'] ?>)">💡 Show Hint</button>
                        <div id="hint-<?= $q['id'] ?>" style="display: none;" class="alert alert-info mt-2">
                            <?= htmlspecialchars($q['hint']) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <button type="submit" class="btn btn-success">Submit Quiz</button>
    </form>
</body>
</html>
