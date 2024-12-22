<?php
// Database connection
$conn = new mysqli("localhost", "your_database_user", "your_database_password", "your_database_name");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch doctors with specialties
$sql = "SELECT doctor_id, name, speciality, availability FROM doctors";
$result = $conn->query($sql);

// Generate doctor options
$options = "";
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $options .= "<option value='" . $row['doctor_id'] . "'>" 
                  . $row['name'] . " (" . $row['speciality'] 
                  . " - Available: " . $row['availability'] . ")</option>";
    }
} else {
    $options = "<option>No doctors available</option>";
}

$conn->close();
echo $options; // Echo the options back to the frontend
?>


