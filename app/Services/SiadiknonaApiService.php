<?php

namespace App\Services;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\LazyCollection;
use Psr\Http\Message\RequestInterface;
use RuntimeException;

/**
 * Client read-only untuk API SIADIKNONA Undana.
 *
 * Format respons list : { meta: {total, currentPage, perPage, lastPage}, urls: {...}, data: [...] }
 * Format respons item : { type, url, id, relationships, attributes: {...} }
 * Filter dinamis       : kirim sebagai query terpisah, mis. ['f-nidn' => '0012345678']
 *                        atau dengan operator: f-{atribut}-eq / -ne / -gt / -gte / -lt / -lte / -in
 */
class SiadiknonaApiService
{
    // I. Master Data Akademik
    public const DOSEN = '/siakadcloud/v1/dosen';
    public const MATA_KULIAH = '/siakadcloud/v1/mata-kuliah';
    public const KELAS = '/siakadcloud/v1/kelas';
    public const PERIODE = '/siakadcloud/v1/periode';
    public const PERKULIAHAN = '/siakadcloud/v1/perkuliahan';
    public const PROGRAM_STUDI = '/siakadcloud/v1/program-studi';
    public const FAKULTAS = '/siakadcloud/v1/fakultas';

    // II. Monev Pelaksanaan Perkuliahan
    public const PRESENSI_DOSEN = '/siakadcloud/v1/presensi-dosen';
    public const PRESENSI_MAHASISWA = '/siakadcloud/v1/presensi-mahasiswa';
    public const RPS = '/siakadcloud/v1/rps';
    public const CPMK = '/siakadcloud/v1/cpmk';
    public const EDLINK_KELAS = '/edlink/v1/kelas';
    public const EDLINK_SESI = '/edlink/v1/sesi';
    public const EDLINK_BAHAN_AJAR = '/edlink/v1/bahan-ajar';

    private array $config;

    public function __construct()
    {
        $this->config = config('services.siadiknona');
    }

    /* =====================================================================
     |  I. Master Data Akademik
     * ===================================================================== */

    public function getDosenList(array $filters = [], int $page = 1, ?int $perPage = null): array
    {
        return $this->getList(self::DOSEN, $filters, $page, $perPage);
    }

    public function getDosen(string $id): array
    {
        return $this->getOne(self::DOSEN, $id);
    }

    public function getMataKuliahList(array $filters = [], int $page = 1, ?int $perPage = null): array
    {
        return $this->getList(self::MATA_KULIAH, $filters, $page, $perPage);
    }

    public function getMataKuliah(string $id): array
    {
        return $this->getOne(self::MATA_KULIAH, $id);
    }

    public function getKelasList(array $filters = [], int $page = 1, ?int $perPage = null): array
    {
        return $this->getList(self::KELAS, $filters, $page, $perPage);
    }

    public function getKelas(string $id): array
    {
        return $this->getOne(self::KELAS, $id);
    }

    public function getKelasDosenList(string $idKelas, int $page = 1, ?int $perPage = null): array
    {
        return $this->getList(self::KELAS.'/'.rawurlencode($idKelas).'/dosen', [], $page, $perPage);
    }

    public function getPeriodeList(array $filters = [], int $page = 1, ?int $perPage = null): array
    {
        return $this->getList(self::PERIODE, $filters, $page, $perPage);
    }

    public function getPerkuliahanList(array $filters = [], int $page = 1, ?int $perPage = null): array
    {
        return $this->getList(self::PERKULIAHAN, $filters, $page, $perPage);
    }

    public function getProgramStudiList(array $filters = [], int $page = 1, ?int $perPage = null): array
    {
        return $this->getList(self::PROGRAM_STUDI, $filters, $page, $perPage);
    }

    public function getFakultasList(array $filters = [], int $page = 1, ?int $perPage = null): array
    {
        return $this->getList(self::FAKULTAS, $filters, $page, $perPage);
    }

    /* =====================================================================
     |  II. Monev Pelaksanaan Perkuliahan
     * ===================================================================== */

    public function getPresensiDosenList(array $filters = [], int $page = 1, ?int $perPage = null): array
    {
        return $this->getList(self::PRESENSI_DOSEN, $filters, $page, $perPage);
    }

