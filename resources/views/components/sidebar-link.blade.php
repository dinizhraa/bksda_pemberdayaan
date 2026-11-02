@props(['href', 'icon', 'active' => false])

<a href="{{ $href }}" @if ($active) aria-current="page" @endif {{ $attributes->class(['side-link', 'side-link-active' => $active]) }}>
    <i class="fa-solid fa-{{ $icon }} w-5 text-center" aria-hidden="true"></i>
    <span>{{ $slot }}</span>
</a>
