document.addEventListener('DOMContentLoaded', function() {
    // Appointment Modification Handler
    function modifyAppointment(appointmentId) {
        const form = document.getElementById('modify-appointment-form');
        
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(form);
            formData.append('action', 'modify');
            formData.append('appointment_id', appointmentId);

            fetch('../actions/appointment_management.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Appointment modified successfully');
                    // Refresh appointments or update UI
                    location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while modifying the appointment');
            });
        });
    }

    // Appointment Deletion Handler
    function deleteAppointment(appointmentId) {
        if (!confirm('Are you sure you want to delete this appointment?')) return;

        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('appointment_id', appointmentId);

        fetch('../actions/appointment_management.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Appointment deleted successfully');
                // Remove appointment from UI or refresh
                location.reload();
            } else {
                alert('Error: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the appointment');
        });
    }

    // Patient Addition Handler
    function addPatient() {
        const form = document.getElementById('add-patient-form');
        
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(form);
            formData.append('action', 'add');

            fetch('../actions/users_management.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Patient added successfully');
                    // Refresh patient list or update UI
                    loadPatientsList();
                    form.reset();
                } else {
                    alert('Error: ' + (data.errors ? data.errors.join(', ') : data.error));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while adding the patient');
            });
        });
    }

    // Load Patients List
    function loadPatientsList() {
        const patientsList = document.getElementById('patients-list');
        
        const formData = new FormData();
        formData.append('action', 'list');

        fetch('patient_management.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Clear existing list
                patientsList.innerHTML = '';
                
                // Create table or list of patients
                const table = document.createElement('table');
                table.className = 'w-full';
                
                // Add table header
                table.innerHTML = `
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="p-3">Name</th>
                            <th class="p-3">Email</th>
                            <th class="p-3">Phone</th>
                        </tr>
                    </thead>
                    <tbody id="patients-table-body"></tbody>
                `;
                
                const tbody = table.querySelector('#patients-table-body');
                
                // Populate table with patients
                data.patients.forEach(patient => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td class="p-3">${patient.fname} ${patient.lname}</td>
                        <td class="p-3">${patient.email}</td>
                        <td class="p-3">${patient.phone}</td>
                    `;
                    tbody.appendChild(row);
                });

                patientsList.appendChild(table);
            } else {
                patientsList.innerHTML = '<p>No patients found.</p>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            patientsList.innerHTML = '<p>Error loading patients.</p>';
        });
    }

    // Event Listeners
    const modifyButtons = document.querySelectorAll('.modify-appointment');
    const deleteButtons = document.querySelectorAll('.delete-appointment');
    const addPatientBtn = document.getElementById('add-patient-btn');

    modifyButtons.forEach(button => {
        button.addEventListener('click', function() {
            const appointmentId = this.getAttribute('data-id');
            modifyAppointment(appointmentId);
        });
    });

    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const appointmentId = this.getAttribute('data-id');
            deleteAppointment(appointmentId);
        });
    });

    if (addPatientBtn) {
        addPatientBtn.addEventListener('click', addPatient);
    }

    // Initially load patients list
    loadPatientsList();
    // Add this to the bottom of your doctor-dashboard.php file or in a separate doctor-dashboard.js file
document.addEventListener('DOMContentLoaded', function() {
    // Modal handling
    const modal = document.getElementById('appointment-modal');
    const closeModal = () => modal.style.display = 'none';
    const openModal = () => modal.style.display = 'block';

    // Add new appointment
    document.getElementById('add-appointment-btn').addEventListener('click', function() {
        document.getElementById('appointment_id').value = '';
        document.getElementById('appointment-form').reset();
        openModal();
    });

    // Edit appointment
    document.querySelectorAll('.edit-appointment').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const patient = this.getAttribute('data-patient');
            const date = this.getAttribute('data-date');
            const time = this.getAttribute('data-time');
            const status = this.getAttribute('data-status');

            document.getElementById('appointment_id').value = id;
            document.getElementById('patient_name').value = patient;
            document.getElementById('appointment_date').value = date;
            document.getElementById('appointment_time').value = time;
            document.getElementById('appointment_status').value = status;

            openModal();
        });
    });

    // Complete appointment
    document.querySelectorAll('.complete-appointment').forEach(button => {
        button.addEventListener('click', async function() {
            const id = this.getAttribute('data-id');
            if (confirm('Mark this appointment as completed?')) {
                try {
                    const response = await fetch('update_appointment_status.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            appointment_id: id,
                            status: 'completed'
                        })
                    });
                    
                    if (response.ok) {
                        window.location.reload();
                    } else {
                        alert('Error updating appointment status');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Error updating appointment status');
                }
            }
        });
    });

    // Delete appointment
    document.querySelectorAll('.delete-appointment').forEach(button => {
        button.addEventListener('click', async function() {
            const id = this.getAttribute('data-id');
            if (confirm('Are you sure you want to delete this appointment?')) {
                try {
                    const response = await fetch('delete_appointment.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            appointment_id: id
                        })
                    });
                    
                    if (response.ok) {
                        window.location.reload();
                    } else {
                        alert('Error deleting appointment');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Error deleting appointment');
                }
            }
        });
    });

    // Handle appointment form submission
    document.getElementById('appointment-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const id = formData.get('appointment_id');
        
        try {
            const response = await fetch(id ? 'update_appointment.php' : 'add_appointment.php', {
                method: 'POST',
                body: formData
            });
            
            if (response.ok) {
                window.location.reload();
            } else {
                alert('Error saving appointment');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error saving appointment');
        }
    });

    // Handle profile form submission
    document.getElementById('profile-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        try {
            const response = await fetch('update_doctor_profile.php', {
                method: 'POST',
                body: formData
            });
            
            if (response.ok) {
                alert('Profile updated successfully');
            } else {
                alert('Error updating profile');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error updating profile');
        }
    });
});
});