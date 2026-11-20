<div>
    <header class="panel-head">
        <x-icon-badge icon="shield-halved" tone="blue" class="size-12 text-lg" />
        <div>
            <h2 class="text-lg font-bold text-ink-900">Ubah password</h2>
            <p class="mt-1 text-sm text-ink-500">Gunakan password yang panjang dan acak agar akun tetap aman.</p>
        </div>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="space-y-5"
          x-data="{
              pw: '',
              saving: false,
              get checks() {
                  return [
                      { ok: this.pw.length >= 8, t: 'Minimal 8 karakter' },
                      { ok: /[a-z]/.test(this.pw) && /[A-Z]/.test(this.pw), t: 'Huruf besar dan kecil' },
                      { ok: /\d/.test(this.pw), t: 'Mengandung angka' },
                      { ok: /[^A-Za-z0-9]/.test(this.pw), t: 'Mengandung simbol' },
                  ];
              },
              get score() { return this.checks.filter((c) => c.ok).length },
              get bar() { return ['', 'bg-rose-400', 'bg-amber-400', 'bg-sky-400', 'bg-emerald-500'][this.score] },
          }"
          @submit="saving = true">
        @csrf
        @method('put')

        <x-password-field id="update_password_current_password" name="current_password" label="Password saat ini"
            autocomplete="current-password" :messages="$errors->updatePassword->get('current_password')" />

        <div class="space-y-3">
            <x-password-field id="update_password_password" name="password" label="Password baru"
                autocomplete="new-password" x-model="pw" :messages="$errors->updatePassword->get('password')" />

            <div x-cloak x-show="pw.length" x-transition class="space-y-2.5">
                <div class="grid grid-cols-4 gap-1.5" aria-hidden="true">
                    <template x-for="i in 4" :key="i">
                        <span class="h-1.5 rounded-full transition-colors duration-300" :class="i <= score ? bar : 'bg-slate-200'"></span>
                    </template>
                </div>
                <ul class="grid gap-1 text-xs sm:grid-cols-2">
                    <template x-for="c in checks" :key="c.t">
                        <li class="flex items-center gap-2 transition-colors duration-200" :class="c.ok ? 'text-emerald-600' : 'text-ink-400'">
                            <i class="fa-solid" :class="c.ok ? 'fa-circle-check' : 'fa-circle'" aria-hidden="true"></i>
                            <span x-text="c.t"></span>
                        </li>
                    </template>
                </ul>
            </div>
        </div>

        <x-password-field id="update_password_password_confirmation" name="password_confirmation" label="Konfirmasi password baru"
            autocomplete="new-password" :messages="$errors->updatePassword->get('password_confirmation')" />

        <div class="flex flex-wrap items-center gap-4 pt-1">
            <x-primary-button class="btn-glow" x-bind:disabled="saving">
                <i class="fa-solid fa-spinner animate-spin" x-show="saving" x-cloak aria-hidden="true"></i>
                Perbarui password
            </x-primary-button>

            @if (session('status') === 'password-updated')
                <p x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3500)" x-transition.opacity class="alert-ok" role="status">
                    <i class="fa-solid fa-circle-check" aria-hidden="true"></i> Password berhasil diperbarui.
                </p>
            @endif
        </div>
    </form>
</div>
