<?php
session_start();
require_once('../db/config.php');
header('Content-Type: application/json');

// Verify admin access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['action']) && $_GET['action'] === 'get_appointment') {
                $stmt = $conn->prepare("SELECT * FROM appointments WHERE appointment_id = ?");
                $stmt->bind_param("i", $_GET['id']);
                $stmt->execute();
                $result = $stmt->get_result();
                echo json_encode(['success' => true, 'appointment' => $result->fetch_assoc()]);
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            $stmt = $conn->prepare("INSERT INTO appointments (user_id, doctor_id, appointment_date, appointment_time, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iisss", $data['patient_id'], $data['doctor_id'], $data['appointment_date'], $data['appointment_time'], $data['status']);
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => 'Appointment created successfully']);
            break;

        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);
            $stmt = $conn->prepare("UPDATE appointments SET user_id = ?, doctor_id = ?, appointment_date = ?, appointment_time = ?, status = ? WHERE appointment_id = ?");
            $stmt->bind_param("iisssi", $data['patient_id'], $data['doctor_id'], $data['appointment_date'], $data['appointment_time'], $data['status'], $data['id']);
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => 'Appointment updated successfully']);
            break;

        case 'DELETE':
            $data = json_decode(file_get_contents('php://input'), true);
            $stmt = $conn->prepare("DELETE FROM appointments WHERE appointment_id = ?");
            $stmt->bind_param("i", $data['id']);
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => 'Appointment deleted successfully']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}