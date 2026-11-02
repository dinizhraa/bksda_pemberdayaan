<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Kawasan;
use App\Models\Kelompok;
use App\Models\Nte;

class DashboardController extends Controller
{
    public function index()
    {
        $totalNte = Nte::sum('total_nte');
        $jumlahKawasan = Kawasan::count();
        $jumlahKelompok = Kelompok::count();
        $totalBantuan = Kelompok::sum('jumlah_bantuan');
        
        $kawasans = Kawasan::with('nte')->get();
        $kelompoks = Kelompok::with(['kawasan', 'desa'])->get();

        return view('dashboard', compact('totalNte', 'jumlahKawasan', 'jumlahKelompok', 'totalBantuan', 'kawasans', 'kelompoks'));
    }

    // Menampilkan Form Tambah Kelompok
    public function createKelompok()
    {
        $kawasans = Kawasan::all();
        return view('tambah_kelompok', compact('kawasans'));
    }

    // Menyimpan Data Kelompok Baru ke Database
    public function storeKelompok(Request $request)
    {
        $request->validate([
            'nama_kelompok' => 'required|string|max:255',
            'kawasan_id' => 'required',
            'nama_ketua' => 'required|string|max:255',
            'jenis_usaha' => 'required|string|max:255',
            'jumlah_bantuan' => 'required|numeric',
        ]);

        Kelompok::create([
            'kawasan_id' => $request->kawasan_id,
            'tahun_pembinaan' => 2026,
            'nama_kelompok' => $request->nama_kelompok,
            'nama_ketua' => $request->nama_ketua,
            'no_hp' => $request->no_hp,
            'jenis_usaha' => $request->jenis_usaha,
            'jumlah_bantuan' => $request->jumlah_bantuan,
        ]);

        return redirect('/')->with('success', 'Data kelompok binaan berhasil ditambahkan!');
    }

    // Menghapus Data Kelompok
    public function destroyKelompok($id)
    {
        $kelompok = Kelompok::findOrFail($id);
        $kelompok->delete();

        return redirect('/')->with('success', 'Data kelompok berhasil dihapus!');
    }
}