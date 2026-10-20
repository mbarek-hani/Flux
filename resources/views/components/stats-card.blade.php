@props([
    'title' => '',
    'value' => '0',
    'icon' => null,
])

<div class="c-stats-card">
    <div class="c-stats-card__content">
        @if($icon)
            <div class="c-stats-card__icon-wrapper">
                <x-custom-icon :name="$icon" class="c-stats-card__icon" />
            </div>
        @endif
        <div class="c-stats-card__info">
            <p class="c-stats-card__title">{{ $title }}</p>
            <p class="c-stats-card__value">{{ $value }}</p>
        </div>
    </div>
</div>
