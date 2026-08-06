<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rencana_tindak_lanjuts', function (Blueprint $table) {
            $table->dropForeign(['verified_by']);
            $table->dropIndex(['status']);
            $table->dropColumn([
                'uraian_rencana_tindak_lanjut',
                'uraian_tindak_koreksi',
                'target_selesai',
                'status',
                'submitted_at',
                'verified_by',
                'verified_at',
                'catatan_verifikasi',
            ]);

            $table->text('uraian_realisasi')->nullable()->after('temuan_id');
            $table->date('waktu_pelaksanaan')->nullable()->after('uraian_realisasi');
            $table->text('catatan')->nullable()->after('waktu_pelaksanaan');
        });
    }

    public function down(): void
    {
        Schema::table('rencana_tindak_lanjuts', function (Blueprint $table) {
            $table->dropColumn(['uraian_realisasi', 'waktu_pelaksanaan', 'catatan']);

            $table->text('uraian_rencana_tindak_lanjut')->nullable();
            $table->text('uraian_tindak_koreksi')->nullable();
            $table->date('target_selesai')->nullable();
            $table->enum('status', ['draft', 'diajukan', 'diverifikasi', 'ditolak'])->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('catatan_verifikasi')->nullable();
        });
    }
};
