<?php

namespace App\Http\Controllers;

use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Trang phân tích AI: dự báo doanh thu + luật gợi ý món mua kèm.
 */
class AnalyticsController extends Controller
{
    public function __construct(private AnalyticsService $analytics) {}

    public function index(Request $request)
    {
        // Superadmin xem toàn hệ thống; admin/nhân viên xem theo chi nhánh.
        $maChiNhanh = session('chuc_vu') === 'superadmin' ? null : session('ma_chi_nhanh');

        // Tham số tùy chỉnh (có ô nhập trên giao diện), kẹp trong khoảng an toàn.
        $pham_vi    = $request->query('pham_vi') === 'don' ? 'don' : 'ngay';
        $days       = (int) $request->query('days', 120);
        $maxOrders  = (int) $request->query('max_orders', 200);
        $minSupport = (int) $request->query('min_support', 2);
        $topN       = (int) $request->query('top', 20);

        $days       = max(1, min(365, $days));
        $maxOrders  = max(5, min(5000, $maxOrders));
        $minSupport = max(1, min(50, $minSupport));
        $topN       = max(5, min(100, $topN));

        $forecast = $this->analytics->revenueForecast($maChiNhanh, lookback: 30, forecast: 7);

        // Luật kết hợp theo phạm vi người dùng chọn: theo NGÀY hoặc theo SỐ ĐƠN gần nhất.
        $limitOrders = $pham_vi === 'don' ? $maxOrders : null;
        ['rules' => $rules, 'total' => $totalDon] = $this->analytics->associationRules(
            $maChiNhanh, minSupport: $minSupport, days: $days, maxOrders: $limitOrders
        );
        $rules = array_slice($rules, 0, $topN);

        $maMons = collect($rules)->flatMap(fn($r) => [$r['a'], $r['b']])->unique()->all();
        $tenMon = DB::table('MON')->whereIn('ma_mon', $maMons ?: ['__none__'])->pluck('ten_mon', 'ma_mon');

        $filters = compact('pham_vi', 'days', 'maxOrders', 'minSupport', 'topN');

        return view('staff.analytics', compact('forecast', 'rules', 'tenMon', 'totalDon', 'filters'));
    }
}
