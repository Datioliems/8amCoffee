<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Yêu cầu đổi bàn của khách (cần nhân viên duyệt) — chỉ với đơn đã gửi/đã xác nhận.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('YEU_CAU_DOI_BAN', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('ma_order', 20);
            $table->string('ma_ban_cu', 10);
            $table->string('ma_ban_moi', 10);
            $table->string('ma_chi_nhanh', 10);
            // cho_duyet | da_duyet | tu_choi
            $table->string('trang_thai', 12)->default('cho_duyet');
            $table->string('ma_nv_xu_ly', 10)->nullable();
            $table->dateTime('thoi_gian_tao')->useCurrent();
            $table->dateTime('thoi_gian_xu_ly')->nullable();

            $table->index(['ma_chi_nhanh', 'trang_thai']);
            $table->index('ma_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('YEU_CAU_DOI_BAN');
    }
};
