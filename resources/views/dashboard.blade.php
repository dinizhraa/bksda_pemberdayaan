@php
    // ---- Ringkasan angka (semua dihitung dari data yang sudah dikirim controller) ----
    $targetNte = 1_575_000_000; // Target NTE: Rp 1,575 M
    $totalNte = (float) ($totalNte ?? 0);
    $capaian = $totalNte > 0 ? round($totalNte / $targetNte * 100, 1) : 0;

    $nteKawasan = $kawasans->filter(fn ($k) => $k->nte);
    $tahunNte = $nteKawasan->first()?->nte?->tahun;
    $urutNte = $nteKawasan->sortByDesc(fn ($k) => (float) $k->nte->total_nte)->values();
    $kawasanTeratas = $urutNte->take(4);
    $topDelapan = $urutNte->take(8);

    $totalLangsung = (float) $nteKawasan->sum(fn ($k) => $k->nte->nte_langsung);
    $porsiLangsung = $totalNte > 0 ? round($totalLangsung / $totalNte * 100, 1) : 0;

    // ---- Bantuan per jenis usaha (4 teratas + lainnya) ----
    $perUsaha = $kelompoks
        ->groupBy(fn ($k) => $k->jenis_usaha ?: 'Lainnya')
        ->map(fn ($grup) => (float) $grup->sum('jumlah_bantuan'))
        ->sortDesc();
    $usahaTampil = $perUsaha->take(4);
    if (($sisa = $perUsaha->slice(4)->sum()) > 0) {
        $usahaTampil->put('Lainnya', ($usahaTampil['Lainnya'] ?? 0) + $sisa);
    }
    $totalUsaha = $usahaTampil->sum();
    $persen = fn ($nilai, $total) => number_format($total > 0 ? $nilai / $total * 100 : 0, 1, ',', '.');
    $palet = ['#0f8f84', '#35bfae', '#7fb4f0', '#a9cdf5', '#d3e6fb'];

    $kelompokTerbaru = $kelompoks->sortByDesc('id')->take(5);
    $nadaAvatar = ['teal', 'blue', 'purple', 'green', 'amber'];
    $nadaFungsi = ['CA' => 'teal', 'SM' => 'blue', 'TWA' => 'green', 'TB' => 'purple'];

    // ---- Data grafik (dibaca oleh resources/js/dashboard.js) ----
    $kumulatif = 0;
    $clusterData = $clusterData ?? App\Http\Controllers\DashboardController::buildKawasanClusterData($kawasans, (float) $totalNte);

    $charts = [
        'nte' => [
            'labels' => $urutNte->pluck('nama_kawasan'),
            'each' => $urutNte->map(fn ($k) => round($k->nte->total_nte / 1e6, 1)),
            'values' => $urutNte->map(function ($k) use (&$kumulatif) {
                $kumulatif += $k->nte->total_nte / 1e6;

                return round($kumulatif, 1);
            }),
        ],
        'usaha' => [
            'labels' => $usahaTampil->keys(),
            'values' => $usahaTampil->values(),
            'colors' => array_slice($palet, 0, $usahaTampil->count()),
        ],
        'split' => [
            'labels' => $topDelapan->pluck('nama_kawasan'),
            'langsung' => $topDelapan->map(fn ($k) => round($k->nte->nte_langsung / 1e6, 1)),
            'tidakLangsung' => $topDelapan->map(fn ($k) => round($k->nte->nte_tidak_langsung / 1e6, 1)),
        ],
        'cluster' => $clusterData,
    ];
    $charts['cluster'] = $clusterAnalysis;
@endphp

