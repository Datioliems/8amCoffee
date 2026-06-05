<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('CHI_NHANH', function (Blueprint $table) {
            $table->string('ma_chi_nhanh', 10)->primary();
            $table->string('ten_chi_nhanh', 100)->unique();
            $table->string('dia_chi', 255)->nullable();
            $table->string('sdt', 15)->nullable();
        });

        Schema::create('NHAN_VIEN', function (Blueprint $table) {
            $table->string('ma_nv', 10)->primary();
            $table->string('ten_nv', 100);
            $table->string('sdt', 15)->nullable();
            $table->string('cccd', 12)->nullable()->unique();
            $table->string('dia_chi', 255)->nullable();
            $table->string('ma_chi_nhanh', 10);

            $table->foreign('ma_chi_nhanh')->references('ma_chi_nhanh')->on('CHI_NHANH')->cascadeOnUpdate();
        });

        Schema::create('TAI_KHOAN', function (Blueprint $table) {
            $table->string('ma_tai_khoan', 10)->primary();
            $table->string('ten_tk', 50)->unique();
            $table->string('mat_khau', 255);
            $table->string('chuc_vu', 20);
            $table->string('trang_thai', 10)->default('active');
            $table->string('ma_nv', 10);

            $table->foreign('ma_nv')->references('ma_nv')->on('NHAN_VIEN')->cascadeOnUpdate()->cascadeOnDelete();
        });

        Schema::create('BAN', function (Blueprint $table) {
            $table->string('ma_ban', 10)->primary();
            $table->unsignedInteger('so_ban');
            $table->string('vi_tri', 50)->nullable();
            $table->string('trang_thai', 10)->default('trong');
            $table->string('ma_chi_nhanh', 10);
            $table->unique(['so_ban', 'ma_chi_nhanh']);

            $table->foreign('ma_chi_nhanh')->references('ma_chi_nhanh')->on('CHI_NHANH')->cascadeOnUpdate()->cascadeOnDelete();
        });

        Schema::create('KHACH_HANG', function (Blueprint $table) {
            $table->string('ma_kh', 10)->primary();
            $table->string('ten_kh', 100);
            $table->string('sdt', 15)->nullable();
            $table->dateTime('ngay_tao')->useCurrent();
        });

        Schema::create('DANH_MUC', function (Blueprint $table) {
            $table->string('ma_danh_muc', 10)->primary();
            $table->string('ten_danh_muc', 100)->unique();
        });

        Schema::create('MON', function (Blueprint $table) {
            $table->string('ma_mon', 10)->primary();
            $table->string('ten_mon', 100);
            $table->decimal('don_gia', 12, 0);
            $table->string('mo_ta', 500)->nullable();
            $table->string('hinh_anh', 255)->nullable();
            $table->string('ma_danh_muc', 10);
            $table->string('trang_thai', 10)->default('active');

            $table->foreign('ma_danh_muc')->references('ma_danh_muc')->on('DANH_MUC')->cascadeOnUpdate();
        });

        Schema::create('NGUYEN_LIEU', function (Blueprint $table) {
            $table->string('ma_nl', 10)->primary();
            $table->string('ten_nl', 100)->unique();
            $table->string('don_vi', 20);
        });

        Schema::create('DINH_MUC', function (Blueprint $table) {
            $table->string('ma_mon', 10);
            $table->string('ma_nl', 10);
            $table->decimal('so_luong_dung', 10, 2);
            $table->string('mo_ta', 200)->nullable();
            $table->primary(['ma_mon', 'ma_nl']);

            $table->foreign('ma_mon')->references('ma_mon')->on('MON')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('ma_nl')->references('ma_nl')->on('NGUYEN_LIEU')->cascadeOnUpdate();
        });

        Schema::create('ORDERS', function (Blueprint $table) {
            $table->string('ma_order', 20)->primary();
            $table->string('ma_ban', 10)->nullable();
            $table->string('ma_kh', 10)->nullable();
            $table->string('ma_chi_nhanh', 10);
            $table->string('trang_thai', 15)->default('cho_xac_nhan');
            $table->date('ngay_order')->useCurrent();
            $table->time('gio_order')->useCurrent();
            $table->string('ghi_chu', 300)->nullable();
            $table->index(['trang_thai', 'ma_chi_nhanh', 'ngay_order']);
            $table->index(['ma_ban', 'ngay_order']);

            $table->foreign('ma_ban')->references('ma_ban')->on('BAN')->nullOnDelete();
            $table->foreign('ma_kh')->references('ma_kh')->on('KHACH_HANG')->nullOnDelete();
            $table->foreign('ma_chi_nhanh')->references('ma_chi_nhanh')->on('CHI_NHANH');
        });

        Schema::create('CHI_TIET_ORDER', function (Blueprint $table) {
            $table->string('ma_order', 20);
            $table->string('ma_mon', 10);
            $table->unsignedInteger('so_luong');
            $table->decimal('don_gia_tai_thoi_diem', 12, 0);
            $table->string('ghi_chu', 200)->nullable();
            $table->primary(['ma_order', 'ma_mon']);

            $table->foreign('ma_order')->references('ma_order')->on('ORDERS')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('ma_mon')->references('ma_mon')->on('MON');
        });

        Schema::create('HOA_DON', function (Blueprint $table) {
            $table->string('ma_hoa_don', 20)->primary();
            $table->string('ma_order', 20)->unique();
            $table->string('ma_kh', 10)->nullable();
            $table->dateTime('thoi_gian_lap')->useCurrent();
            $table->decimal('tong_tien_truoc_ck', 12, 0)->default(0);
            $table->decimal('chiet_khau', 5, 2)->default(0);
            $table->decimal('tong_tien_sau_ck', 12, 0)->default(0);
            $table->string('phuong_thuc_tt', 20)->default('tien_mat');
            $table->string('trang_thai', 15)->default('cho_thanh_toan');
            $table->string('ma_nv_thu_ngan', 10)->nullable();
            $table->index(['thoi_gian_lap', 'trang_thai']);

            $table->foreign('ma_order')->references('ma_order')->on('ORDERS');
            $table->foreign('ma_kh')->references('ma_kh')->on('KHACH_HANG')->nullOnDelete();
            $table->foreign('ma_nv_thu_ngan')->references('ma_nv')->on('NHAN_VIEN')->nullOnDelete();
        });

        Schema::create('TON_KHO', function (Blueprint $table) {
            $table->string('ma_chi_nhanh', 10);
            $table->string('ma_nl', 10);
            $table->decimal('sl_ton_kho_he_thong', 12, 2)->default(0);
            $table->decimal('sl_ton_kho_thuc_te', 12, 2)->default(0);
            $table->decimal('nguong_canh_bao', 12, 2)->default(0);
            $table->decimal('hao_hut_cost', 12, 0)->default(0);
            $table->primary(['ma_chi_nhanh', 'ma_nl']);
            $table->index(['ma_chi_nhanh', 'sl_ton_kho_he_thong', 'nguong_canh_bao']);

            $table->foreign('ma_chi_nhanh')->references('ma_chi_nhanh')->on('CHI_NHANH')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('ma_nl')->references('ma_nl')->on('NGUYEN_LIEU')->cascadeOnUpdate();
        });

        Schema::create('NHA_CUNG_CAP', function (Blueprint $table) {
            $table->string('ma_ncc', 10)->primary();
            $table->string('ten_ncc', 100)->unique();
            $table->string('dia_chi', 255)->nullable();
            $table->string('sdt', 15)->nullable();
            $table->string('email', 100)->nullable();
        });

        Schema::create('PHIEU_NHAP_KHO', function (Blueprint $table) {
            $table->string('ma_pnk', 20)->primary();
            $table->date('ngay_nk')->useCurrent();
            $table->string('ma_ncc', 10);
            $table->string('ma_nv', 10);
            $table->string('ma_chi_nhanh', 10);
            $table->decimal('tong_gia_tri', 15, 0)->default(0);
            $table->string('trang_thai', 10)->default('cho_duyet');
            $table->string('ghi_chu', 300)->nullable();
            $table->index(['ma_chi_nhanh', 'ngay_nk']);

            $table->foreign('ma_ncc')->references('ma_ncc')->on('NHA_CUNG_CAP');
            $table->foreign('ma_nv')->references('ma_nv')->on('NHAN_VIEN');
            $table->foreign('ma_chi_nhanh')->references('ma_chi_nhanh')->on('CHI_NHANH');
        });

        Schema::create('CHI_TIET_NHAP_KHO', function (Blueprint $table) {
            $table->string('ma_pnk', 20);
            $table->string('ma_nl', 10);
            $table->decimal('so_luong', 12, 2);
            $table->decimal('don_gia', 12, 0);
            $table->decimal('tong_tien', 15, 0)->storedAs('so_luong * don_gia');
            $table->primary(['ma_pnk', 'ma_nl']);

            $table->foreign('ma_pnk')->references('ma_pnk')->on('PHIEU_NHAP_KHO')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('ma_nl')->references('ma_nl')->on('NGUYEN_LIEU');
        });

        Schema::create('PHIEU_KIEM_KE', function (Blueprint $table) {
            $table->string('ma_pkk', 20)->primary();
            $table->date('ngay_kk')->useCurrent();
            $table->string('ma_chi_nhanh', 10);
            $table->string('ma_nv', 10);
            $table->string('trang_thai', 15)->default('nhap');
            $table->string('ghi_chu', 300)->nullable();

            $table->foreign('ma_chi_nhanh')->references('ma_chi_nhanh')->on('CHI_NHANH');
            $table->foreign('ma_nv')->references('ma_nv')->on('NHAN_VIEN');
        });

        Schema::create('CHI_TIET_KIEM_KE', function (Blueprint $table) {
            $table->string('ma_pkk', 20);
            $table->string('ma_nl', 10);
            $table->decimal('sl_he_thong', 12, 2)->default(0);
            $table->decimal('sl_thuc_te', 12, 2);
            $table->decimal('chenh_lech', 12, 2)->storedAs('sl_thuc_te - sl_he_thong');
            $table->decimal('don_gia_tb', 12, 0)->nullable();
            $table->primary(['ma_pkk', 'ma_nl']);

            $table->foreign('ma_pkk')->references('ma_pkk')->on('PHIEU_KIEM_KE')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('ma_nl')->references('ma_nl')->on('NGUYEN_LIEU');
        });

        $this->createTriggers();
        $this->createViews();
    }

    public function down(): void
    {
        foreach ([
            'VW_TON_KHO_TONG_QUAN',
            'VW_MENU_HIEN_THI',
            'VW_ORDER_DASHBOARD',
        ] as $view) {
            DB::statement("DROP VIEW IF EXISTS {$view}");
        }

        foreach ([
            'TRG_ORDER_TRU_TON_KHO',
            'TRG_ORDER_CAP_NHAT_TRANG_THAI_BAN',
            'TRG_NHAP_KHO_CAP_NHAT_TON',
            'TRG_NHAP_KHO_TINH_TONG_INSERT',
            'TRG_NHAP_KHO_TINH_TONG_UPDATE',
            'TRG_NHAP_KHO_TINH_TONG_DELETE',
            'TRG_KIEM_KE_CAP_NHAT_TON',
        ] as $trigger) {
            DB::statement("DROP TRIGGER IF EXISTS {$trigger}");
        }

        Schema::dropIfExists('CHI_TIET_KIEM_KE');
        Schema::dropIfExists('PHIEU_KIEM_KE');
        Schema::dropIfExists('CHI_TIET_NHAP_KHO');
        Schema::dropIfExists('PHIEU_NHAP_KHO');
        Schema::dropIfExists('NHA_CUNG_CAP');
        Schema::dropIfExists('TON_KHO');
        Schema::dropIfExists('HOA_DON');
        Schema::dropIfExists('CHI_TIET_ORDER');
        Schema::dropIfExists('ORDERS');
        Schema::dropIfExists('DINH_MUC');
        Schema::dropIfExists('NGUYEN_LIEU');
        Schema::dropIfExists('MON');
        Schema::dropIfExists('DANH_MUC');
        Schema::dropIfExists('KHACH_HANG');
        Schema::dropIfExists('BAN');
        Schema::dropIfExists('TAI_KHOAN');
        Schema::dropIfExists('NHAN_VIEN');
        Schema::dropIfExists('CHI_NHANH');
    }

    private function createTriggers(): void
    {
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

        DB::unprepared("
            CREATE TRIGGER TRG_ORDER_CAP_NHAT_TRANG_THAI_BAN
            AFTER UPDATE ON ORDERS
            FOR EACH ROW
            BEGIN
                IF NEW.trang_thai IN ('cho_xac_nhan', 'da_xac_nhan', 'dang_pha_che', 'da_phuc_vu') THEN
                    UPDATE BAN SET trang_thai = 'co_khach' WHERE ma_ban = NEW.ma_ban;
                END IF;

                IF NEW.trang_thai IN ('hoan_thanh', 'da_huy') THEN
                    IF NOT EXISTS (
                        SELECT 1 FROM ORDERS
                        WHERE ma_ban = NEW.ma_ban
                          AND trang_thai IN ('cho_xac_nhan', 'da_xac_nhan', 'dang_pha_che', 'da_phuc_vu')
                    ) THEN
                        UPDATE BAN SET trang_thai = 'trong' WHERE ma_ban = NEW.ma_ban;
                    END IF;
                END IF;
            END
        ");

        DB::unprepared("
            CREATE TRIGGER TRG_NHAP_KHO_CAP_NHAT_TON
            AFTER UPDATE ON PHIEU_NHAP_KHO
            FOR EACH ROW
            BEGIN
                IF NEW.trang_thai = 'da_duyet' AND OLD.trang_thai <> 'da_duyet' THEN
                    INSERT INTO TON_KHO (ma_chi_nhanh, ma_nl, sl_ton_kho_he_thong, sl_ton_kho_thuc_te, nguong_canh_bao, hao_hut_cost)
                    SELECT NEW.ma_chi_nhanh, ct.ma_nl, ct.so_luong, ct.so_luong, 0, 0
                    FROM CHI_TIET_NHAP_KHO ct
                    WHERE ct.ma_pnk = NEW.ma_pnk
                    ON DUPLICATE KEY UPDATE
                        sl_ton_kho_he_thong = sl_ton_kho_he_thong + VALUES(sl_ton_kho_he_thong),
                        sl_ton_kho_thuc_te = sl_ton_kho_thuc_te + VALUES(sl_ton_kho_thuc_te);
                END IF;
            END
        ");

        foreach (['INSERT' => 'NEW.ma_pnk', 'UPDATE' => 'NEW.ma_pnk', 'DELETE' => 'OLD.ma_pnk'] as $event => $maPnk) {
            DB::unprepared("
                CREATE TRIGGER TRG_NHAP_KHO_TINH_TONG_{$event}
                AFTER {$event} ON CHI_TIET_NHAP_KHO
                FOR EACH ROW
                BEGIN
                    UPDATE PHIEU_NHAP_KHO
                    SET tong_gia_tri = (
                        SELECT COALESCE(SUM(so_luong * don_gia), 0)
                        FROM CHI_TIET_NHAP_KHO
                        WHERE ma_pnk = {$maPnk}
                    )
                    WHERE ma_pnk = {$maPnk};
                END
            ");
        }

        DB::unprepared("
            CREATE TRIGGER TRG_KIEM_KE_CAP_NHAT_TON
            AFTER UPDATE ON PHIEU_KIEM_KE
            FOR EACH ROW
            BEGIN
                IF NEW.trang_thai = 'da_xac_nhan' AND OLD.trang_thai <> 'da_xac_nhan' THEN
                    UPDATE TON_KHO tk
                    JOIN CHI_TIET_KIEM_KE ct ON ct.ma_nl = tk.ma_nl
                    SET tk.sl_ton_kho_thuc_te = ct.sl_thuc_te,
                        tk.sl_ton_kho_he_thong = ct.sl_thuc_te,
                        tk.hao_hut_cost = CASE
                            WHEN ct.sl_thuc_te < ct.sl_he_thong
                            THEN (ct.sl_he_thong - ct.sl_thuc_te) * COALESCE(ct.don_gia_tb, 0)
                            ELSE tk.hao_hut_cost
                        END
                    WHERE ct.ma_pkk = NEW.ma_pkk
                      AND tk.ma_chi_nhanh = NEW.ma_chi_nhanh;
                END IF;
            END
        ");
    }

    private function createViews(): void
    {
        DB::statement("
            CREATE VIEW VW_ORDER_DASHBOARD AS
            SELECT
                o.ma_order,
                b.so_ban,
                cn.ten_chi_nhanh,
                k.ten_kh,
                o.trang_thai,
                o.ngay_order,
                o.gio_order,
                COALESCE(SUM(cto.so_luong * cto.don_gia_tai_thoi_diem), 0) AS tong_tien
            FROM ORDERS o
            JOIN BAN b ON b.ma_ban = o.ma_ban
            JOIN CHI_NHANH cn ON cn.ma_chi_nhanh = o.ma_chi_nhanh
            LEFT JOIN KHACH_HANG k ON k.ma_kh = o.ma_kh
            LEFT JOIN CHI_TIET_ORDER cto ON cto.ma_order = o.ma_order
            WHERE o.trang_thai IN ('cho_xac_nhan', 'da_xac_nhan', 'dang_pha_che')
            GROUP BY o.ma_order, b.so_ban, cn.ten_chi_nhanh, k.ten_kh, o.trang_thai, o.ngay_order, o.gio_order
        ");

        DB::statement("
            CREATE VIEW VW_MENU_HIEN_THI AS
            SELECT m.ma_mon, m.ten_mon, m.don_gia, m.mo_ta, m.hinh_anh, m.trang_thai, dm.ten_danh_muc
            FROM MON m
            JOIN DANH_MUC dm ON dm.ma_danh_muc = m.ma_danh_muc
            WHERE m.trang_thai = 'active'
        ");

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
};
