<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Semester;
use App\Services\SiakadSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class SiakadSyncController extends Controller
{
    public function __construct(
        protected SiakadSyncService $syncService
    ) {
        @set_time_limit(180);
    }

    /**
     * Sinkronisasi data Tahun Akademik dan Semester dari SIAKAD Cloud.
     * Secara default hanya menyinkronkan periode aktif saat ini.
     */
    public function syncPeriode(Request $request): JsonResponse|RedirectResponse
    {
        try {
            $onlyActive = ! $request->boolean('all');
            $stats = $this->syncService->syncPeriode($onlyActive);

            if (($stats['tahun_created'] + $stats['tahun_updated'] + $stats['semester_created'] + $stats['semester_updated']) === 0 && ! empty($stats['errors'])) {
                $errorMsg = 'Gagal sinkronisasi Periode: ' . implode('; ', $stats['errors']);
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $errorMsg], 500);
                }

                return redirect()->back()->with('error', $errorMsg);
            }

            $periodLabel = $stats['active_period'] ?? 'Periode Aktif';
            $message = $onlyActive
                ? "Sinkronisasi berhasil! Tahun Akademik dan Semester aktif SIAKAD ({$periodLabel}) berhasil disinkronkan."
                : "Sinkronisasi Periode SIAKAD selesai. Ditemukan: {$stats['total_api']} periode. " .
                  "Tahun Akademik: {$stats['tahun_created']} baru, {$stats['tahun_updated']} update. " .
                  "Semester: {$stats['semester_created']} baru, {$stats['semester_updated']} update.";

            $this->logActivity('Sinkronisasi Periode SIAKAD', $message);

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $message, 'stats' => $stats]);
            }

            return redirect()->back()->with('success', $message);
        } catch (Throwable $e) {
            $errorMsg = $this->formatErrorMessage('Gagal sinkronisasi Periode', $e);

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $errorMsg], 500);
            }

            return redirect()->back()->with('error', $errorMsg);
        }
    }

    /**
     * Sinkronisasi data Dosen dari SIAKAD Cloud.
     */
    public function syncDosen(Request $request): JsonResponse|RedirectResponse
    {
        try {
            $stats = $this->syncService->syncDosen();

            if (($stats['created'] + $stats['updated']) === 0 && ! empty($stats['errors'])) {
                $errorMsg = 'Gagal sinkronisasi Dosen: ' . implode('; ', $stats['errors']);
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $errorMsg], 500);
                }

                return redirect()->back()->with('error', $errorMsg);
            }

            $message = "Sinkronisasi Dosen SIAKAD selesai. Ditemukan: {$stats['total_api']}, Ditambahkan: {$stats['created']}, Diperbarui: {$stats['updated']}.";

            $this->logActivity('Sinkronisasi Dosen SIAKAD', $message);

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $message, 'stats' => $stats]);
            }

            return redirect()->back()->with('success', $message);
        } catch (Throwable $e) {
            $errorMsg = $this->formatErrorMessage('Gagal sinkronisasi Dosen', $e);

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $errorMsg], 500);
            }

            return redirect()->back()->with('error', $errorMsg);
        }
    }

    /**
     * Sinkronisasi katalog Mata Kuliah dari SIAKAD Cloud.
     */
    public function syncMataKuliah(Request $request): JsonResponse|RedirectResponse
    {
        try {
            $stats = $this->syncService->syncMataKuliah();

            if (($stats['created'] + $stats['updated']) === 0 && ! empty($stats['errors'])) {
                $errorMsg = 'Gagal sinkronisasi Mata Kuliah: ' . implode('; ', $stats['errors']);
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $errorMsg], 500);
                }

                return redirect()->back()->with('error', $errorMsg);
            }

            $message = "Sinkronisasi Mata Kuliah SIAKAD selesai. Total katalog: {$stats['total_api']}, Ditambahkan: {$stats['created']}, Diperbarui: {$stats['updated']}.";

            $this->logActivity('Sinkronisasi Mata Kuliah SIAKAD', $message);

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $message, 'stats' => $stats]);
            }

            return redirect()->back()->with('success', $message);
        } catch (Throwable $e) {
            $errorMsg = $this->formatErrorMessage('Gagal sinkronisasi Mata Kuliah', $e);

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $errorMsg], 500);
            }

            return redirect()->back()->with('error', $errorMsg);
        }
    }

    /**
     * Sinkronisasi data Kelas dari SIAKAD Cloud.
     */
    public function syncKelas(Request $request): JsonResponse|RedirectResponse
    {
        try {
            $semesterId = $request->input('semester_id');
            $targetSemester = $semesterId ? Semester::find($semesterId) : Semester::where('is_active', true)->first();
            $stats = $this->syncService->syncKelas(2, $targetSemester);

            if ($stats['created'] === 0 && $stats['existing'] === 0 && ! empty($stats['errors'])) {
                $errorMsg = 'Gagal sinkronisasi Kelas: ' . implode('; ', $stats['errors']);
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $errorMsg], 500);
                }

                return redirect()->back()->with('error', $errorMsg);
            }

            $message = "Sinkronisasi Kelas SIAKAD selesai. Ditambahkan: {$stats['created']}, Sudah ada: {$stats['existing']}.";

            $this->logActivity('Sinkronisasi Kelas SIAKAD', $message);

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $message, 'stats' => $stats]);
            }

            return redirect()->back()->with('success', $message);
        } catch (Throwable $e) {
            $errorMsg = $this->formatErrorMessage('Gagal sinkronisasi Kelas', $e);

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $errorMsg], 500);
            }

            return redirect()->back()->with('error', $errorMsg);
        }
    }

    /**
     * Sinkronisasi penawaran Perkuliahan & Pengajar untuk semester tertentu dari SIAKAD Cloud.
     */
    public function syncPerkuliahan(Request $request): JsonResponse|RedirectResponse
    {
        try {
            $semesterId = $request->input('semester_id');
            $targetSemester = $semesterId ? Semester::find($semesterId) : Semester::where('is_active', true)->first();
            $periodeId = $request->input('siakad_periode_id');

            $stats = $this->syncService->syncPerkuliahan($targetSemester, $periodeId);

            if ($stats['created'] === 0 && $stats['updated'] === 0 && ! empty($stats['errors'])) {
                $errorMsg = 'Gagal sinkronisasi Perkuliahan: ' . implode('; ', $stats['errors']);
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $errorMsg], 500);
                }

                return redirect()->back()->with('error', $errorMsg);
            }

            $semNama = $targetSemester ? ($targetSemester->tahunAkademik?->nama . ' ' . $targetSemester->nama) : 'Semester Terpilih';
            $message = "Sinkronisasi Perkuliahan {$semNama} selesai. Total kelas API: {$stats['total_api']}, Ditambahkan: {$stats['created']}, Diperbarui: {$stats['updated']}.";

            $this->logActivity('Sinkronisasi Perkuliahan SIAKAD', $message);

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $message, 'stats' => $stats]);
            }

            return redirect()->back()->with('success', $message);
        } catch (Throwable $e) {
            $errorMsg = $this->formatErrorMessage('Gagal sinkronisasi Perkuliahan', $e);

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $errorMsg], 500);
            }

            return redirect()->back()->with('error', $errorMsg);
        }
    }

    /**
     * Sinkronisasi seluruh Master Data secara berurutan.
     */
    public function syncAll(Request $request): JsonResponse|RedirectResponse
    {
        try {
            $periodeStats = $this->syncService->syncPeriode(true, 1);
            $dosenStats = $this->syncService->syncDosen();
            $semesterId = $request->input('semester_id');
            $targetSemester = $semesterId ? Semester::find($semesterId) : Semester::where('is_active', true)->first();
            $kelasStats = $this->syncService->syncKelas(2, $targetSemester);
            $perkuliahanStats = $this->syncService->syncPerkuliahan($targetSemester);

            $message = "Sinkronisasi Master Data SIAKAD berhasil! " .
                "Periode ({$periodeStats['tahun_created']} TA baru, {$periodeStats['semester_created']} Sem baru), " .
                "Dosen ({$dosenStats['created']} baru, {$dosenStats['updated']} update), " .
                "Kelas ({$kelasStats['created']} baru), " .
                "Perkuliahan ({$perkuliahanStats['created']} baru, {$perkuliahanStats['updated']} update).";

            $this->logActivity('Sinkronisasi Lengkap Master Data SIAKAD', $message);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'data' => [
                        'periode' => $periodeStats,
                        'dosen' => $dosenStats,
                        'kelas' => $kelasStats,
                        'perkuliahan' => $perkuliahanStats,
                    ],
                ]);
            }

            return redirect()->back()->with('success', $message);
        } catch (Throwable $e) {
            $errorMsg = $this->formatErrorMessage('Gagal sinkronisasi master data', $e);

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $errorMsg], 500);
            }

            return redirect()->back()->with('error', $errorMsg);
        }
    }

    /**
     * Format pesan error agar ramah pengguna dan menjelaskan akar masalah koneksi/API.
     */
    protected function formatErrorMessage(string $prefix, Throwable $e): string
    {
        $raw = $e->getMessage();
        $userHint = '';

        if (str_contains($raw, 'Operation timed out') || str_contains($raw, 'timed out') || str_contains($raw, 'SSL connection timeout')) {
            $userHint = 'Koneksi ke API SIAKAD Undana waktu habis (Timeout). Server SIAKAD Undana sedang lambat atau sibuk. Silakan coba beberapa saat lagi.';
        } elseif (str_contains($raw, 'Failed to connect') || str_contains($raw, "Couldn't connect")) {
            $userHint = 'Tidak dapat terhubung ke server API SIAKAD Undana. Kemungkinan server Undana sedang offline atau ada kendala jaringan.';
        } elseif (str_contains($raw, '401') || str_contains($raw, '403') || str_contains($raw, 'API key') || str_contains($raw, 'Akses API SIADIKNONA ditolak')) {
            $userHint = 'Akses API SIAKAD ditolak (401/403). Pastikan SIADIKNONA_APP_KEY dan SIADIKNONA_SECRET_KEY di file .env sudah benar dan valid.';
        } elseif (str_contains($raw, '429')) {
            $userHint = 'Batas kuota request ke API SIAKAD tercapai (429 Too Many Requests). Harap tunggu beberapa menit sebelum mencoba lagi.';
        }

        if ($userHint !== '') {
            return "{$prefix}: {$userHint} (Detail: {$raw})";
        }

        return "{$prefix}: {$raw}";
    }

    private function logActivity(string $action, string $description): void
    {
        try {
            if (class_exists(ActivityLog::class)) {
                ActivityLog::create([
                    'user_id' => auth()->id() ?? 1,
                    'aktivitas' => $action,
                    'deskripsi' => $description,
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);
            }
        } catch (Throwable) {
            // Abaikan kegagalan logging
        }
    }
}
