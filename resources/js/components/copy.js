import { selectAll } from '../core/dom';

const SUCCESS_DURATION = 2000;

async function copyText(text) {
    if (navigator.clipboard?.writeText) {
        await navigator.clipboard.writeText(text);
        return;
    }

    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.setAttribute('readonly', '');
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand('copy');
    textarea.remove();
}

export function initCopyButtons(root = document) {
    selectAll('[data-copy-button]', root).forEach((button) => {
        if (button.dataset.copyReady === 'true') {
            return;
        }

        button.dataset.copyReady = 'true';

        button.addEventListener('click', async () => {
            const target = document.getElementById(button.dataset.copyTarget ?? '');

            if (!target) {
                return;
            }

            try {
                await copyText(target.textContent ?? '');
                button.classList.add('is-copied');
                button.setAttribute('aria-label', button.dataset.copySuccessLabel ?? 'Copied');

                window.setTimeout(() => {
                    button.classList.remove('is-copied');
                    button.setAttribute('aria-label', button.dataset.copyLabel ?? 'Copy');
                }, SUCCESS_DURATION);
            } catch (error) {
                console.error('Unable to copy text.', error);
            }
        });
    });
}
