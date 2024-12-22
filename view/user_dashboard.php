<?php
session_start();
include('../db/config.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: Login.php');
    exit();
}


// Fetch user data
$user_id = $_SESSION['user_id'];
$query = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$user_result = $query->get_result();
$user_data = $user_result->fetch_assoc();

// Fetch appointments
$appointments_query = $conn->prepare("SELECT a.*, d.name as doctor_name 
    FROM appointments a 
    JOIN doctors d ON a.doctor_id = d.doctor_id 
    WHERE a.user_id = ? 
    ORDER BY a.appointment_date");
$appointments_query->bind_param("i", $user_id);
$appointments_query->execute();
$appointments_result = $appointments_query->get_result();

// Count total appointments
$total_appointments = $appointments_result->num_rows;

// Fetch hospital beds
$beds_query = "SELECT * FROM beds WHERE is_available = 1";
$beds_result = $conn->query($beds_query);
$available_beds = $beds_result ? $beds_result->num_rows : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/dashboard_styles.css">
    <style>
        #editProfileModal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }
        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 20px;
            border-radius: 5px;
            width: 70%;
            max-width: 500px;
            height: 400px;
        }
        .createtop{
            margin-top: 3rem;
        }

        .bookviews{
            margin: 0 104px ;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <nav class="bg-white shadow-md rounded-lg mb-6">
            <div class="flex justify-between items-center p-4">
                <h1 class="text-2xl font-bold text-blue-600">Welcome, <?php echo htmlspecialchars($user_data['fname']); ?>!</h1>
                <div>
                    <a href="#" class="nav-link px-4 py-2 text-gray-700 hover:text-blue-600" data-section="profile">Profile</a>
                    <a href="#" class="nav-link px-4 py-2 text-gray-700 hover:text-blue-600" data-section="appointments">Appointments</a>
                    <a href="#" class="nav-link px-4 py-2 text-gray-700 hover:text-blue-600" data-section="hospital-beds">Hospital Beds</a>
                    <a href="../actions/logout.php" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Logout</a>
                </div>
            </div>
        </nav>

        <!-- Profile Section -->
        <section id="profile-section" class="dashboard-section">
            <div class="bg-white shadow-md rounded-lg p-6">
                <h2 class="text-xl font-semibold mb-4">My Profile</h2>
                <div class="grid grid-cols-2 gap-4 createtop" >  
                        <strong>Name:</strong> <?php echo htmlspecialchars($user_data['fname'] . ' ' . $user_data['lname']); ?>
                    </div>
                    <div>
                        <strong>Email:</strong> <?php echo htmlspecialchars($user_data['email']); ?>
                    </div>
                </div>
                <button id="edit-profile-btn" class="mt-4 bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Edit Profile</button>
            </div>
        </section>

        <!-- Appointments Section -->
        <section id="appointments-section" class="dashboard-section hidden">
            <div class="bg-white shadow-md rounded-lg p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold">My Appointments</h2>
                    <div class="text-gray-600">Total Appointments: <?php echo $total_appointments; ?></div>
                </div>
                <?php if ($total_appointments > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-200">
                                    <th class="p-2">Doctor</th>
                                    <th class="p-2">Date</th>
                                    <th class="p-2">Time</th>
                                    <th class="p-2">Status</th>
                                    <th class="p-2">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($appointment = $appointments_result->fetch_assoc()): ?>
                                    <tr class="border-b">
                                        <td class="p-2"><?php echo htmlspecialchars($appointment['doctor_name']); ?></td>
                                        <td class="p-2"><?php echo htmlspecialchars($appointment['appointment_date']); ?></td>
                                        <td class="p-2"><?php echo htmlspecialchars($appointment['appointment_time']); ?></td>
                                        <td class="p-2"><?php echo htmlspecialchars($appointment['status']); ?></td>
                                        <td class="p-2">
                                            <?php if ($appointment['status'] === 'pending'): ?>
                                                <button class="bg-red-500 text-white px-2 py-1 rounded text-sm hover:bg-red-600" 
                                                        onclick="cancelAppointment(<?php echo $appointment['appointment_id']; ?>)">
                                                    Cancel
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center text-gray-600">No appointments found.</p>
                <?php endif; ?>
                <a href="Booking.php" class="inline-block mt-4 bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">Book New Appointment</a>
            </div>
        </section>

        <!-- Hospital Beds Section -->
        <section id="hospital-beds-section" class="dashboard-section hidden">
            <div class="bg-white shadow-md rounded-lg p-6">
                <h2 class="text-xl font-semibold mb-4">Available Hospital Beds</h2>
                <div class="text-center">
                    <div class="text-6xl font-bold text-blue-600"><?php echo $available_beds; ?></div>
                    <p class="text-gray-600 mt-2">Currently Available</p>
                </div>
            </div>
        </section>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 bookviews mx-auto" style="margin-top: 1.5rem;">
            <!-- Book Appointment Card -->
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h3 class="text-xl font-semibold mb-4">Book Appointment</h3>
                <p class="text-gray-600 mb-4">Schedule an appointment with one of our doctors.</p>
                <a href="Booking.php" class="inline-block bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600">
                    Book Now
                </a>
            </div>

            <!-- Hospital Beds Card -->
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h3 class="text-xl font-semibold mb-4">Hospital Beds</h3>
                <p class="text-gray-600 mb-4">Check available beds in our hospitals.</p>
                <a href="hospital_beds.php" class="inline-block bg-green-500 text-white px-6 py-2 rounded hover:bg-green-600">
                    View Beds
                </a>
            </div>
        </div>
    </div>

    <!-- Edit Profile Modal -->
    <div id="editProfileModal">
        <div class="modal-content">
            <h2 class="text-xl font-bold mb-4">Edit Profile</h2>
            <form id="editProfileForm">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="fname">First Name</label>
                    <input type="text" id="fname" name="fname" value="<?php echo htmlspecialchars($user_data['fname']); ?>" 
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="lname">Last Name</label>
                    <input type="text" id="lname" name="lname" value="<?php echo htmlspecialchars($user_data['lname']); ?>"
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>"
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="new_password">New Password (leave blank to keep current)</label>
                    <input type="password" id="new_password" name="new_password"
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                <div class="flex justify-end">
                    <button type="button" id="cancelEdit" class="bg-gray-500 text-white px-4 py-2 rounded mr-2 hover:bg-gray-600">Cancel</button>
                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Show/hide sections
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const section = e.target.dataset.section;
                document.querySelectorAll('.dashboard-section').forEach(s => s.classList.add('hidden'));
                document.getElementById(section + '-section').classList.remove('hidden');
            });
        });

        // Cancel appointment function
        function cancelAppointment(appointmentId) {
            if (confirm('Are you sure you want to cancel this appointment?')) {
                fetch('../actions/cancel_appointment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'appointment_id=' + appointmentId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message || 'Failed to cancel appointment');
                    }
                })
                .catch(error => {
                    alert('An error occurred while canceling the appointment');
                });
            }
        }

        // Book new appointment button
        document.getElementById('new-appointment-btn').addEventListener('click', () => {
            window.location.href = '../actions/book_appointment.php';
        });
    </script>
    <script src="../assets/user_dashboard.js"></script>
    <script src="../assets/profile.js"></script>
</body>
</html>