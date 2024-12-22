<?php
session_start();
include('../db/config.php');

// At the top of book_bed.php, after session_start():
header('Content-Type: application/json');

// Add debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log the incoming request
error_log('POST data received: ' . print_r($_POST, true));

// Validate hospital_id more thoroughly
if (!isset($_POST['hospital_id'])) {
    echo json_encode(['success' => false, 'message' => 'Hospital ID not provided']);
    exit;
}

$hospital_id = filter_var($_POST['hospital_id'], FILTER_VALIDATE_INT);
if ($hospital_id === false || $hospital_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid hospital ID: ' . $_POST['hospital_id']]);
    exit;
}






header('Content-Type: application/json');

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
// $hospital_id = isset($_POST['hospital_id']) ? intval($_POST['hospital_id']) : 0;

// Check if hospital ID is valid
// if ($hospital_id <= 0) {
//     echo json_encode(['success' => false, 'message' => 'Invalid hospital ID']);
//     exit;
// }

try {
    // Start transaction
    $conn->begin_transaction();

    // Check if logged-in user is a valid user (not an admin or doctor)
    $user_check_query = "SELECT role FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($user_check_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'User does not exist']);
        exit;
    }

    $user = $result->fetch_assoc();
    
    // Ensure the logged-in user is not an admin or doctor
    if ($user['role'] != 'user') {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Only users can book beds']);
        exit;
    }

    // Check if user has an appointment at this hospital
    $appointment_query = "SELECT a.appointment_id, d.doctor_id, d.name as doctor_name 
                         FROM appointments a 
                         JOIN doctors d ON a.doctor_id = d.doctor_id 
                         WHERE a.user_id = ? 
                         AND d.hospital_id = ? 
                         AND a.status = 'pending'";
    $stmt = $conn->prepare($appointment_query);
    $stmt->bind_param("ii", $user_id, $hospital_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $conn->rollback();
        echo json_encode([ 'success' => false, 'message' => 'You can only book a bed at the hospital where you have an appointment.' ]);
        exit;
    }

    $appointment = $result->fetch_assoc();

    // Check if user already has a bed booked
    $check_query = "SELECT b.bed_id, h.name as hospital_name 
                   FROM bed_bookings bb 
                   JOIN beds b ON bb.bed_id = b.bed_id 
                   JOIN hospitals h ON b.hospital_id = h.hospital_id 
                   WHERE bb.user_id = ? AND bb.status = 'active'";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $booking = $result->fetch_assoc();
        $conn->rollback();
        echo json_encode([ 'success' => false, 'message' => 'You already have a bed booked at ' . $booking['hospital_name'] ]);
        exit;
    }

    // Get an available bed from the selected hospital
    $bed_query = "SELECT b.bed_id, b.num_bed FROM beds b
                  JOIN hospitals h ON b.hospital_id = h.hospital_id
                  WHERE b.hospital_id = ? AND b.num_bed > 0 AND b.status != 'marked_damage'
                  LIMIT 1";
    $stmt = $conn->prepare($bed_query);
    $stmt->bind_param("i", $hospital_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'No beds available at this hospital']);
        exit;
    }

    $bed = $result->fetch_assoc();
    $bed_id = $bed['bed_id'];

    // Decrement the available bed count in the `beds` table
    $update_bed = "UPDATE beds SET num_bed = num_bed - 1 WHERE bed_id = ? AND num_bed > 0";
    $stmt = $conn->prepare($update_bed);
    $stmt->bind_param("i", $bed_id);
    $stmt->execute();

    // Create the bed booking record
    $hospital_query = "SELECT hospital_id FROM beds WHERE bed_id = ?";
    $stmt = $conn->prepare($hospital_query);
    $stmt->bind_param("i", $bed_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Hospital not found for the selected bed']);
        exit;
    }

    $hospital = $result->fetch_assoc();
    $hospital_id = $hospital['hospital_id'];

    $booking_query = "INSERT INTO bed_bookings (user_id, bed_id, hospital_id, booking_date, status) 
                      VALUES (?, ?, ?, NOW(), 'active')";
    $stmt = $conn->prepare($booking_query);
    $stmt->bind_param("iii", $user_id, $bed_id, $hospital_id);
    $stmt->execute();
    $booking_id = $conn->insert_id;

    // Log the action for admin
    $action_description = sprintf(
        "Bed #%d booked by User #%d with Doctor %s (ID: %d) at Hospital ID: %d",
        $bed_id,
        $user_id,
        $appointment['doctor_name'],
        $appointment['doctor_id'],
        $hospital_id
    );

    $log_query = "INSERT INTO adminactions (action_type, action_description, hospital_id, action_date) 
                  VALUES ('create',?, ?,NOW())";
    $stmt = $conn->prepare($log_query);
    $stmt->bind_param("si", $action_description, $hospital_id);
    $stmt->execute();

    // Get hospital details for confirmation
    $hospital_query = "SELECT h.name, SUM(b.num_bed) AS total_beds 
                       FROM hospitals h
                       JOIN beds b ON h.hospital_id = b.hospital_id
                       WHERE h.hospital_id = ?
                       GROUP BY h.hospital_id";
    $stmt = $conn->prepare($hospital_query);
    $stmt->bind_param("i", $hospital_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $hospital = $result->fetch_assoc();

//     // Store booking details in session for confirmation page
    $_SESSION['bed_booking'] = [
        'hospital_name' => $hospital['name'],
        'bed_id' => $bed_id,
        'doctor_name' => $appointment['doctor_name'],
        'booking_date' => date('Y-m-d H:i:s'),
        'status' => 'active',
        'total_beds' => $hospital['total_beds']
    ];

    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Bed booked successfully!',
        'redirect' => '../view/bed_confirmation.php'
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error booking bed: ' . $e->getMessage()]);
}

$conn->close();
?>