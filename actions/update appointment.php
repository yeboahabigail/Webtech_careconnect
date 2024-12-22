<?php
session_start();
require_once '../db/config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

$data = json_decode(file_get_contents('php://input'), true);
$appointment_id = $data['appointment_id'];
$status = $data['status'];

try {
    $stmt = $conn->prepare("UPDATE appointments SET status = ? WHERE appointment_id = ?");
    $stmt->bind_param("si", $status, $appointment_id);
    
    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode(['message' => 'Status updated successfully']);
    } else {
        throw new Exception($stmt->error);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>