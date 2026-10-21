<x-app-layout>
    <x-slot name="title">
        Plugin Marketplace
    </x-slot>

    <div class="p-page" data-marketplace>
        <div class="p-container p-container--lg">
            <div class="p-page__header u-mb-3">
                <div>
                    <h2 class="p-page__title">Plugin Marketplace</h2>
                    <p class="u-text-gray-600">Discover and install new features to extend your Flux dashboard.</p>
                </div>
            </div>

            <div class="p-marketplace-toolbar">
                <form method="GET" action="{{ route('marketplace.index') }}" class="p-marketplace-search">
                    <div class="p-marketplace-search__field">
                        <x-input
                            name="search"
                            placeholder="Search plugins..."
                            value="{{ $search }}"
                            class="u-w-full"
                        />
                    </div>
                    <x-button type="submit" variant="primary">
                        <x-custom-icon name="magnifying-glass" class="c-icon--xs" />
                        <span>Search</span>
                    </x-button>
                </form>
            </div>

            @if(count($plugins) === 0)
                <x-card class="p-marketplace-empty">
                    <div class="p-empty">
                        <x-custom-icon name="puzzle" class="p-empty__icon" />
                        <h3 class="p-empty__title">
                            @if($search)
                                No plugins found
                            @else
                                No plugins available
                            @endif
                        </h3>
                        <p class="p-empty__text">
                            @if($search)
                                We could not find any plugin in the store matching your search criteria.
                            @else
                                There are no plugins available in the store right now.
                            @endif
                        </p>
                    </div>
                </x-card>
            @else
                <div class="p-grid p-grid--1 p-grid--2 p-grid--3 p-grid--gap-md p-marketplace-grid">
                    @foreach($plugins as $plugin)
                        <x-card
                            class="p-marketplace-card"
                            data-marketplace-card
                            data-href="{{ route('marketplace.show', $plugin['id']) }}"
                            role="link"
                            tabindex="0"
                        >
                            <div class="p-marketplace-card__body">
                                <div class="p-marketplace-card__header">
                                    <div class="p-row">
                                        <x-custom-icon name="puzzle" class="c-icon--sm u-text-gray-400" />
                                        <h3 class="p-marketplace-card__title">
                                            {{ $plugin['nom'] }}
                                        </h3>
                                    </div>
                                    <span class="p-badge p-badge--neutral p-marketplace-card__version">{{ $plugin['current_version'] }}</span>
                                </div>

                                <div class="p-marketplace-card__meta">
                                    <span class="p-text--bold">{{ number_format($plugin['total_downloads']) }} downloads</span>

                                    @if($plugin['status'] === 'installed')
                                        <span class="p-badge p-badge--success p-marketplace-card__status">
                                            Installed
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="p-card-footer p-marketplace-card__actions">
                                <div class="p-actions u-flex u-justify-end">
                                    @if($plugin['status'] === 'installed')
                                        @if($plugin['update_available'])
                                            <x-button
                                                variant="primary"
                                                size="sm"
                                                data-marketplace-action="update"
                                                data-plugin-id="{{ $plugin['id'] }}"
                                                data-plugin-name="{{ $plugin['nom'] }}"
                                                data-installed-version="{{ $plugin['installed_version'] }}"
                                                data-current-version="{{ $plugin['current_version'] }}"
                                                title="Update"
                                            >
                                                Update
                                            </x-button>
                                        @endif

                                        <x-button
                                            variant="danger"
                                            size="sm"
                                            data-marketplace-action="uninstall"
                                            data-plugin-id="{{ $plugin['id'] }}"
                                            data-plugin-name="{{ $plugin['nom'] }}"
                                            title="Uninstall"
                                        >
                                            Uninstall
                                        </x-button>
                                    @else
                                        <x-button
                                            variant="primary"
                                            size="sm"
                                            data-marketplace-action="install"
                                            data-plugin-id="{{ $plugin['id'] }}"
                                            data-plugin-name="{{ $plugin['nom'] }}"
                                            title="Install"
                                        >
                                            Install
                                        </x-button>
                                    @endif
                                </div>
                            </div>
                        </x-card>
                    @endforeach
                </div>

                @if(($meta['total_pages'] ?? 1) > 1)
                    <div class="p-marketplace-pagination">
                        <span class="p-text--xs">
                            Page {{ $meta['page'] }} of {{ $meta['total_pages'] }} (total: {{ $meta['total'] }} plugins)
                        </span>
                        <div class="p-actions">
                            @if($meta['page'] > 1)
                                <x-button href="{{ route('marketplace.index', ['page' => $meta['page'] - 1, 'search' => $search]) }}" size="sm">
                                    <x-custom-icon name="chevron-left" class="c-icon--xs" />
                                    <span>Previous</span>
                                </x-button>
                            @endif
                            @if($meta['page'] < $meta['total_pages'])
                                <x-button href="{{ route('marketplace.index', ['page' => $meta['page'] + 1, 'search' => $search]) }}" size="sm">
                                    <span>Next</span>
                                    <x-custom-icon name="chevron-right" class="c-icon--xs" />
                                </x-button>
                            @endif
                        </div>
                    </div>
                @endif
            @endif
        </div>

        @include('marketplace.partials.progress-modal')
    </div>
</x-app-layout>
