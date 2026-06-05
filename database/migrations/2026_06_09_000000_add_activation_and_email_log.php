<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Xác thực email khi tạo tài khoản nhân viên:
 *   - TAI_KHOAN: token kích hoạt + mốc xác thực + thời điểm tạo (để cronjob dọn).
 *   - EMAIL_LOG: nhật ký mọi email hệ thống đã gửi (credentials / kích hoạt / OTP).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('TAI_KHOAN', function (Blueprint $table) {
            $table->dateTime('email_xac_thuc_luc')->nullable()->after('ip_dang_nhap_cuoi');
            $table->string('kich_hoat_token', 64)->nullable()->after('email_xac_thuc_luc');
            $table->dateTime('kich_hoat_het_han')->nullable()->after('kich_hoat_token');
            $table->dateTime('tao_luc')->nullable()->after('kich_hoat_het_han');

            $table->index('kich_hoat_token');
        });

        // Tài khoản cũ coi như đã xác thực (tránh bị cronjob xoá nhầm).
        Schema::getConnection()->table('TAI_KHOAN')
            ->whereNull('email_xac_thuc_luc')
            ->update(['email_xac_thuc_luc' => now(), 'tao_luc' => now()]);

        Schema::create('EMAIL_LOG', function (Blueprint $table) {
            $table->bigIncrements('id');
            // tai_khoan | kich_hoat | otp | khac
            $table->string('loai', 30);
            $table->string('email', 150);
            $table->string('tieu_de', 200)->nullable();
            $table->string('ma_tham_chieu', 30)->nullable();   // ma_tai_khoan / ma_order…
            // thanh_cong | that_bai
            $table->string('trang_thai', 20)->default('that_bai');
            $table->string('loi', 500)->nullable();
            $table->dateTime('thoi_gian')->useCurrent();

            $table->index(['loai', 'thoi_gian']);
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('EMAIL_LOG');

        Schema::table('TAI_KHOAN', function (Blueprint $table) {
            $table->dropColumn(['email_xac_thuc_luc', 'kich_hoat_token', 'kich_hoat_het_han', 'tao_luc']);
        });
    }
};
