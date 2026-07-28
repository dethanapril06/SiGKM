<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laporans', function (Blueprint $table) {
            $table->foreignId('jadwal_monev_id')->nullable()->after('semester_id')->constrained('jadwal_monevs')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('laporans', function (Blueprint $table) {
            $table->dropForeign(['jadwal_monev_id']);
            $table->dropColumn('jadwal_monev_id');
        });
    }
};
