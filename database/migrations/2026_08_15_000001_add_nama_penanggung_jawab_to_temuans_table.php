<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('temuans', function (Blueprint $table) {
            $table->string('nama_penanggung_jawab')->nullable()->after('evaluasi_indikator_id');
        });

        // Backfill nama_penanggung_jawab from rencana_tindak_lanjuts or evaluasi_indikators
        $temuans = DB::table('temuans')->get();
        foreach ($temuans as $temuan) {
            $pj = DB::table('rencana_tindak_lanjuts')
                ->where('temuan_id', $temuan->id)
                ->whereNotNull('penanggung_jawab')
                ->where('penanggung_jawab', '!=', '')
                ->value('penanggung_jawab');

            if (! $pj && $temuan->evaluasi_indikator_id) {
                $pj = DB::table('evaluasi_indikators')
                    ->where('id', $temuan->evaluasi_indikator_id)
                    ->value('nama_penanggung_jawab');
            }

            if ($pj) {
                DB::table('temuans')
                    ->where('id', $temuan->id)
                    ->update(['nama_penanggung_jawab' => $pj]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('temuans', function (Blueprint $table) {
            $table->dropColumn('nama_penanggung_jawab');
        });
    }
};
