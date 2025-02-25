// Smooth Scroll
const links = document.querySelectorAll('[href^="#"]');

for (const link of links) {
    link.addEventListener('click', smoothFn);
}

function smoothFn(e) {
    e.preventDefault();
    const href = this.getAttribute('href');
    const targetId = href.substring(1); // Remove the '#' from the href to match data-id
    const targetElement = document.querySelector(`[id="${targetId}"], [data-id="${targetId}"]`);

    if (targetElement) {
        let headerHeight = document.querySelector('.header').clientHeight;
        const offsetTop = targetElement.offsetTop - headerHeight;
        scroll({
            top: offsetTop,
            behavior: 'smooth'
        });
    }
}