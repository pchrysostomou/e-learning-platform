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
$format = $_GET['format'] ?? 'pdf';
$course_id = $_GET['course_id'] ?? 'all';

// Fetch quiz stats
if ($course_id !== 'all') {
    $stmt = $pdo->prepare("SELECT q.title, q.week, COUNT(a.id) AS attempts, COALESCE(ROUND(AVG(a.score), 2), 0) AS avg_score FROM quizzes q LEFT JOIN quiz_attempts a ON a.quiz_id = q.id JOIN courses c ON q.course_id = c.id WHERE c.teacher_id = ? AND q.course_id = ? GROUP BY q.id ORDER BY q.created_at");
    $stmt->execute([$teacher_id, $course_id]);
} else {
    $stmt = $pdo->prepare("SELECT q.title, q.week, COUNT(a.id) AS attempts, COALESCE(ROUND(AVG(a.score), 2), 0) AS avg_score FROM quizzes q LEFT JOIN quiz_attempts a ON a.quiz_id = q.id JOIN courses c ON q.course_id = c.id WHERE c.teacher_id = ? GROUP BY q.id ORDER BY q.created_at");
    $stmt->execute([$teacher_id]);
}

$quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);
$filename = "quiz_statistics_" . date('Ymd_His');

if ($format === 'excel') {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Quiz Stats');

    $sheet->fromArray(['Title', 'Week', 'Attempts', 'Average Score'], NULL, 'A1');

    $row = 2;
    foreach ($quizzes as $quiz) {
        $sheet->fromArray([
            $quiz['title'],
            'Week ' . $quiz['week'],
            $quiz['attempts'],
            $quiz['avg_score']
        ], NULL, 'A' . $row);
        $row++;
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header("Content-Disposition: attachment; filename=\"$filename.xlsx\"");
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;

} else {
    require_once('../vendor/tecnickcom/tcpdf/tcpdf.php');
    $pdf = new TCPDF();
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Write(0, "Quiz Statistics\n\n");

    $html = '<table border="1" cellpadding="5" cellspacing="0">'
          . '<thead><tr>'
          . '<th><strong>Title</strong></th>'
          . '<th><strong>Week</strong></th>'
          . '<th><strong>Attempts</strong></th>'
          . '<th><strong>Average Score</strong></th>'
          . '</tr></thead><tbody>';

    foreach ($quizzes as $quiz) {
        $html .= '<tr>'
              . '<td>' . htmlspecialchars($quiz['title']) . '</td>'
              . '<td>Week ' . $quiz['week'] . '</td>'
              . '<td>' . $quiz['attempts'] . '</td>'
              . '<td>' . $quiz['avg_score'] . '</td>'
              . '</tr>';
    }

    $html .= '</tbody></table>';

    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Output($filename . ".pdf", 'D');
    exit;
}
