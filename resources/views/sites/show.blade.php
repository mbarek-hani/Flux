<x-app-layout>
    <x-slot name="titre">
        Site : {{ $site->name }}
    </x-slot>
    <div class="p-page">
        <div class="p-container p-container--md p-stack">
            <div class="p-row p-row--between">
                <x-button href="{{ route('sites.index') }}">
                    <x-custom-icon name="arrow-left" class="c-icon c-icon--sm"></x-custom-icon>
                    Retour
                </x-button>
            </div>

            {{-- Infos du site --}}
            <x-card titre="Informations">
                <div class="p-grid p-grid--2 p-grid--gap-md" style="font-size: 0.875rem;">
                    <div>
                        <span class="p-info-label">Domaine</span>
                        <p class="p-info-value">{{ $site->domain }}</p>
                    </div>
                    <div>
                        <span class="p-info-label">Statut</span>
                        <p class="p-info-value {{ $site->is_active ? 'p-info-value--active' : 'p-info-value--inactive' }}">
                            {{ $site->is_active ? 'Actif' : 'Inactif' }}
                        </p>
                    </div>
                    <div>
                        <span class="p-info-label">Créé le</span>
                        <p class="p-info-value">{{ $site->created_at->format('d/m/Y à H:i') }}</p>
                    </div>
                    <div>
                        <span class="p-info-label">Token</span>
                        <p class="p-info-value--mono">{{ $site->tracking_token }}</p>
                    </div>
                </div>
            </x-card>

            {{-- Script de tracking --}}
            <x-card titre="Script de tracking">
                <p class="p-text p-mb-3">
                    Copiez ce code et collez-le juste avant la balise <code
                        class="p-code">&lt;/head&gt;</code> de votre site.
                </p>

                <div x-data="{ copie: false }" class="p-copy-wrapper">
                    <pre
                        class="p-code-block"><code x-ref="code">{{ $site->getTrackingScript() }}</code></pre>

                    <button @click="
                            navigator.clipboard.writeText($refs.code.textContent);
                            copie = true;
                            setTimeout(() => copie = false, 2000);
                        "
                        class="p-copy-btn">
                        <span x-show="!copie" x-cloak>
                            <x-custom-icon name="clipboard" class="c-icon--sm c-icon--gray-500" />
                        </span>

                        <span x-show="copie" x-cloak>
                            <x-custom-icon name="check" class="c-icon--sm c-icon--success" />
                        </span>
                    </button>
                </div>
            </x-card>

        </div>
    </div>
</x-app-layout>
