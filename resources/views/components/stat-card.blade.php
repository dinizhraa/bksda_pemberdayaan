@props(['icon', 'label', 'tone' => 'teal', 'note' => null, 'chip' => null])

<article {{ $attributes->merge(['class' => 'glass-card flex flex-col gap-3 p-4']) }}>
    <div class="flex items-center gap-3">
        <x-icon-badge :icon="$icon" :tone="$tone" class="size-11 text-base" />
        <div class="min-w-0">
            <p class="truncate text-xs font-medium text-ink-500">{{ $label }}</p>
            <p class="truncate text-xl font-bold tracking-tight">{{ $slot }}</p>
        </div>
    </div>

    <p class="flex flex-wrap items-center gap-1.5 text-xs text-ink-500">
        @if ($chip)
            <span class="chip chip-up"><i class="fa-solid fa-arrow-up text-[9px]" aria-hidden="true"></i>{{ $chip }}</span>
        @endif
        {{ $note }}
    </p>
</article>
