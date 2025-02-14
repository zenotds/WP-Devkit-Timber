let autohide = document.querySelectorAll('.autohide');

// add padding-top to body (if necessary)
// navbar_height = document.querySelector('.navbar').offsetHeight;
// document.body.style.paddingTop = navbar_height + 'px';

if (autohide) {
    autohide.forEach(el => {
        let last_pos = 0;
        let threshold = 50;
        window.addEventListener('scroll', function () {
            let current_pos = window.scrollY;
            if (Math.abs(last_pos - current_pos) > threshold) {
                if (current_pos < last_pos) {
                    el.classList.remove('is-autohide');
                }
                else {
                    el.classList.add('is-autohide');
                }
            }
            last_pos = current_pos;
        });

    });
}