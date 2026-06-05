<?php

namespace App\Http\Controllers;

use App\Models\Ban;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $maChiNhanh = session('ma_chi_nhanh');
        $today = now()->toDateString();

        $orderHomNay = Order::where('ma_chi_nhanh', $maChiNhanh)
            ->where('ngay_order', $today)
            ->count();

        $orderChoXacNhan = Order::where('ma_chi_nhanh', $maChiNhanh)
            ->where('trang_thai', 'cho_xac_nhan')
            ->count();

        $orderDangPhaChe = Order::where('ma_chi_nhanh', $maChiNhanh)
            ->where('trang_thai', 'dang_pha_che')
            ->count();

        $doanhThuHomNay = DB::table('HOA_DON as hd')
            ->join('ORDERS as o', 'o.ma_order', '=', 'hd.ma_order')
            ->where('o.ma_chi_nhanh', $maChiNhanh)
            ->whereRaw('CAST(hd.thoi_gian_lap AS DATE) = ?', [$today])
            ->sum('hd.tong_tien_sau_ck');

        $banCoKhach = Ban::where('ma_chi_nhanh', $maChiNhanh)
            ->where('trang_thai', 'co_khach')
            ->count();

        $tongBan = Ban::where('ma_chi_nhanh', $maChiNhanh)->count();

        $orderGanDay = Order::with(['ban', 'khachHang'])
            ->where('ma_chi_nhanh', $maChiNhanh)
            ->whereIn('trang_thai', ['cho_xac_nhan', 'da_xac_nhan', 'dang_pha_che'])
            ->orderByDesc('ngay_order')
            ->orderByDesc('gio_order')
            ->limit(5)
            ->get();

        $doanhThu7NgayRaw = DB::table('HOA_DON as hd')
            ->join('ORDERS as o', 'o.ma_order', '=', 'hd.ma_order')
            ->where('o.ma_chi_nhanh', $maChiNhanh)
            ->whereRaw('CAST(hd.thoi_gian_lap AS DATE) >= ?', [now()->subDays(6)->toDateString()])
            ->selectRaw('CAST(hd.thoi_gian_lap AS DATE) as ngay, SUM(hd.tong_tien_sau_ck) as doanh_thu')
            ->groupBy('ngay')
            ->pluck('doanh_thu', 'ngay');

        $doanhThu7Ngay = collect(range(6, 0))->map(function ($daysAgo) use ($doanhThu7NgayRaw) {
            $date = now()->subDays($daysAgo);
            return [
                'label' => $date->format('d/m'),
                'value' => (float) ($doanhThu7NgayRaw[$date->toDateString()] ?? 0),
            ];
        });

        $topMons = DB::table('CHI_TIET_ORDER as ct')
            ->join('ORDERS as o', 'o.ma_order', '=', 'ct.ma_order')
            ->join('MON as m', 'm.ma_mon', '=', 'ct.ma_mon')
            ->where('o.ma_chi_nhanh', $maChiNhanh)
            ->where('o.ngay_order', $today)
            ->whereNotIn('o.trang_thai', ['da_huy', 'dang_chon'])
            ->selectRaw('m.ten_mon, SUM(ct.so_luong) as so_luong')
            ->groupBy('m.ten_mon')
            ->orderByDesc('so_luong')
            ->limit(5)
            ->get();

        // ── Dữ liệu cho các biểu đồ Chart.js ──────────────────────
        $statusLabels = [
            'cho_xac_nhan' => 'Chờ xác nhận', 'da_xac_nhan' => 'Đã xác nhận',
            'dang_pha_che' => 'Đang pha chế', 'da_phuc_vu' => 'Đã phục vụ',
            'hoan_thanh' => 'Đã thanh toán', 'da_huy' => 'Đã hủy',
        ];
        $methodLabels = [
            'tien_mat' => 'Tiền mặt', 'chuyen_khoan' => 'Chuyển khoản',
            'the' => 'Thẻ', 'vi_dien_tu' => 'Ví điện tử', 'momo' => 'MoMo', 'vnpay' => 'VNPay',
        ];

        // Phương thức thanh toán (theo doanh thu, toàn thời gian của chi nhánh)
        $payRaw = DB::table('HOA_DON as hd')
            ->join('ORDERS as o', 'o.ma_order', '=', 'hd.ma_order')
            ->where('o.ma_chi_nhanh', $maChiNhanh)
            ->selectRaw('hd.phuong_thuc_tt as k, SUM(hd.tong_tien_sau_ck) as v')
            ->groupBy('hd.phuong_thuc_tt')->get();

        // Đơn theo trạng thái (hôm nay)
        $statusRaw = Order::where('ma_chi_nhanh', $maChiNhanh)
            ->where('ngay_order', $today)
            ->selectRaw('trang_thai as k, COUNT(*) as v')
            ->groupBy('trang_thai')->get();

        // Doanh thu theo giờ (hôm nay) — khung 7h–22h
        $hourRaw = DB::table('HOA_DON as hd')
            ->join('ORDERS as o', 'o.ma_order', '=', 'hd.ma_order')
            ->where('o.ma_chi_nhanh', $maChiNhanh)
            ->whereRaw('CAST(hd.thoi_gian_lap AS DATE) = ?', [$today])
            ->selectRaw('HOUR(hd.thoi_gian_lap) as gio, SUM(hd.tong_tien_sau_ck) as v')
            ->groupBy('gio')->pluck('v', 'gio');
        $hours = range(7, 22);

        // Tại chỗ vs mang về (đơn đã thanh toán) — theo hình thức phục vụ thật (cốc nhựa)
        $channelRaw = Order::where('ma_chi_nhanh', $maChiNhanh)
            ->where('trang_thai', 'hoan_thanh')
            ->selectRaw("hinh_thuc as k, COUNT(*) as v")
            ->groupBy('hinh_thuc')->pluck('v', 'k');

        // Doanh thu theo danh mục (đơn đã thanh toán)
        $catRaw = DB::table('CHI_TIET_ORDER as ct')
            ->join('ORDERS as o', 'o.ma_order', '=', 'ct.ma_order')
            ->join('MON as m', 'm.ma_mon', '=', 'ct.ma_mon')
            ->join('DANH_MUC as d', 'd.ma_danh_muc', '=', 'm.ma_danh_muc')
            ->where('o.ma_chi_nhanh', $maChiNhanh)
            ->where('o.trang_thai', 'hoan_thanh')
            ->selectRaw('d.ten_danh_muc as k, SUM(ct.so_luong * ct.don_gia_tai_thoi_diem) as v')
            ->groupBy('d.ten_danh_muc')->orderByDesc('v')->get();

        $chartData = [
            'revenue7' => [
                'labels' => $doanhThu7Ngay->pluck('label')->all(),
                'values' => $doanhThu7Ngay->pluck('value')->all(),
            ],
            'payment' => [
                'labels' => $payRaw->map(fn($r) => $methodLabels[$r->k] ?? $r->k)->all(),
                'values' => $payRaw->pluck('v')->map(fn($v) => (float) $v)->all(),
            ],
            'status' => [
                'labels' => $statusRaw->map(fn($r) => $statusLabels[$r->k] ?? $r->k)->all(),
                'values' => $statusRaw->pluck('v')->map(fn($v) => (int) $v)->all(),
            ],
            'topMons' => [
                'labels' => $topMons->pluck('ten_mon')->all(),
                'values' => $topMons->pluck('so_luong')->map(fn($v) => (int) $v)->all(),
            ],
            'hourly' => [
                'labels' => array_map(fn($h) => $h . 'h', $hours),
                'values' => array_map(fn($h) => (float) ($hourRaw[$h] ?? 0), $hours),
            ],
            'channel' => [
                'labels' => ['Tại bàn', 'Mang về'],
                'values' => [(int) ($channelRaw['tai_ban'] ?? 0), (int) ($channelRaw['mang_ve'] ?? 0)],
            ],
            'category' => [
                'labels' => $catRaw->pluck('k')->all(),
                'values' => $catRaw->pluck('v')->map(fn($v) => (float) $v)->all(),
            ],
        ];

        return view('staff.dashboard', compact(
            'orderHomNay',
            'orderChoXacNhan',
            'orderDangPhaChe',
            'doanhThuHomNay',
            'banCoKhach',
            'tongBan',
            'orderGanDay',
            'doanhThu7Ngay',
            'topMons',
            'chartData'
        ));
    }
}
