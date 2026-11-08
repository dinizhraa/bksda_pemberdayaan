@php
    $kataPutar = ['kelompok binaan', 'bantuan usaha', 'nilai ekonomi', 'desa penyangga'];
    $kataAkhir = ['di', 'sekitar', 'kawasan', 'konservasi'];
    $jenisKawasan = ['Cagar Alam', 'Suaka Margasatwa', 'Taman Buru', 'Taman Wisata Alam'];
    $statContoh = [
        ['wallet', 'teal', 'Total NTE', '1.24', '1,24', 2, 'Rp ', ' M'],
        ['people-group', 'purple', 'Kelompok', '48', '48', 0, '', ''],
        ['mountain-sun', 'green', 'Desa', '126', '126', 0, '', ''],
    ];
    $bars = [38, 62, 48, 80, 58, 92, 70];

    // Partikel daun/cahaya yang naik perlahan (posisi dibuat deterministik)
    $partikel = collect(range(0, 15))->map(fn ($i) => [
        'left' => ($i * 53) % 100,
        'size' => 4 + ($i * 7) % 9,
        'delay' => -($i * 1.7),
        'dur' => 16 + ($i * 5) % 12,
        'dx' => ($i % 2 ? 1 : -1) * (20 + ($i * 11) % 50),
        'rise' => 520 + ($i * 37) % 380,
    ]);
@endphp

