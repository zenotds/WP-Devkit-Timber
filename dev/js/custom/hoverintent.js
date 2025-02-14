// HoverIntent module
const HoverIntent = (function() {
    // Constructor
    return function(elements, userConfig) {
        const defaultOptions = {
            exitDelay: 400,
            interval: 100,
            sensitivity: 7,
        };
        let config = {};
        let currX, currY, prevX, prevY;
        let allElems, pollTimer, exitTimer;

        const extend = (defaults, userArgs) => Object.assign(defaults, userArgs);

        const mouseTrack = ev => {
            currX = ev.pageX;
            currY = ev.pageY;
        };

        const mouseCompare = targetElem => {
            const distX = prevX - currX,
                distY = prevY - currY;
            const distance = Math.sqrt(distX * distX + distY * distY);

            if (distance < config.sensitivity) {
                clearTimeout(exitTimer);
                for (let elem of allElems) {
                    if (elem.isActive) {
                        config.onExit(elem);
                        elem.isActive = false;
                    }
                }
                config.onEnter(targetElem);
                targetElem.isActive = true;
            } else {
                prevX = currX;
                prevY = currY;
                pollTimer = setTimeout(() => mouseCompare(targetElem), config.interval);
            }
        };

        const init = () => {
            if (!userConfig || !userConfig.onEnter || !userConfig.onExit) {
                throw 'onEnter and onExit callbacks must be provided';
            }
            config = extend(defaultOptions, userConfig);
            allElems = elements;

            for (let elem of allElems) {
                elem.isActive = false;
                elem.addEventListener('mousemove', mouseTrack);
                elem.addEventListener('mouseenter', ev => {
                    prevX = ev.pageX;
                    prevY = ev.pageY;
                    if (elem.isActive) {
                        clearTimeout(exitTimer);
                        return;
                    }
                    pollTimer = setTimeout(() => mouseCompare(elem), config.interval);
                });
                elem.addEventListener('mouseleave', () => {
                    clearTimeout(pollTimer);
                    if (!elem.isActive) return;
                    exitTimer = setTimeout(() => {
                        config.onExit(elem);
                        elem.isActive = false;
                    }, config.exitDelay);
                });
            }
        };

        init();
    };
})();

// Recognize touch devices and apply classes
const menuItems = document.querySelectorAll('.nav-item');

const isTouchDevice = () => 'ontouchstart' in window || window.DocumentTouch && document instanceof DocumentTouch;

if (isTouchDevice()) {
    menuItems.forEach(it => it.classList.add('touch-device'));
} else {
    const hi = new HoverIntent(menuItems, {
        onEnter: targetItem => targetItem.classList.add('visible'),
        onExit: targetItem => targetItem.classList.remove('visible'),
        exitDelay: 400,
        interval: 100,
        sensitivity: 7,
    });
}
