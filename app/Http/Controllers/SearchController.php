<?php

namespace App\Http\Controllers;

use App\Models\Ami;
use App\Models\EvaluasiIndikator;
use App\Models\JadwalRtm;
use App\Models\RencanaTindakLanjut;
use App\Models\RingkasanPerkuliahan;
use App\Models\Temuan;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function index(Request $request): View
    {
        $query = trim($request->query('q', ''));
        $user = auth()->user();

        $ringkasan = collect();
        $evaluasi = collect();
        $temuan = collect();
        $rtl = collect();
        $rtm = collect();
        $ami = collect();

        if (mb_strlen($query) >= 2) {
            $keyword = "%{$query}%";

            // 1. Ringkasan Perkuliahan
            $ringkasan = RingkasanPerkuliahan::with([
                'perkuliahan.mataKuliah',
                'perkuliahan.kelas',
                'jadwalMonev.semester.tahunAkademik',
            ])
                ->when($user->hasRole('anggota-gkm'), fn ($q) => $q->where('input_by', $user->id))
                ->when($user->hasRole('koordinator-prodi'), fn ($q) => $q->whereIn('status', ['diajukan', 'diverifikasi', 'ditolak']))
                ->where(function ($q) use ($keyword) {
                    $q->whereHas('perkuliahan.mataKuliah', fn ($mq) => $mq->where('kode_mk', 'like', $keyword)->orWhere('nama_mk', 'like', $keyword))
                        ->orWhereHas('perkuliahan.kelas', fn ($kq) => $kq->where('nama_kelas', 'like', $keyword))
                        ->orWhere('keterangan', 'like', $keyword);
                })
                ->latest()
                ->limit(10)
                ->get();

            // 2. Evaluasi Indikator
            $evaluasi = EvaluasiIndikator::with(['semester.tahunAkademik', 'evaluatable'])
                ->where(function ($q) use ($keyword) {
                    $q->where('nama_penanggung_jawab', 'like', $keyword)
                        ->orWhere('catatan', 'like', $keyword)
                        ->orWhereHasMorph('evaluatable', ['App\Models\IndikatorMutu', 'App\Models\IndikatorKinerjaKegiatanSatuan'], function ($mq, $type) use ($keyword) {
                            if ($type === 'App\Models\IndikatorMutu') {
                                $mq->where('kode_indikator', 'like', $keyword)->orWhere('isi_indikator', 'like', $keyword);
                            } else {
                                $mq->where('kode_ikks', 'like', $keyword)->orWhere('uraian_ikks', 'like', $keyword);
                            }
                        });
                })
                ->latest()
                ->limit(10)
                ->get();

            // 3. Temuan Evaluasi
            $temuan = Temuan::with(['evaluasiIndikator.semester.tahunAkademik'])
                ->when($user->hasRole('anggota-gkm'), fn ($q) => $q->where('created_by', $user->id))
                ->where(function ($q) use ($keyword) {
                    $q->where('kode_temuan', 'like', $keyword)
                        ->orWhere('pernyataan', 'like', $keyword)
                        ->orWhere('rencana_awal', 'like', $keyword);
                })
                ->latest()
                ->limit(10)
                ->get();

            // 4. Realisasi RTL
            $rtl = RencanaTindakLanjut::with(['temuan.evaluasiIndikator.semester.tahunAkademik'])
                ->where(function ($q) use ($keyword) {
                    $q->where('uraian_realisasi', 'like', $keyword)
                        ->orWhere('catatan', 'like', $keyword)
                        ->orWhereHas('temuan', fn ($tq) => $tq->where('kode_temuan', 'like', $keyword)->orWhere('pernyataan', 'like', $keyword));
                })
                ->latest()
                ->limit(10)
                ->get();

            // 5. Jadwal RTM
            $rtm = JadwalRtm::with(['semester.tahunAkademik', 'notulenRtm'])
                ->where(function ($q) use ($keyword) {
                    $q->where('judul', 'like', $keyword)
                        ->orWhere('agenda', 'like', $keyword)
                        ->orWhere('lokasi', 'like', $keyword)
                        ->orWhereHas('notulenRtm', fn ($nq) => $nq->where('isi_notulen', 'like', $keyword));
                })
                ->latest('tanggal')
                ->limit(10)
                ->get();

            // 6. AMI
            $ami = Ami::with('tahunAkademik')
                ->whereHas('tahunAkademik', fn ($tq) => $tq->where('nama', 'like', $keyword))
                ->latest('tanggal_pelaksanaan')
                ->limit(10)
                ->get();
        }

        $totalResults = $ringkasan->count() + $evaluasi->count() + $temuan->count() + $rtl->count() + $rtm->count() + $ami->count();

        return view('search.index', compact(
            'query',
            'ringkasan',
            'evaluasi',
            'temuan',
            'rtl',
            'rtm',
            'ami',
            'totalResults'
        ));
    }
}
