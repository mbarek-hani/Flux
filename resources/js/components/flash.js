import { selectAll } from '../core/dom';

const LEAVE_DURATION = 250;

export function initFlashes(root = document) {
    selectAll('[data-flash]', root).forEach((flash) => {
        if (flash.dataset.flashReady === 'true') {
            return;
        }

        flash.dataset.flashReady = 'true';

        const timeout = Number(flash.dataset.flashTimeout ?? 3000);

        if (timeout <= 0) {
            return;
        }

        window.setTimeout(() => {
            flash.classList.add('is-leaving');

            window.setTimeout(() => {
                flash.hidden = true;
            }, LEAVE_DURATION);
        }, timeout);
    });
}
