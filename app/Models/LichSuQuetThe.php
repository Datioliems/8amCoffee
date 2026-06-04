<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Log mọi lần quẹt thẻ từ đầu đọc Arduino (kể cả thẻ lạ / chưa đăng ký).
 */
class LichSuQuetThe extends Model
{
    protected $table      = 'LICH_SU_QUET_THE';
    protected $primaryKey = 'id';
    public $timestamps    = false;

    protected $fillable = [
        'uid_rfid', 'ma_the', 'ma_thiet_bi', 'ma_chi_nhanh',
        'hanh_dong', 'ket_qua', 'chi_tiet', 'ip', 'thoi_gian',
    ];

    protected $casts = ['thoi_gian' => 'datetime'];

    /** Ghi log quẹt thẻ; nuốt lỗi để KHÔNG làm vỡ luồng nghiệp vụ chính. */
    public static function ghi(array $data): void
    {
        try {
            self::create(array_merge(['thoi_gian' => now()], $data));
        } catch (\Throwable $e) {
            // bỏ qua — logging không được phép làm hỏng giao dịch
        }
    }
}
