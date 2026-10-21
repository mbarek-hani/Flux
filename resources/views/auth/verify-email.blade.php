<x-guest-layout>
    <h2 class="p-auth__title">Verify email</h2>
    <p class="p-auth__text">
        Thanks for signing up. Please verify your email address by clicking the link we just sent you.
    </p>

    @if(session('status') == 'verification-link-sent')
        <div class="p-auth__status">
            A new verification link has been sent.
        </div>
    @endif

    <div class="p-auth__actions">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-button variant="primary" type="submit">
                Resend verification link
            </x-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <x-button type="submit">
                Sign out
            </x-button>
        </form>
    </div>
</x-guest-layout>
