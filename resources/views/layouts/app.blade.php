<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    @include('layouts.partials.head')

    <body class="l-body">
        <div class="l-app" data-sidebar-root>
            @include('layouts.partials.flash')

            <div class="l-app__overlay" data-sidebar-overlay hidden></div>

            @include('layouts.partials.sidebar', ['collapsed' => false])
            @include('layouts.partials.sidebar', ['collapsed' => true])

            <div class="l-main-wrapper l-main-wrapper--shifted" data-main-wrapper>
                @include('layouts.partials.topbar')

                <main class="l-content">
                    {{ $slot }}
                </main>
            </div>
        </div>

        @include('layouts.partials.scripts')
    </body>
</html>
