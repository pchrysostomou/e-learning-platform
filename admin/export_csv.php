<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/session.php';

redirect_if_not_logged_in();
redirect_if_not_admin();

// Get filters
$search = $_GET['search'] ?? '';
$role_filter = $_GET['role'] ?? '';

$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($role_filter)) {
    $where[] = "role = ?";
    $params[] = $role_filter;
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("SELECT name, email, role, is_active, created_at FROM users $where_sql ORDER BY created_at DESC");
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Output CSV
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="users_export.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Name', 'Email', 'Role', 'Active', 'Registered']);

foreach ($users as $user) {
    fputcsv($output, [
        $user['name'],
        $user['email'],
        ucfirst($user['role']),
        $user['is_active'] ? 'Yes' : 'No',
        $user['created_at']
    ]);
}

fclose($output);
exit;
