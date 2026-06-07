<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('MON', function (Blueprint $table) {
            // NULL = auto-managed (scheduler có thể ẩn/hiện)
            // 1    = đã tự động ẩn bởi scheduler
            // 0    = nhân viên chủ động hiện, scheduler bỏ qua
            $table->tinyInteger('tu_dong_an')->nullable()->default(null)->after('trang_thai');
        });
    }

    public function down(): void
    {
        Schema::table('MON', function (Blueprint $table) {
            $table->dropColumn('tu_dong_an');
        });
    }
};
