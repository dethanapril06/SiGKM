<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('keputusan_rtms', function (Blueprint $table) {
            $table->foreignId('temuan_id')->nullable()->after('notulen_rtm_id')->constrained('temuans')->restrictOnDelete();
        });

        Schema::table('keputusan_rtms', function (Blueprint $table) {
            $table->unique(['notulen_rtm_id', 'temuan_id'], 'keputusan_rtm_notulen_temuan_unique');
        });

        try {
            Schema::table('keputusan_rtms', function (Blueprint $table) {
                $table->dropForeign(['rencana_tindak_lanjut_id']);
            });
        } catch (\Throwable $e) {
            // Ignore if foreign key doesn't exist or SQLite
        }

        Schema::table('keputusan_rtms', function (Blueprint $table) {
            $table->dropUnique('keputusan_rtm_notulen_rtl_unique');
            $table->dropColumn('rencana_tindak_lanjut_id');
        });

        Schema::table('keputusan_rtms', function (Blueprint $table) {
            $table->foreignId('temuan_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('keputusan_rtms', function (Blueprint $table) {
            $table->dropForeign(['temuan_id']);
        });
        Schema::table('keputusan_rtms', function (Blueprint $table) {
            $table->dropUnique('keputusan_rtm_notulen_temuan_unique');
            $table->dropColumn('temuan_id');
            $table->foreignId('rencana_tindak_lanjut_id')->after('notulen_rtm_id')->constrained('rencana_tindak_lanjuts')->restrictOnDelete();
            $table->unique(['notulen_rtm_id', 'rencana_tindak_lanjut_id'], 'keputusan_rtm_notulen_rtl_unique');
        });
    }
};
