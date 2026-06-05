<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChiTietKiemKe extends Model
{
    protected $table = 'CHI_TIET_KIEM_KE';
    public $timestamps = false;

    protected $fillable = ['ma_pkk','ma_nl','sl_he_thong','sl_thuc_te','don_gia_tb'];

    public function nguyenLieu() { return $this->belongsTo(NguyenLieu::class, 'ma_nl', 'ma_nl'); }
}
