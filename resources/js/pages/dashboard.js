import Chart from 'chart.js/auto';
import jsVectorMap from 'jsvectormap';
import 'jsvectormap/dist/maps/world.js';

import { selectAll } from '../core/dom';
import { requestJson } from '../core/http';

const FALLBACK_PALETTE = ['#6366f1', '#8b5cf6', '#ec4899', '#f59e0b', '#10b981', '#06b6d4'];

const TRAFFIC_METRICS = {
    visitors: {
        label: 'Visitors',
        color: '--chart-traffic-1',
        fallback: '#ff7711',
        fill: '--chart-traffic-fill-1',
        fallbackFill: 'rgba(55, 65, 81, 0.08)',
    },
    page_views: {
        label: 'Page views',
        color: '--chart-traffic-2',
        fallback: '#9933ff',
        fill: '--chart-traffic-fill-2',
        fallbackFill: 'rgba(156, 163, 175, 0.08)',
    },
};

function cssVar(name, fallback) {
    const value = getComputedStyle(document.documentElement).getPropertyValue(name).trim();

    return value === '' ? fallback : value;
}

function formatNumber(value) {
    return new Intl.NumberFormat('en-GB').format(Number(value) || 0);
}

function formatDecimal(value) {
    return new Intl.NumberFormat('en-GB', {
        minimumFractionDigits: 1,
        maximumFractionDigits: 1,
    }).format(Number(value) || 0);
}

