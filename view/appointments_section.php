<?php
if (!isset($conn) || !isset($user_id)) {
    die('Database connection or user ID not available');
}

// Fetch appointments with hospital information
$appointments_query = $conn->prepare("
    SELECT a.*, d.name as doctor_name, h.name as hospital_name, h.hospital_id
    FROM appointments a 
    JOIN doctors d ON a.doctor_id = d.doctor_id 
    JOIN hospitals h ON d.hospital_id = h.hospital_id
    WHERE a.user_id = ? AND a.status != 'cancelled'
    ORDER BY a.appointment_date, a.appointment_time
");
$appointments_query->bind_param("i", $user_id);
$appointments_query->execute();
$appointments_result = $appointments_query->get_result();

// Fetch hospitals with available beds
$hospitals_query = "
    SELECT h.*, 
           (SELECT COUNT(*) FROM beds b WHERE b.hospital_id = h.hospital_id AND b.is_available = 1) as available_beds
    FROM hospitals h 
    WHERE h.num_beds > 0
    ORDER BY h.name
";
$hospitals_result = $conn->query($hospitals_query);
?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 p-6">
    <!-- Current Appointments -->
    <div class="bg-white shadow-lg rounded-xl p-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">
            <i class="fas fa-calendar-check text-blue-500 mr-2"></i>
            Your Appointments
        </h2>
        
        <?php if ($appointments_result->num_rows > 0): ?>
            <div class="space-y-4">
                <?php while ($appointment = $appointments_result->fetch_assoc()): ?>
                    <div class="border-2 rounded-xl p-4 hover:border-blue-500 transition-all duration-300 bg-gray-50">
                        <div class="flex justify-between items-start">
                            <div class="space-y-2">
                                <h3 class="font-bold text-lg text-gray-800">
                                    Dr. <?php echo htmlspecialchars($appointment['doctor_name']); ?>
                                </h3>
                                <p class="text-gray-600 flex items-center">
                                    <i class="fas fa-hospital-alt text-blue-500 mr-2"></i>
                                    <?php echo htmlspecialchars($appointment['hospital_name']); ?>
                                </p>
                                <p class="text-gray-600 flex items-center">
                                    <i class="fas fa-calendar text-green-500 mr-2"></i>
                                    <?php echo date('F j, Y', strtotime($appointment['appointment_date'])); ?>
                                    at <?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?>
                                </p>
                                <?php if ($appointment['status']): ?>
                                    <span class="inline-block px-3 py-1 text-sm font-semibold rounded-full 
                                        <?php echo $appointment['status'] === 'confirmed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                        <?php echo ucfirst($appointment['status']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <button onclick="cancelAppointment(<?php echo $appointment['appointment_id']; ?>)"
                                    class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition-colors duration-300 flex items-center">
                                <i class="fas fa-times mr-2"></i>
                                Cancel
                            </button>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-8">
                <i class="fas fa-calendar-times text-5xl text-gray-400 mb-4"></i>
                <p class="text-gray-500 mb-4">No appointments scheduled</p>
                <button onclick="showSection('book-appointment')" 
                        class="bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition-colors duration-300">
                    Book an Appointment
                </button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Available Hospital Beds -->
    <div class="bg-white shadow-lg rounded-xl p-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">
            <i class="fas fa-bed text-blue-500 mr-2"></i>
            Hospital Beds
        </h2>

        <?php if ($hospitals_result->num_rows > 0): ?>
            <div class="grid gap-4">
                <?php while ($hospital = $hospitals_result->fetch_assoc()): ?>
                    <div class="border-2 rounded-xl p-4 hover:border-blue-500 transition-all duration-300 bg-gray-50">
                        <div class="flex flex-col space-y-2">
                            <h3 class="font-bold text-lg text-gray-800">
                                <?php echo htmlspecialchars($hospital['name']); ?>
                            </h3>
                            <p class="text-gray-600 flex items-center">
                                <i class="fas fa-map-marker-alt text-red-500 mr-2"></i>
                                <?php echo htmlspecialchars($hospital['location']); ?>
                            </p>
                            <p class="text-blue-600 font-semibold flex items-center">
                                <i class="fas fa-bed mr-2"></i>
                                <?php echo $hospital['available_beds']; ?> beds available
                            </p>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-8">
                <i class="fas fa-bed text-5xl text-gray-400 mb-4"></i>
                <p class="text-gray-500">No hospital beds available at the moment</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="../assets/dashboard.js"></script>
