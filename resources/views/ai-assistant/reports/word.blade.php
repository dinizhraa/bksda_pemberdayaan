<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $report['title'] }}</title>
    <style>
        body { color: #20332e; font-family: Arial, sans-serif; font-size: 11pt; line-height: 1.55; margin: 2cm; }
        h1, h2, h3 { color: #165f56; }
        h1 { font-size: 20pt; margin-bottom: 4pt; }
        h2 { border-bottom: 1px solid #d5e7df; font-size: 14pt; margin-top: 20pt; padding-bottom: 4pt; }
        table { border-collapse: collapse; margin-top: 8pt; width: 100%; }
        th, td { border: 1px solid #cbd9d2; padding: 6pt; text-align: left; }
        th { background: #e8f4ec; }
        .muted { color: #66776f; font-size: 9pt; }
    </style>
</head>
<body>
    <p class="muted">AI ASSISTANT BKSDA · {{ $report['generated_at'] }}</p>
    <h1>{{ $report['title'] }}</h1>

    @if (($report['filter_labels'] ?? []) !== [])
        <h2>Cakupan Laporan</h2>
        <ul>
            @foreach ($report['filter_labels'] as $label => $value)
                <li><strong>{{ $label }}:</strong> {{ $value }}</li>
            @endforeach
        </ul>
    @endif

    <h2>Statistik Database</h2>
    <table>
        <tbody>
            <tr><th>Jumlah kelompok</th><td>{{ number_format($report['summary']['total']) }}</td></tr>
            <tr><th>Aktif</th><td>{{ number_format($report['summary']['active']) }}</td></tr>
            <tr><th>Tidak aktif</th><td>{{ number_format($report['summary']['inactive']) }}</td></tr>
            <tr><th>Anggota laki-laki</th><td>{{ number_format($report['summary']['male_members']) }}</td></tr>
            <tr><th>Anggota perempuan</th><td>{{ number_format($report['summary']['female_members']) }}</td></tr>
            <tr><th>Total bantuan</th><td>Rp {{ number_format($report['summary']['total_aid'], 0, ',', '.') }}</td></tr>
        </tbody>
    </table>

    @if ($report['notice'])
        <h2>Keterangan Data</h2>
        <p>{{ $report['notice'] }}</p>
    @elseif ($report['sections'])
        @foreach ([
            'pendahuluan' => 'Pendahuluan',
            'gambaran_umum' => 'Gambaran Umum',
            'hasil_analisis' => 'Hasil Analisis',
            'kesimpulan' => 'Kesimpulan',
        ] as $key => $heading)
            <h2>{{ $heading }}</h2>
            <p>{!! nl2br(e($report['sections'][$key])) !!}</p>
        @endforeach
        <h2>Rekomendasi</h2>
        <ol>
            @foreach ($report['sections']['rekomendasi'] as $recommendation)
                <li>{{ $recommendation }}</li>
            @endforeach
        </ol>
    @endif

    @if ($report['charts']['kawasan'] !== [])
        <h2>Kelompok per Kawasan</h2>
        <table>
            <thead><tr><th>Kawasan</th><th>Jumlah kelompok</th><th>Total bantuan (Rp)</th></tr></thead>
            <tbody>
                @foreach ($report['charts']['kawasan'] as $row)
                    <tr><td>{{ $row['label'] }}</td><td>{{ number_format($row['value']) }}</td><td>{{ number_format($row['aid'], 0, ',', '.') }}</td></tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
