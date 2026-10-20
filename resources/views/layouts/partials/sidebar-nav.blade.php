@php
    $coreItems = $navigation['core'];
    $pluginItems = $navigation['plugins'];
@endphp

<nav class="l-sidebar__nav">
    <div class="l-sidebar__section">
        @if(! $collapsed)
            <p class="l-sidebar__title">Main</p>
        @endif

        @foreach($coreItems as $item)
            @include('layouts.partials.sidebar-link', [
                'route' => $item['route'],
                'label' => $collapsed ? '' : $item['label'],
                'hover' => $collapsed ? $item['label'] : '',
                'icon' => $item['icon'] ?? 'circle',
            ])
        @endforeach
    </div>

    @if($pluginItems !== [])
        <div class="l-sidebar__divider"></div>

        <div class="l-sidebar__section">
            @if(! $collapsed)
                <p class="l-sidebar__title">Plugins</p>
            @endif

            @foreach($pluginItems as $item)
                @php
                    $children = $item['children'] ?? [];
                    $childrenActive = collect($children)->contains(
                        fn (array $child): bool => request()->routeIs($child['route']),
                    );
                @endphp

                @if($children !== [])
                    @if($collapsed)
                        <div class="c-dropdown" data-dropdown>
                            <button
                                type="button"
                                class="l-sidebar__menu-btn c-sidebar-link {{ $childrenActive ? 'c-sidebar-link--active' : '' }}"
                                data-dropdown-trigger
                                aria-expanded="false"
                            >
                                @if(isset($item['icon']))
                                    <x-custom-icon :name="$item['icon']" class="c-sidebar-link__icon c-icon--sm c-icon--no-shrink" />
                                @endif
                                <span class="c-sidebar-link__tooltip">{{ $item['label'] }}</span>
                            </button>

                            <div class="l-sidebar__dropdown l-sidebar__dropdown--side" data-dropdown-menu hidden>
                                @foreach($children as $child)
                                    <a
                                        href="{{ route($child['route']) }}"
                                        class="l-sidebar__dropdown-link {{ request()->routeIs($child['route']) ? 'l-sidebar__dropdown-link--active' : '' }}"
                                    >
                                        {{ $child['label'] }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div data-sidebar-submenu data-open="{{ $childrenActive ? 'true' : 'false' }}">
                            <button
                                type="button"
                                class="l-sidebar__menu-btn"
                                data-sidebar-submenu-trigger
                                aria-expanded="{{ $childrenActive ? 'true' : 'false' }}"
                            >
                                @if(isset($item['icon']))
                                    <x-custom-icon :name="$item['icon']" class="c-icon--sm c-icon--no-shrink" />
                                @endif

                                <span class="u-flex-1 u-text-left">{{ $item['label'] }}</span>

                                <span data-submenu-icon="closed" @if($childrenActive) hidden @endif>
                                    <x-custom-icon name="chevron-down" class="c-icon--xs" />
                                </span>
                                <span data-submenu-icon="open" @if(! $childrenActive) hidden @endif>
                                    <x-custom-icon name="chevron-up" class="c-icon--xs" />
                                </span>
                            </button>

                            <div class="l-sidebar__submenu" data-sidebar-submenu-panel @if(! $childrenActive) hidden @endif>
                                @foreach($children as $child)
                                    <a
                                        href="{{ route($child['route']) }}"
                                        class="l-sidebar__submenu-link {{ request()->routeIs($child['route']) ? 'l-sidebar__submenu-link--active' : '' }}"
                                    >
                                        {{ $child['label'] }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @else
                    @include('layouts.partials.sidebar-link', [
                        'route' => $item['route'],
                        'label' => $collapsed ? '' : $item['label'],
                        'hover' => $collapsed ? $item['label'] : '',
                        'icon' => $item['icon'] ?? 'puzzle',
                    ])
                @endif
            @endforeach
        </div>
    @endif
</nav>
