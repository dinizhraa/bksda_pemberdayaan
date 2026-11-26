@php
    $user = Auth::user();
    $isAdmin = $user?->isAdmin() ?? false;
    $initials = $user ? collect(preg_split('/\s+/', trim($user->name)))
        ->filter()->take(2)
        ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))
        ->implode('') : 'P';
@endphp

<!-- Backdrop untuk sidebar mobile -->
<div x-cloak x-show="sidebar" x-transition.opacity @click="sidebar = false"
     class="fixed inset-0 z-30 bg-ink-900/30 backdrop-blur-sm lg:hidden"></div>

<aside class="sidebar" :class="{ 'sidebar-open': sidebar }" aria-label="Navigasi utama">
    <a href="{{ route($isAdmin ? 'dashboard' : 'visualisasi') }}" class="rounded-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
        <x-brand-mark />
    </a>

    <nav class="flex flex-col gap-1">
        @if ($isAdmin)
            <x-sidebar-link :href="route('dashboard')" icon="table-cells-large" :active="request()->routeIs('dashboard')">Dashboard</x-sidebar-link>
        @else
            <x-sidebar-link :href="route('visualisasi')" icon="chart-pie" :active="request()->routeIs('visualisasi')">Visualisasi</x-sidebar-link>
        @endif
        <x-sidebar-link :href="route('kelompok.index')" icon="people-group" :active="request()->routeIs('kelompok.index', 'kelompok.edit')">Kelompok Binaan</x-sidebar-link>
        <x-sidebar-link :href="route('kawasan.index')" icon="mountain-sun" :active="request()->routeIs('kawasan.index')">Kawasan & NTE</x-sidebar-link>
        <x-sidebar-link :href="route('kelompok.map')" icon="map-location-dot" :active="request()->routeIs('kelompok.map')">Peta Kawasan</x-sidebar-link>
        @if ($isAdmin)
            <x-sidebar-link :href="route('kelompok.create')" icon="plus" :active="request()->routeIs('kelompok.create')">Tambah Kelompok</x-sidebar-link>
            <div class="mt-4 border-t border-ink-200/70 pt-4">
                <p class="px-3.5 pb-2 text-[10px] font-bold uppercase tracking-[0.16em] text-ink-400">AI ASSISTANT BKSDA</p>
                <div class="flex flex-col gap-1">
                    <x-sidebar-link :href="route('ai.chat.index')" icon="comments" :active="request()->routeIs('ai.chat.*')">Chat dengan AI</x-sidebar-link>
                    <x-sidebar-link :href="route('ai.analytics.index')" icon="chart-line" :active="request()->routeIs('ai.analytics.*')">Analisis Data</x-sidebar-link>
                    <x-sidebar-link :href="route('ai.monev.index')" icon="clipboard-check" :active="request()->routeIs('ai.monev.*')">Monitoring & Evaluasi</x-sidebar-link>
                    <x-sidebar-link :href="route('ai.reports.index')" icon="file-lines" :active="request()->routeIs('ai.reports.*')">Generator Laporan</x-sidebar-link>
                </div>
            </div>
        @endif
        @if ($isAdmin)
            <x-sidebar-link :href="route('profile.edit')" icon="user-gear" :active="request()->routeIs('profile.*')">Profil</x-sidebar-link>
        @endif
    </nav>

    <div class="mt-auto flex flex-col gap-4">
        @if ($isAdmin)
            <div class="glass-card flex flex-col gap-3 p-4">
                <x-icon-badge icon="seedling" class="size-9" />
                <div>
                    <p class="text-sm font-semibold">Perluas pemberdayaan</p>
                    <p class="mt-1 text-xs text-ink-500">Daftarkan kelompok masyarakat baru di sekitar kawasan konservasi.</p>
                </div>
                <a href="{{ route('kelompok.create') }}" class="btn-brand">Tambah Kelompok</a>
            </div>
        @endif

        @if ($user)
        <div class="relative" x-data="{ open: false }" @click.outside="open = false">
            <button type="button" @click="open = ! open" :aria-expanded="open"
                    class="flex w-full items-center gap-3 rounded-2xl p-2 text-left transition-colors hover:bg-white/60 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                <span class="avatar">{{ $initials }}</span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-sm font-semibold">{{ $user->name }}</span>
                    <span class="block truncate text-xs text-ink-500">{{ $user->email }}</span>
                </span>
                <i class="fa-solid fa-chevron-down text-xs text-ink-400 transition-transform" :class="{ 'rotate-180': open }" aria-hidden="true"></i>
            </button>

            <div x-cloak x-show="open" x-transition
                 class="glass absolute inset-x-0 bottom-full mb-2 overflow-hidden rounded-2xl bg-white/90 p-1.5">
                @if ($isAdmin)
                    <a href="{{ route('profile.edit') }}" class="side-link">
                        <i class="fa-solid fa-user w-5 text-center" aria-hidden="true"></i> Profil
                    </a>
                @endif
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="side-link w-full text-left">
                        <i class="fa-solid fa-right-from-bracket w-5 text-center" aria-hidden="true"></i> Keluar
                    </button>
                </form>
            </div>
        </div>
        @else
            <div class="rounded-2xl border border-white/70 bg-white/60 p-3 text-center">
                <p class="text-xs text-ink-500">Akses publik</p>
                <p class="mt-1 text-sm font-semibold text-ink-700">Penyuluhan BKSDA</p>
            </div>
        @endif
    </div>
</aside>
