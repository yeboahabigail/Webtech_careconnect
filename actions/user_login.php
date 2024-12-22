<?php
session_start();
// Include database connection
require_once('../db/config.php');
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    // $role = trim($_POST['role']); // Expect role ('user', 'admin', 'doctor') from the login form

    // Debug log
    error_log("Login attempt - Email: $email");

    //if ($role === 'doctor') {
    // Doctor login process

    $stmt = $conn->prepare(" SELECT user_id, fname, lname, role, password from users where email =?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {

            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['fname'] = $user['fname'];
            $_SESSION['role'] = $user['role'];

            // echo  'role: '.$user['role'];
            //checking whethere the user is an addmin

            // if (isset($_SESSION['user']) && $_SESSION['user']['role'] == 'admin') {
            //     // Redirect to admin dashboard or a specific admin page
            //     header('Location: admin_dashboard.php');
            //     exit(); // Make sure no further code is executed after the redirection
            // } 
            // // var_dump($user['role']);
            // exit();


            // Redirect logic based on role
            $redirect = match ($user['role']) {
                'doctor' => '../view/doctor_dashboard.php',
                'admin' => '../view/admin_dashboard.php',
                'user' => '../view/user_dashboard.php',
                default => ''
            };

            echo json_encode([
                'success' => true,
                'message' => 'Login successful',
                'role' => $user['role'],
                'redirect' => $redirect
            ]);
        }
    }
}