function formatPercent(value) {
    return `${new Intl.NumberFormat('en-GB', {
        maximumFractionDigits: 1,
    }).format(Number(value) || 0)}%`;
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

function emptyState(text) {
    const element = document.createElement('p');
    element.className = 'p-dash__empty';
    element.textContent = text;

    return element;
}

function deltaFor(current, previous) {
    const currentValue = Number(current) || 0;
    const previousValue = Number(previous) || 0;

    if (previousValue <= 0) {
        return null;
    }

    return ((currentValue - previousValue) / previousValue) * 100;
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
    const compareButton = root.querySelector('[data-dashboard-compare]');
    const metricButtons = selectAll('[data-dashboard-traffic-metric]', root);

    let siteId = root.dataset.siteId ?? '';
    let period = root.dataset.period ?? '7d';
    let stats = null;
    let requestSequence = 0;
    let charts = {};
    let map = null;
    let compareEnabled = false;
    let trafficMetric = 'visitors';

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

    const setMeta = (name, value) => {
        selectAll(`[data-dashboard-meta="${name}"]`, root).forEach((element) => {
            element.textContent = value;
        });
    };

    const setGlance = (name, value) => {
        selectAll(`[data-dashboard-glance="${name}"]`, root).forEach((element) => {
            element.textContent = value;
        });
    };

    const listContainer = (name) => root.querySelector(`[data-dashboard-list="${name}"]`);

    const palette = () => {
        return Array.from({ length: 6 }, (_, index) => {
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

    const renderRankList = (name, items, options = {}) => {
        const container = listContainer(name);

        if (!container) {
            return;
        }

        container.replaceChildren();

        const rows = Array.isArray(items) ? items.slice(0, options.limit ?? 6) : [];

        if (rows.length === 0) {
            container.append(emptyState(options.empty ?? 'No data'));
            return;
        }

        const values = rows.map((row) => Number(row?.[options.valueKey] ?? 0) || 0);
        const max = Math.max(...values, 0);
        const total = options.shareBase ?? values.reduce((sum, value) => sum + value, 0);

        rows.forEach((row, index) => {
            const value = values[index];
            const labelText = String(options.label?.(row) ?? row?.[options.labelKey] ?? '—');
            const metaText = String(options.meta?.(row) ?? '');
            const iconUrl = options.icon?.(row) ?? '';
            const share = total > 0 ? (value / total) * 100 : 0;
            const width = max > 0 ? (value / max) * 100 : 0;

            const item = document.createElement('div');
            item.className = 'p-dash__rank';

            const top = document.createElement('div');
            top.className = 'p-dash__rank-top';

            const labelWrap = document.createElement('div');
            labelWrap.className = 'p-dash__rank-label-wrap';

            if (options.color) {
                const dot = document.createElement('span');
                dot.className = 'p-dash__evt-dot';
                dot.style.backgroundColor = options.color(row, index);
                labelWrap.append(dot);
            }

            if (iconUrl) {
                const image = document.createElement('img');
                image.src = iconUrl;
                image.alt = '';
                image.loading = 'lazy';
                image.className = 'p-dash__favicon';
                labelWrap.append(image);
            }

            const label = document.createElement('span');
            label.className = 'p-dash__rank-label';
            label.textContent = labelText || '—';
            label.title = labelText;
            labelWrap.append(label);

            const valueElement = document.createElement('span');
            valueElement.className = 'p-dash__rank-value';
            valueElement.textContent = options.formatValue?.(value) ?? formatNumber(value);
            top.append(labelWrap, valueElement);

            const meta = document.createElement('div');
            meta.className = 'p-dash__rank-meta';

            const metaLabel = document.createElement('span');
            metaLabel.textContent = metaText;
            const shareLabel = document.createElement('span');
            shareLabel.className = 'p-dash__rank-share';
            shareLabel.textContent = formatPercent(share);
            meta.append(metaLabel, shareLabel);

            const bar = document.createElement('div');
            bar.className = 'p-dash__rank-bar';
            const fill = document.createElement('span');
            fill.className = 'p-dash__rank-fill';
            fill.style.width = `${Math.max(0, Math.min(100, width))}%`;
            bar.append(fill);

            item.append(top, meta, bar);
            container.append(item);
        });
    };

    const renderActivity = (name, items, limit = 8) => {
        const container = listContainer(name);

        if (!container) {
            return;
        }

        container.replaceChildren();

        const events = Array.isArray(items) ? items.slice(0, limit) : [];

        if (events.length === 0) {
            container.append(emptyState('No events yet'));
            return;
        }

        events.forEach((event) => {
            const row = document.createElement('div');
            row.className = 'p-dash__activity-row';

            const badge = document.createElement('span');
            badge.className = 'p-dash__evt-badge';
            badge.textContent = event.name ?? '—';

            const path = document.createElement('span');
            path.className = 'p-dash__activity-path';
            path.textContent = event.path ?? '—';
            path.title = event.path ?? '';

            const time = document.createElement('span');
            time.className = 'p-dash__activity-time';
            time.textContent = formatDateTime(event.created_at);

            row.append(badge, path, time);
            container.append(row);
        });
    };

    const renderTrends = () => {
        const summary = stats?.summary ?? {};
        const previous = stats?.comparison?.summary ?? {};

        selectAll('[data-dashboard-trend]', root).forEach((element) => {
            const key = element.dataset.dashboardTrend;
            const delta = compareEnabled ? deltaFor(summary?.[key], previous?.[key]) : null;

            element.classList.remove('is-good', 'is-bad', 'is-neutral');
            element.hidden = delta === null;

            if (delta === null) {
                element.textContent = '';
                return;
            }

            const rounded = Math.abs(delta) < 0.05 ? 0 : delta;
            const sign = rounded > 0 ? '+' : '';
            element.textContent = `${sign}${new Intl.NumberFormat('en-GB', {
                maximumFractionDigits: 1,
            }).format(rounded)}%`;

            if (rounded === 0) {
                element.classList.add('is-neutral');
            } else {
                const lowerIsBetter = key === 'bounce_rate';
                const good = lowerIsBetter ? rounded < 0 : rounded > 0;
                element.classList.add(good ? 'is-good' : 'is-bad');
            }

            element.title = 'Compared with the previous period';
        });
    };

    const renderChartNote = () => {
        const note = root.querySelector('[data-dashboard-chart-note]');

        if (!note) {
            return;
        }

        const previousPeriod = stats?.comparison?.period;

        if (!compareEnabled || !previousPeriod) {
            note.hidden = true;
            note.textContent = '';
            return;
        }

        note.hidden = false;
        note.textContent = `Compared with ${previousPeriod.start} – ${previousPeriod.end}`;
    };

    const renderSummary = () => {
        const summary = stats?.summary ?? {};
        const visitors = Number(summary.unique_visitors) || 0;
        const pageViews = Number(summary.page_views) || 0;
        const newSessions = Number(summary.new_sessions) || 0;

        setStat('unique_visitors', formatNumber(visitors));
        setStat('page_views', formatNumber(pageViews));
        setStat('bounce_rate', `${summary.bounce_rate ?? 0}%`);
        setStat('average_duration', formatDuration(summary.average_duration));

        setMeta('new_sessions', `${formatNumber(newSessions)} new sessions`);
        setMeta('pages_per_visitor', `${formatDecimal(visitors > 0 ? pageViews / visitors : 0)} pages / visitor`);
        setMeta('bounce_context', `Across ${formatNumber(visitors)} visitors`);

        renderTrends();
        renderChartNote();
    };

    const renderGlance = () => {
        const summary = stats?.summary ?? {};
        const eventSummary = stats?.event_summary ?? {};
        const visitors = Number(summary.unique_visitors) || 0;
        const pageViews = Number(summary.page_views) || 0;
        const newSessions = Number(summary.new_sessions) || 0;
        const eventSessions = Number(eventSummary.sessions_with_events) || 0;

        setGlance('new_sessions', formatNumber(newSessions));
        setGlance('returning_visitors', formatNumber(Math.max(visitors - newSessions, 0)));
        setGlance('pages_per_visitor', formatDecimal(visitors > 0 ? pageViews / visitors : 0));
        setGlance('total_events', formatNumber(eventSummary.total_events));
        setGlance('event_sessions', `Across ${formatNumber(eventSessions)} sessions`);
    };

    const renderEventSummary = () => {
        const summary = stats?.summary ?? {};
        const eventSummary = stats?.event_summary ?? {};
        const visitors = Number(summary.unique_visitors) || 0;
        const eventSessions = Number(eventSummary.sessions_with_events) || 0;
        const share = visitors > 0 ? Math.round((eventSessions / visitors) * 100) : 0;

        setStat('total_events', formatNumber(eventSummary.total_events));
        setStat('events_per_session', formatDecimal(eventSummary.events_per_session));
        setStat('event_types', formatNumber(eventSummary.event_types));
        setStat('sessions_with_events', formatNumber(eventSessions));

        setMeta('event_sessions', `Across ${formatNumber(eventSessions)} sessions`);
        setMeta('event_session_share', `${share}% of visitors`);
    };

    const renderDevices = () => {
        const legend = root.querySelector('[data-dashboard-devices]');
        const devices = stats?.devices ?? [];

        if (!legend) {
            return;
        }

        legend.replaceChildren();

        if (devices.length === 0) {
            legend.append(emptyState('No data'));
            return;
        }

        const total = devices.reduce((sum, device) => sum + (Number(device.visitors) || 0), 0);

        devices.forEach((device) => {
            const row = document.createElement('div');
            row.className = 'p-dash__device-row';

            const name = document.createElement('span');
            name.className = 'p-dash__device-name';
            name.textContent = device.device ?? '—';

            const values = document.createElement('span');
            values.className = 'p-dash__device-values';

            const count = document.createElement('span');
            count.textContent = formatNumber(device.visitors);

            const share = document.createElement('span');
            share.className = 'p-dash__device-share';
            share.textContent = formatPercent(total > 0 ? (Number(device.visitors) / total) * 100 : 0);

            values.append(count, share);
            row.append(name, values);
            legend.append(row);
        });
    };

    const renderLists = () => {
        const summary = stats?.summary ?? {};
        const eventSummary = stats?.event_summary ?? {};
        const totalVisitors = Number(summary.unique_visitors) || 0;
        const totalPageViews = Number(summary.page_views) || 0;
        const totalEvents = Number(eventSummary.total_events) || 0;
        const colors = palette();

        renderRankList('top_pages', stats?.top_pages, {
            limit: 6,
            labelKey: 'path',
            valueKey: 'views',
            shareBase: totalPageViews,
            meta: (row) => `${formatNumber(row.visitors)} visitors`,
            empty: 'No page data',
        });

        renderRankList('acquisition_referrers', stats?.top_referrers, {
            limit: 6,
            labelKey: 'referrer_domain',
            valueKey: 'visitors',
            label: (row) => row.referrer_domain || 'Direct',
            icon: (row) => row.referrer_domain ? faviconUrl(row.referrer_domain) : '',
            empty: 'No referrer data',
        });

        renderRankList('acquisition_sources', stats?.top_utm_sources, {
            limit: 6,
            labelKey: 'utm_source',
            valueKey: 'visitors',
            icon: (row) => utmIcon(row.utm_source),
            empty: 'No source data',
        });

        renderRankList('acquisition_mediums', stats?.top_utm_mediums, {
            limit: 6,
            labelKey: 'utm_medium',
            valueKey: 'visitors',
            empty: 'No medium data',
        });

        renderRankList('acquisition_campaigns', stats?.top_utm_campaigns, {
            limit: 6,
            labelKey: 'utm_campaign',
            valueKey: 'visitors',
            empty: 'No campaign data',
        });

        renderRankList('countries', stats?.top_countries, {
            limit: 5,
            labelKey: 'country_name',
            valueKey: 'visitors',
            shareBase: totalVisitors,
            label: (row) => row.country_name || row.country_code || 'Unknown',
            meta: (row) => row.country_code || '',
            empty: 'No country data',
        });

        renderRankList('browsers', stats?.top_browsers, {
            limit: 5,
            labelKey: 'browser',
            valueKey: 'visitors',
            icon: (row) => browserIcon(row.browser),
            empty: 'No browser data',
        });

        renderRankList('operating_systems', stats?.top_operating_systems, {
            limit: 5,
            labelKey: 'operating_system',
            valueKey: 'visitors',
            icon: (row) => operatingSystemIcon(row.operating_system),
            empty: 'No system data',
        });

        renderRankList('event_types', stats?.events_by_name, {
            limit: 5,
            labelKey: 'name',
            valueKey: 'total',
            shareBase: totalEvents,
            meta: (row) => `${formatNumber(row.sessions)} sessions`,
            color: (row, index) => colors[index % colors.length],
            empty: 'No event data',
        });

        renderRankList('event_pages', stats?.events_by_page, {
            limit: 6,
            labelKey: 'path',
            valueKey: 'total',
            shareBase: totalEvents,
            meta: () => 'events',
            empty: 'No contextual data',
        });

        renderActivity('latest_events_overview', stats?.latest_events, 6);
        renderActivity('latest_events', stats?.latest_events, 8);
    };

    const renderTrafficChart = () => {
        const canvas = root.querySelector('[data-dashboard-chart="traffic"]');
        const current = stats?.evolution ?? [];
        const previous = stats?.comparison?.evolution ?? [];
        const metric = TRAFFIC_METRICS[trafficMetric] ?? TRAFFIC_METRICS.visitors;
        const datasets = [{
            label: metric.label,
            data: current.map((entry) => entry[trafficMetric]),
            borderColor: cssVar(metric.color, metric.fallback),
            backgroundColor: cssVar(metric.fill, metric.fallbackFill),
            fill: true,
            tension: 0.35,
            borderWidth: 2,
            pointRadius: 0,
            pointHoverRadius: 3,
        }];

        if (compareEnabled && previous.length > 0) {
            datasets.push({
                label: `Previous ${metric.label.toLowerCase()}`,
                data: previous.map((entry) => entry[trafficMetric]),
                borderColor: cssVar('--gray-400', '#9ca3af'),
                backgroundColor: 'transparent',
                fill: false,
                tension: 0.35,
                borderWidth: 1.5,
                borderDash: [5, 5],
                pointRadius: 0,
                pointHoverRadius: 3,
            });
        }

        chart('traffic', canvas, {
            type: 'line',
            data: {
                labels: current.map((entry) => formatDay(entry.date)),
                datasets,
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { intersect: false, mode: 'index' },
                plugins: {
                    legend: {
                        display: compareEnabled,
                        position: 'bottom',
                        labels: { usePointStyle: true, padding: 14, font: { size: 11 } },
                    },
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { maxTicksLimit: 6, maxRotation: 0, autoSkip: true },
                    },
                    y: {
                        beginAtZero: true,
                        border: { display: false },
                        grid: { color: cssVar('--chart-grid', 'rgba(107, 114, 128, 0.15)') },
                        ticks: { precision: 0, maxTicksLimit: 6 },
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
                maintainAspectRatio: false,
                cutout: '64%',
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
                    tension: 0.35,
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 3,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { maxTicksLimit: 6, maxRotation: 0, autoSkip: true },
                    },
                    y: {
                        beginAtZero: true,
                        border: { display: false },
                        grid: { color: cssVar('--chart-grid', 'rgba(107, 114, 128, 0.15)') },
                        ticks: { precision: 0, maxTicksLimit: 6 },
                    },
                },
            },
        });
    };

    const renderEventTypesChart = () => {
        const canvas = root.querySelector('[data-dashboard-chart="event-types"]');
        const data = (stats?.events_by_name ?? []).slice(0, 6);

        chart('event-types', canvas, {
            type: 'doughnut',
            data: {
                labels: data.map((event) => event.name),
                datasets: [{
                    data: data.map((event) => event.total),
                    backgroundColor: palette(),
                    borderWidth: 2,
                    borderColor: cssVar('--chart-border', '#ffffff'),
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
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
        map = null;
        element.replaceChildren();

        if (element.offsetWidth === 0 || element.offsetHeight === 0) {
            return;
        }

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

    const syncTrafficMetric = () => {
        metricButtons.forEach((button) => {
            const active = button.dataset.dashboardTrafficMetric === trafficMetric;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
    };

    const renderAll = () => {
        renderSummary();
        renderGlance();
        renderEventSummary();
        renderLists();
        renderDevices();
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

    compareButton?.addEventListener('click', () => {
        compareEnabled = !compareEnabled;
        compareButton.classList.toggle('is-active', compareEnabled);
        compareButton.setAttribute('aria-pressed', compareEnabled ? 'true' : 'false');
        renderSummary();
        renderTrafficChart();
    });

    metricButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const metric = button.dataset.dashboardTrafficMetric;

            if (!metric || metric === trafficMetric) {
                return;
            }

            trafficMetric = metric;
            syncTrafficMetric();
            renderTrafficChart();
        });
    });

    let resizeFrame = null;

    window.addEventListener('resize', () => {
        if (resizeFrame !== null) {
            window.cancelAnimationFrame(resizeFrame);
        }

        resizeFrame = window.requestAnimationFrame(() => {
            resizeFrame = null;
            map?.updateSize?.();
        });
    }, { passive: true });

    document.addEventListener('flux:tab-changed', (event) => {
        if (event.detail.group !== 'dashboard') {
            return;
        }

        window.requestAnimationFrame(() => {
            Object.values(charts).forEach((instance) => instance?.resize());

            if (event.detail.target !== 'overview') {
                return;
            }

            const element = root.querySelector('[data-dashboard-map]');

            if (!element || element.offsetWidth === 0 || element.offsetHeight === 0) {
                map?.destroy();
                map = null;

                return;
            }

            if (map) {
                map.updateSize?.();
            } else {
                renderMap();
            }
        });
    });

    syncPeriod();
    syncTrafficMetric();
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
