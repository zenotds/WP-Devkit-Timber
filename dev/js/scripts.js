// Main application entry point

import collapse from "@alpinejs/collapse";
import focus from "@alpinejs/focus";
import Alpine from "alpinejs";
import { CountUp } from "countup.js";
import gsap from "gsap";
import { ScrollTrigger } from "gsap/ScrollTrigger";
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
import VenoBox from "venobox/src/venobox.esm.js";
import Vlitejs from "vlitejs";
import VlitejsMobile from "vlitejs/plugins/mobile";
import VlitejsVolume from "vlitejs/plugins/volume-bar";
import VlitejsVimeo from "vlitejs/providers/vimeo";
import VlitejsYoutube from "vlitejs/providers/youtube";
import { SmoothScroll } from "./custom/custom";

Vlitejs.registerProvider("youtube", VlitejsYoutube);
Vlitejs.registerProvider("vimeo", VlitejsVimeo);
Vlitejs.registerPlugin("volume", VlitejsVolume);
Vlitejs.registerPlugin("mobile", VlitejsMobile);

gsap.registerPlugin(ScrollTrigger);

const reducedMotion = window.matchMedia(
    "(prefers-reduced-motion: reduce)",
).matches;

// Shared Lenis instance — null when reduced motion is on or init failed
let lenis = null;

// Debug — iniettato da esbuild: true in dev, false in produzione
const DEBUG = process.env.NODE_ENV !== "production";

function log(...args) {
    if (DEBUG) console.log(...args);
}

function warn(...args) {
    if (DEBUG) console.warn(...args);
}

function error(...args) {
    console.error(...args);
}

// Utilities

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

// vLite's YT/Vimeo providers mount by element id, so each needs a unique one
let playerSeq = 0;
function ensurePlayerId(el) {
    if (!el.id) el.id = `vplayer-${++playerSeq}`;
}

// Init functions

function initAlpine() {
    Alpine.plugin(collapse);
    Alpine.plugin(focus);
    window.Alpine = Alpine.start();
}

// Lenis driven by GSAP's ticker (single rAF loop) and synced with
// ScrollTrigger so scroll-driven animations follow the smoothed scroll
function initLenis() {
    if (reducedMotion) return;
    lenis = new Lenis();
    lenis.on("scroll", ScrollTrigger.update);
    gsap.ticker.add((time) => lenis.raf(time * 1000));
    gsap.ticker.lagSmoothing(0);
}

// Anchor links scroll through Lenis so the easing matches the rest of the
// page; falls back to the native handler when Lenis is off (reduced motion)
function initAnchors() {
    if (!lenis) {
        SmoothScroll();
        return;
    }

    const header = document.querySelector(".header");

    document.addEventListener("click", (event) => {
        const link = event.target.closest('a[href^="#"]');
        if (!link) return;

        const href = link.getAttribute("href");
        if (!href || href === "#" || href.startsWith("#!")) return;

        const target = document.querySelector(href);
        if (!target) return;

        event.preventDefault();
        const offset = () => -(header?.offsetHeight ?? 0);
        lenis.scrollTo(target, {
            offset: offset(),
            // Re-align once to counter layout shifts during the scroll
            // (e.g. lazy-loaded forms growing above the target)
            onComplete: () => {
                const drift = target.getBoundingClientRect().top + offset();
                if (Math.abs(drift) > 4) {
                    lenis.scrollTo(target, { offset: offset() });
                }
            },
        });
    });
}

// Scroll reveal dei [data-loop]. Trigger per-elemento e non ScrollTrigger.batch:
// il batch non rivela i blocchi già oltre il viewport al reload, lasciandoli col
// transform inline (un full-bleed così sfora in orizzontale). clearProps rimuove
// il transform inline a fine animazione per non disturbare figli sticky/positioned.
function initReveals() {
    if (reducedMotion) return;

    const blocks = gsap.utils.toArray("[data-loop]");
    if (!blocks.length) return;

    for (const el of blocks) {
        // Già in zona reveal all'init (above the fold): resta dipinto, niente flash nascondi-e-rianima
        if (el.getBoundingClientRect().top < window.innerHeight * 0.85) continue;

        gsap.set(el, { autoAlpha: 0, y: 32 });

        ScrollTrigger.create({
            trigger: el,
            start: "top 85%",
            once: true,
            onEnter: () =>
                gsap.to(el, {
                    autoAlpha: 1,
                    y: 0,
                    duration: 0.7,
                    ease: "power2.out",
                    clearProps: "all",
                }),
        });
    }
}

