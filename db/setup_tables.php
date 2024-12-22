<?php
include('config.php');

// Read and execute the SQL file
$sql = file_get_contents('create_roles_tables.sql');

if ($conn->multi_query($sql)) {
    do {
        // Store first result set
        if ($result = $conn->store_result()) {
            $result->free();
        }
        // Prepare next result set
    } while ($conn->more_results() && $conn->next_result());
    
    echo "Tables created successfully!\n";
    echo "\nAdmin account credentials:\n";
    echo "Email: admin@hospital.com\n";
    echo "Password: admin123\n";
} else {
    echo "Error creating tables: " . $conn->error;
}

$conn->close();
