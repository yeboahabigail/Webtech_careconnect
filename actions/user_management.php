<?php
session_start();
include('../db/config.php');

// Ensure only logged-in doctors can access
if (!isset($_SESSION['doctor_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Function to validate input
function validateInput($input) {
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input);
    return $input;
}

// Handle different patient actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? validateInput($_POST['action']) : '';

    switch ($action) {
        case 'add':
            // Validate patient details
            $fname = isset($_POST['fname']) ? validateInput($_POST['fname']) : '';
            $lname = isset($_POST['lname']) ? validateInput($_POST['lname']) : '';
            $email = isset($_POST['email']) ? filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) : false;
            $phone = isset($_POST['phone']) ? validateInput($_POST['phone']) : '';

            // Server-side validation
            $errors = [];
            if (empty($fname)) $errors[] = 'First name is required';
            if (empty($lname)) $errors[] = 'Last name is required';
            if (!$email) $errors[] = 'Invalid email address';
            if (empty($phone)) $errors[] = 'Phone number is required';

            if (!empty($errors)) {
                http_response_code(400);
                echo json_encode(['errors' => $errors]);
                exit();
            }

            // Check if patient already exists
            $check_query = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
            $check_query->bind_param("s", $email);
            $check_query->execute();
            $check_result = $check_query->get_result();

            if ($check_result->num_rows > 0) {
                http_response_code(409);
                echo json_encode(['error' => 'Patient with this email already exists']);
                exit();
            }

            // Prepare insert statement
            $insert_query = $conn->prepare("INSERT INTO users (fname, lname, email, phone, created_by_doctor_id) VALUES (?, ?, ?, ?, ?)");
            $insert_query->bind_param("ssssi", 
                $fname, $lname, $email, $phone, $_SESSION['doctor_id']
            );

            if ($insert_query->execute()) {
                $new_patient_id = $conn->insert_id;
                echo json_encode([
                    'success' => true, 
                    'message' => 'Patient added successfully',
                    'patient_id' => $new_patient_id
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'error' => 'Failed to add patient',
                    'details' => $conn->error
                ]);
            }
            break;

        case 'list':
            // Fetch patients added by this doctor
            $list_query = $conn->prepare("SELECT user_id, fname, lname, email, phone FROM users WHERE created_by_doctor_id = ?");
            $list_query->bind_param("i", $_SESSION['doctor_id']);
            $list_query->execute();
            $result = $list_query->get_result();

            $patients = [];
            while ($patient = $result->fetch_assoc()) {
                $patients[] = $patient;
            }

            echo json_encode([
                'success' => true,
                'patients' => $patients
            ]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
}

$conn->close();
?>