import { selectAll } from '../core/dom';

const LEAVE_DURATION = 300;

export function initAlerts(root = document) {
    selectAll('[data-alert]', root).forEach((alert) => {
        if (alert.dataset.alertReady === 'true') {
            return;
        }

        alert.dataset.alertReady = 'true';

        let dismissed = false;
        const timeout = Number(alert.dataset.alertTimeout ?? 5000);

        const dismiss = () => {
            if (dismissed) {
                return;
            }

            dismissed = true;
            alert.classList.add('is-leaving');

            window.setTimeout(() => alert.remove(), LEAVE_DURATION);
        };

        alert.querySelector('[data-alert-close]')?.addEventListener('click', dismiss);

        if (timeout > 0) {
            window.setTimeout(dismiss, timeout);
        }
    });
}
