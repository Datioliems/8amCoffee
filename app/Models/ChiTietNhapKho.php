<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChiTietNhapKho extends Model
{
    protected $table = 'CHI_TIET_NHAP_KHO';
    public $timestamps = false;

    protected $fillable = ['ma_pnk','ma_nl','so_luong','don_gia'];

    public function nguyenLieu() { return $this->belongsTo(NguyenLieu::class, 'ma_nl', 'ma_nl'); }
}
