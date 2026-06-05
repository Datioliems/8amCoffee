<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $maChiNhanh = session('ma_chi_nhanh');
        $tuNgay = $request->get('tu_ngay', now()->startOfMonth()->toDateString());
        $denNgay = $request->get('den_ngay', now()->toDateString());
        $ngayHoaDon = DB::raw('CAST(hd.thoi_gian_lap AS DATE)');

        $doanhThuTheoNgay = DB::table('HOA_DON as hd')
            ->join('ORDERS as o', 'o.ma_order', '=', 'hd.ma_order')
            ->where('o.ma_chi_nhanh', $maChiNhanh)
            ->whereBetween($ngayHoaDon, [$tuNgay, $denNgay])
            ->select(
                DB::raw('CAST(hd.thoi_gian_lap AS DATE) as ngay'),
                DB::raw('COUNT(*) as so_hoa_don'),
                DB::raw('SUM(hd.tong_tien_sau_ck) as tong_doanh_thu')
            )
            ->groupBy(DB::raw('CAST(hd.thoi_gian_lap AS DATE)'))
            ->orderBy(DB::raw('CAST(hd.thoi_gian_lap AS DATE)'))
            ->get();

        $topMon = DB::table('CHI_TIET_ORDER as cto')
            ->join('ORDERS as o', 'o.ma_order', '=', 'cto.ma_order')
            ->join('MON as m', 'm.ma_mon', '=', 'cto.ma_mon')
            ->where('o.ma_chi_nhanh', $maChiNhanh)
            ->whereBetween(DB::raw('CAST(o.ngay_order AS DATE)'), [$tuNgay, $denNgay])
            ->whereNotIn('o.trang_thai', ['da_huy'])
            ->select('m.ten_mon', DB::raw('SUM(cto.so_luong) as tong_sl'))
            ->groupBy('m.ten_mon')
            ->orderByDesc('tong_sl')
            ->limit(5)
            ->get();

        $tongKet = [
            'tong_doanh_thu' => $doanhThuTheoNgay->sum('tong_doanh_thu'),
            'tong_hoa_don' => $doanhThuTheoNgay->sum('so_hoa_don'),
        ];

        return view('inventory.report', compact(
            'doanhThuTheoNgay',
            'topMon',
            'tongKet',
            'tuNgay',
            'denNgay'
        ));
    }

    public function export(Request $request)
    {
        $maChiNhanh = session('ma_chi_nhanh');
        $tuNgay = $request->get('tu_ngay', now()->startOfMonth()->toDateString());
        $denNgay = $request->get('den_ngay', now()->toDateString());

        $data = DB::table('HOA_DON as hd')
            ->join('ORDERS as o', 'o.ma_order', '=', 'hd.ma_order')
            ->where('o.ma_chi_nhanh', $maChiNhanh)
            ->whereBetween(DB::raw('CAST(hd.thoi_gian_lap AS DATE)'), [$tuNgay, $denNgay])
            ->select('hd.ma_hoa_don', 'o.ma_order', 'hd.tong_tien_sau_ck', 'hd.phuong_thuc_tt', 'hd.thoi_gian_lap')
            ->orderBy('hd.thoi_gian_lap')
            ->get();

        $filename = "bao-cao-{$tuNgay}-den-{$denNgay}.csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');
            fwrite($file, "\xEF\xBB\xBF");
            fputcsv($file, ['Mã hóa đơn', 'Mã order', 'Tổng tiền', 'Phương thức', 'Thời gian lập']);

            foreach ($data as $row) {
                fputcsv($file, [
                    $row->ma_hoa_don,
                    $row->ma_order,
                    $row->tong_tien_sau_ck,
                    $row->phuong_thuc_tt,
                    $row->thoi_gian_lap,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
