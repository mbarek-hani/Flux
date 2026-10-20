<x-app-layout>
    <x-slot name="titre">
        Sites
    </x-slot>
    <div class="p-page">
        <div class="p-container p-container--lg">
            <div class="p-page__header u-mb-3">
                <h2 class="p-page__title u-mb-0">Sites</h2>
                <x-button variant="primary" href="{{ route('sites.create') }}">
                    <x-custom-icon name="plus" class="c-icon--sm" />
                    Ajouter un site
                </x-button>
            </div>
            
            @if($sites->isEmpty())
                <x-card>
                    <div class="p-empty">
                        <x-custom-icon name="globe" class="p-empty__icon" />
                        <p class="p-empty__text">Aucun site pour le moment.</p>
                    </div>
                </x-card>
            @else
                <div class="p-grid p-grid--1 p-grid--2 p-grid--3 p-grid--4 p-grid--gap-md">
                    @foreach($sites as $site)
                        <x-card class="u-h-full u-flex-col">
                            <div class="u-flex-1">
                                <div class="p-row p-mb-1">
                                    <div class="p-status-dot {{ $site->is_active ? 'p-status-dot--active' : 'p-status-dot--inactive' }}"></div>
                                    <h3 class="p-section__title u-flex-1">{{ $site->name }}</h3>
                                </div>
                                <p class="p-text u-text-gray-600 u-break-all">{{ $site->domain }}</p>
                                <div class="p-mt-2">
                                    <p class="p-text--xs u-text-gray-400">
                                        <x-custom-icon name="chart-bar" class="c-icon--xs" />
                                        {{ $site->visits_count ?? 0 }} visites enregistrées
                                    </p>
                                </div>
                            </div>
                            
                            <div class="p-card-footer u-justify-between">
                                <div class="p-actions">
                                    <form method="POST" action="{{ route('sites.toggle-active', $site) }}">
                                        @csrf
                                        <x-button size="sm" type="submit" variant="{{ $site->is_active ? 'default' : 'primary' }}" title="{{ $site->is_active ? 'Désactiver' : 'Activer' }}">
                                            <x-custom-icon name="{{ $site->is_active ? 'stop' : 'play' }}" class="c-icon--xs" />
                                        </x-button>
                                    </form>
                                    <x-button href="{{ route('sites.show', $site) }}" size="sm" title="Détails & Script">
                                        <x-custom-icon name="code" class="c-icon--xs" />
                                    </x-button>
                                </div>
                                
                                <form method="POST" action="{{ route('sites.destroy', $site) }}" onsubmit="return confirm('Supprimer ce site ?')">
                                    @csrf
                                    @method('DELETE')
                                    <x-button size="sm" variant="danger" type="submit" title="Supprimer">
                                        <x-custom-icon name="trash" class="c-icon--xs" />
                                    </x-button>
                                </form>
                            </div>
                        </x-card>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>