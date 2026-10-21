<x-app-layout>
    <x-slot name="title">
        Profile
    </x-slot>
    <div class="p-page">
        <div class="p-container p-container--sm p-stack">
            @include('profile.partials.update-profile-information-form')
            @include('profile.partials.update-password-form')
        </div>
    </div>
</x-app-layout>