<?php
session_start();
// Set content type to JSON for consistent API-like responses
header('Content-Type: application/json');

// Database connection parameters
include('../db/config.php');

// Function to sanitize and validate input
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Response function
function sendResponse($success, $message, $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

// Check if the request is a POST request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect and sanitize form data
    $first_name = sanitizeInput($_POST['fname'] ?? '');
    $last_name = sanitizeInput($_POST['lname'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate input fields
    $errors = [];

    // Validate first name
    if (empty($first_name)) {
        $errors[] = "First name is required";
    } elseif (strlen($first_name) < 2 || strlen($first_name) > 50) {
        $errors[] = "First name must be between 2 and 50 characters";
    }

    // Validate last name
    if (empty($last_name)) {
        $errors[] = "Last name is required";
    } elseif (strlen($last_name) < 2 || strlen($last_name) > 50) {
        $errors[] = "Last name must be between 2 and 50 characters";
    }

    // Validate email
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }

    // Validate phone number (optional, but validate if provided)
    if (!empty($phone) && !preg_match("/^[0-9]{10}$/", $phone)) {
        $errors[] = "Invalid phone number. Please use 10 digits.";
    }

    // Validate password
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }

    // If there are validation errors, return them
    if (!empty($errors)) {
        sendResponse(false, "Validation failed", $errors);
    }

    // // Create database connection
    // $conn = new mysqli($servername, $username, $password, $dbname);

    // // Check connection
    // if ($conn->connect_error) {
    //     sendResponse(false, "Database connection failed: " . $conn->connect_error);
    // }

    // Check if email already exists
    $check_email_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check_email_stmt->bind_param("s", $email);
    $check_email_stmt->execute();
    $check_email_result = $check_email_stmt->get_result();

    if ($check_email_result->num_rows > 0) {
        $check_email_stmt->close();
        $conn->close();
        sendResponse(false, "Email already registered");
    }
    $check_email_stmt->close();

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Prepare SQL statement to insert user
    $stmt = $conn->prepare("INSERT INTO users (fname, lname, email, password, role) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    
    // Default role (1 for regular user, 2 for admin)
    $default_role = 1;

    // Bind parameters
    $stmt->bind_param("sssss", $first_name, $last_name, $email, $phone, $hashed_password, $default_role);

    // Execute the statement
    try {
        $result = $stmt->execute();

        if ($result) {
            // Get the ID of the newly inserted user
            $new_user_id = $conn->insert_id;

            // Close statements and connection
            $stmt->close();
            $conn->close();

            // Send successful registration response
            sendResponse(true, "Registration successful", [
                'user_id' => $new_user_id,
                'fname' => $first_name,
                'email' => $email
            ]);
        } else {
            // Registration failed
            sendResponse(false, "Registration failed: " . $stmt->error);
        }
    } catch (Exception $e) {
        sendResponse(false, "Registration error: " . $e->getMessage());
    }
} else {
    // If not a POST request
    sendResponse(false, "Invalid request method");
}
?>