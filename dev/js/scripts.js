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

// Debug — set to false to silence logs
const DEBUG = true;

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

// Scroll reveals: page-builder blocks ([data-loop]) fade in on first view.
// batch() staggers blocks that enter the viewport together; clearProps
// removes the inline transform afterwards so sticky/positioned children
// aren't affected.
function initReveals() {
    if (reducedMotion) return;

    const blocks = gsap.utils.toArray("[data-loop]");
    if (!blocks.length) return;

    gsap.set(blocks, { autoAlpha: 0, y: 32 });

    ScrollTrigger.batch(blocks, {
        start: "top 85%",
        once: true,
        onEnter: (batch) =>
            gsap.to(batch, {
                autoAlpha: 1,
                y: 0,
                duration: 0.7,
                ease: "power2.out",
                stagger: 0.12,
                clearProps: "all",
            }),
    });
}

function initGsap() {
    // Make GSAP available globally for components (ScrollTrigger registered at import)
    window.gsap = gsap;
    window.dispatchEvent(new CustomEvent("gsap:ready", { detail: { gsap } }));
}

function initVenoBox() {
    if (!document.querySelector(".venobox")) return;
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
    safeInit("VenoBox", initVenoBox);
    safeInit("Video Players", initVideoPlayers);

    showCredits();
    log("✨ Application ready!");
});
