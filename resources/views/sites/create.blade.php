<x-app-layout>
    <x-slot name="title">
        Ajouter un site
    </x-slot>
    <div class="p-page">
        <div class="p-container p-container--sm">
            <x-card>
                <form method="POST" action="{{ route('sites.store') }}">
                    @csrf

                    <div class="p-form-group">
                        <x-input name="name" label="Nom du site" placeholder="Mon blog" :required="true"
                            value="{{ old('name') }}" />

                        <x-input name="domain" label="Domaine" placeholder="monsite.fr" :required="true"
                            aide="Sans http:// ni www. Exemple : monsite.fr" value="{{ old('domain') }}" />
                    </div>

                    <div class="p-card-footer">
                        <x-button variant="primary" type="submit">
                            Ajouter le site
                        </x-button>
                        <x-button href="{{ route('sites.index') }}">
                            Annuler
                        </x-button>
                    </div>
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>