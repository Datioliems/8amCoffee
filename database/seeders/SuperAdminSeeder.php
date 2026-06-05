<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        // Tài khoản chủ chuỗi: superadmin / Admin@123 (xem mọi chi nhánh)
        DB::table('NHAN_VIEN')->updateOrInsert(
            ['ma_nv' => 'NV000'],
            ['ten_nv' => 'Chủ chuỗi 8AM', 'sdt' => '0900000000', 'cccd' => '000000000000', 'dia_chi' => 'Hà Nội', 'ma_chi_nhanh' => 'CN001']
        );
        DB::table('TAI_KHOAN')->updateOrInsert(
            ['ma_tai_khoan' => 'TK000'],
            ['ten_tk' => 'superadmin', 'mat_khau' => Hash::make('Admin@123'), 'chuc_vu' => 'superadmin', 'trang_thai' => 'active', 'ma_nv' => 'NV000']
        );
    }
}
