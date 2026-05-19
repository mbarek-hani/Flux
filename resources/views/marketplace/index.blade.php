<x-app-layout>
    <x-slot name="titre">
        Marketplace de Plugins
    </x-slot>

    <div class="p-page" x-data="marketplaceInstaller()" x-on:modal-closed.window="if ($event.detail === 'progress-modal') fermerEtRecharger()">
        <div class="p-container p-container--lg">

            {{-- En-tête de la page --}}
            <div class="p-page__header u-mb-3">
                <div>
                    <h2 class="p-page__title">Marketplace de Plugins</h2>
                    <p class="u-text-gray-600">Découvrez et installez de nouvelles fonctionnalités pour enrichir votre tableau de bord Flux.</p>
                </div>
            </div>

            {{-- Barre de filtres et recherche --}}
            <div class="p-row p-row--between p-row--wrap u-mb-3" style="gap: 1rem; justify-content: flex-end;">
                {{-- Formulaire de recherche --}}
                <form method="GET" action="{{ route('marketplace.index') }}" class="p-row" style="flex-grow: 1; max-width: 24rem;">
                    <div style="flex-grow: 1;">
                        <x-input
                            name="search"
                            placeholder="Rechercher un plugin..."
                            value="{{ $search }}"
                            class="u-w-full"
                        />
                    </div>
                    <x-button type="submit" variant="primary">
                        <x-custom-icon name="magnifying-glass" class="c-icon--xs" />
                        <span>Recherche</span>
                    </x-button>
                </form>
            </div>

            {{-- Grille des plugins --}}
            @if(empty($plugins))
                <x-card class="u-mt-4" style="margin-top: 1.5rem;">
                    <div class="p-empty">
                        <x-custom-icon name="puzzle" class="p-empty__icon" />
                        <h3 class="p-empty__title">Aucun plugin trouvé</h3>
                        <p class="p-empty__text">
                            Nous n'avons trouvé aucun plugin dans la boutique correspondant à vos critères de recherche.
                        </p>
                    </div>
                </x-card>
            @else
                <div class="p-grid p-grid--1 p-grid--2 p-grid--3 p-grid--gap-md u-mt-4" style="margin-top: 1.5rem;">
                    @foreach($plugins as $plugin)
                        <x-card style="cursor: pointer;" @click="window.location.href = '{{ route('marketplace.show', $plugin['id']) }}'">
                            <div class="u-flex-1" style="padding-top: 0.5rem;">
                                <div class="p-row p-mb-1" style="align-items: center; justify-content: space-between; gap: 0.5rem;">
                                    <div class="p-row" style="align-items: center; gap: 0.5rem;">
                                        <x-custom-icon name="puzzle" class="c-icon--sm u-text-gray-400" />
                                        <h3 class="p-section__title" style="margin: 0; font-size: 1.05rem; font-weight: 600; color: var(--gray-900);">
                                            {{ $plugin['nom'] }}
                                        </h3>
                                    </div>
                                    <span class="p-badge p-badge--neutral" style="font-size: 0.7rem; font-weight: 600;">{{ $plugin['current_version'] }}</span>
                                </div>

                                <div class="p-row p-row--between" style="align-items: center; margin-top: 1rem; padding-top: 0.75rem; border-top: 1px solid var(--gray-300);">
                                    <div class="p-row" style="align-items: center; gap: 0.375rem; color: var(--gray-500); font-size: 0.75rem;">
                                        <span class="p-text--bold">{{ number_format($plugin['total_downloads']) }} Téléchargement</span>
                                    </div>

                                    <div>
                                        @if($plugin['status'] === 'installed')
                                            <span class="p-badge p-badge--success" style="font-size: 0.7rem;">
                                                Installé
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="p-card-footer p-mt-2" style="border-top: none; padding-top: 0.5rem;" @click.stop>
                                <div class="p-actions u-flex u-justify-end">
                                    @if($plugin['status'] === 'installed')
                                        @if($plugin['update_available'])
                                            <x-button variant="primary" size="sm" @click.stop="lancerAction('{{ $plugin['id'] }}', '{{ $plugin['nom'] }}', 'mise_a_jour', '{{ $plugin['installed_version'] }}', '{{ $plugin['current_version'] }}')" title="Mettre à jour">
                                                Mettre à jour
                                            </x-button>
                                        @endif

                                        <x-button variant="danger" size="sm" @click.stop="lancerAction('{{ $plugin['id'] }}', '{{ $plugin['nom'] }}', 'desinstallation')" title="Désinstaller">
                                            Désinstaller
                                        </x-button>
                                    @else
                                        <x-button variant="primary" size="sm" @click.stop="lancerAction('{{ $plugin['id'] }}', '{{ $plugin['nom'] }}', 'installation')" title="Installer">
                                            Installer
                                        </x-button>
                                    @endif
                                </div>
                            </div>
                        </x-card>
                    @endforeach
                </div>

                {{-- Pagination --}}
                @if(($meta['total_pages'] ?? 1) > 1)
                    <div class="p-row p-row--between p-mt-3" style="border-top: 1px solid var(--gray-200); padding-top: 1rem;">
                        <span class="p-text--xs">Page {{ $meta['page'] }} sur {{ $meta['total_pages'] }} (Total : {{ $meta['total'] }} plugins)</span>
                        <div class="p-actions">
                            @if($meta['page'] > 1)
                                <x-button href="?page={{ $meta['page'] - 1 }}&search={{ $search }}" size="sm">
                                    <x-custom-icon name="chevron-left" class="c-icon--xs" />
                                    <span>Précédent</span>
                                </x-button>
                            @endif
                            @if($meta['page'] < $meta['total_pages'])
                                <x-button href="?page={{ $meta['page'] + 1 }}&search={{ $search }}" size="sm">
                                    <span>Suivant</span>
                                    <x-custom-icon name="chevron-right" class="c-icon--xs" />
                                </x-button>
                            @endif
                        </div>
                    </div>
                @endif
            @endif

        </div>

        <x-modal name="progress-modal" focusable closable="completed || errorOccurred">
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
                    actionType: 'installation', // 'installation', 'mise_a_jour', 'desinstallation'
                    progress: 0,
                    statusMessage: 'Connexion...',
                    steps: [],
                    errorOccurred: false,
                    completed: false,
                    eventSource: null,

                    lancerAction(id, nom, type, installedVer = null, currentVer = null) {
                        if (type === 'mise_a_jour' && installedVer && currentVer) {
                            const getMajor = (v) => {
                                const clean = v.toLowerCase().replace(/^v/, '');
                                return parseInt(clean.split('.')[0]) || 0;
                            };
                            if (getMajor(installedVer) !== getMajor(currentVer)) {
                                const confirmUpdate = confirm(
                                    `Attention : Vous allez mettre à jour le plugin de la version ${installedVer} à la version ${currentVer}.\n\n` +
                                    `Il s'agit d'une mise à jour majeure. Toutes vos données associées à ce plugin seront définitivement effacées.\n\n` +
                                    `Voulez-vous vraiment continuer ?`
                                );
                                if (!confirmUpdate) {
                                    return;
                                }
                            }
                        }

                        this.pluginNom = nom;
                        this.actionType = type;
                        this.progress = 0;
                        this.statusMessage = 'Connexion en cours...';
                        this.steps = [];
                        this.errorOccurred = false;
                        this.completed = false;

                        // Déclencher l'affichage du modal
                        this.showModal = true;
                        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'progress-modal' }));

                        // Configurer le flux SSE
                        let streamUrl = '';
                        if (type === 'installation') {
                            streamUrl = `/marketplace/install/${id}/stream`;
                        } else if (type === 'mise_a_jour') {
                            streamUrl = `/marketplace/update/${id}/stream`;
                        } else {
                            streamUrl = `/marketplace/uninstall/${id}/stream`;
                        }

                        // Fermer une ancienne instance EventSource au cas où
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
                                    if (this.steps.length > 0) {
                                        this.steps[this.steps.length - 1].type = 'success';
                                    } else {
                                        this.steps.push({ text: this.statusMessage, type: 'success' });
                                    }
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

                            // Défilement automatique vers le bas pour le terminal de logs
                            this.$nextTick(() => {
                                const consoleDiv = document.getElementById('c-progress-console');
                                if (consoleDiv) {
                                    consoleDiv.scrollTop = consoleDiv.scrollHeight;
                                }
                            });
                        };

                        this.eventSource.onerror = (err) => {
                            // On vérifie si l'opération était déjà terminée avec succès pour éviter de masquer le succès par une fausse erreur de coupure SSE de fin
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

                        // Si l'action a réussi, on recharge la page pour voir le nouveau statut
                        if (this.completed) {
                            window.location.reload();
                        }
                    }
                };
            }
        </script>
    @endpush
</x-app-layout>