    public function getPresensiDosen(string $id): array
    {
        return $this->getOne(self::PRESENSI_DOSEN, $id);
    }

    public function getPresensiMahasiswaList(array $filters = [], int $page = 1, ?int $perPage = null): array
    {
        return $this->getList(self::PRESENSI_MAHASISWA, $filters, $page, $perPage);
    }

    public function getRpsList(array $filters = [], int $page = 1, ?int $perPage = null): array
    {
        return $this->getList(self::RPS, $filters, $page, $perPage);
    }

    public function getCpmkList(array $filters = [], int $page = 1, ?int $perPage = null): array
    {
        return $this->getList(self::CPMK, $filters, $page, $perPage);
    }

    public function getEdlinkKelasList(array $filters = [], int $page = 1, ?int $perPage = null): array
    {
        return $this->getList(self::EDLINK_KELAS, $filters, $page, $perPage);
    }

    public function getEdlinkSesiList(array $filters = [], int $page = 1, ?int $perPage = null): array
    {
        return $this->getList(self::EDLINK_SESI, $filters, $page, $perPage);
    }

    public function getEdlinkBahanAjarList(array $filters = [], int $page = 1, ?int $perPage = null): array
    {
        return $this->getList(self::EDLINK_BAHAN_AJAR, $filters, $page, $perPage);
    }

    /* =====================================================================
     |  Helper Spesifik Prodi (Ilmu Komputer)
     * ===================================================================== */

    public function getProdiId(): string
    {
        return (string) ($this->config['prodi_id'] ?? '55201');
    }

    public function getProdiNama(): string
    {
        return (string) ($this->config['prodi_nama'] ?? 'Ilmu Komputer');
    }

    public function getDosenIlkom(array $filters = [], int $page = 1, ?int $perPage = null): array
    {
        $filters = array_merge(['id_home_base' => $this->getProdiId()], $filters);

        return $this->getDosenList($filters, $page, $perPage);
    }

    public function getMataKuliahIlkom(array $filters = [], int $page = 1, ?int $perPage = null): array
    {
        $filters = array_merge(['id_program_studi' => $this->getProdiId()], $filters);

        return $this->getMataKuliahList($filters, $page, $perPage);
    }

    public function getKelasIlkom(array $filters = [], int $page = 1, ?int $perPage = null): array
    {
        $filters = array_merge(['id_program_studi' => $this->getProdiId()], $filters);

        return $this->getKelasList($filters, $page, $perPage);
    }

    public function getPerkuliahanIlkom(?string $periodeId = null, array $filters = [], int $page = 1, ?int $perPage = null): array
    {
        $filters = array_merge(['id_program_studi' => $this->getProdiId()], $filters);
        if ($periodeId !== null) {
            $filters['id_periode'] = $periodeId;
        }

        return $this->getPerkuliahanList($filters, $page, $perPage);
    }

    public function getPresensiDosenIlkom(?string $idPeriodeKelas = null, array $filters = [], int $page = 1, ?int $perPage = null): array
    {
        $filters = array_merge(['id_program_studi_kelas' => $this->getProdiId()], $filters);
        if ($idPeriodeKelas !== null) {
            $filters['id_periode_kelas'] = $idPeriodeKelas;
        }

        return $this->getPresensiDosenList($filters, $page, $perPage);
    }

    public function getPeriodeAktif(): ?array
    {
        $res = $this->getPeriodeList(['is_aktif' => '1'], 1, 5);

        return $res['data'][0] ?? null;
    }

    /* =====================================================================
     |  Generic helpers
     * ===================================================================== */

    /**
     * Ambil satu halaman list. Mengembalikan ['meta' => [...], 'urls' => [...], 'data' => [...]].
     */
    public function getList(string $path, array $filters = [], int $page = 1, ?int $perPage = null): array
    {
        $query = array_merge($this->normalizeFilters($filters), [
            'page' => max(1, $page),
        ]);

        if ($perPage !== null) {
            $query['per_page'] = $perPage;
        }

        return $this->get($path, $query);
    }

    /**
     * Ambil satu item berdasarkan ID.
     */
    public function getOne(string $path, string $id): array
    {
        return $this->get(rtrim($path, '/').'/'.rawurlencode($id));
    }

