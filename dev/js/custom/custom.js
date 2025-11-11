/**
 * @module CustomScripts
 * @description A collection of reusable vanilla JavaScript modules for common UI patterns.
 * This script provides modern, performant implementations of common web interactions.
 *
 * @author @zenotds
 * @version 2.0
 *
 * @example
 * // Import all modules
 * import { Autohide, HoverIntent, SmoothScroll, Sticky } from './custom/custom.js';
 *
 * // Import specific modules
 * import { SmoothScroll } from './custom/custom.js';
 *
 * // Basic initialization
 * document.addEventListener('DOMContentLoaded', () => {
 *   // Initialize autohide for navigation
 *   Autohide('.main-nav', 80);
 *   
 *   // Enable smooth scrolling for all anchor links
 *   SmoothScroll();
 *   
 *   // Make sidebar sticky with visual feedback
 *   Sticky('.sidebar', 'sidebar--stuck');
 *
 *   // Add hover intent to dropdown menus
 *   const menuItems = document.querySelectorAll('.nav-item');
 *   new HoverIntent(menuItems, {
 *     onEnter: (target) => target.classList.add('visible'),
 *     onExit: (target) => target.classList.remove('visible'),
 *     exitDelay: 300,
 *     sensitivity: 10
 *   });
 * });
 */

// =============================================================================
// AUTOHIDE
// =============================================================================

/**
 * Automatically hides an element when scrolling down and reveals it when scrolling up. Perfect for headers, navigation bars, or floating action buttons.
 * 
 * The element should have CSS transitions defined for smooth animations:
 * ```css
 * .autohide {
 *   transition: transform 0.3s ease-in-out;
 * }
 * .autohide.is-autohide {
 *   transform: translateY(-100%);
 * }
 * ```
 *
 * @param {string} [selector=".autohide"] - CSS selector for the element(s) to autohide
 * @param {number} [threshold=50] - Minimum scroll distance (px) before triggering the effect
 * 
 * @returns {void}
 *
 * @example
 * // Basic usage with default settings
 * Autohide();
 *
 * @example
 * // Custom selector with higher threshold for less sensitive hiding
 * Autohide('.main-header', 100);
 * 
 * @example
 * // Multiple elements with the same behavior
 * Autohide('.autohide-nav, .autohide-toolbar', 60);
 */
export function Autohide(selector = ".autohide", threshold = 50) {
	const elements = document.querySelectorAll(selector);
	if (elements.length === 0) return;

	let lastScrollPosition = 0;

	const handleScroll = () => {
		const currentScrollPosition = window.scrollY;
		const scrollDifference = Math.abs(lastScrollPosition - currentScrollPosition);

		if (scrollDifference > threshold) {
			const isScrollingUp = currentScrollPosition < lastScrollPosition;
			
			elements.forEach((element) => {
				element.classList.toggle('is-autohide', !isScrollingUp);
			});

			lastScrollPosition = currentScrollPosition;
		}
	};

	window.addEventListener('scroll', handleScroll, { passive: true });
}

// =============================================================================
// HOVERINTENT
// =============================================================================

