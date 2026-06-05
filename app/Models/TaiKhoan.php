<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaiKhoan extends Model
{
    protected $table      = 'TAI_KHOAN';
    protected $primaryKey = 'ma_tai_khoan';
    public $incrementing  = false;
    protected $keyType    = 'string';
    public $timestamps    = false;

    protected $fillable = [
        'ma_tai_khoan','ten_tk','mat_khau','chuc_vu','trang_thai','ma_nv',
        'dang_nhap_sai','khoa_den','xac_thuc_2_lop',
        'otp_ma','otp_het_han','otp_sai',
        'lan_dang_nhap_cuoi','ip_dang_nhap_cuoi',
        'email_xac_thuc_luc','kich_hoat_token','kich_hoat_het_han','tao_luc',
    ];

    protected $hidden = ['mat_khau','otp_ma','kich_hoat_token'];

    protected $casts = [
        'khoa_den'           => 'datetime',
        'otp_het_han'        => 'datetime',
        'lan_dang_nhap_cuoi' => 'datetime',
        'xac_thuc_2_lop'     => 'boolean',
        'email_xac_thuc_luc' => 'datetime',
        'kich_hoat_het_han'  => 'datetime',
        'tao_luc'            => 'datetime',
    ];

    public function nhanVien() { return $this->belongsTo(NhanVien::class, 'ma_nv', 'ma_nv'); }

    /** Tài khoản có đang bị khoá tạm thời do đăng nhập sai nhiều lần không? */
    public function dangBiKhoa(): bool
    {
        return $this->khoa_den !== null && $this->khoa_den->isFuture();
    }
}
