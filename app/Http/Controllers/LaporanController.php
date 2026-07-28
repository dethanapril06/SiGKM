<?php

namespace App\Http\Controllers;

use App\Models\IndikatorKinerjaKegiatanSatuan;
use App\Models\IndikatorMutu;
use App\Models\JadwalMonev;
use App\Models\KeputusanRtm;
use App\Models\RencanaTindakLanjut;
use App\Models\Laporan;
use App\Models\Perkuliahan;
use App\Models\RingkasanPerkuliahan;
use App\Models\Semester;
use App\Services\LaporanPerkuliahanExcelService;
use App\Services\LaporanRtlExcelService;
use App\Services\LaporanRtmExcelService;
use App\Services\LaporanStandarMutuExcelService;
use App\Services\WorkflowNotificationService;
use App\Support\WorkflowStatus;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\RedirectResponse;
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
        private WorkflowNotificationService $notifications,
    ) {}

    public function perkuliahan(Request $request): View
    {
        return view('laporan.perkuliahan', $this->perkuliahanData($request));
    }

    public function exportPerkuliahan(Request $request): BinaryFileResponse|RedirectResponse
    {
        $data = $this->perkuliahanData($request);
        $laporan = $data['laporan'];

        if (! $laporan || $laporan->status !== WorkflowStatus::DIVERIFIKASI) {
            return back()->with('error', 'Laporan Pelaksanaan Perkuliahan belum diverifikasi. Unduh Excel hanya tersedia untuk laporan yang sudah diverifikasi.');
        }

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

    public function submitPerkuliahan(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'semester_id' => ['required', 'integer', 'exists:semesters,id'],
            'jadwal_monev_id' => ['required', 'integer', 'exists:jadwal_monevs,id'],
        ]);

        $jadwalMonev = JadwalMonev::with('termin')->findOrFail($validated['jadwal_monev_id']);

        $laporan = Laporan::updateOrCreate(
            [
                'jenis_laporan' => 'perkuliahan',
                'semester_id' => $validated['semester_id'],
                'jadwal_monev_id' => $validated['jadwal_monev_id'],
            ],
            [
                'judul' => 'Laporan Pelaksanaan Perkuliahan - '.($jadwalMonev->nama ?? 'Termin '.($jadwalMonev->termin?->nama ?? '')),
                'status' => WorkflowStatus::DIAJUKAN,
                'generated_by' => auth()->id(),
                'catatan_verifikasi' => null,
            ]
        );

        $this->notifications->sendToRole(
            'ketua-gkm',
            'Laporan Perkuliahan Menunggu Verifikasi',
            'Laporan Pelaksanaan Perkuliahan baru telah diajukan oleh '.(auth()->user()?->name ?? 'Anggota GKM').'.',
            route('verifikasi.index'),
            'bx-file',
            'warning',
        );

        return back()->with('success', 'Laporan Pelaksanaan Perkuliahan berhasil diajukan untuk verifikasi.');
    }

    public function verifikasiLaporan(Request $request, Laporan $laporan): RedirectResponse
    {
        if (auth()->user()?->role?->slug !== 'ketua-gkm') {
            abort(403, 'Hanya Ketua GKM yang dapat memverifikasi laporan.');
        }

        if ($laporan->status !== WorkflowStatus::DIAJUKAN) {
            return back()->with('error', 'Hanya laporan berstatus diajukan yang dapat diverifikasi.');
        }

        $laporan->update([
            'status' => WorkflowStatus::DIVERIFIKASI,
            'verified_by' => auth()->id(),
            'verified_at' => now(),
            'catatan_verifikasi' => null,
        ]);

        if ($laporan->jenis_laporan === 'perkuliahan' && $laporan->jadwal_monev_id) {
            RingkasanPerkuliahan::where('jadwal_monev_id', $laporan->jadwal_monev_id)
                ->update([
                    'status' => WorkflowStatus::DIVERIFIKASI,
                    'verified_by' => auth()->id(),
                    'verified_at' => now(),
                    'catatan_verifikasi' => null,
                ]);
        }

        if ($laporan->pembuat) {
            $this->notifications->sendToUser(
                $laporan->pembuat,
                'Laporan Diverifikasi',
                'Laporan '.$laporan->judul.' telah diverifikasi oleh Ketua GKM.',
                route('laporan.perkuliahan', ['semester_id' => $laporan->semester_id, 'jadwal_monev_id' => $laporan->jadwal_monev_id]),
                'bx-check-circle',
                'success',
            );
        }

        return back()->with('success', 'Laporan berhasil diverifikasi.');
    }

    public function tolakLaporan(Request $request, Laporan $laporan): RedirectResponse
    {
        if (auth()->user()?->role?->slug !== 'ketua-gkm') {
            abort(403, 'Hanya Ketua GKM yang dapat menolak laporan.');
        }

        if ($laporan->status !== WorkflowStatus::DIAJUKAN) {
            return back()->with('error', 'Hanya laporan berstatus diajukan yang dapat ditolak.');
        }

        $validated = $request->validate([
            'catatan_verifikasi' => ['required', 'string'],
        ], [
            'catatan_verifikasi.required' => 'Catatan penolakan wajib diisi.',
        ]);

        $laporan->update([
            'status' => WorkflowStatus::DITOLAK,
            'verified_by' => auth()->id(),
            'verified_at' => now(),
            'catatan_verifikasi' => $validated['catatan_verifikasi'],
        ]);

        if ($laporan->pembuat) {
            $this->notifications->sendToUser(
                $laporan->pembuat,
                'Laporan Perlu Perbaikan',
                'Laporan '.$laporan->judul.' perlu diperbaiki: '.$validated['catatan_verifikasi'],
                route('laporan.perkuliahan', ['semester_id' => $laporan->semester_id, 'jadwal_monev_id' => $laporan->jadwal_monev_id]),
                'bx-x-circle',
                'danger',
            );
        }

        return back()->with('success', 'Laporan berhasil ditolak.');
    }

    public function batalkanVerifikasi(Request $request, Laporan $laporan): RedirectResponse
    {
        if (auth()->user()?->role?->slug !== 'ketua-gkm') {
            abort(403, 'Hanya Ketua GKM yang dapat membatalkan verifikasi laporan.');
        }

        if ($laporan->status !== WorkflowStatus::DIVERIFIKASI) {
            return back()->with('error', 'Hanya laporan berstatus diverifikasi yang dapat dibatalkan.');
        }

        $laporan->update([
            'status' => WorkflowStatus::DIAJUKAN,
            'verified_by' => null,
            'verified_at' => null,
            'catatan_verifikasi' => 'Verifikasi dibatalkan oleh Ketua GKM untuk penyesuaian data.',
        ]);

        if ($laporan->jenis_laporan === 'perkuliahan' && $laporan->jadwal_monev_id) {
            RingkasanPerkuliahan::where('jadwal_monev_id', $laporan->jadwal_monev_id)
                ->update([
                    'status' => WorkflowStatus::DIAJUKAN,
                    'verified_by' => null,
                    'verified_at' => null,
                ]);
        }

        if ($laporan->pembuat) {
            $this->notifications->sendToUser(
                $laporan->pembuat,
                'Verifikasi Laporan Dibatalkan',
                'Verifikasi laporan '.$laporan->judul.' telah dibatalkan oleh Ketua GKM.',
                route('laporan.perkuliahan', ['semester_id' => $laporan->semester_id, 'jadwal_monev_id' => $laporan->jadwal_monev_id]),
                'bx-undo',
                'warning',
            );
        }

        return back()->with('success', 'Verifikasi laporan berhasil dibatalkan. Laporan kini dibuka kembali untuk penyesuaian data.');
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
        $laporan = null;
        $totalPerkuliahanCount = 0;
        $missingCount = 0;

        if ($selectedJadwalMonev && $selectedSemester) {
            $laporan = Laporan::with(['pembuat', 'verifikator'])
                ->where('jenis_laporan', 'perkuliahan')
                ->where('semester_id', $selectedSemester->id)
                ->where('jadwal_monev_id', $selectedJadwalMonev->id)
                ->first();

            $ringkasanPerkuliahan = RingkasanPerkuliahan::with([
                'perkuliahan.mataKuliah',
                'perkuliahan.kelas',
                'perkuliahan.pengajars.dosen',
            ])
                ->where('jadwal_monev_id', $selectedJadwalMonev->id)
                ->whereHas('perkuliahan', fn ($query) => $query->where('semester_id', $selectedSemester->id))
                ->get()
                ->sortBy(fn ($item) => mb_strtolower(
                    ($item->perkuliahan?->mataKuliah?->nama_mk ?? '').'|'.
                    ($item->perkuliahan?->kelas?->nama_kelas ?? '')
                ))
                ->values();

            $totalPerkuliahanCount = Perkuliahan::where('semester_id', $selectedSemester->id)
                ->where('status', 'aktif')
                ->count();

            $missingCount = max(0, $totalPerkuliahanCount - $ringkasanPerkuliahan->count());
        }

        return [
            'semesters' => $semesters,
            'jadwalMonevs' => $jadwalMonevs,
            'selectedSemester' => $selectedSemester,
            'selectedJadwalMonev' => $selectedJadwalMonev,
            'ringkasanPerkuliahan' => $ringkasanPerkuliahan,
            'laporan' => $laporan,
            'totalPerkuliahanCount' => $totalPerkuliahanCount,
            'missingCount' => $missingCount,
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
