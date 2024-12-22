document.addEventListener("DOMContentLoaded", function () {
    loadAppointments();
  });
  
  function loadAppointments() {
    fetch("actions/fetch_appointments.php")
      .then(response => response.json())
      .then(data => {
        const appointmentsList = document.getElementById("appointmentsList");
        appointmentsList.innerHTML = "";
  
        data.forEach(appointment => {
          const div = document.createElement("div");
          div.className = "appointment";
  
          div.innerHTML = `
            <p><strong>Doctor:</strong> ${appointment.doctor_name}</p>
            <p><strong>Date:</strong> ${appointment.appointment_date}</p>
            <p><strong>Time:</strong> ${appointment.appointment_time}</p>
            <button onclick="editAppointment(${appointment.id})">Edit</button>
            <button onclick="rescheduleAppointment(${appointment.id})">Reschedule</button>
            <button onclick="cancelAppointment(${appointment.id})">Cancel</button>
          `;
          appointmentsList.appendChild(div);
        });
      })
      .catch(error => console.error("Error fetching appointments:", error));
  }
  