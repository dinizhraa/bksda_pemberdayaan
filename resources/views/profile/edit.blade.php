@php
    $user = $user ?? Auth::user();
    // Status verifikasi hanya ditampilkan bila fitur verifikasi email aktif pada model User.
    $pakaiVerifikasi = $user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail;
    $verified = $pakaiVerifikasi && $user->hasVerifiedEmail();

    $initials = collect(preg_split('/\s+/', trim($user->name)))->filter()->take(2)
        ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))->implode('');

    // Tab yang bermasalah (galat validasi) atau baru berhasil diubah dibuka otomatis.
    $tabAwal = match (true) {
        $errors->updatePassword->isNotEmpty(), session('status') === 'password-updated' => 'keamanan',
        $errors->userDeletion->isNotEmpty() => 'bahaya',
        default => 'info',
    };

    // [id, ikon, label, kelas warna saat aktif]
    $tabs = [
        ['info', 'user-pen', 'Informasi', 'text-brand-700'],
        ['keamanan', 'shield-halved', 'Keamanan', 'text-brand-700'],
        ['bahaya', 'triangle-exclamation', 'Hapus Akun', 'text-rose-600'],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <h1 class="page-title">Profil</h1>
        <p class="page-sub">Kelola informasi akun, keamanan, dan penghapusan akun Anda.</p>
    </x-slot>

    <div x-data="{ tab: @js($tabAwal) }" class="grid items-start gap-6 lg:grid-cols-[20rem_1fr]">
        <!-- Kartu profil -->
        <aside class="glass-card animate-rise overflow-hidden p-0 lg:sticky lg:top-6">
            <div class="profile-cover" aria-hidden="true">
                <span class="absolute -right-8 -top-10 size-40 animate-drift rounded-full bg-white/25"></span>
                <span class="absolute -bottom-12 left-6 size-32 animate-drift rounded-full bg-white/20 [animation-delay:-7s]"></span>
            </div>

            <div class="px-6 pb-6">
                <div class="-mt-12 flex justify-center">
                    <div class="profile-avatar animate-float-sm" aria-hidden="true">
                        {{ $initials }}
                        @if ($verified)
                            <span class="absolute -bottom-0.5 -right-0.5 grid size-7 place-items-center rounded-full border-2 border-white bg-emerald-500 text-xs text-white"><i class="fa-solid fa-check"></i></span>
                        @endif
                    </div>
                </div>

                <div class="mt-4 text-center">
                    <h2 class="truncate text-xl font-extrabold tracking-tight text-ink-900">{{ $user->name }}</h2>
                    <p class="mt-0.5 truncate text-sm text-ink-500">{{ $user->email }}</p>

                    @if (! $pakaiVerifikasi)
                        <span class="chip chip-up mt-3 px-3 py-1"><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Akun aktif</span>
                    @elseif ($verified)
                        <span class="chip chip-up mt-3 px-3 py-1"><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Email terverifikasi</span>
                    @else
                        <span class="chip mt-3 bg-amber-50 px-3 py-1 text-amber-700"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i> Email belum diverifikasi</span>
                    @endif
                </div>

                <div class="mt-5 space-y-1 border-t border-slate-200/60 pt-4">
                    <div class="info-row">
                        <x-icon-badge icon="calendar-check" tone="teal" class="size-9 text-sm" />
                        <div class="min-w-0">
                            <p class="text-xs text-ink-500">Bergabung sejak</p>
                            <p class="truncate text-sm font-semibold">{{ $user->created_at?->locale('id')->translatedFormat('d F Y') ?? '-' }}</p>
                        </div>
                    </div>
                    <div class="info-row">
                        <x-icon-badge icon="clock-rotate-left" tone="blue" class="size-9 text-sm" />
                        <div class="min-w-0">
                            <p class="text-xs text-ink-500">Terakhir diperbarui</p>
                            <p class="truncate text-sm font-semibold">{{ $user->updated_at?->locale('id')->diffForHumans() ?? '-' }}</p>
                        </div>
                    </div>
                </div>

                <div class="mt-5 grid gap-2">
                    <a href="{{ route('dashboard') }}" class="btn-brand btn-glow"><i class="fa-solid fa-table-cells-large" aria-hidden="true"></i> Ke Dashboard</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn-ghost w-full"><i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i> Keluar</button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Panel pengaturan -->
        <div class="min-w-0 animate-rise space-y-4" style="animation-delay: 120ms">
            <div class="glass rounded-2xl p-1.5" role="tablist" aria-label="Pengaturan akun">
                <div class="relative grid grid-cols-3">
                    <span class="absolute inset-y-0 left-0 w-1/3 rounded-xl bg-white shadow-sm transition-transform duration-300 ease-out"
                          :style="{ transform: 'translateX(' + ({ info: 0, keamanan: 100, bahaya: 200 }[tab]) + '%)' }" aria-hidden="true"></span>

                    @foreach ($tabs as [$id, $icon, $label, $aktif])
                        <button type="button" role="tab" @click="tab = '{{ $id }}'" :aria-selected="tab === '{{ $id }}'"
                                class="relative z-10 flex items-center justify-center gap-2 rounded-xl py-2.5 text-sm font-semibold transition-colors duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
                                :class="tab === '{{ $id }}' ? '{{ $aktif }}' : 'text-ink-500 hover:text-ink-900'">
                            <i class="fa-solid fa-{{ $icon }}" aria-hidden="true"></i>
                            <span class="hidden sm:inline">{{ $label }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            <section x-cloak x-show="tab === 'info'" role="tabpanel" x-transition:enter="transition duration-300 ease-out"
                     x-transition:enter-start="translate-y-3 opacity-0" x-transition:enter-end="translate-y-0 opacity-100" class="glass-card sm:p-8">
                @include('profile.partials.update-profile-information-form')
            </section>

            <section x-cloak x-show="tab === 'keamanan'" role="tabpanel" x-transition:enter="transition duration-300 ease-out"
                     x-transition:enter-start="translate-y-3 opacity-0" x-transition:enter-end="translate-y-0 opacity-100" class="glass-card sm:p-8">
                @include('profile.partials.update-password-form')
            </section>

            <section x-cloak x-show="tab === 'bahaya'" role="tabpanel" x-transition:enter="transition duration-300 ease-out"
                     x-transition:enter-start="translate-y-3 opacity-0" x-transition:enter-end="translate-y-0 opacity-100" class="glass-card sm:p-8">
                @include('profile.partials.delete-user-form')
            </section>
        </div>
    </div>

    {{-- Modal berada di luar kartu kaca agar posisinya tidak terpengaruh efek blur. --}}
    @include('profile.partials.delete-user-modal')
</x-app-layout>
