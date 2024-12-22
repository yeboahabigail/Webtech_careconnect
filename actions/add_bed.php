<?php
include 'db_connect.php';

if (isset($_POST['add_bed'])) {
    $add_bed = $_POST['add_bed'];
    
    // Add beds to the database
    for ($i = 0; $i < $add_bed; $i++) {
        $sql = "INSERT INTO beds (status) VALUES ('is_available')";
        if (mysqli_query($conn, $sql)) {
            echo "Bed added successfully.";
        } else {
            echo "Error adding bed: " . mysqli_error($conn);
        }
    }
}
?>
