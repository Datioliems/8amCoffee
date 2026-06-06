<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

/**
 * Nhật ký hành động nghiệp vụ của nhân viên (audit log).
 */
class NhatKyHanhDong extends Model
{
    protected $table      = 'NHAT_KY_HANH_DONG';
    protected $primaryKey = 'id';
    public $timestamps    = false;

    protected $fillable = [
        'ma_nv','ten_nv','hanh_dong',
        'doi_tuong_loai','doi_tuong_ma',
        'mo_ta','chi_tiet','dia_chi_ip','thoi_gian',
    ];

    protected $casts = [
        'chi_tiet'   => 'array',
        'thoi_gian'  => 'datetime',
    ];

    /**
     * Ghi nhanh một hành động của nhân viên.
     * Đọc session(ma_nv / ten_nv) và request IP tự động.
     *
     * @param string      $hanhDong      Mã hành động, VD: 'duyet_phieu_nhap'
     * @param string|null $doiTuongLoai  Loại đối tượng, VD: 'phieu_nhap'
     * @param string|null $doiTuongMa    Mã đối tượng, VD: 'PNK20260607120000'
     * @param string|null $moTa          Mô tả ngắn, VD: 'Duyệt phiếu nhập PNK...'
     * @param array       $chiTiet       Dữ liệu thêm lưu dạng JSON
     */
    public static function ghi(
        string $hanhDong,
        ?string $doiTuongLoai = null,
        ?string $doiTuongMa   = null,
        ?string $moTa         = null,
        array   $chiTiet      = []
    ): void {
        try {
            static::create([
                'ma_nv'          => session('ma_nv'),
                'ten_nv'         => session('ten_nv'),
                'hanh_dong'      => $hanhDong,
                'doi_tuong_loai' => $doiTuongLoai,
                'doi_tuong_ma'   => $doiTuongMa,
                'mo_ta'          => $moTa,
                'chi_tiet'       => $chiTiet ?: null,
                'dia_chi_ip'     => Request::ip(),
                'thoi_gian'      => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** Nhãn tiếng Việt cho mã hành động. */
    public static function nhanHanhDong(string $hanhDong): string
    {
        return match($hanhDong) {
            'tao_phieu_nhap'          => 'Tạo phiếu nhập',
            'duyet_phieu_nhap'        => 'Duyệt phiếu nhập',
            'huy_phieu_nhap'          => 'Hủy phiếu nhập',
            'tao_kiem_ke'             => 'Tạo kiểm kê',
            'xac_nhan_kiem_ke'        => 'Xác nhận kiểm kê',
            'huy_kiem_ke'             => 'Hủy kiểm kê',
            'tao_nguyen_lieu'         => 'Thêm nguyên liệu',
            'cap_nhat_nguyen_lieu'    => 'Sửa nguyên liệu',
            'xoa_nguyen_lieu'         => 'Xóa nguyên liệu',
            'tao_nha_cung_cap'        => 'Thêm nhà cung cấp',
            'cap_nhat_nha_cung_cap'   => 'Sửa nhà cung cấp',
            'xoa_nha_cung_cap'        => 'Xóa nhà cung cấp',
            'tao_don_hang'            => 'Tạo đơn hàng',
            'xac_nhan_don_hang'       => 'Xác nhận đơn',
            'cap_nhat_trang_thai_don' => 'Đổi trạng thái đơn',
            'gop_don_hang'            => 'Gộp đơn hàng',
            'tach_don_hang'           => 'Tách đơn hàng',
            'thanh_toan_don_hang'     => 'Thanh toán',
            'tao_mon'                 => 'Thêm món',
            'cap_nhat_mon'            => 'Sửa món',
            'an_mon'                  => 'Ẩn món',
            'hien_mon'                => 'Hiện món',
            'tao_tai_khoan'           => 'Tạo tài khoản NV',
            'cap_nhat_tai_khoan'      => 'Sửa tài khoản NV',
            'xoa_tai_khoan'           => 'Xóa tài khoản NV',
            'cap_nhat_phan_quyen'     => 'Cập nhật phân quyền',
            'phat_the'                => 'Phát thẻ thành viên',
            'dieu_chinh_diem'         => 'Điều chỉnh điểm',
            'cap_nhat_trang_thai_the' => 'Đổi trạng thái thẻ',
            'cap_nhat_hang'           => 'Cập nhật hạng TV',
            'xoa_hang'                => 'Xóa hạng TV',
            default                   => $hanhDong,
        };
    }
}
