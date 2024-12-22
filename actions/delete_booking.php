<?php

session_start();
require_once('../db/config.php');


if (isset($_SESSION['user_id'])) {
    $userid = $_SESSION['user_id'];

    $query = "DELETE FROM appointments WHERE user_id = ?";
    
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("i", $userid); 
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            header('Location: ../view/admin_dashboard.php');
            exit();
        } else {

            echo "Error: No appointment found for this user.";
        }
    } else {
        echo "Error: Unable to prepare the query.";
    }
} else {
    header('Location: ../view/admin_dashboard.php');
    exit();
}
?>
