<?php

use App\Models\Dosen;
use App\Models\JadwalMonev;
use App\Models\Kelas;
use App\Models\MataKuliah;
use App\Models\Perkuliahan;
use App\Models\Role;
use App\Models\Semester;
use App\Models\TahunAkademik;
use App\Models\Termin;
use App\Models\User;
use App\Services\SiadiknonaApiService;
use App\Services\SiakadSyncService;
use Illuminate\Support\Facades\Http;

function syncUser(string $roleSlug): User
{
    $role = Role::firstOrCreate(['slug' => $roleSlug], ['name' => ucfirst($roleSlug)]);

    return User::factory()->create(['role_id' => $role->id]);
}

it('syncs dosen successfully using SiakadSyncService', function () {
    Http::fake([
        '*/siakadcloud/v1/dosen*' => Http::response([
            'meta' => ['total' => 1, 'currentPage' => 1, 'lastPage' => 1],
            'data' => [
                [
                    'attributes' => [
                        'nama' => 'Tester Dosen',
                        'nip' => '199001012020011001',
                        'nidn' => '0001019001',
                        'gelar_depan' => 'Dr.',
                        'gelar_belakang' => 'M.Kom.',
                    ],
                ],
            ],
        ], 200),
    ]);

    $service = app(SiakadSyncService::class);
    $stats = $service->syncDosen();

    expect($stats['total_api'])->toBe(1)
        ->and($stats['created'] + $stats['updated'])->toBeGreaterThan(0);

    $dosen = Dosen::where('nidn', '0001019001')->first();
    expect($dosen)->not->toBeNull()
        ->and($dosen->nama_dosen)->toBe('Dr. Tester Dosen, M.Kom.');
});

it('matches existing dosen by smart first and last name without creating duplicate or overwriting existing custom name', function () {
    // 1. Dosen sudah ada di database dengan nama "Yelly N Nubuasa" tanpa NIP/NIDN
    $existing = Dosen::create([
        'nama_dosen' => 'Yelly N Nubuasa',
        'nip' => null,
        'nidn' => null,
    ]);

    // 2. Data datang dari API dengan variasi nama "Yely Nabuasa" dan membawa NIDN baru
    Http::fake([
        '*/siakadcloud/v1/dosen*' => Http::response([
            'meta' => ['total' => 1, 'currentPage' => 1, 'lastPage' => 1],
            'data' => [
                [
                    'attributes' => [
                        'nama' => 'Yely Nabuasa',
                        'nip' => '0024038307',
                        'nidn' => '0024038307',
                        'gelar_depan' => '',
                        'gelar_belakang' => '',
                    ],
                ],
            ],
        ], 200),
    ]);

    $service = app(SiakadSyncService::class);
    $stats = $service->syncDosen();

    // 3. Harus di-update, bukan dibuat baru
    expect($stats['updated'])->toBe(1)
        ->and($stats['created'])->toBe(0)
        ->and(Dosen::count())->toBe(1);

    // 4. Nama di database TIDAK tertimpa, tetapi NIP & NIDN diperbarui
    $reloaded = $existing->fresh();
    expect($reloaded->nama_dosen)->toBe('Yelly N Nubuasa')
        ->and($reloaded->nidn)->toBe('0024038307');
});

it('updates existing mata kuliah when code or name matches without creating duplicates', function () {
    // 1. Sudah ada mata kuliah di database
    $existing = MataKuliah::create([
        'kode_mk' => 'STKOM41310',
        'nama_mk' => 'ALGORITMA DAN PEMROGAMAN I',
        'sks' => 2,
        'is_active' => true,
    ]);

    // 2. Data dari API SIAKAD datang dengan kode yang sama dan SKS yang diperbarui
    Http::fake([
        '*/siakadcloud/v1/mata-kuliah*' => Http::response([
            'meta' => ['total' => 1, 'currentPage' => 1, 'lastPage' => 1],
            'data' => [
                [
                    'attributes' => [
                        'kode' => 'STKOM41310',
                        'nama' => 'ALGORITMA DAN PEMROGRAMAN I',
                        'sks' => '3',
                    ],
                ],
            ],
        ], 200),
    ]);

    $service = app(SiakadSyncService::class);
    $stats = $service->syncMataKuliah();

    // 3. Harus di-update, bukan dibuat baru
    expect($stats['updated'])->toBe(1)
        ->and($stats['created'])->toBe(0)
        ->and(MataKuliah::count())->toBe(1);

    $reloaded = $existing->fresh();
    expect($reloaded->sks)->toBe(3)
        ->and($reloaded->nama_mk)->toBe('ALGORITMA DAN PEMROGRAMAN I');
});