/**
 * A vanilla JavaScript implementation of jQuery's HoverIntent plugin.
 * Delays mouseenter/mouseleave events to determine if the user truly intends to hover.
 * 
 * This prevents accidental triggers when the cursor briefly passes over an element,
 * making dropdown menus and tooltips feel more intentional and less jarring.
 *
 * @param {NodeListOf<Element>|Element[]} elements - Elements to attach hover intent behavior
 * @param {Object} userConfig - Configuration object
 * @param {Function} userConfig.onEnter - Callback when hover intent is confirmed
 *   @param {Element} userConfig.onEnter.target - The element being hovered
 * @param {Function} userConfig.onExit - Callback when hover intent ends
 *   @param {Element} userConfig.onExit.target - The element no longer being hovered
 * @param {number} [userConfig.exitDelay=400] - Delay (ms) before firing onExit callback
 * @param {number} [userConfig.interval=100] - Polling interval (ms) to check mouse position
 * @param {number} [userConfig.sensitivity=7] - Mouse movement distance (px) to confirm intent
 *
 * @returns {Object} HoverIntent instance
 *
 * @example
 * // Basic dropdown menu implementation
 * const menuItems = document.querySelectorAll('.dropdown');
 * new HoverIntent(menuItems, {
 *   onEnter: (item) => {
 *     item.querySelector('.dropdown-menu').style.display = 'block';
 *   },
 *   onExit: (item) => {
 *     item.querySelector('.dropdown-menu').style.display = 'none';
 *   }
 * });
 *
 * @example
 * // Mega menu with custom timing
 * const megaMenuTriggers = document.querySelectorAll('.mega-menu-trigger');
 * new HoverIntent(megaMenuTriggers, {
 *   onEnter: (trigger) => trigger.classList.add('is-open'),
 *   onExit: (trigger) => trigger.classList.remove('is-open'),
 *   exitDelay: 300,    // Quick exit
 *   sensitivity: 10,   // Less sensitive (larger movements needed)
 *   interval: 150      // Check less frequently
 * });
 *
 * @example
 * // Tooltip with high sensitivity
 * const tooltipElements = document.querySelectorAll('[data-tooltip]');
 * new HoverIntent(tooltipElements, {
 *   onEnter: (el) => {
 *     const tooltip = document.createElement('div');
 *     tooltip.className = 'tooltip';
 *     tooltip.textContent = el.dataset.tooltip;
 *     el.appendChild(tooltip);
 *   },
 *   onExit: (el) => {
 *     const tooltip = el.querySelector('.tooltip');
 *     tooltip?.remove();
 *   },
 *   exitDelay: 200,
 *   sensitivity: 5     // Very sensitive (small movements count)
 * });
 */
export class HoverIntent {
	constructor(elements, userConfig) {
		// Validate required callbacks
		if (!userConfig?.onEnter || !userConfig?.onExit) {
			throw new Error('HoverIntent requires both onEnter and onExit callbacks.');
		}

		// Configuration with defaults
		this.config = {
			exitDelay: 400,
			interval: 100,
			sensitivity: 7,
			...userConfig
		};

		// Mouse tracking state
		this.mousePosition = { current: { x: 0, y: 0 }, previous: { x: 0, y: 0 } };
		this.timers = { poll: null, exit: null };
		this.elements = Array.from(elements);

		// Initialize all elements
		this.init();
	}

	/**
	 * Tracks mouse movement for intent detection
	 * @private
	 */
	trackMouse = (event) => {
		this.mousePosition.current.x = event.pageX;
		this.mousePosition.current.y = event.pageY;
	};

	/**
	 * Compares mouse positions to determine hover intent
	 * @private
	 */
	compareMousePosition = (targetElement) => {
		const { current, previous } = this.mousePosition;
		const deltaX = previous.x - current.x;
		const deltaY = previous.y - current.y;
		const distance = Math.sqrt(deltaX * deltaX + deltaY * deltaY);

		if (distance < this.config.sensitivity) {
			// Mouse has settled - user intends to hover
			clearTimeout(this.timers.exit);
			
			// Deactivate all other elements
			this.elements.forEach((element) => {
				if (element.isActive && element !== targetElement) {
					this.config.onExit(element);
					element.isActive = false;
				}
			});

			// Activate current element
			this.config.onEnter(targetElement);
			targetElement.isActive = true;
		} else {
			// Mouse is still moving - keep tracking
			this.mousePosition.previous.x = current.x;
			this.mousePosition.previous.y = current.y;
			this.timers.poll = setTimeout(
				() => this.compareMousePosition(targetElement),
				this.config.interval
			);
		}
	};

	/**
	 * Handles mouseenter event
	 * @private
	 */
	handleMouseEnter = (element, event) => {
		this.mousePosition.previous.x = event.pageX;
		this.mousePosition.previous.y = event.pageY;

		if (element.isActive) {
			clearTimeout(this.timers.exit);
			return;
		}

		this.timers.poll = setTimeout(
			() => this.compareMousePosition(element),
			this.config.interval
		);
	};

	/**
	 * Handles mouseleave event
	 * @private
	 */
	handleMouseLeave = (element) => {
		clearTimeout(this.timers.poll);

		if (!element.isActive) return;

		this.timers.exit = setTimeout(() => {
			this.config.onExit(element);
			element.isActive = false;
		}, this.config.exitDelay);
	};

