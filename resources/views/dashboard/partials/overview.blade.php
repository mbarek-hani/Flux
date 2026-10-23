<div class="p-dash__canvas">
    <section class="p-dash__kpis" aria-label="Key metrics">
        <x-stats-card title="Unique visitors" icon="users" class="p-dash__kpi">
            <x-slot:value>
                <span data-dashboard-stat="unique_visitors">0</span>
            </x-slot:value>
            <x-slot:trend>
                <span class="c-stats-card__trend" data-dashboard-trend="unique_visitors" hidden></span>
            </x-slot:trend>
            <x-slot:meta>
                <span data-dashboard-meta="new_sessions">0 new sessions</span>
            </x-slot:meta>
        </x-stats-card>

        <x-stats-card title="Page views" icon="eye" class="p-dash__kpi">
            <x-slot:value>
                <span data-dashboard-stat="page_views">0</span>
            </x-slot:value>
            <x-slot:trend>
                <span class="c-stats-card__trend" data-dashboard-trend="page_views" hidden></span>
            </x-slot:trend>
            <x-slot:meta>
                <span data-dashboard-meta="pages_per_visitor">0 pages / visitor</span>
            </x-slot:meta>
        </x-stats-card>

        <x-stats-card title="Bounce rate" icon="arrow-trending-down" class="p-dash__kpi">
            <x-slot:value>
                <span data-dashboard-stat="bounce_rate">0%</span>
            </x-slot:value>
            <x-slot:trend>
                <span class="c-stats-card__trend" data-dashboard-trend="bounce_rate" hidden></span>
            </x-slot:trend>
            <x-slot:meta>
                <span data-dashboard-meta="bounce_context">Across all sessions</span>
            </x-slot:meta>
        </x-stats-card>

        <x-stats-card title="Average duration" icon="clock" class="p-dash__kpi">
            <x-slot:value>
                <span data-dashboard-stat="average_duration">0s</span>
            </x-slot:value>
            <x-slot:trend>
                <span class="c-stats-card__trend" data-dashboard-trend="average_duration" hidden></span>
            </x-slot:trend>
            <x-slot:meta>
                <span data-dashboard-meta="duration_context">Engagement per visit</span>
            </x-slot:meta>
        </x-stats-card>
    </section>

    <div class="p-dash__grid">
        <x-card title="Traffic trend" class="p-dash__panel p-dash__span-8">
            <x-slot:actions>
                <div class="p-dash__metric-toggle" role="group" aria-label="Traffic metric">
                    <button
                        type="button"
                        class="p-dash__metric-btn is-active"
                        data-dashboard-traffic-metric="visitors"
                        aria-pressed="true"
                    >
                        Visitors
                    </button>
                    <button
                        type="button"
                        class="p-dash__metric-btn"
                        data-dashboard-traffic-metric="page_views"
                        aria-pressed="false"
                    >
                        Page views
                    </button>
                </div>
            </x-slot:actions>

            <div class="p-dash__chart p-dash__chart--traffic">
                <canvas data-dashboard-chart="traffic"></canvas>
            </div>
            <p class="p-dash__chart-note" data-dashboard-chart-note hidden></p>
        </x-card>

        <x-card title="At a glance" class="p-dash__panel p-dash__span-4">
            <div class="p-dash__glance">
                <div class="p-dash__glance-item">
                    <span class="p-dash__glance-label">New sessions</span>
                    <strong class="p-dash__glance-value" data-dashboard-glance="new_sessions">0</strong>
                </div>
                <div class="p-dash__glance-item">
                    <span class="p-dash__glance-label">Returning visitors</span>
                    <strong class="p-dash__glance-value" data-dashboard-glance="returning_visitors">0</strong>
                </div>
                <div class="p-dash__glance-item">
                    <span class="p-dash__glance-label">Pages / visitor</span>
                    <strong class="p-dash__glance-value" data-dashboard-glance="pages_per_visitor">0</strong>
                </div>
                <div class="p-dash__glance-item">
                    <span class="p-dash__glance-label">Events</span>
                    <strong class="p-dash__glance-value" data-dashboard-glance="total_events">0</strong>
                    <span class="p-dash__glance-meta" data-dashboard-glance="event_sessions">Across 0 sessions</span>
                </div>
            </div>
        </x-card>

        <x-card title="Top content" :padding="false" class="p-dash__panel p-dash__span-7">
            <div class="p-dash__rank-list" data-dashboard-list="top_pages"></div>
        </x-card>

        <x-card title="Acquisition" :padding="false" class="p-dash__panel p-dash__span-5">
            <div data-tabs="acquisition" class="p-dash__subtabs-wrap">
                <div class="p-dash__subtabs">
                    <nav class="p-dash__subtab-nav" role="tablist" aria-label="Acquisition dimension">
                        <button
                            type="button"
                            class="p-dash__subtab is-active"
                            data-tab-target="acquisition-referrers"
                            role="tab"
                            aria-selected="true"
                        >
                            Referrers
                        </button>
                        <button
                            type="button"
                            class="p-dash__subtab"
                            data-tab-target="acquisition-source"
                            role="tab"
                            aria-selected="false"
                            tabindex="-1"
                        >
                            Source
                        </button>
                        <button
                            type="button"
                            class="p-dash__subtab"
                            data-tab-target="acquisition-medium"
                            role="tab"
                            aria-selected="false"
                            tabindex="-1"
                        >
                            Medium
                        </button>
                        <button
                            type="button"
                            class="p-dash__subtab"
                            data-tab-target="acquisition-campaign"
                            role="tab"
                            aria-selected="false"
                            tabindex="-1"
                        >
                            Campaign
                        </button>
                    </nav>
                </div>

                <div data-tab-panel="acquisition-referrers">
                    <div class="p-dash__rank-list" data-dashboard-list="acquisition_referrers"></div>
                </div>

                <div data-tab-panel="acquisition-source" hidden>
                    <div class="p-dash__rank-list" data-dashboard-list="acquisition_sources"></div>
                </div>

                <div data-tab-panel="acquisition-medium" hidden>
                    <div class="p-dash__rank-list" data-dashboard-list="acquisition_mediums"></div>
                </div>

                <div data-tab-panel="acquisition-campaign" hidden>
                    <div class="p-dash__rank-list" data-dashboard-list="acquisition_campaigns"></div>
                </div>
            </div>
        </x-card>

        <x-card title="Geography" class="p-dash__panel p-dash__span-7">
            <div class="p-dash__geo">
                <div class="p-dash__map" data-dashboard-map></div>
                <div class="p-dash__rank-list p-dash__rank-list--compact" data-dashboard-list="countries"></div>
            </div>
        </x-card>

        <x-card title="Audience" class="p-dash__panel p-dash__span-5">
            <div class="p-dash__audience">
                <div class="p-dash__devices">
                    <div class="p-dash__devices-chart">
                        <canvas data-dashboard-chart="devices"></canvas>
                    </div>
                    <div class="p-dash__devices-legend" data-dashboard-devices></div>
                </div>

                <div class="p-dash__tech">
                    <div class="p-dash__tech-block">
                        <h4 class="p-dash__tech-title">Browsers</h4>
                        <div class="p-dash__rank-list p-dash__rank-list--compact" data-dashboard-list="browsers"></div>
                    </div>
                    <div class="p-dash__tech-block">
                        <h4 class="p-dash__tech-title">Operating systems</h4>
                        <div class="p-dash__rank-list p-dash__rank-list--compact" data-dashboard-list="operating_systems"></div>
                    </div>
                </div>
            </div>
        </x-card>

        <x-card title="Recent events" :padding="false" class="p-dash__panel p-dash__span-12">
            <div class="p-dash__activity" data-dashboard-list="latest_events_overview"></div>
        </x-card>
    </div>
</div>

@hook('dashboard.widgets')
