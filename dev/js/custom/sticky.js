// // JavaScript (scroll event)
const stickyElement = document.querySelector('.sticky');

window.addEventListener('scroll', () => {
    const rect = stickyElement.getBoundingClientRect();

    // Check if the sticky element is at the top of the viewport (stuck)
    if (rect.top <= 0) {
        stickyElement.classList.add('is-sticky');
    } else {
        stickyElement.classList.remove('is-sticky');
    }
});