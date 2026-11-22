<x-app-layout>
    <x-slot name="header">
        <h1 class="page-title">{{ isset($kelompok) ? 'Edit Kelompok Binaan' : 'Tambah Kelompok Binaan' }}</h1>
        <p class="page-sub">{{ isset($kelompok) ? 'Perbarui data kelompok masyarakat di sekitar kawasan konservasi.' : 'Daftarkan kelompok masyarakat baru di sekitar kawasan konservasi.' }}</p>
    </x-slot>

    <x-glass-card class="mx-auto w-full max-w-4xl sm:p-8">
        <form action="{{ isset($kelompok) ? route('kelompok.update', $kelompok->id) : route('kelompok.store') }}" method="POST" class="grid gap-5 sm:grid-cols-2">
            @csrf
            @if (isset($kelompok))
                @method('PUT')
            @endif

            <div class="sm:col-span-2">
                <x-input-label for="nama_kelompok" value="Nama Kelompok" />
                <x-text-input id="nama_kelompok" name="nama_kelompok" type="text" class="mt-1.5 block w-full" :value="old('nama_kelompok', $kelompok->nama_kelompok ?? '')" required placeholder="Contoh: Kelompok Tani Hutan Lestari" />
                <x-input-error :messages="$errors->get('nama_kelompok')" class="mt-1.5" />
            </div>

            <div>
                <x-input-label for="status_kelompok" value="Status Kelompok" />
                <x-select-input id="status_kelompok" name="status_kelompok" class="mt-1.5 block w-full" required>
                    <option value="">Pilih status</option>
                    <option value="Aktif" @selected(old('status_kelompok', $kelompok->status_kelompok ?? '') === 'Aktif')>Aktif</option>
                    <option value="Tidak Aktif" @selected(old('status_kelompok', $kelompok->status_kelompok ?? '') === 'Tidak Aktif')>Tidak Aktif</option>
                </x-select-input>
                <x-input-error :messages="$errors->get('status_kelompok')" class="mt-1.5" />
            </div>

            <div class="sm:col-span-2">
                <x-input-label for="kawasan_id" value="Kawasan Konservasi" />
                <x-select-input id="kawasan_id" name="kawasan_id" class="mt-1.5 block w-full" required>
                    <option value="">Pilih kawasan</option>
                    @foreach ($kawasans as $k)
                        <option value="{{ $k->id }}" @selected(old('kawasan_id', $kelompok->kawasan_id ?? null) == $k->id)>{{ $k->nama_kawasan }}</option>
                    @endforeach
                </x-select-input>
                <x-input-error :messages="$errors->get('kawasan_id')" class="mt-1.5" />
            </div>

            <div class="sm:col-span-2">
                <x-input-label for="nama_desa" value="Desa" />
                <x-text-input id="nama_desa" name="nama_desa" type="text" class="mt-1.5 block w-full" :value="old('nama_desa', $kelompok->desa?->nama_desa ?? '')" required placeholder="Masukkan nama desa" />
                <x-input-error :messages="$errors->get('nama_desa')" class="mt-1.5" />
            </div>

            <div>
                <x-input-label for="tahun_pembentukan" value="Tahun Pembentukan Kelompok" />
                <x-text-input id="tahun_pembentukan" name="tahun_pembentukan" type="number" min="1000" max="9999" step="1" inputmode="numeric" class="mt-1.5 block w-full" :value="old('tahun_pembentukan', $kelompok->tahun_pembentukan ?? '')" placeholder="Contoh: 2020" />
                <x-input-error :messages="$errors->get('tahun_pembentukan')" class="mt-1.5" />
            </div>

            <div>
                <x-input-label for="tahun_monitoring" value="Tahun Monitoring" />
                <x-text-input id="tahun_monitoring" name="tahun_monitoring" type="number" min="1000" max="9999" step="1" inputmode="numeric" class="mt-1.5 block w-full" :value="old('tahun_monitoring', $kelompok->tahun_monitoring ?? '')" placeholder="Contoh: 2026" />
                <x-input-error :messages="$errors->get('tahun_monitoring')" class="mt-1.5" />
            </div>

            <div>
                <x-input-label for="nama_ketua" value="Nama Ketua Kelompok" />
                <x-text-input id="nama_ketua" name="nama_ketua" type="text" class="mt-1.5 block w-full" :value="old('nama_ketua', $kelompok->nama_ketua ?? '')" required placeholder="Nama ketua" />
                <x-input-error :messages="$errors->get('nama_ketua')" class="mt-1.5" />
            </div>

            <div>
                <x-input-label for="no_hp" value="Nomor HP / Kontak" />
                <x-text-input id="no_hp" name="no_hp" type="text" class="mt-1.5 block w-full" :value="old('no_hp', $kelompok->no_hp ?? '')" placeholder="08xxxxxxxxxx" />
                <x-input-error :messages="$errors->get('no_hp')" class="mt-1.5" />
            </div>

            <div>
                <x-input-label for="kecamatan" value="Kecamatan" />
                <x-text-input id="kecamatan" name="kecamatan" type="text" class="mt-1.5 block w-full" :value="old('kecamatan', $kelompok->kecamatan ?? '')" placeholder="Contoh: Bualemo" />
                <x-input-error :messages="$errors->get('kecamatan')" class="mt-1.5" />
            </div>

            <div>
                <x-input-label for="kabupaten" value="Kabupaten" />
                <x-text-input id="kabupaten" name="kabupaten" type="text" class="mt-1.5 block w-full" :value="old('kabupaten', $kelompok->kabupaten ?? '')" placeholder="Contoh: Banggai" />
                <x-input-error :messages="$errors->get('kabupaten')" class="mt-1.5" />
            </div>

            <div>
                <x-input-label for="latitude" value="Latitude" />
                <x-text-input id="latitude" name="latitude" type="number" step="any" min="-90" max="90" class="mt-1.5 block w-full" :value="old('latitude', $kelompok->latitude ?? '')" placeholder="Contoh: -0.9012" />
                <x-input-error :messages="$errors->get('latitude')" class="mt-1.5" />
            </div>

            <div>
                <x-input-label for="longitude" value="Longitude" />
                <x-text-input id="longitude" name="longitude" type="number" step="any" min="-180" max="180" class="mt-1.5 block w-full" :value="old('longitude', $kelompok->longitude ?? '')" placeholder="Contoh: 119.8701" />
                <x-input-error :messages="$errors->get('longitude')" class="mt-1.5" />
            </div>
            <p class="text-xs text-ink-500 sm:col-span-2">Koordinat bersifat opsional, tetapi latitude dan longitude harus diisi berpasangan. Salin koordinat desimal dari Google Maps untuk menentukan titik.</p>

            <div>
                <x-input-label for="jenis_usaha" value="Jenis Usaha" />
                <x-text-input id="jenis_usaha" name="jenis_usaha" type="text" class="mt-1.5 block w-full" :value="old('jenis_usaha', $kelompok->jenis_usaha ?? '')" required placeholder="Contoh: Budidaya ikan" />
                <x-input-error :messages="$errors->get('jenis_usaha')" class="mt-1.5" />
            </div>

            <div>
                <x-input-label for="sk_kelompok" value="SK (Surat Keputusan)" />
                <x-text-input id="sk_kelompok" name="sk_kelompok" type="text" class="mt-1.5 block w-full" :value="old('sk_kelompok', $kelompok->sk_kelompok ?? '')" placeholder="Nomor SK kelompok" />
                <x-input-error :messages="$errors->get('sk_kelompok')" class="mt-1.5" />
            </div>

            <div class="sm:col-span-2 grid gap-5 sm:grid-cols-3">
                <div>
                    <x-input-label for="jumlah_anggota_laki_laki" value="Jumlah Anggota Laki-laki" />
                    <x-text-input id="jumlah_anggota_laki_laki" name="jumlah_anggota_laki_laki" type="number" min="0" step="1" class="mt-1.5 block w-full" :value="old('jumlah_anggota_laki_laki', $kelompok->jumlah_anggota_laki_laki ?? '')" placeholder="Contoh: 8" />
                    <x-input-error :messages="$errors->get('jumlah_anggota_laki_laki')" class="mt-1.5" />
                </div>

                <div>
                    <x-input-label for="jumlah_anggota_perempuan" value="Jumlah Anggota Perempuan" />
                    <x-text-input id="jumlah_anggota_perempuan" name="jumlah_anggota_perempuan" type="number" min="0" step="1" class="mt-1.5 block w-full" :value="old('jumlah_anggota_perempuan', $kelompok->jumlah_anggota_perempuan ?? '')" placeholder="Contoh: 7" />
                    <x-input-error :messages="$errors->get('jumlah_anggota_perempuan')" class="mt-1.5" />
                </div>

                <div>
                    <x-input-label for="jumlah_anggota" value="Jumlah Anggota Kelompok" />
                    <x-text-input id="jumlah_anggota" name="jumlah_anggota" type="number" min="0" step="1" class="mt-1.5 block w-full" :value="old('jumlah_anggota', $kelompok->jumlah_anggota ?? 0)" readonly />
                </div>
            </div>

            <div>
                <x-input-label for="jumlah_bantuan" value="Jumlah Bantuan (Rp)" />
                <x-text-input id="jumlah_bantuan" name="jumlah_bantuan" type="number" class="mt-1.5 block w-full" :value="old('jumlah_bantuan', $kelompok->jumlah_bantuan ?? '')" required placeholder="Contoh: 25000000" />
                <x-input-error :messages="$errors->get('jumlah_bantuan')" class="mt-1.5" />
            </div>

            <div class="sm:col-span-2">
                <x-input-label for="catatan_kebutuhan_hasil_monev" value="Catatan Kebutuhan / Hasil Monev" />
                <textarea id="catatan_kebutuhan_hasil_monev" name="catatan_kebutuhan_hasil_monev" rows="6" maxlength="10000" class="field mt-1.5 block w-full" placeholder="Catat kebutuhan kelompok, hasil monitoring dan evaluasi, kendala, serta tindak lanjut yang diperlukan.">{{ old('catatan_kebutuhan_hasil_monev', $kelompok->catatan_kebutuhan_hasil_monev ?? '') }}</textarea>
                <x-input-error :messages="$errors->get('catatan_kebutuhan_hasil_monev')" class="mt-1.5" />
            </div>

            <div class="flex items-center justify-between gap-3 sm:col-span-2">
                <a href="{{ route('dashboard') }}" class="btn-ghost">Kembali</a>
                <x-primary-button>{{ isset($kelompok) ? 'Perbarui Data' : 'Simpan Data' }}</x-primary-button>
            </div>
        </form>
    </x-glass-card>

    @pushOnce('scripts')
        <script>
            const maleInput = document.getElementById('jumlah_anggota_laki_laki');
            const femaleInput = document.getElementById('jumlah_anggota_perempuan');
            const totalInput = document.getElementById('jumlah_anggota');

            if (maleInput && femaleInput && totalInput) {
                const existingTotal = Number(totalInput.value) || 0;
                const updateTotal = () => {
                    const maleCount = maleInput.value === '' ? 0 : Number(maleInput.value);
                    const femaleCount = femaleInput.value === '' ? 0 : Number(femaleInput.value);

                    totalInput.value = maleInput.value === '' && femaleInput.value === ''
                        ? String(existingTotal)
                        : String(maleCount + femaleCount);
                };

                maleInput.addEventListener('input', updateTotal);
                femaleInput.addEventListener('input', updateTotal);
                updateTotal();
            }
        </script>
    @endPushOnce
</x-app-layout>
