<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ORDERS', function (Blueprint $table) {
            if (! Schema::hasColumn('ORDERS', 'thoi_gian_xac_nhan')) {
                $table->dateTime('thoi_gian_xac_nhan')->nullable()->after('gio_order');
            }
            if (! Schema::hasColumn('ORDERS', 'thoi_gian_phuc_vu')) {
                $table->dateTime('thoi_gian_phuc_vu')->nullable()->after('thoi_gian_xac_nhan');
            }
            if (! Schema::hasColumn('ORDERS', 'thoi_gian_thanh_toan')) {
                $table->dateTime('thoi_gian_thanh_toan')->nullable()->after('thoi_gian_phuc_vu');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ORDERS', function (Blueprint $table) {
            foreach (['thoi_gian_xac_nhan', 'thoi_gian_phuc_vu', 'thoi_gian_thanh_toan'] as $col) {
                if (Schema::hasColumn('ORDERS', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
