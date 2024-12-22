document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(tooltip => {
        new bootstrap.Tooltip(tooltip);
    });

    // Handle bed management form submissions
    const addBedForm = document.querySelector('form[action="../actions/update_beds.php"]');
    if (addBedForm) {
        addBedForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!confirm('Are you sure you want to add these beds?')) {
                return;
            }

            const formData = new FormData(this);
            fetch('../actions/update_beds.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                showNotification('Beds added successfully!', 'success');
                setTimeout(() => window.location.reload(), 1500);
            })
            .catch(error => {
                showNotification('Error adding beds: ' + error, 'error');
            });
        });
    }

    // Handle marking bed as damaged
    // const damageBedForm = document.querySelector('form[action="../actions/update_beds.php"][name="damage"]');
    if (damageBedForm) {
        damageBedForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!confirm('Are you sure you want to mark this bed as damaged?')) {
                return;
            }

            const formData = new FormData(this);
            fetch('../actions/update_beds.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                showNotification('Bed marked as damaged', 'success');
                setTimeout(() => window.location.reload(), 1500);
            })
            .catch(error => {
                showNotification('Error marking bed as damaged: ' + error, 'error');
            });
        });
    }

    // Handle appointment status updates
    function updateAppointmentStatus(appointmentId, newStatus) {
        fetch('../actions/update_appointment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `appointment_id=${appointmentId}&status=${newStatus}`
        })
        .then(response => response.text())
        .then(data => {
            showNotification(`Appointment ${newStatus} successfully`, 'success');
            setTimeout(() => window.location.reload(), 1500);
        })
        .catch(error => {
            showNotification('Error updating appointment: ' + error, 'error');
        });
    }

    // Add click handlers for appointment status buttons
    document.querySelectorAll('.appointment-action').forEach(button => {
        button.addEventListener('click', function() {
            const appointmentId = this.dataset.appointmentId;
            const action = this.dataset.action;
            
            if (confirm(`Are you sure you want to ${action} this appointment?`)) {
                updateAppointmentStatus(appointmentId, action);
            }
        });
    });

    // Notification system
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        
        // Add to notification container or body
        const container = document.querySelector('.notification-container') || document.body;
        container.appendChild(notification);
        
        // Remove after 3 seconds
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }

    // Real-time statistics update
    function updateStatistics() {
        fetch('../actions/get_statistics.php')
        .then(response => response.json())
        .then(data => {
            // Update statistics cards
            if (data.total_users) document.querySelector('#total-users').textContent = data.total_users;
            if (data.total_beds) document.querySelector('#total-beds').textContent = data.total_beds;
            if (data.available_beds) document.querySelector('#available-beds').textContent = data.available_beds;
            if (data.damaged_beds) document.querySelector('#damaged-beds').textContent = data.damaged_beds;
            
            // Update charts if they exist
            if (window.appointmentChart && data.appointment_stats) {
                window.appointmentChart.data.datasets[0].data = [
                    data.appointment_stats.confirmed,
                    data.appointment_stats.cancelled,
                    data.appointment_stats.rescheduled
                ];
                window.appointmentChart.update();
            }
            
            if (window.bedChart && data.bed_stats) {
                window.bedChart.data.datasets[0].data = [
                    data.bed_stats.available,
                    data.bed_stats.occupied,
                    data.bed_stats.damaged
                ];
                window.bedChart.update();
            }
        })
        .catch(error => console.error('Error updating statistics:', error));
    }

    // Update statistics every 30 seconds
    setInterval(updateStatistics, 30000);

    // Add search functionality for appointments
    const searchInput = document.querySelector('#appointment-search');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('table tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    }

    // Add sorting functionality for tables
    document.querySelectorAll('th[data-sort]').forEach(header => {
        header.addEventListener('click', function() {
            const table = this.closest('table');
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            const column = this.cellIndex;
            const ascending = this.dataset.sortDirection === 'asc';
            
            // Sort rows
            rows.sort((a, b) => {
                const aValue = a.cells[column].textContent;
                const bValue = b.cells[column].textContent;
                return ascending ? aValue.localeCompare(bValue) : bValue.localeCompare(aValue);
            });
            
            // Update sort direction
            this.dataset.sortDirection = ascending ? 'desc' : 'asc';
            
            // Clear existing rows and append sorted rows
            rows.forEach(row => tbody.appendChild(row));
        });
    });

    // Add export functionality
    document.querySelector('#export-appointments')?.addEventListener('click', function() {
        const table = document.querySelector('table');
        const rows = Array.from(table.querySelectorAll('tr'));
        
        let csv = [];
        rows.forEach(row => {
            const cells = Array.from(row.querySelectorAll('td, th'));
            const rowData = cells.map(cell => `"${cell.textContent.trim()}"`);
            csv.push(rowData.join(','));
        });
        
        const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'appointments.csv';
        a.click();
        window.URL.revokeObjectURL(url);
    });

    // Add print functionality
    document.querySelector('#print-dashboard')?.addEventListener('click', function() {
        window.print();
    });
});

