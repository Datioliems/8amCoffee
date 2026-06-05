<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NguyenLieuSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('NGUYEN_LIEU')->insertOrIgnore([
            ['ma_nl' => 'NL001', 'ten_nl' => 'Cà phê hạt Arabica', 'don_vi' => 'gram'],
            ['ma_nl' => 'NL002', 'ten_nl' => 'Cà phê hạt Robusta Fine', 'don_vi' => 'gram'],
            ['ma_nl' => 'NL003', 'ten_nl' => 'Sữa tươi nguyên kem', 'don_vi' => 'ml'],
            ['ma_nl' => 'NL004', 'ten_nl' => 'Sữa đặc', 'don_vi' => 'ml'],
            ['ma_nl' => 'NL005', 'ten_nl' => 'Nước lọc', 'don_vi' => 'ml'],
            ['ma_nl' => 'NL006', 'ten_nl' => 'Kem béo (heavy cream)', 'don_vi' => 'ml'],
        ]);
    }
}
