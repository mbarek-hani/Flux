export function initConfirmations() {
    if (document.documentElement.dataset.confirmReady === 'true') {
        return;
    }

    document.documentElement.dataset.confirmReady = 'true';

    document.addEventListener('submit', (event) => {
        const message = event.target.dataset.confirm;

        if (message && !window.confirm(message)) {
            event.preventDefault();
        }
    });

    document.addEventListener('click', (event) => {
        const target = event.target.closest('[data-confirm]');

        if (!target || target.tagName === 'FORM') {
            return;
        }

        if (!window.confirm(target.dataset.confirm)) {
            event.preventDefault();
            event.stopPropagation();
        }
    });
}
