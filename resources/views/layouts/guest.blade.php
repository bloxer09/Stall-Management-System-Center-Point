<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ \App\Models\Setting::get('platform_name', config('app.name', 'STALLM')) }}</title>

        <!-- Local Bundled Assets (Vite) -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @livewireStyles(['nonce' => Vite::cspNonce()])
    </head>
    <body class="font-sans antialiased bg-base-200 text-base-content min-h-screen flex flex-col justify-center items-center p-4">
        <div class="w-full max-w-md bg-base-100 p-8 rounded-3xl shadow-xl border border-base-300">
            {{ $slot }}
        </div>
        @livewireScripts(['nonce' => Vite::cspNonce()])
    </body>
</html>
