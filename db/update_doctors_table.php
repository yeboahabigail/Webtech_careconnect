<?php
include('config.php');

// Read and execute the SQL file
$sql = file_get_contents('update_doctors_schedule.sql');

if ($conn->multi_query($sql)) {
    echo "Doctors table updated successfully with schedule field!\n";
} else {
    echo "Error updating doctors table: " . $conn->error;
}

$conn->close();
?>
