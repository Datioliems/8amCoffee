<?php

namespace App\Http\Controllers;

use App\Models\NhatKyHanhDong;
use Illuminate\Http\Request;

class NhatKyHanhDongController extends Controller
{
    public function index(Request $request)
    {
        $hanhDong = (string) $request->get('hanh_dong', '');
        $tuNgay   = (string) $request->get('tu_ngay', '');
        $denNgay  = (string) $request->get('den_ngay', '');
        $maNv     = (string) $request->get('ma_nv', '');

        $query = NhatKyHanhDong::orderByDesc('thoi_gian');

        if ($hanhDong !== '') $query->where('hanh_dong', $hanhDong);
        if ($tuNgay   !== '') $query->whereDate('thoi_gian', '>=', $tuNgay);
        if ($denNgay  !== '') $query->whereDate('thoi_gian', '<=', $denNgay);
        if ($maNv     !== '') $query->where('ma_nv', $maNv);

        $logs = $query->paginate(30)->withQueryString();

        $today              = today()->toDateString();
        $hanhDongHomNay     = NhatKyHanhDong::whereDate('thoi_gian', $today)->count();
        $nhanVienHoatDong   = NhatKyHanhDong::whereDate('thoi_gian', $today)
                                ->distinct('ma_nv')->count('ma_nv');
        $danhSachHanhDong   = NhatKyHanhDong::select('hanh_dong')
                                ->distinct()->orderBy('hanh_dong')->pluck('hanh_dong');

        return view('staff.hanh-dong-log', compact(
            'logs','hanhDong','tuNgay','denNgay','maNv',
            'hanhDongHomNay','nhanVienHoatDong','danhSachHanhDong'
        ));
    }
}
