@foreach($pluginScripts as $scriptPath)
    <script defer src="{{ asset($scriptPath) }}"></script>
@endforeach

@stack('scripts')