it('fetches realization data from sessions for ringkasan perkuliahan', function () {
    $ta = TahunAkademik::firstOrCreate(['nama' => '2026/2027']);
    $sem = Semester::firstOrCreate(
        ['tahun_akademik_id' => $ta->id, 'nama' => 'ganjil'],
        ['is_active' => true, 'tanggal_mulai' => now(), 'tanggal_selesai' => now()->addMonths(6)]
    );
    $mk = MataKuliah::firstOrCreate(['kode_mk' => 'TEST101'], ['nama_mk' => 'Algoritma Pemrograman', 'sks' => 3]);
    $kls = Kelas::firstOrCreate(['nama_kelas' => 'A']);
    $perkuliahan = Perkuliahan::firstOrCreate([
        'semester_id' => $sem->id,
        'mata_kuliah_id' => $mk->id,
        'kelas_id' => $kls->id,
    ]);

    Http::fake([
        '*/siakadcloud/v1/perkuliahan*' => Http::response([
            'meta' => ['total' => 2, 'currentPage' => 1, 'lastPage' => 1],
            'data' => [
                [
                    'attributes' => [
                        'nomor_pertemuan' => '1',
                        'tanggal' => '2026-09-01',
                        'status_perkuliahan' => 'Selesai',
                        'nama_pengisi_materi' => 'Dosen Penguji',
                        'rencana_materi' => 'Kontrak Perkuliahan & Pengantar',
                        'bahasan' => 'Kontrak Perkuliahan & Pengantar telah terlaksana',
                        'metode_pembelajaran' => 'Offline',
                    ],
                ],
                [
                    'attributes' => [
                        'nomor_pertemuan' => '2',
                        'tanggal' => '2026-09-08',
                        'status_perkuliahan' => 'Selesai',
                        'nama_pengisi_materi' => 'Dosen Penguji',
                        'rencana_materi' => 'Variabel dan Tipe Data',
                        'bahasan' => 'Variabel dan Tipe Data telah terlaksana',
                        'metode_pembelajaran' => 'Offline',
                    ],
                ],
            ],
        ], 200),
        '*/siakadcloud/v1/rps*' => Http::response(['data' => []], 200),
    ]);

    $service = app(SiakadSyncService::class);
    $realisasi = $service->fetchRealizationData($perkuliahan);

    expect($realisasi['found'])->toBeTrue()
        ->and($realisasi['jumlah_pertemuan'])->toBe(2)
        ->and($realisasi['kesesuaian_materi'])->toBe('sesuai')
        ->and($realisasi['kontrak_kuliah'])->toBe('ada')
        ->and(count($realisasi['detail_pertemuan']))->toBe(2);
});

it('allows ketua-gkm to trigger sync-siakad endpoints', function () {
    Http::fake([
        '*/siakadcloud/v1/dosen*' => Http::response([
            'meta' => ['total' => 0],
            'data' => [],
        ], 200),
    ]);

    $user = syncUser('ketua-gkm');

    $this->actingAs($user)
        ->postJson(route('sync-siakad.dosen'))
        ->assertStatus(200)
        ->assertJsonStructure(['success', 'message', 'stats']);
});

it('allows ajax fetch of realization data via web route', function () {
    $ta = TahunAkademik::firstOrCreate(['nama' => '2026/2027']);
    $sem = Semester::firstOrCreate(
        ['tahun_akademik_id' => $ta->id, 'nama' => 'ganjil'],
        ['is_active' => true, 'tanggal_mulai' => now(), 'tanggal_selesai' => now()->addMonths(6)]
    );
    $mk = MataKuliah::firstOrCreate(['kode_mk' => 'TEST102'], ['nama_mk' => 'Struktur Data', 'sks' => 3]);
    $kls = Kelas::firstOrCreate(['nama_kelas' => 'B']);
    $perkuliahan = Perkuliahan::firstOrCreate([
        'semester_id' => $sem->id,
        'mata_kuliah_id' => $mk->id,
        'kelas_id' => $kls->id,
    ]);

    Http::fake([
        '*/siakadcloud/v1/perkuliahan*' => Http::response([
            'meta' => ['total' => 1],
            'data' => [
                [
                    'attributes' => [
                        'nomor_pertemuan' => '1',
                        'tanggal' => '2026-09-02',
                        'status_perkuliahan' => 'Selesai',
                        'nama_pengisi_materi' => 'Dosen Penguji',
                        'rencana_materi' => 'Pengenalan Array',
                        'bahasan' => 'Pengenalan Array telah selesai',
                        'metode_pembelajaran' => 'Offline',
                    ],
                ],
            ],
        ], 200),
        '*/siakadcloud/v1/rps*' => Http::response(['data' => []], 200),
    ]);

    $user = syncUser('anggota-gkm');

    $this->actingAs($user)
        ->getJson(route('ringkasan-perkuliahan.fetch-siakad', $perkuliahan))
        ->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.jumlah_pertemuan', 1)
        ->assertJsonPath('data.kesesuaian_materi', 'sesuai');
});

