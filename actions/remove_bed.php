<?php
include 'db_connect.php';

if (isset($_POST['remove_bed'])) {
    $remove_bed = $_POST['remove_bed'];

    // Remove beds from the database
    $sql = "DELETE FROM beds WHERE status = 'is_available' LIMIT $remove_bed";
    if (mysqli_query($conn, $sql)) {
        echo "$remove_bed beds removed successfully.";
    } else {
        echo "Error removing bed: " . mysqli_error($conn);
    }
}
?>
