<x-guest-layout>
    <h2 class="p-auth__title">Reset password</h2>

    <form method="POST" action="{{ route('password.store') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="p-auth__form-group">
            <x-input
                name="email"
                label="Email address"
                type="email"
                :required="true"
                :value="old('email', $request->email)"
                autocomplete="username"
            />

            <x-input
                name="password"
                label="New password"
                type="password"
                :required="true"
                autocomplete="new-password"
            />

            <x-input
                name="password_confirmation"
                label="Confirm password"
                type="password"
                :required="true"
                autocomplete="new-password"
            />
        </div>

        <div class="p-auth__submit">
            <x-button variant="primary" type="submit">
                Reset password
            </x-button>
        </div>
    </form>
</x-guest-layout>
