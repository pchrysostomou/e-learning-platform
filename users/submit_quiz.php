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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quiz_id = $_POST['quiz_id'];
    $user_id = $_SESSION['user_id'];
    $answers = $_POST['answers'] ?? [];

    // Fetch correct answers
    $stmt = $conn->prepare("SELECT id, correct_answer FROM questions WHERE quiz_id = ?");
    $stmt->bind_param("i", $quiz_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $total_questions = $result->num_rows;
    $correct_count = 0;

    $correct_answers = [];
    while ($row = $result->fetch_assoc()) {
        $qid = $row['id'];
        $correct_answers[$qid] = trim(strtolower($row['correct_answer']));
    }

    $stmt->close();

    foreach ($correct_answers as $qid => $correct) {
        $given = isset($answers[$qid]) ? trim(strtolower($answers[$qid])) : '';
        if ($given === $correct) {
            $correct_count++;
        }
    }

    $score = $correct_count;
    $time_taken = 0; // You can pass time in future using JS or session if needed

    // Save attempt to DB
    $stmt = $conn->prepare("INSERT INTO quiz_attempts (quiz_id, user_id, score, total_questions, time_taken_seconds) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iiiii", $quiz_id, $user_id, $score, $total_questions, $time_taken);
    $stmt->execute();
    $attempt_id = $stmt->insert_id;
    $stmt->close();

    // Optionally save individual answers
    foreach ($answers as $question_id => $given_answer) {
        $is_correct = (trim(strtolower($given_answer)) === $correct_answers[$question_id]) ? 1 : 0;

        $stmt = $conn->prepare("INSERT INTO quiz_answers (attempt_id, question_id, selected_answer, is_correct) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iisi", $attempt_id, $question_id, $given_answer, $is_correct);
        $stmt->execute();
        $stmt->close();
    }

    // Redirect to results page (create this page next if you want)
    header("Location: quiz_results.php?attempt_id=" . $attempt_id);
    exit;
}
?>
