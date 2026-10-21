<x-guest-layout>
    <h2 class="p-auth__title">Confirmation required</h2>
    <p class="p-auth__text">
        Please confirm your password before continuing.
    </p>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <div class="p-auth__form-group">
            <x-input
                name="password"
                label="Password"
                type="password"
                :required="true"
                autocomplete="current-password"
            />
        </div>

        <div class="p-auth__submit">
            <x-button variant="primary" type="submit">
                Confirm
            </x-button>
        </div>
    </form>
</x-guest-layout>
