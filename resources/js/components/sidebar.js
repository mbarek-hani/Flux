import { selectAll } from '../core/dom';

const DESKTOP_QUERY = '(min-width: 1024px)';
const STORAGE_KEY = 'sidebar_ouvert';

function isDesktop() {
    return window.matchMedia(DESKTOP_QUERY).matches;
}

function storedOpen() {
    return window.localStorage.getItem(STORAGE_KEY) !== 'false';
}

function setupSubmenus(root) {
    selectAll('[data-sidebar-submenu]', root).forEach((submenu) => {
        if (submenu.dataset.submenuReady === 'true') {
            return;
        }

        const trigger = submenu.querySelector('[data-sidebar-submenu-trigger]');
        const panel = submenu.querySelector('[data-sidebar-submenu-panel]');

        if (!trigger || !panel) {
            return;
        }

        submenu.dataset.submenuReady = 'true';

        const setOpen = (open) => {
            submenu.dataset.open = open ? 'true' : 'false';
            trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
            panel.hidden = !open;

            submenu.querySelectorAll('[data-submenu-icon="closed"]').forEach((icon) => {
                icon.hidden = open;
            });

            submenu.querySelectorAll('[data-submenu-icon="open"]').forEach((icon) => {
                icon.hidden = !open;
            });
        };

        trigger.addEventListener('click', () => {
            setOpen(submenu.dataset.open !== 'true');
        });

        setOpen(submenu.dataset.open === 'true');
    });
}

export function initSidebar(root = document) {
    const sidebarRoot = root.querySelector?.('[data-sidebar-root]') ?? document.querySelector('[data-sidebar-root]');

    if (!sidebarRoot) {
        return;
    }

    const expanded = sidebarRoot.querySelector('[data-sidebar-expanded]');
    const collapsed = sidebarRoot.querySelector('[data-sidebar-collapsed]');
    const overlay = sidebarRoot.querySelector('[data-sidebar-overlay]');
    const wrapper = sidebarRoot.querySelector('[data-main-wrapper]');

    setupSubmenus(sidebarRoot);

    if (sidebarRoot.dataset.sidebarReady === 'true') {
        return;
    }

    sidebarRoot.dataset.sidebarReady = 'true';

    let desktop = isDesktop();
    let open = desktop ? storedOpen() : false;

    const apply = (persist = false) => {
        document.documentElement.dataset.sidebarState = open ? 'true' : 'false';

        if (expanded) {
            expanded.hidden = !open;
        }

        if (collapsed) {
            collapsed.hidden = open;
        }

        if (overlay) {
            overlay.hidden = !(open && !desktop);
        }

        wrapper?.classList.toggle('l-main-wrapper--shifted', open);
        wrapper?.classList.toggle('l-main-wrapper--collapsed', !open);

        if (persist) {
            window.localStorage.setItem(STORAGE_KEY, open ? 'true' : 'false');
        }
    };

    const setOpen = (value, persist = true) => {
        open = value;
        apply(persist);
    };

    sidebarRoot.addEventListener('click', (event) => {
        if (event.target.closest('[data-sidebar-open]')) {
            event.preventDefault();
            setOpen(true);
            return;
        }

        if (event.target.closest('[data-sidebar-close]')) {
            event.preventDefault();
            setOpen(false);
        }
    });

    overlay?.addEventListener('click', () => setOpen(false));

    window.addEventListener('resize', () => {
        const nextDesktop = isDesktop();

        if (nextDesktop === desktop) {
            return;
        }

        desktop = nextDesktop;
        open = desktop ? storedOpen() : false;
        apply(false);
    });

    apply(false);
}
