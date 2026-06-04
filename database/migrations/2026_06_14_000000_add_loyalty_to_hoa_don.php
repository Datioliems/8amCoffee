<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ghi nhận đổi điểm trên hóa đơn: thẻ dùng, số điểm trừ, số tiền giảm từ điểm.
 * Cột thêm đều có default → không ảnh hưởng luồng hóa đơn cũ.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('HOA_DON', function (Blueprint $table) {
            if (! Schema::hasColumn('HOA_DON', 'ma_the')) {
                $table->string('ma_the', 20)->nullable()->after('ma_kh');
            }
            if (! Schema::hasColumn('HOA_DON', 'diem_su_dung')) {
                $table->integer('diem_su_dung')->default(0)->after('chiet_khau');
            }
            if (! Schema::hasColumn('HOA_DON', 'giam_gia_diem')) {
                $table->decimal('giam_gia_diem', 12, 0)->default(0)->after('diem_su_dung');
            }
        });

        // Khóa ngoại tới thẻ thành viên (nếu bảng đã tồn tại).
        if (Schema::hasTable('THE_THANH_VIEN')) {
            Schema::table('HOA_DON', function (Blueprint $table) {
                $table->foreign('ma_the')->references('ma_the')->on('THE_THANH_VIEN')->nullOnDelete()->cascadeOnUpdate();
            });
        }
    }

    public function down(): void
    {
        Schema::table('HOA_DON', function (Blueprint $table) {
            try { $table->dropForeign(['ma_the']); } catch (\Throwable $e) {}
            foreach (['ma_the', 'diem_su_dung', 'giam_gia_diem'] as $col) {
                if (Schema::hasColumn('HOA_DON', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
