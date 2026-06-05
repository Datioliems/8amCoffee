<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NguyenLieu extends Model
{
    protected $table      = 'NGUYEN_LIEU';
    protected $primaryKey = 'ma_nl';
    public $incrementing  = false;
    protected $keyType    = 'string';
    public $timestamps    = false;

    protected $fillable = ['ma_nl','ten_nl','don_vi'];

    public function dinhMucs()    { return $this->hasMany(DinhMuc::class, 'ma_nl', 'ma_nl'); }
    public function tonKhos()     { return $this->hasMany(TonKho::class, 'ma_nl', 'ma_nl'); }
    public function chiTietNhaps(){ return $this->hasMany(ChiTietNhapKho::class, 'ma_nl', 'ma_nl'); }
}