    /**
     * Iterasi seluruh halaman secara lazy (hemat memori) — cocok untuk sinkronisasi.
     * Setiap item yang di-yield adalah elemen dari `data` (berisi id, attributes, dst).
     */
    public function each(string $path, array $filters = [], ?int $perPage = null): LazyCollection
    {
        return LazyCollection::make(function () use ($path, $filters, $perPage) {
            $page = 1;

            do {
                $response = $this->getList($path, $filters, $page, $perPage);
                $items = $response['data'] ?? [];

                foreach ($items as $item) {
                    yield $item;
                }

                $lastPage = (int) ($response['meta']['lastPage'] ?? $page);
                $page++;
            } while (! empty($items) && $page <= $lastPage);
        });
    }

    /**
     * Versi ter-cache dari getList (berguna untuk tampilan / form monev).
     */
    public function cachedList(string $path, array $filters = [], int $page = 1, ?int $perPage = null): array
    {
        $key = 'siadiknona:'.md5($path.'|'.json_encode([$filters, $page, $perPage]));

        return Cache::remember($key, $this->config['cache_ttl'], fn () => $this->getList($path, $filters, $page, $perPage));
    }

    /**
     * Cek apakah kredensial sudah diisi di .env.
     */
    public function isConfigured(): bool
    {
        return filled($this->config['app_key']) && filled($this->config['secret_key']);
    }

    /**
     * Ambil banyak list sekaligus melalui satu koneksi persisten HTTP/1.1 Keep-Alive (menggunakan curl.exe -K - di Windows).
     * Mencegah pemblokiran TCP SYN oleh firewall API Undana saat sinkronisasi puluhan kelas.
     *
     * @param  array<string|int, array<string, mixed>>  $keyedFilters  Map [key => filters]
     * @return array<string|int, array{ok: bool, status: int, data: array, error: ?string}>
     */
    public function getBatchListsKeepAlive(string $path, array $keyedFilters, int $page = 1): array
    {
        $this->ensureConfigured();

        if (empty($keyedFilters)) {
            return [];
        }

        // Gunakan jalur standar bila berjalan di luar Windows atau saat unit/feature test (Http::fake())
        if (PHP_OS_FAMILY !== 'Windows' || ! function_exists('proc_open') || app()->runningUnitTests()) {
            $results = [];
            foreach ($keyedFilters as $key => $filters) {
                $itemPage = isset($filters['page']) ? (int) $filters['page'] : $page;
                unset($filters['page']);
                try {
                    $res = $this->getList($path, $filters, $itemPage);
                    $results[$key] = ['ok' => true, 'status' => 200, 'data' => $res, 'error' => null];
                } catch (\Throwable $e) {
                    $results[$key] = ['ok' => false, 'status' => 0, 'data' => [], 'error' => $e->getMessage()];
                }
            }

            return $results;
        }

        $baseUrl = rtrim($this->config['base_url'], '/');
        $cleanPath = '/'.ltrim($path, '/');
        $keys = array_keys($keyedFilters);
        $configLines = [];

        foreach ($keyedFilters as $filters) {
            $itemPage = isset($filters['page']) ? (int) $filters['page'] : $page;
            unset($filters['page']);
            $query = array_merge($this->normalizeFilters($filters), [
                'page' => max(1, $itemPage),
            ]);
            $url = $baseUrl.$cleanPath.'?'.http_build_query($query);
            $configLines[] = 'url = "'.addcslashes($url, '"\\').'"';
        }

        $connectTimeout = max(10, (int) ($this->config['connect_timeout'] ?? 20));
        $perUrlTimeout = max(60, (int) ($this->config['timeout'] ?? 90));
        $verifySsl = (bool) ($this->config['verify_ssl'] ?? false);

        $cmd = [
            'curl.exe',
            '-s',
            '-S',
            '--http1.1',
            '--connect-timeout',
            (string) $connectTimeout,
            '--max-time',
            (string) $perUrlTimeout,
            '-H',
            'Accept: application/json',
            '-H',
            'X-App-Key: '.trim($this->config['app_key'] ?? ''),
            '-H',
            'X-Secret-Key: '.trim($this->config['secret_key'] ?? ''),
            '-w',
            "\n__SIADIKNONA_BATCH_END__:%{http_code}\n",
            '-K',
            '-',
        ];

        if (! $verifySsl) {
            array_splice($cmd, 4, 0, ['-k']);
        }

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = @proc_open($cmd, $descriptors, $pipes, null, null, ['bypass_shell' => true]);
        if (! is_resource($process)) {
            $results = [];
            foreach ($keys as $k) {
                $results[$k] = ['ok' => false, 'status' => 0, 'data' => [], 'error' => 'Gagal menjalankan curl.exe'];
            }

            return $results;
        }

        fwrite($pipes[0], implode("\n", $configLines)."\n");
        fclose($pipes[0]);

        $stdout = (string) stream_get_contents($pipes[1]);
        $stderr = trim((string) stream_get_contents($pipes[2]));
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        $parts = preg_split('/\r?\n__SIADIKNONA_BATCH_END__:(\d+)\r?\n/', $stdout, -1, PREG_SPLIT_DELIM_CAPTURE);
        $results = [];

        foreach ($keys as $idx => $key) {
            $pos = $idx * 2;
            if ($parts !== false && isset($parts[$pos], $parts[$pos + 1])) {
                $body = $parts[$pos];
                $code = (int) $parts[$pos + 1];
                if ($code >= 200 && $code < 300) {
                    $decoded = json_decode($body, true);
                    $results[$key] = [
                        'ok' => is_array($decoded),
                        'status' => $code,
                        'data' => is_array($decoded) ? $decoded : [],
                        'error' => is_array($decoded) ? null : 'Respons JSON tidak valid',
                    ];
                } else {
                    $results[$key] = [
                        'ok' => false,
                        'status' => $code,
                        'data' => [],
                        'error' => $code === 0 ? ($stderr ?: 'Koneksi ke API SIAKAD timeout') : "HTTP {$code}",
                    ];
                }
            } else {
                $results[$key] = [
                    'ok' => false,
                    'status' => 0,
                    'data' => [],
                    'error' => $stderr ?: 'Tidak ada respons dari API SIAKAD',
                ];
            }
        }

        return $results;
    }