// Cover parallax: full-bleed header images ([data-parallax]) drift slower
// than the scroll, scrubbed through the Lenis-synced ScrollTrigger. The
// image is scaled up so the drift never reveals its edges; the wrapper
// carries overflow-clip to contain it.
function initParallax() {
    if (reducedMotion) return;

    for (const wrap of document.querySelectorAll("[data-parallax]")) {
        // picture for images, .visual-video for vLite players
        const media = wrap.querySelector("picture, .visual-video");
        if (!media) continue;

        gsap.fromTo(
            media,
            { yPercent: -8, scale: 1.2 },
            {
                yPercent: 8,
                scale: 1.2,
                ease: "none",
                scrollTrigger: {
                    trigger: wrap,
                    start: "top bottom",
                    end: "bottom top",
                    scrub: true,
                },
            },
        );
    }
}

function initGsap() {
    // Make GSAP available globally for components (ScrollTrigger registered at import)
    window.gsap = gsap;
    window.dispatchEvent(new CustomEvent("gsap:ready", { detail: { gsap } }));
}

function initVenoBox() {
    if (!document.querySelector(".venobox")) return;

    // Toglie .venobox dalle eventuali slide clonate da Swiper (loop) per non duplicare le voci in lightbox
    for (const el of document.querySelectorAll(".swiper-slide-duplicate .venobox")) {
        el.classList.remove("venobox");
    }

    new VenoBox({
        selector: ".venobox",
        spinner: "wave",
        titleattr: "data-title",
        titlePosition: "bottom",
        autoplay: true,
    });
}

// vLitejs players — see macros mp4() and embed() for the expected markup
function initVideoPlayers() {
    const players = document.querySelectorAll(".player:not([data-visual])");

    for (const el of players) {
        const { title, provider, poster } = el.dataset;

        const config = {
            options: {
                controls: true,
                playsinline: true,
                poster: poster || null,
            },
            plugins: ["volume", "mobile"],
        };
        if (provider) {
            ensurePlayerId(el);
            config.provider = provider;
        }

        try {
            new Vlitejs(el, config);
            log(`🎥 Player initialized: ${title || "Untitled"}`);
        } catch (err) {
            error(`Failed to initialize player:`, err);
        }
    }
}

