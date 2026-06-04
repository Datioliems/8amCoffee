<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Sửa lỗi 1364 "Field doesn't have a default value".
 *
 * Nguyên nhân: các cột kiểu DATE/TIME được tạo bằng ->useCurrent() →
 * sinh DEFAULT CURRENT_TIMESTAMP, nhưng MySQL KHÔNG cho phép CURRENT_TIMESTAMP
 * làm default cho DATE/TIME (chỉ DATETIME/TIMESTAMP mới được). Trên DB bật
 * strict mode (mặc định của MySQL trên Railway), cột không có default →
 * insert thiếu cột đó báo lỗi.
 *
 * Cách sửa: đặt default bằng BIỂU THỨC (CURDATE()/CURTIME()) + nới NULL để chắc
 * chắn không còn 1364. Tương thích MySQL 8.0.13+ và MariaDB 10.2.7+.
 * Nếu engine từ chối default biểu thức → fallback chỉ nới NULL.
 */
return new class extends Migration
{
    /** [bảng, cột, kiểu, biểu_thức_default] */
    private array $cols = [
        ['ORDERS',         'ngay_order', 'DATE', '(CURDATE())'],
        ['ORDERS',         'gio_order',  'TIME', '(CURTIME())'],
        ['PHIEU_NHAP_KHO', 'ngay_nk',    'DATE', '(CURDATE())'],
        ['PHIEU_KIEM_KE',  'ngay_kk',    'DATE', '(CURDATE())'],
    ];

    public function up(): void
    {
        foreach ($this->cols as [$bang, $cot, $kieu, $default]) {
            try {
                DB::statement("ALTER TABLE `{$bang}` MODIFY `{$cot}` {$kieu} NULL DEFAULT {$default}");
            } catch (\Throwable $e) {
                // Engine cũ không hỗ trợ default biểu thức → ít nhất nới NULL để hết lỗi 1364.
                try {
                    DB::statement("ALTER TABLE `{$bang}` MODIFY `{$cot}` {$kieu} NULL");
                } catch (\Throwable $e2) {
                    // bỏ qua nếu bảng/cột không tồn tại ở môi trường nào đó
                }
            }
        }
    }

    public function down(): void
    {
        // Forward-only: không khôi phục trạng thái lỗi cũ (NOT NULL không default).
    }
};
