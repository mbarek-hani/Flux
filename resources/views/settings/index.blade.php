<x-app-layout>
    <x-slot name="title">Settings</x-slot>

    <div class="p-page">
        <div class="p-container p-container--sm p-stack">
            <h2 class="p-page__title">Settings</h2>

            <x-card>
                <div class="p-section__header--with-icon">
                    <x-custom-icon name="swatch" class="p-section__icon" />
                    <h4 class="p-section__title">Appearance</h4>
                    <span class="p-section__badge">Core</span>
                </div>

                <p class="p-text">Choose the application theme:</p>

                <div class="c-theme-switcher" data-theme-switcher>
                    <button type="button" class="c-theme-card" data-theme-value="light" aria-pressed="false">
                        <div class="c-theme-preview c-theme-preview--light">
                            <div class="c-theme-preview__header"></div>
                            <div class="c-theme-preview__line"></div>
                            <div class="c-theme-preview__line c-theme-preview__line--short"></div>
                        </div>
                        <span class="c-theme-card__label">Light</span>
                    </button>

                    <button type="button" class="c-theme-card" data-theme-value="dark" aria-pressed="false">
                        <div class="c-theme-preview c-theme-preview--dark">
                            <div class="c-theme-preview__header"></div>
                            <div class="c-theme-preview__line"></div>
                            <div class="c-theme-preview__line c-theme-preview__line--short"></div>
                        </div>
                        <span class="c-theme-card__label">Dark</span>
                    </button>
                </div>
            </x-card>

            @forelse($settings as $slug => $plugin)
                <x-card>
                    <div class="p-section__header--with-icon">
                        <x-custom-icon name="puzzle" class="p-section__icon" />
                        <h4 class="p-section__title">{{ $plugin['name'] }}</h4>
                        <span class="p-section__badge">Plugin</span>
                    </div>

                    <form
                        class="p-form-group"
                        data-plugin-settings
                        data-slug="{{ $slug }}"
                        data-update-url="{{ route('settings.update') }}"
                    >
                        @foreach($plugin['fields'] as $field)
                            @php
                                $key = $field['key'];
                                $value = $values[$slug][$key] ?? $field['default'] ?? '';
                                $required = $field['required'] ?? false;
                            @endphp

                            <div>
                                <label class="c-input-label" for="setting-{{ $slug }}-{{ $key }}">
                                    {{ $field['label'] }}
                                    @if($required)
                                        <span class="c-input-required">*</span>
                                    @endif
                                </label>

                                @if(($field['type'] ?? 'text') === 'select')
                                    <select
                                        id="setting-{{ $slug }}-{{ $key }}"
                                        name="settings[{{ $key }}]"
                                        class="c-input"
                                        @required($required)
                                    >
                                        @foreach($field['options'] ?? [] as $option)
                                            <option value="{{ $option }}" @selected((string) $value === (string) $option)>
                                                {{ ucfirst($option) }}
                                            </option>
                                        @endforeach
                                    </select>
                                @elseif(($field['type'] ?? 'text') === 'textarea')
                                    <textarea
                                        id="setting-{{ $slug }}-{{ $key }}"
                                        name="settings[{{ $key }}]"
                                        rows="3"
                                        class="c-input"
                                        @required($required)
                                    >{{ $value }}</textarea>
                                @else
                                    <input
                                        id="setting-{{ $slug }}-{{ $key }}"
                                        type="{{ $field['type'] ?? 'text' }}"
                                        name="settings[{{ $key }}]"
                                        value="{{ $value }}"
                                        placeholder="{{ $field['placeholder'] ?? '' }}"
                                        class="c-input"
                                        @required($required)
                                    >
                                @endif

                                @if(isset($field['help']))
                                    <p class="c-input-help">{{ $field['help'] }}</p>
                                @endif
                            </div>
                        @endforeach

                        <div class="p-card-footer">
                            <x-button
                                variant="primary"
                                size="sm"
                                type="submit"
                                data-plugin-settings-submit
                            >
                                Save
                            </x-button>

                            <p class="p-flash p-flash--success" data-plugin-settings-success hidden>Settings saved.</p>
                            <p class="p-flash p-flash--error" data-plugin-settings-error hidden></p>
                        </div>
                    </form>
                </x-card>
            @empty
            @endforelse

            @hook('settings.sections')
        </div>
    </div>
</x-app-layout>
