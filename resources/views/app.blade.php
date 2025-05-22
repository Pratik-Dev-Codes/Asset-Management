<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="app-url" content="{{ config('app.url') }}">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @viteReactRefresh
        @vite(['resources/js/app.jsx', "resources/js/Pages/{$page['component']}.jsx"])
        @inertiaHead
        
        <!-- Ziggy Routes from CDN -->
        <script src="https://cdn.jsdelivr.net/npm/ziggy-js@1.4.2/dist/ziggy.min.js"></script>
        
        <!-- Initialize CSRF Token and User Data -->
        <!-- Laravel Data Initialization -->
        <script src="{{ route('laravel.data.js') }}"></script>
        <script src="{{ asset('js/laravel-init.js') }}"></script>
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
