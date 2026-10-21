@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation" class="p-row p-row--between p-mt-3">
        <div class="p-row">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <x-button size="sm" variant="default" disabled>
                    <x-custom-icon name="chevron-left" class="c-icon--xs" />
                    <span class="u-hidden-mobile">Previous</span>
                </x-button>
            @else
                <x-button :href="$paginator->previousPageUrl()" size="sm" variant="default" rel="prev">
                    <x-custom-icon name="chevron-left" class="c-icon--xs" />
                    <span class="u-hidden-mobile">Previous</span>
                </x-button>
            @endif
        </div>

        {{-- Page Numbers --}}
        <div class="p-row u-hidden-mobile">
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <span class="p-text--xs u-text-gray-400 p-pagination-separator">{{ $element }}</span>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <x-button size="sm" variant="primary">
                                {{ $page }}
                            </x-button>
                        @else
                            <x-button :href="$url" size="sm" variant="default">
                                {{ $page }}
                            </x-button>
                        @endif
                    @endforeach
                @endif
            @endforeach
        </div>

        <div class="p-row">
            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <x-button :href="$paginator->nextPageUrl()" size="sm" variant="default" rel="next">
                    <span class="u-hidden-mobile">Next</span>
                    <x-custom-icon name="chevron-right" class="c-icon--xs" />
                </x-button>
            @else
                <x-button size="sm" variant="default" disabled>
                    <span class="u-hidden-mobile">Next</span>
                    <x-custom-icon name="chevron-right" class="c-icon--xs" />
                </x-button>
            @endif
        </div>
    </nav>
@endif
