<?php
session_start();
include('../db/config.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: Login.php');
    exit();
}

// Fetch hospitals
$hospitals_query = "SELECT * FROM hospitals ORDER BY name";
$hospitals_result = $conn->query($hospitals_query);

// Check if we have any hospitals
if (!$hospitals_result || $hospitals_result->num_rows === 0) {
    echo "No hospitals available. Please try again later.";
    exit();
}

// Fetch doctors
$doctors_query = "SELECT * FROM doctors ORDER BY schedule";
$doctors_result = $conn->query($doctors_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- Add Flatpickr Calendar CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-bold mb-6 text-center">Book an Appointment</h2>
            
            <form id="appointmentForm" class="space-y-6" method="POST" action="../actions/book_appointment.php">
                <!-- Hospital Selection -->
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="hospital_id">Select Hospital</label>
                    <select id="hospital_id" name="hospital_id" required
                            class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Choose a hospital...</option>
                        <?php while ($hospital = $hospitals_result->fetch_assoc()): ?>
                            <option value="<?php echo $hospital['hospital_id']; ?>">
                                <?php echo htmlspecialchars($hospital['name']); ?> - 
                                <?php echo htmlspecialchars($hospital['region']); ?> 
                                (<?php echo htmlspecialchars($hospital['location']); ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- Doctor Selection -->
                <div id="doctor-section" class="hidden">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="doctor_id">
                        Select Doctor
                        <span id="hospital-name" class="text-sm font-normal text-gray-500"></span>
                    </label>
                    <select id="doctor_id" name="doctor_id" required 
                            class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">First select a hospital...</option>
                    </select>
                    <p id="doctor-info" class="text-sm text-gray-500 mt-2 hidden"></p>
                </div>

                <!-- Calendar and Available Dates Section -->
                <div id="days-section" class="hidden">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Calendar Column -->
                        <div class="md:col-span-2">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="appointment_date">
                                Select Appointment Date
                            </label>
                            <input type="text" id="appointment_date" name="appointment_date" required
                                   class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="Click to select date...">
                            <div id="calendar-container" class="mt-2 bg-white rounded-lg shadow p-2"></div>
                        </div>
                        
                        <!-- Available Dates Column -->
                        <div>
                            <div class="bg-white rounded-lg shadow p-4">
                                <h3 class="text-lg font-semibold mb-3">Available Day</h3>
                                <div id="available-dates" class="space-y-2 max-h-64 overflow-y-auto">
                                    <!-- Available dates will be listed here -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Time Selection -->
                <div id="time-section" class="hidden">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="appointment_time">Available Time Slots</label>
                    <select id="appointment_time" name="appointment_time" required
                            class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Select time...</option>
                    </select>
                    <p class="text-sm text-gray-500 mt-2">
                        Note: Time slots shown are available for booking. The doctor's break time and booked slots are excluded.
                    </p>
                </div>

                <!-- Patient Concerns/Notes -->
                <div id="concerns-section">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="concerns">
                        Notes for the Doctor (Optional)
                        <span class="text-sm font-normal text-gray-500">
                            - Please describe your symptoms or concerns
                        </span>
                    </label>
                    <textarea id="concerns" name="concerns" rows="4"
                            class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Describe your symptoms or any concerns you'd like the doctor to know about..."></textarea>
                </div>

                <div class="flex justify-between">
                    <a href="user_dashboard.php" 
                       class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600">
                        Back
                    </a>
                    <button type="submit" 
                            class="bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600">
                        Book Appointment
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Flatpickr Calendar JS and -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        // Hospital Selection Handler
        document.getElementById('hospital_id').addEventListener('change', function() {
            const hospitalId = this.value;
            const doctorSection = document.getElementById('doctor-section');
            const timeSection = document.getElementById('time-section');
            const doctorInfo = document.getElementById('doctor-info');
            const hospitalName = document.getElementById('hospital-name');
            const doctorSelect = document.getElementById('doctor_id');
            const daysSection = document.getElementById('days-section');

            // Reset and hide subsequent sections
            doctorSelect.innerHTML = '<option value="">Select a doctor...</option>';
            timeSection.classList.add('hidden');
            doctorInfo.classList.add('hidden');
            hospitalName.textContent = '';
            daysSection.classList.add('hidden');
            
            if (!hospitalId) {
                doctorSection.classList.add('hidden');
                return;
            }

            // Show loading state
            doctorSelect.innerHTML = '<option value="">Loading doctors...</option>';
            doctorSelect.disabled = true;
            doctorSection.classList.remove('hidden');
            
            // Fetch doctors for this hospital
            fetch(`../actions/get_doctors.php?hospital_id=${hospitalId}`)
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        throw new Error(data.message || 'Failed to load doctors');
                    }
                    
                    doctorSelect.innerHTML = '<option value="">Select a doctor...</option>';
                    
                    if (!data.doctors || data.doctors.length === 0) {
                        doctorSelect.innerHTML = '<option value="">No doctors available at this hospital</option>';
                        doctorInfo.textContent = 'Currently, there are no doctors registered at this hospital. Please select a different hospital.';
                        doctorInfo.classList.remove('hidden');
                        doctorSelect.disabled = true;
                        return;
                    }
                    
                    hospitalName.textContent = ` - ${data.hospital_name}`;
                    data.doctors.forEach(doctor => {
                        const option = document.createElement('option');
                        option.value = doctor.doctor_id;
                        option.textContent = `Dr. ${doctor.name} - ${doctor.specialty}`;
                        if (!doctor.available) {
                            option.textContent += ' (Fully Booked)';
                            option.disabled = true;
                        }
                        doctorSelect.appendChild(option);
                    });
                    
                    doctorSelect.disabled = false;
                    doctorInfo.textContent = `${data.doctors.length} doctor${data.doctors.length > 1 ? 's' : ''} available at this hospital`;
                    doctorInfo.classList.remove('hidden');
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    doctorSelect.innerHTML = '<option value="">Error loading doctors</option>';
                    doctorInfo.textContent = error.message || 'An error occurred while loading doctors. Please try again.';
                    doctorInfo.classList.remove('hidden');
                    doctorSelect.disabled = true;
                });
        });

       // Doctor Selection Handler with Calendar Integration
