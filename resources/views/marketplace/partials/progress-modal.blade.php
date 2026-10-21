<x-modal name="progress-modal" focusable :closable="false">
    <div class="c-progress-container" data-marketplace-progress>
        <h3 class="c-progress-title">
            <span data-marketplace-progress-verb>Installing</span>
            <span class="p-text--bold" data-marketplace-progress-plugin></span>
        </h3>

        <div class="c-progress-bar">
            <div class="c-progress-bar__fill" data-marketplace-progress-bar></div>
        </div>

        <p class="c-progress-status" data-marketplace-progress-status>Connecting...</p>

        <div class="c-progress-steps" data-marketplace-progress-steps></div>

        <div class="c-progress-action">
            <x-button variant="default" size="sm" data-marketplace-close hidden>
                Finish
            </x-button>
        </div>

        <div hidden>
            <template data-marketplace-progress-icon="success">
                <x-custom-icon name="check" class="c-progress-step__icon" />
            </template>
            <template data-marketplace-progress-icon="error">
                <x-custom-icon name="x-mark" class="c-progress-step__icon" />
            </template>
            <template data-marketplace-progress-icon="info">
                <x-custom-icon name="chevron-right" class="c-progress-step__icon" />
            </template>
        </div>
    </div>
</x-modal>
