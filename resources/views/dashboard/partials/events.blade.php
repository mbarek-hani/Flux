<div class="p-grid p-grid--2 p-grid--4 p-mb-3">
    <x-stats-card title="Total events" icon="cursor-click">
        <x-slot:value>
            <span data-dashboard-stat="total_events">0</span>
        </x-slot:value>
    </x-stats-card>
    <x-stats-card title="Events per session" icon="arrows-right-left">
        <x-slot:value>
            <span data-dashboard-stat="events_per_session">0</span>
        </x-slot:value>
    </x-stats-card>
    <x-stats-card title="Distinct types" icon="tag">
        <x-slot:value>
            <span data-dashboard-stat="event_types">0</span>
        </x-slot:value>
    </x-stats-card>
    <x-stats-card title="Active sessions" icon="users">
        <x-slot:value>
            <span data-dashboard-stat="active_sessions">0</span>
        </x-slot:value>
    </x-stats-card>
</div>

<x-card title="Event activity" class="p-mb-3">
    <div class="p-dash__chart--tall">
        <canvas data-dashboard-chart="events"></canvas>
    </div>
</x-card>

<div class="p-dash__grid-2">
    <x-card title="Event type distribution">
        <div class="p-dash__distribution">
            <div class="p-dash__distribution-chart">
                <canvas data-dashboard-chart="event-types"></canvas>
            </div>
            <div class="p-dash__distribution-table">
                <table class="p-dash__table">
                    <tbody data-dashboard-table="event_types"></tbody>
                </table>
            </div>
        </div>
    </x-card>

    <x-card title="Most interactive pages" :padding="false">
        <table class="p-dash__table">
            <thead>
                <tr class="p-dash__table-header--tinted">
                    <th>URL</th>
                    <th class="p-dash__table-th--right">Actions</th>
                </tr>
            </thead>
            <tbody data-dashboard-table="event_pages"></tbody>
        </table>
    </x-card>

    <x-card title="Latest activity" :padding="false" class="p-dash__col-span-2">
        <table class="p-dash__table">
            <thead>
                <tr class="p-dash__table-header--tinted">
                    <th>Event</th>
                    <th>Page</th>
                    <th class="p-dash__table-th--right">Date</th>
                </tr>
            </thead>
            <tbody data-dashboard-table="latest_events"></tbody>
        </table>
    </x-card>
</div>
