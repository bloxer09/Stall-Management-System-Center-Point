<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'STALLM') }}</title>

        <!-- Local Bundled Assets (Vite) -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @livewireStyles
    </head>
    <body class="font-sans antialiased bg-slate-100 text-slate-900 min-h-screen flex flex-col justify-center items-center p-4">
        <div class="w-full max-w-md bg-white p-8 rounded-2xl shadow-xl border border-slate-200">
            {{ $slot }}
        </div>
        @livewireScripts
    </body>
</html>
