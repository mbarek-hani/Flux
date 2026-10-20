@php
    $isCollapsed = $collapsed ?? false;
@endphp

<aside
    class="l-sidebar {{ $isCollapsed ? 'l-sidebar--collapsed' : '' }}"
    @if($isCollapsed) data-sidebar-collapsed @else data-sidebar-expanded @endif
>
    <div class="l-sidebar__header">
        @if($isCollapsed)
            <button type="button" class="l-sidebar__toggle" data-sidebar-open title="Expand sidebar" aria-label="Expand sidebar">
                <x-custom-icon name="bars-3-bottom-right" class="c-icon--lg" />
            </button>
        @else
            <a href="{{ route('dashboard') }}" class="l-sidebar__logo-link">
                <x-application-logo />
            </a>
            <button type="button" class="l-sidebar__close" data-sidebar-close aria-label="Close sidebar">
                <x-custom-icon name="x-mark" class="c-icon--sm" />
            </button>
            <button type="button" class="l-sidebar__toggle" data-sidebar-close title="Collapse sidebar" aria-label="Collapse sidebar">
                <x-custom-icon name="bars-3-bottom-left" class="c-icon--lg" />
            </button>
        @endif
    </div>

    @include('layouts.partials.sidebar-nav', [
        'navigation' => $navigation,
        'collapsed' => $isCollapsed,
    ])

    @if(! $isCollapsed)
        @foreach($sidebarHooks as $html)
            {!! $html !!}
        @endforeach
    @endif

    @include('layouts.partials.sidebar-user', ['collapsed' => $isCollapsed])
</aside>
