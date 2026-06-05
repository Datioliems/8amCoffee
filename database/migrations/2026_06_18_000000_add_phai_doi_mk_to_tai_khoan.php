<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('TAI_KHOAN', 'phai_doi_mk')) {
            Schema::table('TAI_KHOAN', function (Blueprint $table) {
                // Bắt buộc đổi mật khẩu trong lần đăng nhập đầu tiên.
                $table->boolean('phai_doi_mk')->default(true)->after('mat_khau');
            });
        }

        // Tài khoản ĐÃ từng đăng nhập → không làm phiền (chỉ áp dụng cho tài khoản
        // mới / chưa từng đăng nhập, vốn còn dùng mật khẩu khởi tạo).
        DB::table('TAI_KHOAN')->whereNotNull('lan_dang_nhap_cuoi')->update(['phai_doi_mk' => false]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('TAI_KHOAN', 'phai_doi_mk')) {
            Schema::table('TAI_KHOAN', function (Blueprint $table) {
                $table->dropColumn('phai_doi_mk');
            });
        }
    }
};
