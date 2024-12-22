<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Confirmation</title>
    <!-- CSS for Styling -->
    <link rel="stylesheet" href="../styles/confirmation.css">
    <!-- JavaScript for Dynamic Content -->
    <script src="../scripts/fetchBeds.js" defer></script>
</head>
<body>
    <!-- Header -->
    <h1>Appointment Confirmation</h1>
    <!-- Appointment Details -->
    <div class="appointment-details">
        <h2>Your Appointment Details</h2>
        <p><strong>Doctor:</strong> <?php echo htmlspecialchars($appointment['doctor_name']); ?></p>
        <p><strong>Date:</strong> <?php echo htmlspecialchars($appointment['date']); ?></p>
        <p><strong>Time:</strong> <?php echo htmlspecialchars($appointment['time']); ?></p>
    </div>
    <!-- Instructions -->
    <div class="instructions">
        <h2>Important Instructions</h2>
        <ul>
            <li>Bring your insurance card.</li>
            <li>Carry sufficient money in case of any unexpected charges.</li>
        </ul>
    </div>
    <!-- Sample Bed Images -->
    <div class="sample-beds">
        <h2>Sample Beds</h2>
        <p>Here are examples of the beds in the hospital. Scroll down to see real-time availability.</p>
        <div class="bed-gallery">
            <!-- Static bed images -->
            <img src="../images/bed1.jpg" alt="Hospital Bed 1">
            <img src="../images/bed2.jpg" alt="Hospital Bed 2">
            <img src="../images/bed3.jpg" alt="Hospital Bed 3">
            <img src="../images/bed4.jpg" alt="Hospital Bed 4">
            <img src="../images/bed5.jpg" alt="Hospital Bed 5">
        </div>
    </div>
    <!-- Available Beds Section -->
    <div class="bed-info">
        <h2>Available Beds</h2>
        <p>If you might need to be admitted, here are the available beds:</p>
        <div id="bed-container">
            <!-- Real-time bed cards dynamically injected by fetchBeds.js -->
        </div>
    </div>
</body>
</html>