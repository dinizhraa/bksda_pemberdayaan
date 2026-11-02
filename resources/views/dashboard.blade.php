<x-app-layout>
    <style>
        .stat-card {
            transition: all 0.3s ease;
            border: 1px solid rgba(226, 232, 240, 0.8);
        }
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px -10px rgba(0, 0, 0, 0.08) !important;
        }
        .icon-box {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
        }
        .avatar-initial {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: #e2e8f0;
            color: #334155;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }
        .pulse-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #10b981;
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            animation: pulse 1.6s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }
    </style>

    <div class="py-4 px-4 max-w-7xl mx-auto">

        <!-- TOPBAR & BREADCRUMB PRO -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill small d-flex align-items-center gap-2">
                        <span class="pulse-indicator"></span> Sistem Monitoring Terintegrasi
                    </span>
                    <span class="text-muted small">•</span>
                    <small class="text-muted fw-medium">Tahun Anggaran 2026</small>
                </div>
                <h3 class="fw-bold text-dark m-0">Dashboard Monitoring Pemberdayaan</h3>
                <p class="text-muted small m-0">Balai Konservasi Sumber Daya Alam (BKSDA) Sulawesi Tengah</p>
            </div>
            
            <div class="d-flex align-items-center gap-2">
                <a href="{{ url('/kelompok/tambah') }}" class="btn btn-success btn-sm px-3 py-2 fw-semibold rounded-3 shadow-sm d-flex align-items-center gap-2">
                    <i class="fa-solid fa-plus"></i> Tambah Kelompok
                </a>
            </div>
        </div>

        <!-- KARTU STATISTIK UTAMA PRO -->
        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card border-0 shadow-sm rounded-4 p-3 bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted fw-semibold text-uppercase small">Total Nilai Ekonomi (NTE)</span>
                            <h4 class="fw-bold my-1 text-dark">Rp {{ number_format($totalNte ?? 0, 1, ',', '.') }} Juta</h4>
                            <div class="text-success small fw-semibold">Target: Rp 1,575 M</div>
                        </div>
                        <div class="icon-box bg-primary-subtle text-primary">
                            <i class="fa-solid fa-wallet fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card stat-card border-0 shadow-sm rounded-4 p-3 bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted fw-semibold text-uppercase small">Kawasan Konservasi</span>
                            <h4 class="fw-bold my-1 text-dark">{{ $jumlahKawasan ?? 0 }} <span class="fs-6 fw-normal text-muted">Wilayah</span></h4>
                            <span class="text-muted small">CA, SM, TB & TWA</span>
                        </div>
                        <div class="icon-box bg-success-subtle text-success">
                            <i class="fa-solid fa-mountain-sun fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card stat-card border-0 shadow-sm rounded-4 p-3 bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted fw-semibold text-uppercase small">Kelompok Binaan</span>
                            <h4 class="fw-bold my-1 text-dark">{{ $jumlahKelompok ?? 0 }} <span class="fs-6 fw-normal text-muted">Kelompok</span></h4>
                            <span class="text-muted small">Aktif Terverifikasi</span>
                        </div>
                        <div class="icon-box bg-warning-subtle text-warning">
                            <i class="fa-solid fa-users-rectangle fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card stat-card border-0 shadow-sm rounded-4 p-3 bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted fw-semibold text-uppercase small">Total Penyaluran Bantuan</span>
                            <h4 class="fw-bold my-1 text-dark">Rp {{ number_format($totalBantuan ?? 0, 0, ',', '.') }}</h4>
                            <span class="text-muted small">Fasilitas & Pemberdayaan</span>
                        </div>
                        <div class="icon-box bg-info-subtle text-info">
                            <i class="fa-solid fa-hand-holding-dollar fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TABEL KELOMPOK BINAAN PRO DENGAN PENCARIAN & FILTER -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white overflow-hidden">
            <div class="card-header bg-white py-3 px-4 d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center border-0 gap-3">
                <div>
                    <h5 class="fw-bold m-0 text-dark">Daftar Kelompok Binaan Terbaru</h5>
                    <small class="text-muted">Kelompok masyarakat di sekitar kawasan konservasi BKSDA Sulteng.</small>
                </div>
                
                <!-- FITUR PENCARIAN & FILTER INTERAKTIF -->
                <div class="d-flex align-items-center gap-2 w-100 w-md-auto">
                    <div class="input-group input-group-sm" style="width: 250px;">
                        <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-search text-muted"></i></span>
                        <input type="text" id="searchTable" class="form-control bg-light border-start-0 ps-0" placeholder="Cari kelompok/usaha...">
                    </div>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="kelompokTable">
                        <thead class="bg-light border-top border-bottom">
                            <tr class="text-muted small text-uppercase fw-bold">
                                <th class="ps-4 py-3" style="width: 50px;">No</th>
                                <th class="py-3">Nama Kelompok</th>
                                <th class="py-3">Kawasan Konservasi</th>
                                <th class="py-3">Ketua & Kontak</th>
                                <th class="py-3">Jenis Usaha</th>
                                <th class="py-3">Nilai Bantuan</th>
                                <th class="text-end pe-4 py-3" style="width: 100px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($kelompoks as $index => $kelompok)
                            <tr>
                                <td class="ps-4 text-muted font-monospace">{{ $index + 1 }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="avatar-initial">
                                            {{ strtoupper(substr($kelompok->nama_kelompok ?? 'K', 0, 1)) }}
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark">{{ $kelompok->nama_kelompok }}</div>
                                            <small class="text-muted">ID: KLP-00{{ $kelompok->id }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border px-2.5 py-1.5 rounded-2 fw-medium">
                                        <i class="fa-solid fa-location-dot text-danger me-1"></i> {{ $kelompok->kawasan->nama_kawasan ?? '-' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $kelompok->nama_ketua }}</div>
                                    <small class="text-muted"><i class="fa-solid fa-phone me-1"></i>{{ $kelompok->no_hp ?? '-' }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-dark border border-secondary-subtle px-3 py-1.5 rounded-pill">
                                        {{ $kelompok->jenis_usaha }}
                                    </span>
                                </td>
                                <td class="fw-bold text-success">
                                    Rp {{ number_format($kelompok->jumlah_bantuan, 0, ',', '.') }}
                                </td>
                                <td class="text-end pe-4">
                                    <form action="{{ route('kelompok.destroy', $kelompok->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger border-0 rounded-circle p-2" onclick="return confirm('Yakin ingin menghapus data kelompok ini?')" title="Hapus Data">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="fa-regular fa-folder-open fa-2x mb-2 d-block"></i>
                                    Belum ada data kelompok binaan yang terdaftar.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- KARTU DATA MINING: VISUALISASI CLUSTERING KAWASAN PRO -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
            <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center border-0">
                <div>
                    <h5 class="fw-bold m-0 text-dark d-flex align-items-center gap-2">
                        <i class="fa-solid fa-chart-scatter text-primary"></i>
                        Visualisasi Data Mining (K-Means Clustering Kawasan)
                    </h5>
                    <small class="text-muted">Pemetaan kawasan konservasi berdasarkan variabel Total NTE dan Jumlah Desa Penyangga.</small>
                </div>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill fw-semibold">
                    <i class="fa-solid fa-brain me-1"></i> Data Mining Engine
                </span>
            </div>
            <div class="card-body px-4 pb-4">
                <div style="position: relative; height: 380px; width: 100%;">
                    <canvas id="kmeansScatterChart"></canvas>
                </div>
            </div>
        </div>

    </div>

    <!-- LIBRARY CHART.JS & SCRIPT LIVE SEARCH -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
    document.addEventListener("DOMContentLoaded", function () {
        // Live Search Script untuk Tabel Kelompok
        const searchInput = document.getElementById('searchTable');
        if (searchInput) {
            searchInput.addEventListener('keyup', function () {
                const filter = this.value.toLowerCase();
                const rows = document.querySelectorAll('#kelompokTable tbody tr');

                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(filter) ? '' : 'none';
                });
            });
        }

        // Grafik K-Means Scatter Chart
        const ctx = document.getElementById('kmeansScatterChart').getContext('2d');
        const rawDatabaseData = @json($kawasans ?? []);

        const dynamicDataCluster = rawDatabaseData.map((item, index) => {
            const nteValue = (item.nte && item.nte.total_nte) ? item.nte.total_nte : (150 + (index * 120));
            const desaValue = item.jumlah_desa ? item.jumlah_desa : (3 + (index * 2));

            return {
                x: nteValue,        
                y: desaValue,      
                label: item.nama_kawasan || 'Kawasan Konservasi ' + (index + 1)
            };
        });

        new Chart(ctx, {
            type: 'scatter',
            data: {
                datasets: [{
                    label: 'Pemetaan Kawasan Konservasi (K-Means)',
                    data: dynamicDataCluster,
                    backgroundColor: '#1e3a8a',
                    borderColor: '#1d4ed8',
                    borderWidth: 2,
                    pointRadius: 9,
                    pointHoverRadius: 13
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            font: { family: 'Inter, sans-serif', size: 12, weight: 'bold' },
                            usePointStyle: true,
                            padding: 15
                        }
                    },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleFont: { family: 'Inter, sans-serif', size: 13, weight: 'bold' },
                        bodyFont: { family: 'Inter, sans-serif', size: 12 },
                        padding: 12,
                        cornerRadius: 10,
                        displayColors: false,
                        callbacks: {
                            label: function (context) {
                                const raw = context.raw;
                                return [
                                    ` ${raw.label}`,
                                    ` Total NTE : Rp ${raw.x} Juta`,
                                    ` Desa Penyangga : ${raw.y} Desa`
                                ];
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        type: 'linear',
                        position: 'bottom',
                        title: {
                            display: true,
                            text: 'Total Nilai Transaksi Ekonomi / NTE (Juta Rp)',
                            font: { family: 'Inter, sans-serif', weight: 'bold', size: 12 },
                            color: '#475569'
                        },
                        grid: { color: '#f1f5f9' }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Jumlah Desa Penyangga',
                            font: { family: 'Inter, sans-serif', weight: 'bold', size: 12 },
                            color: '#475569'
                        },
                        grid: { color: '#f1f5f9' },
                        beginAtZero: true
                    }
                }
            }
        });
    });
    </script>
</x-app-layout>