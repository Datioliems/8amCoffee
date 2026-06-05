<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Thêm cho TAI_KHOAN:
 *  - reset_token / reset_het_han   : đặt lại mật khẩu (quên mật khẩu)
 *  - remember_token / remember_het_han : ghi nhớ đăng nhập (remember me)
 *  - quyen (JSON)                  : danh sách quyền RIÊNG của tài khoản (override
 *                                    quyền mặc định theo vai trò). NULL = dùng mặc định.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('TAI_KHOAN', function (Blueprint $table) {
            if (! Schema::hasColumn('TAI_KHOAN', 'reset_token')) {
                $table->char('reset_token', 64)->nullable()->after('kich_hoat_het_han');
            }
            if (! Schema::hasColumn('TAI_KHOAN', 'reset_het_han')) {
                $table->dateTime('reset_het_han')->nullable()->after('reset_token');
            }
            if (! Schema::hasColumn('TAI_KHOAN', 'remember_token')) {
                $table->char('remember_token', 64)->nullable()->after('reset_het_han');
            }
            if (! Schema::hasColumn('TAI_KHOAN', 'remember_het_han')) {
                $table->dateTime('remember_het_han')->nullable()->after('remember_token');
            }
            if (! Schema::hasColumn('TAI_KHOAN', 'quyen')) {
                $table->text('quyen')->nullable()->after('chuc_vu'); // JSON mảng quyền
            }
        });
    }

    public function down(): void
    {
        Schema::table('TAI_KHOAN', function (Blueprint $table) {
            foreach (['reset_token', 'reset_het_han', 'remember_token', 'remember_het_han', 'quyen'] as $col) {
                if (Schema::hasColumn('TAI_KHOAN', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
