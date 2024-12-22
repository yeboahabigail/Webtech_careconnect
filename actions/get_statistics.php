<?php
session_start();
include('../db/config.php');

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Not authorized']);
    exit();
}

// Verify admin role
$user_id = $_SESSION['user_id'];
$query = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();
$user_data = $result->fetch_assoc();

if (!$user_data || $user_data['role'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Not authorized']);
    exit();
}

// Get all statistics
try {
    // Total users (excluding admins)
    $total_users_query = "SELECT COUNT(*) as total FROM users WHERE role != 'admin'";
    $total_users_result = $conn->query($total_users_query);
    $total_users = $total_users_result->fetch_assoc()['total'];

    // Bed statistics
    $beds_query = "SELECT 
        COUNT(*) as total_beds,
        SUM(CASE WHEN is_available = 1 THEN 1 ELSE 0 END) as available_beds,
        SUM(CASE WHEN status = 'damaged' THEN 1 ELSE 0 END) as damaged_beds
    FROM beds";
    $beds_result = $conn->query($beds_query);
    $beds_stats = $beds_result->fetch_assoc();

    // Appointment statistics
    $appointment_stats_query = "SELECT 
        COUNT(*) as total_appointments,
        SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
        SUM(CASE WHEN status = 'rescheduled' THEN 1 ELSE 0 END) as rescheduled
    FROM appointments";
    $appointment_stats_result = $conn->query($appointment_stats_query);
    $appointment_stats = $appointment_stats_result->fetch_assoc();

    // Prepare response
    $response = [
        'total_users' => $total_users,
        'bed_stats' => [
            'total' => $beds_stats['total_beds'],
            'available' => $beds_stats['available_beds'],
            'damaged' => $beds_stats['damaged_beds'],
            'occupied' => $beds_stats['total_beds'] - $beds_stats['available_beds'] - $beds_stats['damaged_beds']
        ],
        'appointment_stats' => [
            'total' => $appointment_stats['total_appointments'],
            'confirmed' => $appointment_stats['confirmed'],
            'cancelled' => $appointment_stats['cancelled'],
            'rescheduled' => $appointment_stats['rescheduled']
        ]
    ];

    header('Content-Type: application/json');
    echo json_encode($response);
} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => $e->getMessage()]);
}
