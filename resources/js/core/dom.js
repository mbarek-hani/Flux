export function select(selector, root = document) {
    return root.querySelector(selector);
}

export function selectAll(selector, root = document) {
    return Array.from(root.querySelectorAll(selector));
}

export function delegate(root, eventName, selector, handler) {
    root.addEventListener(eventName, (event) => {
        const target = event.target.closest(selector);

        if (target && root.contains(target)) {
            handler(event, target);
        }
    });
}

export function setHidden(element, hidden) {
    if (!element) {
        return;
    }

    element.hidden = hidden;
    element.setAttribute('aria-hidden', hidden ? 'true' : 'false');
}

export function prefersReducedMotion() {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

export function nextFrame(callback) {
    window.requestAnimationFrame(() => window.requestAnimationFrame(callback));
}
