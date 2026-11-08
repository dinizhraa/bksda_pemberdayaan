@php
    $topik = ['Cagar Alam', 'Suaka Margasatwa', 'Taman Buru', 'Taman Wisata Alam', 'Kelompok binaan', 'Bantuan usaha', 'Nilai ekonomi (NTE)', 'Desa penyangga', 'Analisis K-Means'];
@endphp

<div data-pause-offscreen class="py-6" aria-hidden="true">
    <div class="marquee-wrap">
        <div class="marquee">
            @foreach ([1, 2] as $salinan)
                @foreach ($topik as $item)
                    <span class="marquee-item"><i class="fa-solid fa-leaf text-brand-500"></i>{{ $item }}</span>
                @endforeach
            @endforeach
        </div>
    </div>
</div>
