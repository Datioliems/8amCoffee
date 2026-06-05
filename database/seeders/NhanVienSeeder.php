<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NhanVienSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('NHAN_VIEN')->insertOrIgnore([
            ['ma_nv' => 'NV001', 'ten_nv' => 'Nguyễn Văn An', 'sdt' => '0901234567', 'cccd' => '001234567890', 'dia_chi' => 'Hà Nội', 'ma_chi_nhanh' => 'CN001'],
            ['ma_nv' => 'NV002', 'ten_nv' => 'Trần Thị Bình', 'sdt' => '0912345678', 'cccd' => '001234567891', 'dia_chi' => 'Hà Nội', 'ma_chi_nhanh' => 'CN001'],
            ['ma_nv' => 'NV003', 'ten_nv' => 'Lê Văn Cường', 'sdt' => '0923456789', 'cccd' => '001234567892', 'dia_chi' => 'Hà Nội', 'ma_chi_nhanh' => 'CN001'],
        ]);
    }
}
