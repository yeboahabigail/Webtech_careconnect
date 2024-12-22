<?php
include('config.php');

// Admin credentials
$admin_email = 'admin@hospital.com';
$admin_password = 'password'; // This will be the password you can use to login
$admin_role = 'admin';
$admin_fname = 'Abigail';
$admin_lname = 'Yeboah';

// Hash the password
$hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);

// Check if admin already exists
$check_query = $conn->prepare("SELECT * FROM users WHERE email = ?");
$check_query->bind_param("s", $admin_email);
$check_query->execute();
$result = $check_query->get_result();

if ($result->num_rows > 0) {
    // Update existing admin
    $update_query = $conn->prepare("UPDATE users SET password = ?, role = ?, fname = ?, lname = ? WHERE email = ?");
    $update_query->bind_param("sssss", $hashed_password, $admin_role, $admin_fname, $admin_lname, $admin_email);
    
    if ($update_query->execute()) {
        echo "Admin user updated successfully!\n";
    } else {
        echo "Error updating admin user: " . $conn->error . "\n";
    }
} else {
    // Create new admin
    $insert_query = $conn->prepare("INSERT INTO users (email, password, role, fname, lname) VALUES (?, ?, ?, ?, ?)");
    $insert_query->bind_param("sssss", $admin_email, $hashed_password, $admin_role, $admin_fname, $admin_lname);
    
    if ($insert_query->execute()) {
        echo "Admin user created successfully!\n";
    } else {
        echo "Error creating admin user: " . $conn->error . "\n";
    }
}

echo "\nAdmin account credentials:\n";
echo "Email: " . $admin_email . "\n";
echo "Password: " . $admin_password . "\n";

$conn->close();
?>
