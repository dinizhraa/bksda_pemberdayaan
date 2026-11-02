<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Kelompok Binaan - BKSDA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-success text-white py-3">
                        <h4 class="mb-0">Tambah Data Kelompok Binaan Baru</h4>
                    </div>
                    <div class="card-body p-4">
                        <form action="/kelompok" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label fw-bold">Nama Kelompok</label>
                                <input type="text" name="nama_kelompok" class="form-control" required placeholder="Contoh: Kelompok Tani Hutan Lestari">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Kawasan Konservasi</label>
                                <select name="kawasan_id" class="form-select" required>
                                    <option value="">-- Pilih Kawasan --</option>
                                    @foreach($kawasans as $k)
                                        <option value="{{ $k->id }}">{{ $k->nama_kawasan }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Nama Ketua Kelompok</label>
                                <input type="text" name="nama_ketua" class="form-control" required placeholder="Nama Ketua">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Nomor HP / Kontak</label>
                                <input type="text" name="no_hp" class="form-control" placeholder="08xxxxxxxxxx">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Jenis Usaha / Produk Unggulan</label>
                                <input type="text" name="jenis_usaha" class="form-control" required placeholder="Contoh: Lebah Madu / Ekowisata">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Jumlah Bantuan (Rp)</label>
                                <input type="number" name="jumlah_bantuan" class="form-control" required placeholder="Contoh: 25000000">
                            </div>
                            <div class="d-flex justify-content-between">
                                <a href="/" class="btn btn-secondary">Kembali</a>
                                <button type="submit" class="btn btn-success">Simpan Data</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>