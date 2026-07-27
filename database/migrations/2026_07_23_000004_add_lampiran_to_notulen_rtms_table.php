<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notulen_rtms', function (Blueprint $table) {
            $table->string('file_undangan')->nullable()->after('isi_notulen');
            $table->string('file_absensi')->nullable()->after('file_undangan');
            $table->string('file_dokumentasi')->nullable()->after('file_absensi');
        });
    }

    public function down(): void
    {
        Schema::table('notulen_rtms', function (Blueprint $table) {
            $table->dropColumn(['file_undangan', 'file_absensi', 'file_dokumentasi']);
        });
    }
};
