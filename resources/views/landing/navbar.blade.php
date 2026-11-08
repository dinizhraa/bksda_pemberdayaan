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
            @auth
                <a href="{{ route('dashboard') }}" class="btn-brand btn-glow">Buka Dashboard</a>
            @else
                <button type="button" @click="modal = 'login'" class="btn-ghost btn-glow hidden sm:inline-flex">Masuk</button>
                @if (Route::has('register'))
                    <button type="button" @click="modal = 'register'" class="btn-brand btn-glow max-[380px]:hidden">Daftar</button>
                @endif
            @endauth

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

        @guest
            <button type="button" @click="menu = false; modal = 'login'" class="btn-ghost mt-2 sm:hidden">Masuk</button>
            @if (Route::has('register'))
                <button type="button" @click="menu = false; modal = 'register'" class="btn-brand hidden max-[380px]:inline-flex">Daftar</button>
            @endif
        @endguest
    </div>
</header>
