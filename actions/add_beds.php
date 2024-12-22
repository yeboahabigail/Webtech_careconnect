<?php
session_start();
include('../db/config.php');

header('Content-Type: application/json');

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$hospital_id = isset($_POST['hospital_id']) ? intval($_POST['hospital_id']) : 0;
$num_beds = isset($_POST['num_beds']) ? intval($_POST['num_beds']) : 0;

if ($hospital_id <= 0 || $num_beds <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid input parameters']);
    exit;
}

try {
    $conn->begin_transaction();

    // Get current hospital details
    $hospital_query = "SELECT name, num_beds FROM hospitals WHERE hospital_id = ?";
    $stmt = $conn->prepare($hospital_query);
    $stmt->bind_param("i", $hospital_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $hospital = $result->fetch_assoc();

    // Insert new beds
    $insert_beds = "INSERT INTO beds (hospital_id, is_available, status) VALUES (?, 1, 'available')";
    $stmt = $conn->prepare($insert_beds);
    
    for ($i = 0; $i < $num_beds; $i++) {
        $stmt->bind_param("i", $hospital_id);
        $stmt->execute();
    }

    // Update hospital bed count
    $update_hospital = "UPDATE hospitals 
                       SET num_beds = num_beds + ?,
                           available_beds = available_beds + ?
                       WHERE hospital_id = ?";
    $stmt = $conn->prepare($update_hospital);
    $stmt->bind_param("iii", $num_beds, $num_beds, $hospital_id);
    $stmt->execute();

    // Log the action
    $action_description = sprintf(
        "Added %d new beds to %s (Hospital ID: %d). Total beds now: %d",
        $num_beds,
        $hospital['name'],
        $hospital_id,
        $hospital['num_beds'] + $num_beds
    );
    
    $log_query = "INSERT INTO admin_logs (action_type, description, user_id, hospital_id, created_at) 
                  VALUES ('add_beds', ?, ?, ?, NOW())";
    $stmt = $conn->prepare($log_query);
    $stmt->bind_param("sii", $action_description, $_SESSION['user_id'], $hospital_id);
    $stmt->execute();

    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => sprintf('Successfully added %d beds to %s', $num_beds, $hospital['name'])
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error adding beds: ' . $e->getMessage()]);
}

$conn->close();
?>
