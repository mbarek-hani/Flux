<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Flux') . ' | ' . $titre }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        {{-- CSS des plugins actifs --}}
        @foreach(app(\App\Core\Plugin\PluginManager::class)->getCssPaths() as $cssPath)
            <link rel="stylesheet" href="{{ asset($cssPath) }}">
        @endforeach
        <style>
            [x-cloak] { display: none !important; }
        </style>
        <script>
            if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.setAttribute('data-theme', 'dark');
            } else {
                document.documentElement.removeAttribute('data-theme');
            }
        </script>
    </head>
    <body class="l-body">
        <div x-data="sidebar()" class="l-app">
            @if(session('succes'))
                <x-alert type="succes" :message="session('succes')" />
            @endif

            @if(session('erreur'))
                <x-alert type="erreur" :message="session('erreur')" />
            @endif
            {{-- ══════ OVERLAY MOBILE ══════ --}}
            <div
                x-show="ouvert"
                @click="ouvert = false"
                class="l-app__overlay"
                x-cloak
            ></div>

            {{-- ══════ SIDEBAR ══════ --}}
            {{-- SideBar Ouvert --}}
            <aside
                x-show="ouvert"
                x-cloak
                class="l-sidebar"
            >
                {{-- Logo + Toggle --}}
                <div class="l-sidebar__header">
                    <a href="{{ route('dashboard') }}" class="l-sidebar__logo-link">
                        <x-application-logo />
                    </a>
                    {{-- Mobile: close sidebar --}}
                    <button
                        @click="ouvert = false"
                        class="l-sidebar__close"
                    >
                        <x-custom-icon name="x-mark" class="c-icon--sm" />
                    </button>
                    {{-- Desktop: collapse sidebar --}}
                    <button
                        @click="ouvert = false"
                        class="l-sidebar__toggle"
                        title="Réduire le menu"
                    >
                        <x-custom-icon name="bars-3-bottom-left" class="c-icon--lg" />
                    </button>
                </div>

                {{-- Navigation principale --}}
                <nav class="l-sidebar__nav">

                    {{-- Core links --}}
                    <div class="l-sidebar__section">
                        <p class="l-sidebar__title">
                            Principal
                        </p>
                        @include('layouts.sidebar-link', [
                            'route' => 'dashboard',
                            'label' => 'Tableau de bord',
                            'icon'  => 'chart-bar',
                        ])
                        @include('layouts.sidebar-link', [
                            'route' => 'sites.index',
                            'label' => 'Sites',
                            'icon'  => 'globe',
                        ])
                        @include('layouts.sidebar-link', [
                            'route' => 'plugins.index',
                            'label' => 'Plugins',
                            'icon'  => 'puzzle',
                        ])
                        @include('layouts.sidebar-link', [
                            'route' => 'marketplace.index',
                            'label' => 'Marketplace',
                            'icon'  => 'shopping-cart',
                        ])
                    </div>

                    {{-- Liens ajoutés par les plugins --}}
                    @php
                        $navItems = app(\App\Core\Plugin\PluginManager::class)->getNavigationItems();
                    @endphp

                    @if(!empty($navItems))
                        <div class="l-sidebar__divider"></div>
                        <div class="l-sidebar__section">
                            <p class="l-sidebar__title">
                                Plugins
                            </p>

                            @foreach($navItems as $item)
                                <div x-data="{ open: {{ collect($item['sous_menus'] ?? [])->contains(fn($s) => request()->routeIs($s['route'])) ? 'true' : 'false' }} }">
                                    @if(!empty($item['sous_menus']))
                                        {{-- Cas avec sous-menu : Bouton Toggle --}}
                                        <button
                                            @click="open = !open"
                                            type="button"
                                            class="l-sidebar__menu-btn {{ collect($item['sous_menus'])->contains(fn($s) => request()->routeIs($s['route'])) ? '' : '' }}"
                                        >
                                            @if(isset($item['icone']))
                                                <x-custom-icon :name="$item['icone']" class="c-icon--sm c-icon--no-shrink" />
                                            @endif

                                            <span class="u-flex-1 u-text-left">{{ $item['label'] }}</span>

                                            <span x-show="!open" x-cloak>
                                                <x-custom-icon
                                                    name="chevron-down"
                                                    class="c-icon--xs"
                                                />
                                            </span>
                                            <span x-show="open" x-cloak>
                                                <x-custom-icon
                                                    name="chevron-up"
                                                    class="c-icon--xs"
                                                />
                                            </span>
                                        </button>

                                        <div
                                            x-show="open"
                                            x-cloak
                                            class="l-sidebar__submenu"
                                        >
                                            @foreach($item['sous_menus'] as $sousMenu)
                                                <a
                                                    href="{{ route($sousMenu['route']) }}"
                                                    class="l-sidebar__submenu-link {{ request()->routeIs($sousMenu['route']) ? 'l-sidebar__submenu-link--active' : '' }}"
                                                >
                                                    {{ $sousMenu['label'] }}
                                                </a>
                                            @endforeach
                                        </div>
                                    @else
                                        {{-- Cas sans sous-menu : Lien direct --}}
                                        <a
                                            href="{{ route($item['route']) }}"
                                            class="l-sidebar__menu-btn {{ request()->routeIs($item['route'] . '*') ? 'c-sidebar-link--active' : '' }}"
                                        >
                                            @if(isset($item['icone']))
                                                <x-custom-icon :name="$item['icone']" class="c-icon--sm c-icon--no-shrink" />
                                            @endif
                                            {{ $item['label'] }}
                                        </a>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </nav>

                {{-- Bas de sidebar — User dropdown --}}
                <div class="l-sidebar__footer" x-data="{ userMenu: false }">
                    {{-- Clickable user row --}}
                    <button
                        @click="userMenu = !userMenu"
                        class="l-sidebar__user-btn"
                    >
                        <div class="l-sidebar__user-avatar">
                            <span class="l-sidebar__user-initial">
                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                            </span>
                        </div>
                        <div class="l-sidebar__user-info">
                            <p class="l-sidebar__user-name">
                                {{ Auth::user()->name }}
                            </p>
                            <p class="l-sidebar__user-email">
                                {{ Auth::user()->email }}
                            </p>
                        </div>
                        <div class="l-sidebar__user-icons">
                            <x-custom-icon name="chevron-up" class="c-icon--sm c-icon--gray-900 c-icon--no-shrink" />
                            <x-custom-icon name="chevron-down" class="c-icon--sm c-icon--gray-900 c-icon--no-shrink" />
                        </div>
                    </button>

                    {{-- Dropdown (opens upward) --}}
                    <div
                        x-show="userMenu"
                        @click.outside="userMenu = false"
                        class="l-sidebar__dropdown"
                        x-cloak
                    >
                        <a href="{{ route('settings.index') }}"
                            class="l-sidebar__dropdown-link">
                             <x-custom-icon name="cog" class="c-icon--sm" />
                            Réglages
                        </a>
                        <a href="{{ route('profile.edit') }}"
                            class="l-sidebar__dropdown-link">
                             <x-custom-icon name="users" class="c-icon--sm" />
                            Profil
                        </a>
                        <div class="l-sidebar__dropdown-divider"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                class="l-sidebar__dropdown-link l-sidebar__dropdown-link--danger">
                                 <x-custom-icon name="arrow-right-on-rectangle" class="c-icon--sm" />
                                Déconnexion
                            </button>
                        </form>
                    </div>
                </div>
            </aside>

            {{-- SideBar Reduire --}}
            <aside
                x-show="!ouvert"
                x-cloak
                class="l-sidebar l-sidebar--collapsed"
            >
                {{-- Logo + Toggle --}}
                <div class="l-sidebar__header">
                    <button
                        @click="ouvert = true"
                        class="l-sidebar__toggle"
                        title="Ouvrir le menu"
                    >
                        <x-custom-icon name="bars-3-bottom-right" class="c-icon--lg" />
                    </button>
                </div>

                {{-- Navigation principale --}}
                <nav class="l-sidebar__nav">

                    {{-- Core links --}}
                    <div class="l-sidebar__section">
                        @include('layouts.sidebar-link', [
                            'route' => 'dashboard',
                            'hover' => 'Tableau de bord',
                            'icon'  => 'chart-bar',
                        ])
                        @include('layouts.sidebar-link', [
                            'route' => 'sites.index',
                            'hover' => 'Sites',
                            'icon'  => 'globe',
                        ])
                        @include('layouts.sidebar-link', [
                            'route' => 'plugins.index',
                            'hover' => 'Plugins',
                            'icon'  => 'puzzle',
                        ])
                        @include('layouts.sidebar-link', [
                            'route' => 'marketplace.index',
                            'hover' => 'Marketplace',
                            'icon'  => 'shopping-cart',
                        ])
                    </div>

                    {{-- Liens ajoutés par les plugins --}}
                    @if(!empty($navItems))
                        <div class="l-sidebar__divider"></div>
                        <div class="l-sidebar__section">
                            @foreach($navItems as $item)
                                <div x-data="{ open: false }" style="position: relative;">
                                    @if(!empty($item['sous_menus']))
                                        {{-- Cas avec sous-menu : Bouton Toggle --}}
                                        <button
                                            @click="open = !open"
                                            @click.outside="open = false"
                                            type="button"
                                            class="l-sidebar__menu-btn c-sidebar-link {{ collect($item['sous_menus'])->contains(fn($s) => request()->routeIs($s['route'])) ? 'c-sidebar-link--active' : '' }}"
                                        >
                                            @if(isset($item['icone']))
                                                <x-custom-icon :name="$item['icone']" class="c-sidebar-link__icon c-icon--sm c-icon--no-shrink" />
                                            @endif
                                            <span class="c-sidebar-link__tooltip" x-show="!open">{{ $item['label'] }}</span>
                                        </button>

                                        <div
                                            x-show="open"
                                            x-cloak
                                            class="l-sidebar__dropdown l-sidebar__dropdown--side"
                                        >
                                            @foreach($item['sous_menus'] as $sousMenu)
                                                <a
                                                    href="{{ route($sousMenu['route']) }}"
                                                    class="l-sidebar__dropdown-link {{ request()->routeIs($sousMenu['route']) ? 'l-sidebar__dropdown-link--active' : '' }}"
                                                >
                                                    {{ $sousMenu['label'] }}
                                                </a>
                                            @endforeach
                                        </div>
                                    @else
                                        {{-- Cas sans sous-menu : Lien direct --}}
                                        @include('layouts.sidebar-link', [
                                            'route' => $item['route'],
                                            'hover' => $item['label'],
                                            'icon'  => $item['icone'] ?? 'puzzle',
                                        ])
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </nav>

                {{-- Bas de sidebar — User dropdown --}}
                <div class="l-sidebar__footer" x-data="{ userMenu: false }">
                    {{-- Clickable user row --}}
                    <button
                        @click="userMenu = !userMenu"
                        class="l-sidebar__user-btn"
                    >
                        <div class="l-sidebar__user-avatar">
                            <span class="l-sidebar__user-initial">
                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                            </span>
                        </div>
                    </button>

                    {{-- Dropdown (opens upward) --}}
                    <div
                        x-show="userMenu"
                        @click.outside="userMenu = false"
                        class="l-sidebar__dropdown"
                        x-cloak
                    >
                        <a href="{{ route('settings.index') }}"
                            class="l-sidebar__dropdown-link">
                             <x-custom-icon name="cog" class="c-icon--sm" />
                            Réglages
                        </a>
                        <a href="{{ route('profile.edit') }}"
                            class="l-sidebar__dropdown-link">
                             <x-custom-icon name="users" class="c-icon--sm" />
                            Profil
                        </a>
                        <div class="l-sidebar__dropdown-divider"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                class="l-sidebar__dropdown-link l-sidebar__dropdown-link--danger">
                                 <x-custom-icon name="arrow-right-on-rectangle" class="c-icon--sm" />
                                Déconnexion
                            </button>
                        </form>
                    </div>
                </div>
            </aside>
            {{-- ══════ CONTENU PRINCIPAL ══════ --}}
            <div
                :class="ouvert ? 'l-main-wrapper--shifted' : 'l-main-wrapper--collapsed'"
                class="l-main-wrapper"
            >

                {{-- Topbar (juste le toggle) --}}
                <div class="l-topbar">
                    <button
                        @click="ouvert = true"
                        class="l-topbar__btn"
                    >
                        <x-custom-icon name="bars-3" class="c-icon--md" />
                    </button>
                    <span class="l-topbar__title">
                        {{ config('app.name', 'LaraMetrics') }}
                    </span>
                </div>

                <main class="l-content">
                    {{ $slot }}
                </main>
            </div>
        </div>

        @stack('scripts')
        <script>
            function sidebar() {
                const isDesktop = window.innerWidth >= 1024;
                const saved = localStorage.getItem('sidebar_ouvert');
                const ouvert = isDesktop ? (saved !== null ? saved === 'true' : true) : false;

                return {
                    ouvert,
                    init() {
                        this.$watch('ouvert', (val) => {
                            localStorage.setItem('sidebar_ouvert', val);
                        });
                    }
                };
            }
        </script>
    </body>
</html>
