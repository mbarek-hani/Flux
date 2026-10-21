<x-app-layout>
    <x-slot name="title">
        Plugins
    </x-slot>
    <div class="p-page">
        <div class="p-container p-container--lg">
            <h2 class="p-page__title u-mb-3">Plugins</h2>
            @if(empty($plugins))
                <x-card>
                    <div class="p-empty">
                        <x-custom-icon name="puzzle" class="p-empty__icon" />
                        <h3 class="p-empty__title">No plugins detected</h3>
                        <p class="p-empty__text">
                            Place your plugins in the <code class="p-code">/plugins</code> directory manually, or browse the <a href="{{ route('marketplace.index') }}" class="p-link">Marketplace</a> to discover community-built plugins.
                        </p>
                    </div>
                </x-card>
            @else
                <div class="p-grid p-grid--1 p-grid--2 p-grid--3 p-grid--gap-md">
                    @foreach($plugins as $plugin)
                        <x-card class="u-h-full u-flex-col">
                            <div class="u-flex-1">
                                <div class="p-row p-mb-1">
                                    <x-custom-icon name="puzzle" class="c-icon--sm u-text-gray-400" />
                                    <h3 class="p-section__title u-flex-1">
                                        {{ $plugin['name'] }}
                                    </h3>
                                    <span class="p-text--xs u-text-gray-400">v{{ $plugin['version'] }}</span>
                                </div>

                                @if($plugin['description'])
                                    <p class="p-text p-mb-2 p-clamp-2">
                                        {{ $plugin['description'] }}
                                    </p>
                                @endif

                                <div class="p-row p-mb-2">
                                    <span class="p-badge {{ $plugin['active'] ? 'p-badge--success' : 'p-badge--neutral' }}">
                                        {{ $plugin['active'] ? 'Active' : 'Inactive' }}
                                    </span>
                                    <span class="p-text--xs">By {{ $plugin['author'] }}</span>
                                </div>
                            </div>

                            <div class="p-card-footer u-justify-between">
                                <x-button href="{{ route('plugins.show', $plugin['slug']) }}" size="sm" title="Details">
                                    <x-custom-icon name="eye" class="c-icon--xs" />
                                    <span class="u-hidden-mobile">Details</span>
                                </x-button>

                                <form method="POST" action="{{ route($plugin['active'] ? 'plugins.disable' : 'plugins.enable', $plugin['slug']) }}">
                                    @csrf
                                    <x-button variant="{{ $plugin['active'] ? 'danger' : 'primary' }}" size="sm" type="submit" title="{{ $plugin['active'] ? 'Disable' : 'Enable' }}">
                                        <x-custom-icon name="{{ $plugin['active'] ? 'stop' : 'play' }}" class="c-icon--xs" />
                                        <span class="u-hidden-mobile">{{ $plugin['active'] ? 'Disable' : 'Enable' }}</span>
                                    </x-button>
                                </form>
                            </div>
                        </x-card>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
