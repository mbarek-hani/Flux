<x-guest-layout>
    <h2 class="p-auth__title">Sign in</h2>

    @if(session('status'))
        <div class="p-auth__status">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
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

            <x-input
                name="password"
                label="Password"
                type="password"
                :required="true"
                autocomplete="current-password"
            />

            <div class="p-auth__options">
                <label class="p-auth__checkbox-label">
                    <input type="checkbox" name="remember" class="p-auth__checkbox">
                    <span class="p-auth__checkbox-text">Remember me</span>
                </label>

                @if(Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="p-auth__link">
                        Forgot your password?
                    </a>
                @endif
            </div>
        </div>

        <div class="p-auth__submit">
            <x-button variant="primary" type="submit">
                Sign in
            </x-button>
        </div>

        @if(Route::has('register'))
            <p class="p-auth__footer">
                Don't have an account?
                <a href="{{ route('register') }}" class="p-auth__footer-link">
                    Create one
                </a>
            </p>
        @endif
    </form>
</x-guest-layout>
