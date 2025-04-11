<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/session.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'teacher') {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$teacher_id = $_SESSION['user_id'];
$question_id = $_GET['id'] ?? null;

if (!$question_id) {
    echo json_encode(['error' => 'No question ID provided']);
    exit;
}

// Get the question text
$stmt = $pdo->prepare("SELECT question_text FROM questions_bank WHERE id = ? AND teacher_id = ?");
$stmt->execute([$question_id, $teacher_id]);
$question = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$question) {
    echo json_encode(['error' => 'Question not found or unauthorized']);
    exit;
}

// Find all quizzes using the same question text for this teacher
$usage_stmt = $pdo->prepare("SELECT DISTINCT q.id, q.title FROM questions_bank qb JOIN quizzes q ON qb.quiz_id = q.id WHERE qb.teacher_id = ? AND qb.question_text = ?");
$usage_stmt->execute([$teacher_id, $question['question_text']]);
$usages = $usage_stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($usages);