document.getElementById('doctor_id').addEventListener('change', function() {
    const daysSection = document.getElementById('days-section');
    const timeSection = document.getElementById('time-section');
    const availableDatesDiv = document.getElementById('available-dates');
    
    if (!this.value) {
        daysSection.classList.add('hidden');
        timeSection.classList.add('hidden');
        return;
    }

    daysSection.classList.remove('hidden');
    timeSection.classList.add('hidden');
    
    const doctorId = this.value;

    fetch(`../actions/get_doctor_schedule.php?doctor_id=${doctorId}`)
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                throw new Error(data.message || 'Failed to fetch doctor schedule');
            }

            try {
                // Process the schedule data
                let schedule;
                if (typeof data.schedule === 'string') {
                    let cleanedSchedule = data.schedule
                        .replace(/^"|"$/g, '')
                        .replace(/\\"/g, '"')
                        .replace(/\\n|\\r/g, '');
                    schedule = JSON.parse(cleanedSchedule);
                } else if (typeof data.schedule === 'object') {
                    schedule = data.schedule;
                } else {
                    throw new Error('Invalid schedule format');
                }

                if (!schedule || Object.keys(schedule).length === 0) {
                    throw new Error('Doctor has no available schedule');
                }

                // Get available dates
                const availableDates = Object.keys(schedule);
                const bookedSlots = data.booked_times || [];

                // Update available dates list
                availableDatesDiv.innerHTML = availableDates.map(date => 
                    `<div class="date-list-item" data-date="${date}">${date}</div>`
                ).join('');

                // Add click handlers to date list items
                document.querySelectorAll('.date-list-item').forEach(item => {
                    item.addEventListener('click', () => {
                        const date = item.dataset.date;
                        document.getElementById('appointment_date').value = date;
                        updateTimeSlots(date, schedule, bookedSlots);
                        timeSection.classList.remove('hidden');
                    });
                });

                // Initialize Flatpickr calendar
                flatpickr("#appointment_date", {
                    dateFormat: "Y-m-d",
                    enable: availableDates,
                    inline: true,
                    appendTo: document.getElementById('calendar-container'),
                    onChange: function(selectedDates, dateStr) {
                        updateTimeSlots(dateStr, schedule, bookedSlots);
                        timeSection.classList.remove('hidden');
                    }
                });

            } catch (error) {
                console.error('Schedule processing error:', error);
                throw new Error('Error processing doctor\'s schedule');
            }
        })
        .catch(error => {
            console.error('Schedule fetch error:', error);
            alert(error.message);
            daysSection.classList.add('hidden');
            timeSection.classList.add('hidden');
        });
});

// Function to update time slots
function updateTimeSlots(selectedDate, schedule, bookedTimes) {
    const timesSelect = document.getElementById('appointment_time');
    timesSelect.innerHTML = '<option value="">Select time...</option>';

    if (selectedDate && schedule[selectedDate]) {
        let availableTimes = schedule[selectedDate];
        if (typeof availableTimes === 'string') {
            availableTimes = availableTimes.split(', ');
        }

        // Filter out booked times
        const filteredTimes = availableTimes.filter(time => 
            !bookedTimes.includes(time)
        );

        filteredTimes.forEach(time => {
            const option = document.createElement('option');
            option.value = time;
            option.textContent = time;
            timesSelect.appendChild(option);
        });
    }
}
        // Handle form submission
        document.getElementById('appointmentForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            try {
                const response = await fetch('../actions/book_appointment.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // If booking is successful, redirect to confirmation page
                    window.location.href = data.redirect;
                } else {
                    // Display error message
                    alert(data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while booking the appointment. Please try again.');
            }
        });
    </script>
</body>
</html>