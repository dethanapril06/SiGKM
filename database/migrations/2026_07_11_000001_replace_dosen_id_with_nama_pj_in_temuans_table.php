<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('temuans', function (Blueprint $table) {
            $table->dropForeign(['dosen_id']);
            $table->dropIndex(['dosen_id']);
            $table->dropColumn('dosen_id');
            $table->string('nama_penanggung_jawab')->nullable()->after('kode_temuan');
        });
    }

    public function down(): void
    {
        Schema::table('temuans', function (Blueprint $table) {
            $table->dropColumn('nama_penanggung_jawab');
            $table->foreignId('dosen_id')->constrained('dosens')->restrictOnDelete();
            $table->index('dosen_id');
        });
    }
};
