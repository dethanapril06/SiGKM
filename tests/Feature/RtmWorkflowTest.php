<?php

use App\Models\Dosen;
use App\Models\EvaluasiIndikator;
use App\Models\IndikatorMutu;
use App\Models\JadwalRtm;
use App\Models\NotulenRtm;
use App\Models\RencanaTindakLanjut;
use App\Models\Role;
use App\Models\Semester;
use App\Models\StandarMutu;
use App\Models\TahunAkademik;
use App\Models\Temuan;
use App\Models\User;

function rtmUser(string $role): User
{
    $roleModel = Role::firstOrCreate(['slug' => $role], ['name' => $role]);

    return User::factory()->create(['role_id' => $roleModel->id]);
}

function rtmSemester(string $name, string $start, string $end): Semester
{
    $year = TahunAkademik::firstOrCreate(
        ['nama' => substr($start, 0, 4).'/'.((int) substr($start, 0, 4) + 1)],
        ['tanggal_mulai' => $start, 'tanggal_selesai' => $end]
    );

    return Semester::create([
        'tahun_akademik_id' => $year->id,
        'nama' => $name,
        'tanggal_mulai' => $start,
        'tanggal_selesai' => $end,
    ]);
}

function findingFor(Semester $semester, string $code): Temuan
{
    $dosen = Dosen::create(['nama_dosen' => 'Dosen '.$code]);
    $standard = StandarMutu::create(['nama_standar' => 'Standar '.$code]);
    $indicator = IndikatorMutu::create([
        'standar_mutu_id' => $standard->id,
        'kode_indikator' => $code,
        'isi_indikator' => 'Indikator '.$code,
    ]);
    $evaluation = EvaluasiIndikator::create([
        'semester_id' => $semester->id,
        'evaluatable_type' => 'indikator_mutu',
        'evaluatable_id' => $indicator->id,
        'status_capaian' => 'belum_tercapai',
    ]);
    return Temuan::create([
        'kode_temuan' => $code,
        'evaluasi_indikator_id' => $evaluation->id,
        'dosen_id' => $dosen->id,
        'pernyataan' => 'Temuan '.$code,
        'status' => 'ditutup',
    ]);
}

it('allows an Anggota GKM to submit a notulen and Ketua GKM to verify it', function () {
    $member = rtmUser('anggota-gkm');
    $chair = rtmUser('ketua-gkm');
    $semester = rtmSemester('ganjil', '2026-08-01', '2027-01-31');
    $schedule = JadwalRtm::create([
        'semester_id' => $semester->id,
        'judul' => 'RTM Ganjil',
        'tanggal' => '2027-02-10',
        'status' => 'terjadwal',
    ]);

    $this->actingAs($member)->post(route('notulen-rtm.store'), [
        'jadwal_rtm_id' => $schedule->id,
        'isi_notulen' => 'Pembahasan hasil RTL.',
    ])->assertRedirect(route('notulen-rtm.index'));

    $notulen = NotulenRtm::firstOrFail();
    $this->actingAs($member)->patch(route('notulen-rtm.ajukan', $notulen))->assertRedirect();
    expect($notulen->fresh()->status)->toBe('diajukan');

    $this->actingAs($member)->patch(route('notulen-rtm.verifikasi', $notulen))->assertForbidden();
    $this->actingAs($chair)->patch(route('notulen-rtm.verifikasi', $notulen))->assertRedirect();
    expect($notulen->fresh()->status)->toBe('diverifikasi')
        ->and($notulen->fresh()->verified_by)->toBe($chair->id);
});

