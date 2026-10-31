<?php

namespace App\Services;

use App\Models\Dosen;
use App\Models\JadwalMonev;
use App\Models\Kelas;
use App\Models\MataKuliah;
use App\Models\Pengajar;
use App\Models\Perkuliahan;
use App\Models\RingkasanPerkuliahan;
use App\Models\Semester;
use App\Models\TahunAkademik;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class SiakadSyncService
{
    /**
     * Cache snapshot realisasi per-request/proses agar tidak perlu berulang kali mendeserialisasi cache.
     *
     * @var array<string, array>
     */
    protected array $memorySnapshotCache = [];

    public function __construct(
        protected SiadiknonaApiService $api
    ) {}

    /**
     * Resolusi kode periode SIAKAD (mis. 20261) dari model Semester SiGKM.
     */
    public function resolveSiakadPeriode(?Semester $semester = null): string
    {
        if ($semester) {
            $semester->loadMissing('tahunAkademik');
            $tahunNama = $semester->tahunAkademik?->nama ?? ''; // mis. "2026/2027"
            $tahunAwal = substr(trim($tahunNama), 0, 4);
            $kodeSemester = strtolower(trim($semester->nama ?? '')) === 'genap' ? '2' : '1';

            if (strlen($tahunAwal) === 4) {
                return $tahunAwal . $kodeSemester;
            }
        }

        try {
            $periodeAktif = $this->api->getPeriodeAktif();
            if (! empty($periodeAktif['id'])) {
                return (string) $periodeAktif['id'];
            }
        } catch (Throwable $e) {
            Log::warning('Gagal mengambil periode aktif SIAKAD', ['error' => $e->getMessage()]);
        }

        return '20261';
    }

    /**
     * Format nama lengkap dosen beserta gelar depan dan gelar belakang.
     */
    public function formatNamaDosen(?string $nama, ?string $gelarDepan, ?string $gelarBelakang): string
    {
        $nama = trim($nama ?? '');
        $nama = ltrim($nama, "-., \t\n\r\0\x0B");
        $nama = rtrim($nama, "-, \t\n\r\0\x0B");

        $gelarDepan = trim($gelarDepan ?? '');
        $gelarDepan = ltrim($gelarDepan, "-., \t\n\r\0\x0B");
        $gelarDepan = rtrim($gelarDepan, "-, \t\n\r\0\x0B");

        $gelarBelakang = trim($gelarBelakang ?? '');
        $gelarBelakang = ltrim($gelarBelakang, "-., \t\n\r\0\x0B");
        $gelarBelakang = rtrim($gelarBelakang, "-, \t\n\r\0\x0B");

        $result = '';
        if ($gelarDepan !== '' && stripos($nama, $gelarDepan) !== 0) {
            $result .= $gelarDepan . ' ';
        }
        $result .= $nama;

        if ($gelarBelakang !== '') {
            $normNama = preg_replace('/[^a-z0-9]/i', '', mb_strtolower($nama)) ?? '';
            $normBelakang = preg_replace('/[^a-z0-9]/i', '', mb_strtolower($gelarBelakang)) ?? '';
            if ($normBelakang !== '' && ! str_ends_with($normNama, $normBelakang)) {
                $result .= ', ' . $gelarBelakang;
            }
        }

        return trim(preg_replace('/\s+/u', ' ', $result) ?? $result);
    }

    /**
     * Parse string tanggal dari API SIAKAD menjadi format Y-m-d valid atau null.
     */
    protected function parseValidDate(?string $date): ?string
    {
        if (empty($date)) {
            return null;
        }

        $trimmed = trim($date);
        if ($trimmed === '' || $trimmed === '0000-00-00') {
            return null;
        }

        $timestamp = strtotime($trimmed);

        return $timestamp !== false ? date('Y-m-d', $timestamp) : null;
    }

    /**
     * Sinkronisasi data Tahun Akademik dan Semester dari SIAKAD Cloud.
     * Secara default hanya menyinkronkan periode yang sedang aktif (is_aktif = 1).
     *
     * @param bool $onlyActive Jika true (default), hanya menyinkronkan periode aktif saat ini.
     * @param int|null $limitPages Batas halaman jika menyinkronkan seluruh periode.
     */
    public function syncPeriode(bool $onlyActive = true, ?int $limitPages = null): array
    {
        $stats = [
            'total_api' => 0,
            'tahun_created' => 0,
            'tahun_updated' => 0,
            'semester_created' => 0,
            'semester_updated' => 0,
            'skipped' => 0,
            'errors' => [],
            'active_period' => null,
        ];

        $page = 1;
        $perPage = 100;
        $activeTahunId = null;
        $activeSemesterId = null;

        do {
            try {
                $filters = $onlyActive ? ['is_aktif' => '1'] : [];
                $response = $this->api->getPeriodeList($filters, $page, $perPage);
            } catch (Throwable $e) {
                $stats['errors'][] = "Halaman {$page}: " . $e->getMessage();
                Log::error("SiakadSyncService::syncPeriode error page {$page}", ['error' => $e->getMessage()]);
                if ($page === 1) {
                    throw $e;
                }
                break;
            }

            $items = $response['data'] ?? [];
            if (empty($items)) {
                break;
            }

            $stats['total_api'] = (int) ($response['meta']['total'] ?? count($items));

            foreach ($items as $item) {
                $attr = $item['attributes'] ?? [];
                $periodeId = (string) ($item['id'] ?? '');

                // 1. Tentukan nama Tahun Akademik (mis. "2026/2027")
                $tahunNama = trim($attr['tahun_ajar'] ?? '');
                if ($tahunNama === '' && strlen($periodeId) >= 4) {
                    $prefix = substr($periodeId, 0, 4);
                    if (is_numeric($prefix) && strlen($prefix) === 4) {
                        $tahunNama = $prefix . '/' . ((int) $prefix + 1);
                    }
                }

                // Validasi format tahun akademik: harus berupa 4 digit tahun / 4 digit tahun (mis. 2026/2027)
                if (! preg_match('/^\d{4}\/\d{4}$/', $tahunNama)) {
                    $stats['skipped']++;
                    continue;
                }

                // 2. Tentukan nama Semester: ganjil atau genap sesuai enum schema SiGKM
                $namaPeriode = strtolower(trim($attr['nama_periode'] ?? ''));
                $singkat = strtolower(trim($attr['nama_singkat'] ?? ''));
                $lastDigit = substr($periodeId, -1);

                $semNama = null;
                if ($lastDigit === '1' || str_contains($namaPeriode, 'ganjil') || $singkat === 'gasal') {
                    $semNama = 'ganjil';
                } elseif ($lastDigit === '2' || str_contains($namaPeriode, 'genap')) {
                    $semNama = 'genap';
                } else {
                    // Periode pendek (3) atau periode di luar enum 'ganjil'/'genap'
                    $stats['skipped']++;
                    continue;
                }

                $isAktif = (string) ($attr['is_aktif'] ?? '0') === '1' || $onlyActive;
                $tglMulai = $this->parseValidDate($attr['tanggal_awal'] ?? null);
                $tglSelesai = $this->parseValidDate($attr['tanggal_akhir_uas'] ?? ($attr['tanggal_akhir'] ?? null));

                // 3. Update or Create TahunAkademik
                $tahun = TahunAkademik::where('nama', $tahunNama)->first();
                if ($tahun) {
                    $tahunData = [];
                    if ($tglMulai && ! $tahun->tanggal_mulai) {
                        $tahunData['tanggal_mulai'] = $tglMulai;
                    }
                    if ($tglSelesai && (! $tahun->tanggal_selesai || $tglSelesai > $tahun->tanggal_selesai->format('Y-m-d'))) {
                        $tahunData['tanggal_selesai'] = $tglSelesai;
                    }
                    if (! empty($tahunData)) {
                        $tahun->update($tahunData);
                    }
                    $stats['tahun_updated']++;
                } else {
                    $tahun = TahunAkademik::create([
                        'nama' => $tahunNama,
                        'tanggal_mulai' => $tglMulai,
                        'tanggal_selesai' => $tglSelesai,
                        'is_active' => false,
                    ]);
                    $stats['tahun_created']++;
                }

                // 4. Update or Create Semester
                $semester = Semester::where('tahun_akademik_id', $tahun->id)
                    ->where('nama', $semNama)
                    ->first();

                if ($semester) {
                    $semester->update([
                        'tanggal_mulai' => $tglMulai ?? $semester->tanggal_mulai,
                        'tanggal_selesai' => $tglSelesai ?? $semester->tanggal_selesai,
                    ]);
                    $stats['semester_updated']++;
                } else {
                    $semester = Semester::create([
                        'tahun_akademik_id' => $tahun->id,
                        'nama' => $semNama,
                        'tanggal_mulai' => $tglMulai,
                        'tanggal_selesai' => $tglSelesai,
                        'is_active' => false,
                    ]);
                    $stats['semester_created']++;
                }

                if ($isAktif) {
                    $activeTahunId = $tahun->id;
                    $activeSemesterId = $semester->id;
                    $stats['active_period'] = "{$tahunNama} " . ucfirst($semNama);
                }
            }

            if ($onlyActive) {
                break;
            }

            $lastPage = (int) ($response['meta']['lastPage'] ?? $page);
            $page++;

            if ($limitPages !== null && $page > $limitPages) {
                break;
            }
        } while ($page <= $lastPage);

        // Jika ada periode aktif di SIAKAD, sinkronkan status aktif di SiGKM
        if ($activeTahunId) {
            TahunAkademik::where('id', '!=', $activeTahunId)->where('is_active', true)->update(['is_active' => false]);
            TahunAkademik::where('id', $activeTahunId)->update(['is_active' => true]);
        }

        if ($activeSemesterId) {
            Semester::where('id', '!=', $activeSemesterId)->where('is_active', true)->update(['is_active' => false]);
            Semester::where('id', $activeSemesterId)->update(['is_active' => true]);
        }

        return $stats;
    }

    /**
     * Sinkronisasi data Dosen Home Base Ilmu Komputer (55201).
     */
    public function syncDosen(?int $limitPages = null): array
    {
        $stats = [
            'total_api' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        $page = 1;
        $perPage = 50;

        do {
            try {
                $response = $this->api->getDosenIlkom([], $page, $perPage);
            } catch (Throwable $e) {
                $stats['errors'][] = $e->getMessage();
                Log::error("SiakadSyncService::syncDosen error page {$page}", ['error' => $e->getMessage()]);
                if ($page === 1) {
                    throw $e;
                }
                break;
            }

            $items = $response['data'] ?? [];
            if (empty($items)) {
                break;
            }

            $stats['total_api'] = (int) ($response['meta']['total'] ?? count($items));

            foreach ($items as $item) {
                $attr = $item['attributes'] ?? [];
                $namaAsli = trim($attr['nama'] ?? '');
                if ($namaAsli === '') {
                    $stats['skipped']++;
                    continue;
                }

                $nip = trim($attr['nip'] ?? ($attr['nip_pns'] ?? '')) ?: null;
                $nidn = trim($attr['nidn'] ?? '') ?: null;
                $namaLengkap = $this->formatNamaDosen($namaAsli, $attr['gelar_depan'] ?? null, $attr['gelar_belakang'] ?? null);

                // Cari dosen yang sudah ada berdasarkan NIDN, NIP, atau kemiripan nama
                $dosen = $this->findExistingDosen($namaLengkap, $nip, $nidn)
                    ?: $this->findExistingDosen($namaAsli, $nip, $nidn);

                if ($dosen) {
                    $updates = [];
                    // Pertahankan nama yang sudah ada di database agar tidak tertimpa variasi nama dari API
                    if (empty(trim($dosen->nama_dosen ?? '')) && ! empty($namaLengkap)) {
                        $updates['nama_dosen'] = $namaLengkap;
                    }

                    // Update NIP jika di database belum ada atau yang baru merupakan NIP 18 digit resmi
                    if ($nip && $dosen->nip !== $nip) {
                        $isBetterNip = ! $dosen->nip || (strlen($nip) === 18 && strlen($dosen->nip) !== 18);
                        if ($isBetterNip && ! Dosen::where('nip', $nip)->whereKeyNot($dosen->id)->exists()) {
                            $updates['nip'] = $nip;
                        }
                    }

                    // Update NIDN jika di database belum ada
                    if ($nidn && ! $dosen->nidn && ! Dosen::where('nidn', $nidn)->whereKeyNot($dosen->id)->exists()) {
                        $updates['nidn'] = $nidn;
                    }

                    if (! empty($updates)) {
                        $dosen->update($updates);
                    }
                    $stats['updated']++;
                } else {
                    Dosen::create([
                        'nama_dosen' => $namaLengkap,
                        'nip' => $nip,
                        'nidn' => $nidn,
                    ]);
                    $stats['created']++;
                }
            }

            $lastPage = (int) ($response['meta']['lastPage'] ?? $page);
            $page++;

            if ($limitPages !== null && $page > $limitPages) {
                break;
            }
        } while ($page <= $lastPage);

        return $stats;
    }

    /**
     * Sinkronisasi katalog Mata Kuliah Prodi Ilmu Komputer (55201).
     */
    public function syncMataKuliah(?int $limitPages = null): array
    {
        $stats = [
            'total_api' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        $page = 1;
        $perPage = 50;

        do {
            try {
                $response = $this->api->getMataKuliahIlkom([], $page, $perPage);
            } catch (Throwable $e) {
                $stats['errors'][] = $e->getMessage();
                Log::error("SiakadSyncService::syncMataKuliah error page {$page}", ['error' => $e->getMessage()]);
                if ($page === 1) {
                    throw $e;
                }
                break;
            }

            $items = $response['data'] ?? [];
            if (empty($items)) {
                break;
            }

            $stats['total_api'] = (int) ($response['meta']['total'] ?? count($items));

            foreach ($items as $item) {
                $attr = $item['attributes'] ?? [];
                $kode = trim($attr['kode'] ?? '');
                $nama = trim($attr['nama'] ?? '');
                $sks = (int) ($attr['sks'] ?? 0);

                if ($kode === '' || $nama === '') {
                    $stats['skipped']++;
                    continue;
                }

                $mk = MataKuliah::where('kode_mk', $kode)->first();

                if ($mk) {
                    $mk->update([
                        'nama_mk' => $nama,
                        'sks' => $sks > 0 ? $sks : $mk->sks,
                        'is_active' => true,
                    ]);
                    $stats['updated']++;
                } else {
                    MataKuliah::create([
                        'kode_mk' => $kode,
                        'nama_mk' => $nama,
                        'sks' => max(1, $sks),
                        'is_active' => true,
                    ]);
                    $stats['created']++;
                }
            }

            $lastPage = (int) ($response['meta']['lastPage'] ?? $page);
            $page++;

            if ($limitPages !== null && $page > $limitPages) {
                break;
            }
        } while ($page <= $lastPage);

        return $stats;
    }

    /**
     * Sinkronisasi data Kelas Prodi Ilmu Komputer (55201).
     * Secara default memfilter berdasarkan periode aktif untuk mencegah timeout data historis.
     */
    public function syncKelas(?int $limitPages = null, ?Semester $semester = null): array
    {
        $stats = [
            'total_api' => 0,
            'created' => 0,
            'existing' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        $page = 1;

        $targetSemester = $semester ?? Semester::where('is_active', true)->first();
        $filters = [];
        if ($targetSemester) {
            $filters['id_periode'] = $this->resolveSiakadPeriode($targetSemester);
        }

        do {
            try {
                $response = $this->api->getKelasIlkom($filters, $page);
            } catch (Throwable $e) {
                $stats['errors'][] = "Halaman {$page}: " . $e->getMessage();
                Log::error("SiakadSyncService::syncKelas error page {$page}", ['error' => $e->getMessage()]);
                if ($page === 1) {
                    throw $e;
                }
                break;
            }

            $items = $response['data'] ?? [];
            if (empty($items)) {
                break;
            }

            $stats['total_api'] = (int) ($response['meta']['total'] ?? count($items));

            foreach ($items as $item) {
                $attr = $item['attributes'] ?? [];
                if ((string) ($attr['is_deleted'] ?? '0') === '1') {
                    $stats['skipped']++;
                    continue;
                }

                $namaKelas = trim($attr['nama_kelas'] ?? '');

                if ($namaKelas === '') {
                    $stats['skipped']++;
                    continue;
                }

                $kelas = Kelas::where('nama_kelas', $namaKelas)->first();
                if ($kelas) {
                    $stats['existing']++;
                } else {
                    Kelas::create([
                        'nama_kelas' => $namaKelas,
                        'keterangan' => 'Disinkronkan dari SIAKAD Cloud',
                    ]);
                    $stats['created']++;
                }
            }

            $lastPage = (int) ($response['meta']['lastPage'] ?? $page);
            $page++;

            if ($limitPages !== null && $page > $limitPages) {
                break;
            }
        } while ($page <= $lastPage);

        return $stats;
    }

    /**
     * Sinkronisasi data Perkuliahan & Dosen Pengajar Prodi Ilmu Komputer (55201) untuk semester tertentu.
     * Menggunakan endpoint Kelas SIAKAD per periode yang memuat Mata Kuliah, Kelas, & Relasi Dosen Pengajar.
     */
    public function syncPerkuliahan(?Semester $targetSemester = null, ?string $siakadPeriodeId = null, ?int $limitPages = null): array
    {
        $stats = [
            'total_api' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        // 1. Tentukan semester lokal
        $semester = $targetSemester ?? Semester::where('is_active', true)->first() ?? Semester::latest()->first();
        if (! $semester) {
            $stats['errors'][] = 'Tidak ada data semester aktif di SiGKM. Silakan aktifkan semester terlebih dahulu.';
            return $stats;
        }

        // 2. Tentukan periode SIAKAD
        $periodeId = $siakadPeriodeId ?: $this->resolveSiakadPeriode($semester);

        $page = 1;

        do {
            try {
                $response = $this->api->getKelasList([
                    'id_program_studi' => $this->api->getProdiId(),
                    'id_periode' => $periodeId,
                ], $page);
            } catch (Throwable $e) {
                $stats['errors'][] = "Halaman {$page}: " . $e->getMessage();
                Log::error("SiakadSyncService::syncPerkuliahan error page {$page}", ['error' => $e->getMessage()]);
                if ($page === 1) {
                    throw $e;
                }
                break;
            }

            $items = $response['data'] ?? [];
            if (empty($items)) {
                break;
            }

            $stats['total_api'] = (int) ($response['meta']['total'] ?? count($items));

            foreach ($items as $item) {
                $attr = $item['attributes'] ?? [];
                if ((string) ($attr['is_deleted'] ?? '0') === '1') {
                    $stats['skipped']++;
                    continue;
                }

                $siakadKelasId = trim((string) ($item['id'] ?? '')) ?: null;
                $kodeMk = trim($attr['kode_mata_kuliah'] ?? '');
                $namaMk = trim($attr['mata_kuliah'] ?? '');
                $namaKelas = trim($attr['nama_kelas'] ?? '');
                $sks = (int) ($attr['sks_mata_kuliah'] ?? 0);

                if ($kodeMk === '' || $namaKelas === '' || $this->isNonClassroomCourse($namaMk)) {
                    $stats['skipped']++;
                    continue;
                }

                // A. Hanya sinkronkan kelas yang benar-benar memiliki dosen pengajar aktif
                $dosensApi = $this->extractDosensForKelasItem($attr, $siakadKelasId, $periodeId);
                if (empty($dosensApi)) {
                    $stats['skipped']++;
                    continue;
                }

                // B. Pastikan Mata Kuliah ada di sistem
                $mataKuliah = MataKuliah::firstOrCreate(
                    ['kode_mk' => $kodeMk],
                    [
                        'nama_mk' => $namaMk ?: $kodeMk,
                        'sks' => max(1, $sks),
                        'is_active' => true,
                    ]
                );

                // C. Pastikan Kelas ada di sistem
                $kelas = Kelas::firstOrCreate(
                    ['nama_kelas' => $namaKelas],
                    ['keterangan' => 'Disinkronkan dari SIAKAD Cloud']
                );

                // D. Buat atau update Perkuliahan beserta siakad_kelas_id sebagai jembatan ke /v1/perkuliahan
                $perkuliahan = Perkuliahan::firstOrCreate(
                    [
                        'semester_id' => $semester->id,
                        'mata_kuliah_id' => $mataKuliah->id,
                        'kelas_id' => $kelas->id,
                    ],
                    [
                        'siakad_kelas_id' => $siakadKelasId,
                        'status' => 'aktif',
                    ]
                );

                if ($perkuliahan->wasRecentlyCreated) {
                    $stats['created']++;
                } else {
                    if ($siakadKelasId && $perkuliahan->siakad_kelas_id !== $siakadKelasId) {
                        $perkuliahan->update(['siakad_kelas_id' => $siakadKelasId]);
                    }
                    $stats['updated']++;
                }

                // E. Hubungkan Dosen Pengajar ke Perkuliahan
                foreach ($dosensApi as $dApi) {
                    $dosen = $this->resolveOrCreateDosen(
                        $dApi['nama'] ?? null,
                        $dApi['nip'] ?? null,
                        $dApi['nidn'] ?? null,
                        $dApi['gelar_depan'] ?? null,
                        $dApi['gelar_belakang'] ?? null,
                    );

                    if ($dosen) {
                        Pengajar::firstOrCreate([
                            'perkuliahan_id' => $perkuliahan->id,
                            'dosen_id' => $dosen->id,
                        ]);
                    }
                }
            }

            $lastPage = (int) ($response['meta']['lastPage'] ?? $page);
            $page++;

            if ($limitPages !== null && $page > $limitPages) {
                break;
            }
        } while ($page <= $lastPage);

        return $stats;
    }

    /**
     * Ekstrak daftar dosen pengajar untuk suatu kelas dari:
     * 1) attributes.relation.dosen-pengajar pada payload /kelas,
     * 2) fallback endpoint /kelas/{id}/dosen,
     * 3) fallback nip_pengisi_materi & nama_pengisi_materi pada sesi /perkuliahan?f-id_kelas={id}.
     */
    protected function extractDosensForKelasItem(array $attr, ?string $siakadKelasId, string $periodeId): array
    {
        $dosens = [];
        $hasEmbeddedRelation = isset($attr['relation']) && is_array($attr['relation']) && array_key_exists('dosen-pengajar', $attr['relation']);
        $embedded = $attr['relation']['dosen-pengajar'] ?? [];

        if (! empty($embedded) && is_array($embedded)) {
            foreach ($embedded as $d) {
                $row = $d['attributes'] ?? $d;
                if (! empty(trim($row['nama'] ?? ''))) {
                    $dosens[] = [
                        'nama' => $row['nama'] ?? '',
                        'nip' => $row['nip'] ?? ($row['nip_pns'] ?? null),
                        'nidn' => $row['nidn'] ?? null,
                        'gelar_depan' => $row['gelar_depan'] ?? null,
                        'gelar_belakang' => $row['gelar_belakang'] ?? null,
                    ];
                }
            }
        }

        // Hanya panggil sub-endpoint jika payload /kelas tidak menyertakan objek relation.dosen-pengajar
        if (! $hasEmbeddedRelation && empty($dosens) && $siakadKelasId) {
            try {
                $resDosen = $this->api->getKelasDosenList($siakadKelasId, 1);
                foreach ($resDosen['data'] ?? [] as $d) {
                    $row = $d['attributes'] ?? $d;
                    if (! empty(trim($row['nama'] ?? ''))) {
                        $dosens[] = [
                            'nama' => $row['nama'] ?? '',
                            'nip' => $row['nip'] ?? ($row['nip_pns'] ?? null),
                            'nidn' => $row['nidn'] ?? null,
                            'gelar_depan' => $row['gelar_depan'] ?? null,
                            'gelar_belakang' => $row['gelar_belakang'] ?? null,
                        ];
                    }
                }
            } catch (Throwable $e) {
                Log::debug("Fallback getKelasDosenList gagal untuk kelas {$siakadKelasId}", ['error' => $e->getMessage()]);
            }
        }

        return $dosens;
    }

    /**
     * Bersihkan bagian gelar akademik dari nama dosen untuk keperluan pembandingan nama.
     */
    public function cleanNameBeforeDegrees(string $name): string
    {
        $parts = explode(',', $name);
        $base = trim($parts[0]);
        $base = preg_replace('/^(prof\b|dr\b|dra\b|drs\b|ir\b)[.,\s]*/i', '', $base);
        $clean = preg_replace('/[.,\/\-_()]/u', ' ', mb_strtolower($base)) ?? mb_strtolower($base);

        return trim($clean);
    }

    /**
     * Ekstrak token kata nama pokok (tanpa gelar dan inisial 1 huruf).
     *
     * @return array<string>
     */
    public function extractNameTokens(string $name): array
    {
        $clean = $this->cleanNameBeforeDegrees($name);
        $words = preg_split('/\s+/u', $clean, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $tokens = [];
        foreach ($words as $w) {
            $wClean = trim($w);
            if (mb_strlen($wClean) <= 1) {
                continue;
            }
            $tokens[] = $wClean;
        }

        return array_values($tokens);
    }

    /**
     * Cek apakah dua kata nama serupa (mempertimbangkan typo minor atau perbedaan 1 huruf).
     */
    public function isWordSimilar(string $w1, string $w2): bool
    {
        if ($w1 === $w2) {
            return true;
        }
        $len1 = strlen($w1);
        $len2 = strlen($w2);
        if (abs($len1 - $len2) > 2) {
            return false;
        }

        $dist = levenshtein($w1, $w2);
        if ($dist <= 1) {
            return true;
        }
        if ($dist <= 2 && (soundex($w1) === soundex($w2) || metaphone($w1) === metaphone($w2))) {
            return true;
        }

        return false;
    }

    /**
     * Cek apakah dua nama dosen merujuk pada orang yang sama berdasarkan nama awal dan nama akhir.
     */
    public function isNameMatch(string $nameA, string $nameB): bool
    {
        if (strcasecmp(trim($nameA), trim($nameB)) === 0) {
            return true;
        }

        $tokensA = $this->extractNameTokens($nameA);
        $tokensB = $this->extractNameTokens($nameB);

        if (empty($tokensA) || empty($tokensB)) {
            return strcasecmp(trim($nameA), trim($nameB)) === 0;
        }

        $strA = implode(' ', $tokensA);
        $strB = implode(' ', $tokensB);
        if ($strA === $strB || str_contains($strA, $strB) || str_contains($strB, $strA)) {
            return true;
        }

        $firstA = $tokensA[0];
        $lastA = end($tokensA);
        $firstB = $tokensB[0];
        $lastB = end($tokensB);

        return $this->isWordSimilar($firstA, $firstB) && $this->isWordSimilar($lastA, $lastB);
    }

    /**
     * Cari dosen yang sudah ada di database berdasarkan NIDN, NIP, atau kemiripan nama
     * (termasuk pencocokan cerdas kata awal dan kata akhir untuk menghindari pendobelan).
     */
    public function findExistingDosen(?string $nama, ?string $nip = null, ?string $nidn = null): ?Dosen
    {
        $nip = trim($nip ?? '') ?: null;
        $nidn = trim($nidn ?? '') ?: null;

        if (! $nidn && $nip && strlen($nip) === 10 && ctype_digit($nip)) {
            $nidn = $nip;
        }

        // 1. Prioritas NIDN (cross-check kolom nidn dan nip)
        if ($nidn) {
            $dosen = Dosen::where('nidn', $nidn)->orWhere('nip', $nidn)->first();
            if ($dosen) {
                return $dosen;
            }
        }

        // 2. Prioritas NIP (cross-check kolom nip dan nidn)
        if ($nip) {
            $dosen = Dosen::where('nip', $nip)->orWhere('nidn', $nip)->first();
            if ($dosen) {
                return $dosen;
            }
        }

        // 3. Pencocokan eksak nama
        $nama = trim($nama ?? '');
        if ($nama === '') {
            return null;
        }

        $dosen = Dosen::where('nama_dosen', $nama)->first();
        if ($dosen) {
            return $dosen;
        }

        // 4. Pencocokan LIKE nama pokok
        $baseName = $this->cleanNameBeforeDegrees($nama);
        if ($baseName !== '') {
            $matches = Dosen::where('nama_dosen', 'like', '%' . $baseName . '%')->limit(2)->get();
            if ($matches->count() === 1) {
                return $matches->first();
            }
        }

        // 5. Pencocokan cerdas nama awal dan nama akhir (token fuzzy matching)
        $allDosens = Dosen::all(['id', 'nama_dosen', 'nip', 'nidn']);
        foreach ($allDosens as $candidate) {
            if ($this->isNameMatch($nama, $candidate->nama_dosen ?? '')) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Cari Dosen di database berdasarkan NIDN/NIP/Nama (termasuk kasus nip_pengisi_materi berisi 10 digit NIDN),
     * atau buat baru jika belum ada tanpa menimpa gelar lengkap dosen yang sudah ada.
     */
    protected function resolveOrCreateDosen(
        ?string $namaAsli,
        ?string $nip,
        ?string $nidn,
        ?string $gelarDepan = null,
        ?string $gelarBelakang = null
    ): ?Dosen {
        $namaAsli = trim($namaAsli ?? '');
        if ($namaAsli === '') {
            return null;
        }

        $nip = trim($nip ?? '') ?: null;
        $nidn = trim($nidn ?? '') ?: null;

        // Jika field NIP ternyata berisi 10 digit NIDN (umum terjadi pada nip_pengisi_materi SIAKAD)
        if (! $nidn && $nip && strlen($nip) === 10 && ctype_digit($nip)) {
            $nidn = $nip;
            $nip = null;
        }

        $namaLengkap = $this->formatNamaDosen($namaAsli, $gelarDepan, $gelarBelakang);

        $dosen = $this->findExistingDosen($namaLengkap, $nip, $nidn)
            ?: $this->findExistingDosen($namaAsli, $nip, $nidn);

        if ($dosen) {
            $updates = [];
            // Update NIP jika di database belum ada atau yang baru merupakan NIP 18 digit resmi
            if ($nip && $dosen->nip !== $nip) {
                $isBetterNip = ! $dosen->nip || (strlen($nip) === 18 && strlen($dosen->nip) !== 18);
                if ($isBetterNip && ! Dosen::where('nip', $nip)->whereKeyNot($dosen->id)->exists()) {
                    $updates['nip'] = $nip;
                }
            }
            // Update NIDN jika di database belum ada
            if ($nidn && ! $dosen->nidn && ! Dosen::where('nidn', $nidn)->whereKeyNot($dosen->id)->exists()) {
                $updates['nidn'] = $nidn;
            }
            if (! empty($updates)) {
                $dosen->update($updates);
            }

            return $dosen;
        }

        return Dosen::create([
            'nama_dosen' => $namaLengkap,
            'nip' => $nip,
            'nidn' => $nidn,
        ]);
    }

    /**
     * Bersihkan tag HTML (<br>, <p>, <div>, <li>) dan non-breaking space (\u00a0 / &nbsp;) menjadi teks rapi.
     */
    protected function cleanHtmlText(?string $html): string
    {
        if ($html === null || $html === '') {
            return '';
        }

        $spaced = preg_replace('/<\s*(br\s*\/?|\/p|\/div|\/li|li|ol|ul)\b[^>]*>/i', ' ', $html) ?? $html;
        $decoded = html_entity_decode(strip_tags($spaced), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $normalized = str_replace("\xC2\xA0", ' ', $decoded);

        return trim(preg_replace('/\s+/u', ' ', $normalized) ?? $normalized);
    }

    /**
     * Evaluasi apakah realisasi bahasan sesuai dengan rencana materi (mendukung input Web SIAKAD, Edlink, & UTS/UAS).
     */
    protected function isMateriSesuai(string $rencana, string $bahasan, string $jenisKode = ''): bool
    {
        if ($bahasan === '') {
            return false;
        }

        // Sesi UTS ('T') atau UAS ('A') yang sudah selesai dan memiliki catatan bahasan/evaluasi
        $jenisKode = strtoupper(trim($jenisKode));
        if (in_array($jenisKode, ['T', 'A'], true)) {
            return true;
        }

        if ($rencana === '') {
            return true;
        }

        if (
            strcasecmp($rencana, $bahasan) === 0 ||
            stripos($bahasan, $rencana) !== false ||
            stripos($rencana, $bahasan) !== false ||
            stripos($bahasan, 'terealisasi') !== false ||
            stripos($bahasan, 'terlaksana') !== false ||
            stripos($bahasan, 'sesuai rps') !== false ||
            stripos($bahasan, 'sudah') !== false
        ) {
            return true;
        }

        if (preg_match('/\b(uts|uas|ujian tengah semester|ujian akhir semester)\b/i', $rencana)) {
            return true;
        }

        // Pencocokan kata kunci bermakna (untuk deskripsi capaian dari Edlink yang memparafrase judul rencana)
        $stopwords = [
            'dan', 'atau', 'yang', 'dalam', 'pada', 'dari', 'untuk', 'dengan',
            'sebagai', 'oleh', 'serta', 'tentang', 'konsep', 'pengantar', 'materi',
        ];
        $words = preg_split('/[^a-z0-9]+/i', mb_strtolower($rencana), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $keywords = array_values(array_filter(
            array_unique($words),
            fn (string $w) => strlen($w) >= 4 && ! in_array($w, $stopwords, true)
        ));

        if (! empty($keywords)) {
            $bahasanLower = mb_strtolower($bahasan);
            $matched = 0;
            foreach ($keywords as $kw) {
                if (str_contains($bahasanLower, $kw)) {
                    $matched++;
                }
            }

            if (($matched / count($keywords)) >= 0.6) {
                return true;
            }
        }

        return false;
    }

    /**
     * Apakah jalur snapshot cepat (/edlink/v1/kelas + /edlink/v1/sesi + /siakadcloud/v1/presensi-dosen) diaktifkan.
     */
    protected function shouldUseFastSnapshot(): bool
    {
        if (app()->runningUnitTests()) {
            return (bool) config('services.siadiknona.enable_snapshot_in_tests', false);
        }

        return (bool) config('services.siadiknona.fast_snapshot', true);
    }

    /**
     * Deteksi mata kuliah lapangan/akhir non-tatap muka (KKN, Tugas Akhir, Skripsi, PKL, Magang)
     * yang secara akademik memang tidak memiliki 16 sesi jadwal perkuliahan kelas di SIAKAD.
     */
    protected function isNonClassroomCourse(?string $namaMk): bool
    {
        if (! $namaMk) {
            return false;
        }

        return (bool) preg_match(
            '/\b(kuliah kerja nyata|kkn|tugas akhir|skripsi|tesis|disertasi|kerja praktek|kerja praktik|pkl|magang)\b/i',
            $namaMk
        );
    }

    /**
     * Buat kunci normalisasi "NAMA_MK|NAMA_KELAS" sebagai jembatan cadangan Edlink -> Perkuliahan.
     */
    protected function makeMkKelasKey(?string $namaMk, ?string $namaKelas): string
    {
        $mk = preg_replace('/\s+/u', ' ', mb_strtoupper(trim($namaMk ?? ''))) ?? '';
        $kls = preg_replace('/^kelas\s*:\s*/iu', '', trim($namaKelas ?? '')) ?? '';
        $kls = preg_replace('/\s+/u', ' ', mb_strtoupper(trim($kls))) ?? '';

        return "{$mk}|{$kls}";
    }

    /**
     * Ambil seluruh halaman dari endpoint snapshot cepat SIADIKNONA menggunakan Keep-Alive pada halaman 2..N.
     */
    protected function fetchAllSnapshotPages(string $path, array $baseFilters, int $maxPages = 25): array
    {
        $first = $this->api->getList($path, $baseFilters, 1);
        $rows = $first['data'] ?? [];
        $lastPage = min($maxPages, max(1, (int) ($first['meta']['lastPage'] ?? 1)));

        if ($lastPage > 1) {
            $pageFilters = [];
            for ($p = 2; $p <= $lastPage; $p++) {
                $pageFilters[$p] = array_merge($baseFilters, ['page' => $p]);
            }

            $batch = $this->api->getBatchListsKeepAlive($path, $pageFilters, 1);
            for ($p = 2; $p <= $lastPage; $p++) {
                if (! empty($batch[$p]['ok']) && isset($batch[$p]['data']['data'])) {
                    $rows = array_merge($rows, $batch[$p]['data']['data']);
                } else {
                    $retry = $this->api->getList($path, $baseFilters, $p);
                    $rows = array_merge($rows, $retry['data'] ?? []);
                }
            }
        }

        return $rows;
    }

    /**
     * Bangun peta realisasi seluruh kelas Prodi untuk 1 periode dari snapshot cepat SIADIKNONA
     * (/edlink/v1/kelas + /edlink/v1/sesi + /siakadcloud/v1/presensi-dosen) dalam ~10-18 detik,
     * menghindari timeout 60-95 detik per kelas pada endpoint live /siakadcloud/v1/perkuliahan.
     *
     * @return array{by_kelas_id: array<string, array>, by_mk_kelas: array<string, array>}|null
     */
    public function getBulkRealizationSnapshot(string $periodeId, bool $forceRefresh = false): ?array
    {
        if (! $this->shouldUseFastSnapshot()) {
            return null;
        }

        $prodiId = $this->api->getProdiId();
        $cacheKey = "siadiknona:realization_snapshot:{$prodiId}:{$periodeId}";

        if (! $forceRefresh) {
            if (isset($this->memorySnapshotCache[$cacheKey])) {
                return $this->memorySnapshotCache[$cacheKey];
            }

            $cached = Cache::get($cacheKey);
            if (is_array($cached) && isset($cached['by_kelas_id'])) {
                $this->memorySnapshotCache[$cacheKey] = $cached;

                return $cached;
            }
        }

        try {
            // 1. Jembatan ID kelas Edlink <-> ID kelas SIAKAD (/edlink/v1/kelas)
            $edlinkKelasRows = $this->fetchAllSnapshotPages(SiadiknonaApiService::EDLINK_KELAS, [
                'id_program_studi_siakad' => $prodiId,
                'periode' => $periodeId,
            ], 10);

            $edlinkToSiakad = [];
            foreach ($edlinkKelasRows as $row) {
                $attr = $row['attributes'] ?? [];
                $edlinkId = trim((string) ($attr['id_kelas'] ?? ($row['id'] ?? '')));
                $siakadId = trim((string) ($attr['id_kelas_siakad'] ?? ''));
                if ($edlinkId !== '' && $siakadId !== '') {
                    $edlinkToSiakad[$edlinkId] = $siakadId;
                }
            }

            // 2. Seluruh jadwal & realisasi sesi Edlink (/edlink/v1/sesi: 16 sesi per kelas)
            $edlinkSesiRows = $this->fetchAllSnapshotPages(SiadiknonaApiService::EDLINK_SESI, [
                'id_program_studi_siakad' => $prodiId,
                'periode' => $periodeId,
            ], 30);

            $edlinkBySiakadId = [];
            $edlinkUnmappedByMkKelas = [];
            $edlinkMkKelasToSiakad = [];

            foreach ($edlinkSesiRows as $idx => $row) {
                $attr = $row['attributes'] ?? [];
                $edlinkId = trim((string) ($attr['id_kelas'] ?? ''));
                $siakadId = $edlinkToSiakad[$edlinkId] ?? '';
                $nomor = (int) ($attr['sesi_ke'] ?? 0);
                $slotKey = $nomor > 0 ? $nomor : (1000 + $idx);
                $mkKey = $this->makeMkKelasKey($attr['nama_kelas'] ?? '', $attr['deskripsi_kelas'] ?? '');

                if ($siakadId !== '') {
                    $edlinkBySiakadId[$siakadId][$slotKey] = $attr;
                    if ($mkKey !== '|' && ! isset($edlinkMkKelasToSiakad[$mkKey])) {
                        $edlinkMkKelasToSiakad[$mkKey] = $siakadId;
                    }
                } elseif ($mkKey !== '|') {
                    $edlinkUnmappedByMkKelas[$mkKey][$slotKey] = $attr;
                }
            }

            // 3. Realisasi presensi & berita acara mengajar dosen (/siakadcloud/v1/presensi-dosen)
            $presensiRows = $this->fetchAllSnapshotPages(SiadiknonaApiService::PRESENSI_DOSEN, [
                'id_program_studi_kelas' => $prodiId,
                'id_periode_kelas' => $periodeId,
            ], 20);

            $presensiBySiakadId = [];
            foreach ($presensiRows as $idx => $row) {
                $attr = $row['attributes'] ?? [];
                $siakadId = trim((string) ($attr['id_kelas'] ?? ''));
                if ($siakadId === '') {
                    continue;
                }

                $nomor = (int) ($attr['urutan_pertemuan_perkuliahan'] ?? 0);
                $slotKey = $nomor > 0 ? $nomor : (2000 + $idx);

                $namaDosen = trim($attr['nama_dosen'] ?? '');
                $nip = trim($attr['nip'] ?? '');
                $hadir = strtoupper(trim($attr['status_hadir'] ?? '')) === 'H';
                $rencana = trim($attr['rencana_materi'] ?? '');
                $realisasi = trim($attr['realisasi_materi'] ?? '');
                $statusPerk = trim($attr['status_perkuliahan'] ?? '');
                $isSelesai = strcasecmp($statusPerk, 'Selesai') === 0
                    || (string) ($attr['id_status_perkuliahan'] ?? '') === '1';

                if (! isset($presensiBySiakadId[$siakadId][$slotKey])) {
                    $presensiBySiakadId[$siakadId][$slotKey] = [
                        'id_perkuliahan' => $attr['id_perkuliahan'] ?? null,
                        'nomor' => $nomor,
                        'tanggal' => trim($attr['tanggal_perkuliahan'] ?? ''),
                        'nama_dosen' => $namaDosen,
                        'nip' => $nip,
                        'has_preferred_dosen' => $hadir || $realisasi !== '',
                        'dosens_pengisi' => $namaDosen !== '' ? [['nama' => $namaDosen, 'nip' => $nip]] : [],
                        'id_jenis_jadwal' => trim($attr['id_jenis_jadwal'] ?? ''),
                        'rencana_materi' => $rencana,
                        'realisasi_materi' => $realisasi,
                        'status_perkuliahan' => $statusPerk,
                        'is_selesai' => $isSelesai,
                    ];
                } else {
                    $cur = &$presensiBySiakadId[$siakadId][$slotKey];
                    if ($namaDosen !== '') {
                        $alreadyListed = false;
                        foreach ($cur['dosens_pengisi'] as $dp) {
                            if (strcasecmp($dp['nama'], $namaDosen) === 0) {
                                $alreadyListed = true;
                                break;
                            }
                        }
                        if (! $alreadyListed) {
                            $cur['dosens_pengisi'][] = ['nama' => $namaDosen, 'nip' => $nip];
                        }

                        if (! $cur['has_preferred_dosen'] && ($hadir || $realisasi !== '')) {
                            $cur['nama_dosen'] = $namaDosen;
                            $cur['nip'] = $nip;
                            $cur['has_preferred_dosen'] = true;
                        } elseif ($cur['nama_dosen'] === '') {
                            $cur['nama_dosen'] = $namaDosen;
                            $cur['nip'] = $nip;
                        }
                    }

                    if ($cur['rencana_materi'] === '' && $rencana !== '') {
                        $cur['rencana_materi'] = $rencana;
                    }
                    if ($cur['realisasi_materi'] === '' && $realisasi !== '') {
                        $cur['realisasi_materi'] = $realisasi;
                    }
                    if ($cur['tanggal'] === '' && ! empty($attr['tanggal_perkuliahan'])) {
                        $cur['tanggal'] = trim($attr['tanggal_perkuliahan']);
                    }
                    if ($cur['id_jenis_jadwal'] === '' && ! empty($attr['id_jenis_jadwal'])) {
                        $cur['id_jenis_jadwal'] = trim($attr['id_jenis_jadwal']);
                    }
                    if ($isSelesai) {
                        $cur['is_selesai'] = true;
                        $cur['status_perkuliahan'] = 'Selesai';
                    }
                    unset($cur);
                }
            }

            // 4. Gabungkan sesi Edlink & Presensi Dosen menjadi format standar parseRealizationItems
            $allSiakadIds = array_unique(array_merge(
                array_keys($edlinkBySiakadId),
                array_keys($presensiBySiakadId)
            ));

            $byKelasId = [];
            foreach ($allSiakadIds as $sid) {
                $eMap = $edlinkBySiakadId[$sid] ?? [];
                $pMap = $presensiBySiakadId[$sid] ?? [];
                $byKelasId[(string) $sid] = $this->mergeSnapshotClassSessions((string) $sid, $eMap, $pMap);
            }

            $byMkKelas = [];
            foreach ($edlinkMkKelasToSiakad as $mkKey => $sid) {
                if (isset($byKelasId[(string) $sid])) {
                    $byMkKelas[$mkKey] = $byKelasId[(string) $sid];
                }
            }
            foreach ($edlinkUnmappedByMkKelas as $mkKey => $eMap) {
                if (! isset($byMkKelas[$mkKey])) {
                    $byMkKelas[$mkKey] = $this->mergeSnapshotClassSessions($mkKey, $eMap, []);
                }
            }

            if (empty($byKelasId) && empty($byMkKelas)) {
                return null;
            }

            $snapshot = [
                'by_kelas_id' => $byKelasId,
                'by_mk_kelas' => $byMkKelas,
            ];

            $this->memorySnapshotCache[$cacheKey] = $snapshot;
            Cache::put($cacheKey, $snapshot, 300);

            return $snapshot;
        } catch (Throwable $e) {
            Log::warning('SiakadSyncService::getBulkRealizationSnapshot fallback ke /perkuliahan', [
                'periode' => $periodeId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Gabungkan sesi Edlink dan Presensi Dosen pada satu kelas menjadi struktur item /siakadcloud/v1/perkuliahan.
     */
    protected function mergeSnapshotClassSessions(string $classKey, array $edlinkMap, array $presensiMap): array
    {
        $slotKeys = array_unique(array_merge(array_keys($edlinkMap), array_keys($presensiMap)));
        sort($slotKeys, SORT_NUMERIC);

        $merged = [];
        foreach ($slotKeys as $idx => $slot) {
            $e = $edlinkMap[$slot] ?? null;
            $p = $presensiMap[$slot] ?? null;

            $nomor = (int) ($p['nomor'] ?? ($e['sesi_ke'] ?? 0));
            if ($nomor <= 0) {
                $nomor = $idx + 1;
            }

            $jenisKode = trim((string) ($p['id_jenis_jadwal'] ?? ''));
            if ($jenisKode === '') {
                $jenisEdlink = strtoupper(trim((string) ($e['jenis_sesi'] ?? '')));
                $jenisKode = match ($jenisEdlink) {
                    'UTS' => 'T',
                    'UAS' => 'A',
                    default => 'K',
                };
            }

            $rencana = ! empty($p['rencana_materi'])
                ? $p['rencana_materi']
                : (string) ($e['topik'] ?? '');

            $bahasan = ! empty($p['realisasi_materi'])
                ? $p['realisasi_materi']
                : (string) ($e['capaian'] ?? '');

            $isSelesai = ! empty($p['is_selesai'])
                || strcasecmp(trim((string) ($e['status_sesi'] ?? '')), 'Selesai') === 0;

            $statusStr = $isSelesai
                ? 'Selesai'
                : (trim((string) ($p['status_perkuliahan'] ?? '')) ?: trim((string) ($e['status_sesi'] ?? '')) ?: 'Terjadwal');

            $tanggal = ! empty($p['tanggal'])
                ? $p['tanggal']
                : (! empty($e['waktu_mulai_sesi']) ? substr(trim((string) $e['waktu_mulai_sesi']), 0, 10) : '-');

            $metode = (! empty($e['link_conference']) || stripos((string) ($e['jenis_sesi'] ?? ''), 'online') !== false)
                ? 'Online'
                : 'Offline';

            $merged[] = [
                'id' => (string) ($p['id_perkuliahan'] ?? ($e['id_sesi'] ?? "{$classKey}-{$nomor}")),
                'attributes' => [
                    'nomor_pertemuan' => (string) $nomor,
                    'tanggal' => $tanggal,
                    'rencana_materi' => $rencana,
                    'bahasan' => $bahasan,
                    'id_jenis_perkuliahan' => $jenisKode,
                    'nip_pengisi_materi' => $p['nip'] ?? '',
                    'nama_pengisi_materi' => $p['nama_dosen'] ?? '',
                    'extra_pengisi' => $p['dosens_pengisi'] ?? [],
                    'metode_pembelajaran' => $metode,
                    'kode_status_perkuliahan' => $isSelesai ? '1' : '0',
                    'status_perkuliahan' => $statusStr,
                    'is_deleted' => '0',
                ],
            ];
        }

        return $merged;
    }

    /**
     * Ambil data realisasi perkuliahan dari SIAKAD Cloud untuk form Monev RingkasanPerkuliahan.
     * Mengutamakan snapshot cepat (/edlink/v1/kelas + /edlink/v1/sesi + /siakadcloud/v1/presensi-dosen)
     * dengan fallback otomatis ke /siakadcloud/v1/perkuliahan.
     */
    public function fetchRealizationData(Perkuliahan $perkuliahan): array
    {
        $perkuliahan->loadMissing(['mataKuliah', 'kelas', 'semester.tahunAkademik']);

        $mataKuliah = $perkuliahan->mataKuliah;
        $kelas = $perkuliahan->kelas;
        $kodeMk = $mataKuliah?->kode_mk;
        $namaMk = $mataKuliah?->nama_mk;
        $namaKelas = $kelas?->nama_kelas;
        $periodeId = $this->resolveSiakadPeriode($perkuliahan->semester);
        $siakadKelasId = trim((string) ($perkuliahan->siakad_kelas_id ?? '')) ?: null;

        $data = [
            'found' => false,
            'jumlah_pertemuan' => 0,
            'kesesuaian_materi' => 'tidak_sesuai',
            'kontrak_kuliah' => 'tidak_ada',
            'metode_pembelajaran' => 'Offline',
            'catatan' => '',
            'detail_pertemuan' => [],
            'error' => null,
        ];

        if (! $siakadKelasId && (! $kodeMk || ! $namaKelas)) {
            return $data;
        }

        try {
            // 1. Cek snapshot cepat Prodi terlebih dahulu (bebas timeout untuk puluhan kelas sekaligus)
            $snapshot = $this->getBulkRealizationSnapshot($periodeId);
            if ($snapshot !== null) {
                $snapshotItems = null;
                if ($siakadKelasId) {
                    $snapshotItems = $snapshot['by_kelas_id'][$siakadKelasId] ?? null;
                } elseif ($namaMk && $namaKelas) {
                    $mkKey = $this->makeMkKelasKey($namaMk, $namaKelas);
                    $snapshotItems = $snapshot['by_mk_kelas'][$mkKey] ?? null;
                }

                if (! empty($snapshotItems)) {
                    return $this->parseRealizationItems($perkuliahan, $snapshotItems, $kodeMk);
                }

                // Mata kuliah non-tatap muka (KKN / Tugas Akhir / Skripsi / PKL) tidak memiliki 16 sesi kelas
                if ($this->isNonClassroomCourse($namaMk)) {
                    return $data;
                }
            }

            // 2. Fallback: jika siakad_kelas_id belum tersimpan (perkuliahan dibuat manual), cari di /v1/kelas
            if (! $siakadKelasId && $kodeMk && $namaKelas) {
                try {
                    $kelasRes = $this->api->getKelasList([
                        'id_program_studi' => $this->api->getProdiId(),
                        'id_periode' => $periodeId,
                        'kode_mata_kuliah' => $kodeMk,
                        'nama_kelas' => $namaKelas,
                    ], 1, 10);

                    foreach ($kelasRes['data'] ?? [] as $kRow) {
                        $kAttr = $kRow['attributes'] ?? [];
                        if (
                            (string) ($kAttr['is_deleted'] ?? '0') !== '1' &&
                            strcasecmp(trim($kAttr['kode_mata_kuliah'] ?? ''), $kodeMk) === 0 &&
                            strcasecmp(trim($kAttr['nama_kelas'] ?? ''), $namaKelas) === 0 &&
                            ! empty($kRow['id'])
                        ) {
                            $siakadKelasId = (string) $kRow['id'];
                            $perkuliahan->update(['siakad_kelas_id' => $siakadKelasId]);
                            break;
                        }
                    }
                } catch (Throwable) {
                    // Lanjut ke fallback query kode_mata_kuliah + nama_kelas
                }
            }

            // 3. Fallback ke endpoint live /siakadcloud/v1/perkuliahan per kelas
            $filters = $siakadKelasId
                ? [
                    'id_kelas' => $siakadKelasId,
                    'id_periode' => $periodeId,
                ]
                : [
                    'id_program_studi' => $this->api->getProdiId(),
                    'id_periode' => $periodeId,
                    'kode_mata_kuliah' => $kodeMk,
                    'nama_kelas' => $namaKelas,
                ];

            $res = $this->api->getPerkuliahanList($filters, 1);
            $rawItems = $res['data'] ?? [];
            $data = $this->parseRealizationItems($perkuliahan, $rawItems, $kodeMk);
        } catch (Throwable $e) {
            $data['error'] = $e->getMessage();
            Log::warning('SiakadSyncService::fetchRealizationData error', [
                'perkuliahan_id' => $perkuliahan->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $data;
    }

    /**
     * Olah daftar sesi mentah dari endpoint /siakadcloud/v1/perkuliahan menjadi ringkasan Monev.
     */
    public function parseRealizationItems(Perkuliahan $perkuliahan, array $rawItems, ?string $kodeMk = null): array
    {
        $data = [
            'found' => false,
            'jumlah_pertemuan' => 0,
            'kesesuaian_materi' => 'tidak_sesuai',
            'kontrak_kuliah' => 'tidak_ada',
            'metode_pembelajaran' => 'Offline',
            'catatan' => '',
            'detail_pertemuan' => [],
            'error' => null,
        ];

        $items = array_values(array_filter(
            $rawItems,
            fn (array $row) => (string) (($row['attributes']['is_deleted'] ?? '0')) !== '1'
        ));

        usort($items, fn (array $a, array $b) => ((int) ($a['attributes']['nomor_pertemuan'] ?? 0)) <=> ((int) ($b['attributes']['nomor_pertemuan'] ?? 0)));

        if (! empty($items)) {
            $data['found'] = true;
            $pertemuanSelesai = 0;
            $materiSesuaiCount = 0;
            $hasKontrak = false;
            $metodeList = [];
            $needsPengajarBackfill = ! $perkuliahan->pengajars()->exists();

            foreach ($items as $row) {
                $attr = $row['attributes'] ?? [];
                $nomor = (int) ($attr['nomor_pertemuan'] ?? 0);
                $status = trim($attr['status_perkuliahan'] ?? '');
                $pengisi = trim($attr['nama_pengisi_materi'] ?? '');
                $nipPengisi = trim($attr['nip_pengisi_materi'] ?? '') ?: null;
                $jenisKode = trim($attr['id_jenis_perkuliahan'] ?? '');
                $rencana = $this->cleanHtmlText($attr['rencana_materi'] ?? '');
                $bahasan = $this->cleanHtmlText($attr['bahasan'] ?? '');
                $metode = trim($attr['metode_pembelajaran'] ?? '');

                if ($needsPengajarBackfill) {
                    $candidates = ! empty($attr['extra_pengisi']) && is_array($attr['extra_pengisi'])
                        ? $attr['extra_pengisi']
                        : ($pengisi !== '' ? [['nama' => $pengisi, 'nip' => $nipPengisi]] : []);

                    foreach ($candidates as $cand) {
                        $candNama = trim((string) ($cand['nama'] ?? ''));
                        if ($candNama !== '') {
                            $dosen = $this->resolveOrCreateDosen($candNama, trim((string) ($cand['nip'] ?? '')) ?: null, null);
                            if ($dosen) {
                                Pengajar::firstOrCreate([
                                    'perkuliahan_id' => $perkuliahan->id,
                                    'dosen_id' => $dosen->id,
                                ]);
                            }
                        }
                    }
                }

                $isSelesai = strcasecmp($status, 'Selesai') === 0 || (string) ($attr['kode_status_perkuliahan'] ?? '') === '1';

                if ($isSelesai) {
                    $pertemuanSelesai++;

                    if ($this->isMateriSesuai($rencana, $bahasan, $jenisKode)) {
                        $materiSesuaiCount++;
                    }
                }

                if ($metode !== '') {
                    $metodeList[] = $metode;
                }

                if (stripos($rencana . ' ' . $bahasan, 'kontrak') !== false) {
                    $hasKontrak = true;
                }

                $data['detail_pertemuan'][] = [
                    'pertemuan' => $nomor ?: (count($data['detail_pertemuan']) + 1),
                    'tanggal' => $attr['tanggal'] ?? '-',
                    'dosen' => $pengisi ?: '-',
                    'status' => $status ?: ($isSelesai ? 'Selesai' : 'Terjadwal'),
                    'rencana' => $rencana,
                    'realisasi' => $bahasan,
                    'metode' => $metode,
                ];
            }

            $data['jumlah_pertemuan'] = $pertemuanSelesai;

            if ($pertemuanSelesai > 0) {
                $ratio = $materiSesuaiCount / $pertemuanSelesai;
                if ($ratio >= 0.75) {
                    $data['kesesuaian_materi'] = 'sesuai';
                } elseif ($ratio >= 0.4) {
                    $data['kesesuaian_materi'] = 'sebagian';
                } else {
                    $data['kesesuaian_materi'] = 'tidak_sesuai';
                }
            }

            $data['kontrak_kuliah'] = $hasKontrak ? 'ada' : 'tidak_ada';

            if (! empty($metodeList)) {
                $counts = array_count_values($metodeList);
                arsort($counts);
                $data['metode_pembelajaran'] = array_key_first($counts) ?: 'Offline';
            }

            $totalSesi = count($items);
            $data['catatan'] = "Ditarik dari SIAKAD Cloud: {$pertemuanSelesai} pertemuan selesai dari {$totalSesi} sesi terjadwal.";
        }

        if ($data['found'] && $data['kontrak_kuliah'] === 'tidak_ada' && $kodeMk) {
            try {
                $hasRps = Cache::remember(
                    'siadiknona:has_rps:' . md5($kodeMk),
                    3600,
                    function () use ($kodeMk) {
                        $rpsRes = $this->api->getRpsList(['kode_mata_kuliah' => $kodeMk], 1, 5);

                        return ! empty($rpsRes['data']);
                    }
                );

                if ($hasRps) {
                    $data['kontrak_kuliah'] = 'ada';
                }
            } catch (Throwable) {
                // Abaikan error RPS
            }
        }

        return $data;
    }

    /**
     * Sinkronisasi sekumpulan Perkuliahan sekaligus (mengutamakan snapshot cepat Prodi, fallback Keep-Alive).
     *
     * @param  iterable<Perkuliahan>  $perkuliahans
     */
    public function syncBatchRingkasanPerkuliahan(
        JadwalMonev $jadwalMonev,
        iterable $perkuliahans,
        ?int $userId = null
    ): array {
        $batchResults = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'failed' => 0,
            'items' => [],
        ];

        $jadwalMonev->loadMissing('semester.tahunAkademik');
        $defaultPeriode = $this->resolveSiakadPeriode($jadwalMonev->semester);
        $snapshot = $this->getBulkRealizationSnapshot($defaultPeriode);

        if ($snapshot !== null) {
            foreach ($perkuliahans as $perkuliahan) {
                $res = $this->syncSingleRingkasanPerkuliahan($jadwalMonev, $perkuliahan, $userId);
                $outcome = $res['outcome'];
                if (isset($batchResults[$outcome])) {
                    $batchResults[$outcome]++;
                }
                $batchResults['items'][$perkuliahan->id] = [
                    'outcome' => $outcome,
                    'jumlah_pertemuan' => $res['realisasi']['jumlah_pertemuan'] ?? null,
                    'message' => $res['message'],
                ];
            }

            return $batchResults;
        }

        $perkuliahanMap = [];
        $keyedFilters = [];

        foreach ($perkuliahans as $perkuliahan) {
            $perkuliahan->loadMissing(['mataKuliah', 'kelas', 'semester.tahunAkademik']);
            $existing = RingkasanPerkuliahan::where('jadwal_monev_id', $jadwalMonev->id)
                ->where('perkuliahan_id', $perkuliahan->id)
                ->first();

            if ($existing && $existing->status === 'diverifikasi') {
                $batchResults['skipped']++;
                $batchResults['items'][$perkuliahan->id] = [
                    'outcome' => 'skipped',
                    'message' => 'Sudah diverifikasi',
                ];
                continue;
            }

            $periodeId = $this->resolveSiakadPeriode($perkuliahan->semester);
            $siakadKelasId = trim((string) ($perkuliahan->siakad_kelas_id ?? '')) ?: null;
            $kodeMk = $perkuliahan->mataKuliah?->kode_mk;
            $namaKelas = $perkuliahan->kelas?->nama_kelas;

            if (! $siakadKelasId && (! $kodeMk || ! $namaKelas)) {
                $batchResults['skipped']++;
                $batchResults['items'][$perkuliahan->id] = [
                    'outcome' => 'skipped',
                    'message' => 'Data kelas/MK tidak lengkap',
                ];
                continue;
            }

            $perkuliahanMap[$perkuliahan->id] = [
                'model' => $perkuliahan,
                'existing' => $existing,
                'kode_mk' => $kodeMk,
            ];

            $keyedFilters[$perkuliahan->id] = $siakadKelasId
                ? ['id_kelas' => $siakadKelasId, 'id_periode' => $periodeId]
                : [
                    'id_program_studi' => $this->api->getProdiId(),
                    'id_periode' => $periodeId,
                    'kode_mata_kuliah' => $kodeMk,
                    'nama_kelas' => $namaKelas,
                ];
        }

        if (empty($keyedFilters)) {
            return $batchResults;
        }

        $responses = $this->api->getBatchListsKeepAlive(SiadiknonaApiService::PERKULIAHAN, $keyedFilters, 1);

        foreach ($perkuliahanMap as $pid => $meta) {
            /** @var Perkuliahan $perkuliahan */
            $perkuliahan = $meta['model'];
            /** @var ?RingkasanPerkuliahan $existing */
            $existing = $meta['existing'];
            $resp = $responses[$pid] ?? ['ok' => false, 'data' => [], 'error' => 'Tidak ada respons'];

            if (! $resp['ok']) {
                $batchResults['failed']++;
                $batchResults['items'][$pid] = [
                    'outcome' => 'failed',
                    'message' => $resp['error'] ?? 'Koneksi ke API SIAKAD gagal',
                ];
                continue;
            }

            $rawItems = $resp['data']['data'] ?? [];
            $realisasi = $this->parseRealizationItems($perkuliahan, $rawItems, $meta['kode_mk']);

            if (! $realisasi['found']) {
                $batchResults['skipped']++;
                $batchResults['items'][$pid] = [
                    'outcome' => 'skipped',
                    'message' => '0 sesi pertemuan terjadwal di SIAKAD Cloud',
                ];
                continue;
            }

            $payload = [
                'jadwal_monev_id' => $jadwalMonev->id,
                'perkuliahan_id' => $perkuliahan->id,
                'jumlah_pertemuan' => $realisasi['jumlah_pertemuan'],
                'kesesuaian_materi' => $realisasi['kesesuaian_materi'],
                'kontrak_kuliah' => $realisasi['kontrak_kuliah'],
                'metode_pembelajaran' => $realisasi['metode_pembelajaran'],
                'keterangan' => $realisasi['catatan'],
                'status' => 'diajukan',
                'input_by' => $userId ?? auth()->id() ?? 1,
            ];

            if ($existing) {
                $existing->update($payload);
                $batchResults['updated']++;
                $batchResults['items'][$pid] = [
                    'outcome' => 'updated',
                    'jumlah_pertemuan' => $realisasi['jumlah_pertemuan'],
                    'message' => $realisasi['catatan'],
                ];
            } else {
                RingkasanPerkuliahan::create($payload);
                $batchResults['created']++;
                $batchResults['items'][$pid] = [
                    'outcome' => 'created',
                    'jumlah_pertemuan' => $realisasi['jumlah_pertemuan'],
                    'message' => $realisasi['catatan'],
                ];
            }
        }

        return $batchResults;
    }

    /**
     * Sinkronisasi satu item Ringkasan Perkuliahan untuk Jadwal Monev tertentu dan langsung simpan ke database.
     */
    public function syncSingleRingkasanPerkuliahan(
        JadwalMonev $jadwalMonev,
        Perkuliahan $perkuliahan,
        ?int $userId = null
    ): array {
        $existing = RingkasanPerkuliahan::where('jadwal_monev_id', $jadwalMonev->id)
            ->where('perkuliahan_id', $perkuliahan->id)
            ->first();

        if ($existing && $existing->status === 'diverifikasi') {
            return [
                'outcome' => 'skipped',
                'message' => 'Ringkasan sudah diverifikasi sehingga dikunci.',
                'realisasi' => null,
                'ringkasan' => $existing,
            ];
        }

        $realisasi = $this->fetchRealizationData($perkuliahan);

        if (! $realisasi['found']) {
            if (! empty($realisasi['error'])) {
                return [
                    'outcome' => 'failed',
                    'message' => $realisasi['error'],
                    'realisasi' => $realisasi,
                    'ringkasan' => $existing,
                ];
            }

            return [
                'outcome' => 'skipped',
                'message' => 'Belum ditemukan sesi pertemuan terjadwal di SIAKAD Cloud.',
                'realisasi' => $realisasi,
                'ringkasan' => $existing,
            ];
        }

        $payload = [
            'jadwal_monev_id' => $jadwalMonev->id,
            'perkuliahan_id' => $perkuliahan->id,
            'jumlah_pertemuan' => $realisasi['jumlah_pertemuan'],
            'kesesuaian_materi' => $realisasi['kesesuaian_materi'],
            'kontrak_kuliah' => $realisasi['kontrak_kuliah'],
            'metode_pembelajaran' => $realisasi['metode_pembelajaran'],
            'keterangan' => $realisasi['catatan'],
            'status' => 'diajukan',
            'input_by' => $userId ?? auth()->id() ?? 1,
        ];

        if ($existing) {
            $existing->update($payload);

            return [
                'outcome' => 'updated',
                'message' => $realisasi['catatan'],
                'realisasi' => $realisasi,
                'ringkasan' => $existing->fresh(),
            ];
        }

        $created = RingkasanPerkuliahan::create($payload);

        return [
            'outcome' => 'created',
            'message' => $realisasi['catatan'],
            'realisasi' => $realisasi,
            'ringkasan' => $created,
        ];
    }

    /**
     * Sinkronisasi massal seluruh Ringkasan Perkuliahan untuk Jadwal Monev tertentu.
     */
    public function syncRingkasanPerkuliahanForJadwalMonev(JadwalMonev $jadwalMonev, ?int $userId = null): array
    {
        @set_time_limit(0);

        $stats = [
            'total' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        $jadwalMonev->loadMissing('semester');
        $semesterId = $jadwalMonev->semester_id;

        if (! $semesterId) {
            $stats['errors'][] = 'Jadwal monev tidak memiliki semester terkait.';
            return $stats;
        }

        $perkuliahans = Perkuliahan::with(['mataKuliah', 'kelas', 'semester.tahunAkademik'])
            ->where('semester_id', $semesterId)
            ->where('status', 'aktif')
            ->get();

        $stats['total'] = $perkuliahans->count();

        foreach ($perkuliahans as $perkuliahan) {
            try {
                $res = $this->syncSingleRingkasanPerkuliahan($jadwalMonev, $perkuliahan, $userId);
                $outcome = $res['outcome'];

                if ($outcome === 'created') {
                    $stats['created']++;
                } elseif ($outcome === 'updated') {
                    $stats['updated']++;
                } elseif ($outcome === 'failed') {
                    $stats['failed']++;
                    $stats['skipped']++;
                    $stats['errors'][] = "MK {$perkuliahan->mataKuliah?->nama_mk} ({$perkuliahan->kelas?->nama_kelas}): {$res['message']}";
                } else {
                    $stats['skipped']++;
                }
            } catch (Throwable $e) {
                $stats['failed']++;
                $stats['errors'][] = "MK {$perkuliahan->mataKuliah?->nama_mk}: " . $e->getMessage();
            }
        }

        return $stats;
    }
}
