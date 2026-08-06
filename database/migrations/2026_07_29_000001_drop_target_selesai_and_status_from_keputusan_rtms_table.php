<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('keputusan_rtms', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn(['target_selesai', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('keputusan_rtms', function (Blueprint $table) {
            $table->string('target_selesai')->nullable()->after('strategi');
            $table->string('status')->default('belum_dikerjakan')->after('target_selesai');
        });
    }
};
