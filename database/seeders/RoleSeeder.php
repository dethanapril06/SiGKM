<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Ketua GKM',
                'slug' => 'ketua-gkm',
                'description' => 'Akun untuk Ketua Gugus Kendali Mutu',
            ],
            [
                'name' => 'Anggota GKM',
                'slug' => 'anggota-gkm',
                'description' => 'Akun untuk Anggota Gugus Kendali Mutu',
            ],
            [
                'name' => 'Koordinator Program Studi',
                'slug' => 'koordinator-prodi',
                'description' => 'Akun untuk Koordinator Program Studi',
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['slug' => $role['slug']],
                $role
            );
        }
    }
}