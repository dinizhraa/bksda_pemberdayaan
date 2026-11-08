<footer class="relative overflow-hidden px-3 pb-6 pt-8">
    <div class="land-card mx-auto max-w-6xl p-8">
        <div class="grid gap-8 md:grid-cols-[1.4fr_1fr_1.2fr]">
            <div>
                <x-brand-mark />
                <p class="mt-4 max-w-sm text-sm text-ink-500">
                    Aplikasi penyuluh pemberdayaan masyarakat di sekitar kawasan konservasi BKSDA Sulawesi Tengah.
                </p>
            </div>

            <nav aria-label="Tautan cepat">
                <h2 class="text-sm font-bold text-ink-900">Tautan cepat</h2>
                <ul class="mt-3 space-y-2">
                    @foreach ($menu as [$id, $label])
                        <li><a href="#{{ $id }}" class="footer-link">{{ $label }}</a></li>
                    @endforeach
                    @auth
                        <li><a href="{{ route('dashboard') }}" class="footer-link">Dashboard</a></li>
                    @endauth
                </ul>
            </nav>

            <div>
                <h2 class="text-sm font-bold text-ink-900">Kontak resmi</h2>
                <ul class="mt-3 space-y-2 text-sm text-ink-500">
                    <li class="flex gap-2.5"><i class="fa-solid fa-location-dot mt-1 w-4 text-brand-600" aria-hidden="true"></i>Jl. Prof. Moh. Yamin No. 19, Palu, Sulawesi Tengah</li>
                    <li class="flex gap-2.5"><i class="fa-solid fa-phone mt-1 w-4 text-brand-600" aria-hidden="true"></i><a href="tel:+6281145211717" class="footer-link">0811-4521-1717</a></li>
                    <li class="flex gap-2.5"><i class="fa-solid fa-globe mt-1 w-4 text-brand-600" aria-hidden="true"></i><a href="https://ksdasulteng.com" target="_blank" rel="noopener noreferrer" class="footer-link">ksdasulteng.com</a></li>
                </ul>
            </div>
        </div>


        <div class="mt-4 flex flex-col items-center justify-between gap-2 border-t border-slate-200/70 pt-5 text-xs text-ink-500 sm:flex-row">
            <p>&copy; {{ date('Y') }} Pemberdayaan BKSDA, Balai KSDA Sulawesi Tengah. Hak cipta dilindungi.</p>
            <a href="#hero" class="footer-link">Kembali ke atas <i class="fa-solid fa-arrow-up ml-1" aria-hidden="true"></i></a>
        </div>
    </div>
</footer>
