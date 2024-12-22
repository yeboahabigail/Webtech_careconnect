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

$admin_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';
$hospital_id = isset($_POST['hospital_id']) ? intval($_POST['hospital_id']) : 0;
$bed_count = isset($_POST['bed_count']) ? intval($_POST['bed_count']) : 0;
$reason = $_POST['reason'] ?? '';

if ($action === 'other' && isset($_POST['other_reason'])) {
    $reason = $_POST['other_reason'];
}

if (!$hospital_id || !$bed_count || !$reason) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    $conn->begin_transaction();

    // Get current bed count
    $current_query = "SELECT num_beds FROM hospitals WHERE hospital_id = ?";
    $stmt = $conn->prepare($current_query);
    $stmt->bind_param("i", $hospital_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $current_beds = $result->fetch_assoc()['num_beds'];

    // Calculate new bed count
    $new_beds = $action === 'add' ? 
                $current_beds + $bed_count : 
                $current_beds - $bed_count;

    // Ensure we don't go below 0 beds
    if ($new_beds < 0) {
        throw new Exception('Cannot remove more beds than available');
    }

    // Update hospital bed count
    $update_query = "UPDATE hospitals SET num_beds = ? WHERE hospital_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ii", $new_beds, $hospital_id);
    $stmt->execute();

    // Log the action
    $log_query = "INSERT INTO bed_management (hospital_id, action_type, bed_count, reason, performed_by) 
                  VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($log_query);
    $stmt->bind_param("isisi", $hospital_id, $action, $bed_count, $reason, $admin_id);
    $stmt->execute();

    // If removing beds, update bed availability
    if ($action === 'remove') {
        // Mark the specified number of beds as unavailable
        $update_beds_query = "UPDATE beds 
                            SET is_available = 0 
                            WHERE hospital_id = ? 
                            AND is_available = 1 
                            LIMIT ?";
        $stmt = $conn->prepare($update_beds_query);
        $stmt->bind_param("ii", $hospital_id, $bed_count);
        $stmt->execute();
    } else {
        // Add new bed records
        $add_beds_query = "INSERT INTO beds (hospital_id, is_available) VALUES (?, 1)";
        $stmt = $conn->prepare($add_beds_query);
        for ($i = 0; $i < $bed_count; $i++) {
            $stmt->bind_param("i", $hospital_id);
            $stmt->execute();
        }
    }

    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => ($action === 'add' ? 'Added' : 'Removed') . " $bed_count beds successfully",
        'new_total' => $new_beds
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
