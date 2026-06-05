<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Bỏ FK composite từ CHI_TIET_ORDER_OPTION -> CHI_TIET_ORDER (dò tên thực tế)
        $this->dropFksReferencing('CHI_TIET_ORDER_OPTION', 'CHI_TIET_ORDER');

        // 2. Đổi PK của CHI_TIET_ORDER sang cột id tự tăng (chỉ khi chưa có)
        if (! Schema::hasColumn('CHI_TIET_ORDER', 'id')) {
            DB::unprepared('
                ALTER TABLE `CHI_TIET_ORDER`
                    DROP PRIMARY KEY,
                    ADD COLUMN `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT FIRST,
                    ADD PRIMARY KEY (`id`),
                    ADD INDEX `idx_cto_order_mon` (`ma_order`, `ma_mon`)
            ');
        }

        // 3. Thêm chi_tiet_id vào CHI_TIET_ORDER_OPTION (chỉ khi chưa có)
        if (! Schema::hasColumn('CHI_TIET_ORDER_OPTION', 'chi_tiet_id')) {
            DB::unprepared('ALTER TABLE `CHI_TIET_ORDER_OPTION` ADD COLUMN `chi_tiet_id` BIGINT UNSIGNED NULL AFTER `id`');
        }

        // 4. Map dữ liệu cũ
        DB::unprepared('
            UPDATE `CHI_TIET_ORDER_OPTION` opt
            JOIN `CHI_TIET_ORDER` cto
                ON cto.ma_order = opt.ma_order AND cto.ma_mon = opt.ma_mon
            SET opt.chi_tiet_id = cto.id
            WHERE opt.chi_tiet_id IS NULL
        ');

        // 5. NOT NULL + FK mới trên id (chỉ thêm FK khi chưa có)
        DB::unprepared('ALTER TABLE `CHI_TIET_ORDER_OPTION` MODIFY COLUMN `chi_tiet_id` BIGINT UNSIGNED NOT NULL');

        if (! $this->fkExists('CHI_TIET_ORDER_OPTION', 'chi_tiet_order_option_chi_tiet_id_foreign')) {
            DB::unprepared('
                ALTER TABLE `CHI_TIET_ORDER_OPTION`
                    ADD CONSTRAINT `chi_tiet_order_option_chi_tiet_id_foreign`
                    FOREIGN KEY (`chi_tiet_id`) REFERENCES `CHI_TIET_ORDER`(`id`)
                    ON UPDATE CASCADE ON DELETE CASCADE
            ');
        }
    }

    public function down(): void
    {
        $this->dropFksReferencing('CHI_TIET_ORDER_OPTION', 'CHI_TIET_ORDER');

        if (Schema::hasColumn('CHI_TIET_ORDER_OPTION', 'chi_tiet_id')) {
            DB::unprepared('ALTER TABLE `CHI_TIET_ORDER_OPTION` DROP COLUMN `chi_tiet_id`');
        }

        if (Schema::hasColumn('CHI_TIET_ORDER', 'id')) {
            DB::unprepared('ALTER TABLE `CHI_TIET_ORDER` DROP INDEX `idx_cto_order_mon`');
            DB::unprepared('ALTER TABLE `CHI_TIET_ORDER` DROP PRIMARY KEY, DROP COLUMN `id`, ADD PRIMARY KEY (`ma_order`, `ma_mon`)');
        }

        if (! $this->fkExists('CHI_TIET_ORDER_OPTION', 'fk_ctoo_chi_tiet_order')) {
            DB::unprepared('
                ALTER TABLE `CHI_TIET_ORDER_OPTION`
                    ADD CONSTRAINT `fk_ctoo_chi_tiet_order`
                    FOREIGN KEY (`ma_order`, `ma_mon`) REFERENCES `CHI_TIET_ORDER`(`ma_order`, `ma_mon`)
                    ON UPDATE CASCADE ON DELETE CASCADE
            ');
        }
    }

    /** Xoá mọi FK của $table tham chiếu tới $refTable (bất kể tên). */
    private function dropFksReferencing(string $table, string $refTable): void
    {
        $db = DB::getDatabaseName();
        $fks = DB::select(
            'SELECT DISTINCT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND REFERENCED_TABLE_NAME = ?',
            [$db, $table, $refTable]
        );
        foreach ($fks as $fk) {
            DB::unprepared('ALTER TABLE `'.$table.'` DROP FOREIGN KEY `'.$fk->CONSTRAINT_NAME.'`');
        }
    }

    private function fkExists(string $table, string $name): bool
    {
        $db = DB::getDatabaseName();
        return ! empty(DB::select(
            'SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?',
            [$db, $table, $name]
        ));
    }
};