    /**
     * Kirim request GET ke API.
     *
     * @throws RuntimeException
     */
    public function get(string $path, array $query = []): array
    {
        $this->ensureConfigured();

        try {
            /** @var Response $response */
            $response = $this->client()->get($path, $query);
            $response->throw();

            return $response->json() ?? [];
        } catch (RequestException $e) {
            $status = $e->response->status();

            Log::warning('SIADIKNONA API request failed', [
                'path' => $path,
                'status' => $status,
                'body' => mb_substr((string) $e->response->body(), 0, 500),
            ]);

            $message = match ($status) {
                401, 403 => 'Akses API SIADIKNONA ditolak. Periksa AppKey/SecretKey atau izin endpoint.',
                404 => "Endpoint/data SIADIKNONA tidak ditemukan: {$path}",
                429 => 'Batas request API SIADIKNONA tercapai. Coba lagi beberapa saat.',
                default => "Request API SIADIKNONA gagal (HTTP {$status}).",
            };

            throw new RuntimeException($message, $status, $e);
        } catch (ConnectionException $e) {
            Log::warning('SIADIKNONA API connection failed', ['path' => $path, 'error' => $e->getMessage()]);

            throw new RuntimeException('Tidak dapat terhubung ke API SIADIKNONA: '.$e->getMessage(), 0, $e);
        }
    }

    private function client(): PendingRequest
    {
        $verifySsl = (bool) ($this->config['verify_ssl'] ?? false);
        $options = [
            'verify' => $verifySsl,
            'version' => 1.1,
        ];

        if (extension_loaded('curl')) {
            $options['curl'] = [
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            ];
        }

        $request = Http::baseUrl(rtrim($this->config['base_url'], '/'))
            ->acceptJson()
            ->withHeaders([
                'X-App-Key' => trim($this->config['app_key'] ?? ''),
                'X-Secret-Key' => trim($this->config['secret_key'] ?? ''),
            ])
            ->connectTimeout((int) ($this->config['connect_timeout'] ?? 10))
            ->timeout((int) ($this->config['timeout'] ?? 35))
            ->retry(
                $this->config['retry_times'] ?? 2,
                fn (int $attempt) => $attempt * (int) ($this->config['retry_sleep'] ?? 800),
                fn ($e) => $e instanceof ConnectionException
                    || ($e instanceof RequestException && $e->response->serverError()),
                throw: false,
            )
            ->withOptions($options);

        // Gunakan Windows curl.exe (Schannel + HTTP/1.1) di lingkungan Windows untuk menghindari
        // bug freeze ext-curl/OpenSSL pada respons chunked besar (>16 KB).
        if (PHP_OS_FAMILY === 'Windows' && function_exists('proc_open')) {
            $request->setHandler($this->createWindowsCurlHandler());
        }

        return $request;
    }

