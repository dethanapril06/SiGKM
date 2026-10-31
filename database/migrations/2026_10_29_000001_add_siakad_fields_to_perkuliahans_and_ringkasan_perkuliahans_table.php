<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('perkuliahans', 'siakad_kelas_id')) {
            Schema::table('perkuliahans', function (Blueprint $table) {
                $table->string('siakad_kelas_id', 30)->nullable()->after('kelas_id')->index();
            });
        }

        if (! Schema::hasColumn('ringkasan_perkuliahans', 'metode_pembelajaran')) {
            Schema::table('ringkasan_perkuliahans', function (Blueprint $table) {
                $table->string('metode_pembelajaran', 50)->nullable()->after('kontrak_kuliah');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('ringkasan_perkuliahans', 'metode_pembelajaran')) {
            Schema::table('ringkasan_perkuliahans', function (Blueprint $table) {
                $table->dropColumn('metode_pembelajaran');
            });
        }

        if (Schema::hasColumn('perkuliahans', 'siakad_kelas_id')) {
            Schema::table('perkuliahans', function (Blueprint $table) {
                $table->dropIndex(['siakad_kelas_id']);
                $table->dropColumn('siakad_kelas_id');
            });
        }
    }
};

