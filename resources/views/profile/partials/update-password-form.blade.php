<x-card>
    <div class="p-section__header">
        <h3 class="p-section__title">Password</h3>
        <p class="p-section__subtitle">Use a long, unique password.</p>
    </div>

    <form method="post" action="{{ route('password.update') }}">
        @csrf
        @method('put')

        <div class="p-form-group">
            <x-input
                name="current_password"
                label="Current password"
                type="password"
                :required="true"
                autocomplete="current-password"
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

        <div class="p-card-footer">
            <x-button variant="primary" size="sm" type="submit">
                Update password
            </x-button>

            @if(session('status') === 'password-updated')
                <span class="p-flash p-flash--success" data-flash data-flash-timeout="3000">
                    Password updated.
                </span>
            @endif
        </div>
    </form>
</x-card>
