<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Kelas;

class KelasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $kelas = [
            [
                'nama_kelas' => 'A',
                'keterangan' => 'Kelas A Reguler',
            ],
            [
                'nama_kelas' => 'B',
                'keterangan' => 'Kelas B Reguler',
            ],
            [
                'nama_kelas' => 'C',
                'keterangan' => 'Kelas C Reguler',
            ],
        ];

        foreach ($kelas as $data) {
            Kelas::updateOrCreate(
                [
                    'nama_kelas' => $data['nama_kelas'],
                    'keterangan' => $data['keterangan'],
                ],
                $data
            );
        }
    }
}
