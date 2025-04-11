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
    die("Missing quiz ID.");
}

// Get student info
$student_stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
$student_stmt->execute([$student_id]);
$student_name = $student_stmt->fetchColumn();

// Get quiz title
$quiz_stmt = $pdo->prepare("SELECT title FROM quizzes WHERE id = ?");
$quiz_stmt->execute([$quiz_id]);
$quiz_title = $quiz_stmt->fetchColumn();

// Get answers
$stmt = $pdo->prepare("SELECT qa.*, q.question_text, q.correct_answer, q.type
    FROM quiz_answers qa
    JOIN questions q ON q.id = qa.question_id
    WHERE qa.quiz_id = ? AND qa.user_id = ?
    ORDER BY q.id");
$stmt->execute([$quiz_id, $student_id]);
$answers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Output headers
header("Content-Type: text/csv");
header("Content-Disposition: attachment; filename=Quiz_Results_{$quiz_id}.csv");

$output = fopen("php://output", "w");

// CSV Headers
fputcsv($output, ["Student", "Quiz Title", "Date", "Question", "Your Answer", "Correct Answer", "Type", "Correct"]);

foreach ($answers as $row) {
    $date = date("d-m-Y H:i");
    $student_answer = is_array(json_decode($row['student_answer'], true))
        ? implode(", ", json_decode($row['student_answer'], true))
        : $row['student_answer'];

    $correct_answer = is_array(json_decode($row['correct_answer'], true))
        ? implode(", ", array_map(fn($x) => is_array($x) ? $x['right'] : $x, json_decode($row['correct_answer'], true)))
        : $row['correct_answer'];

    fputcsv($output, [
        $student_name,
        $quiz_title,
        $date,
        $row['question_text'],
        $student_answer,
        $correct_answer,
        $row['type'],
        $row['is_correct'] ? "Yes" : "No"
    ]);
}

fclose($output);
exit;
