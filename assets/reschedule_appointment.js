function editAppointment(id) {
    // Redirect user to the booking form with prefilled details
    fetch(`actions/fetch_appointment.php?id=${id}`)
      .then(response => response.json())
      .then(data => {
        document.getElementById("doctor").value = data.doctor_name;
        document.getElementById("date").value = data.appointment_date;
        document.getElementById("time").value = data.appointment_time;
        document.getElementById("bookingForm").action = "actions/edit_appointment.php";
      });
  }
  
  function cancelAppointment(id) {
    if (confirm("Are you sure you want to cancel this appointment?")) {
      fetch("actions/cancel_appointment.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `id=${id}`
      })
        .then(response => response.text())
        .then(message => {
          alert(message);
          loadAppointments(); // Refresh the appointments list
        });
    }
  }
  
  function rescheduleAppointment(id) {
    // Similar to editAppointment, but you can add custom logic for rescheduling
    editAppointment(id); // Use the same logic for simplicity
  }
  