<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $branches    = DB::table('CHI_NHANH')->pluck('ma_chi_nhanh');
        $nguyenLieus = DB::table('NGUYEN_LIEU')->pluck('ma_nl');

        // Lấy mức tồn mẫu từ CN001 (nếu có) để áp cho các chi nhánh khác
        $mau = DB::table('TON_KHO')->where('ma_chi_nhanh', 'CN001')->get()->keyBy('ma_nl');

        foreach ($branches as $cn) {
            foreach ($nguyenLieus as $nl) {
                $exists = DB::table('TON_KHO')
                    ->where('ma_chi_nhanh', $cn)->where('ma_nl', $nl)->exists();
                if ($exists) {
                    continue;
                }
                $m = $mau->get($nl);
                DB::table('TON_KHO')->insert([
                    'ma_chi_nhanh'        => $cn,
                    'ma_nl'               => $nl,
                    'sl_ton_kho_he_thong' => $m->sl_ton_kho_he_thong ?? 1000,
                    'sl_ton_kho_thuc_te'  => $m->sl_ton_kho_thuc_te ?? 1000,
                    'nguong_canh_bao'     => $m->nguong_canh_bao ?? 100,
                    'hao_hut_cost'        => 0,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Không xóa tồn kho khi rollback để tránh mất dữ liệu vận hành.
    }
};