// Video di sfondo [data-visual]: autoplay muto in loop, senza controlli
function initVisualPlayers() {
    for (const el of document.querySelectorAll(".player[data-visual]")) {
        const { title, provider, poster } = el.dataset;

        const config = {
            options: {
                autoplay: true,
                muted: true,
                loop: true,
                playsinline: true,
                controls: false,
                poster: poster || null,
            },
            onReady: () => log(`🎥 Visual player initialized: ${title || "Untitled"}`),
        };
        if (provider) {
            ensurePlayerId(el);
            config.provider = provider;
        }

        try {
            new Vlitejs(el, config);
        } catch (err) {
            error(`Failed to initialize visual player:`, err);
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
                640: {
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

    // Gallery dei moduli (Columns/Media): data-per-view (default 1) = slide visibili
    // da desktop, >1 = carosello responsive; watchOverflow disattiva swiper/paginazione
    // quando entrano tutte
    for (const el of document.querySelectorAll(".media-slider")) {
        const per = parseInt(el.dataset.perView || "1", 10);
        const slides = el.querySelectorAll(".swiper-slide").length;
        const prev = el.querySelector(".media-prev");
        const next = el.querySelector(".media-next");
        new Swiper(el, {
            slidesPerView: 1,
            spaceBetween: per > 1 ? 24 : 0,
            // Loop solo con più slide di quelle visibili: col loop watchOverflow non blocca
            // e la paginazione resta anche quando entrano tutte
            loop: slides > per,
            watchOverflow: true,
            pagination: {
                el: el.querySelector(".swiper-pagination"),
                clickable: true,
            },
            navigation: prev && next ? { prevEl: prev, nextEl: next } : false,
            breakpoints:
                per > 1
                    ? { 640: { slidesPerView: 2 }, 1024: { slidesPerView: per } }
                    : undefined,
        });
    }

    // Carosello card/post (modulo Posts e simili): 1/2/3 per breakpoint, centra se poche,
    // no loop. watchSlidesProgress: la classe .posts-slider (dev/css/plugins/swiper.css)
    // nasconde le slide senza .swiper-slide-visible, assegnata solo con l'opzione attiva.
    for (const el of document.querySelectorAll(".posts-slider")) {
        new Swiper(el, {
            slidesPerView: 1,
            spaceBetween: 24,
            centerInsufficientSlides: true,
            watchOverflow: true,
            watchSlidesProgress: true,
            pagination: {
                el: el.querySelector(".swiper-pagination"),
                clickable: true,
            },
            breakpoints: {
                640: { slidesPerView: 2 },
                1280: { slidesPerView: 3 },
            },
        });
    }
}

// Apre il popup (partial/popup.twig) dagli elementi .popup-trigger o da un link con
// href="#popup" (utile per i campi ACF link, es. bottoni dei moduli).
function initPopupTriggers() {
    document.addEventListener("click", (e) => {
        const trigger = e.target.closest('.popup-trigger, a[href$="#popup"]');
        if (!trigger) return;
        e.preventDefault();
        window.dispatchEvent(new CustomEvent("popup"));
    });
}

// Redirect alla thank-you page dopo l'invio CF7: legge data-typ dal wrapper .form
// (campo ACF "typ" accanto al campo form)
function initFormRedirects() {
    document.addEventListener("wpcf7mailsent", (e) => {
        const typ = e.target.closest("[data-typ]")?.dataset.typ;
        if (typ) window.location.assign(typ);
    });
}

// Animate numbers on scroll. Values are free text ("+300", ">5.000 kg"):
// the non-numeric prefix/suffix is preserved, only the digits count up.
function initCountUp() {
    const els = document.querySelectorAll("[data-countup]");
    if (!els.length) return;

    const parse = (raw) => {
        const match = raw.match(/^(\D*)([\d.,\s]*\d)(\D*)$/);
        if (!match) return null;
        const [, prefix, numStr, suffix] = match;
        const end = parseInt(numStr.replace(/[.,\s]/g, ""), 10);
        return Number.isNaN(end) ? null : { prefix, end, suffix };
    };

    const observer = new IntersectionObserver(
        (entries, obs) => {
            for (const entry of entries) {
                if (!entry.isIntersecting) continue;
                const el = entry.target;
                obs.unobserve(el);

                const data = parse(el.dataset.countup);
                if (!data) continue;

                const counter = new CountUp(el, data.end, {
                    prefix: data.prefix,
                    suffix: data.suffix,
                    separator: ".",
                    duration: 2,
                });

                if (counter.error) {
                    error("CountUp error:", counter.error);
                    continue;
                }
                counter.start();
            }
        },
        { threshold: 0.4 },
    );

    for (const el of els) observer.observe(el);
}

function showCredits() {
    const art =
        "****97\n\r****22****71\n\r****22****71\n\r****97\n\r****97\n\r****4******12****6****************9*********15**********6\n\r******************8****5*****************6***************9****************3\n\r********6*****7****16*****5*****9*****5*****10***** \n\r*****11*****5****14*****6*****11*****3****13****1\n\r****13****5****13*****7*********************3****14****\n\r****13****5****11*****9****20****14****\n\r****13****5****10****11****20****14****\n\r1*****9*****6****8*****13****11****4****14****\n\r3****************7****6***************6******6*****5****14****\n\r6**********10****5*****************7*************7****14****\n\r";

    const decompressed = art.replace(/(\d+)/g, (_, number) =>
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

// Boot

ready(() => {
    log("🚀 Starting application...");

    safeInit("Alpine", initAlpine);
    safeInit("Lenis", initLenis);
    safeInit("GSAP", initGsap);
    safeInit("Swiper", initSwiper);
    safeInit("CountUp", initCountUp);
    safeInit("Anchors", initAnchors);
    safeInit("Reveals", initReveals);
    safeInit("Parallax", initParallax);
    safeInit("VenoBox", initVenoBox);
    safeInit("Video Players", initVideoPlayers);
    safeInit("Visual Players", initVisualPlayers);
    safeInit("Popup Triggers", initPopupTriggers);
    safeInit("Form Redirects", initFormRedirects);

    showCredits();
    log("✨ Application ready!");
});
