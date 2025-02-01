<?php
function check_db_connection($db_hostname, $db_username, $db_password, $db_port) {
    // Attempt to connect to the database
    $conn = new mysqli($$db_hostname,$db_username, $db_password, "", $db_port);

    // Check connection
    if ($conn->connect_error) {
        return "Connection failed: " . $conn->connect_error;
    } else {
        $conn->close();
        return "Connection successful";
    }
}

?>