it('allows updating Jadwal RTM successfully', function () {
    $chair = rtmUser('ketua-gkm');
    $semester = rtmSemester('ganjil', '2026-08-01', '2027-01-31');
    $schedule = JadwalRtm::create([
        'semester_id' => $semester->id,
        'judul' => 'RTM Awal',
        'tanggal' => '2027-02-10',
        'waktu_mulai' => '09:00:00',
        'waktu_selesai' => '11:00:00',
        'status' => 'terjadwal',
    ]);

    $this->actingAs($chair)->put(route('jadwal-rtm.update', $schedule), [
        'semester_id' => $semester->id,
        'judul' => 'RTM Diperbarui',
        'tanggal' => '2027-02-11',
        'waktu_mulai' => '09:30',
        'waktu_selesai' => '11:30',
        'lokasi' => 'Ruang Rapat',
        'agenda' => 'Pembahasan RTL',
        'status' => 'terjadwal',
    ])->assertRedirect(route('jadwal-rtm.index'));

    expect($schedule->fresh()->judul)->toBe('RTM Diperbarui')
        ->and($schedule->fresh()->lokasi)->toBe('Ruang Rapat');
});

it('accepts any Temuan for a decision based on semester with optional RTM', function () {
    $member = rtmUser('anggota-gkm');
    $previous = rtmSemester('ganjil', '2025-08-01', '2026-01-31');
    $current = rtmSemester('genap', '2026-02-01', '2026-07-31');
    $finding1 = findingFor($previous, 'TMN-PREV');
    $finding2 = findingFor($current, 'TMN-CURR');
    $finding3 = findingFor($current, 'TMN-NO-RTM');
    
    $schedule = JadwalRtm::create([
        'semester_id' => $current->id,
        'judul' => 'RTM Genap',
        'tanggal' => '2026-07-20',
        'status' => 'selesai',
    ]);
    $notulen = NotulenRtm::create([
        'jadwal_rtm_id' => $schedule->id,
        'isi_notulen' => 'Notulen terverifikasi.',
        'status' => 'diverifikasi',
        'input_by' => $member->id,
    ]);

    $payload = [
        'semester_id' => $current->id,
        'notulen_rtm_id' => $notulen->id,
        'uraian_keputusan' => 'Keputusan perbaikan.',
        'strategi' => 'Monitoring bulanan.',
    ];

    // Dapat menggunakan temuan dari semester lalu pada semester berjalan
    $this->actingAs($member)->post(route('keputusan-rtm.store'), $payload + [
        'temuan_id' => $finding1->id,
    ])->assertRedirect(route('keputusan-rtm.fakultas'));

    $this->assertDatabaseHas('keputusan_rtms', [
        'semester_id' => $current->id,
        'notulen_rtm_id' => $notulen->id,
        'temuan_id' => $finding1->id,
        'strategi' => 'Monitoring bulanan.',
    ]);

    // Tidak boleh duplikat temuan pada semester yang sama
    $this->actingAs($member)->post(route('keputusan-rtm.store'), $payload + [
        'temuan_id' => $finding1->id,
    ])->assertSessionHasErrors('temuan_id');

    // Dapat menggunakan temuan dari semester berjalan dengan RTM
    $this->actingAs($member)->post(route('keputusan-rtm.store'), $payload + [
        'temuan_id' => $finding2->id,
    ])->assertRedirect(route('keputusan-rtm.fakultas'));

    // Pilihan RTM bersifat opsional (null)
    $this->actingAs($member)->post(route('keputusan-rtm.store'), [
        'semester_id' => $current->id,
        'notulen_rtm_id' => null,
        'temuan_id' => $finding3->id,
        'uraian_keputusan' => 'Keputusan tanpa RTM.',
        'strategi' => null,
    ])->assertRedirect(route('keputusan-rtm.fakultas'));

    $this->assertDatabaseHas('keputusan_rtms', [
        'semester_id' => $current->id,
        'notulen_rtm_id' => null,
        'temuan_id' => $finding3->id,
        'uraian_keputusan' => 'Keputusan tanpa RTM.',
    ]);
});

it('allows only ketua-gkm to access the verifikasi page', function () {
    $member = rtmUser('anggota-gkm');
    $chair = rtmUser('ketua-gkm');

    $this->actingAs($member)->get(route('verifikasi.index'))->assertForbidden();
    $this->actingAs($chair)->get(route('verifikasi.index'))->assertOk();
});
