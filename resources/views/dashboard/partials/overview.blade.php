<div class="p-grid p-grid--2 p-grid--4 p-mb-3">
    <x-stats-card title="Unique visitors" icon="users">
        <x-slot:value>
            <span data-dashboard-stat="unique_visitors">0</span>
        </x-slot:value>
    </x-stats-card>
    <x-stats-card title="Page views" icon="eye">
        <x-slot:value>
            <span data-dashboard-stat="page_views">0</span>
        </x-slot:value>
    </x-stats-card>
    <x-stats-card title="Bounce rate" icon="arrow-trending-down">
        <x-slot:value>
            <span data-dashboard-stat="bounce_rate">0%</span>
        </x-slot:value>
    </x-stats-card>
    <x-stats-card title="Average duration" icon="clock">
        <x-slot:value>
            <span data-dashboard-stat="average_duration">0s</span>
        </x-slot:value>
    </x-stats-card>
</div>

<x-card title="Traffic evolution" class="p-mb-3">
    <div class="p-dash__chart">
        <canvas data-dashboard-chart="traffic"></canvas>
    </div>
</x-card>

<div class="p-dash__grid-2">
    <x-card title="Popular pages" :padding="false">
        <table class="p-dash__table">
            <thead>
                <tr>
                    <th>Page</th>
                    <th class="p-dash__table-th--right">Views</th>
                </tr>
            </thead>
            <tbody data-dashboard-table="top_pages"></tbody>
        </table>
    </x-card>

    <x-card title="Traffic sources" :padding="false">
        <table class="p-dash__table">
            <thead>
                <tr>
                    <th>Source</th>
                    <th class="p-dash__table-th--right">Visitors</th>
                </tr>
            </thead>
            <tbody data-dashboard-table="top_referrers"></tbody>
        </table>
    </x-card>

    <x-card title="Countries">
        <div class="p-dash__map" data-dashboard-map></div>
    </x-card>

    <x-card title="Devices">
        <div class="p-dash__devices">
            <div class="p-dash__devices-chart">
                <canvas data-dashboard-chart="devices"></canvas>
            </div>
            <div class="p-dash__devices-legend" data-dashboard-devices></div>
        </div>
    </x-card>
</div>

<div class="p-dash__grid-2 p-mt-3">
    <x-card title="Systems & browsers" :padding="false">
        <div class="p-dash__split">
            <div class="p-dash__split-column p-dash__split-column--border">
                <table class="p-dash__table">
                    <thead>
                        <tr>
                            <th>Browser</th>
                            <th class="p-dash__table-th--right">Visitors</th>
                        </tr>
                    </thead>
                    <tbody data-dashboard-table="top_browsers"></tbody>
                </table>
            </div>
            <div class="p-dash__split-column">
                <table class="p-dash__table">
                    <thead>
                        <tr>
                            <th>Operating system</th>
                            <th class="p-dash__table-th--right">Visitors</th>
                        </tr>
                    </thead>
                    <tbody data-dashboard-table="top_operating_systems"></tbody>
                </table>
            </div>
        </div>
    </x-card>

    <x-card title="Marketing campaigns (UTM)" :padding="false">
        <div data-tabs="utm">
            <div class="p-dash__tabs p-dash__subtabs">
                <nav class="p-dash__tab-nav" role="tablist" aria-label="Campaign dimensions">
                    <button type="button" class="p-dash__tab is-active" data-tab-target="utm-source" role="tab" aria-selected="true">Source</button>
                    <button type="button" class="p-dash__tab" data-tab-target="utm-medium" role="tab" aria-selected="false" tabindex="-1">Medium</button>
                    <button type="button" class="p-dash__tab" data-tab-target="utm-campaign" role="tab" aria-selected="false" tabindex="-1">Campaign</button>
                </nav>
            </div>

            <table class="p-dash__table" data-tab-panel="utm-source">
                <thead>
                    <tr>
                        <th>Source</th>
                        <th class="p-dash__table-th--right">Visitors</th>
                    </tr>
                </thead>
                <tbody data-dashboard-table="utm_sources"></tbody>
            </table>

            <table class="p-dash__table" data-tab-panel="utm-medium" hidden>
                <thead>
                    <tr>
                        <th>Medium</th>
                        <th class="p-dash__table-th--right">Visitors</th>
                    </tr>
                </thead>
                <tbody data-dashboard-table="utm_mediums"></tbody>
            </table>

            <table class="p-dash__table" data-tab-panel="utm-campaign" hidden>
                <thead>
                    <tr>
                        <th>Campaign</th>
                        <th class="p-dash__table-th--right">Visitors</th>
                    </tr>
                </thead>
                <tbody data-dashboard-table="utm_campaigns"></tbody>
            </table>
        </div>
    </x-card>
</div>

@hook('dashboard.widgets')
