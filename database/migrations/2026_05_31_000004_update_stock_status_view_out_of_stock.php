<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS VW_TON_KHO_TONG_QUAN');

        DB::statement("
            CREATE VIEW VW_TON_KHO_TONG_QUAN AS
            SELECT
                cn.ten_chi_nhanh,
                nl.ten_nl,
                nl.don_vi,
                tk.sl_ton_kho_he_thong,
                tk.sl_ton_kho_thuc_te,
                tk.nguong_canh_bao,
                tk.hao_hut_cost,
                CASE
                    WHEN tk.sl_ton_kho_he_thong <= 0 THEN 'HET_HANG'
                    WHEN tk.sl_ton_kho_he_thong < tk.nguong_canh_bao THEN 'SAP_HET'
                    ELSE 'DU_HANG'
                END AS trang_thai_kho
            FROM TON_KHO tk
            JOIN CHI_NHANH cn ON cn.ma_chi_nhanh = tk.ma_chi_nhanh
            JOIN NGUYEN_LIEU nl ON nl.ma_nl = tk.ma_nl
        ");
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS VW_TON_KHO_TONG_QUAN');

        DB::statement("
            CREATE VIEW VW_TON_KHO_TONG_QUAN AS
            SELECT
                cn.ten_chi_nhanh,
                nl.ten_nl,
                nl.don_vi,
                tk.sl_ton_kho_he_thong,
                tk.sl_ton_kho_thuc_te,
                tk.nguong_canh_bao,
                tk.hao_hut_cost,
                CASE
                    WHEN tk.sl_ton_kho_he_thong = 0 THEN 'HET_HANG'
                    WHEN tk.sl_ton_kho_he_thong < tk.nguong_canh_bao THEN 'SAP_HET'
                    ELSE 'DU_HANG'
                END AS trang_thai_kho
            FROM TON_KHO tk
            JOIN CHI_NHANH cn ON cn.ma_chi_nhanh = tk.ma_chi_nhanh
            JOIN NGUYEN_LIEU nl ON nl.ma_nl = tk.ma_nl
        ");
    }
};
