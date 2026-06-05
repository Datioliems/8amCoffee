<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChiTietOrder extends Model
{
    protected $table      = 'CHI_TIET_ORDER';
    protected $primaryKey = 'id';
    public $incrementing  = true;
    public $timestamps    = false;

    protected $fillable = ['ma_order','ma_mon','so_luong','don_gia_tai_thoi_diem','ghi_chu'];

    public function mon() { return $this->belongsTo(Mon::class, 'ma_mon', 'ma_mon'); }
    public function options()
    {
        return $this->hasMany(ChiTietOrderOption::class, 'chi_tiet_id', 'id');
    }
}
