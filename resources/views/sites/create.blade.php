<x-app-layout>
    <x-slot name="title">
        Add site
    </x-slot>
    <div class="p-page">
        <div class="p-container p-container--sm">
            <x-card>
                <form method="POST" action="{{ route('sites.store') }}">
                    @csrf

                    <div class="p-form-group">
                        <x-input name="name" label="Site name" placeholder="My blog" :required="true"
                            value="{{ old('name') }}" />

                        <x-input name="domain" label="Domain" placeholder="example.com" :required="true"
                            aide="Without http:// or www. Example: example.com" value="{{ old('domain') }}" />
                    </div>

                    <div class="p-card-footer">
                        <x-button variant="primary" type="submit">
                            Add site
                        </x-button>
                        <x-button href="{{ route('sites.index') }}">
                            Cancel
                        </x-button>
                    </div>
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
