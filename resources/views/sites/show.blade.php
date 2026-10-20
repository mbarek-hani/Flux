<x-app-layout>
    <x-slot name="title">
        Site: {{ $site->name }}
    </x-slot>
    <div class="p-page">
        <div class="p-container p-container--md p-stack">
            <div class="p-row p-row--between">
                <x-button href="{{ route('sites.index') }}">
                    <x-custom-icon name="arrow-left" class="c-icon c-icon--sm"></x-custom-icon>
                    Back
                </x-button>
            </div>

            <x-card title="Information">
                <div class="p-grid p-grid--2 p-grid--gap-md u-text-sm">
                    <div>
                        <span class="p-info-label">Domain</span>
                        <p class="p-info-value">{{ $site->domain }}</p>
                    </div>
                    <div>
                        <span class="p-info-label">Status</span>
                        <p class="p-info-value {{ $site->is_active ? 'p-info-value--active' : 'p-info-value--inactive' }}">
                            {{ $site->is_active ? 'Active' : 'Inactive' }}
                        </p>
                    </div>
                    <div>
                        <span class="p-info-label">Created</span>
                        <p class="p-info-value">{{ $site->created_at->format('d/m/Y \a\t H:i') }}</p>
                    </div>
                    <div>
                        <span class="p-info-label">Token</span>
                        <p class="p-info-value--mono">{{ $site->tracking_token }}</p>
                    </div>
                </div>
            </x-card>

            <x-card title="Tracking script">
                <p class="p-text p-mb-3">
                    Copy this code and paste it just before the <code class="p-code">&lt;/head&gt;</code> tag on your site.
                </p>

                <div class="p-copy-wrapper">
                    <pre class="p-code-block"><code id="tracking-script">{{ $site->getTrackingScript() }}</code></pre>

                    <button type="button"
                        class="p-copy-btn"
                        data-copy-button
                        data-copy-target="tracking-script"
                        data-copy-label="Copy tracking script"
                        data-copy-success-label="Tracking script copied"
                        title="Copy tracking script"
                        aria-label="Copy tracking script">
                        <span class="p-copy-btn__icon p-copy-btn__icon--copy">
                            <x-custom-icon name="clipboard" class="c-icon--sm c-icon--gray-500" />
                        </span>
                        <span class="p-copy-btn__icon p-copy-btn__icon--success">
                            <x-custom-icon name="check" class="c-icon--sm c-icon--success" />
                        </span>
                    </button>
                </div>
            </x-card>

            @hook('site.show.after', ['site' => $site])
        </div>
    </div>
</x-app-layout>
