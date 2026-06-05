<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HoaDon extends Model
{
    protected $table      = 'HOA_DON';
    protected $primaryKey = 'ma_hoa_don';
    public $incrementing  = false;
    protected $keyType    = 'string';
    public $timestamps    = false;

    protected $fillable = [
        'ma_hoa_don','ma_order','ma_kh','thoi_gian_lap',
        'tong_tien_truoc_ck','chiet_khau','tong_tien_sau_ck',
        'phuong_thuc_tt','trang_thai','ma_nv_thu_ngan',
    ];

    public function order()     { return $this->belongsTo(Order::class, 'ma_order', 'ma_order'); }
    public function khachHang() { return $this->belongsTo(KhachHang::class, 'ma_kh', 'ma_kh'); }
}
