<div class="l-sidebar__footer" data-dropdown>
    <button type="button" class="l-sidebar__user-btn" data-dropdown-trigger aria-expanded="false" aria-label="Open user menu">
        <div class="l-sidebar__user-avatar">
            <span class="l-sidebar__user-initial">
                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
            </span>
        </div>

        @if(! $collapsed)
            <div class="l-sidebar__user-info">
                <p class="l-sidebar__user-name">{{ Auth::user()->name }}</p>
                <p class="l-sidebar__user-email">{{ Auth::user()->email }}</p>
            </div>
            <div class="l-sidebar__user-icons">
                <x-custom-icon name="chevron-up" class="c-icon--sm c-icon--gray-900 c-icon--no-shrink" />
                <x-custom-icon name="chevron-down" class="c-icon--sm c-icon--gray-900 c-icon--no-shrink" />
            </div>
        @endif
    </button>

    <div class="l-sidebar__dropdown" data-dropdown-menu hidden>
        <a href="{{ route('settings.index') }}" class="l-sidebar__dropdown-link">
            <x-custom-icon name="cog" class="c-icon--sm" />
            Settings
        </a>
        <a href="{{ route('profile.edit') }}" class="l-sidebar__dropdown-link">
            <x-custom-icon name="users" class="c-icon--sm" />
            Profile
        </a>
        <div class="l-sidebar__dropdown-divider"></div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="l-sidebar__dropdown-link l-sidebar__dropdown-link--danger">
                <x-custom-icon name="arrow-right-on-rectangle" class="c-icon--sm" />
                Sign out
            </button>
        </form>
    </div>
</div>
