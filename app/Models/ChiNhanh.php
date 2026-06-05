<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChiNhanh extends Model
{
    protected $table      = 'CHI_NHANH';
    protected $primaryKey = 'ma_chi_nhanh';
    public $incrementing  = false;
    protected $keyType    = 'string';
    public $timestamps    = false;

    protected $fillable = ['ma_chi_nhanh','ten_chi_nhanh','dia_chi','sdt','model_3d'];

    public function bans() { return $this->hasMany(Ban::class, 'ma_chi_nhanh', 'ma_chi_nhanh'); }
}
