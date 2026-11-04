@php
    $batang = [35, 55, 42, 70, 50, 88, 64, 76, 46, 92, 60, 80];
    $avatar = [['seedling', 'green'], ['leaf', 'teal'], ['user', 'blue'], ['people-group', 'purple'], ['plus', 'amber']];
    $kawasan = ['CA', 'SM', 'TB', 'TWA'];
@endphp

<x-landing-section id="fitur" title="Fitur utama" lead="Semua yang dibutuhkan untuk mencatat, memantau, dan membaca data pemberdayaan.">
    <div class="grid gap-4 md:grid-cols-6">
        <x-bento-card icon="wallet" title="Monitoring NTE" class="md:col-span-3">
            Pantau nilai ekonomi langsung dan tidak langsung tiap kawasan, lengkap dengan capaian terhadap target.
            <x-slot name="visual">
                <div class="flex h-28 w-full items-end gap-1.5">
                    @foreach ($batang as $i => $tinggi)
                        <span class="bar" style="height: {{ $tinggi }}%; animation-delay: {{ 300 + $i * 70 }}ms"></span>
                    @endforeach
                </div>
            </x-slot>
        </x-bento-card>

        <x-bento-card icon="people-group" title="Data kelompok binaan" class="md:col-span-3" :delay="100">
            Catat nama kelompok, ketua, kontak, jenis usaha, dan nilai bantuan dalam satu daftar yang bisa dikelola.
            <x-slot name="visual">
                <div class="flex items-center">
                    @foreach ($avatar as $i => [$icon, $nada])
                        <x-icon-badge :icon="$icon" :tone="$nada" class="dot-float size-12 -ml-3 border-2 border-white text-base transition-all duration-300 first:ml-0 group-hover:ml-1" style="animation-delay: {{ -$i * 1.3 }}s" />
                    @endforeach
                </div>
            </x-slot>
        </x-bento-card>

        <x-bento-card icon="mountain-sun" title="Kawasan dan desa penyangga" class="md:col-span-2">
            Lihat kawasan konservasi beserta jumlah desa sekitar dan desa yang sudah difasilitasi.
            <x-slot name="visual">
                <div class="flex flex-wrap gap-2">
                    @foreach ($kawasan as $i => $kode)
                        <span class="dot-float grid size-12 place-items-center bg-brand-500/15 text-xs font-extrabold text-brand-700 transition-colors duration-300 group-hover:bg-brand-600 group-hover:text-white" style="animation-delay: {{ -$i * 1.5 }}s">{{ $kode }}</span>
                    @endforeach
                </div>
            </x-slot>
        </x-bento-card>

        <x-bento-card icon="brain" title="Analisis K-Means" class="md:col-span-2" :delay="100">
            Pemetaan kawasan berdasarkan Total NTE dan jumlah desa penyangga untuk melihat pola.
            <x-slot name="visual">
                <svg viewBox="0 0 200 90" class="h-24 w-full">
                    <g class="text-brand-500"><circle class="dot-float" cx="30" cy="62" r="6" fill="currentColor" /><circle class="dot-float" cx="48" cy="48" r="5" fill="currentColor" style="animation-delay:-2s" /><circle class="dot-float" cx="40" cy="72" r="4" fill="currentColor" style="animation-delay:-4s" /></g>
                    <g class="text-sky-400"><circle class="dot-float" cx="100" cy="28" r="6" fill="currentColor" style="animation-delay:-1s" /><circle class="dot-float" cx="118" cy="40" r="5" fill="currentColor" style="animation-delay:-3s" /><circle class="dot-float" cx="92" cy="44" r="4" fill="currentColor" style="animation-delay:-5s" /></g>
                    <g class="text-emerald-400"><circle class="dot-float" cx="160" cy="58" r="6" fill="currentColor" style="animation-delay:-2.5s" /><circle class="dot-float" cx="176" cy="42" r="5" fill="currentColor" style="animation-delay:-1.5s" /><circle class="dot-float" cx="150" cy="72" r="4" fill="currentColor" style="animation-delay:-3.5s" /></g>
                </svg>
            </x-slot>
        </x-bento-card>

        <x-bento-card icon="chart-pie" title="Grafik ringkasan" class="md:col-span-2" :delay="200">
            Kartu statistik, grafik area, donat, dan batang menyajikan kondisi pemberdayaan sekilas pandang.
            <x-slot name="visual">
                <svg viewBox="0 0 36 36" class="size-24 -rotate-90 animate-[spin_30s_linear_infinite] transition-transform duration-500 group-hover:scale-110">
                    <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#1d9e92" stroke-width="5" stroke-dasharray="52 48" />
                    <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#7fb4f0" stroke-width="5" stroke-dasharray="30 70" stroke-dashoffset="-52" />
                    <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#6ee7b7" stroke-width="5" stroke-dasharray="18 82" stroke-dashoffset="-82" />
                </svg>
            </x-slot>
        </x-bento-card>

        <x-bento-card icon="magnifying-glass-chart" title="Pencarian cepat" class="md:col-span-6" :delay="100">
            Temukan kelompok atau jenis usaha tertentu langsung dari daftar tanpa membuka halaman lain.
            <x-slot name="visual">
                <div class="flex w-full items-center gap-3 rounded-2xl border border-white/80 bg-white/70 px-4 py-3 text-sm text-ink-700 shadow-sm">
                    <i class="fa-solid fa-magnifying-glass text-ink-400"></i>
                    <span class="typing" style="--w: 27ch">Kelompok Tani Ternak Makmur</span>
                </div>
            </x-slot>
        </x-bento-card>
    </div>
</x-landing-section>
