<div>
    <header class="panel-head">
        <x-icon-badge icon="user-pen" tone="teal" class="size-12 text-lg" />
        <div>
            <h2 class="text-lg font-bold text-ink-900">Informasi profil</h2>
            <p class="mt-1 text-sm text-ink-500">Perbarui nama dan alamat email akun Anda.</p>
        </div>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" x-data="{ saving: false }" @submit="saving = true" class="space-y-5">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" value="Nama lengkap" />
            <div class="relative mt-1.5">
                <i class="fa-solid fa-user pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-ink-400" aria-hidden="true"></i>
                <x-text-input id="name" name="name" type="text" class="block w-full pl-10" :value="old('name', $user->name)" required autocomplete="name" />
            </div>
            <x-input-error class="mt-1.5" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" value="Email" />
            <div class="relative mt-1.5">
                <i class="fa-solid fa-envelope pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-ink-400" aria-hidden="true"></i>
                <x-text-input id="email" name="email" type="email" class="block w-full pl-10" :value="old('email', $user->email)" required autocomplete="username" />
            </div>
            <x-input-error class="mt-1.5" :messages="$errors->get('email')" />
        </div>

        @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
            <div class="alert-warn">
                <i class="fa-solid fa-triangle-exclamation mt-0.5" aria-hidden="true"></i>
                <div>
                    <p class="font-semibold">Email belum diverifikasi</p>
                    <p class="mt-0.5">
                        Verifikasi email agar akun Anda aman.
                        <button form="send-verification" class="font-semibold underline underline-offset-2 hover:text-amber-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500">Kirim ulang tautan verifikasi</button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-semibold text-emerald-700">Tautan verifikasi baru telah dikirim ke email Anda.</p>
                    @endif
                </div>
            </div>
        @endif

        <div class="flex flex-wrap items-center gap-4 pt-1">
            <x-primary-button class="btn-glow" x-bind:disabled="saving">
                <i class="fa-solid fa-spinner animate-spin" x-show="saving" x-cloak aria-hidden="true"></i>
                Simpan perubahan
            </x-primary-button>

            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3500)" x-transition.opacity class="alert-ok" role="status">
                    <i class="fa-solid fa-circle-check" aria-hidden="true"></i> Perubahan tersimpan.
                </p>
            @endif
        </div>
    </form>
</div>
