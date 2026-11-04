@php
    $langkah = [
        ['user-plus', 'Daftarkan kelompok binaan', 'Isi nama kelompok, ketua, kontak, kawasan, dan jenis usaha.'],
        ['hand-holding-dollar', 'Catat bantuan', 'Simpan nilai bantuan yang disalurkan ke tiap kelompok.'],
        ['chart-pie', 'Pantau di dashboard', 'Lihat NTE, bantuan per jenis usaha, dan kelompok terbaru dalam grafik.'],
        ['brain', 'Baca pola kawasan', 'Bandingkan kawasan lewat pemetaan K-Means berdasarkan NTE dan desa penyangga.'],
    ];

    $keunggulan = [
        ['database', 'Terpusat', 'Data kelompok, kawasan, dan NTE tersimpan di satu tempat.'],
        ['eye', 'Mudah dibaca', 'Kartu statistik dan grafik menggantikan tumpukan tabel.'],
        ['lock', 'Terlindungi', 'Data dapat dibuka setelah masuk dengan akun terdaftar.'],
    ];
@endphp

<x-landing-section id="informasi" title="Dari pencatatan sampai pembacaan data"
    lead="Aplikasi ini merapikan kerja penyuluh pemberdayaan sehingga perkembangan kelompok binaan mudah dipantau dan dilaporkan.">

    <div class="grid items-start gap-10 lg:grid-cols-[0.9fr_1.1fr] lg:gap-16">
        <div class="space-y-4 lg:sticky lg:top-28">
            @foreach ($keunggulan as $i => [$icon, $judul, $teks])
                <div class="land-card spotlight glow-border reveal group flex items-start gap-4 p-5 hover:-translate-y-1 hover:bg-white/80" style="--d: {{ $i * 110 }}ms">
                    <span class="feature-icon shrink-0"><i class="fa-solid fa-{{ $icon }}" aria-hidden="true"></i></span>
                    <div>
                        <h3 class="font-bold text-ink-900">{{ $judul }}</h3>
                        <p class="mt-1 text-sm text-ink-500">{{ $teks }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="timeline" data-timeline>
            <div class="timeline-track" aria-hidden="true"><div class="timeline-fill"></div></div>

            @foreach ($langkah as [$icon, $judul, $teks])
                <div class="step" data-step>
                    <span class="step-dot" aria-hidden="true"><i class="fa-solid fa-{{ $icon }}"></i></span>
                    <div class="step-body">
                        <h3 class="font-bold text-ink-900">{{ $judul }}</h3>
                        <p class="mt-1 text-sm text-ink-500">{{ $teks }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-landing-section>
