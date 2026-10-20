@props([
    'name',
    'show' => false,
    'maxWidth' => '2xl',
    'closable' => true,
    'focusable' => false,
])

@php
    $maxWidth = [
        'sm' => 'c-modal__content--sm',
        'md' => 'c-modal__content--md',
        'lg' => 'c-modal__content--lg',
        'xl' => 'c-modal__content--xl',
        '2xl' => 'c-modal__content--2xl',
    ][$maxWidth];

    $isClosable = filter_var($closable, FILTER_VALIDATE_BOOL);
    $isFocusable = filter_var($focusable, FILTER_VALIDATE_BOOL);
@endphp

<div
    class="c-modal"
    data-modal="{{ $name }}"
    data-modal-closable="{{ $isClosable ? 'true' : 'false' }}"
    data-modal-focusable="{{ $isFocusable ? 'true' : 'false' }}"
    role="dialog"
    aria-modal="true"
    aria-hidden="{{ $show ? 'false' : 'true' }}"
    @if (! $show) hidden @endif
>
    <div class="c-modal__overlay" data-modal-overlay>
        <div class="c-modal__backdrop"></div>
    </div>

    <div class="c-modal__content {{ $maxWidth }}">
        {{ $slot }}
    </div>
</div>
