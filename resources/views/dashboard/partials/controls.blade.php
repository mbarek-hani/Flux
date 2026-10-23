@php
    $periods = [
        ['value' => 'today', 'label' => 'Today'],
        ['value' => '7d', 'label' => '7 days'],
        ['value' => '30d', 'label' => '30 days'],
        ['value' => 'this_month', 'label' => 'This month'],
        ['value' => 'this_year', 'label' => 'This year'],
    ];
@endphp

<div class="p-dash__controls">
    <select class="p-dash__select" data-dashboard-site aria-label="Select site">
        @foreach($sites as $site)
            <option value="{{ $site->id }}" @selected($site->id === $currentSite->id)>
                {{ $site->name }} — {{ $site->domain }}
            </option>
        @endforeach
    </select>

    <div class="p-dash__periods" role="group" aria-label="Date range">
        @foreach($periods as $period)
            <button
                type="button"
                class="p-dash__period-btn {{ $period['value'] === '7d' ? 'p-dash__period-btn--active' : '' }}"
                data-dashboard-period="{{ $period['value'] }}"
            >
                {{ $period['label'] }}
            </button>
        @endforeach
    </div>

    <div class="p-dash__compact-controls">
        <select class="p-dash__period-mobile" data-dashboard-period-mobile aria-label="Date range">
            @foreach($periods as $period)
                <option value="{{ $period['value'] }}" @selected($period['value'] === '7d')>
                    {{ $period['label'] }}
                </option>
            @endforeach
        </select>

        <button
            type="button"
            class="p-dash__compare"
            data-dashboard-compare
            aria-pressed="false"
            title="Compare with previous period"
        >
            <x-custom-icon name="arrows-right-left" class="c-icon--sm" />
            <span class="p-dash__compare-label">Compare</span>
        </button>
    </div>
</div>

@if($toolbar !== [])
    <div class="p-dash__toolbar">
        @foreach($toolbar as $html)
            {!! $html !!}
        @endforeach
    </div>
@endif
