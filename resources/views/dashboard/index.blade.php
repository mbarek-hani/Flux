<x-app-layout>
    <x-slot name="title">{{ $title }}</x-slot>

    <div class="p-page">
        <div class="p-container p-container--xl">
            @if($noSite)
                <h2 class="p-page__title p-dash__title">{{ $title }}</h2>

                <x-card>
                    <div class="p-empty">
                        <x-custom-icon name="globe" class="p-empty__icon" />
                        <h3 class="p-empty__title">No site configured</h3>
                        <p class="p-empty__text">Add your first site to start collecting analytics.</p>
                        <div class="p-mt-3">
                            <x-button variant="primary" href="{{ route('sites.create') }}">
                                <x-custom-icon name="plus" class="c-icon--sm" />
                                Add a site
                            </x-button>
                        </div>
                    </div>
                </x-card>
            @else
                <div
                    data-dashboard
                    data-site-id="{{ $currentSite->id }}"
                    data-stats-url="{{ route('dashboard.stats') }}"
                    data-period="7d"
                >
                    <div class="p-dash__header">
                        <div class="p-dash__heading">
                            <h2 class="p-page__title p-dash__title">{{ $title }}</h2>
                            <p class="p-dash__subtitle">{{ $currentSite->name }} · {{ $currentSite->domain }}</p>
                        </div>

                        <div class="p-dash__header-controls">
                            @include('dashboard.partials.controls')
                        </div>
                    </div>

                    <div data-tabs="dashboard">
                        @include('dashboard.partials.tabs')

                        <div class="p-dash__loading" data-dashboard-loading>
                            <x-custom-icon name="arrow-path" class="p-dash__loading-icon" />
                            <span class="p-dash__loading-text">Loading...</span>
                        </div>

                        <div class="p-dash__error" data-dashboard-error hidden>
                            <p class="p-dash__error-text" data-dashboard-error-text></p>
                        </div>

                        <div data-dashboard-content hidden>
                            <div data-tab-panel="overview">
                                @include('dashboard.partials.overview')
                            </div>

                            <div data-tab-panel="events" hidden>
                                @include('dashboard.partials.events')
                            </div>

                            @foreach($tabs as $tab)
                                <div data-tab-panel="{{ $tab['id'] }}" hidden>
                                    @hook('tab.' . $tab['id'])
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
