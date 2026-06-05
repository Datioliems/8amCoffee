<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Nhật ký đăng nhập (audit log) — phục vụ an toàn thông tin:
 * truy vết ai đăng nhập, từ IP nào, thành công/thất bại, OTP…
 */
class NhatKyDangNhap extends Model
{
    protected $table      = 'NHAT_KY_DANG_NHAP';
    protected $primaryKey = 'id';
    public $timestamps    = false;

    protected $fillable = [
        'ten_tk','ma_tai_khoan','ma_nv','hanh_dong',
        'thanh_cong','dia_chi_ip','user_agent','chi_tiet','thoi_gian',
    ];

    protected $casts = [
        'thanh_cong' => 'boolean',
        'thoi_gian'  => 'datetime',
    ];

    /** Ghi nhanh một dòng nhật ký. */
    public static function ghi(string $hanhDong, array $data = []): void
    {
        try {
            static::create(array_merge([
                'hanh_dong'  => $hanhDong,
                'thanh_cong' => false,
                'thoi_gian'  => now(),
            ], $data));
        } catch (\Throwable $e) {
            // Không để việc ghi log làm hỏng luồng đăng nhập.
            report($e);
        }
    }
}
