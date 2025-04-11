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
$quiz_id = $_GET['id'] ?? null;

if (!$quiz_id) {
    $_SESSION['error'] = "Missing quiz ID.";
    header("Location: quiz_list.php");
    exit;
}

// Verify ownership of the quiz
$check = $pdo->prepare("SELECT q.id FROM quizzes q JOIN courses c ON q.course_id = c.id WHERE q.id = ? AND c.teacher_id = ?");
$check->execute([$quiz_id, $teacher_id]);
if (!$check->fetch()) {
    $_SESSION['error'] = "You do not have permission to delete this quiz.";
    header("Location: quiz_list.php");
    exit;
}

// Delete all related questions first
$pdo->prepare("DELETE FROM questions WHERE quiz_id = ?")->execute([$quiz_id]);

// Delete the quiz
$pdo->prepare("DELETE FROM quizzes WHERE id = ?")->execute([$quiz_id]);

$_SESSION['success'] = "✅ Quiz and its questions were deleted successfully.";
header("Location: quiz_list.php");
exit;