    /**
     * Handler Guzzle berbasis curl.exe Windows (Schannel + HTTP/1.1).
     * Kompatibel penuh dengan Http::fake() karena stub handler Laravel berada di atas base handler.
     */
    private function createWindowsCurlHandler(): callable
    {
        return function (RequestInterface $request, array $options): PromiseInterface {
            $connectTimeout = max(5, (int) ($options['connect_timeout'] ?? $this->config['connect_timeout'] ?? 20));
            $timeout = max($connectTimeout, (int) ($options['timeout'] ?? $this->config['timeout'] ?? 90));
            $verifySsl = (bool) ($options['verify'] ?? $this->config['verify_ssl'] ?? false);

            $cmd = [
                'curl.exe',
                '-s',
                '-S',
                '--http1.1',
                '--connect-timeout',
                (string) $connectTimeout,
                '--max-time',
                (string) $timeout,
                '-X',
                strtoupper($request->getMethod()),
            ];

            if (! $verifySsl) {
                $cmd[] = '-k';
            }

            foreach ($request->getHeaders() as $name => $values) {
                if (in_array(strtolower($name), ['host', 'user-agent', 'content-length'], true)) {
                    continue;
                }

                foreach ($values as $value) {
                    $cmd[] = '-H';
                    $cmd[] = "{$name}: {$value}";
                }
            }

            $cmd[] = '-w';
            $cmd[] = "\n__SIADIKNONA_HTTP_CODE__:%{http_code}";
            $cmd[] = (string) $request->getUri();

            $descriptors = [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ];

            $process = @proc_open($cmd, $descriptors, $pipes, null, null, ['bypass_shell' => true]);
            if (! is_resource($process)) {
                return Create::rejectionFor(new ConnectException('Gagal menjalankan proses curl.exe pada Windows.', $request));
            }

            fclose($pipes[0]);
            $stdout = (string) stream_get_contents($pipes[1]);
            $stderr = trim((string) stream_get_contents($pipes[2]));
            fclose($pipes[1]);
            fclose($pipes[2]);
            $exitCode = proc_close($process);

            $httpCode = 0;
            if (preg_match('/\r?\n__SIADIKNONA_HTTP_CODE__:(\d+)\s*$/', $stdout, $matches)) {
                $httpCode = (int) $matches[1];
                $stdout = substr($stdout, 0, -strlen($matches[0]));
            }

            if ($exitCode !== 0 || $httpCode === 0) {
                $errMsg = $stderr !== '' ? $stderr : "curl.exe exited with code {$exitCode}";

                return Create::rejectionFor(
                    new ConnectException("cURL error {$exitCode}: {$errMsg} for {$request->getUri()}", $request)
                );
            }

            return Create::promiseFor(new Psr7Response(
                $httpCode,
                ['Content-Type' => 'application/json; charset=utf-8'],
                $stdout
            ));
        };
    }

    /**
     * Ubah ['nidn' => 'x'] menjadi ['f-nidn' => 'x']; key yang sudah berawalan "f-" dibiarkan.
     */
    private function normalizeFilters(array $filters): array
    {
        $normalized = [];

        foreach ($filters as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $key = str_starts_with($key, 'f-') ? $key : 'f-'.$key;
            $normalized[$key] = is_array($value) ? implode(',', $value) : $value;
        }

        return $normalized;
    }

    private function ensureConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Kredensial API SIADIKNONA belum diatur. Isi SIADIKNONA_APP_KEY dan SIADIKNONA_SECRET_KEY di file .env.');
        }
    }
}
