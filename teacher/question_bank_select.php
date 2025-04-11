<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/session.php';

redirect_if_not_logged_in();

if ($_SESSION['user_role'] !== 'teacher') {
    header("Location: " . base_url("index.php"));
    exit;
}

$quiz_id = $_GET['quiz_id'] ?? null;
$teacher_id = $_SESSION['user_id'];
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_questions']) && $quiz_id) {
    $selected_ids = $_POST['selected_questions'];

    $inserted = 0;
    foreach ($selected_ids as $bank_id) {
        // Prevent duplicates
        $check = $pdo->prepare("SELECT COUNT(*) FROM questions WHERE quiz_id = ? AND qb_id = ?");
        $check->execute([$quiz_id, $bank_id]);
        if ($check->fetchColumn() > 0) continue;

        // Get question from bank
        $stmt = $pdo->prepare("SELECT * FROM questions_bank WHERE id = ? AND teacher_id = ?");
        $stmt->execute([$bank_id, $teacher_id]);
        $q = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$q) continue;

        $insert = $pdo->prepare("INSERT INTO questions 
            (quiz_id, qb_id, question_text, type, option_a, option_b, option_c, option_d, correct_answer, hint, explanation) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $insert->execute([
            $quiz_id,
            $bank_id,
            $q['question_text'],
            $q['question_type'],
            $q['option_a'],
            $q['option_b'],
            $q['option_c'],
            $q['option_d'],
            $q['correct_answer'],
            $q['hint'],
            $q['explanation']
        ]);

        $inserted++;
    }

    $_SESSION['success'] = "$inserted question(s) added from your bank.";
    header("Location: " . base_url("teacher/view_questions.php?quiz_id=$quiz_id"));
    exit;
}

// Filter questions not already in this quiz
$already_added_stmt = $pdo->prepare("SELECT qb_id FROM questions WHERE quiz_id = ? AND qb_id IS NOT NULL");
$already_added_stmt->execute([$quiz_id]);
$added_ids = $already_added_stmt->fetchAll(PDO::FETCH_COLUMN);

$in_clause = "";
if (count($added_ids)) {
    $placeholders = implode(',', array_fill(0, count($added_ids), '?'));
    $in_clause = "AND id NOT IN ($placeholders)";
}

$sql = "SELECT * FROM questions_bank WHERE teacher_id = ? $in_clause ORDER BY id DESC";
$params = array_merge([$teacher_id], $added_ids);
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Select Questions from Bank";
require_once '../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-2 bg-light"><?php include '../includes/sidebar.php'; ?></div>
        <div class="col-md-10 py-4">
            <h2>📋 Select Questions from Your Question Bank</h2>

            <form method="POST">
                <input type="hidden" name="quiz_id" value="<?= htmlspecialchars($quiz_id) ?>">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th></th>
                            <th>ID</th>
                            <th>Type</th>
                            <th>Question</th>
                            <th>Correct Answer</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($questions as $q): ?>
                            <tr>
                                <td><input type="checkbox" name="selected_questions[]" value="<?= $q['id'] ?>"></td>
                                <td><?= $q['id'] ?></td>
                                <td><?= strtoupper($q['question_type']) ?></td>
                                <td><?= htmlspecialchars($q['question_text']) ?></td>
                                <td><?= htmlspecialchars($q['correct_answer']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <button type="submit" class="btn btn-success">➕ Add Selected to Quiz</button>
                <a href="<?= base_url("teacher/view_questions.php?quiz_id=" . $quiz_id) ?>" class="btn btn-secondary">Back</a>
            </form>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?>
