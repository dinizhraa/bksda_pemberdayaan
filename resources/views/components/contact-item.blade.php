@props(['icon', 'label', 'href' => null, 'delay' => 0])

@php $tag = $href ? 'a' : 'div'; @endphp

<{{ $tag }} @if ($href) href="{{ $href }}" @if (str_starts_with($href, 'http')) target="_blank" rel="noopener noreferrer" @endif @endif
    {{ $attributes->class(['contact-item spotlight reveal group']) }} style="--d: {{ $delay }}ms">
    <span class="feature-icon shrink-0"><i class="fa-solid fa-{{ $icon }}" aria-hidden="true"></i></span>
    <span class="min-w-0">
        <span class="block text-xs font-medium text-ink-500">{{ $label }}</span>
        <span class="mt-0.5 block text-sm font-semibold text-ink-900">{{ $slot }}</span>
    </span>
</{{ $tag }}>
