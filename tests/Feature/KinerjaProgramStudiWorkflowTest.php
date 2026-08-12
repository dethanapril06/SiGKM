<?php

use App\Models\EvaluasiIndikator;
use App\Models\IndikatorKinerjaKegiatan;
use App\Models\IndikatorKinerjaKegiatanSatuan;
use App\Models\IndikatorKinerjaUtama;
use App\Models\SasaranStrategis;
use App\Models\Semester;
use App\Models\TahunAkademik;

it('connects an IKKS evaluation to the shared evaluation workflow', function () {
    $year = TahunAkademik::create([
        'nama' => '2026/2027',
        'tanggal_mulai' => '2026-08-01',
        'tanggal_selesai' => '2027-07-31',
    ]);
    $semester = Semester::create([
        'tahun_akademik_id' => $year->id,
        'nama' => 'ganjil',
        'tanggal_mulai' => '2026-08-01',
        'tanggal_selesai' => '2027-01-31',
    ]);
    $sasaran = SasaranStrategis::create(['kode_sasaran' => 'SS-01', 'uraian_sasaran' => 'Sasaran']);
    $iku = IndikatorKinerjaUtama::create([
        'sasaran_strategis_id' => $sasaran->id,
        'kode_iku' => 'IKU-01',
        'uraian_iku' => 'Indikator utama',
    ]);
    $ikk = IndikatorKinerjaKegiatan::create([
        'indikator_kinerja_utama_id' => $iku->id,
        'kode_ikk' => 'IKK-01',
        'uraian_ikk' => 'Indikator kegiatan',
    ]);
    $ikks = IndikatorKinerjaKegiatanSatuan::create([
        'indikator_kinerja_kegiatan_id' => $ikk->id,
        'kode_ikks' => 'IKKS-01',
        'uraian_ikks' => 'Indikator satuan',
    ]);

    $evaluation = EvaluasiIndikator::create([
        'semester_id' => $semester->id,
        'evaluatable_type' => 'ikks',
        'evaluatable_id' => $ikks->id,
        'status_capaian' => 'belum_tercapai',
    ]);

    expect($evaluation->evaluatable)->toBeInstanceOf(IndikatorKinerjaKegiatanSatuan::class)
        ->and($evaluation->sumber_kode)->toBe('IKKS-01')
        ->and($evaluation->sumber_jenis)->toBe('Program Studi')
        ->and($ikk->indikatorKinerjaKegiatanSatuan->is($ikks))->toBeTrue();
});

it('creates and updates a prodi temuan directly with IKKS without pre-existing evaluasi indikator', function () {
    $role = \App\Models\Role::firstOrCreate(['slug' => 'ketua-gkm'], ['name' => 'Ketua GKM']);
    $user = \App\Models\User::factory()->create(['role_id' => $role->id]);

    $year = TahunAkademik::create([
        'nama' => '2026/2027',
        'tanggal_mulai' => '2026-08-01',
        'tanggal_selesai' => '2027-07-31',
    ]);
    $semester = Semester::create([
        'tahun_akademik_id' => $year->id,
        'nama' => 'ganjil',
        'tanggal_mulai' => '2026-08-01',
        'tanggal_selesai' => '2027-01-31',
    ]);
    $sasaran = SasaranStrategis::create(['kode_sasaran' => 'SS-02', 'uraian_sasaran' => 'Sasaran 2']);
    $iku = IndikatorKinerjaUtama::create([
        'sasaran_strategis_id' => $sasaran->id,
        'kode_iku' => 'IKU-02',
        'uraian_iku' => 'Indikator utama 2',
    ]);
    $ikk = IndikatorKinerjaKegiatan::create([
        'indikator_kinerja_utama_id' => $iku->id,
        'kode_ikk' => 'IKK-02',
        'uraian_ikk' => 'Indikator kegiatan 2',
    ]);
    $ikks = IndikatorKinerjaKegiatanSatuan::create([
        'indikator_kinerja_kegiatan_id' => $ikk->id,
        'kode_ikks' => 'IKKS-02',
        'uraian_ikks' => 'Indikator satuan 2',
    ]);

    $response = $this->actingAs($user)->post(route('temuan-evaluasi.store'), [
        'scope_type' => 'prodi',
        'prodi_semester_id' => $semester->id,
        'prodi_ikks_id' => $ikks->id,
        'kode_temuan' => 'TM-PRODI-001',
        'pernyataan' => 'Temuan langsung dari IKKS',
        'rencana_awal' => 'Rencana awal',
        'target_selesai' => '2026-12-31',
    ]);

    $response->assertRedirect(route('temuan-evaluasi.prodi'));

    $temuan = \App\Models\Temuan::where('kode_temuan', 'TM-PRODI-001')->first();
    expect($temuan)->not->toBeNull()
        ->and($temuan->evaluasiIndikator)->not->toBeNull()
        ->and($temuan->evaluasiIndikator->evaluatable_type)->toBe('ikks')
        ->and($temuan->evaluasiIndikator->evaluatable_id)->toBe($ikks->id)
        ->and($temuan->target_capaian)->toBeNull();
});

