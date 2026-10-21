<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Flux') . ' | ' . ($title ?? '') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @foreach($pluginStylesheets as $cssPath)
        <link rel="stylesheet" href="{{ asset($cssPath) }}">
    @endforeach

    <script>
        (function () {
            const storedTheme = localStorage.getItem('theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

            if (storedTheme === 'dark' || (storedTheme === null && prefersDark)) {
                document.documentElement.setAttribute('data-theme', 'dark');
            } else {
                document.documentElement.removeAttribute('data-theme');
            }

            const storedSidebar = localStorage.getItem('sidebar_ouvert');
            const sidebarOpen = window.innerWidth >= 1024 && storedSidebar !== 'false';

            document.documentElement.dataset.sidebarOpen = sidebarOpen ? 'true' : 'false';
        })();
    </script>
</head>
