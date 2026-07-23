<?php

namespace Database\Seeders;

use App\Models\GkmMembership;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $ketuaRole = Role::where('slug', 'ketua-gkm')->first();

        if ($ketuaRole) {
            User::updateOrCreate(
                ['email' => 'ketua@gkm.test'],
                [
                    'role_id' => $ketuaRole->id,
                    'name' => 'Ketua GKM',
                    'password' => Hash::make('password'),
                    'is_active' => true,
                ]
            );

            GkmMembership::updateOrCreate(
                ['nama_anggota' => 'Ketua GKM', 'peran' => 'ketua'],
                [
                    'nip' => '198001012005011001',
                    'tanggal_mulai' => now()->startOfYear()->toDateString(),
                    'tanggal_selesai' => null,
                    'is_active' => true,
                ]
            );
        }

        $anggotaRole = Role::where('slug', 'anggota-gkm')->first();
        if ($anggotaRole) {
            User::updateOrCreate(
                ['email' => 'anggota@gkm.test'],
                [
                    'role_id' => $anggotaRole->id,
                    'name' => 'Anggota GKM',
                    'password' => Hash::make('password'),
                    'is_active' => true,
                ]
            );

            GkmMembership::updateOrCreate(
                ['nama_anggota' => 'Anggota GKM', 'peran' => 'anggota'],
                [
                    'nip' => '198501012010011002',
                    'tanggal_mulai' => now()->startOfYear()->toDateString(),
                    'tanggal_selesai' => null,
                    'is_active' => true,
                ]
            );
        }

        $koordinatorRole = Role::where('slug', 'koordinator-prodi')->first();
        if ($koordinatorRole) {
            User::updateOrCreate(
                ['email' => 'koordinator@prodi.test'],
                [
                    'role_id' => $koordinatorRole->id,
                    'name' => 'Koordinator Prodi',
                    'password' => Hash::make('password'),
                    'is_active' => true,
                ]
            );
        }
    }
}