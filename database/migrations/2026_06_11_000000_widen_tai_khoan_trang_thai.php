<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nới cột TAI_KHOAN.trang_thai (varchar(10) → 20) để chứa trạng thái mới
 * 'cho_xac_minh' (12 ký tự) khi tạo tài khoản chờ kích hoạt.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('TAI_KHOAN', function (Blueprint $table) {
            $table->string('trang_thai', 20)->default('active')->change();
        });
    }

    public function down(): void
    {
        Schema::table('TAI_KHOAN', function (Blueprint $table) {
            $table->string('trang_thai', 10)->default('active')->change();
        });
    }
};
