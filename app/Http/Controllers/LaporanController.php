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
use Illuminate\Support\Collection;
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
                ->sortBy(function ($item) {
                    $mk = $item->perkuliahan?->mataKuliah;
                    $kode = strtoupper($mk?->kode_mk ?? '');
                    $isMku = str_starts_with($kode, 'MKU') ? 1 : 2;

                    return sprintf(
                        '%d|%s|%s|%s',
                        $isMku,
                        $kode,
                        mb_strtolower($mk?->nama_mk ?? ''),
                        mb_strtolower($item->perkuliahan?->kelas?->nama_kelas ?? '')
                    );
                })
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

        $rtl = collect();

        if ($selectedSemester) {
            $rtl = $this->buildRtlCollection($selectedSemester, $jenis);
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

    private function buildRtlCollection(Semester $selectedSemester, string $jenis): Collection
    {
        if ($jenis === 'fakultas') {
            $indikatorMutus = IndikatorMutu::with([
                'standarMutu',
                'evaluasiIndikators' => function ($query) use ($selectedSemester) {
                    $query->where('semester_id', $selectedSemester->id)
                        ->with([
                            'temuans.rencanaTindakLanjuts.buktiTindakLanjuts',
                        ]);
                },
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

            $rows = collect();

            foreach ($indikatorMutus as $indikator) {
                $evaluasi = $indikator->evaluasiIndikators->first();
                $temuans = $evaluasi?->temuans ?? collect();

                if ($temuans->isNotEmpty()) {
                    foreach ($temuans as $temuan) {
                        $rtls = $temuan->rencanaTindakLanjuts;
                        if ($rtls->isNotEmpty()) {
                            foreach ($rtls as $rtl) {
                                $rows->push((object) [
                                    'standar_id' => $indikator->standar_mutu_id,
                                    'standar_kode' => $indikator->standarMutu?->kode_standar ?? '-',
                                    'standar_nama' => $indikator->standarMutu?->nama_standar ?? '-',
                                    'indikator_kode' => $indikator->kode_indikator ?? '-',
                                    'indikator_isi' => $indikator->isi_indikator ?? '-',
                                    'temuan' => $temuan->pernyataan ?: '-',
                                    'rencana_awal' => $temuan->rencana_awal ?: '-',
                                    'realisasi' => $rtl->uraian_realisasi ?: '-',
                                    'catatan' => $rtl->catatan ?: '',
                                    'tindak_lanjut_text' => collect([
                                        $temuan->rencana_awal ? 'Rencana awal: '.$temuan->rencana_awal : null,
                                        $rtl->uraian_realisasi ? 'Realisasi: '.$rtl->uraian_realisasi : null,
                                        $rtl->catatan ? 'Catatan: '.$rtl->catatan : null,
                                    ])->filter()->join("\n") ?: '-',
                                    'penanggung_jawab' => $rtl->penanggung_jawab ?: ($temuan->nama_penanggung_jawab ?: '-'),
                                    'target_selesai' => $temuan->target_selesai ?: '-',
                                    'status' => $temuan->status ?: 'terbuka',
                                    'has_temuan' => true,
                                    'has_evidence' => $rtl->buktiTindakLanjuts->isNotEmpty(),
                                    'rtl_model' => $rtl,
                                    'temuan_model' => $temuan,
                                    'evaluatable' => $indikator,
                                ]);
                            }
                        } else {
                            $rows->push((object) [
                                'standar_id' => $indikator->standar_mutu_id,
                                'standar_kode' => $indikator->standarMutu?->kode_standar ?? '-',
                                'standar_nama' => $indikator->standarMutu?->nama_standar ?? '-',
                                'indikator_kode' => $indikator->kode_indikator ?? '-',
                                'indikator_isi' => $indikator->isi_indikator ?? '-',
                                'temuan' => $temuan->pernyataan ?: '-',
                                'rencana_awal' => $temuan->rencana_awal ?: '-',
                                'realisasi' => '-',
                                'catatan' => '',
                                'tindak_lanjut_text' => $temuan->rencana_awal ? 'Rencana awal: '.$temuan->rencana_awal : '-',
                                'penanggung_jawab' => $temuan->nama_penanggung_jawab ?: '-',
                                'target_selesai' => $temuan->target_selesai ?: '-',
                                'status' => $temuan->status ?: 'terbuka',
                                'has_temuan' => true,
                                'has_evidence' => false,
                                'rtl_model' => null,
                                'temuan_model' => $temuan,
                                'evaluatable' => $indikator,
                            ]);
                        }
                    }
                } else {
                    $rows->push((object) [
                        'standar_id' => $indikator->standar_mutu_id,
                        'standar_kode' => $indikator->standarMutu?->kode_standar ?? '-',
                        'standar_nama' => $indikator->standarMutu?->nama_standar ?? '-',
                        'indikator_kode' => $indikator->kode_indikator ?? '-',
                        'indikator_isi' => $indikator->isi_indikator ?? '-',
                        'temuan' => '-',
                        'rencana_awal' => '-',
                        'realisasi' => '-',
                        'catatan' => '',
                        'tindak_lanjut_text' => '-',
                        'penanggung_jawab' => '-',
                        'target_selesai' => '-',
                        'status' => '-',
                        'has_temuan' => false,
                        'has_evidence' => false,
                        'rtl_model' => null,
                        'temuan_model' => null,
                        'evaluatable' => $indikator,
                    ]);
                }
            }

            return $rows;
        }

        $ikksList = IndikatorKinerjaKegiatanSatuan::with([
            'indikatorKinerjaKegiatan.indikatorKinerjaUtama.sasaranStrategis',
            'evaluasiIndikators' => function ($query) use ($selectedSemester) {
                $query->where('semester_id', $selectedSemester->id)
                    ->with([
                        'temuans.rencanaTindakLanjuts.buktiTindakLanjuts',
                    ]);
            },
        ])
            ->where('is_active', true)
            ->get()
            ->sortBy(function ($item) {
                $ikk = $item->indikatorKinerjaKegiatan;
                $iku = $ikk?->indikatorKinerjaUtama;
                $sasaran = $iku?->sasaranStrategis;

                return collect([
                    $sasaran?->kode_sasaran ?? '',
                    $iku?->kode_iku ?? '',
                    $ikk?->kode_ikk ?? '',
                    $item->kode_ikks ?? '',
                ])->join('|');
            })
            ->values();

        $rows = collect();

        foreach ($ikksList as $ikks) {
            $ikk = $ikks->indikatorKinerjaKegiatan;
            $iku = $ikk?->indikatorKinerjaUtama;
            $sasaran = $iku?->sasaranStrategis;
            $evaluasi = $ikks->evaluasiIndikators->first();
            $temuans = $evaluasi?->temuans ?? collect();

            if ($temuans->isNotEmpty()) {
                foreach ($temuans as $temuan) {
                    $rtls = $temuan->rencanaTindakLanjuts;
                    if ($rtls->isNotEmpty()) {
                        foreach ($rtls as $rtl) {
                            $rows->push((object) [
                                'sasaran_id' => $sasaran?->id,
                                'sasaran_kode' => $sasaran?->kode_sasaran ?? '-',
                                'sasaran_uraian' => $sasaran?->uraian_sasaran ?? '-',
                                'sasaran_text' => collect([$sasaran?->kode_sasaran, $sasaran?->uraian_sasaran])->filter()->join(' - ') ?: '-',
                                'iku_kode' => $iku?->kode_iku ?? '-',
                                'iku_uraian' => $iku?->uraian_iku ?? '-',
                                'ikk_kode' => $ikk?->kode_ikk ?? '-',
                                'ikk_uraian' => $ikk?->uraian_ikk ?? '-',
                                'ikk_text' => collect([$ikk?->kode_ikk, $ikk?->uraian_ikk])->filter()->join(' - ') ?: '-',
                                'ikks_kode' => $ikks->kode_ikks ?? '-',
                                'ikks_uraian' => $ikks->uraian_ikks ?? '-',
                                'ikks_text' => collect([$ikks->kode_ikks, $ikks->uraian_ikks])->filter()->join(' - ') ?: '-',
                                'temuan' => $temuan->pernyataan ?: '-',
                                'rencana_awal' => $temuan->rencana_awal ?: '-',
                                'realisasi' => $rtl->uraian_realisasi ?: '-',
                                'catatan' => $rtl->catatan ?: '',
                                'tindak_lanjut_text' => collect([
                                    $temuan->rencana_awal ? 'Rencana awal: '.$temuan->rencana_awal : null,
                                    $rtl->uraian_realisasi ? 'Realisasi: '.$rtl->uraian_realisasi : null,
                                    $rtl->catatan ? 'Catatan: '.$rtl->catatan : null,
                                ])->filter()->join("\n") ?: '-',
                                'penanggung_jawab' => $rtl->penanggung_jawab ?: ($temuan->nama_penanggung_jawab ?: '-'),
                                'target_selesai' => $temuan->target_selesai ?: '-',
                                'status' => $temuan->status ?: 'terbuka',
                                'has_temuan' => true,
                                'has_evidence' => $rtl->buktiTindakLanjuts->isNotEmpty(),
                                'rtl_model' => $rtl,
                                'temuan_model' => $temuan,
                                'evaluatable' => $ikks,
                            ]);
                        }
                    } else {
                        $rows->push((object) [
                            'sasaran_id' => $sasaran?->id,
                            'sasaran_kode' => $sasaran?->kode_sasaran ?? '-',
                            'sasaran_uraian' => $sasaran?->uraian_sasaran ?? '-',
                            'sasaran_text' => collect([$sasaran?->kode_sasaran, $sasaran?->uraian_sasaran])->filter()->join(' - ') ?: '-',
                            'iku_kode' => $iku?->kode_iku ?? '-',
                            'iku_uraian' => $iku?->uraian_iku ?? '-',
                            'ikk_kode' => $ikk?->kode_ikk ?? '-',
                            'ikk_uraian' => $ikk?->uraian_ikk ?? '-',
                            'ikk_text' => collect([$ikk?->kode_ikk, $ikk?->uraian_ikk])->filter()->join(' - ') ?: '-',
                            'ikks_kode' => $ikks->kode_ikks ?? '-',
                            'ikks_uraian' => $ikks->uraian_ikks ?? '-',
                            'ikks_text' => collect([$ikks->kode_ikks, $ikks->uraian_ikks])->filter()->join(' - ') ?: '-',
                            'temuan' => $temuan->pernyataan ?: '-',
                            'rencana_awal' => $temuan->rencana_awal ?: '-',
                            'realisasi' => '-',
                            'catatan' => '',
                            'tindak_lanjut_text' => $temuan->rencana_awal ? 'Rencana awal: '.$temuan->rencana_awal : '-',
                            'penanggung_jawab' => $temuan->nama_penanggung_jawab ?: '-',
                            'target_selesai' => $temuan->target_selesai ?: '-',
                            'status' => $temuan->status ?: 'terbuka',
                            'has_temuan' => true,
                            'has_evidence' => false,
                            'rtl_model' => null,
                            'temuan_model' => $temuan,
                            'evaluatable' => $ikks,
                        ]);
                    }
                }
            } else {
                $rows->push((object) [
                    'sasaran_id' => $sasaran?->id,
                    'sasaran_kode' => $sasaran?->kode_sasaran ?? '-',
                    'sasaran_uraian' => $sasaran?->uraian_sasaran ?? '-',
                    'sasaran_text' => collect([$sasaran?->kode_sasaran, $sasaran?->uraian_sasaran])->filter()->join(' - ') ?: '-',
                    'iku_kode' => $iku?->kode_iku ?? '-',
                    'iku_uraian' => $iku?->uraian_iku ?? '-',
                    'ikk_kode' => $ikk?->kode_ikk ?? '-',
                    'ikk_uraian' => $ikk?->uraian_ikk ?? '-',
                    'ikk_text' => collect([$ikk?->kode_ikk, $ikk?->uraian_ikk])->filter()->join(' - ') ?: '-',
                    'ikks_kode' => $ikks->kode_ikks ?? '-',
                    'ikks_uraian' => $ikks->uraian_ikks ?? '-',
                    'ikks_text' => collect([$ikks->kode_ikks, $ikks->uraian_ikks])->filter()->join(' - ') ?: '-',
                    'temuan' => '-',
                    'rencana_awal' => '-',
                    'realisasi' => '-',
                    'catatan' => '',
                    'tindak_lanjut_text' => '-',
                    'penanggung_jawab' => '-',
                    'target_selesai' => '-',
                    'status' => '-',
                    'has_temuan' => false,
                    'has_evidence' => false,
                    'rtl_model' => null,
                    'temuan_model' => null,
                    'evaluatable' => $ikks,
                ]);
            }
        }

        return $rows;
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

        $keputusanRtm = collect();

        if ($selectedSemester) {
            $keputusanRtm = $this->buildRtmCollection($selectedSemester, $jenis);
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

    private function buildRtmCollection(Semester $selectedSemester, string $jenis): Collection
    {
        if ($jenis === 'fakultas') {
            $indikatorMutus = IndikatorMutu::with([
                'standarMutu',
                'evaluasiIndikators' => function ($query) use ($selectedSemester) {
                    $query->where('semester_id', $selectedSemester->id)
                        ->with([
                            'temuans.risikoTemuans.tingkatRisiko',
                            'temuans.rencanaTindakLanjuts',
                            'temuans.keputusanRtms.notulenRtm.jadwalRtm',
                        ]);
                },
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

            $rows = collect();

            foreach ($indikatorMutus as $indikator) {
                $evaluasi = $indikator->evaluasiIndikators->first();
                $temuans = $evaluasi?->temuans ?? collect();

                if ($temuans->isNotEmpty()) {
                    foreach ($temuans as $temuan) {
                        $risikoList = $temuan->risikoTemuans ?? collect();
                        $risikoText = $risikoList->pluck('deskripsi_risiko')->filter()->join('; ') ?: '-';
                        $dampakText = $risikoList->pluck('dampak_risiko')->filter()->join('; ') ?: '-';
                        $peringkatText = $risikoList->pluck('tingkatRisiko.nama_tingkat')->filter()->join('; ') ?: '-';

                        $keputusans = $temuan->keputusanRtms ?? collect();
                        $rtls = $temuan->rencanaTindakLanjuts ?? collect();

                        if ($keputusans->isNotEmpty()) {
                            foreach ($keputusans as $keputusan) {
                                $rtl = $keputusan->rencanaTindakLanjut ?? $rtls->first();
                                $rows->push((object) [
                                    'standar_id' => $indikator->standar_mutu_id,
                                    'standar_kode' => $indikator->standarMutu?->kode_standar ?? '-',
                                    'standar_nama' => $indikator->standarMutu?->nama_standar ?? '-',
                                    'indikator_kode' => $indikator->kode_indikator ?? '-',
                                    'indikator_isi' => $indikator->isi_indikator ?? '-',
                                    'temuan' => $temuan->pernyataan ?: '-',
                                    'risiko' => $risikoText,
                                    'dampak' => $dampakText,
                                    'peringkat' => $peringkatText,
                                    'keputusan_rtm' => $keputusan->uraian_keputusan ?: '-',
                                    'tindak_lanjut' => $rtl?->uraian_realisasi ?: ($temuan->rencana_awal ?: '-'),
                                    'strategi' => $keputusan->strategi ?: '-',
                                    'penanggung_jawab' => $rtl?->penanggung_jawab ?: ($temuan->nama_penanggung_jawab ?: '-'),
                                    'target_selesai' => $keputusan->target_selesai ?: ($temuan->target_selesai ?: '-'),
                                    'status' => $keputusan->status ?: ($temuan->status ?: 'terbuka'),
                                    'has_rtm' => true,
                                    'keputusan_model' => $keputusan,
                                    'temuan_model' => $temuan,
                                    'evaluatable' => $indikator,
                                ]);
                            }
                        } elseif ($rtls->isNotEmpty()) {
                            foreach ($rtls as $rtl) {
                                $rows->push((object) [
                                    'standar_id' => $indikator->standar_mutu_id,
                                    'standar_kode' => $indikator->standarMutu?->kode_standar ?? '-',
                                    'standar_nama' => $indikator->standarMutu?->nama_standar ?? '-',
                                    'indikator_kode' => $indikator->kode_indikator ?? '-',
                                    'indikator_isi' => $indikator->isi_indikator ?? '-',
                                    'temuan' => $temuan->pernyataan ?: '-',
                                    'risiko' => $risikoText,
                                    'dampak' => $dampakText,
                                    'peringkat' => $peringkatText,
                                    'keputusan_rtm' => '-',
                                    'tindak_lanjut' => $rtl->uraian_realisasi ?: ($temuan->rencana_awal ?: '-'),
                                    'strategi' => '-',
                                    'penanggung_jawab' => $rtl->penanggung_jawab ?: ($temuan->nama_penanggung_jawab ?: '-'),
                                    'target_selesai' => $temuan->target_selesai ?: '-',
                                    'status' => $temuan->status ?: 'terbuka',
                                    'has_rtm' => true,
                                    'keputusan_model' => null,
                                    'temuan_model' => $temuan,
                                    'evaluatable' => $indikator,
                                ]);
                            }
                        } else {
                            $rows->push((object) [
                                'standar_id' => $indikator->standar_mutu_id,
                                'standar_kode' => $indikator->standarMutu?->kode_standar ?? '-',
                                'standar_nama' => $indikator->standarMutu?->nama_standar ?? '-',
                                'indikator_kode' => $indikator->kode_indikator ?? '-',
                                'indikator_isi' => $indikator->isi_indikator ?? '-',
                                'temuan' => $temuan->pernyataan ?: '-',
                                'risiko' => $risikoText,
                                'dampak' => $dampakText,
                                'peringkat' => $peringkatText,
                                'keputusan_rtm' => '-',
                                'tindak_lanjut' => $temuan->rencana_awal ?: '-',
                                'strategi' => '-',
                                'penanggung_jawab' => $temuan->nama_penanggung_jawab ?: '-',
                                'target_selesai' => $temuan->target_selesai ?: '-',
                                'status' => $temuan->status ?: 'terbuka',
                                'has_rtm' => true,
                                'keputusan_model' => null,
                                'temuan_model' => $temuan,
                                'evaluatable' => $indikator,
                            ]);
                        }
                    }
                } else {
                    $rows->push((object) [
                        'standar_id' => $indikator->standar_mutu_id,
                        'standar_kode' => $indikator->standarMutu?->kode_standar ?? '-',
                        'standar_nama' => $indikator->standarMutu?->nama_standar ?? '-',
                        'indikator_kode' => $indikator->kode_indikator ?? '-',
                        'indikator_isi' => $indikator->isi_indikator ?? '-',
                        'temuan' => '-',
                        'risiko' => '-',
                        'dampak' => '-',
                        'peringkat' => '-',
                        'keputusan_rtm' => '-',
                        'tindak_lanjut' => '-',
                        'strategi' => '-',
                        'penanggung_jawab' => '-',
                        'target_selesai' => '-',
                        'status' => '-',
                        'has_rtm' => false,
                        'keputusan_model' => null,
                        'temuan_model' => null,
                        'evaluatable' => $indikator,
                    ]);
                }
            }

            return $rows;
        }

        $ikksList = IndikatorKinerjaKegiatanSatuan::with([
            'indikatorKinerjaKegiatan.indikatorKinerjaUtama.sasaranStrategis',
            'evaluasiIndikators' => function ($query) use ($selectedSemester) {
                $query->where('semester_id', $selectedSemester->id)
                    ->with([
                        'temuans.risikoTemuans.tingkatRisiko',
                        'temuans.rencanaTindakLanjuts',
                        'temuans.keputusanRtms.notulenRtm.jadwalRtm',
                    ]);
            },
        ])
            ->where('is_active', true)
            ->get()
            ->sortBy(function ($item) {
                $ikk = $item->indikatorKinerjaKegiatan;
                $iku = $ikk?->indikatorKinerjaUtama;
                $sasaran = $iku?->sasaranStrategis;

                return collect([
                    $sasaran?->kode_sasaran ?? '',
                    $iku?->kode_iku ?? '',
                    $ikk?->kode_ikk ?? '',
                    $item->kode_ikks ?? '',
                ])->join('|');
            })
            ->values();

        $rows = collect();

        foreach ($ikksList as $ikks) {
            $ikk = $ikks->indikatorKinerjaKegiatan;
            $iku = $ikk?->indikatorKinerjaUtama;
            $sasaran = $iku?->sasaranStrategis;
            $evaluasi = $ikks->evaluasiIndikators->first();
            $temuans = $evaluasi?->temuans ?? collect();

            if ($temuans->isNotEmpty()) {
                foreach ($temuans as $temuan) {
                    $risikoList = $temuan->risikoTemuans ?? collect();
                    $risikoText = $risikoList->pluck('deskripsi_risiko')->filter()->join('; ') ?: '-';
                    $dampakText = $risikoList->pluck('dampak_risiko')->filter()->join('; ') ?: '-';
                    $peringkatText = $risikoList->pluck('tingkatRisiko.nama_tingkat')->filter()->join('; ') ?: '-';

                    $keputusans = $temuan->keputusanRtms ?? collect();
                    $rtls = $temuan->rencanaTindakLanjuts ?? collect();

                    if ($keputusans->isNotEmpty()) {
                        foreach ($keputusans as $keputusan) {
                            $rtl = $keputusan->rencanaTindakLanjut ?? $rtls->first();
                            $rows->push((object) [
                                'sasaran_id' => $sasaran?->id,
                                'sasaran_kode' => $sasaran?->kode_sasaran ?? '-',
                                'sasaran_uraian' => $sasaran?->uraian_sasaran ?? '-',
                                'sasaran_text' => collect([$sasaran?->kode_sasaran, $sasaran?->uraian_sasaran])->filter()->join(' - ') ?: '-',
                                'iku_kode' => $iku?->kode_iku ?? '-',
                                'iku_uraian' => $iku?->uraian_iku ?? '-',
                                'ikk_kode' => $ikk?->kode_ikk ?? '-',
                                'ikk_uraian' => $ikk?->uraian_ikk ?? '-',
                                'ikk_text' => collect([$ikk?->kode_ikk, $ikk?->uraian_ikk])->filter()->join(' - ') ?: '-',
                                'ikks_kode' => $ikks->kode_ikks ?? '-',
                                'ikks_uraian' => $ikks->uraian_ikks ?? '-',
                                'ikks_text' => collect([$ikks->kode_ikks, $ikks->uraian_ikks])->filter()->join(' - ') ?: '-',
                                'temuan' => $temuan->pernyataan ?: '-',
                                'risiko' => $risikoText,
                                'dampak' => $dampakText,
                                'peringkat' => $peringkatText,
                                'keputusan_rtm' => $keputusan->uraian_keputusan ?: '-',
                                'tindak_lanjut' => $rtl?->uraian_realisasi ?: ($temuan->rencana_awal ?: '-'),
                                'strategi' => $keputusan->strategi ?: '-',
                                'penanggung_jawab' => $rtl?->penanggung_jawab ?: ($temuan->nama_penanggung_jawab ?: '-'),
                                'target_selesai' => $keputusan->target_selesai ?: ($temuan->target_selesai ?: '-'),
                                'status' => $keputusan->status ?: ($temuan->status ?: 'terbuka'),
                                'has_rtm' => true,
                                'keputusan_model' => $keputusan,
                                'temuan_model' => $temuan,
                                'evaluatable' => $ikks,
                            ]);
                        }
                    } elseif ($rtls->isNotEmpty()) {
                        foreach ($rtls as $rtl) {
                            $rows->push((object) [
                                'sasaran_id' => $sasaran?->id,
                                'sasaran_kode' => $sasaran?->kode_sasaran ?? '-',
                                'sasaran_uraian' => $sasaran?->uraian_sasaran ?? '-',
                                'sasaran_text' => collect([$sasaran?->kode_sasaran, $sasaran?->uraian_sasaran])->filter()->join(' - ') ?: '-',
                                'iku_kode' => $iku?->kode_iku ?? '-',
                                'iku_uraian' => $iku?->uraian_iku ?? '-',
                                'ikk_kode' => $ikk?->kode_ikk ?? '-',
                                'ikk_uraian' => $ikk?->uraian_ikk ?? '-',
                                'ikk_text' => collect([$ikk?->kode_ikk, $ikk?->uraian_ikk])->filter()->join(' - ') ?: '-',
                                'ikks_kode' => $ikks->kode_ikks ?? '-',
                                'ikks_uraian' => $ikks->uraian_ikks ?? '-',
                                'ikks_text' => collect([$ikks->kode_ikks, $ikks->uraian_ikks])->filter()->join(' - ') ?: '-',
                                'temuan' => $temuan->pernyataan ?: '-',
                                'risiko' => $risikoText,
                                'dampak' => $dampakText,
                                'peringkat' => $peringkatText,
                                'keputusan_rtm' => '-',
                                'tindak_lanjut' => $rtl->uraian_realisasi ?: ($temuan->rencana_awal ?: '-'),
                                'strategi' => '-',
                                'penanggung_jawab' => $rtl->penanggung_jawab ?: ($temuan->nama_penanggung_jawab ?: '-'),
                                'target_selesai' => $temuan->target_selesai ?: '-',
                                'status' => $temuan->status ?: 'terbuka',
                                'has_rtm' => true,
                                'keputusan_model' => null,
                                'temuan_model' => $temuan,
                                'evaluatable' => $ikks,
                            ]);
                        }
                    } else {
                        $rows->push((object) [
                            'sasaran_id' => $sasaran?->id,
                            'sasaran_kode' => $sasaran?->kode_sasaran ?? '-',
                            'sasaran_uraian' => $sasaran?->uraian_sasaran ?? '-',
                            'sasaran_text' => collect([$sasaran?->kode_sasaran, $sasaran?->uraian_sasaran])->filter()->join(' - ') ?: '-',
                            'iku_kode' => $iku?->kode_iku ?? '-',
                            'iku_uraian' => $iku?->uraian_iku ?? '-',
                            'ikk_kode' => $ikk?->kode_ikk ?? '-',
                            'ikk_uraian' => $ikk?->uraian_ikk ?? '-',
                            'ikk_text' => collect([$ikk?->kode_ikk, $ikk?->uraian_ikk])->filter()->join(' - ') ?: '-',
                            'ikks_kode' => $ikks->kode_ikks ?? '-',
                            'ikks_uraian' => $ikks->uraian_ikks ?? '-',
                            'ikks_text' => collect([$ikks->kode_ikks, $ikks->uraian_ikks])->filter()->join(' - ') ?: '-',
                            'temuan' => $temuan->pernyataan ?: '-',
                            'risiko' => $risikoText,
                            'dampak' => $dampakText,
                            'peringkat' => $peringkatText,
                            'keputusan_rtm' => '-',
                            'tindak_lanjut' => $temuan->rencana_awal ?: '-',
                            'strategi' => '-',
                            'penanggung_jawab' => $temuan->nama_penanggung_jawab ?: '-',
                            'target_selesai' => $temuan->target_selesai ?: '-',
                            'status' => $temuan->status ?: 'terbuka',
                            'has_rtm' => true,
                            'keputusan_model' => null,
                            'temuan_model' => $temuan,
                            'evaluatable' => $ikks,
                        ]);
                    }
                }
            } else {
                $rows->push((object) [
                    'sasaran_id' => $sasaran?->id,
                    'sasaran_kode' => $sasaran?->kode_sasaran ?? '-',
                    'sasaran_uraian' => $sasaran?->uraian_sasaran ?? '-',
                    'sasaran_text' => collect([$sasaran?->kode_sasaran, $sasaran?->uraian_sasaran])->filter()->join(' - ') ?: '-',
                    'iku_kode' => $iku?->kode_iku ?? '-',
                    'iku_uraian' => $iku?->uraian_iku ?? '-',
                    'ikk_kode' => $ikk?->kode_ikk ?? '-',
                    'ikk_uraian' => $ikk?->uraian_ikk ?? '-',
                    'ikk_text' => collect([$ikk?->kode_ikk, $ikk?->uraian_ikk])->filter()->join(' - ') ?: '-',
                    'ikks_kode' => $ikks->kode_ikks ?? '-',
                    'ikks_uraian' => $ikks->uraian_ikks ?? '-',
                    'ikks_text' => collect([$ikks->kode_ikks, $ikks->uraian_ikks])->filter()->join(' - ') ?: '-',
                    'temuan' => '-',
                    'risiko' => '-',
                    'dampak' => '-',
                    'peringkat' => '-',
                    'keputusan_rtm' => '-',
                    'tindak_lanjut' => '-',
                    'strategi' => '-',
                    'penanggung_jawab' => '-',
                    'target_selesai' => '-',
                    'status' => '-',
                    'has_rtm' => false,
                    'keputusan_model' => null,
                    'temuan_model' => null,
                    'evaluatable' => $ikks,
                ]);
            }
        }

        return $rows;
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
