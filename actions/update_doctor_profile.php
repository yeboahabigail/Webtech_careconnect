// Create update_doctor_profile.php
<?php
session_start();
require_once '../db/config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

$user_id = $_SESSION['user_id'];
$name = $_POST['name'];
$email = $_POST['email'];
$phone = $_POST['phone'];
$specialty = $_POST['specialty'];
$address = $_POST['address'];

try {
    $stmt = $conn->prepare("UPDATE doctors d 
                           JOIN doctor_user du ON d.doctor_id = du.doctor_id
                           SET d.name = ?, d.email = ?, d.phone = ?, 
                               d.specialty = ?, d.address = ?
                           WHERE du.user_id = ?");
    $stmt->bind_param("sssssi", $name, $email, $phone, $specialty, $address, $user_id);
    
    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode(['message' => 'Profile updated successfully']);
    } else {
        throw new Exception($stmt->error);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>