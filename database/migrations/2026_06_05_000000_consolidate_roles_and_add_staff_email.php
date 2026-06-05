<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Gộp vai trò: admin→superadmin, quan_ly→admin, bartender→nhan_vien
        // Thứ tự quan trọng: đổi admin trước để không đụng quan_ly→admin.
        DB::table('TAI_KHOAN')->where('chuc_vu', 'admin')->update(['chuc_vu' => 'superadmin']);
        DB::table('TAI_KHOAN')->where('chuc_vu', 'quan_ly')->update(['chuc_vu' => 'admin']);
        DB::table('TAI_KHOAN')->where('chuc_vu', 'bartender')->update(['chuc_vu' => 'nhan_vien']);

        // Email nhân viên (để gửi mật khẩu tự động)
        if (! Schema::hasColumn('NHAN_VIEN', 'email')) {
            Schema::table('NHAN_VIEN', function (Blueprint $table) {
                $table->string('email', 150)->nullable()->after('sdt');
            });
        }
    }

    public function down(): void
    {
        // Khôi phục gần đúng (bartender không tách lại được)
        DB::table('TAI_KHOAN')->where('chuc_vu', 'admin')->update(['chuc_vu' => 'quan_ly']);
        DB::table('TAI_KHOAN')->where('chuc_vu', 'superadmin')->update(['chuc_vu' => 'admin']);

        if (Schema::hasColumn('NHAN_VIEN', 'email')) {
            Schema::table('NHAN_VIEN', function (Blueprint $table) {
                $table->dropColumn('email');
            });
        }
    }
};
