@php
    // Modal terbuka otomatis bila form di dalamnya gagal divalidasi (redirect kembali ke halaman ini).
    $modalAwal = $errors->any() ? (old('_form') === 'register' ? 'register' : 'login') : null;

    $menu = [
        ['hero', 'Hero'],
        ['informasi', 'Informasi'],
        ['fitur', 'Fitur'],
        ['tim', 'Tim'],
        ['kontak', 'Kontak'],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="Aplikasi penyuluh pemberdayaan BKSDA Sulawesi Tengah untuk mencatat dan memantau kelompok binaan di sekitar kawasan konservasi.">

        <title>Pemberdayaan BKSDA | Aplikasi Penyuluh Pemberdayaan Masyarakat</title>

        {{-- Animasi scroll hanya aktif bila JS siap; bila gagal dimuat, konten tetap tampil. --}}
        <script>
            document.documentElement.classList.add('js');
            setTimeout(function () {
                if (! window.__landingReady) document.documentElement.classList.remove('js');
            }, 3000);
        </script>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="app-backdrop">
        <div id="landing"
             x-data="{ modal: @js($modalAwal), menu: false }"
             x-effect="document.body.classList.toggle('overflow-hidden', !! modal)"
             @keydown.escape.window="modal = null; menu = false">

            @if (session('success'))
                <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 5000)" x-show="show" x-cloak
                     x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="-translate-y-4 opacity-0"
                     x-transition:leave="transition duration-300 ease-in" x-transition:leave-end="-translate-y-4 opacity-0"
                     role="status" class="glass fixed left-1/2 top-24 z-[70] flex -translate-x-1/2 items-center gap-3 rounded-2xl px-5 py-3 text-sm font-semibold text-brand-700">
                    <i class="fa-solid fa-circle-check text-emerald-500" aria-hidden="true"></i>
                    {{ session('success') }}
                </div>
            @endif

            <div class="scroll-progress" aria-hidden="true"></div>
            <div class="cursor-glow" data-cursor-glow aria-hidden="true"></div>

            @include('landing.navbar')

            <main>
                @include('landing.hero')
                @include('landing.marquee')
                @include('landing.statistik')
                @include('landing.informasi')
                @include('landing.fitur')
                @include('landing.tim')
                @include('landing.kontak')
            </main>

            @include('landing.footer')

            @guest
                @include('landing.auth-modal')
            @endguest
        </div>
    </body>
</html>