it('syncs active periode only by default', function () {
    Http::fake([
        '*/siakadcloud/v1/periode*' => Http::response([
            'meta' => ['total' => 1, 'currentPage' => 1, 'lastPage' => 1],
            'data' => [
                [
                    'id' => '20291',
                    'attributes' => [
                        'nama_periode' => '2029 Ganjil',
                        'tahun_ajar' => '2029/2030',
                        'is_aktif' => '1',
                        'tanggal_awal' => '2029-09-01',
                        'tanggal_akhir' => '2029-12-15',
                        'tanggal_akhir_uas' => '2029-12-30',
                    ],
                ],
            ],
        ], 200),
    ]);

    $service = app(SiakadSyncService::class);
    $stats = $service->syncPeriode(onlyActive: true);

    expect($stats['total_api'])->toBe(1)
        ->and($stats['tahun_created'] + $stats['tahun_updated'])->toBe(1)
        ->and($stats['semester_created'] + $stats['semester_updated'])->toBe(1)
        ->and($stats['active_period'])->toBe('2029/2030 Ganjil');

    $ta = TahunAkademik::where('nama', '2029/2030')->first();
    expect($ta)->not->toBeNull()
        ->and($ta->is_active)->toBeTrue();

    $sem = Semester::where('tahun_akademik_id', $ta->id)->where('nama', 'ganjil')->first();
    expect($sem)->not->toBeNull()
        ->and($sem->is_active)->toBeTrue()
        ->and($sem->tanggal_mulai?->format('Y-m-d'))->toBe('2029-09-01');
});

it('allows ketua-gkm to trigger sync-siakad.periode route', function () {
    Http::fake([
        '*/siakadcloud/v1/periode*' => Http::response([
            'meta' => ['total' => 0],
            'data' => [],
        ], 200),
    ]);

    $user = syncUser('ketua-gkm');

    $this->actingAs($user)
        ->postJson(route('sync-siakad.periode'))
        ->assertStatus(200)
        ->assertJsonStructure(['success', 'message', 'stats']);
});

it('syncs kelas successfully using SiakadSyncService', function () {
    Http::fake([
        '*/siakadcloud/v1/kelas*' => Http::response([
            'meta' => ['total' => 2, 'currentPage' => 1, 'lastPage' => 1],
            'data' => [
                [
                    'attributes' => [
                        'nama_kelas' => 'D',
                    ],
                ],
                [
                    'attributes' => [
                        'nama_kelas' => 'E',
                    ],
                ],
            ],
        ], 200),
    ]);

    $service = app(SiakadSyncService::class);
    $stats = $service->syncKelas();

    expect($stats['total_api'])->toBe(2)
        ->and($stats['created'])->toBe(2);

    expect(Kelas::where('nama_kelas', 'D')->exists())->toBeTrue()
        ->and(Kelas::where('nama_kelas', 'E')->exists())->toBeTrue();
});

it('allows ketua-gkm to trigger sync-siakad.kelas route', function () {
    Http::fake([
        '*/siakadcloud/v1/kelas*' => Http::response([
            'meta' => ['total' => 0],
            'data' => [],
        ], 200),
    ]);

    $user = syncUser('ketua-gkm');

    $this->actingAs($user)
        ->postJson(route('sync-siakad.kelas'))
        ->assertStatus(200)
        ->assertJsonStructure(['success', 'message', 'stats']);
});

it('displays informative error message to user when siakad fails', function () {
    Http::fake([
        '*/siakadcloud/v1/kelas*' => Http::response([], 500),
    ]);

    $user = syncUser('ketua-gkm');

    $response = $this->actingAs($user)
        ->from(route('kelas.index'))
        ->post(route('sync-siakad.kelas'));

    $response->assertRedirect(route('kelas.index'));
    $response->assertSessionHas('error');
    $errorMessage = session('error');
    expect($errorMessage)->toContain('Gagal sinkronisasi Kelas');
});


