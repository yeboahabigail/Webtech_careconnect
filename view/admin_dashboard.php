<?php
session_start();
include('../db/config.php');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: Login.php');
    exit();
}

// Fetch user data with role
$user_id = $_SESSION['user_id'];
$query = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();
$user_data = $result->fetch_assoc();

// Debug user data
error_log("User Data: " . print_r($user_data, true));

// Verify admin role (case-insensitive comparison)
if (!$user_data || strtolower($user_data['role']) !== 'admin') {
    error_log("User role check failed. User role: " . ($user_data['role'] ?? 'no role'));
    header('Location: user_dashboard.php');
    exit();
}


// Fetch total users (excluding admins)
$total_users_query = "SELECT COUNT(*) as total FROM users WHERE role != 'admin'";
$total_users_result = $conn->query($total_users_query);
$total_users = $total_users_result->fetch_assoc()['total'];

// Fetch appointment statistics
$appointment_stats_query = "SELECT 
    COUNT(*) as total_appointments,
    SUM(status = 'pending') as pending,
    SUM(status = 'confirmed') as confirmed,
    SUM(status = 'completed') as completed,
    SUM(status = 'cancelled') as cancelled
FROM appointments";

$appointment_stats_result = $conn->query($appointment_stats_query);
$appointment_stats = $appointment_stats_result ? $appointment_stats_result->fetch_assoc() : [
    'total_appointments' => 0,
    'pending' => 0,
    'confirmed' => 0,
    'completed' => 0,
    'cancelled' => 0
];

// Fetch bed statistics
$beds_query = "SELECT 
    COUNT(*) as total_beds,
    SUM(is_available = 1 ) as available_beds,
    SUM(status = 'damaged') as damaged_beds
FROM beds";
$beds_result = $conn->query($beds_query);
$beds_stats = $beds_result ? $beds_result->fetch_assoc() : [
    'total_beds' => 0,
    'available_beds' => 0,
    'damaged_beds' => 0
];

// Fetch recent appointments with user and doctor details
$recent_appointments_query = "
    SELECT 
    a.status,
    a.appointment_id AS id,
    a.appointment_date AS appointment_date,
    a.appointment_time AS appointment_time, 
    u.fname AS user_fname, 
    u.lname AS user_lname,
    d.name AS doctor_name,
    h.name AS hospital_name
FROM appointments a
LEFT JOIN users u ON a.user_id = u.user_id
LEFT JOIN doctors d ON a.doctor_id = d.doctor_id
LEFT JOIN hospitals h ON d.hospital_id = h.hospital_id
ORDER BY a.appointment_date DESC, a.appointment_time DESC
LIMIT 10";
$recent_appointments_result = $conn->query($recent_appointments_query);

// Fetch admin actions if the table exists
$admin_actions_query = "SHOW TABLES LIKE 'admin_actions'";
$admin_actions_exists = $conn->query($admin_actions_query)->num_rows > 0;

