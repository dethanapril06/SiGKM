<?php

use App\Models\Dosen;
use App\Models\JadwalMonev;
use App\Models\Perkuliahan;
use App\Models\RingkasanPerkuliahan;
use App\Models\Semester;
use App\Models\TahunAkademik;
use App\Models\Termin;
use App\Models\User;
use App\Services\SiakadSyncService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'services.siadiknona.base_url' => 'https://api.undana.ac.id/siadiknona',
        'services.siadiknona.app_key' => 'test-app-key',
        'services.siadiknona.secret_key' => 'test-secret-key',
        'services.siadiknona.prodi_id' => '55201',
    ]);
});

it('syncs kelas into perkuliahan with siakad_kelas_id and links dosen pengajar cleanly', function () {
    $tahun = TahunAkademik::create([
        'nama' => '2025/2026',
        'is_active' => true,
    ]);

    $semester = Semester::create([
        'tahun_akademik_id' => $tahun->id,
        'nama' => 'genap',
        'is_active' => true,
    ]);

    $existingDosen = Dosen::create([
        'nama_dosen' => 'Dr. APLONIA BANI, S.Sos., M.Si.',
        'nip' => '198004152023212025',
        'nidn' => null,
    ]);

    Http::fake([
        '*/siakadcloud/v1/kelas*' => Http::response([
            'meta' => ['total' => 3, 'currentPage' => 1, 'perPage' => 100, 'lastPage' => 1],
            'data' => [
                [
                    'id' => '474655',
                    'attributes' => [
                        'id_periode' => '20252',
                        'kode_mata_kuliah' => 'MIPAB21230',
                        'mata_kuliah' => 'Kelembagaan dan Pemberdayaan Agribisnis',
                        'sks_mata_kuliah' => '3',
                        'nama_kelas' => 'IP 01',
                        'is_deleted' => '0',
                        'relation' => [
                            'dosen-pengajar' => [
                                [
                                    'nama' => 'APLONIA BANI',
                                    'nip' => '198004152023212025',
                                    'nidn' => '',
                                    'gelar_belakang' => 'S.Sos., M.Si.',
                                ],
                                [
                                    'nama' => 'MUSTAFA ABDURRAHMAN',
                                    'nip' => '',
                                    'nidn' => '0025126120',
                                    'gelar_belakang' => 'M.P.',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'id' => '479254',
                    'attributes' => [
                        'id_periode' => '20252',
                        'kode_mata_kuliah' => 'MKP47401',
                        'mata_kuliah' => 'KULIAH KERJA NYATA',
                        'sks_mata_kuliah' => '4',
                        'nama_kelas' => 'A',
                        'is_deleted' => '0',
                        'relation' => ['dosen-pengajar' => []],
                    ],
                ],
                [
                    'id' => '479628',
                    'attributes' => [
                        'id_periode' => '20252',
                        'kode_mata_kuliah' => 'STKOM45311',
                        'mata_kuliah' => 'KRIPTOGRAFI',
                        'sks_mata_kuliah' => '3',
                        'nama_kelas' => 'A',
                        'is_deleted' => '0',
                        'relation' => ['dosen-pengajar' => []],
                    ],
                ],
            ],
        ], 200),
    ]);

    $service = app(SiakadSyncService::class);
    $stats = $service->syncPerkuliahan($semester);

    expect($stats['created'])->toBe(1)
        ->and($stats['skipped'])->toBe(2)
        ->and($stats['errors'])->toBeEmpty()
        ->and(Perkuliahan::count())->toBe(1);

    $perkuliahan = Perkuliahan::with(['dosens', 'pengajars.dosen', 'mataKuliah', 'kelas'])->first();

    expect($perkuliahan)->not->toBeNull()
        ->and($perkuliahan->siakad_kelas_id)->toBe('474655')
        ->and($perkuliahan->mataKuliah->kode_mk)->toBe('MIPAB21230')
        ->and($perkuliahan->kelas->nama_kelas)->toBe('IP 01')
        ->and($perkuliahan->dosens)->toHaveCount(2);

    // Gelar lengkap dosen yang sudah ada tidak tertimpa nama tanpa gelar depan
    expect($existingDosen->fresh()->nama_dosen)->toBe('Dr. APLONIA BANI, S.Sos., M.Si.')
        ->and($existingDosen->perkuliahans)->toHaveCount(1);
});

it('fetches 16 meetings using f-id_kelas and syncs ringkasan_perkuliahans without SQL errors', function () {
    $user = User::factory()->create();

    $tahun = TahunAkademik::create([
        'nama' => '2025/2026',
        'is_active' => true,
    ]);

    $semester = Semester::create([
        'tahun_akademik_id' => $tahun->id,
        'nama' => 'genap',
        'is_active' => true,
    ]);

    $termin = Termin::create([
        'nama_termin' => 'Akhir Semester',
        'keterangan' => 'Monev Akhir',
    ]);

    $jadwalMonev = JadwalMonev::create([
        'semester_id' => $semester->id,
        'termin_id' => $termin->id,
        'tanggal_mulai' => '2026-06-01',
        'tanggal_selesai' => '2026-07-01',
        'created_by' => $user->id,
        'status' => 'aktif',
    ]);

    $mk = \App\Models\MataKuliah::create([
        'kode_mk' => 'MIPAB21230',
        'nama_mk' => 'Kelembagaan dan Pemberdayaan Agribisnis',
        'sks' => 3,
        'is_active' => true,
    ]);

    $kelas = \App\Models\Kelas::create([
        'nama_kelas' => 'IP 01',
    ]);

    $perkuliahan = Perkuliahan::create([
        'semester_id' => $semester->id,
        'mata_kuliah_id' => $mk->id,
        'kelas_id' => $kelas->id,
        'siakad_kelas_id' => '474655',
        'status' => 'aktif',
    ]);

    Http::fake(function (\Illuminate\Http\Client\Request $request) {
        expect($request->url())->toContain('f-id_kelas=474655')
            ->and($request->url())->toContain('f-id_periode=20252');

        return Http::response([
            'meta' => ['total' => 5, 'currentPage' => 1, 'perPage' => 100, 'lastPage' => 1],
            'data' => [
                [
                    'id' => '832654',
                    'attributes' => [
                        'nomor_pertemuan' => '1',
                        'rencana_materi' => 'Kontrak kuliah<br>Pentingnya Kelembagaan dalam Agribisnis',
                        'bahasan' => '<p>Kontrak kuliah</p><p>Pentingnya Kelembagaan dalam Agribisnis</p>',
                        'id_jenis_perkuliahan' => 'K',
                        'nip_pengisi_materi' => '198004152023212025',
                        'nama_pengisi_materi' => 'APLONIA BANI',
                        'metode_pembelajaran' => 'Offline',
                        'status_perkuliahan' => 'Selesai',
                        'is_deleted' => '0',
                    ],
                ],
                [
                    'id' => '832659',
                    'attributes' => [
                        'nomor_pertemuan' => '6',
                        'rencana_materi' => '<div>Konsep dan Pola Kemitraan dalam Agribisnis</div><div><br></div>',
                        'bahasan' => "Konsep dan Pola Kemitraan dalam\u{00A0}Agribisnis",
                        'id_jenis_perkuliahan' => 'K',
                        'nip_pengisi_materi' => '198004152023212025',
                        'nama_pengisi_materi' => 'APLONIA BANI',
                        'metode_pembelajaran' => 'Offline',
                        'status_perkuliahan' => 'Selesai',
                        'is_deleted' => '0',
                    ],
                ],
                [
                    'id' => '832664',
                    'attributes' => [
                        'nomor_pertemuan' => '11',
                        'rencana_materi' => 'PERSPEKTIF DAN PRINSIP PEMBERDAYAAN',
                        'bahasan' => '<p>Mahasiswa mampu mengkaji dan memahami perspektif dan prinsip pendekatan pemberdayaan.</p>',
                        'id_jenis_perkuliahan' => 'K',
                        'nip_pengisi_materi' => '0025126120',
                        'nama_pengisi_materi' => 'MUSTAFA ABDURRAHMAN',
                        'metode_pembelajaran' => 'Offline',
                        'status_perkuliahan' => 'Selesai',
                        'is_deleted' => '0',
                    ],
                ],
                [
                    'id' => '832669',
                    'attributes' => [
                        'nomor_pertemuan' => '16',
                        'rencana_materi' => 'UJIAN AKHIR SEMESTER',
                        'bahasan' => '<p>Mengevaluasi materi perkuliahan dari pertemuan ke-9 hingga pertemuan ke-15</p>',
                        'id_jenis_perkuliahan' => 'A',
                        'nip_pengisi_materi' => '0025126120',
                        'nama_pengisi_materi' => 'MUSTAFA ABDURRAHMAN',
                        'metode_pembelajaran' => 'Offline',
                        'status_perkuliahan' => 'Selesai',
                        'is_deleted' => '0',
                    ],
                ],
                [
                    'id' => '999999',
                    'attributes' => [
                        'nomor_pertemuan' => '17',
                        'rencana_materi' => 'Jadwal Batal',
                        'bahasan' => '',
                        'id_jenis_perkuliahan' => 'K',
                        'status_perkuliahan' => 'Selesai',
                        'is_deleted' => '1',
                    ],
                ],
            ],
        ], 200);
    });

    $service = app(SiakadSyncService::class);
    $syncStats = $service->syncRingkasanPerkuliahanForJadwalMonev($jadwalMonev, $user->id);

    expect($syncStats['created'])->toBe(1)
        ->and($syncStats['errors'])->toBeEmpty();

    $ringkasan = RingkasanPerkuliahan::first();
    expect($ringkasan)->not->toBeNull()
        ->and($ringkasan->jumlah_pertemuan)->toBe(4)
        ->and($ringkasan->kontrak_kuliah)->toBe('ada')
        ->and($ringkasan->kesesuaian_materi)->toBe('sesuai')
        ->and($ringkasan->metode_pembelajaran)->toBe('Offline');

    // Backfill dosen pengajar dari sesi pertemuan (termasuk 10 digit NIDN pada nip_pengisi_materi)
    expect($perkuliahan->fresh()->dosens)->toHaveCount(2);
    expect(Dosen::where('nidn', '0025126120')->exists())->toBeTrue();
});

it('supports progressive per-class AJAX sync (mode=init and perkuliahan_id) without redundant RPS calls on timeout', function () {
    $role = \App\Models\Role::firstOrCreate(
        ['slug' => 'ketua-gkm'],
        ['name' => 'Ketua GKM']
    );
    $user = User::factory()->create(['role_id' => $role->id, 'is_active' => true]);

    $tahun = TahunAkademik::create(['nama' => '2026/2027', 'is_active' => true]);
    $semester = Semester::create(['tahun_akademik_id' => $tahun->id, 'nama' => 'ganjil', 'is_active' => true]);
    $termin = Termin::create(['nama_termin' => 'Tengah Semester']);
    $jadwalMonev = JadwalMonev::create([
        'semester_id' => $semester->id,
        'termin_id' => $termin->id,
        'tanggal_mulai' => '2026-10-01',
        'tanggal_selesai' => '2026-11-01',
        'created_by' => $user->id,
        'status' => 'aktif',
    ]);

    $mk1 = \App\Models\MataKuliah::create(['kode_mk' => 'STKOM41305', 'nama_mk' => 'ALJABAR LINEAR', 'sks' => 3, 'is_active' => true]);
    $mk2 = \App\Models\MataKuliah::create(['kode_mk' => 'STKOM41309', 'nama_mk' => 'KALKULUS', 'sks' => 3, 'is_active' => true]);
    $kelasB = \App\Models\Kelas::create(['nama_kelas' => 'B']);

    $p1 = Perkuliahan::create([
        'semester_id' => $semester->id,
        'mata_kuliah_id' => $mk1->id,
        'kelas_id' => $kelasB->id,
        'siakad_kelas_id' => '478854',
        'status' => 'aktif',
    ]);
    $p2 = Perkuliahan::create([
        'semester_id' => $semester->id,
        'mata_kuliah_id' => $mk2->id,
        'kelas_id' => $kelasB->id,
        'siakad_kelas_id' => '478676',
        'status' => 'aktif',
    ]);

    // 1. mode=init returns both queued items
    $initRes = $this->actingAs($user)->postJson(route('ringkasan-perkuliahan.sync-siakad'), [
        'jadwal_monev_id' => $jadwalMonev->id,
        'mode' => 'init',
        'only_missing' => true,
    ]);

    $initRes->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('total_active', 2)
        ->assertJsonCount(2, 'items');

    // 2. Sync p1 succeeds & Sync p2 fails cleanly without hitting /rps
    $rpsCalled = false;
    Http::fake(function (\Illuminate\Http\Client\Request $request) use (&$rpsCalled) {
        if (str_contains($request->url(), '/rps')) {
            $rpsCalled = true;
        }
        if (str_contains($request->url(), 'f-id_kelas=478854')) {
            return Http::response([
                'data' => [
                    [
                        'id' => '9001',
                        'attributes' => [
                            'nomor_pertemuan' => '1',
                            'rencana_materi' => 'Kontrak Kuliah dan Matriks',
                            'bahasan' => 'Kontrak Kuliah dan Matriks',
                            'status_perkuliahan' => 'Selesai',
                            'metode_pembelajaran' => 'Offline',
                            'is_deleted' => '0',
                        ],
                    ],
                ],
            ], 200);
        }

        return Http::response(['message' => 'Gateway Timeout'], 504);
    });

    $step1 = $this->actingAs($user)->postJson(route('ringkasan-perkuliahan.sync-siakad'), [
        'jadwal_monev_id' => $jadwalMonev->id,
        'perkuliahan_id' => $p1->id,
    ]);
    $step1->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('outcome', 'created')
        ->assertJsonPath('jumlah_pertemuan', 1);

    $step2 = $this->actingAs($user)->postJson(route('ringkasan-perkuliahan.sync-siakad'), [
        'jadwal_monev_id' => $jadwalMonev->id,
        'perkuliahan_id' => $p2->id,
    ]);
    $step2->assertOk()
        ->assertJsonPath('success', false)
        ->assertJsonPath('outcome', 'failed');

    expect($rpsCalled)->toBeFalse();

    // 3. Re-running mode=init with only_missing=true now returns ONLY p2!
    $initRetry = $this->actingAs($user)->postJson(route('ringkasan-perkuliahan.sync-siakad'), [
        'jadwal_monev_id' => $jadwalMonev->id,
        'mode' => 'init',
        'only_missing' => true,
    ]);
    $initRetry->assertOk()
        ->assertJsonPath('already_synced', 1)
        ->assertJsonCount(1, 'items')
        ->assertJsonPath('items.0.id', $p2->id);
});

it('builds fast realization snapshot from edlink kelas, edlink sesi, and presensi-dosen without hitting slow /perkuliahan', function () {
    config(['services.siadiknona.enable_snapshot_in_tests' => true]);

    $user = User::factory()->create();
    $tahun = TahunAkademik::create(['nama' => '2026/2027', 'is_active' => true]);
    $semester = Semester::create(['tahun_akademik_id' => $tahun->id, 'nama' => 'ganjil', 'is_active' => true]);
    $termin = Termin::create(['nama_termin' => 'Tengah Semester']);
    $jadwalMonev = JadwalMonev::create([
        'semester_id' => $semester->id,
        'termin_id' => $termin->id,
        'tanggal_mulai' => '2026-10-01',
        'tanggal_selesai' => '2026-11-01',
        'created_by' => $user->id,
        'status' => 'aktif',
    ]);

    $mkKuliah = \App\Models\MataKuliah::create(['kode_mk' => 'STKOM41305', 'nama_mk' => 'ALJABAR LINEAR', 'sks' => 3, 'is_active' => true]);
    $mkKkn = \App\Models\MataKuliah::create(['kode_mk' => 'MKP47401', 'nama_mk' => 'KULIAH KERJA NYATA', 'sks' => 4, 'is_active' => true]);
    $kelasB = \App\Models\Kelas::create(['nama_kelas' => 'B']);
    $kelasA = \App\Models\Kelas::create(['nama_kelas' => 'A']);

    $pKuliah = Perkuliahan::create([
        'semester_id' => $semester->id,
        'mata_kuliah_id' => $mkKuliah->id,
        'kelas_id' => $kelasB->id,
        'siakad_kelas_id' => '478854',
        'status' => 'aktif',
    ]);

    $pKkn = Perkuliahan::create([
        'semester_id' => $semester->id,
        'mata_kuliah_id' => $mkKkn->id,
        'kelas_id' => $kelasA->id,
        'siakad_kelas_id' => '479254',
        'status' => 'aktif',
    ]);

    $perkuliahanEndpointHit = false;

    Http::fake(function (\Illuminate\Http\Client\Request $request) use (&$perkuliahanEndpointHit) {
        $url = $request->url();

        if (str_contains($url, '/siakadcloud/v1/perkuliahan')) {
            $perkuliahanEndpointHit = true;

            return Http::response(['message' => 'Should not hit slow /perkuliahan'], 504);
        }

        if (str_contains($url, '/edlink/v1/kelas')) {
            return Http::response([
                'meta' => ['total' => 1, 'currentPage' => 1, 'perPage' => 100, 'lastPage' => 1],
                'data' => [
                    [
                        'id' => '2153954',
                        'attributes' => [
                            'id_kelas' => '2153954',
                            'id_kelas_siakad' => '478854',
                            'nama_kelas' => 'ALJABAR LINEAR',
                            'periode' => '20261',
                        ],
                    ],
                ],
            ], 200);
        }

        if (str_contains($url, '/edlink/v1/sesi')) {
            return Http::response([
                'meta' => ['total' => 3, 'currentPage' => 1, 'perPage' => 100, 'lastPage' => 1],
                'data' => [
                    [
                        'id' => '101',
                        'attributes' => [
                            'id_kelas' => '2153954',
                            'sesi_ke' => '1',
                            'jenis_sesi' => 'Kuliah',
                            'status_sesi' => 'Terjadwal',
                            'topik' => 'Kontrak Kuliah dan Pengantar Vektor',
                            'capaian' => '',
                            'waktu_mulai_sesi' => '2026-09-01 08:00:00',
                        ],
                    ],
                    [
                        'id' => '108',
                        'attributes' => [
                            'id_kelas' => '2153954',
                            'sesi_ke' => '8',
                            'jenis_sesi' => 'UTS',
                            'status_sesi' => 'Selesai',
                            'topik' => 'Ujian Tengah Semester',
                            'capaian' => 'Evaluasi tengah semester terlaksana',
                            'waktu_mulai_sesi' => '2026-10-20 08:00:00',
                        ],
                    ],
                    [
                        'id' => '109',
                        'attributes' => [
                            'id_kelas' => '2153954',
                            'sesi_ke' => '9',
                            'jenis_sesi' => 'Kuliah',
                            'status_sesi' => 'Terjadwal',
                            'topik' => 'Ruang Eigen',
                            'capaian' => '',
                            'waktu_mulai_sesi' => '2026-11-03 08:00:00',
                        ],
                    ],
                ],
            ], 200);
        }

        if (str_contains($url, '/siakadcloud/v1/presensi-dosen')) {
            return Http::response([
                'meta' => ['total' => 2, 'currentPage' => 1, 'perPage' => 100, 'lastPage' => 1],
                'data' => [
                    [
                        'id' => '501',
                        'attributes' => [
                            'id_kelas' => '478854',
                            'id_perkuliahan' => '9901',
                            'urutan_pertemuan_perkuliahan' => '1',
                            'tanggal_perkuliahan' => '2026-09-01',
                            'nama_dosen' => 'DOSEN TIM SATU',
                            'nip' => '198501012010121001',
                            'status_hadir' => 'H',
                            'id_jenis_jadwal' => 'K',
                            'rencana_materi' => 'Kontrak Kuliah dan Pengantar Vektor',
                            'realisasi_materi' => 'Kontrak Kuliah dan Pengantar Vektor',
                            'id_status_perkuliahan' => '1',
                            'status_perkuliahan' => 'Selesai',
                        ],
                    ],
                    [
                        'id' => '502',
                        'attributes' => [
                            'id_kelas' => '478854',
                            'id_perkuliahan' => '9901',
                            'urutan_pertemuan_perkuliahan' => '1',
                            'tanggal_perkuliahan' => '2026-09-01',
                            'nama_dosen' => 'DOSEN TIM DUA',
                            'nip' => '0012038801',
                            'status_hadir' => 'A',
                            'id_jenis_jadwal' => 'K',
                            'rencana_materi' => 'Kontrak Kuliah dan Pengantar Vektor',
                            'realisasi_materi' => '',
                            'id_status_perkuliahan' => '1',
                            'status_perkuliahan' => 'Selesai',
                        ],
                    ],
                ],
            ], 200);
        }

        return Http::response(['data' => []], 200);
    });

    $service = app(SiakadSyncService::class);
    $stats = $service->syncRingkasanPerkuliahanForJadwalMonev($jadwalMonev, $user->id);

    expect($perkuliahanEndpointHit)->toBeFalse()
        ->and($stats['created'])->toBe(1)
        ->and($stats['skipped'])->toBe(1)
        ->and($stats['failed'])->toBe(0);

    $ringkasan = RingkasanPerkuliahan::where('perkuliahan_id', $pKuliah->id)->first();
    expect($ringkasan)->not->toBeNull()
        ->and($ringkasan->jumlah_pertemuan)->toBe(2) // Pertemuan 1 (Presensi) + Pertemuan 8 (UTS Edlink)
        ->and($ringkasan->kontrak_kuliah)->toBe('ada')
        ->and($ringkasan->kesesuaian_materi)->toBe('sesuai')
        ->and($ringkasan->keterangan)->toContain('2 pertemuan selesai dari 3 sesi terjadwal');

    // Kedua dosen tim pengajar berhasil di-backfill tanpa menggandakan jumlah pertemuan
    expect($pKuliah->fresh()->dosens)->toHaveCount(2);
    expect(RingkasanPerkuliahan::where('perkuliahan_id', $pKkn->id)->exists())->toBeFalse();
});



