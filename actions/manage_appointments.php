<?php
session_start();
include('../db/config.php');

header('Content-Type: application/json');

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 3) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$action = $_POST['action'] ?? '';
$appointment_id = isset($_POST['appointment_id']) ? intval($_POST['appointment_id']) : 0;

if (!$appointment_id) {
    echo json_encode(['success' => false, 'message' => 'Missing appointment ID']);
    exit;
}

try {
    $conn->begin_transaction();

    switch ($action) {
        case 'complete':
            // Mark appointment as completed
            $update_query = "UPDATE appointments SET status = 'completed' WHERE appointment_id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("i", $appointment_id);
            $stmt->execute();

            // Log the action
            $log_query = "INSERT INTO admin_actions (admin_id, action_type, action_description) 
                         VALUES (?, 'complete_appointment', 'Marked appointment #" . $appointment_id . " as completed')";
            $stmt = $conn->prepare($log_query);
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();

            // Update appointment status
            $status_query = "INSERT INTO appointment_status (appointment_id, status) 
                           VALUES (?, 'completed')";
            $stmt = $conn->prepare($status_query);
            $stmt->bind_param("i", $appointment_id);
            $stmt->execute();
            break;

        case 'remove':
            // Get appointment details for logging
            $details_query = "SELECT user_id, doctor_id FROM appointments WHERE appointment_id = ?";
            $stmt = $conn->prepare($details_query);
            $stmt->bind_param("i", $appointment_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $appointment = $result->fetch_assoc();

            // Remove the appointment
            $delete_query = "DELETE FROM appointments WHERE appointment_id = ? AND status = 'completed'";
            $stmt = $conn->prepare($delete_query);
            $stmt->bind_param("i", $appointment_id);
            $stmt->execute();

            if ($stmt->affected_rows === 0) {
                throw new Exception('Can only remove completed appointments');
            }

            // Log the action
            $log_query = "INSERT INTO admin_actions (admin_id, action_type, action_description) 
                         VALUES (?, 'remove_appointment', 'Removed completed appointment #" . $appointment_id . "')";
            $stmt = $conn->prepare($log_query);
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            break;

        default:
            throw new Exception('Invalid action');
    }

    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => $action === 'complete' ? 
                    'Appointment marked as completed' : 
                    'Appointment removed successfully'
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
