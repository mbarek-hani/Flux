<x-guest-layout>
    <h2 class="p-auth__title">Forgot password</h2>
    <p class="p-auth__text">
        Enter your email address and we will send you a password reset link.
    </p>

    @if(session('status'))
        <div class="p-auth__status">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="p-auth__form-group">
            <x-input
                name="email"
                label="Email address"
                type="email"
                :required="true"
                :value="old('email')"
                autocomplete="username"
            />
        </div>

        <div class="p-auth__submit">
            <x-button variant="primary" type="submit">
                Send reset link
            </x-button>
        </div>

        <p class="p-auth__footer">
            <a href="{{ route('login') }}" class="p-auth__link">
                Back to sign in
            </a>
        </p>
    </form>
</x-guest-layout>
