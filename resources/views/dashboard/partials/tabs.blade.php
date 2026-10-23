<div class="p-dash__tabs" data-dashboard-tabs>
    <nav class="p-dash__tab-nav" role="tablist" aria-label="Dashboard sections">
        <button
            type="button"
            class="p-dash__tab is-active"
            data-tab-target="overview"
            role="tab"
            aria-selected="true"
        >
            <x-custom-icon name="chart-bar" class="c-icon--sm" />
            Overview
        </button>

        <button
            type="button"
            class="p-dash__tab"
            data-tab-target="events"
            role="tab"
            aria-selected="false"
            tabindex="-1"
        >
            <x-custom-icon name="cursor-click" class="c-icon--sm" />
            Events
        </button>

        @if($tabs !== [])
            <div class="p-dash__tab-extras" id="dashboard-extra-tabs" data-tab-overflow-panel>
                @foreach($tabs as $tab)
                    <button
                        type="button"
                        class="p-dash__tab p-dash__tab--extra"
                        data-tab-target="{{ $tab['id'] }}"
                        role="tab"
                        aria-selected="false"
                        tabindex="-1"
                    >
                        <x-custom-icon :name="$tab['icon']" class="c-icon--sm" />
                        {{ $tab['label'] }}
                    </button>
                @endforeach
            </div>
        @endif
    </nav>

    @if($tabs !== [])
        <button
            type="button"
            class="p-dash__tab-overflow"
            data-tab-overflow-trigger
            aria-expanded="false"
            aria-controls="dashboard-extra-tabs"
            aria-label="More dashboard sections"
            title="More sections"
        >
            <x-custom-icon name="ellipsis-horizontal" class="c-icon--md" />
            <span class="p-dash__tab-overflow-dot" aria-hidden="true"></span>
        </button>
    @endif
</div>
