document.addEventListener('DOMContentLoaded', () => {
    // Smooth scrolling for navigation links within the same page
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            document.querySelector(this.getAttribute('href')).scrollIntoView({
                behavior: 'smooth'
            });
        });
    });

    // Navigate to the signup page when "Get Started" is clicked
    const getStartedButton = document.querySelector('.btn-get-started');
    if (getStartedButton) {
        getStartedButton.addEventListener('click', (e) => {
            e.preventDefault(); // Prevent default anchor behavior
            window.location.href = '../view/SignUp.php'; // Redirect to the signup page
        });
    }

    // Optional: Add scroll animation for sections
    const observerOptions = {
        root: null,
        rootMargin: '0px',
        threshold: 0.1
    };

    const fadeInObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in');
            }
        });
    }, observerOptions);

    // Add fade-in effect to sections
    document.querySelectorAll('.about-section, .services-section').forEach(section => {
        fadeInObserver.observe(section);
    });
});
