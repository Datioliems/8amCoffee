<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Sửa FK constraint ma_nv → NHAN_VIEN trong PHIEU_NHAP_KHO và PHIEU_KIEM_KE:
 *  - Chuyển từ RESTRICT (default) → SET NULL khi xóa nhân viên.
 *  - Cho phép cột nullable để MySQL chấp nhận SET NULL.
 * Mục đích: khi xóa tài khoản nhân viên, phiếu của họ vẫn giữ nguyên
 *           (ma_nv = NULL) thay vì bị MySQL chặn hoàn toàn.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['PHIEU_NHAP_KHO', 'PHIEU_KIEM_KE'] as $table) {
            // 1. Tìm tên constraint hiện tại (tránh hard-code tên sai)
            $fks = DB::select("
                SELECT CONSTRAINT_NAME
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = ?
                  AND COLUMN_NAME = 'ma_nv'
                  AND REFERENCED_TABLE_NAME = 'NHAN_VIEN'
            ", [$table]);

            foreach ($fks as $fk) {
                DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
            }

            // 2. Cho phép NULL
            DB::statement("ALTER TABLE `{$table}` MODIFY COLUMN `ma_nv` VARCHAR(10) NULL");

            // 3. Thêm lại FK với SET NULL
            $constraintName = strtolower($table) . '_ma_nv_foreign';
            DB::statement("
                ALTER TABLE `{$table}`
                ADD CONSTRAINT `{$constraintName}`
                FOREIGN KEY (`ma_nv`) REFERENCES `NHAN_VIEN`(`ma_nv`)
                ON DELETE SET NULL ON UPDATE CASCADE
            ");
        }
    }

    public function down(): void
    {
        foreach (['PHIEU_NHAP_KHO', 'PHIEU_KIEM_KE'] as $table) {
            $constraintName = strtolower($table) . '_ma_nv_foreign';

            $fks = DB::select("
                SELECT CONSTRAINT_NAME
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = ?
                  AND COLUMN_NAME = 'ma_nv'
                  AND REFERENCED_TABLE_NAME = 'NHAN_VIEN'
            ", [$table]);

            foreach ($fks as $fk) {
                DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
            }

            DB::statement("ALTER TABLE `{$table}` MODIFY COLUMN `ma_nv` VARCHAR(10) NOT NULL");

            DB::statement("
                ALTER TABLE `{$table}`
                ADD CONSTRAINT `{$constraintName}`
                FOREIGN KEY (`ma_nv`) REFERENCES `NHAN_VIEN`(`ma_nv`)
            ");
        }
    }
};
