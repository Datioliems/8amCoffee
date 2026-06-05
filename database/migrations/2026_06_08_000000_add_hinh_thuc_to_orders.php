<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hình thức phục vụ cho mỗi đơn:
 *   - tai_ban : uống tại bàn (dùng ly/cốc sứ - thủy tinh).
 *   - mang_ve : mang về (đóng cốc nhựa / mang đi).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ORDERS', function (Blueprint $table) {
            $table->string('hinh_thuc', 10)->default('tai_ban')->after('ghi_chu');
        });

        // Đơn mang về cũ (không có bàn) → đánh dấu mang_ve cho nhất quán.
        Schema::getConnection()->table('ORDERS')
            ->whereNull('ma_ban')
            ->update(['hinh_thuc' => 'mang_ve']);
    }

    public function down(): void
    {
        Schema::table('ORDERS', function (Blueprint $table) {
            $table->dropColumn('hinh_thuc');
        });
    }
};
