<?php 
session_start();
$timeout = 10 * 60; // 10 minutes in seconds

include "db_conn.php";

// Check if user is logged in and session variables are set
if (isset($_SESSION['username']) && isset($_SESSION['id'])) {
    
    // Check for inactivity timeout
    if (isset($_SESSION['LAST_ACTIVITY'])) {
        $elapsedTime = time() - $_SESSION['LAST_ACTIVITY'];
        if ($elapsedTime > $timeout) {
            session_unset(); 
            session_destroy();
            header("Location: logout.php"); // Redirect to logout page
            exit();
        }
    }

    $_SESSION['LAST_ACTIVITY'] = time(); // Update activity timestamp
?>

<!DOCTYPE html>
<html>
<head>
    <title>HOME</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-giJF6kkoqNQ00vy+HMDP7azOuL0xtbfIcaT9wjKHr8RbDVddVHyTfAAsrekwKmP1" crossorigin="anonymous">
</head>
<body>

    <div class="container d-flex justify-content-center align-items-center"
    style="min-height: 100vh">
        <?php if ($_SESSION['role'] == 'admin') { ?>
            <!-- For Admin -->
            <div class="card" style="width: 18rem;">
                <img src="img/admin-default.png" class="card-img-top" alt="admin image">
                <div class="card-body text-center">
                    <h5 class="card-title">
                        <?=$_SESSION['name']?>
                    </h5>
                    <a href="logout.php" class="btn btn-dark">Logout</a>
                </div>
            </div>
            <div class="p-3">
                <?php include 'php/members.php';
                if (mysqli_num_rows($res) > 0) { ?>
                
                <h1 class="display-4 fs-1">Members</h1>
                <table class="table" style="width: 32rem;">
                    <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Name</th>
                            <th scope="col">User name</th>
                            <th scope="col">Role</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $i = 1;
                        while ($rows = mysqli_fetch_assoc($res)) { ?>
                        <tr>
                            <th scope="row"><?=$i?></th>
                            <td><?=$rows['name']?></td>
                            <td><?=$rows['username']?></td>
                            <td><?=$rows['role']?></td>
                        </tr>
                        <?php $i++; } ?>
                    </tbody>
                </table>
                <?php } ?>
            </div>
        <?php } else { ?>
            <!-- For Users -->
            <div class="card" style="width: 18rem;">
                <img src="img/user-default.png" class="card-img-top" alt="user image">
                <div class="card-body text-center">
                    <h5 class="card-title">
                        <?=$_SESSION['name']?>
                    </h5>
                    <a href="logout.php" class="btn btn-dark">Logout</a>
                </div>
            </div>
        <?php } ?>
    </div>

    <!-- JavaScript to Log Out on Tab Close -->
    <script>
        window.addEventListener("beforeunload", function () {
            navigator.sendBeacon('logout.php');
        });
    </script>

</body>
</html>

<?php 
} else {
    header("Location: index.php");
    exit();
} 
?>