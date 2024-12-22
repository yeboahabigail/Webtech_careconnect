<?php

session_start();

require_once('../db/config.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if (isset($_POST['damaged_beds'],$_POST['total_beds'], $_POST['id'])) {

        $hospitalId = $_POST['id'];
        $totalBeds = filter_var($_POST['total_beds'], FILTER_VALIDATE_INT);
        $damagedBeds = filter_var($_POST['damaged_beds'], FILTER_VALIDATE_INT);

        $query = "UPDATE beds SET num_bed = ?, status= ? WHERE hospital_id = ?";

        if ($stmt = $conn->prepare($query)) {
        
            $stmt->bind_param("iii", $totalBeds, $damagedBeds, $hospitalId);

            // Execute the statement
            if ($stmt->execute()) {
                
                if ($stmt->affected_rows > 0) {
                    
                    header('Location: ../view/admin_dashboard.php?status=success');
                    exit();
                } else {
                    
                    echo "Error: No changes were made or invalid hospital ID.";
                }
            } else {
                
                echo "Error: Unable to execute the query.";
            }

            
            $stmt->close();
        } else {
            
            echo "Error: Unable to prepare the query.";
        }
    } else {

        $a = $_POST['damaged_beds'];
        $b = $_POST['total_beds'];
        $c = $_POST['id'];

        echo "$c";
        echo "eddy";
        echo "Error: Missing required form data.";
    }
} else {
    // Redirect if the form is not submitted via POST
    header('Location: ../view/admin_dashboard.php');
    exit();
}

// Close the database connection
$conn->close();
?>
