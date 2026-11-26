<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <a href="{{ route('ai.monev.index') }}" class="text-xs font-semibold text-brand-700 hover:text-brand-900">
                    <i class="fa-solid fa-arrow-left mr-1" aria-hidden="true"></i>Kembali ke Monitoring & Evaluasi
                </a>
                <h1 class="page-title mt-2">{{ $kelompok->nama_kelompok }}</h1>
                <p class="page-sub">{{ $kelompok->kawasan?->nama_kawasan ?? 'Kawasan belum diisi' }} · {{ $kelompok->desa?->nama_desa ?? 'Desa belum diisi' }}</p>
            </div>
            <div class="no-print flex gap-2">
                @if ($evaluation)
                    <button type="button" data-print-report class="btn-ghost">
                        <i class="fa-solid fa-print" aria-hidden="true"></i>Cetak hasil
                    </button>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="flex flex-col gap-4">
        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
        @endif
        @if (session('monev_notice'))
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm leading-6 text-amber-900" role="status">{{ session('monev_notice') }}</div>
        @endif
        @if ($errors->has('gemini'))
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">{{ $errors->first('gemini') }}</div>
        @endif

        <section class="glass-card">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="card-title">Data kelompok yang digunakan</h2>
                    <p class="mt-1 text-xs text-ink-500">AI tidak menerima nama ketua, nomor HP, atau identitas akun.</p>
                </div>
                @if ($hasMonevData)
                    <form method="POST" action="{{ route('ai.monev.analyze', $kelompok, false) }}" class="no-print">
                        @csrf
                        <button type="submit" class="btn-brand">
                            <i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i>
                            {{ $evaluation ? 'Analisis ulang' : 'Analisis dengan AI' }}
                        </button>
                    </form>
                @endif
            </div>

            @if (! $hasMonevData)
                <p class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900">
                    Data monitoring dan catatan Monev belum tersedia. AI tidak dijalankan agar tidak mengarang kendala, hasil, atau rekomendasi lapangan.
                </p>
            @elseif (! filled($kelompok->catatan_kebutuhan_hasil_monev))
                <p class="mt-4 rounded-2xl border border-sky-200 bg-sky-50 p-4 text-sm leading-6 text-sky-900">
                    Tahun monitoring tercatat, tetapi catatan Monev belum diisi. Analisis hanya dapat memakai metadata dan tidak dapat menyimpulkan hasil atau kendala lapangan.
                </p>
            @endif

            <dl class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-xl bg-white/70 p-3"><dt class="text-xs text-ink-500">Status Kelompok</dt><dd class="mt-1 font-semibold">{{ $kelompok->status_kelompok ?? '-' }}</dd></div>
                <div class="rounded-xl bg-white/70 p-3"><dt class="text-xs text-ink-500">Tahun Pembentukan</dt><dd class="mt-1 font-semibold">{{ $kelompok->tahun_pembentukan ?? '-' }}</dd></div>
                <div class="rounded-xl bg-white/70 p-3"><dt class="text-xs text-ink-500">Tahun Monitoring</dt><dd class="mt-1 font-semibold">{{ $kelompok->tahun_monitoring ?? '-' }}</dd></div>
                <div class="rounded-xl bg-white/70 p-3"><dt class="text-xs text-ink-500">Jenis Usaha</dt><dd class="mt-1 font-semibold">{{ $kelompok->jenis_usaha ?? '-' }}</dd></div>
                <div class="rounded-xl bg-white/70 p-3"><dt class="text-xs text-ink-500">Jumlah Anggota</dt><dd class="mt-1 font-semibold">{{ number_format($kelompok->jumlah_anggota ?? 0) }}</dd></div>
                <div class="rounded-xl bg-white/70 p-3"><dt class="text-xs text-ink-500">Anggota L / P</dt><dd class="mt-1 font-semibold">{{ $kelompok->jumlah_anggota_laki_laki ?? '-' }} / {{ $kelompok->jumlah_anggota_perempuan ?? '-' }}</dd></div>
                <div class="rounded-xl bg-white/70 p-3"><dt class="text-xs text-ink-500">Jumlah Bantuan</dt><dd class="mt-1 font-semibold">Rp {{ number_format((float) $kelompok->jumlah_bantuan, 0, ',', '.') }}</dd></div>
                <div class="rounded-xl bg-white/70 p-3 sm:col-span-2 xl:col-span-4">
                    <dt class="text-xs text-ink-500">Catatan Kebutuhan / Hasil Monev</dt>
                    <dd class="mt-1 whitespace-pre-wrap text-sm leading-6">{{ filled($kelompok->catatan_kebutuhan_hasil_monev) ? $kelompok->catatan_kebutuhan_hasil_monev : 'Belum ada catatan.' }}</dd>
                </div>
            </dl>
        </section>

        @if ($evaluation)
            <section class="ai-print-content glass-card">
                <h2 class="text-xl font-extrabold text-ink-900">{{ $kelompok->nama_kelompok }}</h2>
                <p class="mt-1 text-sm text-ink-500">
                    {{ $kelompok->kawasan?->nama_kawasan ?? 'Kawasan belum diisi' }} ·
                    {{ $kelompok->desa?->nama_desa ?? 'Desa belum diisi' }} ·
                    Tahun monitoring: {{ $kelompok->tahun_monitoring ?? '-' }}
                </p>
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="card-title">Hasil Analisis AI</h2>
                        <p class="mt-1 text-xs text-ink-500">Dibuat {{ $evaluation->evaluated_at?->format('d-m-Y H:i') ?? '-' }}</p>
                    </div>
                    <span @class([
                        'rounded-full px-3 py-1.5 text-xs font-bold uppercase',
                        'bg-rose-100 text-rose-700' => $evaluation->priority === 'tinggi',
                        'bg-amber-100 text-amber-800' => $evaluation->priority === 'sedang',
                        'bg-emerald-100 text-emerald-700' => $evaluation->priority === 'rendah',
                    ])>Prioritas {{ $evaluation->priority }}</span>
                </div>

                @if ($evaluationIsStale)
                    <p class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
                        Data kelompok berubah sejak analisis dibuat. Jalankan analisis ulang untuk memperbarui hasil.
                    </p>
                @endif

                <p class="mt-4 text-sm leading-6 text-ink-700">{{ $evaluation->summary }}</p>
                <div class="mt-5 grid gap-5 lg:grid-cols-2">
                    <div>
                        <h3 class="font-semibold text-ink-900">Alasan prioritas</h3>
                        <p class="mt-2 whitespace-pre-wrap text-sm leading-6 text-ink-600">{{ $evaluation->rationale }}</p>
                    </div>
                    <div>
                        <h3 class="font-semibold text-ink-900">Temuan</h3>
                        <ul class="mt-2 list-disc space-y-2 pl-5 text-sm leading-6 text-ink-600">
                            @foreach ($evaluation->findings as $finding)
                                <li>{{ $finding }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <div class="mt-5">
                    <h3 class="font-semibold text-ink-900">Rekomendasi tindak lanjut</h3>
                    <ol class="mt-2 list-decimal space-y-2 pl-5 text-sm leading-6 text-ink-600">
                        @foreach ($evaluation->recommendations as $recommendation)
                            <li>{{ $recommendation }}</li>
                        @endforeach
                    </ol>
                </div>
            </section>
        @endif
    </div>
</x-app-layout>
