<?php

namespace App\Http\Controllers;

use App\Models\JadwalMonev;
use App\Models\Laporan;
use App\Models\Perkuliahan;
use App\Models\RingkasanPerkuliahan;
use App\Models\Semester;
use App\Models\Termin;
use App\Services\SiakadSyncService;
use App\Services\WorkflowNotificationService;
use App\Support\WorkflowStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class RingkasanPerkuliahanController extends Controller
{
    public function __construct(private WorkflowNotificationService $notifications) {}

    public function index(Request $request): View
    {
        $user = auth()->user();

        $selectedSemester = $request->query('semester_id');
        $selectedTermin = $request->query('termin_id');
        $selectedKesesuaian = $request->query('kesesuaian_materi');
        $selectedStatus = $request->query('status');

        $semesters = Semester::with('tahunAkademik')->orderByDesc('tanggal_mulai')->get();
        $termins = Termin::orderBy('nama_termin')->get();

        $ringkasanPerkuliahan = RingkasanPerkuliahan::with([
            'jadwalMonev.semester.tahunAkademik',
            'jadwalMonev.termin',
            'perkuliahan.mataKuliah',
            'perkuliahan.kelas',
            'perkuliahan.semester.tahunAkademik',
            'perkuliahan.pengajars.dosen',
            'penginput',
            'verifikator',
        ])
            ->when($selectedSemester, function ($query) use ($selectedSemester) {
                $query->whereHas('jadwalMonev', function ($q) use ($selectedSemester) {
                    $q->where('semester_id', $selectedSemester);
                });
            })
            ->when($selectedTermin, function ($query) use ($selectedTermin) {
                $query->whereHas('jadwalMonev', function ($q) use ($selectedTermin) {
                    $q->where('termin_id', $selectedTermin);
                });
            })
            ->when($selectedKesesuaian, function ($query) use ($selectedKesesuaian) {
                $query->where('kesesuaian_materi', $selectedKesesuaian);
            })
            ->when($selectedStatus, function ($query) use ($selectedStatus) {
                $query->where('status', $selectedStatus);
            })
            ->when($user->hasRole('anggota-gkm'), function ($query) use ($user) {
                $query->where('input_by', $user->id);
            })
            ->when($user->hasRole('koordinator-prodi'), function ($query) {
                $query->whereIn('status', ['diajukan', 'diverifikasi', 'ditolak']);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $jadwalMonevs = JadwalMonev::with(['semester.tahunAkademik', 'termin'])
            ->where('status', 'aktif')
            ->latest()
            ->get();
        if ($jadwalMonevs->isEmpty()) {
            $jadwalMonevs = JadwalMonev::with(['semester.tahunAkademik', 'termin'])->latest()->take(5)->get();
        }

        return view('monev.ringkasan-perkuliahan.index', compact(
            'ringkasanPerkuliahan',
            'semesters',
            'termins',
            'jadwalMonevs',
            'selectedSemester',
            'selectedTermin',
            'selectedKesesuaian',
            'selectedStatus'
        ));
    }

    public function create(): View
    {
        $jadwalMonev = JadwalMonev::with(['semester.tahunAkademik', 'termin'])
            ->where('status', 'aktif')
            ->latest()
            ->get();

        $perkuliahan = Perkuliahan::with([
            'semester.tahunAkademik',
            'mataKuliah',
            'kelas',
            'pengajars.dosen',
        ])
            ->where('status', 'aktif')
            ->latest()
            ->get();

        return view('monev.ringkasan-perkuliahan.create', compact(
            'jadwalMonev',
            'perkuliahan'
        ));
    }

    public function show(RingkasanPerkuliahan $ringkasanPerkuliahan): View
    {
        $ringkasanPerkuliahan->load([
            'jadwalMonev.semester.tahunAkademik',
            'jadwalMonev.termin',
            'perkuliahan.mataKuliah',
            'perkuliahan.kelas',
            'perkuliahan.pengajars.dosen',
            'penginput',
            'verifikator',
        ]);

        $user = auth()->user();
        $isOwner = (int) $ringkasanPerkuliahan->input_by === (int) $user->id;
        $isPublished = $ringkasanPerkuliahan->status !== 'draft';
        $visible = $user->hasRole('ketua-gkm')
            || $isOwner
            || ($user->hasAnyRole(['ketua-gkm', 'koordinator-prodi']) && $isPublished);
        abort_unless($visible, 403);

        return view('monev.ringkasan-perkuliahan.show', compact('ringkasanPerkuliahan'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'jadwal_monev_id' => ['required', 'exists:jadwal_monevs,id'],
            'perkuliahan_id' => ['required', 'exists:perkuliahans,id'],
            'jumlah_pertemuan' => ['required', 'integer', 'min:0', 'max:30'],
            'kesesuaian_materi' => ['required', 'in:sesuai,sebagian,tidak_sesuai'],
            'kontrak_kuliah' => ['required', 'in:ada,tidak_ada'],
            'metode_pembelajaran' => ['nullable', 'string'],
            'keterangan' => ['nullable', 'string'],
        ], [
            'jadwal_monev_id.required' => 'Jadwal monev wajib dipilih.',
            'perkuliahan_id.required' => 'Perkuliahan wajib dipilih.',
            'jumlah_pertemuan.required' => 'Jumlah pertemuan wajib diisi.',
            'kesesuaian_materi.required' => 'Kesesuaian materi wajib dipilih.',
            'kontrak_kuliah.required' => 'Kontrak kuliah wajib dipilih.',
        ]);

        $laporanTerbukti = Laporan::where('jenis_laporan', 'perkuliahan')
            ->where('jadwal_monev_id', $validated['jadwal_monev_id'])
            ->first();

        if ($laporanTerbukti && $laporanTerbukti->status === WorkflowStatus::DIVERIFIKASI) {
            throw ValidationException::withMessages([
                'jadwal_monev_id' => 'Laporan Pelaksanaan Perkuliahan untuk periode ini telah diverifikasi dan dikunci. Data baru tidak dapat ditambahkan.',
            ]);
        }

        $isDuplicate = RingkasanPerkuliahan::query()
            ->where('jadwal_monev_id', $validated['jadwal_monev_id'])
            ->where('perkuliahan_id', $validated['perkuliahan_id'])
            ->exists();

        if ($isDuplicate) {
            throw ValidationException::withMessages([
                'perkuliahan_id' => 'Ringkasan untuk jadwal monev dan perkuliahan tersebut sudah tersedia.',
            ]);
        }

        $validated['status'] = 'diajukan';
        $validated['input_by'] = auth()->id();

        RingkasanPerkuliahan::create($validated);

        return redirect()
            ->route('ringkasan-perkuliahan.index')
            ->with('success', 'Ringkasan perkuliahan berhasil ditambahkan.');
    }

    public function edit(RingkasanPerkuliahan $ringkasanPerkuliahan): View
    {
        if (! $ringkasanPerkuliahan->canBeEditedBy(auth()->user())) {
            abort(403, 'Ringkasan ini tidak dapat diedit.');
        }

        $jadwalMonev = JadwalMonev::with(['semester.tahunAkademik', 'termin'])
            ->latest()
            ->get();

        $perkuliahan = Perkuliahan::with([
            'semester.tahunAkademik',
            'mataKuliah',
            'kelas',
            'pengajars.dosen',
        ])
            ->latest()
            ->get();

        return view('monev.ringkasan-perkuliahan.edit', compact(
            'ringkasanPerkuliahan',
            'jadwalMonev',
            'perkuliahan'
        ));
    }

    public function update(Request $request, RingkasanPerkuliahan $ringkasanPerkuliahan): RedirectResponse
    {
        if (! $ringkasanPerkuliahan->canBeEditedBy(auth()->user())) {
            abort(403, 'Ringkasan ini tidak dapat diedit.');
        }

        $validated = $request->validate([
            'jadwal_monev_id' => ['required', 'exists:jadwal_monevs,id'],
            'perkuliahan_id' => ['required', 'exists:perkuliahans,id'],
            'jumlah_pertemuan' => ['required', 'integer', 'min:0', 'max:30'],
            'kesesuaian_materi' => ['required', 'in:sesuai,sebagian,tidak_sesuai'],
            'kontrak_kuliah' => ['required', 'in:ada,tidak_ada'],
            'metode_pembelajaran' => ['nullable', 'string'],
            'keterangan' => ['nullable', 'string'],
        ], [
            'kesesuaian_materi.required' => 'Kesesuaian materi wajib dipilih.',
            'kontrak_kuliah.required' => 'Kontrak kuliah wajib dipilih.',
        ]);

        $laporanTerbukti = Laporan::where('jenis_laporan', 'perkuliahan')
            ->where('jadwal_monev_id', $validated['jadwal_monev_id'])
            ->first();

        if ($laporanTerbukti && $laporanTerbukti->status === WorkflowStatus::DIVERIFIKASI) {
            throw ValidationException::withMessages([
                'jadwal_monev_id' => 'Laporan Pelaksanaan Perkuliahan untuk periode ini telah diverifikasi dan dikunci.',
            ]);
        }

        $isDuplicate = RingkasanPerkuliahan::query()
            ->where('jadwal_monev_id', $validated['jadwal_monev_id'])
            ->where('perkuliahan_id', $validated['perkuliahan_id'])
            ->whereKeyNot($ringkasanPerkuliahan->id)
            ->exists();

        if ($isDuplicate) {
            throw ValidationException::withMessages([
                'perkuliahan_id' => 'Ringkasan untuk jadwal monev dan perkuliahan tersebut sudah tersedia.',
            ]);
        }

        $ringkasanPerkuliahan->update($validated);

        return redirect()
            ->route('ringkasan-perkuliahan.index')
            ->with('success', 'Ringkasan perkuliahan berhasil diperbarui.');
    }

    public function destroy(RingkasanPerkuliahan $ringkasanPerkuliahan): RedirectResponse
    {
        if (! $ringkasanPerkuliahan->canBeEditedBy(auth()->user())) {
            abort(403, 'Ringkasan ini tidak dapat dihapus.');
        }

        $laporanTerbukti = Laporan::where('jenis_laporan', 'perkuliahan')
            ->where('jadwal_monev_id', $ringkasanPerkuliahan->jadwal_monev_id)
            ->first();

        if ($laporanTerbukti && $laporanTerbukti->status === WorkflowStatus::DIVERIFIKASI) {
            return back()->with('error', 'Laporan Pelaksanaan Perkuliahan untuk periode ini telah diverifikasi dan dikunci. Data tidak dapat dihapus.');
        }

        $ringkasanPerkuliahan->delete();

        return redirect()
            ->route('ringkasan-perkuliahan.index')
            ->with('success', 'Ringkasan perkuliahan berhasil dihapus.');
    }

    public function submit(RingkasanPerkuliahan $ringkasanPerkuliahan): RedirectResponse
    {
        if (! $ringkasanPerkuliahan->canBeEditedBy(auth()->user())) {
            abort(403, 'Ringkasan ini tidak dapat diajukan.');
        }

        $ringkasanPerkuliahan->update([
            'status' => 'diajukan',
            'catatan_verifikasi' => null,
            'verified_by' => null,
            'verified_at' => null,
        ]);

        $this->notifyRingkasanSubmitted($ringkasanPerkuliahan);

        return back()->with('success', 'Ringkasan perkuliahan berhasil diajukan ke Ketua GKM.');
    }

    public function verify(RingkasanPerkuliahan $ringkasanPerkuliahan): RedirectResponse
    {
        if (! auth()->user()->hasRole('ketua-gkm')) {
            abort(403, 'Hanya Ketua GKM yang dapat memverifikasi ringkasan.');
        }

        if (! $ringkasanPerkuliahan->isDiajukan()) {
            return back()->with('error', 'Hanya ringkasan berstatus diajukan yang dapat diverifikasi.');
        }

        $ringkasanPerkuliahan->update([
            'status' => 'diverifikasi',
            'catatan_verifikasi' => null,
            'verified_by' => auth()->id(),
            'verified_at' => now(),
        ]);

        $this->notifications->sendToUser(
            $ringkasanPerkuliahan->penginput,
            'Ringkasan Diverifikasi',
            'Ringkasan perkuliahan Anda telah diverifikasi oleh Ketua GKM.',
            route('ringkasan-perkuliahan.show', $ringkasanPerkuliahan),
            'bx-check-circle',
            'success',
        );

        return back()->with('success', 'Ringkasan perkuliahan berhasil diverifikasi.');
    }

    public function reject(Request $request, RingkasanPerkuliahan $ringkasanPerkuliahan): RedirectResponse
    {
        if (! auth()->user()->hasRole('ketua-gkm')) {
            abort(403, 'Hanya Ketua GKM yang dapat menolak ringkasan.');
        }

        if (! $ringkasanPerkuliahan->isDiajukan()) {
            return back()->with('error', 'Hanya ringkasan berstatus diajukan yang dapat ditolak.');
        }

        $validated = $request->validate([
            'catatan_verifikasi' => ['required', 'string'],
        ], [
            'catatan_verifikasi.required' => 'Catatan penolakan wajib diisi.',
        ]);

        $ringkasanPerkuliahan->update([
            'status' => 'ditolak',
            'catatan_verifikasi' => $validated['catatan_verifikasi'],
            'verified_by' => auth()->id(),
            'verified_at' => now(),
        ]);

        $this->notifications->sendToUser(
            $ringkasanPerkuliahan->penginput,
            'Ringkasan Ditolak',
            'Ringkasan perkuliahan perlu diperbaiki: '.$validated['catatan_verifikasi'],
            route('ringkasan-perkuliahan.show', $ringkasanPerkuliahan),
            'bx-error-circle',
            'danger',
        );

        return back()->with('success', 'Ringkasan perkuliahan berhasil ditolak.');
    }

    private function notifyRingkasanSubmitted(RingkasanPerkuliahan $ringkasan): void
    {
        $this->notifications->sendToRole(
            'ketua-gkm',
            'Ringkasan Menunggu Verifikasi',
            'Ringkasan perkuliahan baru telah diajukan oleh '.($ringkasan->penginput?->name ?? 'Anggota GKM').'.',
            route('verifikasi.index'),
            'bx-book-content',
            'warning',
        );
    }

    /**
     * Endpoint AJAX untuk menarik realisasi perkuliahan langsung dari SIAKAD Cloud.
     */
    public function fetchSiakad(Perkuliahan $perkuliahan, SiakadSyncService $syncService): JsonResponse
    {
        @set_time_limit(120);
        if (session()->isStarted()) {
            session()->save();
        }

        try {
            $data = $syncService->fetchRealizationData($perkuliahan);

            return response()->json([
                'success' => empty($data['error']) || $data['found'],
                'message' => ! empty($data['error']) && ! $data['found']
                    ? 'Koneksi ke API SIAKAD kendala/timeout: ' . $data['error']
                    : null,
                'data' => $data,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menarik data SIAKAD: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sinkronisasi data realisasi Ringkasan Perkuliahan dari SIAKAD untuk suatu Jadwal Monev.
     * Mendukung mode bertahap (AJAX per-kelas: mode=init & perkuliahan_id) agar bebas timeout HTTP,
     * serta mode penuh untuk pemanggilan langsung.
     */
    public function syncSiakad(Request $request, SiakadSyncService $syncService): RedirectResponse|JsonResponse
    {
        @set_time_limit(0);

        $validated = $request->validate([
            'jadwal_monev_id' => ['required', 'exists:jadwal_monevs,id'],
            'mode' => ['nullable', 'in:init'],
            'perkuliahan_id' => ['nullable', 'exists:perkuliahans,id'],
            'perkuliahan_ids' => ['nullable', 'array', 'max:15'],
            'perkuliahan_ids.*' => ['integer', 'exists:perkuliahans,id'],
            'only_missing' => ['nullable', 'boolean'],
        ], [
            'jadwal_monev_id.required' => 'Jadwal monev wajib dipilih untuk sinkronisasi.',
        ]);

        $jadwalMonev = JadwalMonev::with('semester')->findOrFail($validated['jadwal_monev_id']);

        $laporanTerbukti = Laporan::where('jenis_laporan', 'perkuliahan')
            ->where('jadwal_monev_id', $jadwalMonev->id)
            ->first();

        if ($laporanTerbukti && $laporanTerbukti->status === WorkflowStatus::DIVERIFIKASI) {
            $lockedMsg = 'Laporan Pelaksanaan Perkuliahan untuk periode ini telah diverifikasi dan dikunci.';
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $lockedMsg], 422);
            }

            return redirect()->back()->with('error', $lockedMsg);
        }

        // 1. Mode inisialisasi antrean sinkronisasi bertahap (AJAX)
        if (($validated['mode'] ?? null) === 'init') {
            $existingMap = RingkasanPerkuliahan::where('jadwal_monev_id', $jadwalMonev->id)
                ->get(['perkuliahan_id', 'status'])
                ->keyBy('perkuliahan_id');

            $onlyMissing = $request->boolean('only_missing');

            $perkuliahans = Perkuliahan::with(['mataKuliah', 'kelas'])
                ->where('semester_id', $jadwalMonev->semester_id)
                ->where('status', 'aktif')
                ->orderBy('id')
                ->get();

            $items = [];
            $alreadySyncedCount = 0;

            foreach ($perkuliahans as $p) {
                $existingRow = $existingMap->get($p->id);
                $hasRingkasan = $existingRow !== null;
                $isVerified = $existingRow?->status === 'diverifikasi';

                if ($hasRingkasan) {
                    $alreadySyncedCount++;
                }

                if ($isVerified || ($onlyMissing && $hasRingkasan)) {
                    continue;
                }

                $kode = $p->mataKuliah?->kode_mk ?? '-';
                $namaMk = $p->mataKuliah?->nama_mk ?? '-';
                $namaKelas = $p->kelas?->nama_kelas ?? '-';

                $items[] = [
                    'id' => $p->id,
                    'label' => "{$kode} - {$namaMk} (Kelas {$namaKelas})",
                    'has_ringkasan' => $hasRingkasan,
                ];
            }

            return response()->json([
                'success' => true,
                'total_active' => $perkuliahans->count(),
                'already_synced' => $alreadySyncedCount,
                'items' => $items,
            ]);
        }

        $userId = auth()->id();
        if (session()->isStarted()) {
            session()->save();
        }

        // 2a. Mode sinkronisasi batch Keep-Alive (beberapa kelas dalam 1 koneksi TLS)
        if (! empty($validated['perkuliahan_ids'])) {
            $batchModels = Perkuliahan::with(['mataKuliah', 'kelas', 'semester.tahunAkademik'])
                ->whereIn('id', $validated['perkuliahan_ids'])
                ->get();

            $batchRes = $syncService->syncBatchRingkasanPerkuliahan($jadwalMonev, $batchModels, $userId);

            return response()->json([
                'success' => true,
                'batch' => $batchRes,
            ]);
        }

        // 2b. Mode sinkronisasi satu kelas spesifik (AJAX per langkah, langsung tersimpan ke DB)
        if (! empty($validated['perkuliahan_id'])) {
            $perkuliahan = Perkuliahan::with(['mataKuliah', 'kelas', 'semester.tahunAkademik'])
                ->findOrFail($validated['perkuliahan_id']);

            try {
                $res = $syncService->syncSingleRingkasanPerkuliahan($jadwalMonev, $perkuliahan, $userId);
                $kode = $perkuliahan->mataKuliah?->kode_mk ?? '-';
                $namaMk = $perkuliahan->mataKuliah?->nama_mk ?? '-';
                $namaKelas = $perkuliahan->kelas?->nama_kelas ?? '-';

                return response()->json([
                    'success' => $res['outcome'] !== 'failed',
                    'outcome' => $res['outcome'],
                    'label' => "{$kode} - {$namaMk} (Kelas {$namaKelas})",
                    'jumlah_pertemuan' => $res['realisasi']['jumlah_pertemuan'] ?? null,
                    'message' => $res['message'],
                ]);
            } catch (Throwable $e) {
                return response()->json([
                    'success' => false,
                    'outcome' => 'failed',
                    'message' => $e->getMessage(),
                ], 200);
            }
        }

        // 3. Mode penuh (fallback non-AJAX / CLI / pengujian)
        try {
            $stats = $syncService->syncRingkasanPerkuliahanForJadwalMonev($jadwalMonev, $userId);

            $message = "Sinkronisasi Realisasi Monev dari SIAKAD selesai. " .
                "Total kelas: {$stats['total']}, Ditambahkan: {$stats['created']}, Diperbarui: {$stats['updated']}, Dilewati: {$stats['skipped']}.";

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'stats' => $stats,
                ]);
            }

            return redirect()->back()->with('success', $message);
        } catch (Throwable $e) {
            $errorMsg = 'Gagal sinkronisasi data monev dari SIAKAD: ' . $e->getMessage();

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMsg,
                ], 500);
            }

            return redirect()->back()->with('error', $errorMsg);
        }
    }
}
