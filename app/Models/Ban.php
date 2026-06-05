<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ban extends Model
{
    protected $table      = 'BAN';
    protected $primaryKey = 'ma_ban';
    public $incrementing  = false;
    protected $keyType    = 'string';
    public $timestamps    = false;

    protected $fillable = ['ma_ban','so_ban','so_ghe','vi_tri','trang_thai','ma_chi_nhanh'];

    public function orders()  { return $this->hasMany(Order::class, 'ma_ban', 'ma_ban'); }
    public function chiNhanh(){ return $this->belongsTo(ChiNhanh::class, 'ma_chi_nhanh', 'ma_chi_nhanh'); }
}