<section id="hero" data-pause-offscreen class="relative flex min-h-screen scroll-mt-24 items-center overflow-x-clip pb-24 pt-32 sm:pt-36">
    <!-- Latar aurora beranimasi -->
    <div class="pointer-events-none absolute inset-0" aria-hidden="true">
        <span class="blob blob-teal"></span>
        <span class="blob blob-sky"></span>
        <span class="blob blob-green"></span>
        <div class="absolute inset-0 grid place-items-center"><div class="aurora-conic"></div></div>

        <svg class="absolute left-1/2 top-1/2 size-[60rem] -translate-x-1/2 -translate-y-1/2 text-brand-500/15" viewBox="0 0 400 400" fill="none" stroke="currentColor" stroke-width="1">
            <ellipse cx="200" cy="200" rx="190" ry="150" /><ellipse cx="200" cy="200" rx="160" ry="120" />
            <ellipse cx="200" cy="200" rx="130" ry="92" /><ellipse cx="200" cy="200" rx="100" ry="66" />
        </svg>

        <div class="absolute inset-0 overflow-hidden">
            @foreach ($partikel as $p)
                <span class="particle" style="left: {{ $p['left'] }}%; width: {{ $p['size'] }}px; height: {{ $p['size'] }}px; animation-delay: {{ $p['delay'] }}s; animation-duration: {{ $p['dur'] }}s; --dx: {{ $p['dx'] }}px; --rise: {{ $p['rise'] }}px"></span>
            @endforeach
        </div>

        <i class="fa-solid fa-leaf absolute left-[6%] top-[30%] animate-float text-3xl text-brand-400/40"></i>
        <i class="fa-solid fa-seedling absolute right-[8%] top-[20%] animate-float-sm text-2xl text-emerald-400/40" style="animation-delay: -3s"></i>
        <div class="grain"></div>
    </div>

    <div class="relative mx-auto grid w-full max-w-6xl items-center gap-14 px-4 lg:grid-cols-[1.2fr_1fr]">
        <!-- Teks -->
        <div>
            <h1 class="hero-title" aria-label="Dampingi dan pantau kelompok binaan di sekitar kawasan konservasi">
                <span aria-hidden="true">
                    <span class="block">
                        @foreach (['Dampingi', 'dan', 'pantau'] as $i => $kata)
                            <span class="word" style="--i: {{ $i }}">{{ $kata }}</span>
                        @endforeach
                    </span>
                    <span class="word block" style="--i: 3">
                        <span class="rotator">
                            @foreach ($kataPutar as $i => $kata)
                                <span style="--i: {{ $i }}">{{ $kata }}</span>
                            @endforeach
                        </span>
                    </span>
                    <span class="block">
                        @foreach ($kataAkhir as $i => $kata)
                            <span class="word" style="--i: {{ $i + 4 }}">{{ $kata }}</span>
                        @endforeach
                    </span>
                </span>
            </h1>

            <p class="mt-6 max-w-xl animate-rise text-base leading-relaxed text-ink-500 sm:text-lg" style="animation-delay: 900ms">
                Aplikasi ini membantu penyuluh pemberdayaan BKSDA Sulawesi Tengah mencatat kelompok binaan, bantuan, dan nilai ekonomi kawasan, lalu menampilkannya sebagai grafik yang mudah dibaca.
            </p>

            <div class="mt-8 flex animate-rise flex-wrap items-center gap-3" style="animation-delay: 1050ms">
                @auth
                    <a href="{{ route('dashboard') }}" data-magnetic class="btn-brand btn-glow btn-lg">Buka Dashboard</a>
                @else
                    <button type="button" data-magnetic @click="modal = 'register'" class="btn-brand btn-glow btn-lg">Mulai Sekarang</button>
                    <button type="button" data-magnetic @click="modal = 'login'" class="btn-ghost btn-glow btn-lg">Masuk</button>
                @endauth
                <a href="#fitur" class="group inline-flex items-center gap-2 rounded-xl px-3 py-3 text-sm font-semibold text-ink-700 transition-colors hover:text-brand-700">
                    Lihat fitur
                    <i class="fa-solid fa-chevron-down text-xs transition-transform duration-300 group-hover:translate-y-1" aria-hidden="true"></i>
                </a>
            </div>

            <ul class="mt-10 flex animate-rise flex-wrap gap-2" style="animation-delay: 1200ms" aria-label="Jenis kawasan yang dipantau">
                @foreach ($jenisKawasan as $jenis)
                    <li class="tag transition-all duration-200 hover:-translate-y-0.5 hover:border-brand-300 hover:text-brand-700">
                        <span class="size-1.5 rounded-full bg-brand-500"></span>{{ $jenis }}
                    </li>
                @endforeach
            </ul>
        </div>

        <!-- Ilustrasi interaktif -->
        <div data-tilt-area class="relative mx-auto w-full max-w-lg animate-rise py-8" style="animation-delay: 300ms">
            <div class="orb" aria-hidden="true"></div>
            <div class="pointer-events-none absolute -inset-6 animate-spin-slow rounded-full border border-dashed border-brand-400/40" aria-hidden="true"></div>
            <div class="pointer-events-none absolute -inset-14 animate-spin-rev rounded-full border border-dashed border-sky-400/30" aria-hidden="true"></div>

            <div class="tilt-card glass relative rounded-4xl p-5" aria-hidden="true">
                <div class="mb-4 flex items-center justify-between">
                    <div class="flex gap-1.5"><span class="size-2.5 rounded-full bg-rose-300"></span><span class="size-2.5 rounded-full bg-amber-300"></span><span class="size-2.5 rounded-full bg-emerald-300"></span></div>
                    <span class="flex items-center gap-2 text-xs font-semibold text-ink-500">
                        <span class="relative flex size-2"><span class="absolute inline-flex size-full animate-ping rounded-full bg-emerald-400 opacity-75"></span><span class="relative inline-flex size-2 rounded-full bg-emerald-500"></span></span>
                        Terpantau
                    </span>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    @foreach ($statContoh as [$icon, $tone, $label, $nilai, $awal, $desimal, $awalan, $akhiran])
                        <div class="rounded-2xl border border-white/70 bg-white/60 p-3">
                            <x-icon-badge :icon="$icon" :tone="$tone" class="size-8 text-xs" />
                            <p class="mt-2 text-[10px] font-medium text-ink-500">{{ $label }}</p>
                            <p class="text-base font-extrabold tabular-nums tracking-tight text-ink-900">{{ $awalan }}<span data-count="{{ $nilai }}" data-decimals="{{ $desimal }}">{{ $awal }}</span>{{ $akhiran }}</p>
                        </div>
                    @endforeach
                </div>

                <div class="relative mt-3 rounded-2xl border border-white/70 bg-white/60 p-4">
                    <span class="block h-2.5 w-1/3 rounded-full bg-ink-500/30"></span>
                    <div class="relative mt-3">
                        <svg viewBox="0 0 320 120" class="h-32 w-full" fill="none" preserveAspectRatio="none">
                            <defs>
                                <linearGradient id="heroFill" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0" stop-color="#1d9e92" stop-opacity="0.35" /><stop offset="1" stop-color="#1d9e92" stop-opacity="0" />
                                </linearGradient>
                            </defs>
                            <path d="M0 96 C30 92 50 66 80 68 S130 86 160 56 S215 34 245 42 S295 16 320 10 L320 120 L0 120 Z" fill="url(#heroFill)" class="animate-[fade-in_1.2s_ease-out_1.8s_both]" />
                            <path d="M0 96 C30 92 50 66 80 68 S130 86 160 56 S215 34 245 42 S295 16 320 10" pathLength="1" stroke="#1d9e92" stroke-width="3" stroke-linecap="round" class="draw-line" />
                        </svg>
                        <span class="absolute right-0 top-[8%] flex size-3 -translate-y-1/2 translate-x-1/2 animate-[fade-in_0.4s_ease-out_2.3s_both]">
                            <span class="absolute inline-flex size-full animate-ping rounded-full bg-brand-400 opacity-75"></span>
                            <span class="relative inline-flex size-3 rounded-full border-2 border-white bg-brand-500"></span>
                        </span>
                    </div>
                </div>

                <div class="mt-3 grid grid-cols-[auto_1fr] items-center gap-4 rounded-2xl border border-white/70 bg-white/60 p-4">
                    <svg viewBox="0 0 36 36" class="size-16 -rotate-90 animate-[spin_30s_linear_infinite]">
                        <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#1d9e92" stroke-width="5" stroke-dasharray="58 42" />
                        <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#7fb4f0" stroke-width="5" stroke-dasharray="27 73" stroke-dashoffset="-58" />
                        <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#6ee7b7" stroke-width="5" stroke-dasharray="15 85" stroke-dashoffset="-85" />
                    </svg>
                    <div class="flex h-14 items-end gap-2">
                        @foreach ($bars as $i => $height)
                            <span class="grow-bar" style="height: {{ $height }}%; animation-delay: {{ 1400 + $i * 90 }}ms"></span>
                        @endforeach
                    </div>
                </div>

                <p class="mt-3 text-right text-[10px] text-ink-400">Contoh tampilan data</p>
            </div>

            <!-- Chip melayang -->
            <div class="parallax-1 absolute -right-2 top-2 sm:-right-6" aria-hidden="true">
                <div class="float-chip animate-float"><x-icon-badge icon="seedling" tone="green" class="size-7 text-xs" />Kelompok binaan terdata</div>
            </div>
            <div class="parallax-2 absolute -left-2 top-1/2 sm:-left-8" aria-hidden="true">
                <div class="float-chip animate-float" style="animation-delay: -2s"><x-icon-badge icon="wallet" tone="teal" class="size-7 text-xs" />Nilai ekonomi (NTE)</div>
            </div>
            <div class="parallax-1 absolute -bottom-1 right-6 sm:right-2" aria-hidden="true">
                <div class="float-chip animate-float" style="animation-delay: -4s"><x-icon-badge icon="brain" tone="purple" class="size-7 text-xs" />Analisis K-Means</div>
            </div>
        </div>
    </div>

    <a href="#informasi" class="absolute bottom-6 left-1/2 hidden -translate-x-1/2 flex-col items-center gap-1 text-xs font-medium text-ink-500 transition-colors hover:text-brand-700 lg:flex" aria-label="Gulir ke bawah">
        Gulir
        <i class="fa-solid fa-chevron-down animate-bounce" aria-hidden="true"></i>
    </a>
</section>
