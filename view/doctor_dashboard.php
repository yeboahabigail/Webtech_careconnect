<?php
session_start();

// Database connection
require_once '../db/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch doctor's information
$user_id = $_SESSION['user_id'];
$doctor_query = "SELECT u.fname, u.lname, d.specialty, d.name, d.email, d.doctor_id
                FROM users u
                JOIN doctor_user du ON u.user_id = du.user_id
                JOIN doctors d ON d.doctor_id = du.doctor_id 
                WHERE u.user_id = ?";

$stmt = $conn->prepare($doctor_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$doctor_result = $stmt->get_result();
$doctor_data = $doctor_result->fetch_assoc();

// Fetch appointments
$appointments_query = "SELECT 
    a.appointment_id,
    a.appointment_date,
    a.appointment_time,
    a.status,
    a.doctor_id,
    u.fname as patient_fname,
    u.lname as patient_lname,
    u.user_id as patient_id,
    h.name as hospital_name
FROM appointments a
JOIN users u ON a.user_id = u.user_id
JOIN doctors d ON a.doctor_id = d.doctor_id
JOIN hospitals h ON d.hospital_id = h.hospital_id
WHERE a.doctor_id = ?
ORDER BY a.appointment_date DESC, a.appointment_time DESC";

$stmt = $conn->prepare($appointments_query);
$stmt->bind_param("i", $doctor_data['doctor_id']);
$stmt->execute();
$appointments_result = $stmt->get_result();

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_status':
                $appointment_id = $_POST['appointment_id'];
                $new_status = $_POST['status'];
                $update_query = "UPDATE appointments SET status = ? WHERE appointment_id = ?";
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param("si", $new_status, $appointment_id);
                $stmt->execute();
                header("Location: dashboard.php?message=Status updated successfully");
                exit();
                break;

            case 'delete':
                $appointment_id = $_POST['appointment_id'];
                $delete_query = "DELETE FROM appointments WHERE appointment_id = ?";
                $stmt = $conn->prepare($delete_query);
                $stmt->bind_param("i", $appointment_id);
                $stmt->execute();
                header("Location: dashboard.php?message=Appointment deleted successfully");
                exit();
                break;

            case 'add':
                $patient_id = $_POST['patient_id'];
                $appointment_date = $_POST['appointment_date'];
                $appointment_time = $_POST['appointment_time'];
                $insert_query = "INSERT INTO appointments (user_id, doctor_id, appointment_date, appointment_time, status) 
                               VALUES (?, ?, ?, ?, 'pending')";
                $stmt = $conn->prepare($insert_query);
                $stmt->bind_param("iiss", $patient_id, $doctor_data['doctor_id'], $appointment_date, $appointment_time);
                $stmt->execute();
                header("Location: doctor_dashboard.php?message=Appointment added successfully");
                exit();
                break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }

        body {
            background-color: lightblue;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar styles */
        .sidebar {
            width: 250px;
            background-color: #fff;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            padding: 20px;
        }

        .profile-section {
            text-align: center;
            padding: 20px 0;
            border-bottom: 1px solid #eee;
        }

        .profile-image {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            margin-bottom: 10px;
        }

        .nav-links {
            margin-top: 20px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: #374151;
            text-decoration: none;
            border-radius: 6px;
            margin-bottom: 5px;
            cursor: pointer;
        }

        .nav-link:hover {
            background-color: #f3f4f6;
        }

        /* Main content styles */
        .main-content {
            flex: 1;
            padding: 20px;
        }

        .top-bar {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .stat-card {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .modal-content {
            background-color: #fff;
            margin: 15% auto;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            position: relative;
        }

        /* Profile Modal specific styles */
        .profile-modal .modal-content {
            max-width: 600px;
        }

        .profile-details {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 10px;
            margin-top: 20px;
        }

        .profile-details dt {
            font-weight: bold;
            color: #374151;
        }

        /* Table styles */
        .table-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        th {
            font-weight: 600;
            background-color: #f9fafb;
        }

        /* Status badges */
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-pending { background-color: #fef3c7; color: #92400e; }
        .status-confirmed { background-color: #d1fae5; color: #065f46; }
        .status-cancelled { background-color: #fee2e2; color: #991b1b; }

        /* Button styles */
        .btn {
            padding: 8px 16px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
        }

        .btn-primary { background-color: #3b82f6; color: white; }
        .btn-success { background-color: #10b981; color: white; }
        .btn-danger { background-color: #ef4444; color: white; }
        .btn-secondary { background-color: #6b7280; color: white; }

        .btn:hover { opacity: 0.9; }

        /* Modal header */
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .close {
            font-size: 24px;
            cursor: pointer;
            color: #666;
            border: none;
            background: none;
        }

        /* Delete confirmation specific styles */
        .delete-confirm {
            text-align: center;
            padding: 20px;
        }

        .delete-confirm .btn-group {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        /* View appointment modal styles */
        .appointment-details {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 10px;
            margin-top: 20px;
        }

        .appointment-details dt {
            font-weight: bold;
            color: #374151;
        }

        /* Alert styles */
        .alert {
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            display: none;
        }

        .alert-success { background-color: #d1fae5; color: #065f46; }
        .alert-error { background-color: #fee2e2; color: #991b1b; }

        /* Action buttons in table */
        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .action-btn {
            padding: 4px 8px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: opacity 0.2s;
        }

        .action-btn:hover {
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="profile-section">
                <h2><?php echo htmlspecialchars($doctor_data['name']); ?></h2>
                <p><?php echo htmlspecialchars($doctor_data['specialty']); ?></p>
            </div>
            <nav class="nav-links">
    
    <form id="profileForm" action="../actions/update_profile.php" method="POST" style="display: inline;">
        <input type="hidden" name="action" value="view_profile">
        <button type="submit" class="nav-link" style="background: none; border: none; color: inherit; text-align: left; display: inline-flex; align-items: center; cursor: pointer;">
            <i class="fas fa-user-circle"></i>
            <span>Profile</span>
        </button>
    </form>

    <!-- Appointments -->
    <a class="nav-link active">
        <i class="fas fa-calendar-alt"></i>
        <span>Appointments</span>
    </a>

    <!-- Settings -->
    <a class="nav-link">
        <i class="fas fa-cog"></i>
        <span>Settings</span>
    </a>

    <!-- Logout -->
    <a href="logout.php" class="nav-link">
        <i class="fas fa-sign-out-alt"></i>
        <span>Logout</span>
    </a>
</nav>

        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="top-bar">
                <div>
                    <h1>Doctor's Dashboard</h1>
                    <p>Welcome back, Dr. <?php echo htmlspecialchars($doctor_data['fname']); ?></p>
                </div>
                <button class="btn btn-primary" onclick="openAddModal()">Add Appointment</button>
            </div>

            <!-- Table Container -->
            <div class="table-container">
                <h2>Patient Appointments</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($appointment = $appointments_result->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center;">
                                    <div>
                                        <?php echo htmlspecialchars($appointment['patient_fname'] . ' ' . $appointment['patient_lname']); ?>
                                        <br>
                                        <small>ID: <?php echo htmlspecialchars($appointment['patient_id']); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo date('F j, Y', strtotime($appointment['appointment_date'])); ?></td>
                            <td><?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo htmlspecialchars($appointment['status']); ?>">
                                    <?php echo ucfirst(htmlspecialchars($appointment['status'])); ?>
                                </span>
                            </td>
                            <td class="action-buttons">
                                <!-- <button onclick="viewAppointment(<//?php echo $appointment['appointment_id']; ?>)" class="btn btn-primary">View</button> -->
                                <button onclick="confirmAppointment(<?php echo $appointment['appointment_id']; ?>)" class="btn btn-success">Confirm</button>
                                <button onclick="confirmDelete(<?php echo $appointment['appointment_id']; ?>)" class="btn btn-danger">Delete</button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Profile Modal -->
    <div id="profileModal" class="modal profile-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Doctor Profile</h2>
                <button class="close" onclick="closeModal('profileModal')">&times;</button>
            </div>
            <div class="profile-details">
                <dt>Name:</dt>
                <dd><?php echo htmlspecialchars($doctor_data['name']); ?></dd>
                
                <dt>Specialty:</dt>
                <dd><?php echo htmlspecialchars($doctor_data['specialty']); ?></dd>
                
                <dt>Email:</dt>
                <dd><?php echo htmlspecialchars($doctor_data['email']); ?></dd>
            </div>
        </div>
    </div>

    <!-- View Appointment Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Appointment Details</h2>
                <button class="close" onclick="closeModal('viewModal')">&times;</button>
            </div>
            <div id="appointmentDetails" class="appointment-details">
                <!-- Details will be populated by JavaScript -->
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Confirm Deletion</h2>
                <button class="close" onclick="closeModal('deleteModal')">&times;</button>
            </div>
            <div class="delete-confirm">
                <p>Are you sure you want to delete this appointment?</p>
                <div class="btn-group">
                    <button class="btn btn-secondary" onclick="closeModal('deleteModal')">Cancel</button>
                    <button class="btn btn-danger" onclick="deleteAppointment()">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Appointment Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Appointment</h2>
                <button class="close" onclick="closeModal('addModal')">&times;</button>
            </div>
            <form id="addAppointmentForm" action="../actions/book_appointment.php" method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label for="user_id">Patient ID</label>
                    <input type="text" id="patient_id" name="patient_id" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="appointment_date">Date</label>
                    <input type="date" id="appointment_date" name="appointment_date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="appointment_time">Time</label>
                    <input type="time" id="appointment_time" name="appointment_time" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary">Add Appointment</button>
            </form>
        </div>
    </div>

    <!-- View Appointment Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Appointment Details</h2>
            <div id="appointmentDetails">
                <!-- Details will be populated by JavaScript -->
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Confirm Deletion</h2>
            <p>Are you sure you want to delete this appointment?</p>
            <form action="../actions/book_appointment.php" method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" id="delete_appointment_id" name="appointment_id">
                <div style="margin-top: 20px;">
                    <button type="button" class="btn btn-primary" onclick="closeModal('deleteModal')">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal and Button Handlers
    const addModal = document.getElementById('addModal');
    const viewModal = document.getElementById('viewModal');
    const deleteModal = document.getElementById('deleteModal');
    const addBtn = document.getElementById('addAppointmentBtn');
    const closeButtons = document.getElementsByClassName('close');

    // Open Add Appointment Modal
    function openAddModal() {
        addModal.style.display = 'block';
    }

    // Close Modal by ID
    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        modal.style.display = 'none';
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target === addModal || event.target === viewModal || event.target === deleteModal) {
            closeModal('addModal');
            closeModal('viewModal');
            closeModal('deleteModal');
        }
    };

    // View Appointment Details
    function viewAppointment(appointmentId) {
        fetch(`../actions/book_appointment.php?id=${appointmentId}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('appointmentDetails').innerHTML = `
                    <p><strong>Patient Name:</strong> ${data.patient_name}</p>
                    <p><strong>Date:</strong> ${data.appointment_date}</p>
                    <p><strong>Time:</strong> ${data.appointment_time}</p>
                    <p><strong>Status:</strong> ${data.status}</p>
                    <p><strong>Hospital:</strong> ${data.hospital_name}</p>
                `;
                viewModal.style.display = 'block';
            })
            .catch(error => console.error('Error fetching appointment details:', error));
    }

    // Confirm Appointment Status
    function confirmAppointment(appointmentId) {
        if (confirm('Are you sure you want to confirm this appointment?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'confirmation.php';

            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'confirm';

            const appointmentInput = document.createElement('input');
            appointmentInput.type = 'hidden';
            appointmentInput.name = 'appointment_id';
            appointmentInput.value = appointmentId;

            form.appendChild(actionInput);
            form.appendChild(appointmentInput);
            document.body.appendChild(form);
            form.submit();
        }
    }

    // Show Delete Confirmation Modal
    function confirmDelete(appointmentId) {
        document.getElementById('delete_appointment_id').value = appointmentId;
        deleteModal.style.display = 'block';
    }

    // Handle Appointment Deletion
    function deleteAppointment() {
        const appointmentId = document.getElementById('delete_appointment_id').value;
        if (appointmentId) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '../actions/book_appointment.php';

            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'delete';

            const appointmentInput = document.createElement('input');
            appointmentInput.type = 'hidden';
            appointmentInput.name = 'appointment_id';
            appointmentInput.value = appointmentId;

            form.appendChild(actionInput);
            form.appendChild(appointmentInput);
            document.body.appendChild(form);
            form.submit();
        }
    }

    // Update Current Time
    function updateTime() {
        const timeElement = document.querySelector('.current-time');
        if (timeElement) {
            const now = new Date();
            timeElement.textContent = now.toLocaleTimeString();
        }
    }
    setInterval(updateTime, 1000);
    updateTime();

    // Auto-hide success messages
    const alertMessage = document.querySelector('.alert');
    if (alertMessage) {
        setTimeout(() => {
            alertMessage.style.display = 'none';
        }, 3000);
    }
    </script>
</body>
</html>