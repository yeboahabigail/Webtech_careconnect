<?php
session_start(); // Start the session
include('../db/config.php');

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "User not logged in. Please log in.";
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch the user's role
$query = $conn->prepare("SELECT role FROM users WHERE user_id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $role = $row['role']; // Assign role
} else {
    echo "Role not found for the logged-in user.";
    exit;
}
?>