if ($admin_actions_exists) {
    $admin_actions_query = "
        SELECT * FROM admin_actions 
        ORDER BY action_date DESC 
        LIMIT 10";
    $admin_actions_result = $conn->query($admin_actions_query);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/admin_dashboard.css">
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <!-- Navigation Bar -->
        <nav class="bg-white shadow-lg rounded-xl mb-8">
            <div class="max-w-7xl mx-auto px-4">
                <div class="flex justify-between items-center py-4">
                    <div class="flex items-center">
                        <i class="fas fa-hospital-user text-blue-600 text-3xl mr-3"></i>
                        <h1 class="text-2xl font-bold text-gray-800">Welcome, <?php echo htmlspecialchars($user_data['fname']); ?>!</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="Landing_page.php" class="text-gray-600 hover:text-blue-500 transition-colors duration-200">
                            <i class="fas fa-home mr-1"></i> Home
                        </a>
                        <a href="../actions/logout.php" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition-colors duration-200">
                            <i class="fas fa-sign-out-alt mr-1"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Statistics Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Users Card -->
            <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow duration-200">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-500">
                        <i class="fas fa-users text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-gray-500">Total Users</p>
                        <p class="text-2xl font-bold"><?php echo $total_users; ?></p>
                    </div>
                </div>
            </div>

            <!-- Total Appointments Card -->
            <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow duration-200">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-500">
                        <i class="fas fa-calendar-check text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-gray-500">Total Appointments</p>
                        <p class="text-2xl font-bold"><?php echo $appointment_stats['total_appointments']; ?></p>
                    </div>
                </div>
            </div>

            <!-- Confirmed Appointments Card -->
            <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow duration-200">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-500">
                        <i class="fas fa-check-circle text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-gray-500">Confirmed Appointments</p>
                        <p class="text-2xl font-bold"><?php echo $appointment_stats['confirmed']; ?></p>
                    </div>
                </div>
            </div>

            <!-- Cancelled Appointments Card -->
            <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow duration-200">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-red-100 text-red-500">
                        <i class="fas fa-times-circle text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-gray-500">Cancelled Appointments</p>
                        <p class="text-2xl font-bold"><?php echo $appointment_stats['cancelled']; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Analytics Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Appointment Statistics -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold mb-6">Appointment Analytics</h2>
                <canvas id="appointmentChart" class="w-full"></canvas>
            </div>

            <!-- Bed Status -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold mb-6">Bed Status Distribution</h2>
                <canvas id="bedChart" class="w-full"></canvas>
            </div>
        </div>

      <!-- Updated appointments table with action buttons -->
<div class="bg-white rounded-xl shadow-lg p-6 mb-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold">Recent Appointments</h2>
        <button onclick="openAddModal()" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
            Add Appointment
        </button>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead>
                <tr class="bg-gray-50">
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Doctor</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if ($recent_appointments_result && $recent_appointments_result->num_rows > 0): ?>
                    <?php while ($appointment = $recent_appointments_result->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($appointment['user_fname'] . ' ' . $appointment['user_lname']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">Dr. <?php echo htmlspecialchars($appointment['doctor_name']); ?> (<?php echo htmlspecialchars($appointment['hospital_name']); ?>)</td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo date('F j, Y', strtotime($appointment['appointment_date'])); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?php 
                                    echo match($appointment['status']) {
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'confirmed' => 'bg-green-100 text-green-800',
                                        'completed' => 'bg-blue-100 text-blue-800',
                                        'cancelled' => 'bg-red-100 text-red-800',
                                        default => 'bg-gray-100 text-gray-800'
                                    };
                                    ?>">
                                    <?php echo ucfirst($appointment['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                <button onclick="viewAppointment(<?php echo $appointment['id']; ?>)" 
                                        class="text-blue-600 hover:text-blue-900">View</button>
                                <button onclick="openEditModal(<?php echo $appointment['id']; ?>)"
                                        class="text-green-600 hover:text-green-900">Edit</button>
                                <button onclick="confirmDelete()" class="text-red-600 hover:text-red-900">Delete</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">No appointments found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Templates -->
<div id="appointmentModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900" id="modalTitle">Add Appointment</h3>
            <form id="appointmentForm" class="mt-4">
                <input type="hidden" id="appointmentId" name="id">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Patient</label>
                    <select name="patient_id" id="patientSelect" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        <?php foreach ($patients as $patient): ?>
                            <option value="<?php echo $patient['id']; ?>">
                                <?php echo htmlspecialchars($patient['fname'] . ' ' . $patient['lname']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Doctor</label>
                    <select name="doctor_id" id="doctorSelect" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        <?php foreach ($doctors as $doctor): ?>
                            <option value="<?php echo $doctor['id']; ?>">
                                Dr. <?php echo htmlspecialchars($doctor['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Date</label>
                    <input type="date" name="appointment_date" id="appointmentDate" 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Time</label>
                    <input type="time" name="appointment_time" id="appointmentTime" 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Status</label>
                    <select name="status" id="statusSelect" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeModal()" 
                            class="bg-gray-200 px-4 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-300">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="bg-blue-500 px-4 py-2 rounded-md text-sm font-medium text-white hover:bg-blue-600">
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Modal -->
<div id="viewModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900">Appointment Details</h3>
            <div class="mt-4 space-y-4" id="appointmentDetails">
                <!-- Details will be populated dynamically -->
            </div>
            <div class="flex justify-end mt-4">
                <button onclick="closeViewModal()" 
                        class="bg-gray-200 px-4 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-300">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
        <!-- Admin Actions Log -->
        <?php if ($admin_actions_exists): ?>
            <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
                <h2 class="text-xl font-bold mb-6">Recent Admin Actions</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if ($admin_actions_result && $admin_actions_result->num_rows > 0): ?>
                                <?php while ($action = $admin_actions_result->fetch_assoc()): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                <?php 
                                                echo match($action['admin_action_type']) {
                                                    'ADD_BEDS' => 'bg-green-100 text-green-800',
                                                    'MARK_BED_DAMAGED' => 'bg-red-100 text-red-800',
                                                    default => 'bg-blue-100 text-blue-800'
                                                };
                                                ?>">
                                                <?php echo str_replace('_', ' ', $action['admin_action_type']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4"><?php echo htmlspecialchars($action['action_discription']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap"><?php echo date('F j, Y g:i A', strtotime($action['action_date'])); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="px-6 py-4 text-center text-gray-500">No admin actions recorded</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <?php


// Fetch hospitals with bed counts from beds table
$query = "SELECT 
    b.hospital_id, 
    h.name, 
    COUNT(CASE WHEN b.status = 'active_beds' THEN 1 END) AS active_beds,
    COUNT(CASE WHEN b.status = 'damaged' THEN 1 END) AS damaged_beds,
    SUM(b.num_bed) AS total_beds
FROM beds b
LEFT JOIN hospitals h ON b.hospital_id = h.hospital_id
GROUP BY b.hospital_id, h.name
ORDER BY h.name;
";

$result = $conn->query($query);
$hospitalData = [];
while ($row = $result->fetch_assoc()) {
    $hospitalData[] = $row;
}
?>

<!-- Bed Management Section -->
<div class="bg-white rounded-xl shadow-lg p-6">
    <h2 class="text-xl font-bold mb-6">Hospital Bed Overview</h2>
    
    <!-- Hospital Beds Table -->
    <div class="overflow-x-auto mb-8">
        <table class="min-w-full bg-white border border-gray-300">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 border-b text-left">Hospital ID</th>
                    <th class="px-6 py-3 border-b text-left">Hospital</th>
                    <th class="px-6 py-3 border-b text-center">Active Beds</th>
                    <th class="px-6 py-3 border-b text-center">Damaged Beds</th>
                    <th class="px-6 py-3 border-b text-center">Total Beds</th>
                    <th class="px-6 py-3 border-b text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($hospitalData as $hospital): ?>
                <tr class="hover:bg-gray-50">
    
                    <td class="px-6 py-4 border-b" id ="id" ><?php echo htmlspecialchars($hospital['hospital_id']); ?></td>
                    <td class="px-6 py-4 border-b"><?php echo htmlspecialchars($hospital['name']); ?></td>
                    <td class="px-6 py-4 border-b text-center"><?php echo $hospital['active_beds']; ?></td>
                    <td class="px-6 py-4 border-b text-center"><?php echo $hospital['damaged_beds']; ?></td>
                    <td class="px-6 py-4 border-b text-center"><?php echo $hospital['total_beds']; ?></td>
                    <td class="px-6 py-4 border-b text-center space-x-2">
                 <button onclick="openUpdateModal(<?php echo htmlspecialchars(json_encode($hospital)); ?>)" 
                                class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600">
                            Edit
                        </button>
                        <button onclick="openRemoveBedsModal(<?php echo htmlspecialchars(json_encode($hospital)); ?>)"
                                class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600">
                            Remove Beds
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <!-- Update Modal -->
    <div id="updateModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Update Bed Status</h3>
                <form id="updateForm" method="POST" action= "../actions/update_hospital_beds.php" class="space-y-4">

                   <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Hospital ID</label>
                        <input type="text" id="hospitalId"  name = "id"  class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Hospital Name</label>
                        <input type="text" id="hospitalName" disabled class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Total Number of Beds</label>
                        <input type="number" id="totalBeds" name="total_beds" required min="1" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Number of Damaged Beds</label>
                        <input type="number" id="damagedBeds" name="damaged_beds" required min="0"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                    
                    <div class="flex justify-end space-x-4">
                        <button type="button" onclick="closeModal('updateModal')" 
                                class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                            Cancel
                        </button>
                        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                            add
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Remove Beds Modal -->
    <div id="removeBedsModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Remove Beds</h3>
                <form id="removeBedsForm" class="space-y-4">
                    <input type="hidden" id="removeHospitalId" name="hospital_id">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Hospital Name</label>
                        <input type="text" id="removeHospitalName" disabled class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Number of Beds to Remove</label>
                        <input type="number" id="bedsToRemove" name="beds_to_remove" required min="1"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            
                    </div>
                    <div class="mt-2">
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="remove_damaged" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-sm text-gray-700">Remove from damaged beds first</span>
                        </label>
                    </div>
                    <div class="flex justify-end space-x-4">
                        <button type="button" onclick="closeModal('removeBedsModal')" 
                                class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                            Cancel
                        </button>
                        <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">
                            Remove Beds
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
const hospitals = <?php echo json_encode($hospitalData); ?>;

// Chart initialization
const ctx = document.getElementById('bedChart').getContext('2d');
const colors = ['#4CAF50', '#f44336'];

const chartData = {
    labels: ['Active Beds', 'Damaged Beds'],
    datasets: hospitals.map((hospital) => ({
        label: hospital.name,
        data: [hospital.active_beds, hospital.damaged_beds],
        backgroundColor: colors,
    }))
};

new Chart(ctx, {
    type: 'pie',
    data: chartData,
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top',
            },
            title: {
                display: true,
                text: 'Hospital Bed Distribution'
            }
        }
    }
});

// Modal Functions
function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
}

function openUpdateModal(hospital) {
    document.getElementById('hospitalId').value = hospital.hospital_id;
    document.getElementById('hospitalName').value = hospital.name;
    document.getElementById('totalBeds').value = hospital.total_beds;
    document.getElementById('damagedBeds').value = hospital.damaged_beds;
    document.getElementById('updateModal').classList.remove('hidden');
}

function openRemoveBedsModal(hospital) {
    document.getElementById('removeHospitalId').value = hospital.hospital_id;
    document.getElementById('removeHospitalName').value = hospital.name;
    document.getElementById('bedsToRemove').max = hospital.total_beds;
    document.getElementById('removeBedsModal').classList.remove('hidden');
}

// Form Submissions
document.getElementById('updateForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    const hospitalId = formData.get('hospital_id');
    const totalBeds = parseInt(formData.get('total_beds'));
    const damagedBeds = parseInt(formData.get('damaged_beds'));
    
    try {
        // Delete existing beds
        await fetch('../actions/api.php', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ hospital_id: hospitalId })
        });
        
        // Add active beds
        if (totalBeds - damagedBeds > 0) {
            await fetch('../api/actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    hospital_id: hospitalId,
                    num_beds: totalBeds - damagedBeds,
                    status: 'active_beds'
                })
            });
        }
        
        // Add damaged beds
        if (damagedBeds > 0) {
            await fetch('../actions/api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    hospital_id: hospitalId,
                    num_beds: damagedBeds,
                    status: 'damaged'
                })
            });
        }
        
        alert('Bed status updated successfully');
        window.location.reload();
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred while updating bed status');
    }
    
    closeModal('updateModal');
});

