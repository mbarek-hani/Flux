@props(['align' => 'right', 'width' => '48', 'contentClasses' => 'c-dropdown__menu'])

@php
    $alignmentClasses = match ($align) {
        'left' => 'c-dropdown__content--align-left',
        'top' => 'c-dropdown__content--align-top',
        default => 'c-dropdown__content--align-right',
    };

    $width = match ($width) {
        '48' => 'c-dropdown__content--w48',
        default => $width,
    };
@endphp

<div class="c-dropdown" data-dropdown>
    <div data-dropdown-trigger aria-haspopup="true" aria-expanded="false">
        {{ $trigger }}
    </div>

    <div class="c-dropdown__content {{ $width }} {{ $alignmentClasses }}" data-dropdown-menu hidden>
        <div class="{{ $contentClasses }}">
            {{ $content }}
        </div>
    </div>
</div>
