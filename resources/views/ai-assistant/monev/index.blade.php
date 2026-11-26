<x-app-layout>
    <x-slot name="header">
        <h1 class="page-title">Monitoring &amp; Evaluasi</h1>
        <p class="page-sub">Tinjau kelengkapan data dan hasil analisis per kelompok. AI tidak menyusun temuan jika data Monev belum ada.</p>
    </x-slot>

    <div class="flex flex-col gap-4">
        <section class="grid gap-3 sm:grid-cols-3">
            <article class="glass-card p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-ink-500">Total Kelompok</p>
                <p class="mt-2 text-3xl font-extrabold text-brand-800">{{ number_format($groups->total()) }}</p>
            </article>
            <article class="glass-card p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-ink-500">Belum Dimonitoring</p>
                <p class="mt-2 text-3xl font-extrabold text-amber-700">{{ number_format($unmonitoredCount) }}</p>
            </article>
            <article class="glass-card p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-ink-500">Tanpa Catatan Monev</p>
                <p class="mt-2 text-3xl font-extrabold text-ink-700">{{ number_format($withoutMonevNotesCount) }}</p>
            </article>
        </section>

        <section class="glass-card overflow-hidden p-0">
            <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                <div>
                    <h2 class="card-title">Daftar hasil monitoring</h2>
                    <p class="mt-1 text-xs text-ink-500">Pilih kelompok untuk melihat data dan menjalankan analisis berbasis catatan yang tersedia.</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="glass-table min-w-[760px]">
                    <thead>
                        <tr>
                            <th>Nama Kelompok</th>
                            <th>Kawasan</th>
                            <th>Desa</th>
                            <th>Status</th>
                            <th>Tahun Monitoring</th>
                            <th>Data Monev</th>
                            <th class="text-right">Hasil</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($groups as $kelompok)
                            <tr>
                                <td class="font-semibold">{{ $kelompok->nama_kelompok }}</td>
                                <td>{{ $kelompok->kawasan?->nama_kawasan ?? '-' }}</td>
                                <td>{{ $kelompok->desa?->nama_desa ?? '-' }}</td>
                                <td>{{ $kelompok->status_kelompok ?? '-' }}</td>
                                <td>{{ $kelompok->tahun_monitoring ?? '-' }}</td>
                                <td>
                                    @if (filled($kelompok->catatan_kebutuhan_hasil_monev))
                                        <span class="chip chip-up">Tersedia</span>
                                    @else
                                        <span class="chip chip-neutral">Belum ada catatan</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('ai.monev.show', $kelompok) }}" class="btn-ghost px-3 py-2 text-xs">
                                        Lihat detail
                                        <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="py-8 text-center text-ink-500">Belum ada kelompok binaan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($groups->hasPages())
                <div class="border-t border-ink-100 px-5 py-4">{{ $groups->links() }}</div>
            @endif
        </section>
    </div>
</x-app-layout>
