<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KhachHang extends Model
{
    protected $table      = 'KHACH_HANG';
    protected $primaryKey = 'ma_kh';
    public $incrementing  = false;
    protected $keyType    = 'string';
    public $timestamps    = false;

    protected $fillable = ['ma_kh','ten_kh','sdt','sdt_hash','ngay_tao'];

    // PII mã hóa khi lưu, tự giải mã khi đọc.
    protected $casts = [
        'ten_kh' => \App\Casts\EncryptedString::class,
        'sdt'    => \App\Casts\EncryptedString::class,
    ];

    /** Tự sinh blind index sdt_hash mỗi khi lưu (để tra cứu theo SĐT). */
    protected static function booted(): void
    {
        static::saving(function (self $m) {
            $m->sdt_hash = \App\Support\Pii::phoneHash($m->sdt);
        });
    }
}
