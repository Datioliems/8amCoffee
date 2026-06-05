<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonOption extends Model
{
    protected $table = 'MON_OPTION';
    protected $primaryKey = 'ma_option';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'ma_option',
        'ma_mon',
        'loai_option',
        'ten_option',
        'gia_them',
        'bat_buoc',
        'thu_tu',
        'trang_thai',
    ];

    public function mon()
    {
        return $this->belongsTo(Mon::class, 'ma_mon', 'ma_mon');
    }
}
