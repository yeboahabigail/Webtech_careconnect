// Wait for the DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Handle form submissions
    document.querySelectorAll('.book-bed-form').forEach(form => {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const hospitalId = this.querySelector('input[name="hospital_id"]').value;
            const submitButton = this.querySelector('button[type="submit"]');
            
            if (confirm('Are you sure you want to book a bed at this hospital?')) {
                try {
                    submitButton.disabled = true;
                    
                    const formData = new FormData(this);
                    const response = await fetch(this.action, {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        alert('Bed booked successfully!');
                        window.location.href = data.redirect;
                    } else {
                        alert(data.message || 'An error occurred while booking the bed.');
                        submitButton.disabled = false;
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('An error occurred while booking the bed. Please try again.');
                    submitButton.disabled = false;
                }
            }
        });
    });
});