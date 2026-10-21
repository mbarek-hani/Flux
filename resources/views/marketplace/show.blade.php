<x-app-layout>
    <x-slot name="title">
        Plugin details - {{ $plugin['nom'] }}
    </x-slot>

    <div class="p-page" data-marketplace>
        <div class="p-container p-container--lg">
            <div class="p-page__header u-mb-3 p-row p-row--between">
                <div>
                    <h2 class="p-page__title p-marketplace-detail__page-title">
                        {{ $plugin['nom'] }}
                    </h2>
                    <p class="u-text-gray-600">View details and manage this plugin's store status.</p>
                </div>
                <x-button href="{{ route('marketplace.index') }}" variant="default" size="sm">
                    <x-custom-icon name="chevron-left" class="c-icon--xs" />
                    <span>Back</span>
                </x-button>
            </div>

            <div class="p-marketplace-detail">
                <div class="p-marketplace-detail__main">
                    <x-card>
                        <div class="p-marketplace-hero">
                            <div class="p-marketplace-hero__icon">
                                <x-custom-icon name="puzzle" class="c-icon--md u-text-gray-600" />
                            </div>
                            <div>
                                <h3 class="p-marketplace-hero__title">
                                    {{ $plugin['nom'] }}
                                </h3>
                                <p class="p-marketplace-hero__author">
                                    By <span>{{ $plugin['author'] }}</span>
                                </p>
                            </div>
                        </div>

                        @if($plugin['description'])
                            <div class="p-marketplace-description">
                                <h4 class="p-marketplace-description__title">Description</h4>
                                <p class="p-marketplace-description__text">
                                    {{ $plugin['description'] }}
                                </p>
                            </div>
                        @endif
                    </x-card>
                </div>

                <div class="p-marketplace-detail__sidebar">
                    <x-card class="p-marketplace-status">
                        <h4 class="p-marketplace-status__title">Plugin status</h4>

                        <div class="p-marketplace-status__list">
                            <div class="p-marketplace-status__row">
                                <span class="p-marketplace-status__label">Current status:</span>
                                @if($plugin['status'] === 'installed')
                                    <div class="p-marketplace-status__badges">
                                        <span class="p-badge p-badge--success">Installed</span>
                                        @if($plugin['actif'])
                                            <span class="p-badge p-badge--info">Active</span>
                                        @else
                                            <span class="p-badge p-badge--neutral">Inactive</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="p-badge p-badge--neutral">Not installed</span>
                                @endif
                            </div>

                            <div class="p-marketplace-status__row">
                                <span class="p-marketplace-status__label">Available version:</span>
                                <span class="p-marketplace-status__value">{{ $plugin['current_version'] }}</span>
                            </div>

                            @if($plugin['status'] === 'installed')
                                <div class="p-marketplace-status__row">
                                    <span class="p-marketplace-status__label">Installed version:</span>
                                    <span class="p-marketplace-status__value">{{ $plugin['installed_version'] }}</span>
                                </div>
                            @endif

                            <div class="p-marketplace-status__row">
                                <span class="p-marketplace-status__label">Downloads:</span>
                                <span class="p-marketplace-status__value">{{ number_format($plugin['total_downloads']) }}</span>
                            </div>

                            <div class="p-marketplace-status__row">
                                <span class="p-marketplace-status__label">License:</span>
                                <span class="p-marketplace-status__value p-marketplace-status__value--license">{{ $plugin['licence'] }}</span>
                            </div>

                            @if($plugin['repo_url'])
                                <div class="p-marketplace-status__section">
                                    <x-button href="{{ $plugin['repo_url'] }}" target="_blank" variant="default" class="u-w-full u-flex u-justify-center">
                                        <span>View repository</span>
                                    </x-button>
                                </div>
                            @endif

                            <div class="p-marketplace-status__section">
                                @if($plugin['status'] === 'installed')
                                    <div class="p-marketplace-actions">
                                        @if($plugin['update_available'])
                                            <x-button
                                                variant="primary"
                                                class="u-w-full u-flex u-justify-center"
                                                data-marketplace-action="update"
                                                data-plugin-id="{{ $plugin['id'] }}"
                                                data-plugin-name="{{ $plugin['nom'] }}"
                                                data-installed-version="{{ $plugin['installed_version'] }}"
                                                data-current-version="{{ $plugin['current_version'] }}"
                                            >
                                                <span>Update</span>
                                            </x-button>
                                        @endif

                                        <x-button
                                            variant="danger"
                                            class="u-w-full u-flex u-justify-center"
                                            data-marketplace-action="uninstall"
                                            data-plugin-id="{{ $plugin['id'] }}"
                                            data-plugin-name="{{ $plugin['nom'] }}"
                                        >
                                            <span>Uninstall</span>
                                        </x-button>
                                    </div>
                                @else
                                    <x-button
                                        variant="primary"
                                        class="u-w-full u-flex u-justify-center"
                                        data-marketplace-action="install"
                                        data-plugin-id="{{ $plugin['id'] }}"
                                        data-plugin-name="{{ $plugin['nom'] }}"
                                    >
                                        <span>Install plugin</span>
                                    </x-button>
                                @endif
                            </div>
                        </div>
                    </x-card>
                </div>
            </div>
        </div>

        @include('marketplace.partials.progress-modal')
    </div>
</x-app-layout>
