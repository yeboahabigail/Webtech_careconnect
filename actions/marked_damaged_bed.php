<?php
include 'db_connect.php';

if (isset($_POST['damaged_bed_id'])) {
    $damaged_bed_id = $_POST['damaged_bed_id'];

    // Mark the bed as damaged
    $sql = "UPDATE beds SET status = 'damaged' WHERE bed_id = $damaged_bed_id";
    if (mysqli_query($conn, $sql)) {
        echo "Bed marked as damaged.";
    } else {
        echo "Error marking bed as damaged: " . mysqli_error($conn);
    }
}
?>
