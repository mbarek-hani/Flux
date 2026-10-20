@props(['route', 'label' => '', 'icon', 'hover' => ''])

<a
    href="{{ route($route) }}"
    class="c-sidebar-link {{ request()->routeIs($route . '*') || request()->routeIs($route) ? 'c-sidebar-link--active' : '' }}"
>
    <x-custom-icon :name="$icon" class="c-sidebar-link__icon c-icon--sm c-icon--no-shrink" />
    @if($label)
        <span class="c-sidebar-link__label">{{ $label }}</span>
    @endif
    @if($hover)
        <span class="c-sidebar-link__tooltip">{{ $hover }}</span>
    @endif
</a>
