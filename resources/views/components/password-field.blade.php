@props(['id', 'name', 'label', 'autocomplete' => 'current-password', 'messages' => null])

<div x-data="{ show: false }">
    <x-input-label :for="$id" :value="$label" />

    <div class="relative mt-1.5">
        <x-text-input :id="$id" :name="$name" type="password" x-bind:type="show ? 'text' : 'password'" :autocomplete="$autocomplete"
            {{ $attributes->merge(['class' => 'block w-full pl-10 pr-11']) }} />

        <i class="fa-solid fa-lock pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-ink-400" aria-hidden="true"></i>

        <button type="button" @click="show = ! show" :aria-label="show ? 'Sembunyikan password' : 'Tampilkan password'"
                class="absolute right-2 top-1/2 grid size-8 -translate-y-1/2 place-items-center rounded-lg text-ink-400 transition-colors hover:bg-slate-100 hover:text-ink-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
            <i class="fa-solid" :class="show ? 'fa-eye-slash' : 'fa-eye'" aria-hidden="true"></i>
        </button>
    </div>

    <x-input-error :messages="$messages" class="mt-1.5" />
</div>
