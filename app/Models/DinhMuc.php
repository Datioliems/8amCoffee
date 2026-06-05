<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DinhMuc extends Model
{
    protected $table = 'DINH_MUC';
    public $timestamps = false;
    protected $fillable = ['ma_mon','ma_nl','so_luong_dung','mo_ta'];

    public function mon()        { return $this->belongsTo(Mon::class, 'ma_mon', 'ma_mon'); }
    public function nguyenLieu() { return $this->belongsTo(NguyenLieu::class, 'ma_nl', 'ma_nl'); }
}
