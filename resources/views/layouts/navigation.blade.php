@php
    $user = Auth::user();
    $initials = collect(preg_split('/\s+/', trim($user->name)))
        ->filter()->take(2)
        ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))
        ->implode('');
@endphp

<!-- Backdrop untuk sidebar mobile -->
<div x-cloak x-show="sidebar" x-transition.opacity @click="sidebar = false"
     class="fixed inset-0 z-30 bg-ink-900/30 backdrop-blur-sm lg:hidden"></div>

<aside class="sidebar" :class="{ 'sidebar-open': sidebar }" aria-label="Navigasi utama">
    <a href="{{ route('dashboard') }}" class="rounded-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
        <x-brand-mark />
    </a>

    <nav class="flex flex-col gap-1">
        <x-sidebar-link :href="route('dashboard')" icon="table-cells-large" :active="request()->routeIs('dashboard')">Ringkasan</x-sidebar-link>
        <x-sidebar-link :href="route('dashboard').'#kelompok'" icon="people-group">Kelompok Binaan</x-sidebar-link>
        <x-sidebar-link :href="route('dashboard').'#kawasan'" icon="mountain-sun">Kawasan Konservasi</x-sidebar-link>
        <x-sidebar-link :href="route('dashboard').'#clustering'" icon="chart-simple">Data Mining</x-sidebar-link>
        <x-sidebar-link :href="route('kelompok.create')" icon="plus" :active="request()->routeIs('kelompok.create')">Tambah Kelompok</x-sidebar-link>
        <x-sidebar-link :href="route('profile.edit')" icon="user-gear" :active="request()->routeIs('profile.*')">Profil</x-sidebar-link>
    </nav>

    <div class="mt-auto flex flex-col gap-4">
        <div class="glass-card flex flex-col gap-3 p-4">
            <x-icon-badge icon="seedling" class="size-9" />
            <div>
                <p class="text-sm font-semibold">Perluas pemberdayaan</p>
                <p class="mt-1 text-xs text-ink-500">Daftarkan kelompok masyarakat baru di sekitar kawasan konservasi.</p>
            </div>
            <a href="{{ route('kelompok.create') }}" class="btn-brand">Tambah Kelompok</a>
        </div>

        <!-- Menu pengguna -->
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
                <a href="{{ route('profile.edit') }}" class="side-link">
                    <i class="fa-solid fa-user w-5 text-center" aria-hidden="true"></i> Profil
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="side-link w-full text-left">
                        <i class="fa-solid fa-right-from-bracket w-5 text-center" aria-hidden="true"></i> Keluar
                    </button>
                </form>
            </div>
        </div>
    </div>
</aside>
