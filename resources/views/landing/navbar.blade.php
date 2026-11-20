<header data-nav class="landing-nav">
    <div class="landing-nav-inner">
        <a href="#hero" class="rounded-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500" aria-label="Pemberdayaan BKSDA, kembali ke atas">
            <x-brand-mark />
        </a>

        <nav class="hidden items-center gap-1 lg:flex" aria-label="Menu halaman">
            @foreach ($menu as [$id, $label])
                <a href="#{{ $id }}" data-spy class="nav-pill">{{ $label }}</a>
            @endforeach
        </nav>

        <div class="flex items-center gap-2">
            <a href="{{ route('login') }}" class="btn-ghost btn-glow hidden sm:inline-flex">Masuk Admin</a>
            <a href="{{ route('visualisasi') }}" class="btn-brand btn-glow">Lihat Data</a>
            <button type="button" class="icon-btn lg:hidden" @click="menu = ! menu" :aria-expanded="menu" aria-label="Buka menu navigasi">
                <i class="fa-solid" :class="menu ? 'fa-xmark' : 'fa-bars'" aria-hidden="true"></i>
            </button>
        </div>
    </div>

    <!-- Menu mobile -->
    <div x-cloak x-show="menu" x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="-translate-y-3 opacity-0"
         x-transition:enter-end="translate-y-0 opacity-100" x-transition:leave="transition duration-200 ease-in"
         x-transition:leave-start="translate-y-0 opacity-100" x-transition:leave-end="-translate-y-3 opacity-0"
         class="landing-menu lg:hidden">
        @foreach ($menu as [$id, $label])
            <a href="#{{ $id }}" data-spy @click="menu = false" class="nav-pill">{{ $label }}</a>
        @endforeach

        <a href="{{ route('login') }}" @click="menu = false" class="btn-ghost mt-2 sm:hidden">Masuk Admin</a>
        <a href="{{ route('visualisasi') }}" @click="menu = false" class="btn-brand mt-2">Lihat Data</a>
    </div>
</header>
