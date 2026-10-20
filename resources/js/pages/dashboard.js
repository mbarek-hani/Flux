import Chart from 'chart.js/auto';
import jsVectorMap from 'jsvectormap';
import 'jsvectormap/dist/maps/world.js';

import { selectAll } from '../core/dom';
import { requestJson } from '../core/http';

const FALLBACK_PALETTE = ['#6366f1', '#8b5cf6', '#ec4899', '#f59e0b', '#10b981'];

function cssVar(name, fallback) {
    const value = getComputedStyle(document.documentElement).getPropertyValue(name).trim();

    return value === '' ? fallback : value;
}

function formatNumber(value) {
    return new Intl.NumberFormat('en-GB').format(Number(value) || 0);
}

function formatDuration(seconds) {
    const total = Number(seconds) || 0;

    if (total < 1) {
        return '0s';
    }

    const minutes = Math.floor(total / 60);
    const remaining = Math.floor(total % 60);

    return minutes > 0 ? `${minutes}m ${remaining}s` : `${remaining}s`;
}

function formatDay(value) {
    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return '';
    }

    return date.toLocaleDateString('en-GB', { day: 'numeric', month: 'short' });
}

function formatDateTime(value) {
    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return '';
    }

    return date.toLocaleString('en-GB', {
        day: 'numeric',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function browserIcon(name) {
    if (!name) {
        return '';
    }

    const value = name.toLowerCase();
    const base = 'https://cdnjs.cloudflare.com/ajax/libs/browser-logos/70.4.0';

    if (value.includes('chrome')) return `${base}/chrome/chrome_16x16.png`;
    if (value.includes('firefox')) return `${base}/firefox/firefox_16x16.png`;
    if (value.includes('safari')) return `${base}/safari/safari_16x16.png`;
    if (value.includes('edge')) return `${base}/edge/edge_16x16.png`;
    if (value.includes('opera')) return `${base}/opera/opera_16x16.png`;
    if (value.includes('brave')) return `${base}/brave/brave_16x16.png`;

    return '';
}

function operatingSystemIcon(name) {
    if (!name) {
        return '';
    }

    const value = name.toLowerCase();

    if (value.includes('windows')) return 'https://img.icons8.com/color/16/windows-10.png';
    if (value.includes('mac') || value.includes('ios')) return 'https://img.icons8.com/color/16/mac-os.png';
    if (value.includes('linux') || value.includes('ubuntu')) return 'https://img.icons8.com/color/16/linux.png';
    if (value.includes('android')) return 'https://img.icons8.com/color/16/android-os.png';

    return '';
}

function utmIcon(name) {
    if (!name) {
        return '';
    }

    const value = name.toLowerCase();
    let domain = value;

    if (['google', 'facebook', 'twitter', 'linkedin', 'instagram', 'youtube', 'tiktok', 'pinterest', 'reddit'].includes(value)) {
        domain = `${value}.com`;
    }

    if (!domain.includes('.')) {
        return '';
    }

    return `https://www.google.com/s2/favicons?domain=${encodeURIComponent(domain)}&sz=16`;
}

function faviconUrl(domain) {
    return `https://www.google.com/s2/favicons?domain=${encodeURIComponent(domain)}&sz=16`;
}

function cell(text, className = '') {
    const element = document.createElement('td');
    element.textContent = text ?? '';
    element.className = className;

    return element;
}

function emptyRow(body, colspan, text) {
    const row = document.createElement('tr');
    const emptyCell = cell(text, 'p-dash__table-cell--center-lg');
    emptyCell.colSpan = colspan;
    row.append(emptyCell);
    body.append(row);
}

function icon(name, src) {
    if (!src) {
        return null;
    }

    const image = document.createElement('img');
    image.src = src;
    image.alt = '';
    image.className = 'p-dash__favicon';
    image.loading = 'lazy';
    image.dataset.icon = name;

    return image;
}

function initDashboardElement(root) {
    if (root.dataset.dashboardReady === 'true') {
        return;
    }

    root.dataset.dashboardReady = 'true';

    const statsUrl = root.dataset.statsUrl;
    const loading = root.querySelector('[data-dashboard-loading]');
    const errorBox = root.querySelector('[data-dashboard-error]');
    const errorText = root.querySelector('[data-dashboard-error-text]');
    const content = root.querySelector('[data-dashboard-content]');
    const siteSelect = root.querySelector('[data-dashboard-site]');
    const periodButtons = selectAll('[data-dashboard-period]', root);
    const periodSelect = root.querySelector('[data-dashboard-period-mobile]');

    let siteId = root.dataset.siteId ?? '';
    let period = root.dataset.period ?? '7d';
    let stats = null;
    let requestSequence = 0;
    let charts = {};
    let map = null;

    const setLoading = (state) => {
        if (loading) {
            loading.hidden = !state;
        }

        if (content) {
            content.hidden = state || errorBox?.hidden === false;
        }

        if (state && errorBox) {
            errorBox.hidden = true;
        }
    };

    const setStat = (name, value) => {
        selectAll(`[data-dashboard-stat="${name}"]`, root).forEach((element) => {
            element.textContent = value;
        });
    };

    const tableBody = (name) => {
        const body = root.querySelector(`[data-dashboard-table="${name}"]`);

        body?.replaceChildren();

        return body;
    };

    const palette = () => {
        return Array.from({ length: 5 }, (_, index) => {
            return cssVar(`--chart-${index + 1}`, FALLBACK_PALETTE[index]);
        });
    };

    const chart = (key, canvas, config) => {
        if (charts[key]) {
            charts[key].destroy();
            delete charts[key];
        }

        if (canvas) {
            charts[key] = new Chart(canvas, config);
        }
    };

    const renderSummary = () => {
        const summary = stats?.summary ?? {};

        setStat('unique_visitors', formatNumber(summary.unique_visitors));
        setStat('page_views', formatNumber(summary.page_views));
        setStat('bounce_rate', `${summary.bounce_rate ?? 0}%`);
        setStat('average_duration', formatDuration(summary.average_duration));
    };

    const renderEventSummary = () => {
        const events = stats?.events_by_name ?? [];
        const total = events.reduce((sum, event) => sum + (Number(event.total) || 0), 0);
        const sessions = events.reduce((sum, event) => sum + (Number(event.sessions) || 0), 0);

        setStat('total_events', formatNumber(total));
        setStat('event_types', formatNumber(events.length));
        setStat('active_sessions', formatNumber(sessions));
        setStat('events_per_session', sessions > 0 ? (total / sessions).toFixed(1) : '0');
    };

    const renderTopPages = () => {
        const body = tableBody('top_pages');
        const pages = (stats?.top_pages ?? []).slice(0, 8);

        if (!body) return;

        if (pages.length === 0) {
            emptyRow(body, 2, 'No data');
            return;
        }

        pages.forEach((page) => {
            const row = document.createElement('tr');
            row.append(
                cell(page.path ?? '—', 'p-dash__table-cell--truncate'),
                cell(formatNumber(page.views), 'p-dash__table-cell--right'),
            );
            body.append(row);
        });
    };

    const renderReferrers = () => {
        const body = tableBody('top_referrers');
        const referrers = (stats?.top_referrers ?? []).slice(0, 8);

        if (!body) return;

        if (referrers.length === 0) {
            emptyRow(body, 2, 'No referrer');
            return;
        }

        referrers.forEach((referrer) => {
            const row = document.createElement('tr');
            const sourceCell = document.createElement('td');
            const wrapper = document.createElement('div');
            wrapper.className = 'p-dash__list-name';
            const domain = referrer.referrer_domain ?? '';
            const favicon = icon('referrer', domain ? faviconUrl(domain) : '');
            const label = document.createElement('span');
            label.className = 'p-dash__table-cell--truncate';
            label.textContent = domain || 'Direct';

            if (favicon) {
                wrapper.append(favicon);
            }

            wrapper.append(label);
            sourceCell.append(wrapper);
            row.append(sourceCell, cell(formatNumber(referrer.visitors), 'p-dash__table-cell--right'));
            body.append(row);
        });
    };

    const renderParameterTable = (name, key, itemKey, emptyText = 'No data') => {
        const body = tableBody(name);
        const rows = (stats?.[key] ?? []).slice(0, 5);

        if (!body) return;

        if (rows.length === 0) {
            emptyRow(body, 2, emptyText);
            return;
        }

        rows.forEach((item) => {
            const row = document.createElement('tr');
            const labelCell = document.createElement('td');
            const label = item[itemKey] ?? '';

            if (name === 'utm_sources') {
                const wrapper = document.createElement('div');
                wrapper.className = 'p-dash__list-name';
                const favicon = icon('utm', utmIcon(label));

                if (favicon) {
                    wrapper.append(favicon);
                }

                const text = document.createElement('span');
                text.className = 'p-dash__table-cell--truncate';
                text.textContent = label || '—';
                wrapper.append(text);
                labelCell.append(wrapper);
            } else {
                labelCell.className = 'p-dash__table-cell--truncate';
                labelCell.textContent = label || '—';
            }

            row.append(labelCell, cell(formatNumber(item.visitors), 'p-dash__table-cell--right'));
            body.append(row);
        });
    };

    const renderBrowserTables = () => {
        const browserBody = tableBody('top_browsers');
        const browserRows = (stats?.top_browsers ?? []).slice(0, 5);

        if (browserBody) {
            if (browserRows.length === 0) {
                emptyRow(browserBody, 2, 'No data');
            } else {
                browserRows.forEach((browser) => {
                    const row = document.createElement('tr');
                    const labelCell = document.createElement('td');
                    const wrapper = document.createElement('div');
                    wrapper.className = 'p-dash__list-name';
                    const favicon = icon('browser', browserIcon(browser.browser));
                    const label = document.createElement('span');
                    label.className = 'p-dash__table-cell--truncate';
                    label.textContent = browser.browser ?? '—';

                    if (favicon) {
                        wrapper.append(favicon);
                    }

                    wrapper.append(label);
                    labelCell.append(wrapper);
                    row.append(labelCell, cell(formatNumber(browser.visitors), 'p-dash__table-cell--right'));
                    browserBody.append(row);
                });
            }
        }

        const osBody = tableBody('top_operating_systems');
        const osRows = (stats?.top_operating_systems ?? []).slice(0, 5);

        if (!osBody) return;

        if (osRows.length === 0) {
            emptyRow(osBody, 2, 'No data');
            return;
        }

        osRows.forEach((system) => {
            const row = document.createElement('tr');
            const labelCell = document.createElement('td');
            const wrapper = document.createElement('div');
            wrapper.className = 'p-dash__list-name';
            const favicon = icon('os', operatingSystemIcon(system.operating_system));
            const label = document.createElement('span');
            label.className = 'p-dash__table-cell--truncate';
            label.textContent = system.operating_system ?? '—';

            if (favicon) {
                wrapper.append(favicon);
            }

            wrapper.append(label);
            labelCell.append(wrapper);
            row.append(labelCell, cell(formatNumber(system.visitors), 'p-dash__table-cell--right'));
            osBody.append(row);
        });
    };

    const renderDevices = () => {
        const legend = root.querySelector('[data-dashboard-devices]');
        const devices = stats?.devices ?? [];

        if (!legend) return;

        legend.replaceChildren();

        if (devices.length === 0) {
            const empty = document.createElement('p');
            empty.className = 'p-text';
            empty.textContent = 'No data';
            legend.append(empty);
            return;
        }

        devices.forEach((device) => {
            const row = document.createElement('div');
            row.className = 'p-dash__device-row';
            const name = document.createElement('span');
            name.className = 'p-dash__device-name';
            name.textContent = device.device ?? '—';
            const count = document.createElement('span');
            count.className = 'p-dash__device-count';
            count.textContent = formatNumber(device.visitors);
            row.append(name, count);
            legend.append(row);
        });
    };

    const renderEventTypes = () => {
        const body = tableBody('event_types');
        const events = (stats?.events_by_name ?? []).slice(0, 5);
        const total = (stats?.events_by_name ?? []).reduce((sum, event) => sum + (Number(event.total) || 0), 0);
        const colors = palette();

        if (!body) return;

        if (events.length === 0) {
            emptyRow(body, 3, 'No data');
            return;
        }

        events.forEach((event, index) => {
            const row = document.createElement('tr');
            row.className = 'p-dash__evt-row';

            const nameCell = document.createElement('td');
            nameCell.className = 'p-row';
            const dot = document.createElement('div');
            dot.className = 'p-dash__evt-dot';
            dot.style.backgroundColor = colors[index % colors.length];
            const name = document.createElement('span');
            name.className = 'p-dash__evt-name';
            name.textContent = event.name ?? '—';
            nameCell.append(dot, name);

            const percentage = total > 0 ? Math.round((Number(event.total) / total) * 100) : 0;

            row.append(
                nameCell,
                cell(formatNumber(event.total), 'p-dash__table-cell--right'),
                cell(`${percentage}%`, 'p-dash__table-cell--muted'),
            );
            body.append(row);
        });
    };

    const renderEventPages = () => {
        const body = tableBody('event_pages');
        const pages = (stats?.events_by_page ?? []).slice(0, 6);

        if (!body) return;

        if (pages.length === 0) {
            emptyRow(body, 2, 'No contextual data');
            return;
        }

        pages.forEach((page) => {
            const row = document.createElement('tr');
            row.append(
                cell(page.path ?? '—', 'p-dash__table-cell--truncate'),
                cell(formatNumber(page.total), 'p-dash__table-cell--mono'),
            );
            body.append(row);
        });
    };

    const renderLatestEvents = () => {
        const body = tableBody('latest_events');
        const events = (stats?.latest_events ?? []).slice(0, 8);

        if (!body) return;

        if (events.length === 0) {
            emptyRow(body, 3, 'No events yet');
            return;
        }

        events.forEach((event) => {
            const row = document.createElement('tr');
            const nameCell = document.createElement('td');
            const badge = document.createElement('span');
            badge.className = 'p-dash__evt-badge';
            badge.textContent = event.name ?? '—';
            nameCell.append(badge);
            row.append(
                nameCell,
                cell(event.path ?? '—', 'p-dash__table-cell--truncate p-dash__table-cell--path'),
                cell(formatDateTime(event.created_at), 'p-dash__table-cell--muted'),
            );
            body.append(row);
        });
    };

    const renderTrafficChart = () => {
        const canvas = root.querySelector('[data-dashboard-chart="traffic"]');
        const data = stats?.evolution ?? [];

        chart('traffic', canvas, {
            type: 'line',
            data: {
                labels: data.map((entry) => formatDay(entry.date)),
                datasets: [
                    {
                        label: 'Visitors',
                        data: data.map((entry) => entry.visitors),
                        borderColor: cssVar('--chart-traffic-1', '#ff7711'),
                        backgroundColor: cssVar('--chart-traffic-fill-1', 'rgba(55, 65, 81, 0.05)'),
                        fill: true,
                        tension: 0.3,
                        borderWidth: 2,
                        pointRadius: 2,
                    },
                    {
                        label: 'Page views',
                        data: data.map((entry) => entry.page_views),
                        borderColor: cssVar('--chart-traffic-2', '#9933ff'),
                        backgroundColor: cssVar('--chart-traffic-fill-2', 'rgba(156, 163, 175, 0.05)'),
                        fill: true,
                        tension: 0.3,
                        borderWidth: 2,
                        pointRadius: 2,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { intersect: false, mode: 'index' },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { usePointStyle: true, padding: 15, font: { size: 11 } },
                    },
                },
            },
        });
    };

    const renderDevicesChart = () => {
        const canvas = root.querySelector('[data-dashboard-chart="devices"]');
        const data = stats?.devices ?? [];

        chart('devices', canvas, {
            type: 'doughnut',
            data: {
                labels: data.map((device) => device.device),
                datasets: [{
                    data: data.map((device) => device.visitors),
                    backgroundColor: [
                        cssVar('--chart-device-1', '#ffaabb'),
                        cssVar('--chart-device-2', '#aaffbb'),
                        cssVar('--chart-device-3', '#aabbff'),
                        cssVar('--chart-device-4', '#666666'),
                    ],
                    borderWidth: 0,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                cutout: '60%',
                plugins: { legend: { display: false } },
            },
        });
    };

    const renderEventsChart = () => {
        const canvas = root.querySelector('[data-dashboard-chart="events"]');
        const data = stats?.events_by_day ?? [];

        chart('events', canvas, {
            type: 'line',
            data: {
                labels: data.map((entry) => formatDay(entry.date)),
                datasets: [{
                    label: 'Events',
                    data: data.map((entry) => entry.total),
                    borderColor: cssVar('--chart-events', '#6366f1'),
                    backgroundColor: cssVar('--chart-events-fill', 'rgba(99, 102, 241, 0.1)'),
                    fill: true,
                    tension: 0.4,
                    borderWidth: 3,
                    pointRadius: 3,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { display: false } },
                    x: { grid: { display: false } },
                },
            },
        });
    };

    const renderEventTypesChart = () => {
        const canvas = root.querySelector('[data-dashboard-chart="event-types"]');
        const data = stats?.events_by_name ?? [];
        const colors = palette();

        chart('event-types', canvas, {
            type: 'doughnut',
            data: {
                labels: data.map((event) => event.name),
                datasets: [{
                    data: data.map((event) => event.total),
                    backgroundColor: colors,
                    borderWidth: 2,
                    borderColor: cssVar('--chart-border', '#ffffff'),
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                cutout: '75%',
                plugins: { legend: { display: false } },
            },
        });
    };

    const renderMap = () => {
        const element = root.querySelector('[data-dashboard-map]');

        if (!element) {
            return;
        }

        map?.destroy();

        const values = {};

        (stats?.top_countries ?? []).forEach((country) => {
            const code = String(country.country_code ?? '').toUpperCase();

            if (code !== '') {
                values[code] = country.visitors;
            }
        });

        map = new jsVectorMap({
            selector: element,
            map: 'world',
            backgroundColor: cssVar('--map-background', 'transparent'),
            zoomOnScroll: false,
            regionStyle: {
                initial: {
                    fill: cssVar('--map-region', '#e5e7eb'),
                    stroke: cssVar('--map-region-stroke', '#ffffff'),
                    strokeWidth: 1,
                    fillOpacity: 1,
                },
                hover: {
                    fill: cssVar('--map-region-hover', '#34d399'),
                    cursor: 'pointer',
                },
            },
            visualizeData: {
                scale: [
                    cssVar('--map-scale-start', '#a7f3d0'),
                    cssVar('--map-scale-end', '#047857'),
                ],
                values,
            },
            onRegionTooltipShow(event, tooltip, code) {
                const count = values[code] || 0;
                const label = count === 1 ? 'visitor' : 'visitors';

                tooltip.text(
                    `<div class="p-dash__map-tooltip">`
                    + `<div class="p-dash__map-tooltip-title">${tooltip.text()}</div>`
                    + `<div class="p-dash__map-tooltip-value">${count} ${label}</div>`
                    + `</div>`,
                    true,
                );
            },
        });
    };

    const renderAll = () => {
        renderSummary();
        renderEventSummary();
        renderTopPages();
        renderReferrers();
        renderParameterTable('utm_sources', 'top_utm_sources', 'utm_source');
        renderParameterTable('utm_mediums', 'top_utm_mediums', 'utm_medium');
        renderParameterTable('utm_campaigns', 'top_utm_campaigns', 'utm_campaign');
        renderBrowserTables();
        renderDevices();
        renderEventTypes();
        renderEventPages();
        renderLatestEvents();
        renderTrafficChart();
        renderDevicesChart();
        renderEventsChart();
        renderEventTypesChart();
        renderMap();
    };

    const syncPeriod = () => {
        periodButtons.forEach((button) => {
            button.classList.toggle('p-dash__period-btn--active', button.dataset.dashboardPeriod === period);
        });

        if (periodSelect) {
            periodSelect.value = period;
        }
    };

    const loadStats = async () => {
        const currentRequest = ++requestSequence;

        setLoading(true);

        try {
            const query = new URLSearchParams({ site_id: siteId, period });
            const response = await requestJson(`${statsUrl}?${query.toString()}`);

            if (currentRequest !== requestSequence) {
                return;
            }

            stats = response;
            setLoading(false);

            if (content) {
                content.hidden = false;
            }

            window.requestAnimationFrame(renderAll);
        } catch (error) {
            if (currentRequest !== requestSequence) {
                return;
            }

            stats = null;

            if (loading) {
                loading.hidden = true;
            }

            if (content) {
                content.hidden = true;
            }

            if (errorBox) {
                errorBox.hidden = false;
            }

            if (errorText) {
                errorText.textContent = error.message;
            }
        }
    };

    periodButtons.forEach((button) => {
        button.addEventListener('click', () => {
            if (period === button.dataset.dashboardPeriod) {
                return;
            }

            period = button.dataset.dashboardPeriod;
            root.dataset.period = period;
            syncPeriod();
            loadStats();
        });
    });

    periodSelect?.addEventListener('change', () => {
        period = periodSelect.value;
        root.dataset.period = period;
        syncPeriod();
        loadStats();
    });

    siteSelect?.addEventListener('change', () => {
        siteId = siteSelect.value;
        loadStats();
    });

    document.addEventListener('flux:tab-changed', (event) => {
        if (event.detail.group !== 'dashboard') {
            return;
        }

        window.setTimeout(() => {
            Object.values(charts).forEach((instance) => instance?.resize());
            map?.updateSize?.();
        }, 50);
    });

    syncPeriod();
    loadStats();
}

export function initDashboard(root = document) {
    const found = new Set();
    const self = root.matches?.('[data-dashboard]') ? root : null;

    if (self) {
        found.add(self);
    }

    selectAll('[data-dashboard]', root).forEach((element) => found.add(element));

    found.forEach(initDashboardElement);
}
