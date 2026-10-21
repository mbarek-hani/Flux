<x-app-layout>
    <x-slot name="title">
        Plugin details: {{ $details['name'] }}
    </x-slot>

    <div class="p-page">
        <div class="p-container p-container--lg p-stack">
            <div class="p-page__header">
                <div class="p-row">
                    <h2 class="p-page__title u-mb-0">{{ $details['name'] }}</h2>
                </div>
                <div class="p-actions">
                    <x-button href="{{ route('plugins.index') }}" size="sm">
                        <x-custom-icon name="arrow-left" class="c-icon--sm" />
                        Back
                    </x-button>
                    @if($details['active'])
                        <form method="POST" action="{{ route('plugins.disable', $details['slug']) }}" class="inline">
                            @csrf
                            <x-button type="submit" size="sm" variant="danger">
                                <x-custom-icon name="stop" class="c-icon--sm" />
                                Disable
                            </x-button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('plugins.enable', $details['slug']) }}" class="inline">
                            @csrf
                            <x-button type="submit" size="sm" variant="primary">
                                <x-custom-icon name="play" class="c-icon--sm" />
                                Enable
                            </x-button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="p-grid p-grid--3 u-items-start">
                <div class="p-col-span-2 p-stack">
                    <x-card title="Description">
                        <p class="p-text--muted u-leading-relaxed">
                            {{ $details['description'] }}
                        </p>
                    </x-card>

                    <x-card title="Built-in features">
                        @if(empty($details['tabs']) && empty($details['hooks']) && empty($details['navigation']) && empty($details['settings']))
                            <p class="p-text u-italic">This plugin does not expose any standard UI features.</p>
                        @else
                            @if(!empty($details['tabs']))
                                <div class="p-mb-3">
                                    <h4 class="p-section__title--sm">
                                        <x-custom-icon name="folder" class="p-section__icon c-icon--gray-400" />
                                        Dashboard tabs
                                    </h4>
                                    <ul class="p-indent p-list-disc">
                                        @foreach($details['tabs'] as $tab)
                                            <li>{{ $tab['label'] ?? 'Tab' }} (ID: <code class="p-code">{{ $tab['id'] ?? 'N/A' }}</code>)</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if(!empty($details['navigation']))
                                <div class="p-mb-3">
                                    <h4 class="p-section__title--sm">
                                        <x-custom-icon name="bars-3" class="p-section__icon c-icon--gray-400" />
                                        Navigation links
                                    </h4>
                                    <ul class="p-indent p-list-disc">
                                        @foreach($details['navigation'] as $nav)
                                            <li>{{ $nav['label'] ?? 'Link' }} (Route: <code class="p-code">{{ $nav['route'] ?? 'N/A' }}</code>)</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if(!empty($details['hooks']))
                                <div class="p-mb-3">
                                    <h4 class="p-section__title--sm">
                                        <x-custom-icon name="bolt" class="p-section__icon c-icon--gray-400" />
                                        Visual hooks
                                    </h4>
                                    <div class="p-row p-row--wrap p-indent">
                                        @foreach($details['hooks'] as $hook)
                                            <span class="p-badge p-badge--info">{{ $hook }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if(!empty($details['settings']))
                                <div class="p-mb-3">
                                    <h4 class="p-section__title--sm">
                                        <x-custom-icon name="cog" class="p-section__icon c-icon--gray-400" />
                                        Settings fields
                                    </h4>
                                    <ul class="p-indent p-list-disc">
                                        @foreach($details['settings'] as $setting)
                                            <li>{{ $setting['label'] ?? 'Setting' }} <span class="u-text-gray-400">({{ $setting['type'] ?? 'text' }})</span></li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        @endif
                    </x-card>
                </div>

                <div class="p-stack">
                    <x-card title="Metadata">
                        <div class="p-stack--sm p-text">
                            <div class="p-row p-row--between p-info-row">
                                <span class="p-info-label">Status</span>
                                @if($details['active'])
                                    <span class="p-badge p-badge--success">Active</span>
                                @else
                                    <span class="p-badge p-badge--neutral">Inactive</span>
                                @endif
                            </div>

                            <div class="p-row p-row--between p-info-row">
                                <span class="p-info-label">Version</span>
                                <span class="p-info-value">v{{ $details['version'] }}</span>
                            </div>

                            <div class="p-row p-row--between p-info-row">
                                <span class="p-info-label">Slug</span>
                                <code class="p-code u-text-gray-800">{{ $details['slug'] }}</code>
                            </div>

                            <div class="p-row p-row--between p-info-row">
                                <span class="p-info-label">Author</span>
                                <span class="p-text--xs u-text-gray-900">{{ $details['author'] }}</span>
                            </div>

                            <div class="p-row p-row--between p-info-row">
                                <span class="p-info-label">License</span>
                                <span class="p-text--xs u-text-gray-900">{{ $details['license'] }}</span>
                            </div>

                            @if($details['enabled_at'])
                                <div class="p-row p-row--between p-info-row">
                                    <span class="p-info-label">Enabled on</span>
                                    <span class="p-info-value u-font-normal p-text">{{ \Carbon\Carbon::parse($details['enabled_at'])->format('d/m/Y H:i') }}</span>
                                </div>
                            @endif
                        </div>
                    </x-card>

                    @if(!empty($details['url']))
                        <x-card>
                            <div class="p-row p-list-item--top p-mb-3">
                                <x-custom-icon name="globe-alt" class="p-section__icon c-icon--gray-400 c-icon--md" />
                                <div>
                                    <h4 class="p-section__title">Website</h4>
                                    <a href="{{ $details['url'] }}" target="_blank" rel="noopener noreferrer" class="p-text u-text-info u-no-underline u-block p-mt-1 u-break-all">
                                        {{ $details['url'] }}
                                    </a>
                                </div>
                            </div>
                        </x-card>
                    @endif
                </div>
            </div>

            @hook('plugin.show.after', ['plugin' => $details])
        </div>
    </div>
</x-app-layout>
