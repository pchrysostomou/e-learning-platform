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
$success = null;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];

    if ($file['error'] === 0 && pathinfo($file['name'], PATHINFO_EXTENSION) === 'csv') {
        $handle = fopen($file['tmp_name'], 'r');
        $headers = fgetcsv($handle);

        if (!$headers) {
            $errors[] = "CSV file is empty or malformed.";
        } else {
            // Fix header mapping
            $headers = array_map(function($h) {
                return $h === 'question_type' ? 'type' : strtolower(trim($h));
            }, $headers);

            $inserted = 0;

            // ✅ Prepare the insert statement once
            $stmt = $pdo->prepare("
                INSERT INTO questions_bank 
                (teacher_id, question_text, type, correct_answer, option_a, option_b, option_c, option_d, hint, explanation)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            while (($row = fgetcsv($handle)) !== false) {
                $data = array_combine($headers, $row);

                if (!isset($data['question_text']) || !isset($data['type']) || !isset($data['correct_answer'])) {
                    continue; // skip invalid rows
                }

                $stmt->execute([
                    $teacher_id,
                    $data['question_text'],
                    strtolower($data['type']),
                    $data['correct_answer'],
                    $data['option_a'] ?? null,
                    $data['option_b'] ?? null,
                    $data['option_c'] ?? null,
                    $data['option_d'] ?? null,
                    $data['hint'] ?? null,
                    $data['explanation'] ?? null
                ]);

                $inserted++;
            }

            fclose($handle);
            $success = "$inserted questions imported successfully.";
        }
    } else {
        $errors[] = "Invalid file type. Please upload a .csv file.";
    }
}

$page_title = "Import Questions to Question Bank";
require_once '../templates/header.php';
?>

<h2 class="mb-4">📂 Import Questions from CSV</h2>

<?php if ($success): ?>
    <div class="alert alert-success"><?= $success ?></div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<p>Upload a CSV file with the following headers:</p>
<ul>
    <li><strong>question_type</strong> (e.g. mcq, true_false, matching, fill_blank_dropdown)</li>
    <li><strong>question_text</strong> (required)</li>
    <li><strong>correct_answer</strong> (required)</li>
    <li>option_a, option_b, option_c, option_d (optional for MCQ)</li>
    <li>hint, explanation (optional)</li>
</ul>

<form method="POST" enctype="multipart/form-data">
    <div class="mb-3">
        <label class="form-label">CSV File</label>
        <input type="file" name="csv_file" accept=".csv" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-primary">Import Questions</button>
    <a href="question_bank.php" class="btn btn-secondary">Cancel</a>
</form>

<?php require_once '../templates/footer.php'; ?>
