@php
    // Sumber: situs dan akun resmi Balai KSDA Sulawesi Tengah. Periksa kembali sebelum rilis.
    $kontak = [
        ['location-dot', 'Alamat', 'Jl. Prof. Moh. Yamin No. 19, Birobuli Utara, Palu Selatan, Kota Palu, Sulawesi Tengah', 'https://www.google.com/maps/search/?api=1&query=Balai+KSDA+Sulawesi+Tengah+Palu'],
        ['phone', 'Call center', '0811-4521-1717', 'tel:+6281145211717'],
        ['clock', 'Jam layanan', 'Senin sampai Kamis 07.30 sampai 16.00, Jumat 07.30 sampai 16.30', null],
        ['globe', 'Situs resmi', 'ksdasulteng.com', 'https://ksdasulteng.com'],
    ];
@endphp

<x-landing-section id="kontak" title="Hubungi kami" lead="Balai Konservasi Sumber Daya Alam Sulawesi Tengah.">
    <div class="grid gap-4 sm:grid-cols-2">
        @foreach ($kontak as $i => [$icon, $label, $value, $href])
            <x-contact-item :icon="$icon" :label="$label" :href="$href" :delay="($i % 2) * 100">{{ $value }}</x-contact-item>
        @endforeach
    </div>

    <!-- Ajakan bertindak -->
    <div class="cta-band reveal mt-16 text-center" data-pause-offscreen>
        <div class="pointer-events-none absolute inset-0 -z-10" aria-hidden="true">
            <span class="absolute -left-20 -top-24 size-80 animate-drift rounded-full bg-emerald-300/30 blur-3xl"></span>
            <span class="absolute -bottom-24 -right-16 size-96 animate-drift rounded-full bg-sky-300/30 blur-3xl [animation-delay:-8s]"></span>
        </div>

        <h2 class="mx-auto max-w-2xl text-3xl font-extrabold tracking-tight sm:text-4xl">Siap mendampingi kelompok binaan dengan lebih terarah?</h2>
        <p class="mx-auto mt-4 max-w-xl text-brand-100">Masuk untuk membuka dashboard, atau buat akun untuk mulai mencatat.</p>

        <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
            @auth
                <a href="{{ route('dashboard') }}" data-magnetic class="btn-white">Buka Dashboard</a>
            @else
                @if (Route::has('register'))
                    <button type="button" data-magnetic @click="modal = 'register'" class="btn-white">Buat Akun</button>
                @endif
                <button type="button" data-magnetic @click="modal = 'login'" class="inline-flex items-center justify-center rounded-xl border border-white/40 px-6 py-3.5 text-base font-semibold text-white transition-all duration-300 hover:bg-white/15 focus:outline-none focus-visible:ring-2 focus-visible:ring-white">Masuk</button>
            @endauth
        </div>
    </div>
</x-landing-section>
