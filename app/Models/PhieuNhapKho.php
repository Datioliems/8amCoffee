<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhieuNhapKho extends Model
{
    protected $table      = 'PHIEU_NHAP_KHO';
    protected $primaryKey = 'ma_pnk';
    public $incrementing  = false;
    protected $keyType    = 'string';
    public $timestamps    = false;

    protected $fillable = ['ma_pnk','ngay_nk','ma_ncc','ma_nv','ma_chi_nhanh','tong_gia_tri','trang_thai','ghi_chu'];

    public function nhaCungCap()      { return $this->belongsTo(NhaCungCap::class, 'ma_ncc', 'ma_ncc'); }
    public function nhanVien()        { return $this->belongsTo(NhanVien::class, 'ma_nv', 'ma_nv'); }
    public function chiTietNhapKhos() { return $this->hasMany(ChiTietNhapKho::class, 'ma_pnk', 'ma_pnk'); }
}
