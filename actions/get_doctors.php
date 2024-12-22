<?php
session_start();
include('../db/config.php');

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

if (!isset($_GET['hospital_id'])) {
    echo json_encode(['success' => false, 'message' => 'Hospital ID is required']);
    exit;
}

$hospital_id = intval($_GET['hospital_id']);

try {
    // First get hospital info
    $hospital_query = "SELECT * FROM hospitals WHERE hospital_id = ?";
    $stmt = $conn->prepare($hospital_query);
    $stmt->bind_param("i", $hospital_id);
    $stmt->execute();
    $hospital_result = $stmt->get_result();
    $hospital = $hospital_result->fetch_assoc();

    // Debug log
    error_log("Looking for hospital with ID: " . $hospital_id);
    error_log("Hospital data: " . json_encode($hospital));

    if (!$hospital) {
        throw new Exception('Hospital not found with ID: ' . $hospital_id);
    }

    // Get doctors for the selected hospital
    $query = "SELECT 
                d.*,
                h.name as hospital_name,
                h.location as hospital_location,
                COUNT(a.appointment_id) as appointment_count
              FROM doctors d
              JOIN hospitals h ON d.hospital_id = h.hospital_id
              LEFT JOIN appointments a ON d.doctor_id = a.doctor_id 
                AND a.appointment_date >= CURDATE() 
                AND a.status = 'pending'
              WHERE d.hospital_id = ?
              GROUP BY d.doctor_id, d.name, d.specialty, h.name, h.location
              ORDER BY d.name";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $hospital_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Debug log
    error_log("SQL Query: " . $query);
    error_log("Hospital ID in query: " . $hospital_id);

    $doctors = [];
    while ($doctor = $result->fetch_assoc()) {
        // Debug log each doctor
        error_log("Found doctor: " . json_encode($doctor));
        $doctors[] = [
            'doctor_id' => $doctor['doctor_id'],
            'name' => $doctor['name'],
            'specialty' => $doctor['specialty'],
            'hospital_name' => $doctor['hospital_name'],
            'available' => ($doctor['appointment_count'] < 10)
        ];
    }

    // Log the results
    error_log("Hospital ID: " . $hospital_id);
    error_log("Hospital name: " . ($hospital['name'] ?? 'Not found'));
    error_log("Number of doctors found: " . count($doctors));
    
    echo json_encode([
        'success' => true,
        'doctors' => $doctors,
        'hospital_name' => $hospital['name'],
        'debug_info' => [
            'hospital_id' => $hospital_id,
            'hospital_data' => $hospital,
            'doctor_count' => count($doctors),
            'raw_sql' => $query
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_doctors.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching doctors: ' . $e->getMessage(),
        'debug_info' => [
            'hospital_id' => $hospital_id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
}
