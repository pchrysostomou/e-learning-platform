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
$quiz_id = $_POST['quiz_id'] ?? null;
$submitted_answers = $_POST['answers'] ?? [];

if (!$quiz_id || empty($submitted_answers)) {
    $_SESSION['error'] = "Invalid quiz submission.";
    header("Location: available_quizzes.php");
    exit;
}

// Prevent retake
$check = $pdo->prepare("SELECT COUNT(*) FROM quiz_attempts WHERE user_id = ? AND quiz_id = ?");
$check->execute([$student_id, $quiz_id]);
if ($check->fetchColumn() > 0) {
    $_SESSION['info'] = "You have already taken this quiz.";
    header("Location: view_score.php?quiz_id=$quiz_id");
    exit;
}

// Fetch quiz questions
$q_stmt = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = ?");
$q_stmt->execute([$quiz_id]);
$questions = $q_stmt->fetchAll(PDO::FETCH_ASSOC);

$total = count($questions);
$correct = 0;
$answers_to_insert = [];

foreach ($questions as $q) {
    $qid = $q['id'];
    $type = $q['type'];
    $correct_answer = $q['correct_answer'];

    if (!isset($submitted_answers[$qid])) continue;

    $student_answer = $submitted_answers[$qid];
    $is_correct = false;

    if ($type === 'mcq' || $type === 'true_false') {
        $is_correct = trim($student_answer) === trim($correct_answer);
    } elseif ($type === 'matching') {
        $expected_pairs = json_decode($correct_answer, true);
        if (is_array($expected_pairs) && is_array($student_answer)) {
            $is_correct = true;
            foreach ($expected_pairs as $i => $pair) {
                if (!isset($student_answer[$i]) || trim(strtolower($student_answer[$i])) !== trim(strtolower($pair['right']))) {
                    $is_correct = false;
                    break;
                }
            }
        }
    } elseif ($type === 'fill_blank_dropdown') {
        $expected = json_decode($correct_answer, true);
        if (is_array($expected) && is_array($student_answer)) {
            $is_correct = true;
            foreach ($expected as $i => $correct_piece) {
                if (!isset($student_answer[$i]) || trim($student_answer[$i]) !== trim($correct_piece)) {
                    $is_correct = false;
                    break;
                }
            }
        }
    }

    if ($is_correct) $correct++;

    $answers_to_insert[] = [
        'question_id' => $qid,
        'answer' => is_array($student_answer) ? json_encode($student_answer) : $student_answer,
        'is_correct' => $is_correct ? 1 : 0
    ];
}

// Save quiz attempt
$score = round(($correct / $total) * 100, 2);
$insert_attempt = $pdo->prepare("INSERT INTO quiz_attempts (user_id, quiz_id, score, total_questions) VALUES (?, ?, ?, ?)");
$insert_attempt->execute([$student_id, $quiz_id, $score, $total]);
$attempt_id = $pdo->lastInsertId(); // ✅ GET attempt_id to use in answers

// Save individual answers with attempt_id
$insert_answer = $pdo->prepare("INSERT INTO quiz_answers (attempt_id, user_id, quiz_id, question_id, student_answer, is_correct) VALUES (?, ?, ?, ?, ?, ?)");
foreach ($answers_to_insert as $ans) {
    $insert_answer->execute([
        $attempt_id,
        $student_id,
        $quiz_id,
        $ans['question_id'],
        $ans['answer'],
        $ans['is_correct']
    ]);
}

// Redirect to results
header("Location: quiz_results.php?attempt_id=$attempt_id");
exit;
