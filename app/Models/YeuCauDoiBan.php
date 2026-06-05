<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class YeuCauDoiBan extends Model
{
    protected $table      = 'YEU_CAU_DOI_BAN';
    protected $primaryKey = 'id';
    public $timestamps    = false;

    protected $fillable = [
        'ma_order', 'ma_ban_cu', 'ma_ban_moi', 'ma_chi_nhanh',
        'trang_thai', 'ma_nv_xu_ly', 'thoi_gian_tao', 'thoi_gian_xu_ly',
    ];

    protected $casts = [
        'thoi_gian_tao'    => 'datetime',
        'thoi_gian_xu_ly'  => 'datetime',
    ];

    public function order() { return $this->belongsTo(Order::class, 'ma_order', 'ma_order'); }
}
