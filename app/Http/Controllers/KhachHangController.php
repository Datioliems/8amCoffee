<?php

namespace App\Http\Controllers;

use App\Support\Pii;
use Illuminate\Support\Facades\DB;

class KhachHangController extends Controller
{
    /** Danh sách khách hàng — chỉ những khách có giao dịch (hóa đơn). */
    public function index()
    {
        $isSuper    = session('chuc_vu') === 'superadmin';
        $maChiNhanh = (string) session('ma_chi_nhanh', '');

        // ten_kh/sdt đã mã hóa (IV ngẫu nhiên) → KHÔNG group-by trực tiếp.
        // Gộp theo ma_kh, lấy MAX(ciphertext) (mỗi ma_kh chỉ 1 dòng) rồi giải mã ở PHP.
        $khachHangs = DB::table('KHACH_HANG as kh')
            ->join('HOA_DON as hd', 'hd.ma_kh', '=', 'kh.ma_kh')
            ->join('ORDERS as o', 'o.ma_order', '=', 'hd.ma_order')
            ->when(! $isSuper, fn($q) => $q->where('o.ma_chi_nhanh', $maChiNhanh))
            ->select(
                'kh.ma_kh',
                DB::raw('MAX(kh.ten_kh) as ten_kh'),
                DB::raw('MAX(kh.sdt) as sdt'),
                DB::raw('COUNT(DISTINCT hd.ma_hoa_don) as so_don'),
                DB::raw('SUM(hd.tong_tien_sau_ck) as tong_chi_tieu'),
                DB::raw('MAX(hd.thoi_gian_lap) as lan_cuoi')
            )
            ->groupBy('kh.ma_kh')
            ->orderByDesc('tong_chi_tieu')
            ->paginate(50);

        // Giải mã PII để hiển thị.
        $khachHangs->getCollection()->transform(function ($r) {
            $r->ten_kh = Pii::tryDecrypt($r->ten_kh);
            $r->sdt    = Pii::tryDecrypt($r->sdt);
            return $r;
        });

        return view('staff.khachhang-list', compact('khachHangs', 'isSuper'));
    }
}
