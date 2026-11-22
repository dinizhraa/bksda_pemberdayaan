<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Masuk Admin | Pemberdayaan BKSDA</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-brand-50 font-sans text-slate-900 antialiased">
        <main class="grid min-h-screen lg:grid-cols-[1.05fr_.95fr]">
            <section class="relative isolate flex min-h-[300px] flex-col overflow-hidden bg-gradient-to-br from-brand-900 via-brand-700 to-brand-500 px-6 py-7 text-white sm:min-h-[340px] sm:px-10 sm:py-9 lg:min-h-screen lg:px-14 lg:py-12 xl:px-20">

                <div class="relative z-20 inline-flex w-fit items-center gap-2 text-sm font-semibold tracking-wide text-brand-50">
                    <i class="fa-solid fa-leaf text-brand-200" aria-hidden="true"></i>
                    BKSDA Sulawesi Tengah
                </div>

                <div class="relative z-20 mt-10 max-w-xl sm:mt-12 lg:mt-4 lg:max-w-[360px] 2xl:mt-16">
                    <p class="text-xs font-bold uppercase tracking-[0.22em] text-brand-100">Portal pengelolaan</p>
                    <h1 class="mt-4 text-3xl font-bold leading-tight tracking-tight sm:text-4xl lg:text-[2.4rem] xl:text-5xl">
                        Menjaga alam, menguatkan masyarakat.
                    </h1>
                    <p class="mt-4 max-w-md text-sm leading-6 text-brand-50/80 sm:text-base sm:leading-7">
                        Kelola data kelompok binaan dan dukung pemberdayaan masyarakat di sekitar kawasan konservasi.
                    </p>
                </div>

                <img
                    src="{{ asset('images/bksda-kolaborasi-login.svg') }}"
                    alt="Petugas BKSDA berkolaborasi mengelola peta dan grafik kawasan konservasi pada layar digital"
                    class="pointer-events-none relative z-10 mt-6 ml-auto block w-full max-w-[720px] select-none lg:absolute lg:bottom-12 lg:right-2 lg:ml-0 lg:mt-0 lg:w-[min(84%,680px)] lg:max-w-none"
                    width="960"
                    height="640"
                    loading="eager"
                >

                <div class="relative z-20 mt-auto hidden items-center gap-2 pt-16 text-xs font-medium text-brand-50/70 lg:flex">
                    <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                    Akses khusus administrator
                </div>
            </section>

            <section class="flex items-center justify-center px-5 py-10 sm:px-8 sm:py-12 lg:min-h-screen lg:px-12 xl:px-16">
                <div class="w-full max-w-[440px]">
                    <a href="{{ route('home') }}" class="mb-7 inline-flex rounded-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-4">
                        <x-brand-mark />
                    </a>

                    <div class="glass rounded-[28px] bg-white/80 p-6 shadow-[0_24px_70px_-28px_rgba(12,66,62,0.24)] sm:p-9">
                        <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-white/80 bg-brand-100/60 px-3 py-1.5 text-xs font-semibold text-brand-700 backdrop-blur-md">
                            <i class="fa-solid fa-lock text-[11px] text-brand-600" aria-hidden="true"></i>
                            Area administrator
                        </div>

                        <h2 class="text-2xl font-bold tracking-tight text-brand-900 sm:text-[1.75rem]">Masuk Admin</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-500">
                            Masuk menggunakan akun admin untuk mengelola data pemberdayaan.
                        </p>

                        <x-auth-session-status class="mt-5 rounded-xl bg-brand-50 px-4 py-3 text-sm text-brand-800" :status="session('status')" />

                        <form method="POST" action="{{ route('login') }}" class="mt-7 space-y-5">
                            @csrf

                            <div>
                                <label for="email" class="mb-2 block text-sm font-semibold text-slate-700">Alamat email</label>
                                <div class="relative">
                                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-brand-600">
                                        <i class="fa-solid fa-envelope" aria-hidden="true"></i>
                                    </span>
                                    <input
                                        id="email"
                                        name="email"
                                        type="email"
                                        value="{{ old('email') }}"
                                        autocomplete="username"
                                        required
                                        autofocus
                                        aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
                                        class="w-full rounded-xl border border-white/80 bg-white/70 py-3.5 pl-11 pr-4 text-sm text-slate-800 shadow-sm outline-none backdrop-blur-sm transition placeholder:text-slate-400 focus:border-brand-500 focus:bg-white/95 focus:ring-4 focus:ring-brand-500/10"
                                        placeholder="nama@contoh.go.id"
                                    >
                                </div>
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>

                            <div x-data="{ visible: false }">
                                <label for="password" class="mb-2 block text-sm font-semibold text-slate-700">Kata sandi</label>
                                <div class="relative">
                                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-brand-600">
                                        <i class="fa-solid fa-key" aria-hidden="true"></i>
                                    </span>
                                    <input
                                        id="password"
                                        name="password"
                                        :type="visible ? 'text' : 'password'"
                                        type="password"
                                        autocomplete="current-password"
                                        required
                                        aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}"
                                        class="w-full rounded-xl border border-white/80 bg-white/70 py-3.5 pl-11 pr-12 text-sm text-slate-800 shadow-sm outline-none backdrop-blur-sm transition placeholder:text-slate-400 focus:border-brand-500 focus:bg-white/95 focus:ring-4 focus:ring-brand-500/10"
                                        placeholder="Masukkan kata sandi"
                                    >
                                    <button
                                        type="button"
                                        @click="visible = ! visible"
                                        :aria-label="visible ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                                        :aria-pressed="visible"
                                        class="absolute inset-y-0 right-0 inline-flex items-center rounded-r-xl px-4 text-slate-400 transition hover:text-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand-500"
                                    >
                                        <i class="fa-solid" :class="visible ? 'fa-eye-slash' : 'fa-eye'" aria-hidden="true"></i>
                                    </button>
                                </div>
                                <x-input-error :messages="$errors->get('password')" class="mt-2" />
                            </div>

                            <div class="flex flex-wrap items-center justify-between gap-3 pt-1">
                                <label for="remember_me" class="inline-flex cursor-pointer items-center gap-2.5 text-sm text-slate-600">
                                    <input
                                        id="remember_me"
                                        type="checkbox"
                                        name="remember"
                                        class="size-4 rounded border-slate-300 text-brand-700 shadow-sm focus:ring-brand-500"
                                    >
                                    Ingat saya
                                </label>

                                @if (Route::has('password.request'))
                                    <a
                                        href="{{ route('password.request') }}"
                                        class="text-sm font-semibold text-brand-700 underline-offset-4 transition hover:text-brand-900 hover:underline focus:outline-none focus-visible:rounded focus-visible:ring-2 focus-visible:ring-brand-500"
                                    >
                                        Lupa kata sandi?
                                    </a>
                                @endif
                            </div>

                            <button
                                type="submit"
                                class="group inline-flex w-full items-center justify-center gap-2.5 rounded-xl bg-gradient-to-r from-brand-700 to-brand-500 px-5 py-3.5 text-sm font-bold text-white shadow-lg shadow-brand-900/15 transition duration-200 hover:-translate-y-0.5 hover:from-brand-900 hover:to-brand-700 hover:shadow-xl hover:shadow-brand-900/20 focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-500/25 active:translate-y-0"
                            >
                                <span>Masuk</span>
                                <i class="fa-solid fa-arrow-right text-xs transition-transform group-hover:translate-x-1" aria-hidden="true"></i>
                            </button>
                        </form>
                    </div>

                    <p class="mt-6 text-center text-xs leading-5 text-slate-500">
                        Sistem informasi pemberdayaan masyarakat<br class="sm:hidden">
                        Balai KSDA Sulawesi Tengah
                    </p>
                </div>
            </section>
        </main>
    </body>
</html>
