<?php
session_start();
include('../db/config.php');

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $appointment_id = isset($_POST['appointment_id']) ? intval($_POST['appointment_id']) : 0;
    $user_id = $_SESSION['user_id'];

    if (!$appointment_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid appointment ID']);
        exit();
    }

    // Update appointment status to cancelled
    $update_query = "UPDATE appointments SET status = 'cancelled' WHERE appointment_id = ? AND user_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ii", $appointment_id, $user_id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Appointment cancelled successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Appointment not found or already cancelled'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $conn->error
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
