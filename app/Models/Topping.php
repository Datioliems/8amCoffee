<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Topping extends Model
{
    protected $table = 'TOPPING';
    protected $primaryKey = 'ma_topping';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = ['ma_topping', 'ten_topping', 'gia_them', 'canh_bao', 'trang_thai'];
}
