<?php
session_start();
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once('../db/config.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Debug log
    error_log("Login attempt - Email: " . $email);

    // Check if email exists and get user role
    $stmt = $conn->prepare("SELECT user_id, fname, lname, email, password, role FROM users WHERE email = ?");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        die(json_encode([
            'success' => false,
            'message' => 'Database error: ' . $conn->error
        ]));
    }

    $stmt->bind_param("s", $email);
    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        die(json_encode([
            'success' => false,
            'message' => 'Query error: ' . $stmt->error
        ]));
    }

    $result = $stmt->get_result();
    error_log("Number of rows found: " . $result->num_rows);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        error_log("User found: " . print_r($user, true));
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            error_log("Password verified successfully");
            
            // Set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['fname'] = $user['fname'];
            $_SESSION['role'] = $user['role'];

            // Determine redirect URL based on role
            $redirect_url = '';
            
            // Case-insensitive role comparison
            $role = strtolower($user['role']);
            error_log("User role: " . $role);

            if ($role === 'admin') {
                $redirect_url = '../view/admin_dashboard.php';
            } else if ($role === 'doctor') {
                $redirect_url = '../view/doctor_dashboard.php';
            } else {
                $redirect_url = '../view/user_dashboard.php';
            }

            echo json_encode([
                'success' => true,
                'message' => 'Login successful',
                'role' => $user['role'],
                'redirect' => $redirect_url
            ]);
        } else {
            error_log("Password verification failed");
            echo json_encode([
                'success' => false,
                'message' => 'Invalid email or password'
            ]);
        }
    } else {
        error_log("No user found with email: " . $email);
        echo json_encode([
            'success' => false,
            'message' => 'No account found with this email'
        ]);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?>
