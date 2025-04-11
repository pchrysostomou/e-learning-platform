<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

redirect_if_not_logged_in();

if ($_SESSION['user_role'] !== 'teacher') {
    header("Location: " . base_url("index.php"));
    exit;
}

$teacher_id = $_SESSION['user_id'];
$question_id = $_GET['id'] ?? null;
$quiz_id = $_GET['quiz_id'] ?? null;

if (!$question_id || !$quiz_id) {
    $_SESSION['error'] = "Missing quiz or question ID.";
    header("Location: quiz_list.php");
    exit;
}

// Verify ownership of the question and quiz
$check = $pdo->prepare("SELECT q.id FROM questions q JOIN quizzes z ON q.quiz_id = z.id JOIN courses c ON z.course_id = c.id WHERE q.id = ? AND z.id = ? AND c.teacher_id = ?");
$check->execute([$question_id, $quiz_id, $teacher_id]);
if (!$check->fetch()) {
    $_SESSION['error'] = "You do not have permission to delete this question.";
    header("Location: view_questions.php?quiz_id=$quiz_id");
    exit;
}

// Delete the question
$delete_stmt = $pdo->prepare("DELETE FROM questions WHERE id = ?");
$delete_stmt->execute([$question_id]);

$_SESSION['success'] = "✅ Question deleted.";
header("Location: view_questions.php?quiz_id=$quiz_id");
exit;
