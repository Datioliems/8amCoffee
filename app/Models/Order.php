<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table      = 'ORDERS';
    protected $primaryKey = 'ma_order';
    public $incrementing  = false;
    protected $keyType    = 'string';
    public $timestamps    = false;

    protected $fillable = ['ma_order','ma_ban','ma_kh','ten_khach','sdt_khach','ma_chi_nhanh','trang_thai','ngay_order','gio_order','ghi_chu','hinh_thuc','thoi_gian_xac_nhan','thoi_gian_phuc_vu','thoi_gian_thanh_toan'];

    protected $casts = [
        'ngay_order'          => 'date',
        'gio_order'           => 'string',
        'thoi_gian_xac_nhan'  => 'datetime',
        'thoi_gian_phuc_vu'   => 'datetime',
        'thoi_gian_thanh_toan'=> 'datetime',
        // PII khách tạm trên đơn → mã hóa (cast chịu lỗi)
        'ten_khach'           => \App\Casts\EncryptedString::class,
        'sdt_khach'           => \App\Casts\EncryptedString::class,
    ];

    /** Tên khách để hiển thị: ưu tiên khách đã lưu (có giao dịch), fallback tên tạm trên đơn. */
    public function getCustomerNameAttribute(): ?string
    {
        return $this->khachHang?->ten_kh ?: $this->ten_khach;
    }

    /** Có đóng cốc nhựa / mang đi không. */
    public function getDungCocNhuaAttribute(): bool
    {
        return $this->hinh_thuc === 'mang_ve';
    }

    /** Nhãn hình thức phục vụ. */
    public function getHinhThucLabelAttribute(): string
    {
        return $this->hinh_thuc === 'mang_ve' ? 'Mang về' : 'Tại bàn';
    }

    public function ban()          { return $this->belongsTo(Ban::class, 'ma_ban', 'ma_ban'); }
    public function khachHang()    { return $this->belongsTo(KhachHang::class, 'ma_kh', 'ma_kh'); }
    public function chiNhanh()     { return $this->belongsTo(ChiNhanh::class, 'ma_chi_nhanh', 'ma_chi_nhanh'); }
    public function chiTietOrders(){ return $this->hasMany(ChiTietOrder::class, 'ma_order', 'ma_order'); }
    public function hoaDon()       { return $this->hasOne(HoaDon::class, 'ma_order', 'ma_order'); }
    public function logs()         { return $this->hasMany(OrderLog::class, 'ma_order', 'ma_order'); }
}
