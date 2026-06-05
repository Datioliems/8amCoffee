<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('SCAN_LOG')) {
            return;
        }
        Schema::create('SCAN_LOG', function (Blueprint $table) {
            $table->id();
            $table->string('ma_ban', 10);
            $table->string('ma_chi_nhanh', 10)->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 300)->nullable();
            $table->dateTime('thoi_gian');
            $table->index(['ma_chi_nhanh', 'thoi_gian']);
            $table->index('ma_ban');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('SCAN_LOG');
    }
};
