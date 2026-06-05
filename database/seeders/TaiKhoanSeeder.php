<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TaiKhoanSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('TAI_KHOAN')->insertOrIgnore([
            [
                'ma_tai_khoan' => 'TK001',
                'ten_tk' => 'admin_8am',
                'mat_khau' => Hash::make('Admin@123'),
                'chuc_vu' => 'admin',
                'trang_thai' => 'active',
                'ma_nv' => 'NV001',
            ],
            [
                'ma_tai_khoan' => 'TK002',
                'ten_tk' => 'bartender01',
                'mat_khau' => Hash::make('Admin@123'),
                'chuc_vu' => 'nhan_vien',
                'trang_thai' => 'active',
                'ma_nv' => 'NV002',
            ],
            [
                'ma_tai_khoan' => 'TK003',
                'ten_tk' => 'staff01',
                'mat_khau' => Hash::make('Admin@123'),
                'chuc_vu' => 'nhan_vien',
                'trang_thai' => 'active',
                'ma_nv' => 'NV003',
            ],
        ]);
    }
}
