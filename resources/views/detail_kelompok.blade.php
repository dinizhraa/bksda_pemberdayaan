<x-app-layout>
    <x-slot name="header">
        <h1 class="page-title">Detail Kelompok Binaan</h1>
        <p class="page-sub">Informasi lengkap kelompok dan lokasi tersimpan.</p>
    </x-slot>

    <x-glass-card class="mx-auto w-full max-w-4xl">
        <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-700">ID KLP-00{{ $kelompok->id }}</p>
                <h2 class="mt-1 text-2xl font-bold text-ink-900">{{ $kelompok->nama_kelompok }}</h2>
                <p class="mt-1 text-sm text-ink-500">{{ $kelompok->kawasan?->nama_kawasan ?? '-' }}</p>
            </div>
            @if (Auth::check() && Auth::user()->isAdmin())
                <a href="{{ route('kelompok.edit', $kelompok->id) }}" class="btn-brand">
                    <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                    Edit Data
                </a>
            @endif
        </div>

        <dl class="grid gap-px overflow-hidden rounded-2xl border border-slate-200 bg-slate-200 sm:grid-cols-2">
            @foreach ([
                'Tahun Pembinaan' => $kelompok->tahun_pembinaan ?? '-',
                'Status Kelompok' => $kelompok->status_kelompok ?? '-',
                'Tahun Pembentukan' => $kelompok->tahun_pembentukan ?? '-',
                'Tahun Monitoring' => $kelompok->tahun_monitoring ?? '-',
                'Kawasan Konservasi' => $kelompok->kawasan?->nama_kawasan ?? '-',
                'Desa' => $kelompok->desa?->nama_desa ?? '-',
                'Kecamatan' => $kelompok->kecamatan ?? $kelompok->desa?->kecamatan ?? '-',
                'Kabupaten' => $kelompok->kabupaten ?? $kelompok->desa?->kabupaten ?? '-',
                'Nama Ketua' => $kelompok->nama_ketua ?? '-',
                'Nomor HP / Kontak' => $kelompok->no_hp ?? '-',
                'Jenis Usaha' => $kelompok->jenis_usaha ?? '-',
                'SK Kelompok' => $kelompok->sk_kelompok ?? '-',
                'Jumlah Anggota Laki-laki' => $kelompok->jumlah_anggota_laki_laki ?? '-',
                'Jumlah Anggota Perempuan' => $kelompok->jumlah_anggota_perempuan ?? '-',
                'Total Anggota' => $kelompok->jumlah_anggota ?? 0,
                'Jumlah Bantuan' => 'Rp '.number_format((float) $kelompok->jumlah_bantuan, 0, ',', '.'),
                'Latitude' => $kelompok->latitude !== null ? number_format((float) $kelompok->latitude, 7, '.', '') : '-',
                'Longitude' => $kelompok->longitude !== null ? number_format((float) $kelompok->longitude, 7, '.', '') : '-',
                'Catatan Kebutuhan / Hasil Monev' => $kelompok->catatan_kebutuhan_hasil_monev ?? '-',
            ] as $label => $value)
                <div @class(['bg-white px-4 py-3', 'sm:col-span-2' => $label === 'Catatan Kebutuhan / Hasil Monev'])>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-ink-500">{{ $label }}</dt>
                    <dd class="mt-1 whitespace-pre-line break-words font-semibold text-ink-800">{{ $value }}</dd>
                </div>
            @endforeach
        </dl>

        <div class="mt-6 flex justify-between">
            <a href="{{ route('kelompok.index') }}" class="btn-ghost">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                Kembali
            </a>
            @if ($kelompok->latitude !== null && $kelompok->longitude !== null)
                <a href="{{ route('kelompok.map') }}" class="btn-brand">
                    <i class="fa-solid fa-map-location-dot" aria-hidden="true"></i>
                    Lihat di Peta
                </a>
            @endif
        </div>
    </x-glass-card>
</x-app-layout>
