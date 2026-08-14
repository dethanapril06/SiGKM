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
        Schema::table('keputusan_rtms', function (Blueprint $table) {
            $table->foreignId('semester_id')->nullable()->after('id')->constrained('semesters')->cascadeOnDelete();
        });

        // Backfill semester_id from notulen_rtm -> jadwal_rtm or temuan -> evaluasi_indikator
        $keputusans = DB::table('keputusan_rtms')->get();
        foreach ($keputusans as $keputusan) {
            $semesterId = null;

            if ($keputusan->notulen_rtm_id) {
                $semesterId = DB::table('notulen_rtms')
                    ->join('jadwal_rtms', 'notulen_rtms.jadwal_rtm_id', '=', 'jadwal_rtms.id')
                    ->where('notulen_rtms.id', $keputusan->notulen_rtm_id)
                    ->value('jadwal_rtms.semester_id');
            }

            if (! $semesterId && $keputusan->temuan_id) {
                $semesterId = DB::table('temuans')
                    ->join('evaluasi_indikators', 'temuans.evaluasi_indikator_id', '=', 'evaluasi_indikators.id')
                    ->where('temuans.id', $keputusan->temuan_id)
                    ->value('evaluasi_indikators.semester_id');
            }

            if ($semesterId) {
                DB::table('keputusan_rtms')
                    ->where('id', $keputusan->id)
                    ->update(['semester_id' => $semesterId]);
            }
        }

        try {
            Schema::table('keputusan_rtms', function (Blueprint $table) {
                $table->dropForeign(['notulen_rtm_id']);
            });
        } catch (\Throwable $e) {
            // Ignore if SQLite or drop foreign fails
        }

        try {
            Schema::table('keputusan_rtms', function (Blueprint $table) {
                $table->dropUnique('keputusan_rtm_notulen_temuan_unique');
            });
        } catch (\Throwable $e) {
            // Ignore if index doesn't exist
        }

        Schema::table('keputusan_rtms', function (Blueprint $table) {
            $table->foreignId('notulen_rtm_id')->nullable()->change();
            $table->unique(['semester_id', 'temuan_id'], 'keputusan_rtm_semester_temuan_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            Schema::table('keputusan_rtms', function (Blueprint $table) {
                $table->dropUnique('keputusan_rtm_semester_temuan_unique');
            });
        } catch (\Throwable $e) {
            // Ignore
        }

        Schema::table('keputusan_rtms', function (Blueprint $table) {
            $table->dropForeign(['semester_id']);
            $table->dropColumn('semester_id');
        });
    }
};
