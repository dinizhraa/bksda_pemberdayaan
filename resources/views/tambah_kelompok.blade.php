<x-app-layout>
    <x-slot name="header">
        <h1 class="page-title">Tambah Kelompok Binaan</h1>
        <p class="page-sub">Daftarkan kelompok masyarakat baru di sekitar kawasan konservasi.</p>
    </x-slot>

    <x-glass-card class="max-w-3xl sm:p-8">
        <form action="{{ route('kelompok.store') }}" method="POST" class="grid gap-5 sm:grid-cols-2">
            @csrf

            <div class="sm:col-span-2">
                <x-input-label for="nama_kelompok" value="Nama Kelompok" />
                <x-text-input id="nama_kelompok" name="nama_kelompok" type="text" class="mt-1.5 block w-full" :value="old('nama_kelompok')" required placeholder="Contoh: Kelompok Tani Hutan Lestari" />
                <x-input-error :messages="$errors->get('nama_kelompok')" class="mt-1.5" />
            </div>

            <div class="sm:col-span-2">
                <x-input-label for="kawasan_id" value="Kawasan Konservasi" />
                <x-select-input id="kawasan_id" name="kawasan_id" class="mt-1.5 block w-full" required>
                    <option value="">Pilih kawasan</option>
                    @foreach ($kawasans as $k)
                        <option value="{{ $k->id }}" @selected(old('kawasan_id') == $k->id)>{{ $k->nama_kawasan }}</option>
                    @endforeach
                </x-select-input>
                <x-input-error :messages="$errors->get('kawasan_id')" class="mt-1.5" />
            </div>

            <div>
                <x-input-label for="nama_ketua" value="Nama Ketua Kelompok" />
                <x-text-input id="nama_ketua" name="nama_ketua" type="text" class="mt-1.5 block w-full" :value="old('nama_ketua')" required placeholder="Nama ketua" />
                <x-input-error :messages="$errors->get('nama_ketua')" class="mt-1.5" />
            </div>

            <div>
                <x-input-label for="no_hp" value="Nomor HP / Kontak" />
                <x-text-input id="no_hp" name="no_hp" type="text" class="mt-1.5 block w-full" :value="old('no_hp')" placeholder="08xxxxxxxxxx" />
                <x-input-error :messages="$errors->get('no_hp')" class="mt-1.5" />
            </div>

            <div>
                <x-input-label for="jenis_usaha" value="Jenis Usaha / Produk Unggulan" />
                <x-text-input id="jenis_usaha" name="jenis_usaha" type="text" class="mt-1.5 block w-full" :value="old('jenis_usaha')" required placeholder="Contoh: Lebah Madu / Ekowisata" />
                <x-input-error :messages="$errors->get('jenis_usaha')" class="mt-1.5" />
            </div>

            <div>
                <x-input-label for="jumlah_bantuan" value="Jumlah Bantuan (Rp)" />
                <x-text-input id="jumlah_bantuan" name="jumlah_bantuan" type="number" class="mt-1.5 block w-full" :value="old('jumlah_bantuan')" required placeholder="Contoh: 25000000" />
                <x-input-error :messages="$errors->get('jumlah_bantuan')" class="mt-1.5" />
            </div>

            <div class="flex items-center justify-between gap-3 sm:col-span-2">
                <a href="{{ route('dashboard') }}" class="btn-ghost">Kembali</a>
                <x-primary-button>Simpan Data</x-primary-button>
            </div>
        </form>
    </x-glass-card>
</x-app-layout>
