<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Nhật ký email hệ thống đã gửi (credentials / kích hoạt / OTP).
 */
class EmailLog extends Model
{
    protected $table      = 'EMAIL_LOG';
    protected $primaryKey = 'id';
    public $timestamps    = false;

    protected $fillable = [
        'loai', 'email', 'tieu_de', 'ma_tham_chieu', 'trang_thai', 'loi', 'thoi_gian',
    ];

    protected $casts = ['thoi_gian' => 'datetime'];

    /** Ghi 1 dòng log gửi email (không bao giờ làm vỡ luồng chính). */
    public static function ghi(string $loai, string $email, bool $thanhCong, ?string $tieuDe = null, ?string $maThamChieu = null, ?string $loi = null): void
    {
        try {
            static::create([
                'loai'          => $loai,
                'email'         => mb_substr($email, 0, 150),
                'tieu_de'       => $tieuDe ? mb_substr($tieuDe, 0, 200) : null,
                'ma_tham_chieu' => $maThamChieu,
                'trang_thai'    => $thanhCong ? 'thanh_cong' : 'that_bai',
                'loi'           => $loi ? mb_substr($loi, 0, 500) : null,
                'thoi_gian'     => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
