// =============================================================================
// MAIN APPLICATION ENTRY POINT
// =============================================================================

import collapse from "@alpinejs/collapse";
import Alpine from "alpinejs";
import Lenis from "lenis";
import Swiper from "swiper";
import {
    A11y,
    Autoplay,
    EffectFade,
    Mousewheel,
    Navigation,
    Pagination
} from "swiper/modules";
import { PlyrLayout, VidstackPlayer } from "vidstack/global/player";
// import { VidstackPlayer, VidstackPlayerLayout } from 'vidstack/global/player';
import { SmoothScroll } from "./custom/custom";

// =============================================================================
// DEBUG CONFIGURATION
// =============================================================================

// Set to false to disable debug messages
const DEBUG = true;

function log(...args) {
    if (DEBUG) console.log(...args);
}

function warn(...args) {
    if (DEBUG) console.warn(...args);
}

// Always show errors
function error(...args) {
    console.error(...args);
}

// =============================================================================
// UTILITIES
// =============================================================================

function ready(callback) {
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", callback);
    } else {
        callback();
    }
}

function safeInit(name, initFunction) {
    try {
        initFunction();
        log(`✅ ${name} initialized`);
    } catch (err) {
        error(`❌ Failed to initialize ${name}:`, err);
    }
}

// =============================================================================
// INITIALIZATION FUNCTIONS
// =============================================================================

function initAlpine() {
	Alpine.plugin(collapse);
    window.Alpine = Alpine.start();
}

function initLenis() {
    new Lenis({ autoRaf: true });
}

function initCustom() {
    SmoothScroll();
}

async function initVideoPlayers() {
    const players = document.querySelectorAll(".player");

    for (const el of players) {
        const { src, title, poster } = el.dataset;
        if (!src) continue;

        try {
            await VidstackPlayer.create({
                layout: new PlyrLayout(),
                target: el,
                src: src,
                poster: poster,
            });
            log(`🎥 Player initialized: ${title || "Untitled"}`);
        } catch (err) {
            error(`Failed to initialize player:`, err);
        }
    }
}

function initSwiper() {
    Swiper.use([Navigation, Pagination, Autoplay, EffectFade, Mousewheel, A11y]);

    // Homepage slider
    if (document.querySelector(".homepage-slider")) {
        new Swiper(".slider", {
            spaceBetween: 24,
            centerInsufficientSlides: true,
            breakpoints: {
                0: {
                    slidesPerView: 1,
                },
                576: {
                    slidesPerView: 2,
                },
                1200: {
                    slidesPerView: 3,
                },
            },
            navigation: {
                nextEl: ".slider-nav.next",
                prevEl: ".slider-nav.prev",
            },
        });
    }
}

function showCredits() {
    const art =
        "****97\n\r****22****71\n\r****22****71\n\r****97\n\r****97\n\r****4******12****6****************9*********15**********6\n\r******************8****5*****************6***************9****************3\n\r********6*****7****16*****5*****9*****5*****10***** \n\r*****11*****5****14*****6*****11*****3****13****1\n\r****13****5****13*****7*********************3****14****\n\r****13****5****11*****9****20****14****\n\r****13****5****10****11****20****14****\n\r1*****9*****6****8*****13****11****4****14****\n\r3****************7****6***************6******6*****5****14****\n\r6**********10****5*****************7*************7****14****\n\r";

    const decompressed = art.replace(/(\d+)/g, (match, number) =>
        " ".repeat(number),
    );
    console.log("🎨 A DIGITAL PROJECT BY");
    console.log(
        decompressed
            .replace(/\\n\\r/g, "\n")
            .replace(/\\n/g, "\n")
            .replace(/\\r/g, ""),
    );
}

// =============================================================================
// MAIN INITIALIZATION
// =============================================================================

ready(async () => {
    log("🚀 Starting application...");

    // Initialize synchronous modules
    safeInit("Alpine", initAlpine);
    safeInit("Lenis", initLenis);
    safeInit("Swiper", initSwiper);
    safeInit("Custom Scripts", initCustom);

    // Initialize async modules
    try {
        await initVideoPlayers();
        log("✅ Video players initialized");
    } catch (err) {
        error("❌ Failed to initialize video players:", err);
    }

    showCredits();
    log("✨ Application ready!");
});
