<?php
session_start();
include('../db/config.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: Login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch current user data
$query = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <!-- <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet"> -->
    <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"> -->
     <link rel="stylesheet" href="../assets/edit_profile.css">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-md mx-auto bg-white shadow-md rounded-lg p-6">
            <h2 class="text-2xl font-bold mb-6 text-center">
                <i class="fas fa-user-edit text-blue-500 mr-2"></i>Edit Profile
            </h2>
            
            <form id="editProfileForm" method="POST" action="../actions/update_profile.php" enctype="multipart/form-data">
                <!-- First Name -->
                <div class="mb-4">
                    <label for="first_name" class="block text-gray-700 font-bold mb-2">First Name</label>
                    <input type="text" id="first_name" name="fname" 
                           value="<?php echo htmlspecialchars($user_data['first_name']); ?>" 
                           class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500"
                           required>
                </div>

                <!-- Last Name -->
                <div class="mb-4">
                    <label for="last_name" class="block text-gray-700 font-bold mb-2">Last Name</label>
                    <input type="text" id="last_name" name="lname" 
                           value="<?php echo htmlspecialchars($user_data['lname']); ?>" 
                           class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500"
                           required>
                </div>

                <!-- Email -->
                <div class="mb-4">
                    <label for="email" class="block text-gray-700 font-bold mb-2">Email</label>
                    <input type="email" id="email" name="email" 
                           value="<?php echo htmlspecialchars($user_data['email']); ?>" 
                           class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500"
                           required>
                </div>

                <!-- Phone -->
                <!-- <div class="mb-4">
                    <label for="phone" class="block text-gray-700 font-bold mb-2">Phone Number</label>
                    <input type="tel" id="phone" name="phone" 
                           value="<//?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>" 
                           class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500"
                           pattern="[0-9]{10}"
                           title="Please enter a 10-digit phone number">
                </div> -->

                <!-- Address -->
                <!-- <div class="mb-4">
                    <label for="address" class="block text-gray-700 font-bold mb-2">Address</label>
                    <textarea id="address" name="address" 
                              class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500"
                              rows="3"><//?php echo htmlspecialchars($user_data['address'] ?? ''); ?></textarea>
                </div> -->

                <!-- Profile Picture -->
                <!-- <div class="mb-4">
                    <label for="profile_picture" class="block text-gray-700 font-bold mb-2">Profile Picture</label>
                    <input type="file" id="profile_picture" name="profile_picture" 
                           accept="image/*"
                           class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                     -->
                    <!-- Current Profile Picture -->
                    <!-- <//?php if(!empty($user_data['profile_picture'])): ?>
                        <div class="mt-2">
                            <p class="text-sm text-gray-600">Current profile picture:</p>
                            <img src="</?php echo htmlspecialchars($user_data['profile_picture']); ?>" 
                                 alt="Current Profile" 
                                 class="w-32 h-32 object-cover rounded-full mt-2">
                        </div>
                    <//?php endif; ?>
                </div> -->

                <!-- Buttons -->
                <div class="flex justify-between items-center">
                    <button type="submit" 
                            class="bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition duration-300">
                        <i class="fas fa-save mr-2"></i>Save Changes
                    </button>
                    <a href="user_dashboard.php" 
                       class="text-gray-600 hover:text-gray-800 transition duration-300">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/profile.js"></script>
    <script>
    document.getElementById('editProfileForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Create FormData object
        const formData = new FormData(this);

        // Send form data via AJAX
        fetch('../actions/update_profile.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Profile Updated',
                    text: data.message,
                    confirmButtonText: 'OK'
                }).then(() => {
                    window.location.href = 'user_dashboard.php';
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Update Failed',
                    text: data.message,
                    confirmButtonText: 'Try Again'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'System Error',
                text: 'An unexpected error occurred. Please try again.',
                confirmButtonText: 'OK'
            });
        });
    });
    </script>
</body>
</html>