<x-app-layout>
    <x-slot name="title">
        Réglages
    </x-slot>
    <div class="p-page">
        <div class="p-container p-container--sm p-stack">
            <h2 class="p-page__title">Réglages</h2>
            <x-card x-data="themeSwitcher()">
                <div class="p-section__header--with-icon">
                    <x-custom-icon name="swatch" class="p-section__icon" />
                    <h4 class="p-section__title">Apparence</h4>
                    <span class="p-section__badge">Core</span>
                </div>

                <p class="p-text">Choisissez le thème de l'application :</p>

                <div class="c-theme-switcher">
                    <button type="button" class="c-theme-card" :class="{ 'c-theme-card--active': theme === 'light' }" @click="setTheme('light')">
                        <div class="c-theme-preview c-theme-preview--light">
                            <div class="c-theme-preview__header"></div>
                            <div class="c-theme-preview__line"></div>
                            <div class="c-theme-preview__line c-theme-preview__line--short"></div>
                        </div>
                        <span class="p-text--bold" style="font-size: 0.875rem;">Clair</span>
                    </button>

                    <button type="button" class="c-theme-card" :class="{ 'c-theme-card--active': theme === 'dark' }" @click="setTheme('dark')">
                        <div class="c-theme-preview c-theme-preview--dark">
                            <div class="c-theme-preview__header"></div>
                            <div class="c-theme-preview__line"></div>
                            <div class="c-theme-preview__line c-theme-preview__line--short"></div>
                        </div>
                        <span class="p-text--bold" style="font-size: 0.875rem;">Sombre</span>
                    </button>
                </div>
            </x-card>

            @forelse($settings as $slug => $plugin)
                <x-card x-data="pluginSettings('{{ $slug }}', {{ json_encode($values[$slug] ?? []) }})">
                    <div class="p-section__header--with-icon">
                        <x-custom-icon name="puzzle" class="p-section__icon" />
                        <h4 class="p-section__title">{{ $plugin['name'] }}</h4>
                        <span class="p-section__badge">Plugin</span>
                    </div>

                    <form @submit.prevent="save" class="p-form-group">
                        @foreach($plugin['fields'] as $field)
                            <div>
                                <label class="c-input-label">
                                    {{ $field['label'] }}
                                    @if($field['required'] ?? false)
                                        <span class="c-input-required">*</span>
                                    @endif
                                </label>

                                @if(($field['type'] ?? 'text') === 'select')
                                    <select x-model="values['{{ $field['key'] }}']" class="c-input" {{ ($field['required'] ?? false) ? 'required' : '' }}>
                                        @foreach($field['options'] ?? [] as $option)
                                            <option value="{{ $option }}">{{ ucfirst($option) }}</option>
                                        @endforeach
                                    </select>
                                @elseif(($field['type'] ?? 'text') === 'textarea')
                                    <textarea x-model="values['{{ $field['key'] }}']" rows="3" class="c-input" {{ ($field['required'] ?? false) ? 'required' : '' }}></textarea>
                                @else
                                    <input type="{{ $field['type'] ?? 'text' }}" x-model="values['{{ $field['key'] }}']"
                                        placeholder="{{ $field['placeholder'] ?? '' }}" class="c-input" {{ ($field['required'] ?? false) ? 'required' : '' }}>
                                @endif

                                @if(isset($field['help']))
                                    <p class="c-input-help">{{ $field['help'] }}</p>
                                @endif
                            </div>
                        @endforeach
                    </form>

                    <div class="p-card-footer">
                        <x-button variant="primary" size="sm" @click="$el.closest('.c-card').querySelector('form').requestSubmit()" x-bind:disabled="saving">
                            <span x-text="saving ? 'Sauvegarde...' : 'Sauvegarder'"></span>
                        </x-button>

                        <div x-show="saved" x-cloak>
                            <template x-if="saved">
                                <x-alert type="success" message="Réglages sauvegardés avec succès." />
                            </template>
                        </div>

                        <div x-show="error" x-cloak>
                            <template x-if="error">
                                <x-alert type="error">
                                    <span x-text="error"></span>
                                </x-alert>
                            </template>
                        </div>
                    </div>
                </x-card>
            @empty
            @endforelse
        </div>
    </div>

    @push('scripts')
        <script>
            function themeSwitcher() {
                return {
                    theme: localStorage.getItem('theme') || 'light',
                    init() {
                        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                            this.theme = 'dark';
                        }
                    },
                    setTheme(value) {
                        this.theme = value;
                        localStorage.setItem('theme', value);
                        if (value === 'dark') {
                            document.documentElement.setAttribute('data-theme', 'dark');
                        } else {
                            document.documentElement.removeAttribute('data-theme');
                        }
                    }
                }
            }

            function pluginSettings(slug, initialValues) {
                return {
                    values: initialValues || {},
                    saving: false,
                    saved: false,
                    error: null,

                    async save() {
                        this.saving = true;
                        this.saved = false;
                        this.error = null;

                        try {
                            const response = await fetch('{{ route('settings.update') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                },
                                body: JSON.stringify({
                                    plugin: slug,
                                    settings: this.values,
                                }),
                            });

                            const data = await response.json();

                            if (response.ok) {
                                this.saved = true;
                                setTimeout(() => this.saved = false, 3000);
                            } else {
                                this.error = data.error ?? 'Erreur inconnue.';
                            }
                        } catch (exception) {
                            this.error = exception.message;
                        } finally {
                            this.saving = false;
                        }
                    },
                }
            }
        </script>
    @endpush
</x-app-layout>
