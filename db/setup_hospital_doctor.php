<?php
include('config.php');

// Read and execute the SQL file
$sql = file_get_contents('setup_hospital_doctor.sql');

if ($conn->multi_query($sql)) {
    do {
        // Store first result set
        if ($result = $conn->store_result()) {
            $result->free();
        }
        // Prepare next result set
    } while ($conn->more_results() && $conn->next_result());
    
    echo "Hospital and doctor data setup successfully!\n";
} else {
    echo "Error setting up hospital and doctor data: " . $conn->error;
}

$conn->close();
?>
