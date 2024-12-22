<?php
session_start();
include('../db/config.php'); // Include your database configuration

// Assuming you already have the appointment details stored in the session
if (isset($_SESSION['appointment'])) {
    $appointment = $_SESSION['appointment'];
}

// Check the action from the form submission
if (isset($_POST['action'])) {
    if ($_POST['action'] == 'view_beds') {
        // Process to show available beds
        header('Location: beds_page.php'); // Redirect to available beds page
        exit;
    } elseif ($_POST['action'] == 'decline_beds') {
        // Process if the user declines viewing beds
        echo "You chose not to view available beds.";
    }
}

// Check if the logout button was clicked
if (isset($_POST['logout'])) {
    // Destroy session to log the user out
    session_destroy();
    header('Location: login.php'); // Redirect to the login page
    exit;
}
?>
