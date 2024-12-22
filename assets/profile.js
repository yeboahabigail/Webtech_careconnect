// Profile update functionality
document.addEventListener('DOMContentLoaded', function() {
    const editProfileModal = document.getElementById('editProfileModal');
    const editProfileBtn = document.getElementById('edit-profile-btn');
    const cancelEditBtn = document.getElementById('cancelEdit');
    const editProfileForm = document.getElementById('editProfileForm');

    // Show modal
    editProfileBtn.addEventListener('click', function() {
        editProfileModal.style.display = 'flex';
    });

    // Hide modal
    cancelEditBtn.addEventListener('click', function() {
        editProfileModal.style.display = 'none';
    });

    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target === editProfileModal) {
            editProfileModal.style.display = 'none';
        }
    });

    // Handle form submission
    editProfileForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(editProfileForm);
        
        fetch('../actions/update_profile.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Profile updated successfully!');
                window.location.reload();
            } else {
                alert(data.message || 'Failed to update profile');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while updating your profile');
        });
    });
});
