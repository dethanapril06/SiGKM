<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Hapus tabel dokumen_amis karena tidak dipakai lagi
        Schema::dropIfExists('dokumen_amis');

        Schema::table('amis', function (Blueprint $table) {
            // Hapus kolom lama yang tidak dipakai
            $table->dropIndex(['tanggal_pelaksanaan', 'status']);
            $table->dropColumn([
                'temuan',
                'rekomendasi',
                'tindak_lanjut',
                'target_selesai',
                'status',
            ]);

            // Tambah kolom file tetap (path file di storage)
            $table->string('file_ami')->nullable()->after('tanggal_pelaksanaan');
            $table->string('file_tindak_lanjut')->nullable()->after('file_ami');
            $table->string('file_dokumentasi')->nullable()->after('file_tindak_lanjut');
            $table->string('file_absensi')->nullable()->after('file_dokumentasi');
        });
    }

    public function down(): void
    {
        Schema::table('amis', function (Blueprint $table) {
            $table->dropColumn([
                'file_ami',
                'file_tindak_lanjut',
                'file_dokumentasi',
                'file_absensi',
            ]);

            $table->text('temuan')->after('tanggal_pelaksanaan');
            $table->text('rekomendasi')->after('temuan');
            $table->text('tindak_lanjut')->nullable()->after('rekomendasi');
            $table->date('target_selesai')->nullable()->after('tindak_lanjut');
            $table->enum('status', ['draft', 'aktif', 'selesai'])->default('draft')->after('input_by');
            $table->index(['tanggal_pelaksanaan', 'status']);
        });

        Schema::create('dokumen_amis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ami_id')->constrained('amis')->cascadeOnDelete();
            $table->string('nama_dokumen');
            $table->string('file_path')->nullable();
            $table->string('link_url')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }
};
