<?php

namespace App\Http\Controllers;

use App\Models\NhatKyDangNhap;
use Illuminate\Http\Request;

/**
 * Nhật ký đăng nhập / an toàn thông tin (chỉ superadmin).
 */
class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $hanhDong = $request->query('hanh_dong');

        $logs = NhatKyDangNhap::query()
            ->when($hanhDong, fn($q) => $q->where('hanh_dong', $hanhDong))
            ->orderByDesc('thoi_gian')
            ->paginate(50)
            ->withQueryString();

        // Thống kê nhanh 24h gần nhất.
        $tu24h = now()->subDay();
        $thatBai24h = NhatKyDangNhap::where('hanh_dong', 'dang_nhap_that_bai')->where('thoi_gian', '>=', $tu24h)->count();
        $khoa24h    = NhatKyDangNhap::where('hanh_dong', 'tai_khoan_bi_khoa')->where('thoi_gian', '>=', $tu24h)->count();
        $thanhCong24h = NhatKyDangNhap::where('hanh_dong', 'dang_nhap')->where('thoi_gian', '>=', $tu24h)->count();

        return view('staff.audit-log', compact('logs', 'hanhDong', 'thatBai24h', 'khoa24h', 'thanhCong24h'));
    }
}
