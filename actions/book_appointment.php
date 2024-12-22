<?php
session_start();
error_reporting(E_ALL);
ini_set('display_error',1);
include('../db/config.php'); 

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST' || $_SERVER['REQUEST_METHOD'] == 'GET') {
    // Ensure the user is logged in
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'You must be logged in to book an appointment.']);
        exit;
    }
     
    // Sanitize and validate inputs
    $user_id = $_SESSION['user_id'];
    $doctor_id = isset($_POST['doctor_id']) ? intval($_POST['doctor_id']) : 0;
    $appointment_date = isset($_POST['appointment_date']) ? $_POST['appointment_date'] : '';
    $appointment_time = isset($_POST['appointment_time']) ? $_POST['appointment_time'] : '';
    $concerns = isset($_POST['concerns']) ? $_POST['concerns'] : '';

    try {
        // First check if doctor exists
        $check_doctor = $conn->prepare("SELECT doctor_id FROM doctors WHERE doctor_id = ?");
        $check_doctor->bind_param("i", $doctor_id);
        $check_doctor->execute();
        $doctor_result = $check_doctor->get_result();

        if ($doctor_result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'This doctor is not available in our system. Please select a doctor from ID 11 to 20.']);
            exit;
        }

        // Only check other fields if doctor exists
        if (empty($appointment_date) || empty($appointment_time)) {
            echo json_encode(['success' => false, 'message' => 'Please provide both appointment date and time.']);
            exit;
        }

        // Rest of your existing code for checking pending appointments and double booking...
        // Check if the user already has a pending appointment
        $check_query = $conn->prepare("SELECT * FROM appointments WHERE user_id = ? AND status = 'pending'");
        $check_query->bind_param("i", $user_id);
        $check_query->execute();
        $check_result = $check_query->get_result();
       
        if ($check_result->num_rows > 0) {
            throw new Exception('You already have a pending appointment. Please cancel it first to book a new one.');
        }

        // Check for double booking
        $check_doctor_query = $conn->prepare("SELECT * FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? AND status != 'cancelled'");
        $check_doctor_query->bind_param("iss", $doctor_id, $appointment_date, $appointment_time);
        $check_doctor_query->execute();
        $doctor_result = $check_doctor_query->get_result();
       
        if ($doctor_result->num_rows > 0) {
            throw new Exception('This time slot is already booked. Please select another time.');
        }

        // Insert new appointment
        $current_time = date('Y-m-d H:i:s');
        $insert_query = $conn->prepare("
            INSERT INTO appointments (user_id, doctor_id, appointment_date, appointment_time, status, created_at)
            VALUES (?, ?, ?, ?, 'pending', ?)
        ");
        $insert_query->bind_param("iisss", $user_id, $doctor_id, $appointment_date, $appointment_time, $current_time);
        
        if (!$insert_query->execute()) {
            throw new Exception('Failed to book appointment: ' . $insert_query->error);
        }

        $appointment_id = $conn->insert_id;

        // Get appointment details
        $details_query = $conn->prepare("
            SELECT a.*, d.name as doctor_name, h.name as hospital_name
            FROM appointments a
            JOIN doctors d ON a.doctor_id = d.doctor_id
            JOIN hospitals h ON d.hospital_id = h.hospital_id
            WHERE a.appointment_id = ?");
        $details_query->bind_param("i", $appointment_id);
        $details_query->execute();
        $details = $details_query->get_result()->fetch_assoc();

        if (!$details) {
            throw new Exception('No appointment details found.');
        }

        // Store appointment details in session for confirmation page
        $_SESSION['last_appointment'] = [
            'doctor_name' => $details['doctor_name'],
            'hospital_name' => $details['hospital_name'],
            'date' => $details['appointment_date'],
            'time' => $details['appointment_time'],
            'status' => $details['status']
        ];

        echo json_encode([
            'success' => true,
            'message' => 'Appointment booked successfully!',
            'appointment' => [
                'id' => $appointment_id,
                'doctor_name' => $details['doctor_name'],
                'hospital_name' => $details['hospital_name'],
                'date' => $details['appointment_date'],
                'time' => $details['appointment_time'],
                'status' => $details['status']
            ],
            'redirect' => '../view/confirmation.php'
        ]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}