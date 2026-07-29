<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluasi_indikators', function (Blueprint $table) {
            $table->string('nama_penanggung_jawab')->nullable()->after('status_capaian');
        });

        // Copy existing penanggung_jawab from temuans to evaluasi_indikators
        $temuans = DB::table('temuans')->whereNotNull('nama_penanggung_jawab')->get();
        foreach ($temuans as $temuan) {
            if ($temuan->evaluasi_indikator_id) {
                DB::table('evaluasi_indikators')
                    ->where('id', $temuan->evaluasi_indikator_id)
                    ->update(['nama_penanggung_jawab' => $temuan->nama_penanggung_jawab]);
            }
        }

        Schema::table('temuans', function (Blueprint $table) {
            $table->dropColumn('nama_penanggung_jawab');
        });
    }

    public function down(): void
    {
        Schema::table('temuans', function (Blueprint $table) {
            $table->string('nama_penanggung_jawab')->nullable()->after('kode_temuan');
        });

        Schema::table('evaluasi_indikators', function (Blueprint $table) {
            $table->dropColumn('nama_penanggung_jawab');
        });
    }
};
