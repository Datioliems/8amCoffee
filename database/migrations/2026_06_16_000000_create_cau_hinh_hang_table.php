<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cấu hình HẠNG hội viên trong DB (để sửa qua web, không phải sửa config file).
 * Seed sẵn từ config('loyalty.tiers'). Nếu bảng trống/không có → LoyaltyService
 * tự fallback về config.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('CAU_HINH_HANG')) {
            Schema::create('CAU_HINH_HANG', function (Blueprint $table) {
                $table->string('ma_hang', 20)->primary();          // thuong | bac | vang | kim_cuong
                $table->string('nhan', 50);                        // nhãn hiển thị
                $table->unsignedInteger('nguong')->default(0);     // điểm tối thiểu đạt hạng
                $table->decimal('he_so', 5, 2)->default(1);        // hệ số tích điểm
                $table->string('giam_loai', 20)->default('phan_tram'); // phan_tram | tien
                $table->decimal('giam_gia_tri', 12, 0)->default(0);    // % hoặc đồng
                $table->unsignedInteger('thu_tu')->default(0);
            });
        }

        // Seed từ config nếu bảng đang rỗng.
        if (DB::table('CAU_HINH_HANG')->count() === 0) {
            $i = 0;
            foreach ((array) config('loyalty.tiers', []) as $ma => $c) {
                DB::table('CAU_HINH_HANG')->insert([
                    'ma_hang'      => $ma,
                    'nhan'         => $c['nhan'] ?? $ma,
                    'nguong'       => (int) ($c['nguong'] ?? 0),
                    'he_so'        => (float) ($c['he_so'] ?? 1),
                    'giam_loai'    => $c['giam_loai'] ?? 'phan_tram',
                    'giam_gia_tri' => (float) ($c['giam_gia_tri'] ?? 0),
                    'thu_tu'       => $i++,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('CAU_HINH_HANG');
    }
};
