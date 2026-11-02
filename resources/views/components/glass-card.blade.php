@props(['title' => null])

<section {{ $attributes->merge(['class' => 'glass-card']) }}>
    @if ($title || isset($action))
        <header class="mb-4 flex items-center justify-between gap-3">
            <h2 class="card-title">{{ $title }}</h2>
            {{ $action ?? '' }}
        </header>
    @endif

    {{ $slot }}
</section>