<x-app-layout>
    <x-slot name="header">
        <h1 class="page-title">Data Mining | Kerja Praktek | Manajemen Data</h1>
        <p class="page-sub">Selamat datang, {{ strtok(Auth::user()->name, ' ') }}! Dashboard ini menampilkan analisis kawasan konservasi berbasis data untuk mendukung proses data mining, kerja praktek, dan manajemen data BKSDA Sulawesi Tengah.</p>
    </x-slot>

    <x-slot name="actions">
        <label class="search-box">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            <input id="searchTable" type="search" placeholder="Cari kelompok atau usaha…" aria-label="Cari kelompok binaan">
        </label>
        <span class="pill">
            <i class="fa-solid fa-calendar-days text-brand-600" aria-hidden="true"></i>
            Tahun Anggaran 2026
        </span>
    </x-slot>

    <div class="flex flex-col gap-4">
        <!-- Kartu statistik -->
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-card icon="wallet" tone="teal" label="Total Nilai Ekonomi (NTE)" :chip="number_format($capaian, 1, ',', '.').'%'" note="dari target Rp 1,575 M">
                <x-rupiah :value="$totalNte" />
            </x-stat-card>

            <x-stat-card icon="mountain-sun" tone="green" label="Kawasan Konservasi" note="CA, SM, TB & TWA">
                {{ $jumlahKawasan ?? 0 }} Wilayah
            </x-stat-card>

            <x-stat-card icon="users" tone="purple" label="Kelompok Binaan" note="Aktif terverifikasi">
                {{ $jumlahKelompok ?? 0 }} Kelompok
            </x-stat-card>

            <x-stat-card icon="hand-holding-dollar" tone="blue" label="Total Penyaluran Bantuan" note="Fasilitas & pemberdayaan">
                <x-rupiah :value="$totalBantuan ?? 0" />
            </x-stat-card>
        </div>

        <x-glass-card title="Tahapan Data Mining" class="scroll-mt-6">
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl border border-brand-100 bg-brand-50/60 p-3">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-[0.18em] text-brand-700">01. Preprocessing</p>
                    <p class="text-sm text-slate-700">Pembersihan dan penyusunan data NTE, kawasan, dan kelompok agar siap dianalisis secara konsisten.</p>
                </div>
                <div class="rounded-2xl border border-amber-100 bg-amber-50/60 p-3">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-[0.18em] text-amber-700">02. Clustering</p>
                    <p class="text-sm text-slate-700">Pengelompokan kawasan konservasi berdasarkan kontribusi NTE dan karakteristik jumlah desa penyangga.</p>
                </div>
                <div class="rounded-2xl border border-sky-100 bg-sky-50/60 p-3">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-[0.18em] text-sky-700">03. Evaluasi</p>
                    <p class="text-sm text-slate-700">Interpretasi hasil analisis untuk menilai pola wilayah, kontribusi, dan prioritas pemberdayaan masyarakat.</p>
                </div>
                <div class="rounded-2xl border border-emerald-100 bg-emerald-50/60 p-3">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">04. Proyek Data Mining</p>
                    <p class="text-sm text-slate-700">Hasil visualisasi ini menjadi dasar laporan kerja praktek dan manajemen data berbasis bukti.</p>
                </div>
            </div>
        </x-glass-card>

        <!-- Baris 1: sebaran NTE, bantuan per usaha, kelompok terbaru -->
        <div class="grid gap-4 lg:grid-cols-2 xl:grid-cols-[1.7fr_1fr_1.1fr]">
            <x-glass-card title="Akumulasi NTE per Kawasan" class="lg:col-span-2 xl:col-span-1">
                <x-slot name="action">
                    @if ($tahunNte)<span class="card-pill">Tahun {{ $tahunNte }}</span>@endif
                </x-slot>

                <div class="flex flex-wrap items-center gap-2.5">
                    <p class="big-number"><x-rupiah :value="$totalNte" /></p>
                    <span class="chip chip-up"><i class="fa-solid fa-arrow-up text-[9px]" aria-hidden="true"></i>{{ number_format($capaian, 1, ',', '.') }}%</span>
                    <span class="text-xs text-ink-500">dari target Rp 1,575 M</span>
                </div>

                <div class="relative mt-4 h-60">
                    <canvas id="chart-nte" role="img" aria-label="Grafik area akumulasi NTE per kawasan konservasi, diurutkan dari NTE terbesar"></canvas>
                </div>
            </x-glass-card>

            <x-glass-card title="Bantuan per Jenis Usaha">
                @if ($usahaTampil->isEmpty())
                    <div class="empty-state">
                        <i class="fa-solid fa-chart-pie text-2xl text-ink-400" aria-hidden="true"></i>
                        Belum ada data bantuan.
                    </div>
                @else
                    <div class="relative mx-auto size-44">
                        <canvas id="chart-usaha" role="img" aria-label="Grafik donat bantuan per jenis usaha"></canvas>
                        <div class="pointer-events-none absolute inset-0 grid place-content-center text-center">
                            <p class="text-xs text-ink-500">Total bantuan</p>
                            <p class="text-base font-bold"><x-rupiah :value="$totalUsaha" /></p>
                        </div>
                    </div>

                    <ul class="mt-5 space-y-2.5">
                        @foreach ($usahaTampil as $nama => $nilai)
                            <li class="flex items-center gap-2 text-xs">
                                <span class="legend-dot" style="background-color: {{ $palet[$loop->index] }}"></span>
                                <span class="min-w-0 flex-1 truncate text-ink-700" title="{{ $nama }}">{{ $nama }}</span>
                                <span class="font-semibold"><x-rupiah :value="$nilai" /></span>
                                <span class="text-ink-500">({{ $persen($nilai, $totalUsaha) }}%)</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-glass-card>

            <x-glass-card title="Kelompok Terbaru">
                <x-slot name="action"><a href="#kelompok" class="card-link">Lihat semua</a></x-slot>

                @forelse ($kelompokTerbaru as $kelompok)
                    <div class="list-row">
                        <x-icon-badge :tone="$nadaAvatar[$loop->index % count($nadaAvatar)]">{{ mb_strtoupper(mb_substr($kelompok->nama_kelompok, 0, 1)) }}</x-icon-badge>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold">{{ $kelompok->nama_kelompok }}</p>
                            <p class="truncate text-xs text-ink-500">{{ $kelompok->kawasan->nama_kawasan ?? '-' }}</p>
                        </div>
                        <p class="shrink-0 text-sm font-semibold"><x-rupiah :value="$kelompok->jumlah_bantuan" /></p>
                    </div>
                @empty
                    <div class="empty-state">
                        <i class="fa-solid fa-folder-open text-2xl text-ink-400" aria-hidden="true"></i>
                        Belum ada kelompok binaan.
                    </div>
                @endforelse
            </x-glass-card>
        </div>

        <!-- Baris 2: kawasan unggulan, kawasan teratas, NTE langsung vs tidak langsung -->
        <div id="kawasan" class="grid scroll-mt-6 gap-4 lg:grid-cols-2 xl:grid-cols-[1.15fr_1fr_1.4fr]">
            <x-glass-card title="Kawasan Unggulan">
                @if ($kawasanTeratas->isEmpty())
                    <div class="empty-state">Belum ada data NTE kawasan.</div>
                @else
                    <div x-data="{ i: 0 }">
                        <div class="grid grid-cols-[1fr_4.75rem] gap-3">
                            <div>
                                @foreach ($kawasanTeratas as $k)
                                    <article x-show="i === {{ $loop->index }}" @unless ($loop->first) x-cloak @endunless class="kawasan-card">
                                        <div class="flex items-start justify-between">
                                            <span class="card-chip"></span>
                                            <span class="text-xl font-extrabold italic tracking-wide">{{ $k->fungsi_kawasan }}</span>
                                        </div>
                                        <p class="whitespace-nowrap text-base font-semibold tracking-wide"><x-rupiah :value="$k->nte->total_nte" full /></p>
                                        <div class="flex items-end justify-between gap-3">
                                            <p class="min-w-0 truncate text-sm font-semibold">{{ $k->nama_kawasan }}</p>
                                            <div class="shrink-0 text-right">
                                                <p class="text-[10px] text-white/70">Desa</p>
                                                <p class="text-xs font-semibold">{{ $k->jumlah_desa_difasilitasi }}/{{ $k->jumlah_desa_sekitar }}</p>
                                            </div>
                                        </div>
                                    </article>
                                @endforeach
                            </div>

                            <a href="{{ route('kelompok.create') }}" class="add-tile">
                                <span class="grid size-9 place-items-center rounded-full border border-slate-300"><i class="fa-solid fa-plus" aria-hidden="true"></i></span>
                                Tambah Kelompok
                            </a>
                        </div>

                        <div class="mt-4 flex justify-center gap-2">
                            @foreach ($kawasanTeratas as $k)
                                <button type="button" class="carousel-dot" :class="{ 'carousel-dot-active': i === {{ $loop->index }} }"
                                        @click="i = {{ $loop->index }}" aria-label="Tampilkan {{ $k->nama_kawasan }}"></button>
                            @endforeach
                        </div>
                    </div>
                @endif
            </x-glass-card>

            <x-glass-card title="Kawasan Teratas">
                <x-slot name="action"><span class="card-pill">Total NTE</span></x-slot>

                @forelse ($kawasanTeratas as $k)
                    <div class="list-row">
                        <x-icon-badge :tone="$nadaFungsi[$k->fungsi_kawasan] ?? 'amber'" class="text-[11px]">{{ $k->fungsi_kawasan }}</x-icon-badge>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold">{{ $k->nama_kawasan }}</p>
                            <p class="text-xs text-ink-500">{{ $k->jumlah_desa_sekitar }} desa sekitar</p>
                        </div>
                        <p class="shrink-0 text-sm font-semibold"><x-rupiah :value="$k->nte->total_nte" /></p>
                        <span class="chip chip-neutral shrink-0">{{ $persen($k->nte->total_nte, $totalNte) }}%</span>
                    </div>
                @empty
                    <div class="empty-state">Belum ada data NTE kawasan.</div>
                @endforelse
            </x-glass-card>

            <x-glass-card title="Komposisi NTE" class="lg:col-span-2 xl:col-span-1">
                <x-slot name="action"><span class="card-pill">8 kawasan teratas</span></x-slot>

                <div class="flex items-end justify-between gap-3">
                    <div>
                        <p class="big-number"><x-rupiah :value="$totalLangsung" /></p>
                        <p class="mt-2 flex items-center gap-1.5 text-xs text-ink-500">
                            <span class="chip chip-up"><i class="fa-solid fa-arrow-up text-[9px]" aria-hidden="true"></i>{{ number_format($porsiLangsung, 1, ',', '.') }}%</span>
                            NTE langsung dari total
                        </p>
                    </div>
                    <ul class="space-y-1.5 text-xs text-ink-700">
                        <li class="flex items-center gap-2"><span class="legend-dot bg-brand-500"></span>Langsung</li>
                        <li class="flex items-center gap-2"><span class="legend-dot bg-[#7fb4f0]"></span>Tidak langsung</li>
                    </ul>
                </div>

                <div class="relative mt-4 h-52">
                    <canvas id="chart-split" role="img" aria-label="Grafik batang NTE langsung dan tidak langsung per kawasan"></canvas>
                </div>
            </x-glass-card>
        </div>

        <!-- Tabel kelompok binaan -->
        <x-glass-card id="kelompok" class="scroll-mt-6 p-0 sm:p-0">
            <header class="flex flex-wrap items-center justify-between gap-3 px-5 pb-3 pt-5">
                <div>
                    <h2 class="card-title">Daftar Kelompok Binaan</h2>
                    <p class="mt-0.5 text-xs text-ink-500">Kelompok masyarakat di sekitar kawasan konservasi BKSDA Sulteng.</p>
                </div>
                <a href="{{ route('kelompok.create') }}" class="btn-brand"><i class="fa-solid fa-plus" aria-hidden="true"></i> Tambah Kelompok</a>
            </header>

            <div class="overflow-x-auto px-3 pb-3">
                <table class="glass-table" id="kelompokTable">
                    <thead>
                        <tr>
                            <th class="w-14">No</th>
                            <th>Nama Kelompok</th>
                            <th>Kawasan Konservasi</th>
                            <th>Ketua & Kontak</th>
                            <th>Jenis Usaha</th>
                            <th>Nilai Bantuan</th>
                            <th class="w-20 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($kelompoks as $index => $kelompok)
                            <tr>
                                <td class="text-ink-500">{{ $index + 1 }}</td>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <span class="avatar">{{ mb_strtoupper(mb_substr($kelompok->nama_kelompok ?? 'K', 0, 1)) }}</span>
                                        <div>
                                            <p class="font-semibold">{{ $kelompok->nama_kelompok }}</p>
                                            <p class="text-xs text-ink-500">ID: KLP-00{{ $kelompok->id }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="tag"><i class="fa-solid fa-location-dot text-rose-500" aria-hidden="true"></i>{{ $kelompok->kawasan->nama_kawasan ?? '-' }}</span>
                                </td>
                                <td>
                                    <p class="font-medium">{{ $kelompok->nama_ketua }}</p>
                                    <p class="text-xs text-ink-500"><i class="fa-solid fa-phone mr-1" aria-hidden="true"></i>{{ $kelompok->no_hp ?? '-' }}</p>
                                </td>
                                <td><span class="tag">{{ $kelompok->jenis_usaha }}</span></td>
                                <td class="whitespace-nowrap font-semibold text-brand-700"><x-rupiah :value="$kelompok->jumlah_bantuan" full /></td>
                                <td class="text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('kelompok.edit', $kelompok->id) }}" class="btn-icon" title="Edit data" aria-label="Edit {{ $kelompok->nama_kelompok }}">
                                            <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                                        </a>
                                        <form action="{{ route('kelompok.destroy', $kelompok->id) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-icon-danger" title="Hapus data" aria-label="Hapus {{ $kelompok->nama_kelompok }}"
                                                    onclick="return confirm('Yakin ingin menghapus data kelompok ini?')">
                                                <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state">
                                        <i class="fa-solid fa-folder-open text-2xl text-ink-400" aria-hidden="true"></i>
                                        Belum ada data kelompok binaan yang terdaftar.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-glass-card>

        <x-glass-card id="clustering" class="scroll-mt-6">
            <header class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
<<<<<<< Updated upstream
                    <h2 class="card-title">Analisis Clustering Kawasan Berdasarkan NTE</h2>
                    <p class="mt-0.5 text-xs text-ink-500">Pemetaan kawasan hasil proses data mining untuk melihat pola kontribusi NTE dan keterkaitan dengan jumlah desa penyangga.</p>
=======
                    <h2 class="card-title">Data Mining: K-Means Clustering Kawasan</h2>
                    <p class="mt-0.5 text-xs text-ink-500">Fitur: Total NTE, jumlah desa sekitar, dan rasio desa difasilitasi. Data dinormalisasi dengan Min-Max sebelum pengelompokan.</p>
>>>>>>> Stashed changes
                </div>
                <span class="chip chip-neutral px-3 py-1.5"><i class="fa-solid fa-brain" aria-hidden="true"></i> Data Mining</span>
            </header>

<<<<<<< Updated upstream
            <div class="relative mt-5 h-80">
                <canvas id="chart-cluster" role="img" aria-label="Diagram sebar kawasan berdasarkan total NTE dan jumlah desa"></canvas>
=======
            <div class="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl border border-slate-100 bg-slate-50 p-3">
                    <p class="text-xs text-ink-500">Data yang dianalisis</p>
                    <p class="mt-1 text-lg font-bold">{{ $clusterAnalysis['sample_count'] }} kawasan</p>
                    @if ($clusterAnalysis['excluded_count'] > 0)
                        <p class="text-xs text-amber-700">{{ $clusterAnalysis['excluded_count'] }} data tidak valid dikeluarkan</p>
                    @endif
                </div>
                <div class="rounded-xl border border-slate-100 bg-slate-50 p-3">
                    <p class="text-xs text-ink-500">Jumlah cluster</p>
                    <p class="mt-1 text-lg font-bold">K = {{ $clusterAnalysis['k'] }}</p>
                    <p class="text-xs text-ink-500">K-Means, maksimal 100 iterasi</p>
                </div>
                <div class="rounded-xl border border-slate-100 bg-slate-50 p-3">
                    <p class="text-xs text-ink-500">Validasi silhouette</p>
                    <p class="mt-1 text-lg font-bold">{{ $clusterAnalysis['silhouette'] !== null ? number_format($clusterAnalysis['silhouette'], 3, ',', '.') : 'Belum tersedia' }}</p>
                    <p class="text-xs text-ink-500">Rentang -1 sampai 1; makin tinggi umumnya makin terpisah</p>
                </div>
                @foreach ($clusterAnalysis['clusters'] as $cluster)
                    <div class="rounded-xl border border-slate-100 bg-slate-50 p-3">
                        <p class="text-xs text-ink-500">{{ $cluster['name'] }} ({{ $cluster['count'] }} kawasan)</p>
                        <p class="mt-1 text-sm font-bold">NTE rata-rata Rp {{ number_format($cluster['mean_nte_million'], 1, ',', '.') }} juta</p>
                        <p class="text-xs text-ink-500">Fasilitasi desa rata-rata {{ number_format($cluster['mean_coverage_percent'], 1, ',', '.') }}%</p>
                    </div>
                @endforeach
>>>>>>> Stashed changes
            </div>

            @if ($clusterAnalysis['status'] === 'ok')
                <div class="relative h-80">
                    <canvas id="chart-cluster" role="img" aria-label="Diagram sebar hasil K-Means berdasarkan total NTE, dengan warna berbeda untuk setiap cluster"></canvas>
                </div>
                <p class="mt-3 text-xs text-ink-500">Kelompok diurutkan menurut rerata NTE. Hasil bersifat eksploratif dan perlu ditafsirkan bersama kondisi lapangan; silhouette adalah validasi internal, bukan bukti sebab-akibat.</p>
            @else
                <div class="empty-state">Clustering belum dapat dijalankan: diperlukan sedikitnya tiga kawasan valid dengan pola fitur yang berbeda.</div>
            @endif
        </x-glass-card>

    </div>

    <script type="application/json" id="dashboard-data">@json($charts, JSON_HEX_TAG | JSON_HEX_AMP)</script>
</x-app-layout>
