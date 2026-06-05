<?php

namespace App\Http\Controllers;

use App\Models\ChiNhanh;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /** Trang báo cáo doanh thu (lọc theo kỳ + chi nhánh). */
    public function index(Request $request)
    {
        return view('report.index', $this->collect($request));
    }

    /** Trang IN báo cáo (bố cục tối giản, tự mở hộp thoại in / Lưu PDF). */
    public function print(Request $request)
    {
        return view('report.print', $this->collect($request) + [
            'inLuc' => now()->format('d/m/Y H:i'),
        ]);
    }

    /** Xuất CSV chi tiết hóa đơn theo cùng bộ lọc. */
    public function export(Request $request)
    {
        [$tuNgay, $denNgay] = $this->resolvePeriod($request->get('ky', 'thang_nay'), $request);
        $branchFilter = $this->resolveBranch($request);

        $data = DB::table('HOA_DON as hd')
            ->join('ORDERS as o', 'o.ma_order', '=', 'hd.ma_order')
            ->leftJoin('CHI_NHANH as cn', 'cn.ma_chi_nhanh', '=', 'o.ma_chi_nhanh')
            ->when($branchFilter, fn ($q, $b) => $q->where('o.ma_chi_nhanh', $b))
            ->whereBetween(DB::raw('CAST(hd.thoi_gian_lap AS DATE)'), [$tuNgay, $denNgay])
            ->select('hd.ma_hoa_don', 'o.ma_order', 'cn.ten_chi_nhanh', 'hd.tong_tien_sau_ck', 'hd.phuong_thuc_tt', 'hd.thoi_gian_lap')
            ->orderBy('hd.thoi_gian_lap')
            ->get();

        $filename = "bao-cao-{$tuNgay}-den-{$denNgay}.csv";

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');
            fwrite($file, "\xEF\xBB\xBF");
            fputcsv($file, ['Mã hóa đơn', 'Mã order', 'Chi nhánh', 'Tổng tiền', 'Phương thức', 'Thời gian lập']);

            foreach ($data as $row) {
                fputcsv($file, [
                    $row->ma_hoa_don,
                    $row->ma_order,
                    $row->ten_chi_nhanh,
                    $row->tong_tien_sau_ck,
                    $row->phuong_thuc_tt,
                    $row->thoi_gian_lap,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Gom toàn bộ dữ liệu báo cáo (dùng chung cho index/print).
     *
     * @return array<string,mixed>
     */
    private function collect(Request $request): array
    {
        $isSuper      = session('chuc_vu') === 'superadmin';
        $branchFilter = $this->resolveBranch($request);
        $ky           = $request->get('ky', 'thang_nay');
        [$tuNgay, $denNgay] = $this->resolvePeriod($ky, $request);

        // Doanh thu theo ngày
        $doanhThuTheoNgay = DB::table('HOA_DON as hd')
            ->join('ORDERS as o', 'o.ma_order', '=', 'hd.ma_order')
            ->when($branchFilter, fn ($q, $b) => $q->where('o.ma_chi_nhanh', $b))
            ->whereBetween(DB::raw('CAST(hd.thoi_gian_lap AS DATE)'), [$tuNgay, $denNgay])
            ->select(
                DB::raw('CAST(hd.thoi_gian_lap AS DATE) as ngay'),
                DB::raw('COUNT(*) as so_hoa_don'),
                DB::raw('SUM(hd.tong_tien_sau_ck) as tong_doanh_thu')
            )
            ->groupBy(DB::raw('CAST(hd.thoi_gian_lap AS DATE)'))
            ->orderBy(DB::raw('CAST(hd.thoi_gian_lap AS DATE)'))
            ->get();

        // Top 5 món bán chạy
        $topMon = DB::table('CHI_TIET_ORDER as cto')
            ->join('ORDERS as o', 'o.ma_order', '=', 'cto.ma_order')
            ->join('MON as m', 'm.ma_mon', '=', 'cto.ma_mon')
            ->when($branchFilter, fn ($q, $b) => $q->where('o.ma_chi_nhanh', $b))
            ->whereBetween(DB::raw('CAST(o.ngay_order AS DATE)'), [$tuNgay, $denNgay])
            ->whereNotIn('o.trang_thai', ['da_huy'])
            ->select('m.ten_mon', DB::raw('SUM(cto.so_luong) as tong_sl'))
            ->groupBy('m.ten_mon')
            ->orderByDesc('tong_sl')
            ->limit(5)
            ->get();

        // Khi xem TẤT CẢ chi nhánh → thêm bảng tách theo từng chi nhánh
        $doanhThuTheoChiNhanh = collect();
        if ($branchFilter === null) {
            $doanhThuTheoChiNhanh = DB::table('HOA_DON as hd')
                ->join('ORDERS as o', 'o.ma_order', '=', 'hd.ma_order')
                ->leftJoin('CHI_NHANH as cn', 'cn.ma_chi_nhanh', '=', 'o.ma_chi_nhanh')
                ->whereBetween(DB::raw('CAST(hd.thoi_gian_lap AS DATE)'), [$tuNgay, $denNgay])
                ->select(
                    'o.ma_chi_nhanh',
                    'cn.ten_chi_nhanh',
                    DB::raw('COUNT(*) as so_hoa_don'),
                    DB::raw('SUM(hd.tong_tien_sau_ck) as tong_doanh_thu')
                )
                ->groupBy('o.ma_chi_nhanh', 'cn.ten_chi_nhanh')
                ->orderByDesc('tong_doanh_thu')
                ->get();
        }

        $tongKet = [
            'tong_doanh_thu' => $doanhThuTheoNgay->sum('tong_doanh_thu'),
            'tong_hoa_don'   => $doanhThuTheoNgay->sum('so_hoa_don'),
        ];

        $branchLabel = $branchFilter
            ? (optional(ChiNhanh::find($branchFilter))->ten_chi_nhanh ?? $branchFilter)
            : 'Tất cả chi nhánh';

        return [
            'doanhThuTheoNgay'     => $doanhThuTheoNgay,
            'doanhThuTheoChiNhanh' => $doanhThuTheoChiNhanh,
            'topMon'               => $topMon,
            'tongKet'              => $tongKet,
            'tuNgay'               => $tuNgay,
            'denNgay'              => $denNgay,
            'ky'                   => $ky,
            'isSuper'              => $isSuper,
            'branches'             => ChiNhanh::orderBy('ma_chi_nhanh')->get(['ma_chi_nhanh', 'ten_chi_nhanh']),
            'branchFilter'         => $branchFilter,
            'branchLabel'          => $branchLabel,
            'kyLabel'              => $this->kyLabel($ky),
        ];
    }

    /** Chi nhánh áp dụng: superadmin chọn 1 hoặc để trống = tất cả; vai trò khác khóa theo chi nhánh của mình. */
    private function resolveBranch(Request $request): ?string
    {
        if (session('chuc_vu') === 'superadmin') {
            $param = $request->get('ma_chi_nhanh', '');
            return ($param === '' || $param === 'all') ? null : $param;
        }
        return session('ma_chi_nhanh');
    }

    /** Quy đổi kỳ → [tu_ngay, den_ngay]. */
    private function resolvePeriod(string $ky, Request $request): array
    {
        $today = now();

        return match ($ky) {
            'hom_nay'  => [$today->toDateString(), $today->toDateString()],
            'tuan_nay' => [$today->copy()->startOfWeek()->toDateString(), $today->toDateString()],
            'quy_nay'  => [$today->copy()->startOfQuarter()->toDateString(), $today->toDateString()],
            'nam_nay'  => [$today->copy()->startOfYear()->toDateString(), $today->toDateString()],
            'tuy_chon' => [
                $request->get('tu_ngay', $today->copy()->startOfMonth()->toDateString()),
                $request->get('den_ngay', $today->toDateString()),
            ],
            default    => [$today->copy()->startOfMonth()->toDateString(), $today->toDateString()],
        };
    }

    private function kyLabel(string $ky): string
    {
        return [
            'hom_nay'  => 'Hôm nay',
            'tuan_nay' => 'Tuần này',
            'thang_nay'=> 'Tháng này',
            'quy_nay'  => 'Quý này',
            'nam_nay'  => 'Năm này',
            'tuy_chon' => 'Tùy chọn',
        ][$ky] ?? 'Tháng này';
    }
}
