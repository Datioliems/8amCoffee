<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Thẻ RFID thành viên (tái sử dụng nhiều lần), gắn với một khách hàng.
 * Số dư điểm `diem_hien_tai` là bản denormalize; nguồn sự thật là GIAO_DICH_DIEM.
 */
class TheThanhVien extends Model
{
    protected $table      = 'THE_THANH_VIEN';
    protected $primaryKey = 'ma_the';
    public $incrementing  = false;
    protected $keyType    = 'string';
    public $timestamps    = false;

    protected $fillable = [
        'ma_the', 'uid_rfid', 'ma_kh', 'diem_hien_tai', 'tong_diem_tich_luy',
        'hang_the', 'trang_thai', 'ngay_phat', 'ma_chi_nhanh', 'ghi_chu', 'tao_luc',
    ];

    protected $casts = [
        'diem_hien_tai'      => 'integer',
        'tong_diem_tich_luy' => 'integer',
        'ngay_phat'          => 'date',
        'tao_luc'            => 'datetime',
    ];

    public function khachHang()
    {
        return $this->belongsTo(KhachHang::class, 'ma_kh', 'ma_kh');
    }

    public function giaoDichDiem()
    {
        return $this->hasMany(GiaoDichDiem::class, 'ma_the', 'ma_the');
    }

    public function dangHoatDong(): bool
    {
        return $this->trang_thai === 'hoat_dong';
    }

    /** Quy đổi số dư điểm hiện tại ra giá trị tiền (đ) theo cấu hình. */
    public function giaTriTien(): int
    {
        return (int) $this->diem_hien_tai * (int) config('loyalty.point_value', 50);
    }
}
