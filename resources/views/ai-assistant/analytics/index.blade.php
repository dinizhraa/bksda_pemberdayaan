<x-app-layout>
    <x-slot name="header">
        <h1 class="page-title">Analisis Data</h1>
        <p class="page-sub">Statistik dihitung langsung dari database. AI hanya menyusun insight dan rekomendasi.</p>
    </x-slot>

    <div
        data-ai-analytics
        data-ai-charts="{{ json_encode($metrics['charts'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}"
        class="flex flex-col gap-4"
    >
        <form method="GET" action="{{ route('ai.analytics.index') }}" class="glass-card grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            <div>
                <label for="year" class="field-label">Tahun Pembinaan</label>
                <select id="year" name="year" class="field mt-1">
                    <option value="">Semua tahun</option>
                    @foreach ($years as $year)
                        <option value="{{ $year }}" @selected((string) ($filters['year'] ?? '') === (string) $year)>{{ $year }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="kawasan_id" class="field-label">Kawasan</label>
                <select id="kawasan_id" name="kawasan_id" class="field mt-1">
                    <option value="">Semua kawasan</option>
                    @foreach ($kawasans as $kawasan)
                        <option value="{{ $kawasan->id }}" @selected((string) ($filters['kawasan_id'] ?? '') === (string) $kawasan->id)>{{ $kawasan->nama_kawasan }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="desa_id" class="field-label">Desa</label>
                <select id="desa_id" name="desa_id" class="field mt-1">
                    <option value="">Semua desa</option>
                    @foreach ($desas as $desa)
                        <option value="{{ $desa->id }}" @selected((string) ($filters['desa_id'] ?? '') === (string) $desa->id)>{{ $desa->nama_desa }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="status" class="field-label">Status Kelompok</label>
                <select id="status" name="status" class="field mt-1">
                    <option value="">Semua status</option>
                    <option value="Aktif" @selected(($filters['status'] ?? '') === 'Aktif')>Aktif</option>
                    <option value="Tidak Aktif" @selected(($filters['status'] ?? '') === 'Tidak Aktif')>Tidak Aktif</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="btn-brand flex-1 justify-center">Terapkan</button>
                <a href="{{ route('ai.analytics.index') }}" class="btn-ghost justify-center">Reset</a>
            </div>
        </form>

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Statistik kelompok">
            <article class="glass-card p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-ink-500">Jumlah Kelompok</p>
                <p class="mt-2 text-3xl font-extrabold text-brand-800">{{ number_format($metrics['summary']['total']) }}</p>
                <p class="mt-1 text-xs text-ink-500">Sesuai filter yang diterapkan</p>
            </article>
            <article class="glass-card p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-ink-500">Aktif / Tidak Aktif</p>
                <p class="mt-2 text-3xl font-extrabold text-brand-800">{{ number_format($metrics['summary']['active']) }} <span class="text-lg font-semibold text-ink-400">/ {{ number_format($metrics['summary']['inactive']) }}</span></p>
                <p class="mt-1 text-xs text-ink-500">Status kosong: {{ number_format($metrics['summary']['unknown_status']) }}</p>
            </article>
            <article class="glass-card p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-ink-500">Anggota Terdata</p>
                <p class="mt-2 text-3xl font-extrabold text-brand-800">{{ number_format($metrics['summary']['male_members'] + $metrics['summary']['female_members']) }}</p>
                <p class="mt-1 text-xs text-ink-500">L: {{ number_format($metrics['summary']['male_members']) }} · P: {{ number_format($metrics['summary']['female_members']) }}</p>
            </article>
            <article class="glass-card p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-ink-500">Total Bantuan</p>
                <p class="mt-2 break-words text-2xl font-extrabold text-brand-800">Rp {{ number_format($metrics['summary']['total_aid'], 0, ',', '.') }}</p>
                <p class="mt-1 text-xs text-ink-500">{{ number_format($metrics['summary']['unmonitored']) }} kelompok belum memiliki tahun monitoring</p>
            </article>
        </section>

        <section class="glass-card flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-bold text-ink-900">Insight dan rekomendasi AI</h2>
                <p class="mt-1 text-sm text-ink-500">AI menerima ringkasan agregat, bukan data kontak atau identitas ketua.</p>
            </div>
            <button type="button" id="ai-analytics-insight-button" data-url="{{ route('ai.analytics.insight', [], false) }}" class="btn-brand shrink-0 justify-center">
                <i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i>
                Buat insight
            </button>
            <div id="ai-analytics-insight" class="hidden basis-full rounded-2xl border border-brand-100 bg-brand-50/70 p-4 text-sm leading-6 text-ink-700 sm:basis-full" role="status" aria-live="polite"></div>
        </section>

        <section class="grid gap-4 xl:grid-cols-2" aria-label="Grafik analisis kelompok">
            <article class="glass-card min-w-0">
                <h2 class="card-title">Status Kelompok</h2>
                <div class="mt-3 h-64"><canvas id="ai-chart-status" aria-label="Grafik perbandingan status kelompok"></canvas></div>
            </article>
            <article class="glass-card min-w-0">
                <h2 class="card-title">Persebaran per Kawasan</h2>
                <div class="mt-3 h-64"><canvas id="ai-chart-kawasan" aria-label="Grafik jumlah kelompok per kawasan"></canvas></div>
            </article>
            <article class="glass-card min-w-0">
                <h2 class="card-title">Persebaran per Desa</h2>
                <div class="mt-3 h-64"><canvas id="ai-chart-desa" aria-label="Grafik jumlah kelompok per desa"></canvas></div>
            </article>
            <article class="glass-card min-w-0">
                <h2 class="card-title">Anggota Laki-laki dan Perempuan</h2>
                <div class="mt-3 h-64"><canvas id="ai-chart-gender" aria-label="Grafik jumlah anggota berdasarkan jenis kelamin"></canvas></div>
            </article>
            <article class="glass-card min-w-0">
                <h2 class="card-title">Jenis Usaha dan Bantuan</h2>
                <div class="mt-3 h-64"><canvas id="ai-chart-business" aria-label="Grafik jumlah kelompok dan bantuan per jenis usaha"></canvas></div>
            </article>
            <article class="glass-card min-w-0">
                <h2 class="card-title">Perkembangan berdasarkan Tahun Pembentukan</h2>
                <div class="mt-3 h-64"><canvas id="ai-chart-formation" aria-label="Grafik jumlah kelompok berdasarkan tahun pembentukan"></canvas></div>
            </article>
            <article class="glass-card min-w-0 xl:col-span-2">
                <h2 class="card-title">Referensi NTE per Kawasan{{ $metrics['charts']['nte_year'] ? ' · '.$metrics['charts']['nte_year'] : '' }}</h2>
                <p class="mt-1 text-xs text-ink-500">NTE ditampilkan sebagai data referensi kawasan, terpisah dari filter status dan tahun pembinaan kelompok.</p>
                <div class="mt-3 h-64"><canvas id="ai-chart-nte" aria-label="Grafik nilai NTE per kawasan"></canvas></div>
            </article>
        </section>

        <section class="grid gap-4 xl:grid-cols-2">
            <article class="glass-card overflow-hidden p-0">
                <h2 class="card-title px-5 pt-5">Ringkasan Kawasan</h2>
                <div class="overflow-x-auto p-4">
                    <table class="glass-table">
                        <thead><tr><th>Kawasan</th><th>Jumlah Kelompok</th><th>Total Bantuan</th></tr></thead>
                        <tbody>
                            @forelse ($metrics['charts']['kawasan'] as $row)
                                <tr><td>{{ $row['label'] }}</td><td>{{ number_format($row['value']) }}</td><td>Rp {{ number_format($row['aid'], 0, ',', '.') }}</td></tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-ink-500">Belum ada data untuk filter ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>
            <article class="glass-card overflow-hidden p-0">
                <h2 class="card-title px-5 pt-5">Catatan Monitoring</h2>
                <div class="p-5">
                    <p class="text-sm text-ink-600">Kelompok belum memiliki tahun monitoring: <strong>{{ number_format($metrics['summary']['unmonitored']) }}</strong></p>
                    <p class="mt-2 text-sm text-ink-600">Kelompok dengan catatan Monev: <strong>{{ number_format($metrics['summary']['monev_records']) }}</strong></p>
                    <p class="mt-4 text-sm font-semibold text-ink-700">Kelompok menurut tahun monitoring</p>
                    <ul class="mt-2 space-y-1 text-sm text-ink-600">
                        @forelse ($metrics['charts']['monitoring_year'] as $row)
                            <li>{{ $row['label'] }}: <strong>{{ number_format($row['value']) }}</strong> kelompok</li>
                        @empty
                            <li>Belum ada tahun monitoring terdata.</li>
                        @endforelse
                    </ul>
                    @if ($metrics['summary']['monev_records'] === 0)
                        <p class="mt-3 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs leading-5 text-amber-800">Data catatan Monev belum tersedia pada filter ini. Insight tidak boleh menyimpulkan hasil monitoring.</p>
                    @endif
                </div>
            </article>
        </section>
    </div>
</x-app-layout>
