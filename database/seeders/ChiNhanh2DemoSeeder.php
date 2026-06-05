<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ChiNhanh2DemoSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Gán map giả lập cho CN002
        DB::table('CHI_NHANH')->where('ma_chi_nhanh', 'CN002')->update(['model_3d' => 'cafe_CN002.glb']);

        // 2) 6 bàn CN002 (khớp mesh BAN_B101..B106 trong cafe_CN002.glb)
        for ($i = 1; $i <= 6; $i++) {
            DB::table('BAN')->updateOrInsert(
                ['ma_ban' => 'B10'.$i],
                ['so_ban' => $i, 'vi_tri' => 'Tầng 1', 'so_ghe' => 4, 'ma_chi_nhanh' => 'CN002']
            );
        }

        // 3) Tài khoản quản lý cho CN002 (đăng nhập: manager_hcm / Admin@123)
        DB::table('NHAN_VIEN')->updateOrInsert(
            ['ma_nv' => 'NV101'],
            ['ten_nv' => 'Phạm Quản Lý HCM', 'sdt' => '0931234567', 'cccd' => '079234567001', 'dia_chi' => 'TP HCM', 'ma_chi_nhanh' => 'CN002']
        );
        DB::table('TAI_KHOAN')->updateOrInsert(
            ['ma_tai_khoan' => 'TK101'],
            ['ten_tk' => 'manager_hcm', 'mat_khau' => Hash::make('Admin@123'), 'chuc_vu' => 'quan_ly', 'trang_thai' => 'active', 'ma_nv' => 'NV101']
        );
    }
}
