document.getElementById('signup-form').addEventListener('submit', function (event) {
    event.preventDefault(); // Prevent form submission

    // Get user details from the form
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value.trim();
    const role = document.querySelector('input[name="role"]:checked').value; // Radio button for role selection

    // Validate inputs
    const emailPattern = /^[a-zA-Z][a-zA-Z0-9._%+-]*@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    const passwordPattern = /^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;

    if (!emailPattern.test(email)) {
        alert('Enter a valid email.');
        return;
    }

    if (!passwordPattern.test(password)) {
        alert('Password must include uppercase, lowercase, a number, and a special character.');
        return;
    }

    // Store user credentials and role
    localStorage.setItem('userEmail', email);
    localStorage.setItem('userPassword', password);
    localStorage.setItem('userRole', role);

    // Show success popup
    alert('Hurray! You are successfully signed up.');

    // Redirect to login page
    window.location.href = 'login.html';
});
