<?php
session_start();
include('../db/config.php'); // Assuming you have a database connection file

// Fetch user data from session
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE user_id = '$user_id'";
$user_result = mysqli_query($conn, $query);
$user_data = mysqli_fetch_assoc($user_result);

// Fetch appointments
$appointments_query = "SELECT * FROM appointments WHERE user_id = '$user_id'";
$appointments_result = mysqli_query($conn, $appointments_query);

// Fetch doctor recommendations
$doctor_recommendations_query = "SELECT * FROM doctor_recommendations WHERE user_id = '$user_id'";
$doctor_recommendations_result = mysqli_query($conn, $doctor_recommendations_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="user-profile-container">
        <h2>User Profile</h2>
        <p>Name: <?php echo $_SESSION['fname']; ?> <?php echo $_SESSION['lname']; ?></p>
        <p>Email: <?php echo $_SESSION['email']; ?></p>

        <!-- Doctor Recommendations -->
        <h3>Doctor Recommendations</h3>
        <ul>
            <?php
                while ($recommendation = mysqli_fetch_assoc($doctor_recommendations_result)) {
                    echo "<li>{$recommendation['recommendation']}</li>";
                }
            ?>
        </ul>

        <!-- Appointments -->
        <h3>Your Appointments</h3>
        <ul>
            <?php
                while ($appointment = mysqli_fetch_assoc($appointments_result)) {
                    $doctor_id = $appointment['doctor_id'];
                    $doctor_query = "SELECT name FROM doctors WHERE doctor_id = '$doctor_id'";
                    $doctor_result = mysqli_query($conn, $doctor_query);
                    $doctor_data = mysqli_fetch_assoc($doctor_result);
                    $doctor_name = $doctor_data['name'];

                    echo "<li>Doctor: {$doctor_name} | Date: {$appointment['appointment_date']} | Time: {$appointment['appointment_time']} | Status: {$appointment['status']}
                          <a href='edit_appointment.php?appointment_id={$appointment['appointment_id']}'>Reschedule</a> 
                          <a href='cancel_appointment.php?appointment_id={$appointment['appointment_id']}'>Cancel</a></li>";
                }
            ?>
        </ul>

        <!-- Booking Form (Only if no appointment exists) -->
        <?php
            $existing_appointments = mysqli_num_rows($appointments_result);
            if ($existing_appointments == 0) {
                echo "<h3>Book an Appointment</h3>";
                echo "<form action='book_appointment.php' method='POST'>
                        <label for='doctor-select'>Select Doctor:</label>
                        <select name='doctor_id' id='doctor-select'>";
                
                $doctor_query = "SELECT * FROM doctors";
                $doctor_result = mysqli_query($conn, $doctor_query);
                while ($doctor = mysqli_fetch_assoc($doctor_result)) {
                    echo "<option value='{$doctor['doctor_id']}'>{$doctor['name']}</option>";
                }

                echo "  </select>
                        <label for='appointment-date'>Select Date:</label>
                        <input type='date' name='appointment_date' required>
                        <label for='appointment-time'>Select Time:</label>
                        <input type='time' name='appointment_time' required>
                        <button type='submit'>Book Appointment</button>
                    </form>";
            }
        ?>

        <button onclick="window.location.href='edit_profile.php'">Edit Profile</button>
    </div>
</body>
</html>
