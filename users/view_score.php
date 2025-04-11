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
$quiz_id = $_GET['quiz_id'] ?? null;

if (!$quiz_id) {
    $_SESSION['error'] = "Quiz ID is missing.";
    header("Location: available_quizzes.php");
    exit;
}

// Get quiz info
$q_stmt = $pdo->prepare("SELECT q.*, c.title AS course_title FROM quizzes q JOIN courses c ON c.id = q.course_id WHERE q.id = ?");
$q_stmt->execute([$quiz_id]);
$quiz = $q_stmt->fetch(PDO::FETCH_ASSOC);

// Get attempt
$a_stmt = $pdo->prepare("SELECT * FROM quiz_attempts WHERE quiz_id = ? AND user_id = ?");
$a_stmt->execute([$quiz_id, $student_id]);
$attempt = $a_stmt->fetch(PDO::FETCH_ASSOC);

if (!$attempt) {
    $_SESSION['error'] = "You have not taken this quiz yet.";
    header("Location: available_quizzes.php");
    exit;
}

// Get all answers
$qa_stmt = $pdo->prepare("SELECT qa.*, q.question_text, q.correct_answer, q.type
    FROM quiz_answers qa
    JOIN questions q ON q.id = qa.question_id
    WHERE qa.quiz_id = ? AND qa.user_id = ?
    ORDER BY qa.question_id ASC");
$qa_stmt->execute([$quiz_id, $student_id]);
$answers = $qa_stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Quiz Results";
require_once '../templates/header.php';
?>

<div class="container py-4">
    <h2 class="mb-3">🧾 Results: <?= htmlspecialchars($quiz['title']) ?> (<?= htmlspecialchars($quiz['course_title']) ?>)</h2>

    <div class="alert alert-info">
        <strong>Score:</strong> <?= $attempt['score'] ?>%

    </div>

<div class="mb-3">
    <a href="export_score_pdf.php?quiz_id=<?= $quiz_id ?>" class="btn btn-outline-danger me-2">📥 Download PDF</a>
    <a href="export_score_csv.php?quiz_id=<?= $quiz_id ?>" class="btn btn-outline-success">📊 Download CSV</a>
</div>

    <?php if (empty($answers)): ?>
        <div class="alert alert-warning">No answers found for this quiz.</div>
    <?php else: ?>
        <?php foreach ($answers as $index => $ans): ?>
            <div class="border p-3 mb-3 <?= $ans['is_correct'] ? 'border-success' : 'border-danger' ?>">
                <strong>Q<?= $index + 1 ?>:</strong> <?= nl2br(htmlspecialchars($ans['question_text'])) ?>

                <div class="mt-2">
                    <strong>Your Answer:</strong>
                    <div>
                        <?php
                        if (in_array($ans['type'], ['matching', 'fill_blank_dropdown'])) {
                            $student = json_decode($ans['student_answer'], true);
                            if (is_array($student)) {
                                echo "<ul class='mb-0'>";
                                foreach ($student as $i => $val) {
                                    echo "<li>" . htmlspecialchars($val) . "</li>";
                                }
                                echo "</ul>";
                            } else {
                                echo htmlspecialchars($ans['student_answer']);
                            }
                        } else {
                            echo htmlspecialchars($ans['student_answer']);
                        }
                        ?>
                    </div>
                </div>

                <?php if (!$ans['is_correct']): ?>
                    <div class="mt-2 text-danger">
                        <strong>Correct Answer:</strong>
                        <?php
                        if (in_array($ans['type'], ['matching', 'fill_blank_dropdown'])) {
                            $correct = json_decode($ans['correct_answer'], true);
                            if (is_array($correct)) {
                                echo "<ul class='mb-0'>";
                                foreach ($correct as $item) {
                                    echo "<li>" . (is_array($item) ? htmlspecialchars($item['right']) : htmlspecialchars($item)) . "</li>";
                                }
                                echo "</ul>";
                            } else {
                                echo htmlspecialchars($ans['correct_answer']);
                            }
                        } else {
                            echo htmlspecialchars($ans['correct_answer']);
                        }
                        ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once '../templates/footer.php'; ?>
