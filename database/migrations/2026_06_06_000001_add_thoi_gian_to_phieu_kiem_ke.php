<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('PHIEU_KIEM_KE', 'thoi_gian_kk')) {
            Schema::table('PHIEU_KIEM_KE', function (Blueprint $table) {
                $table->dateTime('thoi_gian_kk')->nullable()->after('ngay_kk');
            });
            // Suy thời gian từ mã phiếu cũ (PKK + YmdHis) nếu có
            foreach (DB::table('PHIEU_KIEM_KE')->whereNull('thoi_gian_kk')->get() as $pkk) {
                $ts = null;
                if (preg_match('/^PKK(\d{14})$/', $pkk->ma_pkk, $m)) {
                    $ts = substr($m[1],0,4).'-'.substr($m[1],4,2).'-'.substr($m[1],6,2).' '
                        . substr($m[1],8,2).':'.substr($m[1],10,2).':'.substr($m[1],12,2);
                }
                DB::table('PHIEU_KIEM_KE')->where('ma_pkk', $pkk->ma_pkk)
                    ->update(['thoi_gian_kk' => $ts ?: ($pkk->ngay_kk ? $pkk->ngay_kk.' 00:00:00' : null)]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('PHIEU_KIEM_KE', 'thoi_gian_kk')) {
            Schema::table('PHIEU_KIEM_KE', function (Blueprint $table) {
                $table->dropColumn('thoi_gian_kk');
            });
        }
    }
};
