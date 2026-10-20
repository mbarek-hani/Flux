@props(['type' => 'success', 'message' => ''])

@php
    $modifier = match ($type) {
        'success' => 'c-alert--success',
        'error' => 'c-alert--error',
        'warning' => 'c-alert--warning',
        default => 'c-alert--info',
    };
@endphp

<div
    class="c-alert-wrapper"
    data-alert
    data-alert-timeout="5000"
    role="{{ in_array($type, ['error', 'warning'], true) ? 'alert' : 'status' }}"
>
    <div class="c-alert {{ $modifier }}">
        <div class="c-alert__message">
            @if($message)
                <p>{{ $message }}</p>
            @endif
            {{ $slot }}
        </div>

        <button type="button" class="c-alert__close" data-alert-close aria-label="Dismiss notification">
            <x-custom-icon name="x-mark" class="c-icon--sm" />
        </button>
    </div>
</div>
