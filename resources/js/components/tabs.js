import { selectAll } from '../core/dom';

function belongsTo(tabs, element) {
    return element.closest('[data-tabs]') === tabs;
}

function panelFor(tabs, target) {
    return selectAll('[data-tab-panel]', tabs).find((panel) => {
        return belongsTo(tabs, panel) && panel.dataset.tabPanel === String(target);
    });
}

function triggerFor(tabs, target) {
    return selectAll('[data-tab-target]', tabs).find((trigger) => {
        return belongsTo(tabs, trigger) && trigger.dataset.tabTarget === String(target);
    });
}

function triggersFor(tabs) {
    return selectAll('[data-tab-target]', tabs).filter((trigger) => belongsTo(tabs, trigger));
}

function panelsFor(tabs) {
    return selectAll('[data-tab-panel]', tabs).filter((panel) => belongsTo(tabs, panel));
}

function activate(tabs, target, notify = true) {
    const panel = panelFor(tabs, target);
    const trigger = triggerFor(tabs, target);

    if (!panel || !trigger) {
        return;
    }

    triggersFor(tabs).forEach((item) => {
        const active = item === trigger;
        item.classList.toggle('is-active', active);
        item.setAttribute('aria-selected', active ? 'true' : 'false');
        item.setAttribute('tabindex', active ? '0' : '-1');
    });

    panelsFor(tabs).forEach((item) => {
        item.hidden = item !== panel;
    });

    if (notify) {
        document.dispatchEvent(new CustomEvent('flux:tab-changed', {
            detail: {
                group: tabs.dataset.tabs ?? '',
                target: String(target),
            },
        }));
    }
}

export function initTabs(root = document) {
    selectAll('[data-tabs]', root).forEach((tabs) => {
        if (tabs.dataset.tabsReady === 'true') {
            return;
        }

        tabs.dataset.tabsReady = 'true';

        const triggers = triggersFor(tabs);
        const initial = triggers.find((trigger) => trigger.classList.contains('is-active'))
            ?? triggers.find((trigger) => trigger.getAttribute('aria-selected') === 'true')
            ?? triggers[0];

        triggers.forEach((trigger) => {
            trigger.addEventListener('click', (event) => {
                event.preventDefault();
                activate(tabs, trigger.dataset.tabTarget);
            });

            trigger.addEventListener('keydown', (event) => {
                const currentIndex = triggers.indexOf(trigger);
                let nextIndex = null;

                if (event.key === 'ArrowRight') {
                    nextIndex = (currentIndex + 1) % triggers.length;
                } else if (event.key === 'ArrowLeft') {
                    nextIndex = (currentIndex - 1 + triggers.length) % triggers.length;
                } else if (event.key === 'Home') {
                    nextIndex = 0;
                } else if (event.key === 'End') {
                    nextIndex = triggers.length - 1;
                }

                if (nextIndex === null) {
                    return;
                }

                event.preventDefault();
                const next = triggers[nextIndex];
                activate(tabs, next.dataset.tabTarget);
                next.focus();
            });
        });

        if (initial) {
            activate(tabs, initial.dataset.tabTarget, false);
        }
    });
}
