// Hover Intent
import './custom/hoverintent';

// Smooth Scroll
import './custom/smoothscroll';

// Alpine
import Alpine from 'alpinejs'
window.Alpine = Alpine.start()

// Vidstack
import { PlyrLayout, VidstackPlayer } from 'vidstack/global/player';
// import { VidstackPlayer, VidstackPlayerLayout } from 'vidstack/global/player';

async function initializePlayers() {
    const players = document.querySelectorAll('.player');

    players.forEach(async (el) => {
        const src = el.dataset.src;
        const title = el.dataset.title;
        const poster = el.dataset.poster;
        
        // Skip if no source is provided
        if (!src) return;

        await VidstackPlayer.create({
            layout: new PlyrLayout(),
            // layout: new VidstackPlayerLayout(),
            target: el,
            src: src,
            // title: title,
            poster: poster
        });

        console.log(`Player initialized for ${title}`);
    });
}

// Swiper //
import Swiper from 'swiper';
import { Navigation, Pagination, Autoplay } from 'swiper/modules';
Swiper.use([Navigation, Pagination, Autoplay]);

// Cards slider //
const exampleSlider = new Swiper('.slider', {
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
        nextEl: ".slider-nav.next",
        prevEl: ".slider-nav.prev",
    }
});