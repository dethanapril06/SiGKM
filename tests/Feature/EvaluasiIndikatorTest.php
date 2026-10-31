<?php

use App\Models\EvaluasiIndikator;
use App\Models\IndikatorMutu;
use App\Models\Role;
use App\Models\Semester;
use App\Models\StandarMutu;
use App\Models\TahunAkademik;
use App\Models\User;

beforeEach(function () {
    $role = Role::firstOrCreate(['slug' => 'anggota-gkm'], ['name' => 'Anggota GKM']);
    $this->user = User::factory()->create(['role_id' => $role->id, 'is_active' => true]);
});

it('provides standarMutu in evaluasi-indikator create view', function () {
    $tahun = TahunAkademik::create(['nama' => '2026/2027', 'is_active' => true]);
    Semester::create(['tahun_akademik_id' => $tahun->id, 'nama' => 'ganjil', 'is_active' => true]);
    $standar = StandarMutu::create(['kode_standar' => 'STD-001', 'nama_standar' => 'Standar Visi Misi', 'is_active' => true]);
    IndikatorMutu::create([
        'standar_mutu_id' => $standar->id,
        'kode_indikator' => 'IKU-01',
        'isi_indikator' => 'Kesesuaian Visi Misi',
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->user)->get(route('evaluasi-indikator.create'));

    $response->assertOk()
        ->assertViewHas('standarMutu')
        ->assertSee('STD-001 - Standar Visi Misi')
        ->assertSee('data-standar="' . $standar->id . '"', false);
});

it('stores evaluasi indikator cleanly when filter standar_mutu_id is submitted', function () {
    $tahun = TahunAkademik::create(['nama' => '2026/2027', 'is_active' => true]);
    $semester = Semester::create(['tahun_akademik_id' => $tahun->id, 'nama' => 'ganjil', 'is_active' => true]);
    $standar = StandarMutu::create(['kode_standar' => 'STD-002', 'nama_standar' => 'Standar Tata Pamong', 'is_active' => true]);
    $indikator = IndikatorMutu::create([
        'standar_mutu_id' => $standar->id,
        'kode_indikator' => 'IKU-02',
        'isi_indikator' => 'Efektivitas Tata Pamong',
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->user)->post(route('evaluasi-indikator.store'), [
        'semester_id' => $semester->id,
        'standar_mutu_id' => $standar->id,
        'evaluatable_key' => 'indikator_mutu:' . $indikator->id,
        'status_capaian' => 'tercapai',
        'nama_penanggung_jawab' => 'GKM Prodi',
        'catatan' => 'Sesuai standar',
    ]);

    $response->assertRedirect(route('evaluasi-indikator.index'));

    $evaluasi = EvaluasiIndikator::first();
    expect($evaluasi)->not->toBeNull()
        ->and($evaluasi->evaluatable_id)->toBe($indikator->id)
        ->and($evaluasi->status_capaian)->toBe('tercapai');
});

