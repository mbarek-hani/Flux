<x-card>
    <div class="p-section__header">
        <h3 class="p-section__title">Profile information</h3>
        <p class="p-section__subtitle">Update your name and email address.</p>
    </div>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}">
        @csrf
        @method('patch')

        <div class="p-form-group">
            <x-input
                name="name"
                label="Name"
                type="text"
                :required="true"
                :value="old('name', $user->name)"
                autocomplete="name"
            />

            <div>
                <x-input
                    name="email"
                    label="Email address"
                    type="email"
                    :required="true"
                    :value="old('email', $user->email)"
                    autocomplete="username"
                />

                @if($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                    <div class="p-mt-2">
                        <p class="p-text--muted">
                            Your email address is not verified.
                            <button form="send-verification" class="p-text--bold p-link-button">
                                Resend verification link
                            </button>
                        </p>

                        @if(session('status') === 'verification-link-sent')
                            <p class="p-flash p-flash--success p-mt-1">
                                A new verification link has been sent.
                            </p>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <div class="p-card-footer">
            <x-button variant="primary" size="sm" type="submit">
                Save
            </x-button>

            @if(session('status') === 'profile-updated')
                <span class="p-flash p-flash--success" data-flash data-flash-timeout="3000">
                    Saved.
                </span>
            @endif
        </div>
    </form>
</x-card>
