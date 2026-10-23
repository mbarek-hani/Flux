import { selectAll } from '../core/dom';

const MOBILE_QUERY = '(max-width: 767px)';

export function initTabOverflow(root = document) {
    selectAll('[data-tab-overflow-trigger]', root).forEach((trigger) => {
        if (trigger.dataset.tabOverflowReady === 'true') {
            return;
        }

        const tabs = trigger.closest('[data-tabs]');
        const container = trigger.closest('.p-dash__tabs');
        const nav = tabs?.querySelector('.p-dash__tab-nav');
        const panel = tabs?.querySelector('[data-tab-overflow-panel]');

        if (!tabs || !container || !nav || !panel) {
            return;
        }

        trigger.dataset.tabOverflowReady = 'true';

        const media = window.matchMedia(MOBILE_QUERY);
        const triggers = () => selectAll('[data-tab-target]', nav);
        const activeTrigger = () => triggers().find((item) => item.getAttribute('aria-selected') === 'true') ?? null;
        const isOpen = () => container.classList.contains('is-open');

        const sync = () => {
            const active = activeTrigger();
            const extraActive = active !== null && panel.contains(active);
            const label = active?.textContent?.trim() ?? '';

            trigger.classList.toggle('is-active', extraActive);
            trigger.setAttribute(
                'aria-label',
                extraActive && label !== ''
                    ? `More dashboard sections, ${label} selected`
                    : 'More dashboard sections',
            );
        };

        const close = (returnFocus = false) => {
            container.classList.remove('is-open');
            trigger.setAttribute('aria-expanded', 'false');

            if (returnFocus && media.matches) {
                trigger.focus();
            }
        };

        const open = () => {
            if (!media.matches) {
                return;
            }

            const active = activeTrigger();
            const target = active !== null && panel.contains(active)
                ? active
                : panel.querySelector('[data-tab-target]');

            container.classList.add('is-open');
            trigger.setAttribute('aria-expanded', 'true');

            window.requestAnimationFrame(() => target?.focus());
        };

        trigger.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();

            if (isOpen()) {
                close();
            } else {
                open();
            }
        });

        document.addEventListener('click', (event) => {
            if (isOpen() && !container.contains(event.target)) {
                close();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && isOpen()) {
                event.preventDefault();
                close(true);
            }
        });

        media.addEventListener('change', () => {
            close();

            const active = activeTrigger();

            if (!media.matches && active !== null) {
                window.requestAnimationFrame(() => {
                    active.scrollIntoView({ block: 'nearest', inline: 'nearest' });
                });
            }

            sync();
        });

        document.addEventListener('flux:tab-changed', (event) => {
            if (event.detail.group !== 'dashboard') {
                return;
            }

            const active = triggers().find((item) => item.dataset.tabTarget === String(event.detail.target));
            const extraActive = active !== undefined && panel.contains(active);
            const wasOpen = isOpen();

            sync();

            if (wasOpen) {
                close(extraActive && media.matches);
            }

            if (!media.matches && active !== undefined) {
                window.requestAnimationFrame(() => {
                    active.scrollIntoView({ block: 'nearest', inline: 'nearest' });
                });
            }
        });

        sync();
    });
}
