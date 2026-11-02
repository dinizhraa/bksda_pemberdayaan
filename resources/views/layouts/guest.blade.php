<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'BKSDA Sulawesi Tengah') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="app-backdrop">
        <div class="flex min-h-screen flex-col items-center px-4 pt-10 sm:justify-center sm:pt-0">
            <a href="/" class="rounded-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                <x-brand-mark />
            </a>

            <div class="glass-card mt-6 w-full sm:max-w-md sm:p-8">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
