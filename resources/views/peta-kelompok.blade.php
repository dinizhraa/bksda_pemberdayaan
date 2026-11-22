<x-app-layout>
    <x-slot name="header">
        <h1 class="page-title">Peta Kawasan</h1>
        <p class="page-sub">Lokasi kelompok binaan yang tersimpan beserta informasi ringkasnya.</p>
    </x-slot>

    <x-glass-card class="flex flex-col gap-4">
        <div id="group-map" class="h-[65vh] min-h-[420px] w-full overflow-hidden rounded-2xl border border-slate-200/80"
             data-locations="{{ json_encode($locations) }}"
             role="region" aria-label="Peta titik lokasi kelompok binaan"></div>
        <div class="flex flex-wrap items-center justify-between gap-2">
            <p class="text-xs text-ink-500">{{ $locations->count() }} titik koordinat kelompok tersimpan.</p>
            <a href="{{ route('kelompok.index') }}" class="btn-ghost text-xs">
                <i class="fa-solid fa-list" aria-hidden="true"></i>
                Lihat daftar kelompok
            </a>
        </div>
        <p class="text-xs text-ink-500">Citra: Esri World Imagery; jalur: © OpenStreetMap contributors; detail: Esri.</p>
    </x-glass-card>

    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
              integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    @endpush

    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
                integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    @endpush
</x-app-layout>
