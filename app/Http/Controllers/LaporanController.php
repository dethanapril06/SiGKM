<?php

namespace App\Http\Controllers;

use App\Models\IndikatorKinerjaKegiatanSatuan;
use App\Models\IndikatorMutu;
use App\Models\JadwalMonev;
use App\Models\KeputusanRtm;
use App\Models\RencanaTindakLanjut;
use App\Models\RingkasanPerkuliahan;
use App\Models\Semester;
use App\Services\LaporanPerkuliahanExcelService;
use App\Services\LaporanRtlExcelService;
use App\Services\LaporanRtmExcelService;
use App\Services\LaporanStandarMutuExcelService;
use App\Support\WorkflowStatus;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LaporanController extends Controller
{
    public function __construct(
        private LaporanPerkuliahanExcelService $excelService,
        private LaporanStandarMutuExcelService $standarMutuExcelService,
        private LaporanRtlExcelService $rtlExcelService,
        private LaporanRtmExcelService $rtmExcelService,
    ) {}

    public function perkuliahan(Request $request): View
    {
        return view('laporan.perkuliahan', $this->perkuliahanData($request));
    }

    public function exportPerkuliahan(Request $request): BinaryFileResponse
    {
        $data = $this->perkuliahanData($request);
        $semester = $data['selectedSemester'];

        $path = $this->excelService->generate(
            $data['ringkasanPerkuliahan'],
            $semester?->nama,
            $semester?->tahunAkademik?->nama,
            $data['programStudi'],
            $data['tanggalLaporan'],
        );

        $filename = 'Laporan Pelaksanaan Perkuliahan - '.
            ($semester?->label ?? 'Tanpa Semester').'.xlsx';
        $filename = str_replace(['/', '\\'], '-', $filename);

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    public function standarMutu(Request $request): View
    {
        return view('laporan.standar-mutu', $this->standarMutuData($request));
    }

    public function exportStandarMutu(Request $request): BinaryFileResponse
    {
        $data = $this->standarMutuData($request);
        $semester = $data['selectedSemester'];

        $path = $this->standarMutuExcelService->generate(
            $data['indikatorMutu'],
            $semester?->nama,
            $semester?->tahunAkademik?->nama,
            $data['fakultas'],
            $data['tanggalLaporan'],
        );

        $filename = 'Laporan Evaluasi Standar Mutu Fakultas - '.
            ($semester?->label ?? 'Tanpa Semester').'.xlsx';
        $filename = str_replace(['/', '\\'], '-', $filename);

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    public function rtlFakultas(Request $request): View
    {
        return view('laporan.rtl', $this->rtlData($request, 'fakultas'));
    }

    public function exportRtlFakultas(Request $request): BinaryFileResponse
    {
        $data = $this->rtlData($request, 'fakultas');
        $semester = $data['selectedSemester'];

        $path = $this->rtlExcelService->generateFakultas(
            $data['rtl'],
            $semester?->nama,
            $semester?->tahunAkademik?->nama,
            $data['fakultas'],
            $data['tanggalLaporan'],
        );

        $filename = 'Laporan RTL Fakultas - '.($semester?->label ?? 'Tanpa Semester').'.xlsx';
        $filename = str_replace(['/', '\\'], '-', $filename);

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    public function rtlProdi(Request $request): View
    {
        return view('laporan.rtl', $this->rtlData($request, 'prodi'));
    }

    public function exportRtlProdi(Request $request): BinaryFileResponse
    {
        $data = $this->rtlData($request, 'prodi');
        $semester = $data['selectedSemester'];

        $path = $this->rtlExcelService->generateProdi(
            $data['rtl'],
            $semester?->nama,
            $semester?->tahunAkademik?->nama,
            $data['programStudi'],
            $data['tanggalLaporan'],
        );

        $filename = 'Laporan RTL Prodi - '.($semester?->label ?? 'Tanpa Semester').'.xlsx';
        $filename = str_replace(['/', '\\'], '-', $filename);

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    public function rtmFakultas(Request $request): View
    {
        return view('laporan.rtm', $this->rtmData($request, 'fakultas'));
    }

    public function exportRtmFakultas(Request $request): BinaryFileResponse
    {
        $data = $this->rtmData($request, 'fakultas');
        $semester = $data['selectedSemester'];

        $path = $this->rtmExcelService->generateFakultas(
            $data['keputusanRtm'],
            $semester?->nama,
            $semester?->tahunAkademik?->nama,
            $data['fakultas'],
            $data['tanggalLaporan'],
        );

        $filename = 'Laporan RTM Fakultas - '.($semester?->label ?? 'Tanpa Semester').'.xlsx';
        $filename = str_replace(['/', '\\'], '-', $filename);

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    public function rtmProdi(Request $request): View
    {
        return view('laporan.rtm', $this->rtmData($request, 'prodi'));
    }

    public function exportRtmProdi(Request $request): BinaryFileResponse
    {
        $data = $this->rtmData($request, 'prodi');
        $semester = $data['selectedSemester'];

        $path = $this->rtmExcelService->generateProdi(
            $data['keputusanRtm'],
            $semester?->nama,
            $semester?->tahunAkademik?->nama,
            $data['programStudi'],
            $data['tanggalLaporan'],
        );

        $filename = 'Laporan RTM Prodi - '.($semester?->label ?? 'Tanpa Semester').'.xlsx';
        $filename = str_replace(['/', '\\'], '-', $filename);

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    private function perkuliahanData(Request $request): array
    {
        $request->validate([
            'semester_id' => ['nullable', 'integer', 'exists:semesters,id'],
            'jadwal_monev_id' => ['nullable', 'integer', 'exists:jadwal_monevs,id'],
        ]);

        $semesters = Semester::with('tahunAkademik')->orderByDesc('tanggal_mulai')->get();
        $selectedSemester = $semesters->firstWhere('id', $request->integer('semester_id'))
            ?? $semesters->firstWhere('is_active', true)
            ?? $semesters->first();

        $jadwalMonevs = collect();
        $selectedJadwalMonev = null;

        if ($selectedSemester) {
            $jadwalMonevs = JadwalMonev::with('termin')
                ->where('semester_id', $selectedSemester->id)
                ->orderByDesc('tanggal_mulai')
                ->get();
            $selectedJadwalMonev = $jadwalMonevs->firstWhere('id', $request->integer('jadwal_monev_id'))
                ?? $jadwalMonevs->first();
        }

        $ringkasanPerkuliahan = collect();

        if ($selectedJadwalMonev) {
            $ringkasanPerkuliahan = RingkasanPerkuliahan::with([
                'perkuliahan.mataKuliah',
                'perkuliahan.kelas',
                'perkuliahan.pengajars.dosen',
            ])
                ->where('jadwal_monev_id', $selectedJadwalMonev->id)
                ->where('status', 'diverifikasi')
                ->whereHas('perkuliahan', fn ($query) => $query->where('semester_id', $selectedSemester->id))
                ->get()
                ->sortBy(fn ($item) => mb_strtolower(
                    ($item->perkuliahan?->mataKuliah?->nama_mk ?? '').'|'.
                    ($item->perkuliahan?->kelas?->nama_kelas ?? '')
                ))
                ->values();
        }

        return [
            'semesters' => $semesters,
            'jadwalMonevs' => $jadwalMonevs,
            'selectedSemester' => $selectedSemester,
            'selectedJadwalMonev' => $selectedJadwalMonev,
            'ringkasanPerkuliahan' => $ringkasanPerkuliahan,
            'programStudi' => config('sigkm.program_studi'),
            'tanggalLaporan' => now(),
        ];
    }

    private function rtlData(Request $request, string $jenis): array
    {
        $request->validate([
            'semester_id' => ['nullable', 'integer', 'exists:semesters,id'],
        ]);

        $semesters = Semester::with('tahunAkademik')->orderByDesc('tanggal_mulai')->get();
        $selectedSemester = $semesters->firstWhere('id', $request->integer('semester_id'))
            ?? $semesters->firstWhere('is_active', true)
            ?? $semesters->first();

        $evaluatableType = $jenis === 'fakultas' ? 'indikator_mutu' : 'ikks';
        $rtl = collect();

        if ($selectedSemester) {
            $rtl = RencanaTindakLanjut::with([
                'buktiTindakLanjuts',
                'temuan.evaluasiIndikator.semester.tahunAkademik',
                'temuan.evaluasiIndikator.evaluatable' => function (MorphTo $morphTo) {
                    $morphTo->morphWith([
                        IndikatorMutu::class => ['standarMutu'],
                        IndikatorKinerjaKegiatanSatuan::class => [
                            'indikatorKinerjaKegiatan.indikatorKinerjaUtama.sasaranStrategis',
                        ],
                    ]);
                },
            ])
                ->whereHas('temuan.evaluasiIndikator', function ($query) use ($selectedSemester, $evaluatableType) {
                    $query
                        ->where('semester_id', $selectedSemester->id)
                        ->where('evaluatable_type', $evaluatableType);
                })
                ->get()
                ->sortBy(fn ($item) => $this->rtlSortKey($item, $jenis))
                ->values();
        }

        return [
            'semesters' => $semesters,
            'selectedSemester' => $selectedSemester,
            'rtl' => $rtl,
            'jenis' => $jenis,
            'fakultas' => config('sigkm.fakultas', 'FAKULTAS SAINS DAN TEKNIK'),
            'programStudi' => config('sigkm.program_studi'),
            'tanggalLaporan' => now(),
        ];
    }

    private function rtmData(Request $request, string $jenis): array
    {
        $request->validate([
            'semester_id' => ['nullable', 'integer', 'exists:semesters,id'],
        ]);

        $semesters = Semester::with('tahunAkademik')->orderByDesc('tanggal_mulai')->get();
        $selectedSemester = $semesters->firstWhere('id', $request->integer('semester_id'))
            ?? $semesters->firstWhere('is_active', true)
            ?? $semesters->first();

        $evaluatableType = $jenis === 'fakultas' ? 'indikator_mutu' : 'ikks';
        $keputusanRtm = collect();

        if ($selectedSemester) {
            $keputusanRtm = KeputusanRtm::with([
                'notulenRtm.jadwalRtm.semester.tahunAkademik',
                'temuan.risikoTemuans.tingkatRisiko',
                'temuan.rencanaTindakLanjuts',
                'temuan.evaluasiIndikator.evaluatable' => function (MorphTo $morphTo) {
                    $morphTo->morphWith([
                        IndikatorMutu::class => ['standarMutu'],
                        IndikatorKinerjaKegiatanSatuan::class => [
                            'indikatorKinerjaKegiatan.indikatorKinerjaUtama.sasaranStrategis',
                        ],
                    ]);
                },
            ])
                ->whereHas('notulenRtm.jadwalRtm', fn ($query) => $query->where('semester_id', $selectedSemester->id))
                ->whereHas('temuan.evaluasiIndikator', fn ($query) => $query->where('evaluatable_type', $evaluatableType))
                ->get()
                ->sortBy(fn ($item) => $this->rtmSortKey($item, $jenis))
                ->values();
        }

        return [
            'semesters' => $semesters,
            'selectedSemester' => $selectedSemester,
            'keputusanRtm' => $keputusanRtm,
            'jenis' => $jenis,
            'fakultas' => config('sigkm.fakultas', 'FAKULTAS SAINS DAN TEKNIK'),
            'programStudi' => config('sigkm.program_studi'),
            'tanggalLaporan' => now(),
        ];
    }

    private function rtlSortKey(RencanaTindakLanjut $rtl, string $jenis): string
    {
        $evaluatable = $rtl->temuan?->evaluasiIndikator?->evaluatable;

        if ($jenis === 'fakultas' && $evaluatable instanceof IndikatorMutu) {
            return sprintf(
                '%06d|%06d|%s',
                $evaluatable->standar_mutu_id ?? 0,
                (int) preg_replace('/\D+/', '', $evaluatable->kode_indikator ?? '0'),
                $rtl->temuan?->kode_temuan ?? ''
            );
        }

        if ($evaluatable instanceof IndikatorKinerjaKegiatanSatuan) {
            $ikk = $evaluatable->indikatorKinerjaKegiatan;
            $iku = $ikk?->indikatorKinerjaUtama;
            $sasaran = $iku?->sasaranStrategis;

            return collect([
                $sasaran?->kode_sasaran,
                $iku?->kode_iku,
                $ikk?->kode_ikk,
                $evaluatable->kode_ikks,
                $rtl->temuan?->kode_temuan,
            ])->filter()->join('|');
        }

        return $rtl->temuan?->kode_temuan ?? '';
    }

    private function rtmSortKey(KeputusanRtm $keputusanRtm, string $jenis): string
    {
        $temuan = $keputusanRtm->temuan;
        $evaluatable = $temuan?->evaluasiIndikator?->evaluatable;

        if ($jenis === 'fakultas' && $evaluatable instanceof IndikatorMutu) {
            return sprintf(
                '%06d|%06d|%s',
                $evaluatable->standar_mutu_id ?? 0,
                (int) preg_replace('/\D+/', '', $evaluatable->kode_indikator ?? '0'),
                $temuan?->kode_temuan ?? ''
            );
        }

        if ($evaluatable instanceof IndikatorKinerjaKegiatanSatuan) {
            $ikk = $evaluatable->indikatorKinerjaKegiatan;
            $iku = $ikk?->indikatorKinerjaUtama;
            $sasaran = $iku?->sasaranStrategis;

            return collect([
                $sasaran?->kode_sasaran,
                $iku?->kode_iku,
                $ikk?->kode_ikk,
                $evaluatable->kode_ikks,
                $temuan?->kode_temuan,
            ])->filter()->join('|');
        }

        return $temuan?->kode_temuan ?? '';
    }

    private function standarMutuData(Request $request): array
    {
        $request->validate([
            'semester_id' => ['nullable', 'integer', 'exists:semesters,id'],
        ]);

        $semesters = Semester::with('tahunAkademik')->orderByDesc('tanggal_mulai')->get();
        $selectedSemester = $semesters->firstWhere('id', $request->integer('semester_id'))
            ?? $semesters->firstWhere('is_active', true)
            ?? $semesters->first();

        $indikatorMutu = collect();

        if ($selectedSemester) {
            $indikatorMutu = IndikatorMutu::with([
                'standarMutu',
                'evaluasiIndikators' => fn ($query) => $query
                    ->where('semester_id', $selectedSemester->id)
                    ->with('temuans.rencanaTindakLanjuts'),
            ])
                ->active()
                ->get()
                ->sortBy(fn ($item) => sprintf(
                    '%06d|%06d|%s',
                    $item->standar_mutu_id ?? 0,
                    (int) preg_replace('/\D+/', '', $item->kode_indikator ?? '0'),
                    $item->kode_indikator ?? ''
                ))
                ->values();
        }

        return [
            'semesters' => $semesters,
            'selectedSemester' => $selectedSemester,
            'indikatorMutu' => $indikatorMutu,
            'fakultas' => config('sigkm.fakultas', 'FAKULTAS SAINS DAN TEKNIK'),
            'tanggalLaporan' => now(),
        ];
    }
}
