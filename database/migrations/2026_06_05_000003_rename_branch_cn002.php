<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Đổi tên chi nhánh 2 sang "Hai Bà Trưng" (giữ nguyên nếu đã được đổi thủ công)
        DB::table('CHI_NHANH')
            ->where('ma_chi_nhanh', 'CN002')
            ->where('ten_chi_nhanh', '8AM Coffee HCM')
            ->update(['ten_chi_nhanh' => '8AM Coffee Hai Bà Trưng']);
    }

    public function down(): void
    {
        DB::table('CHI_NHANH')
            ->where('ma_chi_nhanh', 'CN002')
            ->where('ten_chi_nhanh', '8AM Coffee Hai Bà Trưng')
            ->update(['ten_chi_nhanh' => '8AM Coffee HCM']);
    }
};
