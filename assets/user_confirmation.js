document.addEventListener("DOMContentLoaded", function () {
    // Fetch appointment details when the page loads
    fetch('book_appointment.php', {
        method: 'POST',
        body: new URLSearchParams({
            // doctor_id: '<?php echo $_POST['doctor_id']; ?>',
            // appointment_date: '<?php echo $_POST['appointment_date']; ?>',
            // appointment_time: '<?php echo $_POST['appointment_time']; ?>',
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update the page with the appointment details
            document.getElementById('user-name').textContent = data.appointment.user_name;
            document.getElementById('doctor-name').textContent = data.appointment.doctor_name;
            document.getElementById('appointment-date').textContent = data.appointment.appointment_date;
            document.getElementById('appointment-time').textContent = data.appointment.appointment_time;
        } else {
            alert(data.error); // Show error message if any
        }
    })
    .catch(error => {
        console.error('Error fetching appointment data:', error);
    });
});

function proceedToBeds() {
    window.location.href = '../beds/availableBeds.php'; // Redirect to available beds page
}

function declineBeds() {
    alert('You chose not to view the available beds.');
}

function logoutUser() {
    window.location.href = '../logout.php'; // Redirect to logout page
}
