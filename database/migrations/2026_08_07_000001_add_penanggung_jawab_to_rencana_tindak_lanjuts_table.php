<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rencana_tindak_lanjuts', function (Blueprint $table) {
            $table->string('penanggung_jawab')->nullable()->after('temuan_id');
        });
    }

    public function down(): void
    {
        Schema::table('rencana_tindak_lanjuts', function (Blueprint $table) {
            $table->dropColumn('penanggung_jawab');
        });
    }
};
