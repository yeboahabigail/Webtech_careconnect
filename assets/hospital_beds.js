// document.addEventListener('DOMContentLoaded', function() {
//      // Store the selected hospital globally
//      let selectedHospital = null;
//     // Smooth scrolling when selecting a hospital
//     const hospitalSelect = document.getElementById('hospital_select');
//     if (hospitalSelect) {
//         hospitalSelect.addEventListener('change', function() {
//             if (this.value) {
//                 window.scrollTo({ 
//                     top: document.body.scrollHeight, 
//                     behavior: 'smooth' 
//                 });
//             }
//         });
//     }

//     // Add hover effects to bed cards
//     const bedCards = document.querySelectorAll('.bed-card');
//     bedCards.forEach(card => {
//         card.addEventListener('mouseenter', function() {
//             this.querySelector('.bed-icon').classList.add('pulse');
//         });
        
//         card.addEventListener('mouseleave', function() {
//             this.querySelector('.bed-icon').classList.remove('pulse');
//         });
//     });

//     // Animate stats numbers
//     const statsNumbers = document.querySelectorAll('.stats-number');
//     statsNumbers.forEach(number => {
//         const finalValue = parseInt(number.textContent);
//         animateValue(number, 0, finalValue, 1000);
//     });
// });

// function animateValue(obj, start, end, duration) {
//     if (start === end) return;
//     const range = end - start;
//     const increment = end > start ? 1 : -1;
//     const stepTime = Math.abs(Math.floor(duration / range));
//     let current = start;
//     const timer = setInterval(function() {
//         current += increment;
//         obj.textContent = current;
//         if (current === end) {
//             clearInterval(timer);
//         }
//     }, stepTime);
// }

// function bookBed(hospitalId, bedId) {
//     if (!confirm('Are you sure you want to book this bed?')) {
//         return;
//     }

//     const formData = new FormData();
//     formData.append('hospital_id', hospitalId);
//     formData.append('bed_id', bedId);

//     fetch('../actions/book_bed.php', {
//         method: 'POST',
//         body: formData
//     })
//     .then(response => response.json())
//     .then(data => {
//         if (data.success) {
//             alert('Bed booked successfully!');
//             // Refresh the page to update bed availability
//             window.location.reload();
//         } else {
//             alert(data.message || 'Failed to book bed. Please try again.');
//         }
//     })
//     .catch(error => {
//         console.error('Error:', error);
//         alert('An error occurred. Please try again.');
//     });
// }
