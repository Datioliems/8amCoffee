<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BanSeeder extends Seeder
{
    public function run(): void
    {
        // 17 bàn khớp model 3D (public/models):
        //   B001–B008 = Tầng 1 (2 ghế) · B009–B013 = Tầng 2 · B014–B017 = Tầng 3 (4 ghế)
        for ($i = 1; $i <= 17; $i++) {
            if ($i <= 8)       $tang = 'Tầng 1';
            elseif ($i <= 13)  $tang = 'Tầng 2';
            else               $tang = 'Tầng 3';

            $maBan = 'B'.str_pad((string) $i, 3, '0', STR_PAD_LEFT);

            // updateOrInsert: cập nhật so_ghe/vi_tri cho bàn cũ, thêm bàn mới —
            // KHÔNG đụng tới trang_thai (giữ nguyên bàn đang có khách).
            DB::table('BAN')->updateOrInsert(
                ['ma_ban' => $maBan],
                [
                    'so_ban'       => $i,
                    'vi_tri'       => $tang,
                    'so_ghe'       => $i <= 8 ? 2 : 4,
                    'ma_chi_nhanh' => 'CN001',
                ]
            );
        }
    }
}
