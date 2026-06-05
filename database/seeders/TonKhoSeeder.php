<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TonKhoSeeder extends Seeder
{
    public function run(): void
    {
        // Mức tồn mẫu theo từng nguyên liệu
        $mau = [
            'NL001' => [5000.00, 500.00],
            'NL002' => [3000.00, 300.00],
            'NL003' => [15000.00, 1500.00],
            'NL004' => [5000.00, 500.00],
            'NL005' => [50000.00, 5000.00],
            'NL006' => [3000.00, 300.00],
        ];

        // Seed tồn kho cho TẤT CẢ chi nhánh × nguyên liệu (tránh chi nhánh mới bị "thiếu" giả)
        $branches    = DB::table('CHI_NHANH')->pluck('ma_chi_nhanh');
        $nguyenLieus = DB::table('NGUYEN_LIEU')->pluck('ma_nl');

        foreach ($branches as $cn) {
            foreach ($nguyenLieus as $nl) {
                [$ton, $nguong] = $mau[$nl] ?? [1000.00, 100.00];
                DB::table('TON_KHO')->insertOrIgnore([
                    'ma_chi_nhanh'        => $cn,
                    'ma_nl'               => $nl,
                    'sl_ton_kho_he_thong' => $ton,
                    'sl_ton_kho_thuc_te'  => $ton,
                    'nguong_canh_bao'     => $nguong,
                    'hao_hut_cost'        => 0,
                ]);
            }
        }
    }
}
