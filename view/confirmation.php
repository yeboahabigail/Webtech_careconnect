<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Check if there are appointment details in session
if (!isset($_SESSION['last_appointment'])) {
    header('Location: user_dashboard.php');
    exit;
}

$appointment = $_SESSION['last_appointment'];
// Clear the appointment details from session after displaying
unset($_SESSION['last_appointment']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Confirmation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .confirmation-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            background-color: #fff;
        }
        .success-icon {
            color: #28a745;
            font-size: 48px;
            margin-bottom: 20px;
        }
        .detail-row {
            margin-bottom: 15px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .detail-label {
            font-weight: bold;
            color: #6c757d;
        }
        .action-buttons {
            margin-top: 30px;
        }
        .action-buttons .btn {
            margin: 0 10px;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container">
        <div class="confirmation-container">
            <div class="text-center">
                <div class="success-icon">✓</div>
                <h2 class="mb-4">Appointment Confirmed!</h2>
                <p class="text-muted mb-4">Your appointment has been successfully booked. Here are your appointment details:</p>
            </div>

            <div class="appointment-details">
                <div class="detail-row">
                    <span class="detail-label">Doctor:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($appointment['doctor_name']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Hospital:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($appointment['hospital_name']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Date:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($appointment['date']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Time:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($appointment['time']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="detail-value text-success"><?php echo ucfirst(htmlspecialchars($appointment['status'])); ?></span>
                </div>
            </div>

            <div class="action-buttons text-center">
                <a href="user_dashboard.php" class="btn btn-primary">Return to Dashboard</a>
                <a href="Booking.php" class="btn btn-outline-primary">Book Another Appointment</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
