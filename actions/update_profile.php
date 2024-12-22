<?php
session_start();
include('../db/config.php');

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
$response = ['success' => false, 'message' => ''];

// Get form data
$fname = trim($_POST['fname'] ?? '');
$lname = trim($_POST['lname'] ?? '');
$email = trim($_POST['email'] ?? '');
$new_password = trim($_POST['new_password'] ?? '');

// Validate inputs
if (empty($fname) || empty($lname) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'All fields except password are required']);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit();
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Check if email exists for another user
    $email_check = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
    $email_check->bind_param("si", $email, $user_id);
    $email_check->execute();
    $result = $email_check->get_result();

    if ($result->num_rows > 0) {
        throw new Exception('Email already exists');
    }

    // Prepare base update query
    $update_fields = "fname = ?, lname = ?, email = ?";
    $param_types = "sss";
    $params = [$fname, $lname, $email];

    // Add password to update if provided
    if (!empty($new_password)) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_fields .= ", password = ?";
        $param_types .= "s";
        $params[] = $hashed_password;
    }

    // Add user_id to parameters
    $param_types .= "i";
    $params[] = $user_id;

    // Prepare and execute update query
    $update_query = $conn->prepare("UPDATE users SET $update_fields WHERE user_id = ?");
    $update_query->bind_param($param_types, ...$params);
    
    if (!$update_query->execute()) {
        throw new Exception('Failed to update profile');
    }

    // Commit transaction
    $conn->commit();
    
    // Update session data
    $_SESSION['fname'] = $fname;
    
    echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
