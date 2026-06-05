<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NhaCungCap extends Model
{
    protected $table      = 'NHA_CUNG_CAP';
    protected $primaryKey = 'ma_ncc';
    public $incrementing  = false;
    protected $keyType    = 'string';
    public $timestamps    = false;

    protected $fillable = ['ma_ncc','ten_ncc','dia_chi','sdt','email'];

    // PII/thông tin liên hệ NCC mã hóa khi lưu, tự giải mã khi đọc.
    protected $casts = [
        'dia_chi' => \App\Casts\EncryptedString::class,
        'sdt'     => \App\Casts\EncryptedString::class,
        'email'   => \App\Casts\EncryptedString::class,
    ];

    public function phieuNhapKhos() { return $this->hasMany(PhieuNhapKho::class, 'ma_ncc', 'ma_ncc'); }
}
