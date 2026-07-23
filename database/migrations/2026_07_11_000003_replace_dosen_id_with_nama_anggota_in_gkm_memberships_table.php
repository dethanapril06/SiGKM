<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gkm_memberships', function (Blueprint $table) {
            $table->dropForeign(['dosen_id']);
            $table->dropColumn('dosen_id');

            $table->string('nama_anggota')->after('id');
            $table->string('nip', 50)->nullable()->after('nama_anggota');
        });
    }

    public function down(): void
    {
        Schema::table('gkm_memberships', function (Blueprint $table) {
            $table->dropColumn(['nama_anggota', 'nip']);
            $table->foreignId('dosen_id')->nullable()->constrained('dosens')->restrictOnDelete();
        });
    }
};
