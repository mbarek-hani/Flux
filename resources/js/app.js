import Chart from 'chart.js/auto';
import jsVectorMap from 'jsvectormap';
import 'jsvectormap/dist/maps/world.js';
import 'jsvectormap/dist/jsvectormap.min.css';

import { initAlerts } from './components/alert';
import { initConfirmations } from './components/confirm';
import { initCopyButtons } from './components/copy';
import { initDropdowns } from './components/dropdown';
import { initFlashes } from './components/flash';
import { closeModal, initModals, openModal, setupModalGlobalListeners } from './components/modal';
import { initSidebar } from './components/sidebar';
import { initTabs } from './components/tabs';
import { initThemeSwitchers } from './components/theme-switcher';
import { initDashboard } from './pages/dashboard';
import { initMarketplace } from './pages/marketplace';
import { initSettings } from './pages/settings';
import { isReady, markReady, onReady } from './core/ready';
import { currentTheme, setTheme } from './core/theme';

window.Chart = Chart;
window.jsVectorMap = jsVectorMap;

export function initComponents(root = document) {
    setupModalGlobalListeners();
    initAlerts(root);
    initConfirmations();
    initCopyButtons(root);
    initDropdowns(root);
    initFlashes(root);
    initModals(root);
    initSidebar(root);
    initTabs(root);
    initThemeSwitchers(root);
    initDashboard(root);
    initMarketplace(root);
    initSettings(root);
}

function boot() {
    initComponents();
    markReady();
}

window.FluxAdmin = {
    init: initComponents,
    isReady,
    onReady,
    modal: {
        close: closeModal,
        open: openModal,
    },
    theme: {
        get: currentTheme,
        set: setTheme,
    },
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}
