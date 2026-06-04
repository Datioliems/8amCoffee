<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Đầu đọc RFID (Arduino/ESP) đặt tại quầy/chi nhánh.
 */
class ThietBiArduino extends Model
{
    protected $table      = 'THIET_BI_ARDUINO';
    protected $primaryKey = 'ma_thiet_bi';
    public $incrementing  = false;
    protected $keyType    = 'string';
    public $timestamps    = false;

    protected $fillable = [
        'ma_thiet_bi', 'ten_thiet_bi', 'ma_chi_nhanh', 'vi_tri',
        'api_key_hash', 'trang_thai', 'lan_ket_noi_cuoi', 'ip_cuoi', 'tao_luc',
    ];

    protected $hidden = ['api_key_hash'];

    protected $casts = [
        'lan_ket_noi_cuoi' => 'datetime',
        'tao_luc'          => 'datetime',
    ];

    public function chiNhanh()
    {
        return $this->belongsTo(ChiNhanh::class, 'ma_chi_nhanh', 'ma_chi_nhanh');
    }

    /** Băm token thiết bị (so khớp khi Arduino gọi API). */
    public static function hashKey(string $token): string
    {
        return hash('sha256', $token);
    }
}
