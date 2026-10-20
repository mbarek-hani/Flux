const callbacks = [];
let ready = false;

export function onReady(callback) {
    if (typeof callback !== 'function') {
        return;
    }

    if (ready) {
        callback();
        return;
    }

    callbacks.push(callback);
}

export function markReady() {
    if (ready) {
        return;
    }

    ready = true;

    callbacks.splice(0).forEach((callback) => callback());

    document.dispatchEvent(new CustomEvent('flux:ready'));
}

export function isReady() {
    return ready;
}
