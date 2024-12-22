document.getElementById('loginForm').addEventListener('submit', function(event) {
    event.preventDefault();
    
    const errorMsg = document.getElementById('errorMsg');
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;

    // Basic validation
    if (!email || !password) {
        errorMsg.textContent = 'Please enter both email and password';
        errorMsg.style.display = 'block';
        return;
    }

    // Create form data
    const formData = new FormData();
    formData.append('email', email);
    formData.append('password', password);

    // Show loading state
    errorMsg.textContent = 'Logging in...';
    errorMsg.style.display = 'block';

    // Send the login request
    fetch('../actions/user_login.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        console.log('Login response:', data); // Debug log
        
        if (data.success) {
            errorMsg.style.display = 'none';
            console.log(data.redirect)
            // Redirect to the appropriate dashboard
            window.location.href = data.redirect;
        } else {
            errorMsg.textContent = data.message;
            errorMsg.style.display = 'block';
        }
    })
    .catch(error => {
        console.error('Login error:', error);
        errorMsg.textContent = 'Invalid password or email. Please try again.';
        errorMsg.style.display = 'block';
    });
});
