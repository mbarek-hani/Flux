<x-app-layout>
    <x-slot name="titre">
        Détails du Plugin - {{ $plugin['nom'] }}
    </x-slot>

    <div class="p-page" x-data="marketplaceInstaller()">
        <div class="p-container p-container--lg">
            
            {{-- En-tête de la page --}}
            <div class="p-page__header u-mb-3 p-row p-row--between">
                <div>
                    <h2 class="p-page__title" style="margin: 0; font-size: 1.5rem; font-weight: 700; color: var(--gray-900);">
                        {{ $plugin['nom'] }}
                    </h2>
                    <p class="p-text--xs u-text-gray-600">Consultez les détails et gérez l'état de ce plugin de la boutique.</p>
                </div>
                <x-button href="{{ route('marketplace.index') }}" variant="default" size="sm" style="border-radius: 0.375rem;">
                    <x-custom-icon name="chevron-left" class="c-icon--xs" />
                    <span>Retour</span>
                </x-button>
            </div>

            {{-- Contenu du Plugin --}}
            <div class="p-grid p-grid--1 p-grid--3 p-grid--gap-md u-mt-4" style="margin-top: 2rem; display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;">
                
                {{-- Colonne principale --}}
                <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                    
                    {{-- Carte d'informations principales --}}
                    <x-card>
                        <div style="padding: 1.5rem 0.5rem 0.5rem;">
                            <div class="p-row p-mb-3" style="align-items: center; gap: 0.75rem;">
                                <div style="background-color: var(--gray-100); padding: 0.75rem; border-radius: 0.5rem;">
                                    <x-custom-icon name="puzzle" class="c-icon--md u-text-gray-600" />
                                </div>
                                <div>
                                    <h3 style="margin: 0; font-size: 1.25rem; font-weight: 700; color: var(--gray-900);">
                                        {{ $plugin['nom'] }}
                                    </h3>
                                    <p style="margin: 0.125rem 0 0; font-size: 0.85rem; color: var(--gray-500);">
                                        Par <span style="font-weight: 600; color: var(--gray-700);">{{ $plugin['author'] }}</span>
                                    </p>
                                </div>
                            </div>

                            @if($plugin['description'])
                                <div style="margin-top: 1.5rem;">
                                    <h4 style="font-size: 0.95rem; font-weight: 600; color: var(--gray-700); margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.05em;">Description</h4>
                                    <p class="p-text" style="line-height: 1.6; color: var(--gray-600); font-size: 0.925rem;">
                                        {{ $plugin['description'] }}
                                    </p>
                                </div>
                            @endif
                        </div>
                    </x-card>

                </div>

                {{-- Barre latérale d'état --}}
                <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                    
                    {{-- Carte d'actions et état --}}
                    <x-card style="border: 1px solid var(--gray-200);">
                        <h4 style="font-size: 0.9rem; font-weight: 700; color: var(--gray-700); margin-bottom: 1rem; border-bottom: 1px solid var(--gray-100); padding-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.05em;">
                            État du plugin
                        </h4>
                        
                        <div class="p-stack--md" style="display: flex; flex-direction: column; gap: 1rem;">
                            
                            {{-- Statut --}}
                            <div class="p-row p-row--between" style="align-items: center; font-size: 0.85rem;">
                                <span style="color: var(--gray-500);">Statut actuel :</span>
                                @if($plugin['status'] === 'installed')
                                    <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 0.25rem;">
                                        <span class="p-badge p-badge--success" style="font-weight: 600;">
                                            Installé
                                        </span>
                                        @if($plugin['actif'])
                                            <span class="p-badge p-badge--info" style="font-size: 0.75rem;">Actif</span>
                                        @else
                                            <span class="p-badge p-badge--neutral" style="font-size: 0.75rem;">Inactif</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="p-badge p-badge--neutral" style="font-weight: 600;">
                                        Non installé
                                    </span>
                                @endif
                            </div>

                            {{-- Version disponible --}}
                            <div class="p-row p-row--between" style="align-items: center; font-size: 0.85rem;">
                                <span style="color: var(--gray-500);">Version disponible :</span>
                                <span style="font-weight: 600; color: var(--gray-900);">{{ $plugin['current_version'] }}</span>
                            </div>

                            {{-- Version installée --}}
                            @if($plugin['status'] === 'installed')
                                <div class="p-row p-row--between" style="align-items: center; font-size: 0.85rem;">
                                    <span style="color: var(--gray-500);">Version installée :</span>
                                    <span style="font-weight: 600; color: var(--gray-900);">{{ $plugin['installed_version'] }}</span>
                                </div>
                            @endif

                            {{-- Téléchargements --}}
                            <div class="p-row p-row--between" style="align-items: center; font-size: 0.85rem;">
                                <span style="color: var(--gray-500);">Téléchargements :</span>
                                <span style="font-weight: 600; color: var(--gray-900);">{{ number_format($plugin['total_downloads']) }}</span>
                            </div>

                            {{-- Licence --}}
                            <div class="p-row p-row--between" style="align-items: center; font-size: 0.85rem;">
                                <span style="color: var(--gray-500);">Licence :</span>
                                <span style="font-weight: 600; color: var(--gray-900); text-transform: uppercase;">{{ $plugin['licence'] }}</span>
                            </div>

                            {{-- Dépôt code source --}}
                            @if($plugin['repo_url'])
                                <div style="margin-top: 0.5rem; border-top: 1px solid var(--gray-100); padding-top: 1rem;">
                                    <x-button href="{{ $plugin['repo_url'] }}" target="_blank" variant="default" class="u-w-full u-flex u-justify-center">
                                        <span>Consulter le dépôt</span>
                                    </x-button>
                                </div>
                            @endif

                            {{-- Boutons d'action --}}
                            <div style="margin-top: 0.5rem; border-top: 1px solid var(--gray-100); padding-top: 1rem;">
                                @if($plugin['status'] === 'installed')
                                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                                        @if($plugin['update_available'])
                                            <x-button variant="primary" class="u-w-full u-flex u-justify-center" @click="lancerAction('{{ $plugin['id'] }}', '{{ $plugin['nom'] }}', 'mise_a_jour')">
                                                <span>Mettre à jour</span>
                                            </x-button>
                                        @endif
                                        
                                        <x-button variant="danger" class="u-w-full u-flex u-justify-center" @click="lancerAction('{{ $plugin['id'] }}', '{{ $plugin['nom'] }}', 'desinstallation')">
                                            <span>Désinstaller</span>
                                        </x-button>
                                    </div>
                                @else
                                    <x-button variant="primary" class="u-w-full u-flex u-justify-center" @click="lancerAction('{{ $plugin['id'] }}', '{{ $plugin['nom'] }}', 'installation')">
                                        <span>Installer le plugin</span>
                                    </x-button>
                                @endif
                            </div>

                        </div>
                    </x-card>

                </div>

            </div>

        </div>

        {{-- Modal de progression en temps réel (SSE) --}}
        <x-modal name="progress-modal" focusable>
            <div class="c-progress-container">
                <h3 class="c-progress-title">
                    <span x-text="actionType === 'installation' ? 'Installation de ' : (actionType === 'mise_a_jour' ? 'Mise à jour de ' : 'Désinstallation de ')"></span>
                    <span class="p-text--bold" x-text="pluginNom"></span>
                </h3>
                
                {{-- Barre de progression --}}
                <div class="c-progress-bar">
                    <div class="c-progress-bar__fill" :style="'width: ' + progress + '%'"></div>
                </div>
                
                <p class="c-progress-status" x-text="statusMessage"></p>
                
                {{-- Terminal de logs --}}
                <div class="c-progress-steps" id="c-progress-console">
                    <template x-for="step in steps">
                        <div class="c-progress-step" :class="{ 'c-progress-step--success': step.type === 'success', 'c-progress-step--error': step.type === 'error' }">
                            <x-custom-icon name="check" class="c-progress-step__icon" x-show="step.type === 'success'" />
                            <x-custom-icon name="x-mark" class="c-progress-step__icon" x-show="step.type === 'error'" />
                            <x-custom-icon name="chevron-right" class="c-progress-step__icon" x-show="step.type === 'info'" />
                            <span x-text="step.text"></span>
                        </div>
                    </template>
                </div>
                
                {{-- Actions --}}
                <div class="c-progress-action-btn">
                    <x-button variant="default" size="sm" @click="fermerEtRecharger" x-show="completed || errorOccurred">
                        <span x-text="completed ? 'Terminer' : 'Fermer'"></span>
                    </x-button>
                </div>
            </div>
        </x-modal>
    </div>

    @push('scripts')
        <script>
            function marketplaceInstaller() {
                return {
                    showModal: false,
                    pluginNom: '',
                    actionType: 'installation',
                    progress: 0,
                    statusMessage: 'Connexion...',
                    steps: [],
                    errorOccurred: false,
                    completed: false,
                    eventSource: null,

                    lancerAction(id, nom, type) {
                        this.pluginNom = nom;
                        this.actionType = type;
                        this.progress = 0;
                        this.statusMessage = 'Connexion en cours...';
                        this.steps = [];
                        this.errorOccurred = false;
                        this.completed = false;
                        
                        this.showModal = true;
                        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'progress-modal' }));

                        let streamUrl = '';
                        if (type === 'installation') {
                            streamUrl = `/marketplace/install/${id}/stream`;
                        } else if (type === 'mise_a_jour') {
                            streamUrl = `/marketplace/update/${id}/stream`;
                        } else {
                            streamUrl = `/marketplace/uninstall/${id}/stream`;
                        }

                        if (this.eventSource) {
                            this.eventSource.close();
                        }

                        this.eventSource = new EventSource(streamUrl);

                        this.eventSource.onmessage = (event) => {
                            try {
                                const data = JSON.parse(event.data);
                                
                                if (data.status === 'progress') {
                                    this.progress = data.progress;
                                    this.statusMessage = data.message;
                                    this.steps.push({ text: data.message, type: 'info' });
                                } else if (data.status === 'success') {
                                    this.progress = 100;
                                    this.statusMessage = data.message || 'Opération terminée avec succès !';
                                    this.steps.push({ text: this.statusMessage, type: 'success' });
                                    this.completed = true;
                                    this.eventSource.close();
                                } else if (data.status === 'error') {
                                    this.progress = 100;
                                    this.statusMessage = data.message || 'Une erreur est survenue lors de l\'opération.';
                                    this.steps.push({ text: this.statusMessage, type: 'error' });
                                    this.errorOccurred = true;
                                    this.eventSource.close();
                                }
                            } catch (e) {
                                console.error("Erreur de parsing JSON SSE :", e);
                            }

                            this.$nextTick(() => {
                                const consoleDiv = document.getElementById('c-progress-console');
                                if (consoleDiv) {
                                    consoleDiv.scrollTop = consoleDiv.scrollHeight;
                                }
                            });
                        };

                        this.eventSource.onerror = (err) => {
                            if (!this.completed && !this.errorOccurred) {
                                this.steps.push({ text: 'Erreur réseau ou déconnexion du flux en direct.', type: 'error' });
                                this.errorOccurred = true;
                                this.statusMessage = 'Erreur réseau';
                            }
                            this.eventSource.close();
                        };
                    },

                    fermerEtRecharger() {
                        if (this.eventSource) {
                            this.eventSource.close();
                        }
                        this.showModal = false;
                        window.dispatchEvent(new CustomEvent('close-modal', { detail: 'progress-modal' }));
                        
                        if (this.completed) {
                            window.location.href = "{{ route('marketplace.index') }}";
                        }
                    }
                };
            }
        </script>
    @endpush
</x-app-layout>
