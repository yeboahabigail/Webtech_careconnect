<?php
include('config.php');

// Read and execute the SQL file
$sql = file_get_contents('create_appointments_table.sql');

if ($conn->multi_query($sql)) {
    echo "Appointments table created successfully!\n";
} else {
    echo "Error creating appointments table: " . $conn->error;
}

$conn->close();
