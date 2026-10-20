import { selectAll } from '../core/dom';
import { currentTheme, setTheme } from '../core/theme';

function updateOptions(root, theme) {
    selectAll('[data-theme-value]', root).forEach((option) => {
        const active = option.dataset.themeValue === theme;
        option.classList.toggle('is-active', active);
        option.setAttribute('aria-pressed', active ? 'true' : 'false');
    });
}

export function initThemeSwitchers(root = document) {
    selectAll('[data-theme-switcher]', root).forEach((switcher) => {
        if (switcher.dataset.themeSwitcherReady === 'true') {
            return;
        }

        switcher.dataset.themeSwitcherReady = 'true';

        updateOptions(switcher, currentTheme());

        selectAll('[data-theme-value]', switcher).forEach((option) => {
            option.addEventListener('click', () => {
                const theme = setTheme(option.dataset.themeValue);
                updateOptions(switcher, theme);
            });
        });

        document.addEventListener('flux:theme-changed', (event) => {
            updateOptions(switcher, event.detail.theme);
        });
    });
}
