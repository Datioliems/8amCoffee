<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS TRG_ORDER_TRU_TON_KHO');

        DB::unprepared("
            CREATE TRIGGER TRG_ORDER_TRU_TON_KHO
            AFTER UPDATE ON ORDERS
            FOR EACH ROW
            BEGIN
                IF NEW.trang_thai IN ('da_phuc_vu', 'hoan_thanh')
                   AND OLD.trang_thai NOT IN ('da_phuc_vu', 'hoan_thanh') THEN
                    UPDATE TON_KHO tk
                    JOIN DINH_MUC dm ON dm.ma_nl = tk.ma_nl
                    JOIN CHI_TIET_ORDER cto ON cto.ma_mon = dm.ma_mon
                    SET tk.sl_ton_kho_he_thong = tk.sl_ton_kho_he_thong - (cto.so_luong * dm.so_luong_dung)
                    WHERE cto.ma_order = NEW.ma_order
                      AND tk.ma_chi_nhanh = NEW.ma_chi_nhanh;
                END IF;
            END
        ");
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS TRG_ORDER_TRU_TON_KHO');

        DB::unprepared("
            CREATE TRIGGER TRG_ORDER_TRU_TON_KHO
            AFTER UPDATE ON ORDERS
            FOR EACH ROW
            BEGIN
                IF NEW.trang_thai = 'da_xac_nhan' AND OLD.trang_thai <> 'da_xac_nhan' THEN
                    UPDATE TON_KHO tk
                    JOIN DINH_MUC dm ON dm.ma_nl = tk.ma_nl
                    JOIN CHI_TIET_ORDER cto ON cto.ma_mon = dm.ma_mon
                    SET tk.sl_ton_kho_he_thong = tk.sl_ton_kho_he_thong - (cto.so_luong * dm.so_luong_dung)
                    WHERE cto.ma_order = NEW.ma_order
                      AND tk.ma_chi_nhanh = NEW.ma_chi_nhanh;
                END IF;
            END
        ");
    }
};
