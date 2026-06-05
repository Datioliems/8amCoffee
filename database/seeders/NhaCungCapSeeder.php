<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NhaCungCapSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('NHA_CUNG_CAP')->insertOrIgnore([
            ['ma_ncc' => 'NCC001', 'ten_ncc' => 'Phúc Sinh Corporation', 'dia_chi' => 'TP. Hồ Chí Minh', 'sdt' => '0281234567', 'email' => 'info@phucsinh.com'],
            ['ma_ncc' => 'NCC002', 'ten_ncc' => 'Dalat Milk', 'dia_chi' => 'Đà Lạt, Lâm Đồng', 'sdt' => '0263456789', 'email' => 'dalatmilk@dl.vn'],
            ['ma_ncc' => 'NCC003', 'ten_ncc' => 'Khánh Hòa Salanganes', 'dia_chi' => 'Khánh Hòa', 'sdt' => '0258765432', 'email' => null],
            ['ma_ncc' => 'NCC004', 'ten_ncc' => 'Thực phẩm Đức Việt', 'dia_chi' => 'Hà Nội', 'sdt' => '0241112222', 'email' => 'ducviet@hn.vn'],
        ]);
    }
}
