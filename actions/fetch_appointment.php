<?php
// Database connection
$host = "localhost";
$dbname = "your_database_name";
$username = "your_database_user";
$password = "your_database_password";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch appointments
    $stmt = $conn->prepare("SELECT * FROM appointment ORDER BY appointment_date, appointment_time");
    $stmt->execute();
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($appointments);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
