<?php
session_start();
include 'config.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_SESSION['email'])) {
        echo "User not logged in!";
        exit();
    }

    $email = $_SESSION['email'];
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];

    // Validate input fields
    if (empty($current_password) || empty($new_password)) {
        echo "All fields are required!";
        exit();
    }

    // Fetch the current password from the database
    $query = "SELECT password FROM users WHERE email='$email'";
    $result = mysqli_query($conn, $query);

    if (!$result) {
        die("Query Failed: " . mysqli_error($conn)); // Debugging SQL Errors
    }

    $user = mysqli_fetch_assoc($result);

    if (!$user) {
        echo "User not found!";
        exit();
    }

    // Verify current password
    if (!password_verify($current_password, $user['password'])) {
        echo "Incorrect current password!";
        exit();
    }

    // Hash new password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // Update the password in the database
    $update_query = "UPDATE users SET password='$hashed_password' WHERE email='$email'";

    if (mysqli_query($conn, $update_query)) {
        echo "Password updated successfully!";
    } else {
        echo "Error updating password: " . mysqli_error($conn);
    }
}
?>
