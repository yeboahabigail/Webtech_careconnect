<?php
// API endpoint for updating booking status
session_start();
require_once('../db/config.php');
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['booking_id']) || !isset($data['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$booking_id = $data['booking_id'];
$status = $data['status'];

$query = "UPDATE bed_bookings SET status = ? WHERE booking_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("si", $status, $booking_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update booking']);
}
?>
