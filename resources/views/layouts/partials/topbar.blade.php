<div class="l-topbar">
    <button type="button" class="l-topbar__btn" data-sidebar-open aria-label="Open sidebar">
        <x-custom-icon name="bars-3" class="c-icon--md" />
    </button>
    <span class="l-topbar__title">{{ config('app.name', 'Flux') }}</span>

    <div class="l-topbar__actions">
        @foreach($topbarActions as $html)
            {!! $html !!}
        @endforeach
    </div>
</div>
