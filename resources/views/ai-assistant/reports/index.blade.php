<x-app-layout>
    <x-slot name="header">
        <h1 class="page-title">Generator Laporan AI</h1>
        <p class="page-sub">Pilih cakupan data untuk membuat pratinjau laporan. Angka dihitung oleh aplikasi dari database.</p>
    </x-slot>

    <div class="flex flex-col gap-4">
        @if ($errors->has('gemini'))
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">{{ $errors->first('gemini') }}</div>
        @endif

        <form method="POST" action="{{ route('ai.reports.preview', [], false) }}" class="no-print glass-card grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @csrf
            <div>
                <label for="type" class="field-label">Jenis Laporan</label>
                <select id="type" name="type" class="field mt-1" required>
                    <option value="">Pilih jenis laporan</option>
                    <option value="groups" @selected(old('type', $report['type'] ?? '') === 'groups')>Kelompok binaan</option>
                    <option value="area_desa" @selected(old('type', $report['type'] ?? '') === 'area_desa')>Kawasan dan desa</option>
                    <option value="development" @selected(old('type', $report['type'] ?? '') === 'development')>Perkembangan kelompok</option>
                    <option value="monev" @selected(old('type', $report['type'] ?? '') === 'monev')>Hasil Monev</option>
                    <option value="needs" @selected(old('type', $report['type'] ?? '') === 'needs')>Kebutuhan dan pendampingan</option>
                    <option value="aid" @selected(old('type', $report['type'] ?? '') === 'aid')>Bantuan kelompok</option>
                </select>
            </div>
            <div>
                <label for="period_start" class="field-label">Periode dari (Tahun Pembinaan)</label>
                <select id="period_start" name="period_start" class="field mt-1">
                    <option value="">Awal data</option>
                    @foreach ($years as $year)
                        <option value="{{ $year }}" @selected((string) old('period_start', $filters['period_start'] ?? '') === (string) $year)>{{ $year }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="period_end" class="field-label">Periode sampai</label>
                <select id="period_end" name="period_end" class="field mt-1">
                    <option value="">Tahun terbaru</option>
                    @foreach ($years as $year)
                        <option value="{{ $year }}" @selected((string) old('period_end', $filters['period_end'] ?? '') === (string) $year)>{{ $year }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('period_end')" class="mt-1" />
            </div>
            <div>
                <label for="kawasan_id" class="field-label">Kawasan</label>
                <select id="kawasan_id" name="kawasan_id" class="field mt-1">
                    <option value="">Semua kawasan</option>
                    @foreach ($kawasans as $kawasan)
                        <option value="{{ $kawasan->id }}" @selected((string) old('kawasan_id', $filters['kawasan_id'] ?? '') === (string) $kawasan->id)>{{ $kawasan->nama_kawasan }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="desa_id" class="field-label">Desa</label>
                <select id="desa_id" name="desa_id" class="field mt-1">
                    <option value="">Semua desa</option>
                    @foreach ($desas as $desa)
                        <option value="{{ $desa->id }}" @selected((string) old('desa_id', $filters['desa_id'] ?? '') === (string) $desa->id)>{{ $desa->nama_desa }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="kelompok_id" class="field-label">Kelompok</label>
                <select id="kelompok_id" name="kelompok_id" class="field mt-1">
                    <option value="">Semua kelompok</option>
                    @foreach ($kelompoks as $kelompok)
                        <option value="{{ $kelompok->id }}" @selected((string) old('kelompok_id', $filters['kelompok_id'] ?? '') === (string) $kelompok->id)>{{ $kelompok->nama_kelompok }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2 sm:col-span-2 xl:col-span-3">
                <button type="submit" class="btn-brand">
                    <i class="fa-solid fa-file-circle-plus" aria-hidden="true"></i>
                    Buat pratinjau
                </button>
                <a href="{{ route('ai.reports.index') }}" class="btn-ghost">Reset</a>
            </div>
        </form>

        @if ($report)
            <article class="ai-print-content glass-card flex flex-col gap-5">
                <header class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-brand-700">AI Assistant BKSDA</p>
                        <h2 class="mt-1 text-2xl font-extrabold tracking-tight text-ink-900">{{ $report['title'] }}</h2>
                        <p class="mt-1 text-xs text-ink-500">Dibuat {{ $report['generated_at'] }}</p>
                    </div>
                    <div class="no-print flex flex-wrap gap-2">
                        <button type="button" data-print-report class="btn-ghost">
                            <i class="fa-solid fa-file-pdf" aria-hidden="true"></i>Ekspor PDF
                        </button>
                        <a href="{{ route('ai.reports.word', $reportId) }}" class="btn-brand">
                            <i class="fa-solid fa-file-word" aria-hidden="true"></i>Unduh Word
                        </a>
                    </div>
                </header>

                @if ($filterLabels !== [])
                    <section class="rounded-2xl border border-ink-100 bg-white/70 p-4">
                        <h3 class="text-xs font-bold uppercase tracking-wide text-ink-500">Cakupan laporan</h3>
                        <dl class="mt-2 flex flex-wrap gap-x-6 gap-y-2 text-sm">
                            @foreach ($filterLabels as $label => $value)
                                <div class="flex gap-1"><dt class="font-semibold">{{ $label }}:</dt><dd>{{ $value }}</dd></div>
                            @endforeach
                        </dl>
                    </section>
                @endif

                <section>
                    <h3 class="font-bold text-ink-900">Statistik terhitung dari database</h3>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <div class="rounded-2xl bg-brand-50 p-4"><p class="text-xs text-brand-700">Jumlah Kelompok</p><p class="mt-1 text-2xl font-extrabold text-brand-900">{{ number_format($report['summary']['total']) }}</p></div>
                        <div class="rounded-2xl bg-emerald-50 p-4"><p class="text-xs text-emerald-700">Aktif / Tidak Aktif</p><p class="mt-1 text-2xl font-extrabold text-emerald-900">{{ number_format($report['summary']['active']) }} / {{ number_format($report['summary']['inactive']) }}</p></div>
                        <div class="rounded-2xl bg-sky-50 p-4"><p class="text-xs text-sky-700">Jumlah Anggota L / P</p><p class="mt-1 text-2xl font-extrabold text-sky-900">{{ number_format($report['summary']['male_members']) }} / {{ number_format($report['summary']['female_members']) }}</p></div>
                        <div class="rounded-2xl bg-amber-50 p-4"><p class="text-xs text-amber-700">Total Bantuan</p><p class="mt-1 break-words text-xl font-extrabold text-amber-900">Rp {{ number_format($report['summary']['total_aid'], 0, ',', '.') }}</p></div>
                    </div>
                </section>

                @if ($report['notice'])
                    <p class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900" role="status">{{ $report['notice'] }}</p>
                @elseif ($report['sections'])
                    <section class="space-y-5">
                        @foreach ([
                            'pendahuluan' => 'Pendahuluan',
                            'gambaran_umum' => 'Gambaran Umum',
                            'hasil_analisis' => 'Hasil Analisis',
                            'kesimpulan' => 'Kesimpulan',
                        ] as $key => $heading)
                            <div>
                                <h3 class="font-bold text-ink-900">{{ $heading }}</h3>
                                <p class="mt-2 whitespace-pre-wrap text-sm leading-7 text-ink-700">{{ $report['sections'][$key] }}</p>
                            </div>
                        @endforeach
                        <div>
                            <h3 class="font-bold text-ink-900">Rekomendasi</h3>
                            <ol class="mt-2 list-decimal space-y-2 pl-5 text-sm leading-6 text-ink-700">
                                @foreach ($report['sections']['rekomendasi'] as $recommendation)
                                    <li>{{ $recommendation }}</li>
                                @endforeach
                            </ol>
                        </div>
                    </section>
                @endif

                @if ($report['charts']['kawasan'] !== [])
                    <section class="grid gap-4 lg:grid-cols-2">
                        <div>
                            <h3 class="font-bold text-ink-900">Kelompok per Kawasan</h3>
                            <div class="mt-2 overflow-x-auto">
                                <table class="glass-table">
                                    <thead><tr><th>Kawasan</th><th>Kelompok</th><th>Bantuan (Rp)</th></tr></thead>
                                    <tbody>
                                        @foreach ($report['charts']['kawasan'] as $row)
                                            <tr><td>{{ $row['label'] }}</td><td>{{ number_format($row['value']) }}</td><td>{{ number_format($row['aid'], 0, ',', '.') }}</td></tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div>
                            <h3 class="font-bold text-ink-900">Kelompok per Desa</h3>
                            <div class="mt-2 overflow-x-auto">
                                <table class="glass-table">
                                    <thead><tr><th>Desa</th><th>Kelompok</th></tr></thead>
                                    <tbody>
                                        @forelse ($report['charts']['desa'] as $row)
                                            <tr><td>{{ $row['label'] }}</td><td>{{ number_format($row['value']) }}</td></tr>
                                        @empty
                                            <tr><td colspan="2" class="text-center text-ink-500">Belum ada data.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>
                @endif

                @if ($report['records_truncated'])
                    <p class="text-xs text-ink-500">Daftar contoh dibatasi hingga 30 kelompok untuk menjaga ukuran konteks AI.</p>
                @endif
            </article>
        @endif
    </div>
</x-app-layout>
