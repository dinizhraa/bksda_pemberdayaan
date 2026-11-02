@props(['icon' => null, 'tone' => 'teal'])

@php
    // Nama kelas ditulis lengkap agar terdeteksi Tailwind saat build.
    $tones = [
        'teal' => 'tone-teal',
        'green' => 'tone-green',
        'purple' => 'tone-purple',
        'blue' => 'tone-blue',
        'amber' => 'tone-amber',
    ];
@endphp

<span {{ $attributes->class(['icon-badge', $tones[$tone] ?? 'tone-teal']) }}>
    @if ($icon)
        <i class="fa-solid fa-{{ $icon }}" aria-hidden="true"></i>
    @endif
    {{ $slot }}
</span>
