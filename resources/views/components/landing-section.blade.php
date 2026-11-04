@props(['id', 'title', 'lead' => null])

<section id="{{ $id }}" {{ $attributes->class(['scroll-mt-24 py-16 sm:py-24']) }}>
    <div class="mx-auto max-w-6xl px-4">
        <header class="reveal mx-auto mb-12 max-w-2xl text-center">
            <h2 class="section-title">{{ $title }}</h2>
            @if ($lead)
                <p class="section-lead">{{ $lead }}</p>
            @endif
        </header>

        {{ $slot }}
    </div>
</section>
