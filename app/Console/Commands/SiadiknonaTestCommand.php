<?php

namespace App\Console\Commands;

use App\Services\SiadiknonaApiService;
use Illuminate\Console\Command;
use Throwable;

class SiadiknonaTestCommand extends Command
{
    protected $signature = 'siadiknona:test
                            {--endpoint= : Uji satu endpoint saja, mis. /siakadcloud/v1/dosen}';

    protected $description = 'Uji koneksi & hak akses ke endpoint API SIADIKNONA yang dipakai SiGKM';

    public function handle(SiadiknonaApiService $api): int
    {
        if (! $api->isConfigured()) {
            $this->error('SIADIKNONA_APP_KEY / SIADIKNONA_SECRET_KEY belum diisi di .env.');
            $this->line('Setelah mengisi, jalankan: php artisan config:clear');

            return self::FAILURE;
        }

        $endpoints = $this->option('endpoint')
            ? [$this->option('endpoint')]
            : [
                SiadiknonaApiService::DOSEN,
                SiadiknonaApiService::MATA_KULIAH,
                SiadiknonaApiService::KELAS,
                SiadiknonaApiService::PERIODE,
                SiadiknonaApiService::PERKULIAHAN,
                SiadiknonaApiService::PROGRAM_STUDI,
                SiadiknonaApiService::FAKULTAS,
                SiadiknonaApiService::PRESENSI_DOSEN,
                SiadiknonaApiService::PRESENSI_MAHASISWA,
                SiadiknonaApiService::RPS,
                SiadiknonaApiService::CPMK,
                SiadiknonaApiService::EDLINK_SESI,
                SiadiknonaApiService::EDLINK_BAHAN_AJAR,
            ];

        $this->info('Base URL: '.config('services.siadiknona.base_url'));

        $rows = [];
        $failed = 0;

        foreach ($endpoints as $path) {
            try {
                $filters = $path === SiadiknonaApiService::PRESENSI_MAHASISWA
                    ? $this->presensiMahasiswaFilter($api)
                    : [];

                $res = $api->getList($path, $filters, 1, 1);
                $rows[] = ['<fg=green>OK</>', $path, $res['meta']['total'] ?? '-', $filters ? 'filter: '.json_encode($filters) : ''];
            } catch (Throwable $e) {
                $failed++;
                $rows[] = ['<fg=red>GAGAL</>', $path, '-', mb_strimwidth($e->getMessage(), 0, 120, '...')];
            }
        }

        $this->table(['Status', 'Endpoint', 'Total Data', 'Keterangan'], $rows);

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Endpoint presensi-mahasiswa mewajibkan f-id_periode_kelas,
     * jadi ambil satu contoh ID dari data presensi-dosen.
     */
    private function presensiMahasiswaFilter(SiadiknonaApiService $api): array
    {
        $sample = $api->getPresensiDosenList([], 1, 1)['data'][0]['attributes'] ?? [];

        return filled($sample['id_periode_kelas'] ?? null)
            ? ['id_periode_kelas' => $sample['id_periode_kelas']]
            : [];
    }
}
