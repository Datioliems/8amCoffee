<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ThanhToanOnline extends Model
{
    protected $table      = 'THANH_TOAN_ONLINE';
    protected $primaryKey = 'id';
    public $timestamps    = false;

    protected $fillable = [
        'ma_giao_dich','ma_order','cong','so_tien','chiet_khau','trang_thai',
        'ma_gd_cong','ma_phan_hoi','ma_nv','ma_hoa_don','du_lieu',
        'thoi_gian_tao','thoi_gian_cap_nhat',
    ];

    protected $casts = [
        'du_lieu'            => 'array',
        'so_tien'            => 'decimal:0',
        'chiet_khau'         => 'decimal:2',
        'thoi_gian_tao'      => 'datetime',
        'thoi_gian_cap_nhat' => 'datetime',
    ];

    public function order() { return $this->belongsTo(Order::class, 'ma_order', 'ma_order'); }
}
