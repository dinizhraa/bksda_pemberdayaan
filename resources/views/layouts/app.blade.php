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
        <div x-data="{ sidebar: false }" @keydown.escape.window="sidebar = false" class="min-h-screen p-3 sm:p-4">
            <div class="app-frame">
                @include('layouts.navigation')

                <div class="flex min-w-0 flex-1 flex-col gap-6">
                    <!-- Topbar -->
                    <header class="flex flex-wrap items-center justify-between gap-4">
                        <div class="flex items-start gap-3">
                            <button type="button" class="icon-btn lg:hidden" @click="sidebar = true" aria-label="Buka menu">
                                <i class="fa-solid fa-bars" aria-hidden="true"></i>
                            </button>

                            @isset($header)
                                <div>{{ $header }}</div>
                            @endisset
                        </div>

                        @isset($actions)
                            <div class="flex w-full flex-wrap items-center gap-3 sm:w-auto">{{ $actions }}</div>
                        @endisset
                    </header>

                    <!-- Konten halaman -->
                    <main class="flex-1">
                        {{ $slot }}
                    </main>
                </div>
            </div>
        </div>
    </body>
</html>
