<?php
session_start();
require_once('../db/config.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signup - CareConnect</title>
    <link rel="stylesheet" href="../assets/Signup.css">
    <style>
        .popup-container {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: #fff;
            padding: 20px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.5);
            border-radius: 10px;
            text-align: center;
            z-index: 1000;
        }
        .popup-container h2 {
            margin: 0;
            color: blue;
        }
        .popup-container p {
            margin: 10px 0;
        }
        .popup-container a {
            display: inline-block;
            margin-top: 10px;
            padding: 10px 20px;
            background-color: #28a745;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
        }
        .popup-container a:hover {
            background-color: #218838;
        }
    </style>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background: url('../img/signup.jpg') no-repeat center center fixed; background-size: cover; height: 100vh; display: flex; justify-content: center; align-items: center;">
    <div class="signup-container">
        <h1>Sign Up</h1>
        <form id="signup-form" method="POST" action="">
            <label for="firstname">First Name:</label>
            <input type="text" id="firstname" name="firstname" required>
            
            <label for="lastname">Last Name:</label>
            <input type="text" id="lastname" name="lastname" required>
            
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
            
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
            
            <label for="confirm-password">Confirm Password:</label>
            <input type="password" id="confirm-password" name="confirm-password" required>

            <div>
                <input type="checkbox" id="togglePassword" onclick="togglePasswordVisibility()">
                <label for="togglePassword">Show Passwords</label>
            </div>
            
            <button type="submit">Sign Up</button>
        </form>
        <p>Already have an account? <a href="Login.php">Log In</a></p>
        
        <div class="popup-container" id="success-popup">
            <h2> Hurray! </h2>
            <p>You have successfully signed up!</p>
            <p>Move to the login page now:</p>
            <a href="Login.php">Go to Login</a>
        </div>
        
        <?php
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $firstname = trim($_POST['firstname']);
            $lastname = trim($_POST['lastname']);
            $email = trim($_POST['email']);
            $password = trim($_POST['password']);
            $confirmPassword = trim($_POST['confirm-password']);
            
            // Validate names
            $namePattern = '/^[A-Z][a-zA-Z\s]*$/';
            $emailPattern = '/^[a-zA-Z][a-zA-Z0-9._%+-]*@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';
            $errors = [];

            if (!preg_match($namePattern, $firstname)) {
                $errors[] = "First name must start with a capital letter and contain only letters.";
            }
            if (!preg_match($namePattern, $lastname)) {
                $errors[] = "Last name must start with a capital letter and contain only letters.";
            }
            if (!preg_match($emailPattern, $email)) {
                $errors[] = "Email must start with a letter and be in a valid format.";
            }

            // Validate password
            $passwordPattern = '/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/';
            if (!preg_match($passwordPattern, $password)) {
                $errors[] = "Password must be at least 8 characters long and include uppercase, lowercase, number, and special character.";
            }
            if ($password !== $confirmPassword) {
                $errors[] = "Passwords do not match.";
            }

            // Check if email already exists
            // Check if email already exists
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        $errors[] = "Email already exists. Please use a different email.";
    }

    // Display errors or proceed with insertion
    if (!empty($errors)) {
        foreach ($errors as $error) {
            echo "<p style='color: red;'>$error</p>";
        }
    } else {
        try {
            // Hash the password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user into database with default role 'user'
            $stmt = $conn->prepare("INSERT INTO users (fname, lname, email, password, role) VALUES (?, ?, ?, ?, 'user')");
            $stmt->bind_param("ssss", $firstname, $lastname, $email, $hashedPassword);
            
            if ($stmt->execute()) {
                echo "<script>document.getElementById('success-popup').style.display = 'block';</script>";
            } else {
                echo "<p style='color: red;'>Error creating account. Please try again.</p>";
            }
        } catch(Exception $e) {
            echo "<p style='color: red;'>Error creating account: " . $e->getMessage() . "</p>";
        }
    }
}
        ?>
    </div>
    
    <script>
        function togglePasswordVisibility() {
            const passwordField = document.getElementById('password');
            const confirmPasswordField = document.getElementById('confirm-password');
            const isChecked = document.getElementById('togglePassword').checked;
            passwordField.type = isChecked ? 'text' : 'password';
            confirmPasswordField.type = isChecked ? 'text' : 'password';
        }
    </script>
</body>
</html>
