<?php

namespace App\Http\Controllers;

use App\Models\NotulenRtm;
use App\Models\RencanaTindakLanjut;
use App\Models\RingkasanPerkuliahan;
use Illuminate\View\View;

class VerifikasiController extends Controller
{
    public function index(): View
    {
        $ringkasanPerkuliahan = RingkasanPerkuliahan::with([
            'jadwalMonev.semester.tahunAkademik',
            'jadwalMonev.termin',
            'perkuliahan.mataKuliah',
            'perkuliahan.kelas',
            'perkuliahan.pengajars.dosen',
            'penginput',
        ])->where('status', 'diajukan')->oldest()->paginate(10, ['*'], 'ringkasan_page')->withQueryString();

        $rtl = RencanaTindakLanjut::with([
            'temuan.evaluasiIndikator.semester.tahunAkademik',
            'temuan.evaluasiIndikator.evaluatable',
            'buktiTindakLanjuts',
        ])->whereRaw('1 = 0')->paginate(10, ['*'], 'rtl_page')->withQueryString();

        $notulenRtm = NotulenRtm::with([
            'jadwalRtm.semester.tahunAkademik',
            'penginput',
        ])->where('status', 'diajukan')->oldest()->paginate(10, ['*'], 'notulen_page')->withQueryString();

        return view('verifikasi.index', compact('ringkasanPerkuliahan', 'rtl', 'notulenRtm'));
    }
}
