<?php
session_start();
include('../db/config.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: Login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if user already has a bed booked
$check_booking = "SELECT b.bed_id, h.name as hospital_name 
                 FROM bed_bookings bb 
                 JOIN beds b ON bb.bed_id = b.bed_id 
                 JOIN hospitals h ON b.hospital_id = h.hospital_id 
                 WHERE bb.user_id = ? AND bb.status = 'active'";
$stmt = $conn->prepare($check_booking);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$booking_result = $stmt->get_result();
$has_active_booking = $booking_result->num_rows > 0;
$active_booking = $has_active_booking ? $booking_result->fetch_assoc() : null;

// Get user's appointment hospital
$appointment_query = "SELECT DISTINCT h.hospital_id, h.name as hospital_name 
                     FROM appointments a 
                     JOIN doctors d ON a.doctor_id = d.doctor_id 
                     JOIN hospitals h ON d.hospital_id = h.hospital_id 
                     WHERE a.user_id = ? AND a.status = 'pending'";
$stmt = $conn->prepare($appointment_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$appointment_result = $stmt->get_result();
$has_appointment = $appointment_result->num_rows > 0;
$appointment_hospital = $has_appointment ? $appointment_result->fetch_assoc() : null;

// Get all hospitals
$hospitals_query = "
    SELECT 
        h.*, 
        (SELECT COUNT(*) FROM beds b WHERE b.hospital_id = h.hospital_id AND b.is_available = 1 AND b.status != 'marked_damage') AS available_beds,
        (SELECT SUM(b.num_bed) FROM beds b WHERE b.hospital_id = h.hospital_id) AS total_beds,
        CASE WHEN h.hospital_id = ? THEN 1 ELSE 0 END as has_appointment
    FROM hospitals h
    ORDER BY has_appointment DESC, h.name";
    
$stmt = $conn->prepare($hospitals_query);
$appointment_hospital_id = $has_appointment ? $appointment_hospital['hospital_id'] : 0;
$stmt->bind_param("i", $appointment_hospital_id);
$stmt->execute();
$hospitals_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Beds</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .hospital-card {
            transition: all 0.3s ease;
        }
        .hospital-card:hover {
            transform: translateY(-5px);
        }
        /* .disabled-card {
            opacity: 0.7;
            cursor: not-allowed;
        } */
        .appointment-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: #10B981;
            color: white;
            padding: 4px 8px;
            border-radius: 9999px;
            font-size: 0.75rem;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-6xl mx-auto">
            <!-- Header -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="text-3xl font-bold text-gray-800">
                            <i class="fas fa-bed text-blue-500 mr-3"></i>
                            Hospital Beds
                        </h2>
                        <p class="text-gray-600 mt-2">View and book available hospital beds</p>
                    </div>
                    <a href="user_dashboard.php" class="flex items-center bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">
                        <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>

            <?php if (!$has_appointment): ?>
            <!-- No Appointment Alert -->
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-8">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-yellow-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700">
                            You need to have an active appointment to book a bed. Please book an appointment first.
                            <a href="Booking.php" class="font-medium underline">Book an appointment</a>
                        </p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($has_active_booking): ?>
            <!-- Active Booking Alert -->
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-8">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-yellow-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700">
                            You already have an active bed booking at <?php echo htmlspecialchars($active_booking['hospital_name']); ?> 
                            (Bed #<?php echo htmlspecialchars($active_booking['bed_id']); ?>).
                            You cannot book another bed until your current booking is completed or cancelled.
                        </p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Hospital Selection -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h3 class="text-xl font-bold text-gray-800 mb-4">Select Hospital</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php while ($hospital = $hospitals_result->fetch_assoc()): 
                        $can_book = $has_appointment && $hospital['hospital_id'] == $appointment_hospital['hospital_id'];
                    ?>
                        <div class="hospital-card relative bg-white rounded-lg shadow-md p-6 border border-gray-200 hover:border-blue-500 transition duration-200 <?php echo (!$can_book || $has_active_booking ? : ''); ?>">
                            <?php if ($hospital['has_appointment']): ?>
                                <span class="appointment-badge">Your Appointment Hospital</span>
                            <?php endif; ?>
                            <h4 class="text-lg font-bold text-gray-800 mb-2">
                                <?php echo htmlspecialchars($hospital['name']); ?>
                            </h4>
                            <p class="text-gray-600 mb-4">
                                <i class="fas fa-map-marker-alt text-red-500 mr-2"></i>
                                <?php echo htmlspecialchars($hospital['location']); ?>
                            </p>
                            <div class="flex justify-between items-center mb-4">
                                <div>
                                    <p class="text-sm text-gray-600">Available Beds</p>
                                    <p class="text-2xl font-bold <?php echo $hospital['available_beds'] > 0 ? 'text-blue-600' : 'text-red-600'; ?>">
                                        <?php echo $hospital['available_beds']; ?> / <?php echo $hospital['total_beds']; ?>
                                    </p>
                                </div>

                      <form method="POST" action="../actions/book_bed.php" class="book-bed-form" id="book-form-<?php echo $hospital['hospital_id']; ?>">
                        <input type="hidden" name="hospital_id" value="<?php echo $hospital['hospital_id']; ?>">
                        <button type="submit" 
                                id="book-btn-<?php echo $hospital['hospital_id']; ?>"
                                class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition-colors <?php echo (!$can_book || $has_active_booking || $hospital['available_beds'] == 0 ? 'opacity-50 cursor-not-allowed' : ''); ?>"
                                <?php echo (!$can_book || $has_active_booking || $hospital['available_beds'] == 0 ? 'disabled' : ''); ?>>
                            <?php 
                                if (!$can_book) {
                                    echo 'No Appointment Here';
                                } elseif ($hospital['available_beds'] == 0) {
                                    echo 'No Beds Available';
                                } else {
                                    echo 'Book Bed';
                                }
                            ?>
                        </button>
                        </form>




                                <!-- <form method="POST" action="../actions/book_bed.php" class="book-bed-form">
                                    <input type="hidden" name="hospital_id" value="<?php echo $hospital['hospital_id']; ?>">
                                    <button type="submit" 
                                            class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition-colors <?php echo (!$can_book || $has_active_booking || $hospital['available_beds'] == 0 ? 'opacity-50 cursor-not-allowed' : ''); ?>"
                                            <//?php echo (!$can_book || $has_active_booking || $hospital['available_beds'] == 0 ? : ''); ?>>
                                        <//?php 
                                            if (!$can_book) {
                                                echo 'No Appointment Here';
                                            } elseif ($hospital['available_beds'] == 0) {
                                                echo 'No Beds Available';
                                            } else {
                                                 echo 'Book Bed';
                                            }
                                        ?>
                                    </button>
                                </form> -->
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- <script src="../assets/hospital_beds1.js"></script> -->
<!-- <script>
    //form submission
document.querySelectorAll('.book-bed-form').forEach(form => {
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        if (confirm('Are you sure you want to book a bed at this hospital?')) {
            try {
                const response = await fetch(this.action, {
                    method: 'POST',
                    body: new FormData(this)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Bed booked successfully!');
                    window.location.href = data.redirect;
                } else {
                    alert(data.message || 'An error occurred while booking the bed.');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while booking the bed. Please try again.');
            }
        }
    });
});
</script> -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle form submissions
    document.querySelectorAll('.book-bed-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const hospitalId = this.querySelector('input[name="hospital_id"]').value;
            console.log('Sending hospital_id:', hospitalId); // Debug log

            if (confirm('Are you sure you want to book a bed at this hospital?')) {
                // Create form data
                const formData = new FormData();
                formData.append('hospital_id', hospitalId);

                // Send request using fetch
                fetch('../actions/book_bed.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Response:', data); // Debug log
                    
                    if (data.success) {
                        alert('Bed booked successfully!');
                        if (data.redirect) {
                            window.location.href = data.redirect;
                        }
                    } else {
                        alert(data.message || 'Failed to book bed. Please try again.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while booking the bed. Please try again.');
                });
            }
        });
    });
});
</script>






</body>
</html>