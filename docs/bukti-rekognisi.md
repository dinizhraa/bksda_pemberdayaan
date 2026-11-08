# Pemetaan Bukti Rekognisi Mata Kuliah

Dokumen ini mencatat bukti yang dapat diverifikasi pada aplikasi BKSDA Pemberdayaan. Kesesuaian final tetap perlu dicocokkan dengan RPS lengkap dan arahan program studi.

## Data Mining

| Butir bukti RPS | Implementasi/bukti dalam proyek | Status |
|---|---|---|
| Pra-proses data | Data tanpa NTE, nilai negatif, jumlah desa nol, atau desa difasilitasi melebihi jumlah desa sekitar dikeluarkan dari analisis; jumlah data yang dikeluarkan ditampilkan. | Diimplementasikan |
| Feature engineering | Tiga fitur: total NTE, jumlah desa sekitar, dan rasio desa difasilitasi terhadap desa sekitar. | Diimplementasikan |
| Klasifikasi/clustering/asosiasi/regresi | K-Means dengan K=3 dan inisialisasi deterministik, maksimal 100 iterasi; cluster dinamai menurut rerata NTE. | Diimplementasikan |
| Validasi model | Skor silhouette rata-rata ditampilkan pada dashboard. Ini validasi internal clustering dan bukan klaim akurasi prediksi. | Diimplementasikan |
| Insight bisnis/operasional | Dashboard menampilkan jumlah kawasan dan rerata NTE serta cakupan fasilitasi desa per cluster. Interpretasi tetap harus divalidasi dengan kondisi lapangan. | Diimplementasikan |
| Dataset dan sumber data | Seeder berisi 18 kawasan untuk demonstrasi. Asal-usul, periode, dan otorisasi data operasional perlu didokumentasikan sebelum hasil dipakai sebagai temuan resmi. | Perlu konfirmasi pemilik data |

Implementasi utama berada di `app/Services/KawasanClusteringService.php`; pemanggilan di `app/Http/Controllers/DashboardController.php`; visualisasi di `resources/views/dashboard.blade.php` dan `resources/js/dashboard.js`; pengujian algoritma di `tests/Unit/KawasanClusteringServiceTest.php`.

## Kerja Praktek

RPS Kerja Praktek belum disertakan pada bahan yang tersedia, sehingga pemetaan di bawah adalah bukti proyek yang dapat ditunjukkan, bukan pernyataan bahwa semua capaian RPS telah terpenuhi.

- Produk: aplikasi web pengelolaan dan pemantauan pemberdayaan masyarakat sekitar kawasan konservasi.
- Bukti teknis saat ini: autentikasi, pengelolaan data kelompok, relasi kawasan/NTE, dashboard, pipeline clustering, tes unit, dan hasil build frontend.
- Bukti yang perlu dilengkapi tim/pembimbing: surat atau persetujuan mitra, kebutuhan yang disahkan, logbook dan konsultasi, bukti pengujian/UAT, demo/penerimaan pengguna, serta laporan Kerja Praktek sesuai format kampus.
- Data contoh dari seeder tidak boleh diklaim sebagai data lapangan tanpa verifikasi sumber dan izin.

## Manajemen Proyek

### Project charter (draf)

- **Tujuan:** membantu pengelolaan data kelompok binaan dan menyajikan ringkasan NTE serta pengelompokan kawasan untuk mendukung eksplorasi prioritas pendampingan.
- **Pengguna/pemangku kepentingan:** pengelola data BKSDA, kelompok binaan, tim pengembang, dan pembimbing akademik; nama penanggung jawab perlu ditetapkan.
- **Ruang lingkup:** autentikasi, data kawasan/desa/NTE/kelompok, dashboard, dan analisis clustering eksploratif.
- **Di luar ruang lingkup:** keputusan bantuan otomatis, prediksi dampak kausal, dan penggunaan data operasional yang belum disetujui pemiliknya.
- **Kriteria penerimaan:** fitur dapat didemokan; data invalid ditangani; tes terkait lulus; hasil clustering dan keterbatasannya dijelaskan kepada pengguna.

### WBS / backlog awal

| ID | Pekerjaan | Status bukti saat ini |
|---|---|---|
| 1 | Konfirmasi kebutuhan, pengguna, sumber data, dan izin penggunaan | Perlu dilengkapi bersama mitra |
| 2 | Bangun skema dan relasi data kawasan, desa, NTE, dan kelompok | Ada pada migration dan model |
| 3 | Bangun autentikasi dan pengelolaan data kelompok | Ada pada aplikasi; perlu UAT |
| 4 | Bangun dashboard ringkasan dan visualisasi | Ada pada dashboard |
| 5 | Implementasikan preprocessing, feature engineering, K-Means, dan evaluasi silhouette | Implementasi serta tes unit tersedia |
| 6 | Pengujian sistem, UAT, perbaikan, dan demo | Perlu bukti hasil pengujian pengguna |
| 7 | Siapkan deployment, panduan pengguna, dan laporan akhir | Perlu dilengkapi sesuai RPS/mitra |

### Jadwal, risiko, monitoring, dan evaluasi

- **Jadwal:** gunakan urutan WBS sebagai baseline fase. Tanggal mulai/selesai dan penanggung jawab belum tersedia; tetapkan bersama tim sebelum dijadikan jadwal resmi.
- **Risiko:** data tidak lengkap/tidak berizin (mitigasi: validasi dan persetujuan pemilik data); jumlah sampel kecil (mitigasi: tampilkan status tidak cukup data); hasil cluster disalahartikan sebagai rekomendasi otomatis (mitigasi: jelaskan batasan dan validasi lapangan); perubahan kebutuhan terlambat (mitigasi: review backlog berkala).
- **Monitoring progres:** perbarui status tiap item WBS pada setiap rapat, catat tanggal, PIC, bukti selesai, hambatan, dan tindak lanjut. Tabel status di atas adalah baseline, bukan riwayat rapat.
- **Evaluasi/laporan:** lampirkan hasil tes, tangkapan layar/demo, masukan pengguna, perubahan backlog, hasil evaluasi model, serta keterbatasan data pada laporan akhir.

Untuk penyesuaian formal, tambahkan RPS lengkap Kerja Praktek dan Manajemen Proyek, jadwal aktual, nama/PIC, dan bukti kegiatan yang disetujui pembimbing.
