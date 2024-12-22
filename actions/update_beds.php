<?php
session_start();
include('../db/config.php');

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id'])) {
    header('Location: ../view/Login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$role_query = $conn->prepare("SELECT role FROM users WHERE user_id = ?");
$role_query->bind_param("i", $user_id);
$role_query->execute();
$role_result = $role_query->get_result();
$user_role = $role_result->fetch_assoc();


if ($user_role['role_name'] !== 'admin') {
    header('Location: ../view/user_dashboard.php');
    exit();
}

// Function to log admin actions
function logAdminAction($conn, $action_type, $description) {
    $stmt = $conn->prepare("INSERT INTO adminactiontable (admin_action_type, action_discription, action_date) VALUES (?, ?, NOW())");
    $stmt->bind_param("ss", $action_type, $description);
    $stmt->execute();
}

// Handle bed management actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' && isset($_POST['add_bed'])) {
        $num_beds = intval($_POST['add_bed']);
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Get the highest bed number
            $max_bed_query = "SELECT MAX(bed_number) as max_num FROM beds";
            $max_result = $conn->query($max_bed_query);
            $max_bed = $max_result->fetch_assoc()['max_num'] ?? 0;
            
            // Add new beds
            $stmt = $conn->prepare("INSERT INTO beds (bed_number, is_available) VALUES (?, 1)");
            
            for ($i = 1; $i <= $num_beds; $i++) {
                $bed_number = $max_bed + $i;
                $stmt->bind_param("i", $bed_number);
                $stmt->execute();
            }
            
            // Update total beds in hospitals table
            $update_hospital = "UPDATE beds SET num_bed = num_bed + ?";
            $stmt = $conn->prepare($update_hospital);
            $stmt->bind_param("i", $num_beds);
            $stmt->execute();
            
            // Log the action
            $description = "Added $num_beds new bed(s) to the system";
            logAdminAction($conn, 'ADD_BEDS', $description);
            
            $conn->commit();
            $_SESSION['success_message'] = "Successfully added $num_beds new bed(s)";
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error_message'] = "Error adding beds: " . $e->getMessage();
        }
    }
    elseif ($action === 'damage' && isset($_POST['bed_id'])) {
        $bed_id = intval($_POST['bed_id']);
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Mark bed as damaged
            $stmt = $conn->prepare("UPDATE beds SET is_available = 0, status = 'damaged' WHERE bed_id = ?");
            $stmt->bind_param("i", $bed_id);
            
            if ($stmt->execute()) {
                // Log the action
                $description = "Marked bed #$bed_id as damaged";
                logAdminAction($conn, 'MARK_BED_DAMAGED', $description);
                
                $conn->commit();
                $_SESSION['success_message'] = "Bed marked as damaged successfully";
            } else {
                throw new Exception("Failed to mark bed as damaged");
            }
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error_message'] = "Error marking bed as damaged: " . $e->getMessage();
        }
    }
}

// Redirect back to admin dashboard
header('Location: ../view/admin_dashboard.php');
exit();
