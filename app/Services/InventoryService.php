<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class InventoryService
{
    /** Lấy toàn bộ tồn kho của một chi nhánh */
    public function getStockOverview(string $maChiNhanh)
    {
        return DB::table('TON_KHO as tk')
            ->join('NGUYEN_LIEU as nl', 'nl.ma_nl', '=', 'tk.ma_nl')
            ->where('tk.ma_chi_nhanh', $maChiNhanh)
            ->select('nl.ten_nl','nl.don_vi','tk.sl_ton_kho_he_thong',
                     'tk.sl_ton_kho_thuc_te','tk.nguong_canh_bao','tk.hao_hut_cost')
            ->orderBy('nl.ten_nl')
            ->get();
    }

    /** Danh sách nguyên liệu dưới ngưỡng cảnh báo */
    public function getLowStockAlerts(string $maChiNhanh)
    {
        return DB::table('TON_KHO as tk')
            ->join('NGUYEN_LIEU as nl', 'nl.ma_nl', '=', 'tk.ma_nl')
            ->where('tk.ma_chi_nhanh', $maChiNhanh)
            ->where('tk.sl_ton_kho_he_thong', '<=', DB::raw('tk.nguong_canh_bao'))
            ->select('nl.ten_nl','nl.don_vi','tk.sl_ton_kho_he_thong','tk.nguong_canh_bao')
            ->orderBy('tk.sl_ton_kho_he_thong')
            ->get();
    }

    /** Trừ tồn kho thủ công (dùng khi cần override trigger) */
    public function deductStock(string $maChiNhanh, string $maNl, float $soLuong): void
    {
        DB::table('TON_KHO')
            ->where('ma_chi_nhanh', $maChiNhanh)
            ->where('ma_nl', $maNl)
            ->decrement('sl_ton_kho_he_thong', $soLuong);
    }
}
