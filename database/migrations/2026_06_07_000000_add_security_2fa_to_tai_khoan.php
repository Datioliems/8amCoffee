<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An toàn thông tin: thêm cột chống brute-force + xác thực 2 lớp (OTP email)
 * vào TAI_KHOAN và tạo bảng NHAT_KY_DANG_NHAP (audit log).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('TAI_KHOAN', function (Blueprint $table) {
            // Chống brute-force: đếm số lần sai + khoá tạm thời.
            $table->unsignedInteger('dang_nhap_sai')->default(0)->after('trang_thai');
            $table->dateTime('khoa_den')->nullable()->after('dang_nhap_sai');

            // Xác thực 2 lớp (OTP gửi qua email NHAN_VIEN).
            $table->boolean('xac_thuc_2_lop')->default(true)->after('khoa_den');
            $table->string('otp_ma', 255)->nullable()->after('xac_thuc_2_lop');   // lưu HASH của OTP
            $table->dateTime('otp_het_han')->nullable()->after('otp_ma');
            $table->unsignedInteger('otp_sai')->default(0)->after('otp_het_han');

            // Theo dõi đăng nhập.
            $table->dateTime('lan_dang_nhap_cuoi')->nullable()->after('otp_sai');
            $table->string('ip_dang_nhap_cuoi', 45)->nullable()->after('lan_dang_nhap_cuoi');
        });

        Schema::create('NHAT_KY_DANG_NHAP', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('ten_tk', 50)->nullable();
            $table->string('ma_tai_khoan', 10)->nullable();
            $table->string('ma_nv', 10)->nullable();
            // dang_nhap | dang_nhap_that_bai | tai_khoan_bi_khoa | otp_gui |
            // otp_thanh_cong | otp_sai | dang_xuat
            $table->string('hanh_dong', 30);
            $table->boolean('thanh_cong')->default(false);
            $table->string('dia_chi_ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->string('chi_tiet', 255)->nullable();
            $table->dateTime('thoi_gian')->useCurrent();

            $table->index(['ten_tk', 'thoi_gian']);
            $table->index('hanh_dong');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('NHAT_KY_DANG_NHAP');

        Schema::table('TAI_KHOAN', function (Blueprint $table) {
            $table->dropColumn([
                'dang_nhap_sai', 'khoa_den', 'xac_thuc_2_lop',
                'otp_ma', 'otp_het_han', 'otp_sai',
                'lan_dang_nhap_cuoi', 'ip_dang_nhap_cuoi',
            ]);
        });
    }
};
