<?php

namespace App\Http\Controllers;

use App\Models\Laporan;
use App\Models\NotulenRtm;
use App\Models\RencanaTindakLanjut;
use Illuminate\View\View;

class VerifikasiController extends Controller
{
    public function index(): View
    {
        $laporanPerkuliahan = Laporan::with([
            'semester.tahunAkademik',
            'jadwalMonev.termin',
            'pembuat',
        ])->where('jenis_laporan', 'perkuliahan')->where('status', 'diajukan')->oldest()->paginate(10, ['*'], 'laporan_perkuliahan_page')->withQueryString();

        $rtl = RencanaTindakLanjut::with([
            'temuan.evaluasiIndikator.semester.tahunAkademik',
            'temuan.evaluasiIndikator.evaluatable',
            'buktiTindakLanjuts',
        ])->whereRaw('1 = 0')->paginate(10, ['*'], 'rtl_page')->withQueryString();

        $notulenRtm = NotulenRtm::with([
            'jadwalRtm.semester.tahunAkademik',
            'penginput',
        ])->where('status', 'diajukan')->oldest()->paginate(10, ['*'], 'notulen_page')->withQueryString();

        return view('verifikasi.index', compact('laporanPerkuliahan', 'rtl', 'notulenRtm'));
    }
}
