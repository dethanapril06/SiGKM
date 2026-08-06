<?php

use App\Models\Ami;
use App\Models\Role;
use App\Models\TahunAkademik;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function amiUser(string $role): User
{
    $roleModel = Role::create(['name' => $role, 'slug' => $role]);

    return User::factory()->create(['role_id' => $roleModel->id, 'is_active' => true]);
}

it('stores AMI by academic year with file uploads', function () {
    Storage::fake('public');
    $member = amiUser('anggota-gkm');
    $year = TahunAkademik::create(['nama' => '2026/2027']);

    $this->actingAs($member)->post(route('ami.store'), [
        'tahun_akademik_id' => $year->id,
        'tanggal_pelaksanaan' => '2027-06-01',
        'file_ami' => UploadedFile::fake()->create('ami.pdf', 100, 'application/pdf'),
        'file_tindak_lanjut' => UploadedFile::fake()->create('tindak_lanjut.pdf', 100, 'application/pdf'),
    ])->assertRedirect(route('ami.index'));

    $this->assertDatabaseHas('amis', [
        'tahun_akademik_id' => $year->id,
    ]);

    $ami = Ami::first();
    expect($ami->file_ami)->not->toBeNull();
    expect($ami->file_tindak_lanjut)->not->toBeNull();
});

it('allows the coordinator to view AMI but not modify it', function () {
    $coordinator = amiUser('koordinator-prodi');

    $this->actingAs($coordinator)->get(route('ami.index'))->assertOk();
    $this->actingAs($coordinator)->get(route('ami.create'))->assertForbidden();
});
