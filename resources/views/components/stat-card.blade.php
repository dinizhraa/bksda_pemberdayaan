@props(['icon', 'label', 'tone' => 'teal', 'note' => null, 'chip' => null])

<article {{ $attributes->merge(['class' => 'glass-card flex h-full min-h-[9rem] flex-col justify-between gap-4 p-4']) }}>
    <div class="flex items-start gap-3">
        <x-icon-badge :icon="$icon" :tone="$tone" class="size-11 text-base" />
        <div class="min-w-0 flex-1">
            <p class="min-h-8 text-xs font-medium leading-4 text-ink-500">{{ $label }}</p>
            <p class="break-words text-xl font-bold leading-tight tracking-tight">{{ $slot }}</p>
        </div>
    </div>

    <p class="mt-auto flex min-h-5 flex-wrap items-center gap-1.5 text-xs text-ink-500">
        @if ($chip)
            <span class="chip chip-up"><i class="fa-solid fa-arrow-up text-[9px]" aria-hidden="true"></i>{{ $chip }}</span>
        @endif
        {{ $note }}
    </p>
</article>
