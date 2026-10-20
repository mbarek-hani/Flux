const STORAGE_KEY = 'theme';

export function storedTheme() {
    const value = window.localStorage.getItem(STORAGE_KEY);

    return value === 'dark' || value === 'light' ? value : null;
}

export function preferredTheme() {
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

export function currentTheme() {
    return document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
}

export function applyTheme(theme) {
    if (theme === 'dark') {
        document.documentElement.setAttribute('data-theme', 'dark');
    } else {
        document.documentElement.removeAttribute('data-theme');
    }

    document.dispatchEvent(new CustomEvent('flux:theme-changed', { detail: { theme } }));
}

export function setTheme(theme) {
    const resolved = theme === 'dark' ? 'dark' : 'light';

    window.localStorage.setItem(STORAGE_KEY, resolved);
    applyTheme(resolved);

    return resolved;
}

export function resolvedTheme() {
    return storedTheme() ?? currentTheme() ?? preferredTheme();
}
