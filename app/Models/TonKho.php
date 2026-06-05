<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TonKho extends Model
{
    protected $table     = 'TON_KHO';
    public $incrementing = false;
    public $timestamps   = false;

    protected $primaryKey = null;

    protected $fillable = [
        'ma_chi_nhanh','ma_nl',
        'sl_ton_kho_he_thong','sl_ton_kho_thuc_te',
        'nguong_canh_bao','hao_hut_cost',
    ];

    public function nguyenLieu() { return $this->belongsTo(NguyenLieu::class, 'ma_nl', 'ma_nl'); }
    public function chiNhanh()   { return $this->belongsTo(ChiNhanh::class, 'ma_chi_nhanh', 'ma_chi_nhanh'); }
}
