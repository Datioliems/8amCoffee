<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DanhMucSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('DANH_MUC')->insertOrIgnore([
            ['ma_danh_muc' => 'DM001', 'ten_danh_muc' => 'Arabica Base'],
            ['ma_danh_muc' => 'DM002', 'ten_danh_muc' => 'Signature'],
            ['ma_danh_muc' => 'DM003', 'ten_danh_muc' => 'Hand Brew'],
            ['ma_danh_muc' => 'DM004', 'ten_danh_muc' => 'Cold Brew'],
            ['ma_danh_muc' => 'DM005', 'ten_danh_muc' => 'Fine Robusta Base'],
            ['ma_danh_muc' => 'DM006', 'ten_danh_muc' => 'Not-café'],
            ['ma_danh_muc' => 'DM007', 'ten_danh_muc' => 'Eats'],
        ]);
    }
}
