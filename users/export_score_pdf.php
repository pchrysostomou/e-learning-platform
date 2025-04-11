<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/session.php';
require_once '../vendor/autoload.php'; // TCPDF autoload

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

// Get student name
$student_stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
$student_stmt->execute([$student_id]);
$student_name = $student_stmt->fetchColumn();

// Get quiz info
$quiz_stmt = $pdo->prepare("SELECT q.*, c.title AS course_title FROM quizzes q JOIN courses c ON c.id = q.course_id WHERE q.id = ?");
$quiz_stmt->execute([$quiz_id]);
$quiz = $quiz_stmt->fetch(PDO::FETCH_ASSOC);

// Get attempt
$attempt_stmt = $pdo->prepare("SELECT * FROM quiz_attempts WHERE quiz_id = ? AND user_id = ?");
$attempt_stmt->execute([$quiz_id, $student_id]);
$attempt = $attempt_stmt->fetch(PDO::FETCH_ASSOC);

if (!$attempt) {
    die("You haven't taken this quiz.");
}

// Get answers
$answers_stmt = $pdo->prepare("SELECT qa.*, q.question_text, q.correct_answer, q.type
    FROM quiz_answers qa
    JOIN questions q ON q.id = qa.question_id
    WHERE qa.quiz_id = ? AND qa.user_id = ?
    ORDER BY qa.question_id ASC");
$answers_stmt->execute([$quiz_id, $student_id]);
$answers = $answers_stmt->fetchAll(PDO::FETCH_ASSOC);

// Generate PDF
$pdf = new TCPDF();
$pdf->SetCreator('Quiz System');
$pdf->SetAuthor('Online Learning Platform');
$pdf->SetTitle('Quiz Report');
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 12);

// Header section
$pdf->Write(0, "Quiz Report", '', 0, 'L', true);
$pdf->Ln(2);
$pdf->Write(0, "Student: " . $student_name, '', 0, 'L', true);
$pdf->Write(0, "Course: " . $quiz['course_title'], '', 0, 'L', true);
$pdf->Write(0, "Quiz: " . $quiz['title'], '', 0, 'L', true);
$pdf->Write(0, "Date: " . date("d-m-Y H:i"), '', 0, 'L', true);
$pdf->Write(0, "Score: " . $attempt['score'] . "%", '', 0, 'L', true);
$pdf->Ln(5);

// Questions loop
foreach ($answers as $i => $q) {
    $pdf->Write(0, "Q" . ($i + 1) . ": " . $q['question_text'], '', 0, 'L', true);

    $student_answer = is_json($q['student_answer']) ? implode(", ", json_decode($q['student_answer'], true)) : $q['student_answer'];
    $pdf->Write(0, "Your Answer: " . $student_answer, '', 0, 'L', true);

    if (!$q['is_correct']) {
        $correct = is_json($q['correct_answer'])
            ? implode(", ", array_map(fn($x) => is_array($x) ? $x['right'] : $x, json_decode($q['correct_answer'], true)))
            : $q['correct_answer'];
        $pdf->Write(0, "Correct Answer: " . $correct, '', 0, 'L', true);
    }

    $pdf->Ln(3);
}

$pdf->Output("Quiz_Results_{$quiz_id}.pdf", 'D');

// Helper function to detect JSON
function is_json($string) {
    json_decode($string);
    return json_last_error() === JSON_ERROR_NONE;
}
