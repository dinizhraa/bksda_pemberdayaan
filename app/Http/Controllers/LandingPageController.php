<?php

namespace App\Http\Controllers;

use App\Models\Kelompok;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

class LandingPageController extends Controller
{
    public function __invoke(): View
    {
        $jumlahKelompok = Kelompok::query()->count();

        $desaPenyangga = DB::table('desa_kawasan')
            ->join('desas', 'desas.id', '=', 'desa_kawasan.desa_id')
            ->whereNotNull('desas.kode_desa')
            ->where('desas.kode_desa', '<>', '')
            ->where('desas.nama_desa', '<>', '')
            ->whereNotNull('desas.kecamatan')
            ->where('desas.kecamatan', '<>', '')
            ->whereNotNull('desas.kabupaten')
            ->where('desas.kabupaten', '<>', '')
            ->select('desas.kode_desa', 'desa_kawasan.kawasan_id')
            ->distinct();

        $jumlahDesaPenyangga = DB::query()
            ->fromSub($desaPenyangga, 'desa_kawasan_bernama')
            ->count();

        return view('landing', compact('jumlahKelompok', 'jumlahDesaPenyangga'));
    }
}
