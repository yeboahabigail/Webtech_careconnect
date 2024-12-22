<?php
// Database connection
$conn = new mysqli("localhost", "your_database_user", "your_database_password", "your_database_name");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch available beds
$sql = "SELECT * FROM beds WHERE is_available = 1";
$result = $conn->query($sql);

$bedData = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $bedData[] = $row;
    }
}
$conn->close();

// Return JSON for easier usage in JavaScript
header('Content-Type: application/json');
echo json_encode($bedData);
?>
