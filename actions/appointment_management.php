<?php
session_start();
include('../db/config.php');

// Ensure only logged-in doctors can access
if (!isset($_SESSION['doctor_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Function to validate input
function validateInput($input) {
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input);
    return $input;
}

// Handle different appointment actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? validateInput($_POST['action']) : '';
    $appointment_id = isset($_POST['appointment_id']) ? intval($_POST['appointment_id']) : 0;

    switch ($action) {
        case 'modify':
            // Validate and modify appointment
            $new_date = isset($_POST['date']) ? validateInput($_POST['date']) : '';
            $new_time = isset($_POST['time']) ? validateInput($_POST['time']) : '';
            $new_status = isset($_POST['status']) ? validateInput($_POST['status']) : '';

            // Server-side validation
            if (empty($new_date) || empty($new_time) || empty($new_status)) {
                http_response_code(400);
                echo json_encode(['error' => 'All fields are required']);
                exit();
            }

            // Prepare update statement
            $update_query = $conn->prepare("UPDATE appointments 
                SET appointment_date = ?, appointment_time = ?, status = ? 
                WHERE appointment_id = ? AND doctor_id = ?");
            $update_query->bind_param("sssii", 
                $new_date, $new_time, $new_status, 
                $appointment_id, $_SESSION['doctor_id']
            );

            if ($update_query->execute()) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Appointment updated successfully'
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'error' => 'Failed to update appointment',
                    'details' => $conn->error
                ]);
            }
            break;

        case 'delete':
            // Prepare delete statement
            $delete_query = $conn->prepare("DELETE FROM appointments 
                WHERE appointment_id = ? AND doctor_id = ?");
            $delete_query->bind_param("ii", 
                $appointment_id, $_SESSION['doctor_id']
            );

            if ($delete_query->execute()) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Appointment deleted successfully'
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'error' => 'Failed to delete appointment',
                    'details' => $conn->error
                ]);
            }
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
}

$conn->close();
?>