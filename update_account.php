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

    $current_email = $_SESSION['email']; // Get current session email
    $new_name = mysqli_real_escape_string($conn, $_POST['name']);
    $new_email = mysqli_real_escape_string($conn, $_POST['email']);
    $current_password = isset($_POST['current_password']) ? $_POST['current_password'] : "";
    $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : "";

    // Fetch user details
    $query = "SELECT * FROM users WHERE email='$current_email'";
    $result = mysqli_query($conn, $query);
    $user = mysqli_fetch_assoc($result);

    if (!$user) {
        echo "User not found!";
        exit();
    }

    // Update name and email
    $update_query = "UPDATE users SET name='$new_name', email='$new_email' WHERE email='$current_email'";
    if (!mysqli_query($conn, $update_query)) {
        echo "Error updating account details: " . mysqli_error($conn);
        exit();
    }

    // If password fields are filled, update password
    if (!empty($current_password) && !empty($new_password)) {
        if (!password_verify($current_password, $user['password'])) {
            echo "Incorrect current password!";
            exit();
        }

        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_password_query = "UPDATE users SET password='$hashed_password' WHERE email='$current_email'";

        if (!mysqli_query($conn, $update_password_query)) {
            echo "Error updating password: " . mysqli_error($conn);
            exit();
        }
    }

    // Update session variables
    $_SESSION['email'] = $new_email;
    $_SESSION['name'] = $new_name;

    echo "Account updated successfully!";
    header("Location: account.php"); // Redirect back to account page
    exit();
}
?>
