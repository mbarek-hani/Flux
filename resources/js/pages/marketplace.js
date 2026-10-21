import { select } from '../core/dom';

const ACTION_LABELS = {
    install: 'Installing',
    update: 'Updating',
    uninstall: 'Uninstalling',
};

const STREAM_URLS = {
    install: (id) => `/marketplace/install/${id}/stream`,
    update: (id) => `/marketplace/update/${id}/stream`,
    uninstall: (id) => `/marketplace/uninstall/${id}/stream`,
};

function majorVersion(version) {
    const clean = String(version ?? '').toLowerCase().replace(/^v/, '');

    return Number.parseInt(clean.split('.')[0], 10) || 0;
}

function confirmMajorUpdate(action, pluginName, installedVersion, currentVersion) {
    if (action !== 'update' || !installedVersion || !currentVersion) {
        return true;
    }

    if (majorVersion(installedVersion) === majorVersion(currentVersion)) {
        return true;
    }

    return window.confirm(
        `Warning: you are about to update ${pluginName} from version ${installedVersion} to version ${currentVersion}.\n\n` +
        'This is a major update. All data associated with this plugin will be permanently deleted.\n\n' +
        'Do you want to continue?',
    );
}

export function initMarketplace(root = document) {
    const page = select('[data-marketplace]', root);

    if (!page || page.dataset.marketplaceReady === 'true') {
        return;
    }

    page.dataset.marketplaceReady = 'true';

    const modal = document.querySelector('[data-modal="progress-modal"]');
    const verb = modal?.querySelector('[data-marketplace-progress-verb]') ?? null;
    const pluginName = modal?.querySelector('[data-marketplace-progress-plugin]') ?? null;
    const progressBar = modal?.querySelector('[data-marketplace-progress-bar]') ?? null;
    const status = modal?.querySelector('[data-marketplace-progress-status]') ?? null;
    const steps = modal?.querySelector('[data-marketplace-progress-steps]') ?? null;
    const closeButton = modal?.querySelector('[data-marketplace-close]') ?? null;

    let eventSource = null;
    let completed = false;
    let failed = false;

    const iconTemplate = (type) => {
        return modal?.querySelector(`template[data-marketplace-progress-icon="${type}"]`) ?? null;
    };

    const setProgress = (value) => {
        if (progressBar) {
            progressBar.style.width = `${Math.max(0, Math.min(100, Number(value) || 0))}%`;
        }
    };

    const setStatus = (message) => {
        if (status) {
            status.textContent = message;
        }
    };

    const showCloseButton = (label) => {
        if (!closeButton) {
            return;
        }

        closeButton.textContent = label;
        closeButton.hidden = false;
    };

    const addStep = (text, type = 'info') => {
        if (!steps || !text) {
            return;
        }

        const step = document.createElement('div');
        step.className = 'c-progress-step';

        if (type === 'success') {
            step.classList.add('c-progress-step--success');
        } else if (type === 'error') {
            step.classList.add('c-progress-step--error');
        }

        const template = iconTemplate(type);

        if (template) {
            step.appendChild(template.content.cloneNode(true));
        }

        const label = document.createElement('span');
        label.textContent = text;
        step.appendChild(label);

        steps.appendChild(step);
        steps.scrollTop = steps.scrollHeight;
    };

    const updateLastStep = (type) => {
        const last = steps?.lastElementChild;

        if (!last) {
            return false;
        }

        last.classList.remove('c-progress-step--success', 'c-progress-step--error');

        if (type === 'success') {
            last.classList.add('c-progress-step--success');
        } else if (type === 'error') {
            last.classList.add('c-progress-step--error');
        }

        const template = iconTemplate(type);

        if (template) {
            last.querySelector('.c-progress-step__icon')?.remove();
            last.prepend(template.content.cloneNode(true));
        }

        return true;
    };

    const closeProgress = () => {
        eventSource?.close();
        eventSource = null;

        window.dispatchEvent(new CustomEvent('close-modal', { detail: 'progress-modal' }));

        if (completed) {
            window.location.reload();
        }
    };

    const launch = (button) => {
        const { marketplaceAction: action, pluginId: id } = button.dataset;

        if (!action || !id || !STREAM_URLS[action]) {
            return;
        }

        const name = button.dataset.pluginName ?? '';

        if (!confirmMajorUpdate(action, name, button.dataset.installedVersion, button.dataset.currentVersion)) {
            return;
        }

        eventSource?.close();

        completed = false;
        failed = false;

        if (verb) {
            verb.textContent = ACTION_LABELS[action];
        }

        if (pluginName) {
            pluginName.textContent = name;
        }

        if (steps) {
            steps.innerHTML = '';
        }

        if (closeButton) {
            closeButton.hidden = true;
        }

        setProgress(0);
        setStatus('Connecting...');
        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'progress-modal' }));

        eventSource = new EventSource(STREAM_URLS[action](id));

        eventSource.onmessage = (event) => {
            let data;

            try {
                data = JSON.parse(event.data);
            } catch (error) {
                console.error('Unable to parse marketplace stream response.', error);
                return;
            }

            if (data.status === 'progress') {
                setProgress(data.progress);
                setStatus(data.message ?? '');
                addStep(data.message, 'info');
                return;
            }

            if (data.status === 'success') {
                setProgress(100);
                setStatus(data.message || 'Operation completed successfully.');

                if (!updateLastStep('success')) {
                    addStep(data.message || 'Operation completed successfully.', 'success');
                }

                completed = true;
                eventSource?.close();
                eventSource = null;
                showCloseButton('Finish');
                return;
            }

            if (data.status === 'error') {
                setProgress(100);
                setStatus(data.message || 'An error occurred while processing this operation.');
                addStep(data.message || 'An error occurred while processing this operation.', 'error');
                failed = true;
                eventSource?.close();
                eventSource = null;
                showCloseButton('Close');
            }
        };

        eventSource.onerror = () => {
            if (!completed && !failed) {
                addStep('Network error or live stream disconnected.', 'error');
                setStatus('Network error');
                failed = true;
                showCloseButton('Close');
            }

            eventSource?.close();
            eventSource = null;
        };
    };

    page.addEventListener('click', (event) => {
        const action = event.target.closest('[data-marketplace-action]');

        if (action) {
            event.preventDefault();
            launch(action);
            return;
        }

        const card = event.target.closest('[data-marketplace-card]');

        if (!card || event.target.closest('a, button, form, input, select, textarea')) {
            return;
        }

        window.location.href = card.dataset.href;
    });

    page.addEventListener('keydown', (event) => {
        const card = event.target.closest('[data-marketplace-card]');

        if (!card || event.target !== card || !['Enter', ' '].includes(event.key)) {
            return;
        }

        event.preventDefault();
        window.location.href = card.dataset.href;
    });

    closeButton?.addEventListener('click', closeProgress);
}
