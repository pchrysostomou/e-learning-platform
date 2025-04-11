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
$question_id = $_POST['question_id'] ?? null;
$quiz_id = $_POST['quiz_id'] ?? null;

if (!$question_id || !$quiz_id) {
    $_SESSION['error'] = "Missing question or quiz ID.";
    header("Location: question_bank.php");
    exit;
}

// Fetch original question
$stmt = $pdo->prepare("SELECT * FROM questions_bank WHERE id = ? AND teacher_id = ?");
$stmt->execute([$question_id, $teacher_id]);
$question = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$question) {
    $_SESSION['error'] = "Question not found or access denied.";
    header("Location: question_bank.php");
    exit;
}

// Copy question into same table with new quiz ID
$copy = $pdo->prepare("INSERT INTO questions_bank (teacher_id, course_id, quiz_id, question_text, type, option_a, option_b, option_c, option_d, correct_answer, hint, explanation) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$copy->execute([
    $teacher_id,
    $question['course_id'],
    $quiz_id,
    $question['question_text'],
    $question['type'],
    $question['option_a'],
    $question['option_b'],
    $question['option_c'],
    $question['option_d'],
    $question['correct_answer'],
    $question['hint'],
    $question['explanation']
]);

$_SESSION['success'] = "🔄 Question added to selected quiz.";
header("Location: question_bank.php");
exit;
