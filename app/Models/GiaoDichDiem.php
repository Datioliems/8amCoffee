<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Sổ cái điểm thưởng — mỗi dòng là một biến động điểm.
 * Số dư của thẻ = SUM(so_diem) các giao dịch của thẻ đó.
 */
class GiaoDichDiem extends Model
{
    protected $table      = 'GIAO_DICH_DIEM';
    protected $primaryKey = 'id';
    public $timestamps    = false;

    protected $fillable = [
        'ma_the', 'ma_kh', 'loai', 'so_diem', 'so_diem_sau', 'so_tien_lien_quan',
        'ma_order', 'ma_hoa_don', 'ma_thiet_bi', 'mo_ta', 'thoi_gian',
    ];

    protected $casts = [
        'so_diem'           => 'integer',
        'so_diem_sau'       => 'integer',
        'so_tien_lien_quan' => 'integer',
        'thoi_gian'         => 'datetime',
    ];

    public function the()
    {
        return $this->belongsTo(TheThanhVien::class, 'ma_the', 'ma_the');
    }
}