document.getElementById('removeBedsForm').addEventListener('submit', async (e) => {
    // e.preventDefault();
    const formData = new FormData(e.target);
    
    try {
        const response = await fetch('../actions/api.php', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                hospital_id: formData.get('hospital_id'),
                num_beds: formData.get('beds_to_remove'),
                remove_damaged_first: formData.get('remove_damaged') === 'on'
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('Beds removed successfully');
            window.location.reload();
        } else {
            alert(data.message || 'Failed to remove beds');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred while removing beds');
    }
    
    closeModal('removeBedsModal');
});
</script>
    

     <!-- Add Chart.js before your custom scripts -->
     <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Initialize Charts -->
    <script>
        // Appointment Analytics Chart
        const appointmentCtx = document.getElementById('appointmentChart').getContext('2d');
        window.appointmentChart = new Chart(appointmentCtx, {
            type: 'pie',
            data: {
                labels: ['Pending', 'Confirmed', 'Completed', 'Cancelled'],
                datasets: [{
                    data: [
                        <?php echo $appointment_stats['pending'] ?? 0; ?>,
                        <?php echo $appointment_stats['confirmed'] ?? 0; ?>,
                        <?php echo $appointment_stats['completed'] ?? 0; ?>,
                        <?php echo $appointment_stats['cancelled'] ?? 0; ?>
                    ],
                    backgroundColor: ['#F59E0B', '#10B981', '#3B82F6', '#EF4444']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Bed Status Chart
        const bedCtx = document.getElementById('bedChart').getContext('2d');
        window.bedChart = new Chart(bedCtx, {
            type: 'pie',
            data: {
                labels: ['Available', 'Occupied', 'Damaged'],
                datasets: [{
                    data: [
                        <?php echo $beds_stats['available_beds'] ?? 0; ?>,
                        <?php echo ($beds_stats['total_beds'] - $beds_stats['available_beds'] - $beds_stats['damaged_beds']) ?? 0; ?>,
                        <?php echo $beds_stats['damaged_beds'] ?? 0; ?>
                    ],
                    backgroundColor: ['#10B981', '#F59E0B', '#EF4444']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
<
  
<div class="bg-white rounded-lg shadow-md p-6 mb-8">
    <h3 class="text-xl font-bold mb-4">Recent Bed Bookings</h3>
    <div class="overflow-x-auto">
        <table class="min-w-full table-auto">
            <thead>
                <tr class="bg-gray-100">
                    <th class="px-6 py-3 text-left">Booking ID</th>
                    <th class="px-6 py-3 text-left">Patient</th>
                    <th class="px-6 py-3 text-left">Hospital</th>
                    <th class="px-6 py-3 text-left">Bed Number</th>
                    <th class="px-6 py-3 text-left">Status</th>
                    <th class="px-6 py-3 text-left">Booking Date</th>
                    <th class="px-6 py-3 text-left">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $bookings_query = "SELECT bb.*, u.fname as patient_fname, u.lname as patient_lname, h.name as hospital_name
                                    FROM bed_bookings bb
                                    JOIN users u ON bb.user_id = u.user_id
                                    JOIN hospitals h ON bb.hospital_id = h.hospital_id
                                    ORDER BY bb.booking_date DESC";
                $bookings_result = $conn->query($bookings_query);
                while ($booking = $bookings_result->fetch_assoc()):
                ?>
                <tr id="booking-<?php echo $booking['booking_id']; ?>" class="border-b">
                    <td class="px-6 py-4"><?php echo $booking['booking_id']; ?></td>
                    <td class="px-6 py-4"><?php echo htmlspecialchars($booking['patient_fname']); ?></td>
                    <td class="px-6 py-4"><?php echo htmlspecialchars($booking['hospital_name']); ?></td>
                    <td class="px-6 py-4"><?php echo $booking['bed_id']; ?></td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 rounded-full text-sm <?php echo htmlspecialchars($booking['status']); ?>">
                            <?php echo ucfirst($booking['status']); ?>
                        </span>
                    </td>
                    <td class="px-6 py-4"><?php echo date('Y-m-d H:i', strtotime($booking['booking_date'])); ?></td>
                    <td class="px-6 py-4">
                        <button onclick="openEditModal(<?php echo $booking['booking_id']; ?>)" class="bg-blue-500 text-white px-4 py-2 rounded">Edit</button>
                        <button onclick="deleteBooking(<?php echo $booking['booking_id']; ?>)" class="bg-red-500 text-white px-4 py-2 rounded">Delete</button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal for Edit Booking -->
<div id="editModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full flex justify-center items-center">
    <div class="relative bg-white p-6 rounded-lg w-96">
        <h3 class="text-lg font-bold mb-4">Edit Booking</h3>
        <form id="edit-form">
            <input type="hidden" id="booking-id" name="booking_id">
            <div class="mb-4">
                <label for="status" class="block">Status</label>
                <select id="status" name="status" class="w-full p-2 border rounded">
                    <option value="active">Active</option>
                    <option value="completed">Completed</option>
                    <option value="canceled">Canceled</option>
                </select>
            </div>
            <div class="mb-4">
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Submit</button>
                <button type="button" class="bg-gray-500 text-white px-4 py-2 rounded" onclick="closeEditModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- <script>
    // Open Edit Modal with data
    function openEditModal(bookingId) {
        const bookingRow = document.getElementById(`booking-${bookingId}`);
        const status = bookingRow.cells[4].textContent.trim().toLowerCase();

        document.getElementById('booking-id').value = bookingId;
        document.getElementById('status').value = status;

        // Show the modal
        document.getElementById('editModal').classList.remove('hidden');
    }

    // Close Edit Modal
    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
    }

    // Handle Edit Form Submission (via AJAX)
    document.getElementById('edit-form').addEventListener('submit', function(e) {
        e.preventDefault();

        const bookingId = document.getElementById('booking-id').value;
        const status = document.getElementById('status').value;

        // Perform AJAX request to update booking
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'api/update_booking.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4 && xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    // Update the row in the table
                    const bookingRow = document.getElementById(`booking-${bookingId}`);
                    bookingRow.cells[4].innerHTML = `<span class="px-2 py-1 rounded-full text-sm ${getStatusClass(status)}">${status}</span>`;
                    closeEditModal(); // Close the modal
                } else {
                    alert('Error updating booking.');
                }
            }
        };

        const data = JSON.stringify({
            booking_id: bookingId,
            status: status
        });

        xhr.send(data);
    });

    // Delete a booking
    function deleteBooking(bookingId) {
        if (confirm('Are you sure you want to delete this booking?')) {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'api/delete_booking.php', true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        // Remove the row from the table
                        document.getElementById(`booking-${bookingId}`).remove();
                    } else {
                        alert('Error deleting booking.');
                    }
                }
            };

            const data = JSON.stringify({ booking_id: bookingId });
            xhr.send(data);
        }
    }

    // Helper function to get status class for styling
    function getStatusClass(status) {
        switch (status) {
            case 'active': return 'bg-green-100 text-green-800';
            case 'completed': return 'bg-blue-100 text-blue-800';
            case 'canceled': return 'bg-red-100 text-red-800';
            default: return '';
        }
    }
</script> -->

   
    <!-- Add your custom admin dashboard JavaScript -->
    <script src="../assets/admin_dashboard.js"></script>
</body>
</html>