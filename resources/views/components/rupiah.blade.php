@props(['value' => 0, 'full' => false])

@php
    $n = (float) $value;

    // Nilai di database disimpan dalam rupiah penuh.
    $text = match (true) {
        $full => 'Rp '.number_format($n, 0, ',', '.'),
        abs($n) >= 1e9 => 'Rp '.number_format($n / 1e9, 2, ',', '.').' M',
        abs($n) >= 1e6 => 'Rp '.number_format($n / 1e6, 1, ',', '.').' Jt',
        default => 'Rp '.number_format($n, 0, ',', '.'),
    };
@endphp
{{ $text }}
