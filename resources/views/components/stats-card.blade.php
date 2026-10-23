@props([
    'title' => '',
    'value' => '0',
    'icon' => null,
])

<div {{ $attributes->merge(['class' => 'c-stats-card']) }}>
    <div class="c-stats-card__content">
        @if($icon)
            <div class="c-stats-card__icon-wrapper">
                <x-custom-icon :name="$icon" class="c-stats-card__icon" />
            </div>
        @endif
        <div class="c-stats-card__info">
            <p class="c-stats-card__title">{{ $title }}</p>
            <div class="c-stats-card__value-row">
                <p class="c-stats-card__value">{{ $value }}</p>
                @isset($trend)
                    <span class="c-stats-card__trend">{{ $trend }}</span>
                @endisset
            </div>
            @isset($meta)
                <p class="c-stats-card__meta">{{ $meta }}</p>
            @endisset
        </div>
    </div>
</div>
