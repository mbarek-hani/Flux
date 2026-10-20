<div class="p-dash__tabs">
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

        @foreach($tabs as $tab)
            <button
                type="button"
                class="p-dash__tab"
                data-tab-target="{{ $tab['id'] }}"
                role="tab"
                aria-selected="false"
                tabindex="-1"
            >
                <x-custom-icon :name="$tab['icon']" class="c-icon--sm" />
                {{ $tab['label'] }}
            </button>
        @endforeach
    </nav>
</div>
