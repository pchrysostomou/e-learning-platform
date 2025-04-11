<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/session.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

redirect_if_not_logged_in();

if ($_SESSION['user_role'] !== 'teacher') {
    header("Location: " . base_url("users/dashboard.php"));
    exit;
}

$teacher_id = $_SESSION['user_id'];
$course_id = $_GET['course_id'] ?? 'all';
$quiz_id = $_GET['quiz_id'] ?? 'all';

$where = "WHERE qb.teacher_id = ?";
$params = [$teacher_id];

if ($course_id !== 'all') {
    $where .= " AND qb.course_id = ?";
    $params[] = $course_id;
}
if ($quiz_id !== 'all') {
    $where .= " AND qb.quiz_id = ?";
    $params[] = $quiz_id;
}

$stmt = $pdo->prepare("SELECT qb.*, c.title AS course_title, q.title AS quiz_title FROM questions_bank qb LEFT JOIN courses c ON qb.course_id = c.id LEFT JOIN quizzes q ON qb.quiz_id = q.id $where ORDER BY qb.created_at DESC");
$stmt->execute($params);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Question Bank');

$headers = [
    'Question', 'Type', 'Correct Answer', 'Option A', 'Option B', 'Option C', 'Option D', 'Hint', 'Explanation', 'Course', 'Quiz'
];
$sheet->fromArray($headers, NULL, 'A1');

$row = 2;
foreach ($questions as $q) {
    $sheet->fromArray([
        $q['question_text'],
        strtoupper($q['type']),
        $q['correct_answer'],
        $q['option_a'],
        $q['option_b'],
        $q['option_c'],
        $q['option_d'],
        $q['hint'],
        $q['explanation'],
        $q['course_title'],
        $q['quiz_title']
    ], NULL, 'A' . $row);
    $row++;
}

$filename = "question_bank_export_" . date('Ymd_His') . ".xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;