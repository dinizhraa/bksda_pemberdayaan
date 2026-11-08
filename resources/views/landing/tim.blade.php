@php
    // Ubah daftar ini untuk menambah atau mengganti anggota tim.
    $tim = [
        ['name' => 'Dini Zahra', 'role' => 'Pengembang Aplikasi', 'github' => 'dinizhraa'],
        ['name' => 'Fidya', 'role' => 'Pengembang Aplikasi', 'github' => 'fidya28'],
    ];
@endphp

<x-landing-section id="tim" title="Tim pengembang" lead="Orang-orang di balik aplikasi ini.">
    <div class="mx-auto grid max-w-3xl gap-5 sm:grid-cols-2">
        @foreach ($tim as $i => $anggota)
            <x-team-card :name="$anggota['name']" :role="$anggota['role']" :github="$anggota['github']" :delay="$i * 120" />
        @endforeach
    </div>
</x-landing-section>
