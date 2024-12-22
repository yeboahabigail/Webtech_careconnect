<?php
session_start();
include('db.php'); 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $appointment_id = $_POST['appointment_id'];
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];

    // Update appointment date and time
    $update_query = "UPDATE appointments SET appointment_date = '$appointment_date', appointment_time = '$appointment_time' 
                     WHERE appointment_id = '$appointment_id'";
    if (mysqli_query($conn, $update_query)) {
        echo "Appointment rescheduled successfully!";
    } else {
        echo "Error rescheduling appointment: " . mysqli_error($conn);
    }
} else {
    $appointment_id = $_GET['appointment_id'];
    $query = "SELECT * FROM appointments WHERE appointment_id = '$appointment_id'";
    $result = mysqli_query($conn, $query);
    $appointment = mysqli_fetch_assoc($result);
?>

<form action="edit_appointment.php" method="POST">
    <input type="hidden" name="appointment_id" value="<?php echo $appointment['appointment_id']; ?>">
    <label for="appointment-date">Select New Date:</label>
    <input type="date" name="appointment_date" value="<?php echo $appointment['appointment_date']; ?>" required>
    <label for="appointment-time">Select New Time:</label>
    <input type="time" name="appointment_time" value="<?php echo $appointment['appointment_time']; ?>" required>
    <button type="submit">Reschedule</button>
</form>

<?php } ?>


