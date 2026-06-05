<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChiNhanhSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('CHI_NHANH')->insertOrIgnore([
            ['ma_chi_nhanh' => 'CN001', 'ten_chi_nhanh' => '8AM Coffee & Roastery', 'dia_chi' => '34 Tăng Bạt Hổ, Hai Bà Trưng, Hà Nội', 'sdt' => '0241234567'],
            ['ma_chi_nhanh' => 'CN002', 'ten_chi_nhanh' => '8AM Coffee HCM', 'dia_chi' => '456 Nguyễn Huệ, Q1, TP HCM', 'sdt' => '0289876543'],
        ]);
    }
}
