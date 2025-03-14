<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: login_register.php");
    exit();
}

include 'config.php';

$email = $_SESSION['email'];
$query = "SELECT * FROM users WHERE email='$email'";
$result = mysqli_query($conn, $query);
$user = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="sidebar">
        <h2>Logo</h2>
        <ul>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="enrollments.php">Enrollments</a></li>
            <li><a href="courses.php">Courses</a></li>
            <li><a href="account.php">Account</a></li>
        </ul>
    </div>
    
    <div class="content">
        <h2>Account</h2>
        <form method="post" action="update_account.php">
            <h3>Update Account Details</h3>
            <input type="text" name="name" placeholder="Full Name" value="<?php echo htmlspecialchars($user['name']); ?>" required><br>
            <input type="email" name="email" placeholder="Email" value="<?php echo htmlspecialchars($user['email']); ?>" required><br>

            <h3>Change Password</h3>
            <input type="password" name="current_password" placeholder="Current Password"><br>
            <input type="password" name="new_password" placeholder="New Password"><br>
            
            <button type="submit">Update</button>
        </form>
    </div>
</body>
</html>
