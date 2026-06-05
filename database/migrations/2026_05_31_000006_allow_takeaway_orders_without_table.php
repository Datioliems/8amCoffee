<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->dropOrdersBanFk();
        DB::statement('ALTER TABLE ORDERS MODIFY ma_ban VARCHAR(10) NULL');

        if (! $this->fkExists('fk_orders_ban')) {
            DB::statement('ALTER TABLE ORDERS ADD CONSTRAINT fk_orders_ban FOREIGN KEY (ma_ban) REFERENCES BAN(ma_ban) ON DELETE SET NULL');
        }
    }

    public function down(): void
    {
        $this->dropOrdersBanFk();
        DB::statement('ALTER TABLE ORDERS MODIFY ma_ban VARCHAR(10) NOT NULL');

        if (! $this->fkExists('fk_orders_ban')) {
            DB::statement('ALTER TABLE ORDERS ADD CONSTRAINT fk_orders_ban FOREIGN KEY (ma_ban) REFERENCES BAN(ma_ban)');
        }
    }

    /** Xoá MỌI foreign key trên ORDERS.ma_ban -> BAN (bất kể tên thực tế là gì). */
    private function dropOrdersBanFk(): void
    {
        $db = DB::getDatabaseName();
        $fks = DB::select(
            'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = "ORDERS"
               AND COLUMN_NAME = "ma_ban" AND REFERENCED_TABLE_NAME = "BAN"',
            [$db]
        );
        foreach ($fks as $fk) {
            DB::statement('ALTER TABLE ORDERS DROP FOREIGN KEY `'.$fk->CONSTRAINT_NAME.'`');
        }
    }

    private function fkExists(string $name): bool
    {
        $db = DB::getDatabaseName();
        return ! empty(DB::select(
            'SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = "ORDERS" AND CONSTRAINT_NAME = ?',
            [$db, $name]
        ));
    }
};
