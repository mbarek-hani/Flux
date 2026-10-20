import { selectAll } from '../core/dom';

const CLOSE_DURATION = 120;
let globalListenersReady = false;

function findDropdown(element) {
    return element?.closest('[data-dropdown]') ?? null;
}

function openDropdown(dropdown) {
    const trigger = dropdown.querySelector('[data-dropdown-trigger]');
    const menu = dropdown.querySelector('[data-dropdown-menu]');

    if (!trigger || !menu) {
        return;
    }

    window.clearTimeout(dropdown.closeTimer);
    menu.hidden = false;
    trigger.setAttribute('aria-expanded', 'true');

    window.requestAnimationFrame(() => dropdown.classList.add('is-open'));
}

function closeDropdown(dropdown) {
    if (!dropdown) {
        return;
    }

    const trigger = dropdown.querySelector('[data-dropdown-trigger]');
    const menu = dropdown.querySelector('[data-dropdown-menu]');

    dropdown.classList.remove('is-open');
    trigger?.setAttribute('aria-expanded', 'false');

    window.clearTimeout(dropdown.closeTimer);
    dropdown.closeTimer = window.setTimeout(() => {
        if (!dropdown.classList.contains('is-open') && menu) {
            menu.hidden = true;
        }
    }, CLOSE_DURATION);
}

export function closeAllDropdowns(except = null) {
    selectAll('[data-dropdown].is-open').forEach((dropdown) => {
        if (dropdown !== except) {
            closeDropdown(dropdown);
        }
    });
}

function setupGlobalListeners() {
    if (globalListenersReady) {
        return;
    }

    globalListenersReady = true;

    document.addEventListener('click', (event) => {
        if (!event.target.closest('[data-dropdown]')) {
            closeAllDropdowns();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeAllDropdowns();
        }
    });
}

export function initDropdowns(root = document) {
    setupGlobalListeners();

    selectAll('[data-dropdown]', root).forEach((dropdown) => {
        if (dropdown.dataset.dropdownReady === 'true') {
            return;
        }

        const trigger = dropdown.querySelector('[data-dropdown-trigger]');
        const menu = dropdown.querySelector('[data-dropdown-menu]');

        if (!trigger || !menu) {
            return;
        }

        dropdown.dataset.dropdownReady = 'true';

        trigger.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();

            const isOpen = dropdown.classList.contains('is-open');

            closeAllDropdowns(dropdown);

            if (isOpen) {
                closeDropdown(dropdown);
            } else {
                openDropdown(dropdown);
            }
        });

        menu.addEventListener('click', (event) => {
            if (event.target.closest('a, button')) {
                closeDropdown(dropdown);
            }
        });
    });
}
