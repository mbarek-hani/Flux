<div class="p-dash__canvas">
    <section class="p-dash__kpis" aria-label="Event metrics">
        <x-stats-card title="Total events" icon="cursor-click" class="p-dash__kpi">
            <x-slot:value>
                <span data-dashboard-stat="total_events">0</span>
            </x-slot:value>
            <x-slot:meta>
                <span data-dashboard-meta="event_sessions">Across 0 sessions</span>
            </x-slot:meta>
        </x-stats-card>

        <x-stats-card title="Events per session" icon="arrows-right-left" class="p-dash__kpi">
            <x-slot:value>
                <span data-dashboard-stat="events_per_session">0</span>
            </x-slot:value>
            <x-slot:meta>
                <span data-dashboard-meta="event_depth">Interaction depth</span>
            </x-slot:meta>
        </x-stats-card>

        <x-stats-card title="Distinct types" icon="tag" class="p-dash__kpi">
            <x-slot:value>
                <span data-dashboard-stat="event_types">0</span>
            </x-slot:value>
            <x-slot:meta>
                <span data-dashboard-meta="event_type_context">Tracked activities</span>
            </x-slot:meta>
        </x-stats-card>

        <x-stats-card title="Sessions with events" icon="users" class="p-dash__kpi">
            <x-slot:value>
                <span data-dashboard-stat="sessions_with_events">0</span>
            </x-slot:value>
            <x-slot:meta>
                <span data-dashboard-meta="event_session_share">0% of visitors</span>
            </x-slot:meta>
        </x-stats-card>
    </section>

    <div class="p-dash__grid">
        <x-card title="Event activity" class="p-dash__panel p-dash__span-8">
            <div class="p-dash__chart p-dash__chart--events">
                <canvas data-dashboard-chart="events"></canvas>
            </div>
        </x-card>

        <x-card title="Event mix" class="p-dash__panel p-dash__span-4">
            <div class="p-dash__mix">
                <div class="p-dash__mix-chart">
                    <canvas data-dashboard-chart="event-types"></canvas>
                </div>
                <div class="p-dash__rank-list p-dash__rank-list--compact" data-dashboard-list="event_types"></div>
            </div>
        </x-card>

        <x-card title="Most interactive pages" :padding="false" class="p-dash__panel p-dash__span-6">
            <div class="p-dash__rank-list" data-dashboard-list="event_pages"></div>
        </x-card>

        <x-card title="Latest activity" :padding="false" class="p-dash__panel p-dash__span-6">
            <div class="p-dash__activity" data-dashboard-list="latest_events"></div>
        </x-card>
    </div>
</div>
