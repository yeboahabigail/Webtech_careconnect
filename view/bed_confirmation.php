<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: Login.php');
    exit;
}

if (!isset($_SESSION['bed_booking'])) {
    header('Location: user_dashboard.php');
    exit;
}

$booking = $_SESSION['bed_booking'];
// Clear the booking details from session after displaying
unset($_SESSION['bed_booking']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bed Booking Confirmation</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-md p-8">
            <!-- Success Icon -->
            <div class="text-center mb-8">
                <i class="fas fa-check-circle text-6xl text-green-500"></i>
                <h1 class="text-3xl font-bold text-gray-800 mt-4">Bed Booked Successfully!</h1>
                <p class="text-gray-600 mt-2">Your hospital bed has been reserved.</p>
            </div>

            <!-- Booking Details -->
            <div class="bg-gray-50 rounded-lg p-6 mb-8">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Booking Details</h2>
                <div class="grid grid-cols-1 gap-4">
                    <div class="border-b pb-3">
                        <p class="text-gray-600">Hospital</p>
                        <p class="font-semibold"><?php echo htmlspecialchars($booking['hospital_name']); ?></p>
                    </div>
                    <div class="border-b pb-3">
                        <p class="text-gray-600">Bed Number</p>
                        <p class="font-semibold">#<?php echo htmlspecialchars($booking['bed_id']); ?></p>
                    </div>
                    <div class="border-b pb-3">
                        <p class="text-gray-600">Booking Date</p>
                        <p class="font-semibold"><?php echo htmlspecialchars($booking['booking_date']); ?></p>
                    </div>
                    <div>
                        <p class="text-gray-600">Status</p>
                        <p class="font-semibold text-green-600"><?php echo ucfirst(htmlspecialchars($booking['status'])); ?></p>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col md:flex-row justify-center space-y-4 md:space-y-0 md:space-x-4">
                <a href="user_dashboard.php" class="bg-blue-500 text-white px-6 py-3 rounded-lg hover:bg-blue-600 transition-colors text-center">
                    <i class="fas fa-home mr-2"></i>
                    Return to Dashboard
                </a>
                <a href="hospital_beds.php" class="bg-gray-500 text-white px-6 py-3 rounded-lg hover:bg-gray-600 transition-colors text-center">
                    <i class="fas fa-hospital mr-2"></i>
                    View All Hospitals
                </a>
            </div>

            <!-- Additional Information -->
            <div class="mt-8 text-center text-gray-600">
                <p>Please contact the hospital directly for any specific requirements or questions.</p>
                <p class="mt-2">You can view your bed booking details in your dashboard.</p>
            </div>
        </div>
    </div>
</body>
</html>
