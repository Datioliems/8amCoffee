<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Cấu hình một HẠNG hội viên (sửa được qua web).
 */
class CauHinhHang extends Model
{
    protected $table      = 'CAU_HINH_HANG';
    protected $primaryKey = 'ma_hang';
    public $incrementing  = false;
    protected $keyType    = 'string';
    public $timestamps    = false;

    protected $fillable = ['ma_hang', 'nhan', 'nguong', 'he_so', 'giam_loai', 'giam_gia_tri', 'thu_tu'];

    protected $casts = [
        'nguong'       => 'integer',
        'he_so'        => 'float',
        'giam_gia_tri' => 'float',
        'thu_tu'       => 'integer',
    ];
}
