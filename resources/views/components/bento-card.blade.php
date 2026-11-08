@props(['icon', 'title', 'delay' => 0])

<article {{ $attributes->class(['bento spotlight glow-border reveal group']) }} data-tilt-card style="--d: {{ $delay }}ms">
    <div class="flex items-start gap-4">
        <span class="feature-icon shrink-0"><i class="fa-solid fa-{{ $icon }}" aria-hidden="true"></i></span>
        <div>
            <h3 class="text-lg font-bold text-ink-900">{{ $title }}</h3>
            <p class="mt-1.5 text-sm leading-relaxed text-ink-500">{{ $slot }}</p>
        </div>
    </div>

    @isset($visual)
        <div class="mt-6 flex flex-1 items-end" aria-hidden="true">{{ $visual }}</div>
    @endisset
</article>