	/**
	 * Initializes hover intent for all elements
	 * @private
	 */
	init() {
		this.elements.forEach((element) => {
			element.isActive = false;

			element.addEventListener('mousemove', this.trackMouse, { passive: true });
			element.addEventListener('mouseenter', (event) => this.handleMouseEnter(element, event));
			element.addEventListener('mouseleave', () => this.handleMouseLeave(element));
		});
	}
}

// =============================================================================
// SMOOTHSCROLL
// =============================================================================

/**
 * Enables smooth scrolling to anchor links on the same page.
 * Automatically accounts for fixed header heights to prevent content from hiding behind headers.
 * 
 * Works with any anchor link that starts with '#' and points to an element with a matching ID.
 *
 * @param {string} [selector='[href^="#"]'] - CSS selector for anchor links to enhance
 * @param {string} [headerSelector='.header'] - CSS selector for fixed header (for offset calculation)
 * 
 * @returns {void}
 *
 * @example
 * // Basic usage - works with all anchor links
 * SmoothScroll();
 * // Now clicking <a href="#section-2">Go to Section 2</a> will smooth scroll
 *
 * @example
 * // Custom selectors for specific navigation
 * SmoothScroll('nav a[href^="#"]', '.main-navigation');
 *
 * @example
 * // Table of contents with custom header
 * SmoothScroll('.toc a', '.sticky-header');
 * 
 * @example
 * // HTML structure this works with:
 * // <nav class="header">...</nav>
 * // <a href="#about">About Us</a>
 * // <section id="about">...</section>
 */
export function SmoothScroll(selector = '[href^="#"]', headerSelector = '.header') {
	const links = document.querySelectorAll(selector);
	if (links.length === 0) return;

	const header = document.querySelector(headerSelector);
	const headerHeight = header?.clientHeight || 0;

	const handleClick = function(event) {
		const href = this.getAttribute('href');

		// Skip invalid anchors
		if (!href || href === '#' || href.startsWith('#!')) return;

		const targetElement = document.querySelector(href);
		if (!targetElement) return;

		event.preventDefault();

		const targetPosition = targetElement.offsetTop - headerHeight;
		
		window.scroll({
			top: targetPosition,
			behavior: 'smooth'
		});
	};

	links.forEach((link) => {
		link.addEventListener('click', handleClick);
	});
}

// =============================================================================
// STICKY
// =============================================================================

/**
 * Adds a class to an element when it becomes "stuck" (pinned) at the top of the viewport. Uses the modern IntersectionObserver API for efficient performance.
 * 
 * The element must have CSS position: sticky defined:
 * ```css
 * .sticky {
 *   position: sticky;
 *   top: -1px; // Important: negative value triggers the observer
 * }
 * .sticky.is-sticky {
 *   box-shadow: 0 2px 8px rgba(0,0,0,0.1);
 * }
 * ```
 *
 * @param {string} [selector=".sticky"] - CSS selector for the sticky element
 * @param {string} [stickyClass="is-sticky"] - Class name to add when element is stuck
 * 
 * @returns {void}
 *
 * @example
 * // Basic usage with default class
 * Sticky();
 *
 * @example
 * // Custom sidebar with custom class
 * Sticky('.sidebar', 'sidebar--pinned');
 *
 * @example
 * // Table header that changes appearance when stuck
 * Sticky('.table-header', 'is-floating');
 * 
 * @example
 * // Complete CSS setup:
 * // .sticky {
 * //   position: sticky;
 * //   top: -1px;
 * //   transition: box-shadow 0.2s;
 * // }
 * // .sticky.is-sticky {
 * //   box-shadow: 0 4px 12px rgba(0,0,0,0.15);
 * //   background: white;
 * // }
 */
export function Sticky(selector = '.sticky', stickyClass = 'is-sticky') {
	const stickyElement = document.querySelector(selector);
	if (!stickyElement) return;

	const observer = new IntersectionObserver(
		([entry]) => {
			const isStuck = entry.intersectionRatio < 1;
			entry.target.classList.toggle(stickyClass, isStuck);
		},
		{ threshold: [1] }
	);

	observer.observe(stickyElement);
}