<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE evaluasi_indikators MODIFY COLUMN status_capaian VARCHAR(50) NOT NULL DEFAULT 'belum_tercapai'");
        }
        DB::table('evaluasi_indikators')->where('status_capaian', 'hampir_tercapai')->update(['status_capaian' => 'dalam_proses']);
    }

    public function down(): void
    {
        DB::table('evaluasi_indikators')->where('status_capaian', 'dalam_proses')->update(['status_capaian' => 'hampir_tercapai']);
    }
};
