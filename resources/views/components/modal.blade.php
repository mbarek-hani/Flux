@props([
    'name',
    'show' => false,
    'maxWidth' => '2xl',
    'closable' => 'true'
])

@php
$maxWidth = [
    'sm' => 'c-modal__content--sm',
    'md' => 'c-modal__content--md',
    'lg' => 'c-modal__content--lg',
    'xl' => 'c-modal__content--xl',
    '2xl' => 'c-modal__content--2xl',
][$maxWidth];
@endphp

<div
    x-data="{
        show: @js($show),
        focusables() {
            // All focusable element types...
            let selector = 'a, button, input:not([type=\'hidden\']), textarea, select, details, [tabindex]:not([tabindex=\'-1\'])'
            return [...$el.querySelectorAll(selector)]
                // All non-disabled elements...
                .filter(el => ! el.hasAttribute('disabled'))
        },
        firstFocusable() { return this.focusables()[0] },
        lastFocusable() { return this.focusables().slice(-1)[0] },
        nextFocusable() { return this.focusables()[this.nextFocusableIndex()] || this.firstFocusable() },
        prevFocusable() { return this.focusables()[this.prevFocusableIndex()] || this.lastFocusable() },
        nextFocusableIndex() { return (this.focusables().indexOf(document.activeElement) + 1) % (this.focusables().length + 1) },
        prevFocusableIndex() { return Math.max(0, this.focusables().indexOf(document.activeElement)) -1 },
    }"
    x-init="$watch('show', value => {
        if (value) {
            document.body.style.overflowY = 'hidden';
            {{ $attributes->has('focusable') ? 'setTimeout(() => firstFocusable().focus(), 100)' : '' }}
        } else {
            document.body.style.overflowY = '';
        }
    })"
    x-on:open-modal.window="$event.detail == '{{ $name }}' ? show = true : null"
    x-on:close-modal.window="$event.detail == '{{ $name }}' ? show = false : null"
    x-on:close.stop="if ({{ $closable }}) { show = false; $dispatch('modal-closed', '{{ $name }}'); }"
    x-on:keydown.escape.window="if ({{ $closable }}) { show = false; $dispatch('modal-closed', '{{ $name }}'); }"
    x-on:keydown.tab.prevent="$event.shiftKey || nextFocusable().focus()"
    x-on:keydown.shift.tab.prevent="prevFocusable().focus()"
    x-show="show"
    class="c-modal"
    style="display: {{ $show ? 'block' : 'none' }};"
>
    <div
        x-show="show"
        class="c-modal__overlay"
        x-on:click="if ({{ $closable }}) { show = false; $dispatch('modal-closed', '{{ $name }}'); }"
        x-transition:enter="c-transition-modal-enter"
        x-transition:enter-start="c-transition-modal-enter-start"
        x-transition:enter-end="c-transition-modal-enter-end"
        x-transition:leave="c-transition-modal-leave"
        x-transition:leave-start="c-transition-modal-leave-start"
        x-transition:leave-end="c-transition-modal-leave-end"
    >
        <div class="c-modal__backdrop"></div>
    </div>

    <div
        x-show="show"
        class="c-modal__content {{ $maxWidth }}"
        x-transition:enter="c-transition-modal-content-enter"
        x-transition:enter-start="c-transition-modal-content-enter-start"
        x-transition:enter-end="c-transition-modal-content-enter-end"
        x-transition:leave="c-transition-modal-content-leave"
        x-transition:leave-start="c-transition-modal-content-leave-start"
        x-transition:leave-end="c-transition-modal-content-leave-end"
    >
        {{ $slot }}
    </div>
</div>
