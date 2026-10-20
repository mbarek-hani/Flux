import { selectAll } from '../core/dom';
import { requestJson } from '../core/http';

const FEEDBACK_DURATION = 3000;

export function initSettings(root = document) {
    selectAll('[data-plugin-settings]', root).forEach((form) => {
        if (form.dataset.settingsReady === 'true') {
            return;
        }

        form.dataset.settingsReady = 'true';

        const button = form.querySelector('[data-plugin-settings-submit]');
        const success = form.querySelector('[data-plugin-settings-success]');
        const error = form.querySelector('[data-plugin-settings-error]');
        let feedbackTimer = null;

        const hideFeedback = () => {
            if (success) success.hidden = true;
            if (error) error.hidden = true;
        };

        const showFeedback = (element, text = '') => {
            window.clearTimeout(feedbackTimer);
            hideFeedback();

            if (!element) {
                return;
            }

            if (text !== '') {
                element.textContent = text;
            }

            element.hidden = false;
            feedbackTimer = window.setTimeout(hideFeedback, FEEDBACK_DURATION);
        };

        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            if (!button || button.disabled) {
                return;
            }

            const settings = {};

            new FormData(form).forEach((value, key) => {
                if (key.startsWith('settings[') && key.endsWith(']')) {
                    settings[key.slice(9, -1)] = value;
                }
            });

            const originalLabel = button.textContent.trim();

            button.disabled = true;
            button.textContent = 'Saving...';
            hideFeedback();

            try {
                await requestJson(form.dataset.updateUrl, {
                    method: 'POST',
                    body: {
                        plugin: form.dataset.slug,
                        settings,
                    },
                });

                showFeedback(success);
            } catch (exception) {
                showFeedback(error, exception.message);
            } finally {
                button.disabled = false;
                button.textContent = originalLabel;
            }
        });
    });
}
