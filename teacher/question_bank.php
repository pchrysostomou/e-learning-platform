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

$teacher_id = $_SESSION['user_id'];
$page_title = "Question Bank";

// Fetch all question bank entries by teacher
$stmt = $pdo->prepare("SELECT * FROM questions_bank WHERE teacher_id = ? ORDER BY id DESC");
$stmt->execute([$teacher_id]);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-2 bg-light">
            <?php include '../includes/sidebar.php'; ?>
        </div>

        <div class="col-md-10 py-4">
            <h2 class="mb-4">📚 Question Bank</h2>

            <div class="mb-3">
                <a href="add_question.php" class="btn btn-primary">➕ Add New Question</a>
                <a href="question_bank_import.php" class="btn btn-outline-success">📥 Import CSV</a>
            </div>

            <?php if (empty($questions)): ?>
                <div class="alert alert-warning">No questions found in your bank yet.</div>
            <?php else: ?>
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Type</th>
                            <th>Question</th>
                            <th>Correct Answer</th>
                            <th>Details</th>
                            <th style="width: 160px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($questions as $q): ?>
                            <tr>
                                <td><?= $q['id'] ?></td>
                                <td><?= strtoupper($q['type']) ?></td>
                                <td><?= nl2br(htmlspecialchars($q['question_text'])) ?></td>
                                <td>
                                    <?php
                                        if ($q['type'] === 'matching') {
                                            echo '<code>' . htmlspecialchars(substr($q['correct_answer'], 0, 80)) . '...</code>';
                                        } elseif ($q['type'] === 'fill_blank_dropdown') {
                                            echo '<code>' . htmlspecialchars($q['correct_answer']) . '</code>';
                                        } else {
                                            echo htmlspecialchars($q['correct_answer']);
                                        }
                                    ?>
                                </td>
                                <td>
                                    <?php if (!empty($q['hint'])): ?>
                                        <div><strong>Hint:</strong> <?= htmlspecialchars($q['hint']) ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($q['explanation'])): ?>
                                        <div><strong>Explanation:</strong> <?= htmlspecialchars($q['explanation']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="question_bank_edit_question.php?id=<?= $q['id'] ?>" class="btn btn-sm btn-warning">✏ Edit</a>
                                    <a href="question_bank_delete_question.php?id=<?= $q['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this question?')">🗑 Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?>
