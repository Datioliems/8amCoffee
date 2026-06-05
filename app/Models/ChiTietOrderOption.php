<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChiTietOrderOption extends Model
{
    protected $table = 'CHI_TIET_ORDER_OPTION';
    public $timestamps = false;

    protected $fillable = [
        'chi_tiet_id',
        'ma_order',
        'ma_mon',
        'loai_option',
        'ten_lua_chon',
        'gia_them',
    ];
}
