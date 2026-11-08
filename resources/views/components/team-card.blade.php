@props(['name', 'role', 'github' => null, 'delay' => 0])

@php
    $initials = collect(preg_split('/\s+/', trim($name)))->filter()->take(2)
        ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))->implode('');
@endphp

<article {{ $attributes->class(['team-card spotlight glow-border reveal reveal-zoom group']) }} data-tilt-card style="--d: {{ $delay }}ms">
    <span class="team-avatar">{{ $initials }}</span>
    <div>
        <h3 class="text-lg font-bold text-ink-900">{{ $name }}</h3>
        <p class="mt-0.5 text-sm text-ink-500">{{ $role }}</p>
    </div>
    @if ($github)
        <a href="https://github.com/{{ $github }}" target="_blank" rel="noopener noreferrer" class="tag transition-colors hover:border-brand-300 hover:text-brand-700">
            <i class="fa-solid fa-link" aria-hidden="true"></i> github.com/{{ $github }}
        </a>
    @endif
</article>