// Modal handling functions
function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add Appointment';
    document.getElementById('appointmentForm').reset();
    document.getElementById('appointmentId').value = '';
    document.getElementById('appointmentModal').classList.remove('hidden');
}

function openEditModal(id) {
    document.getElementById('modalTitle').textContent = 'Edit Appointment';
    document.getElementById('appointmentId').value = id;
    
    // Fetch appointment details
    fetch(`../actions/book_apointment.php/${id}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('patientSelect').value = data.patient_id;
            document.getElementById('doctorSelect').value = data.doctor_id;
            document.getElementById('appointmentDate').value = data.appointment_date;
            document.getElementById('appointmentTime').value = data.appointment_time;
            document.getElementById('statusSelect').value = data.status;
            document.getElementById('appointmentModal').classList.remove('hidden');
        })
        .catch(error => {
            alert('Error fetching appointment details');
            console.error('Error:', error);
        });
}

function closeModal() {
    document.getElementById('appointmentModal').classList.add('hidden');
}

function closeViewModal() {
    document.getElementById('viewModal').classList.add('hidden');
}

function viewAppointment(id) {
    fetch(`../actions/book_apointment.php/${id}`)
        .then(response => response.json())
        .then(data => {
            const detailsHTML = `
                <p><strong>Patient:</strong> ${data.patient_name}</p>
                <p><strong>Doctor:</strong> Dr. ${data.doctor_name}</p>
                <p><strong>Hospital:</strong> ${data.hospital_name}</p>
                <p><strong>Date:</strong> ${new Date(data.appointment_date).toLocaleDateString()}</p>
                <p><strong>Time:</strong> ${data.appointment_time}</p>
                <p><strong>Status:</strong> ${data.status}</p>
            `;
            document.getElementById('appointmentDetails').innerHTML = detailsHTML;
            document.getElementById('viewModal').classList.remove('hidden');
        })
        .catch(error => {
            alert('Error fetching appointment details');
            console.error('Error:', error);
        });
}

function confirmDelete() {


    window.location.href = "../actions/delete_booking.php";
   
}

// CRUD operations
document.getElementById('appointmentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const id = document.getElementById('appointmentId').value;
    const url = id ? `../actions/book_apointment.php/${id}` : '../actions/book_apointment.php';
    const method = id ? 'PUT' : 'POST';

    fetch(url, {
        method: method,
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeModal();
            window.location.reload(); // Refresh the table
        } else {
            alert(data.message || 'Error saving appointment');
        }
    })
    .catch(error => {
        alert('Error saving appointment');
        console.error('Error:', error);
    });
});

function deleteAppointment(id) {
    fetch(`../actions/book_apointment.php/${id}`, {
        method: 'DELETE'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload(); // Refresh the table
        } else {
            alert(data.message || 'Error deleting appointment');
        }
    })
    .catch(error => {
        alert('Error deleting appointment');
        console.error('Error:', error);
    });
}
