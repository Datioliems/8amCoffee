<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ORDERS', function (Blueprint $table) {
            if (! Schema::hasColumn('ORDERS', 'ten_khach')) {
                $table->string('ten_khach', 100)->nullable()->after('ma_kh');
            }
            if (! Schema::hasColumn('ORDERS', 'sdt_khach')) {
                $table->string('sdt_khach', 20)->nullable()->after('ten_khach');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ORDERS', function (Blueprint $table) {
            foreach (['ten_khach', 'sdt_khach'] as $col) {
                if (Schema::hasColumn('ORDERS', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
