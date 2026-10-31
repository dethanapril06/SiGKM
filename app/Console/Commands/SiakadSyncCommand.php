<?php

namespace App\Console\Commands;

use App\Models\Semester;
use App\Services\SiakadSyncService;
use Illuminate\Console\Command;
use Throwable;

class SiakadSyncCommand extends Command
{
    protected $signature = 'siakad:sync
                            {--type=all : Tipe data: all, periode, dosen, mk, kelas, perkuliahan}
                            {--semester= : ID Semester SiGKM untuk perkuliahan (default: semester aktif)}
                            {--periode= : Kode periode SIAKAD (mis. 20261)}
                            {--all-periode : Tarik seluruh histori periode (secara default hanya periode aktif)}';

    protected $description = 'Sinkronisasi Master Data dan Perkuliahan dari API SIADIKNONA SIAKAD Cloud Undana';

    public function handle(SiakadSyncService $syncService): int
    {
        $type = strtolower($this->option('type') ?? 'all');
        $this->info("=== Memulai Sinkronisasi SIAKAD Cloud Undana (Tipe: {$type}) ===");

        $startTime = microtime(true);

        try {
            if (in_array($type, ['all', 'periode', 'tahun-akademik', 'semester'], true)) {
                $onlyActive = ! $this->option('all-periode');
                $modeLabel = $onlyActive ? 'Periode Aktif' : 'Seluruh Histori Periode';
                $this->line("\n[1/5] Menyinkronkan {$modeLabel} SIAKAD...");
                $stats = $syncService->syncPeriode($onlyActive);
                $this->table(['Total API', 'TA Baru', 'TA Update', 'Sem Baru', 'Sem Update', 'Skip'], [
                    [$stats['total_api'], $stats['tahun_created'], $stats['tahun_updated'], $stats['semester_created'], $stats['semester_updated'], $stats['skipped']]
                ]);
                if ($stats['active_period']) {
                    $this->info("✔ Periode Aktif terpasang: {$stats['active_period']}");
                }
            }

            if (in_array($type, ['all', 'dosen'], true)) {
                $this->line("\n[2/5] Menyinkronkan Dosen Prodi Ilmu Komputer...");
                $stats = $syncService->syncDosen();
                $this->table(['Total API', 'Baru', 'Update', 'Skip'], [
                    [$stats['total_api'], $stats['created'], $stats['updated'], $stats['skipped']]
                ]);
            }

            if (in_array($type, ['all', 'mk', 'matakuliah'], true)) {
                $this->line("\n[3/5] Menyinkronkan Katalog Mata Kuliah...");
                $stats = $syncService->syncMataKuliah();
                $this->table(['Total API', 'Baru', 'Update', 'Skip'], [
                    [$stats['total_api'], $stats['created'], $stats['updated'], $stats['skipped']]
                ]);
            }

            if (in_array($type, ['all', 'kelas'], true)) {
                $this->line("\n[4/5] Menyinkronkan Master Kelas...");
                $stats = $syncService->syncKelas(2);
                $this->table(['Total API', 'Baru', 'Sudah Ada', 'Skip'], [
                    [$stats['total_api'], $stats['created'], $stats['existing'], $stats['skipped']]
                ]);
            }

            if (in_array($type, ['all', 'perkuliahan'], true)) {
                $semId = $this->option('semester');
                $semester = $semId ? Semester::find($semId) : (Semester::where('is_active', true)->first() ?? Semester::latest()->first());
                $semNama = $semester ? ($semester->tahunAkademik?->nama . ' ' . $semester->nama) : 'Semester Aktif';

                $this->line("\n[5/5] Menyinkronkan Perkuliahan & Dosen Pengajar ({$semNama})...");
                $stats = $syncService->syncPerkuliahan($semester, $this->option('periode'));
                $this->table(['Total API', 'Baru', 'Update', 'Skip'], [
                    [$stats['total_api'], $stats['created'], $stats['updated'], $stats['skipped']]
                ]);
            }

            $elapsed = round(microtime(true) - $startTime, 2);
            $this->info("\n Sinkronisasi selesai dalam {$elapsed} detik.");

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error("\n❌ Sinkronisasi gagal: " . $e->getMessage());

            return self::FAILURE;
        }
    }
}
