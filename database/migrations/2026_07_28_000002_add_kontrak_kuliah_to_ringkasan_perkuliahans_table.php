<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ringkasan_perkuliahans', function (Blueprint $table) {
            $table->enum('kontrak_kuliah', ['ada', 'tidak_ada'])->default('ada')->after('kesesuaian_materi');
        });
    }

    public function down(): void
    {
        Schema::table('ringkasan_perkuliahans', function (Blueprint $table) {
            $table->dropColumn('kontrak_kuliah');
        });
    }
};
