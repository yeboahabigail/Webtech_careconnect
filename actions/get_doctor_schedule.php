<?php
session_start();
include('../db/config.php');

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

//| !isset($_GET['schedule'])
if (!isset($_GET['doctor_id'])) {
    echo json_encode(['success' => false, 'message' => 'Doctor ID and date are required']);
    exit;
}

$doctor_id = intval($_GET['doctor_id']);
$date = $_GET['date'] ?? date('Y-m-d'); // Use current date if not provided

try {
    // First, check if the doctor is available on this date
    $doctor_query = "SELECT available_1, schedule FROM doctors WHERE doctor_id = ?";
    $stmt = $conn->prepare($doctor_query);
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $doctor = $result->fetch_assoc();

    if (!$doctor['available_1']) {
        echo json_encode(['success' => false, 'message' => 'Doctor is not available for appointments']);
        exit;
    }

    // if($doctor['available_1']){
     // Decode the JSON schedule
     $schedule = json_encode($doctor['schedule'], true);
     // Extract unique days and times
    $unique_days = [];
    $unique_times = [];


    // Process schedule to get unique days and times
    if ($schedule && is_array($schedule)) {
        foreach ($schedule as $slot) {
            // Assuming each schedule item has 'day' and 'time' keys
            if (isset($slot['day']) && !in_array($slot['day'], $unique_days)) {
                $unique_days[] = $slot['day'];
            }
            
            if (isset($slot['time']) && !in_array($slot['time'], $unique_times)) {
                $unique_times[] = $slot['time'];
            }
        }
    }



        // echo json_encode([
        //     'success' => true,
        //     'schedule' => $schedule,
        //     'available_slots' => $available_slots
        // ])
    // }

    // Get all booked appointments for this doctor on this date
    $appointments_query = "SELECT appointment_time 
                         FROM appointments 
                         WHERE doctor_id = ? 
                         AND appointment_date = ? 
                         AND status != 'cancelled'";
    $stmt = $conn->prepare($appointments_query);
    $stmt->bind_param("is", $doctor_id, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $booked_times = [];
    while ($row = $result->fetch_assoc()) {
        $booked_times[] = $row['appointment_time'];
    }

    

    // Return JSON response
    echo json_encode([
        'success' => true,
        'days' => $unique_days,
        'times' => $unique_times,
        'booked_times' => $booked_times,
        'schedule' => $schedule
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching doctor schedule: ' . $e->getMessage()
    ]);
}
?>
