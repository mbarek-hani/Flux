@props([
    'title' => null,
    'padding' => true,
])

<div {{ $attributes->merge(['class' => 'c-card']) }}>
    @if($title || isset($actions))
        <div class="c-card__header">
            @if($title)
                <h3 class="c-card__title">{{ $title }}</h3>
            @endif
            @isset($actions)
                <div class="c-card__actions">{{ $actions }}</div>
            @endisset
        </div>
    @endif

    <div class="{{ $padding ? 'c-card__body--padded' : '' }}">
        {{ $slot }}
    </div>
</div>
