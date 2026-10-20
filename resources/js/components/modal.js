import { prefersReducedMotion, selectAll } from '../core/dom';

const TRANSITION_DURATION = 200;
const FOCUSABLE_SELECTOR = [
    'a[href]',
    'button:not([disabled])',
    'input:not([disabled]):not([type="hidden"])',
    'select:not([disabled])',
    'textarea:not([disabled])',
    '[tabindex]:not([tabindex="-1"])',
].join(', ');

function findModal(name) {
    if (!name) {
        return null;
    }

    return document.querySelector(`[data-modal="${CSS.escape(String(name))}"]`);
}

function modalName(modal) {
    return modal?.dataset.modal ?? '';
}

function isClosable(modal) {
    return modal?.dataset.modalClosable !== 'false';
}

function focusableElements(modal) {
    return selectAll(FOCUSABLE_SELECTOR, modal).filter((element) => {
        return element.offsetParent !== null && !element.hasAttribute('disabled');
    });
}

function focusFirst(modal) {
    if (modal?.dataset.modalFocusable !== 'true') {
        return;
    }

    window.setTimeout(() => {
        focusableElements(modal)[0]?.focus();
    }, 50);
}

function updateBodyLock() {
    const hasOpenModal = document.querySelector('[data-modal]:not([hidden])') !== null;

    document.body.classList.toggle('has-modal', hasOpenModal);
}

export function openModal(name) {
    const modal = findModal(name);

    if (!modal || !modal.hidden) {
        return;
    }

    window.clearTimeout(modal.closeTimer);
    modal.hidden = false;
    modal.classList.remove('is-closing');
    modal.setAttribute('aria-hidden', 'false');

    window.requestAnimationFrame(() => modal.classList.add('is-open'));

    updateBodyLock();
    focusFirst(modal);

    modal.dispatchEvent(new CustomEvent('modal-opened', { detail: modalName(modal) }));
}

export function closeModal(name, options = {}) {
    const modal = findModal(name);

    if (!modal || modal.hidden) {
        return;
    }

    const { notify = false } = options;

    modal.classList.remove('is-open');
    modal.classList.add('is-closing');

    const finish = () => {
        modal.hidden = true;
        modal.classList.remove('is-closing');
        modal.setAttribute('aria-hidden', 'true');
        updateBodyLock();

        if (notify) {
            modal.dispatchEvent(new CustomEvent('modal-closed', { detail: modalName(modal) }));
        }
    };

    window.clearTimeout(modal.closeTimer);

    if (prefersReducedMotion()) {
        finish();
        return;
    }

    modal.closeTimer = window.setTimeout(finish, TRANSITION_DURATION);
}

function dismissModal(modal) {
    if (!modal || !isClosable(modal)) {
        return;
    }

    closeModal(modalName(modal), { notify: true });
}

function trapFocus(modal, event) {
    const focusable = focusableElements(modal);

    if (focusable.length === 0) {
        event.preventDefault();
        return;
    }

    const first = focusable[0];
    const last = focusable[focusable.length - 1];

    if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
    }
}

export function initModals(root = document) {
    selectAll('[data-modal]', root).forEach((modal) => {
        if (modal.dataset.modalReady === 'true') {
            return;
        }

        modal.dataset.modalReady = 'true';

        if (!modal.hasAttribute('aria-hidden')) {
            modal.setAttribute('aria-hidden', modal.hidden ? 'true' : 'false');
        }
    });
}

let globalListenersReady = false;

export function setupModalGlobalListeners() {
    if (globalListenersReady) {
        return;
    }

    globalListenersReady = true;

    window.addEventListener('open-modal', (event) => openModal(event.detail));
    window.addEventListener('close-modal', (event) => closeModal(event.detail));

    document.addEventListener('click', (event) => {
        const opener = event.target.closest('[data-modal-open]');

        if (opener) {
            event.preventDefault();
            openModal(opener.dataset.modalOpen);
            return;
        }

        const closer = event.target.closest('[data-modal-close]');

        if (closer) {
            event.preventDefault();
            closeModal(closer.dataset.modalClose, { notify: true });
            return;
        }

        const overlay = event.target.closest('[data-modal-overlay]');

        if (overlay) {
            dismissModal(overlay.closest('[data-modal]'));
        }
    });

    document.addEventListener('keydown', (event) => {
        const modal = selectAll('[data-modal]:not([hidden])').pop();

        if (!modal) {
            return;
        }

        if (event.key === 'Escape' && isClosable(modal)) {
            event.preventDefault();
            dismissModal(modal);
            return;
        }

        if (event.key === 'Tab') {
            trapFocus(modal, event);
        }
    });
}
