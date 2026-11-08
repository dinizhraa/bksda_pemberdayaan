@php
    // Galat & isian lama hanya ditampilkan di form yang barusan dikirim.
    $loginErr = old('_form') === 'login' ? $errors : null;
    $registerErr = old('_form') === 'register' ? $errors : null;
    $loginEmail = old('_form') === 'login' ? old('email') : '';
    $registerOld = old('_form') === 'register' ? old() : [];
    $canRegister = Route::has('register');
@endphp

<div x-cloak x-show="modal" x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="opacity-0"
     x-transition:leave="transition duration-200 ease-in" x-transition:leave-end="opacity-0"
     x-effect="if (modal) $nextTick(() => $refs[modal + 'First']?.focus())"
     class="fixed inset-0 z-[60] grid place-items-center overflow-y-auto p-4"
     role="dialog" aria-modal="true" aria-labelledby="auth-title">

    <div class="fixed inset-0 bg-ink-900/40 backdrop-blur-sm" @click="modal = null"></div>

    <div x-show="modal" x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="translate-y-6 scale-95 opacity-0"
         x-transition:enter-end="translate-y-0 scale-100 opacity-100" x-transition:leave="transition duration-200 ease-in"
         x-transition:leave-start="translate-y-0 scale-100 opacity-100" x-transition:leave-end="translate-y-4 scale-95 opacity-0"
         class="modal-panel">

        <button type="button" @click="modal = null" aria-label="Tutup"
                class="absolute right-4 top-4 grid size-9 place-items-center rounded-full text-ink-500 transition-all duration-300 hover:rotate-90 hover:bg-white hover:text-ink-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
        </button>

        <x-brand-mark />

        <h2 id="auth-title" class="mt-6 text-2xl font-extrabold tracking-tight text-ink-900" x-text="modal === 'register' ? 'Buat akun baru' : 'Selamat datang kembali'"></h2>
        <p class="mt-1 text-sm text-ink-500" x-text="modal === 'register' ? 'Daftar untuk mulai memantau pemberdayaan.' : 'Masuk untuk membuka dashboard.'"></p>

        @if ($canRegister)
            <div class="tab-switch mt-5" role="tablist">
                <button type="button" role="tab" @click="modal = 'login'" :aria-selected="modal === 'login'" class="tab-btn" :class="{ 'tab-btn-active': modal === 'login' }">Masuk</button>
                <button type="button" role="tab" @click="modal = 'register'" :aria-selected="modal === 'register'" class="tab-btn" :class="{ 'tab-btn-active': modal === 'register' }">Daftar</button>
            </div>
        @endif

        <!-- Form masuk -->
        <form method="POST" action="{{ route('login') }}" x-show="modal === 'login'" x-transition:enter="transition duration-300 ease-out"
              x-transition:enter-start="translate-y-2 opacity-0" x-transition:enter-end="translate-y-0 opacity-100" class="mt-5 space-y-4">
            @csrf
            <input type="hidden" name="_form" value="login">

            <div>
                <x-input-label for="login_email" value="Email" />
                <x-text-input id="login_email" name="email" type="email" class="mt-1.5 block w-full" :value="$loginEmail" required autocomplete="username" x-ref="loginFirst" />
                <x-input-error :messages="$loginErr?->get('email')" class="mt-1.5" />
            </div>

            <div>
                <x-input-label for="login_password" value="Password" />
                <x-text-input id="login_password" name="password" type="password" class="mt-1.5 block w-full" required autocomplete="current-password" />
                <x-input-error :messages="$loginErr?->get('password')" class="mt-1.5" />
            </div>

            <div class="flex items-center justify-between gap-3">
                <label for="login_remember" class="inline-flex items-center gap-2 text-sm text-ink-700">
                    <input id="login_remember" type="checkbox" name="remember" class="rounded border-slate-300 text-brand-600 shadow-sm focus:ring-brand-500">
                    Ingat saya
                </label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-sm font-medium text-brand-700 hover:underline">Lupa password?</a>
                @endif
            </div>

            <x-primary-button class="btn-glow w-full py-3">Masuk</x-primary-button>
        </form>

        <!-- Form daftar -->
        @if ($canRegister)
            <form method="POST" action="{{ route('register') }}" x-show="modal === 'register'" x-transition:enter="transition duration-300 ease-out"
                  x-transition:enter-start="translate-y-2 opacity-0" x-transition:enter-end="translate-y-0 opacity-100" class="mt-5 space-y-4">
                @csrf
                <input type="hidden" name="_form" value="register">

                <div>
                    <x-input-label for="register_name" value="Nama lengkap" />
                    <x-text-input id="register_name" name="name" type="text" class="mt-1.5 block w-full" :value="$registerOld['name'] ?? ''" required autocomplete="name" x-ref="registerFirst" />
                    <x-input-error :messages="$registerErr?->get('name')" class="mt-1.5" />
                </div>

                <div>
                    <x-input-label for="register_email" value="Email" />
                    <x-text-input id="register_email" name="email" type="email" class="mt-1.5 block w-full" :value="$registerOld['email'] ?? ''" required autocomplete="username" />
                    <x-input-error :messages="$registerErr?->get('email')" class="mt-1.5" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="register_password" value="Password" />
                        <x-text-input id="register_password" name="password" type="password" class="mt-1.5 block w-full" required autocomplete="new-password" />
                        <x-input-error :messages="$registerErr?->get('password')" class="mt-1.5" />
                    </div>
                    <div>
                        <x-input-label for="register_password_confirmation" value="Ulangi password" />
                        <x-text-input id="register_password_confirmation" name="password_confirmation" type="password" class="mt-1.5 block w-full" required autocomplete="new-password" />
                    </div>
                </div>

                <x-primary-button class="btn-glow w-full py-3">Buat Akun</x-primary-button>
            </form>
        @endif
    </div>
</div>
