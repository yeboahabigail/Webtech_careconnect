<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Database connection
$host = "localhost";
$dbname = "your_database_name";
$username = "your_database_user";
$password = "your_database_password";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);

    // Validate input
    $requiredFields = ['doctor_id', 'patient_name', 'patient_email', 'appointment_date', 'appointment_time'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            throw new Exception("Missing or empty $field");
        }
    }

    // Check doctor availability
    $checkStmt = $conn->prepare("SELECT schedule FROM doctors WHERE doctor_id = ? AND available = TRUE");
    $checkStmt->execute([$data['doctor_id']]);
    $doctorSchedule = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$doctorSchedule) {
        throw new Exception("Doctor not available");
    }

    // Insert appointment
    $stmt = $conn->prepare("INSERT INTO appointments 
        (doctor_id, patient_name, patient_email, appointment_date, appointment_time, status) 
        VALUES (:doctor_id, :patient_name, :patient_email, :appointment_date, :appointment_time, 'pending')");
    
    $stmt->execute([
        ':doctor_id' => $data['doctor_id'],
        ':patient_name' => $data['patient_name'],
        ':patient_email' => $data['patient_email'],
        ':appointment_date' => $data['appointment_date'],
        ':appointment_time' => $data['appointment_time']
    ]);

    echo json_encode([
        'success' => true, 
        'message' => 'Appointment booked successfully',
        'appointment_id' => $conn->lastInsertId()
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage()
    ]);
}
?>