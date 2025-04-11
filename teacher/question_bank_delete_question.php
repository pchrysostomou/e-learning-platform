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
$question_id = $_GET['id'] ?? null;

if (!$question_id) {
    header("Location: question_bank.php");
    exit;
}

// Confirm question exists and belongs to teacher
$stmt = $pdo->prepare("SELECT id FROM questions_bank WHERE id = ? AND teacher_id = ?");
$stmt->execute([$question_id, $teacher_id]);
$question = $stmt->fetch();

if (!$question) {
    $_SESSION['error'] = "Question not found or access denied.";
    header("Location: question_bank.php");
    exit;
}

// Delete the question
$delete_stmt = $pdo->prepare("DELETE FROM questions_bank WHERE id = ? AND teacher_id = ?");
$delete_stmt->execute([$question_id, $teacher_id]);

$_SESSION['success'] = "❌ Question deleted successfully.";
header("Location: question_bank.php");
exit;
