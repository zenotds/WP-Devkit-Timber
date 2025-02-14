// Bootstrap //
import { Dropdown, Collapse, Modal, Tab, Offcanvas, } from 'bootstrap';

// Hover Intent //
import './custom/hoverintent';

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

// Plyr //
import Plyr from 'plyr';

// Video Player //
const video_player = Plyr.setup('.video-player', {
    controls: [
        'play-large',
        'play',
        'progress',
        'current-time',
        'mute',
        'volume',
        'fullscreen'
    ],
    youtube: {
        rel: 0,
        showinfo: 0,
        modestbranding: 1
    },
    vimeo: {
        byline: false,
        portrait: false,
        title: false,
        speed: true,
        transparent: false
    }
});

// Swiper //
import Swiper from 'swiper';
import { Navigation, Pagination, Autoplay } from 'swiper/modules';
Swiper.use([Navigation, Pagination, Autoplay]);

// Cards slider //
const cardsSlider = new Swiper('.cards-slider', {
    spaceBetween: 24,
    centerInsufficientSlides: true,
    breakpoints: {
        0: {
            slidesPerView: 1
        },
        576: {
            slidesPerView: 2
        },
        1200: {
            slidesPerView: 3
        },
    },
    navigation: {
        nextEl: ".cards-nav.next",
        prevEl: ".cards-nav.prev",
    }
});