<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DanhMuc extends Model
{
    protected $table      = 'DANH_MUC';
    protected $primaryKey = 'ma_danh_muc';
    public $incrementing  = false;
    protected $keyType    = 'string';
    public $timestamps    = false;

    protected $fillable = ['ma_danh_muc','ten_danh_muc'];

    public function mons() { return $this->hasMany(Mon::class, 'ma_danh_muc', 'ma_danh_muc'); }
}
