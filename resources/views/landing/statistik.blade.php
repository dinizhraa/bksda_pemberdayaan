@php
    // Angka dari data resmi BKSDA Sulteng (18 unit kawasan) dan isi aplikasi ini.
    $angka = [
        ['mountain-sun', 18, 'Unit kawasan konservasi', 'dikelola Balai KSDA Sulawesi Tengah'],
        ['layer-group', 4, 'Jenis kawasan', 'Cagar Alam, Suaka Margasatwa, Taman Buru, Taman Wisata Alam'],
        ['wand-magic-sparkles', 6, 'Fitur utama', 'tersedia dalam satu aplikasi'],
    ];
@endphp

<section class="px-4 py-12" aria-label="Angka ringkas">
    <div class="mx-auto grid max-w-6xl gap-4 md:grid-cols-3">
        @foreach ($angka as $i => [$icon, $nilai, $label, $catatan])
            <div class="stat-card reveal group" style="--d: {{ $i * 110 }}ms">
                <span class="feature-icon size-14 shrink-0 text-xl"><i class="fa-solid fa-{{ $icon }}" aria-hidden="true"></i></span>
                <div>
                    <p class="stat-number" data-count="{{ $nilai }}">{{ $nilai }}</p>
                    <p class="mt-1 text-sm font-bold text-ink-900">{{ $label }}</p>
                    <p class="text-xs text-ink-500">{{ $catatan }}</p>
                </div>
            </div>
        @endforeach
    </div>
</section>
