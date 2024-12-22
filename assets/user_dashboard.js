// Function to cancel appointment
function cancelAppointment(appointmentId) {
    if (!confirm('Are you sure you want to cancel this appointment?')) {
        return;
    }

    const formData = new FormData();
    formData.append('appointment_id', appointmentId);

    fetch('../actions/cancel_appoinment.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Appointment cancelled successfully');
            location.reload(); // Reload the page to update the appointments list
        } else {
            alert(data.message || 'Failed to cancel appointment');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while cancelling the appointment');
    });
}

// Function to show section
function showSection(sectionId) {
    // Hide all sections
    document.querySelectorAll('.dashboard-section').forEach(section => {
        section.classList.add('hidden');
    });
    
    // Show the selected section
    document.getElementById(sectionId).classList.remove('hidden');
}

// Edit Profile Modal Functions
function openEditProfileModal() {
    document.getElementById('editProfileModal').style.display = 'block';
}

function closeEditProfileModal() {
    document.getElementById('editProfileModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('editProfileModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}
