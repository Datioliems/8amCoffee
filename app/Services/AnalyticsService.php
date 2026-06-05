<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * AnalyticsService — tầng phân tích/ML cho 8AM Coffee.
 *
 *  1) revenueForecast(): dự báo doanh thu N ngày tới bằng HỒI QUY TUYẾN TÍNH
 *     (least squares) trên chuỗi doanh thu ngày lịch sử.
 *  2) itemAssociations() / recommendForItems(): GỢI Ý MÓN MUA KÈM bằng luật
 *     kết hợp (market-basket: support / confidence / lift) tính từ dữ liệu đơn thật.
 *
 *  Toàn bộ tính trên dữ liệu thật trong DB, không mock.
 */
class AnalyticsService
{
    // ─────────────────────────────────────────────────────────────
    // 1) DỰ BÁO DOANH THU — HỒI QUY TUYẾN TÍNH
    // ─────────────────────────────────────────────────────────────

    /**
     * @return array{
     *   history: array<int,array{label:string,value:float}>,
     *   forecast: array<int,array{label:string,value:float}>,
     *   slope: float, intercept: float, r2: float,
     *   trend: string, avg: float, next_total: float
     * }
     */
    public function revenueForecast(?string $maChiNhanh, int $lookback = 30, int $forecast = 7): array
    {
        $from = now()->subDays($lookback - 1)->toDateString();

        $raw = DB::table('HOA_DON as hd')
            ->join('ORDERS as o', 'o.ma_order', '=', 'hd.ma_order')
            ->when($maChiNhanh, fn($q) => $q->where('o.ma_chi_nhanh', $maChiNhanh))
            ->whereRaw('CAST(hd.thoi_gian_lap AS DATE) >= ?', [$from])
            ->selectRaw('CAST(hd.thoi_gian_lap AS DATE) as ngay, SUM(hd.tong_tien_sau_ck) as v')
            ->groupBy('ngay')
            ->pluck('v', 'ngay');

        // Zero-fill: chuỗi liên tục `lookback` ngày.
        $history = [];
        $y = [];
        for ($i = $lookback - 1; $i >= 0; $i--) {
            $d = now()->subDays($i);
            $val = (float) ($raw[$d->toDateString()] ?? 0);
            $history[] = ['label' => $d->format('d/m'), 'value' => $val];
            $y[] = $val;
        }

        $n = count($y);
        [$slope, $intercept, $r2] = $this->linearFit($y);

        // Dự báo `forecast` ngày kế tiếp (x = n .. n+forecast-1), không âm.
        $forecastArr = [];
        $nextTotal = 0.0;
        for ($k = 0; $k < $forecast; $k++) {
            $x = $n + $k;
            $pred = max(0.0, $intercept + $slope * $x);
            $nextTotal += $pred;
            $forecastArr[] = [
                'label' => now()->addDays($k + 1)->format('d/m'),
                'value' => round($pred),
            ];
        }

        $avg = $n > 0 ? array_sum($y) / $n : 0.0;

        return [
            'history'    => $history,
            'forecast'   => $forecastArr,
            'slope'      => round($slope, 2),
            'intercept'  => round($intercept, 2),
            'r2'         => round($r2, 3),
            'trend'      => $slope > 0 ? 'tăng' : ($slope < 0 ? 'giảm' : 'ổn định'),
            'avg'        => round($avg),
            'next_total' => round($nextTotal),
        ];
    }

    /**
     * Hồi quy tuyến tính least squares cho y[] với x = 0,1,2,...
     * @return array{0:float,1:float,2:float} [slope, intercept, r2]
     */
    private function linearFit(array $y): array
    {
        $n = count($y);
        if ($n < 2) return [0.0, $n ? (float) $y[0] : 0.0, 0.0];

        $sumX = $sumY = $sumXY = $sumX2 = 0.0;
        foreach ($y as $x => $val) {
            $sumX  += $x;
            $sumY  += $val;
            $sumXY += $x * $val;
            $sumX2 += $x * $x;
        }
        $denom = ($n * $sumX2 - $sumX * $sumX);
        $slope = $denom != 0.0 ? ($n * $sumXY - $sumX * $sumY) / $denom : 0.0;
        $intercept = ($sumY - $slope * $sumX) / $n;

        // R²
        $meanY = $sumY / $n;
        $ssTot = $ssRes = 0.0;
        foreach ($y as $x => $val) {
            $pred = $intercept + $slope * $x;
            $ssRes += ($val - $pred) ** 2;
            $ssTot += ($val - $meanY) ** 2;
        }
        $r2 = $ssTot > 0 ? max(0.0, 1 - $ssRes / $ssTot) : 0.0;

        return [$slope, $intercept, $r2];
    }

    // ─────────────────────────────────────────────────────────────
    // 2) GỢI Ý MÓN MUA KÈM — MARKET BASKET (LUẬT KẾT HỢP)
    // ─────────────────────────────────────────────────────────────

    /**
     * Lấy "giỏ" mỗi đơn (tập món riêng biệt) trong các đơn hợp lệ gần đây.
     * @return array<string,array<int,string>>  ma_order => [ma_mon,...]
     */
    private function baskets(?string $maChiNhanh, int $days = 120, ?int $maxOrders = null): array
    {
        $from = now()->subDays($days)->toDateString();

        // Nếu giới hạn theo SỐ ĐƠN gần nhất: lấy danh sách ma_order mới nhất trước.
        $orderIds = null;
        if ($maxOrders !== null && $maxOrders > 0) {
            $orderIds = DB::table('ORDERS')
                ->when($maChiNhanh, fn($q) => $q->where('ma_chi_nhanh', $maChiNhanh))
                ->whereNotIn('trang_thai', ['da_huy', 'dang_chon'])
                ->where('ngay_order', '>=', $from)
                ->orderByDesc('ngay_order')->orderByDesc('gio_order')
                ->limit($maxOrders)
                ->pluck('ma_order');
        }

        $rows = DB::table('CHI_TIET_ORDER as ct')
            ->join('ORDERS as o', 'o.ma_order', '=', 'ct.ma_order')
            ->when($maChiNhanh, fn($q) => $q->where('o.ma_chi_nhanh', $maChiNhanh))
            ->whereNotIn('o.trang_thai', ['da_huy', 'dang_chon'])
            ->where('o.ngay_order', '>=', $from)
            ->when($orderIds !== null, fn($q) => $q->whereIn('ct.ma_order', $orderIds))
            ->select('ct.ma_order', 'ct.ma_mon')
            ->get();

        $baskets = [];
        foreach ($rows as $r) {
            $baskets[$r->ma_order][$r->ma_mon] = $r->ma_mon; // dùng key để khử trùng lặp
        }
        return array_map('array_values', $baskets);
    }

    /**
     * Tính luật kết hợp cho TẤT CẢ các món.
     * @return array{
     *   rules: array<int,array{a:string,b:string,support:int,confidence:float,lift:float}>,
     *   itemCount: array<string,int>, total: int
     * }
     */
    public function associationRules(?string $maChiNhanh, int $minSupport = 2, int $days = 120, ?int $maxOrders = null): array
    {
        $baskets = $this->baskets($maChiNhanh, $days, $maxOrders);
        $total = count($baskets);

        $itemCount = [];   // số đơn chứa món A
        $pairCount = [];    // "A|B" => số đơn chứa cả A và B

        foreach ($baskets as $items) {
            foreach ($items as $a) {
                $itemCount[$a] = ($itemCount[$a] ?? 0) + 1;
            }
            $m = count($items);
            for ($i = 0; $i < $m; $i++) {
                for ($j = $i + 1; $j < $m; $j++) {
                    $a = $items[$i]; $b = $items[$j];
                    $pairCount["$a|$b"] = ($pairCount["$a|$b"] ?? 0) + 1;
                }
            }
        }

        $rules = [];
        foreach ($pairCount as $key => $supp) {
            if ($supp < $minSupport) continue;
            [$a, $b] = explode('|', $key);
            // Tạo luật 2 chiều A->B và B->A
            foreach ([[$a, $b], [$b, $a]] as [$x, $y]) {
                $confidence = $itemCount[$x] > 0 ? $supp / $itemCount[$x] : 0;
                $suppY = $itemCount[$y] / max(1, $total);
                $lift = $suppY > 0 ? $confidence / $suppY : 0;
                $rules[] = [
                    'a' => $x, 'b' => $y,
                    'support' => $supp,
                    'confidence' => round($confidence, 3),
                    'lift' => round($lift, 2),
                ];
            }
        }

        // Sắp theo lift giảm dần (luật "đáng tin & bất ngờ" lên đầu)
        usort($rules, fn($p, $q) => $q['lift'] <=> $p['lift'] ?: $q['confidence'] <=> $p['confidence']);

        return ['rules' => $rules, 'itemCount' => $itemCount, 'total' => $total];
    }

    /**
     * Gợi ý món mua kèm cho một GIỎ hiện tại (danh sách ma_mon).
     * Trả về danh sách món kèm tên/giá, sắp theo điểm gợi ý (tổng lift × confidence).
     *
     * @param array<int,string> $maMons
     * @return array<int,array{ma_mon:string,ten_mon:string,don_gia:float,score:float,confidence:float}>
     */
    public function recommendForItems(array $maMons, ?string $maChiNhanh, int $limit = 4): array
    {
        $maMons = array_values(array_unique(array_filter($maMons)));
        if (empty($maMons)) {
            return $this->popularItems($maChiNhanh, $limit);
        }

        ['rules' => $rules] = $this->associationRules($maChiNhanh, 1);
        $inCart = array_flip($maMons);

        $scores = [];      // ma_mon => điểm
        $conf = [];        // ma_mon => confidence cao nhất
        foreach ($rules as $r) {
            if (!isset($inCart[$r['a']])) continue;   // chỉ xét luật xuất phát từ món trong giỏ
            if (isset($inCart[$r['b']])) continue;    // bỏ món đã có trong giỏ
            $scores[$r['b']] = ($scores[$r['b']] ?? 0) + $r['lift'] * $r['confidence'];
            $conf[$r['b']] = max($conf[$r['b']] ?? 0, $r['confidence']);
        }

        if (empty($scores)) {
            return $this->popularItems($maChiNhanh, $limit, $maMons);
        }

        arsort($scores);
        $top = array_slice(array_keys($scores), 0, $limit);

        return $this->hydrateItems($top, fn($ma) => [
            'score'      => round($scores[$ma], 2),
            'confidence' => round(($conf[$ma] ?? 0) * 100),
        ]);
    }

    /** Món bán chạy (fallback khi chưa đủ dữ liệu luật kết hợp). */
    public function popularItems(?string $maChiNhanh, int $limit = 4, array $exclude = []): array
    {
        $rows = DB::table('CHI_TIET_ORDER as ct')
            ->join('ORDERS as o', 'o.ma_order', '=', 'ct.ma_order')
            ->when($maChiNhanh, fn($q) => $q->where('o.ma_chi_nhanh', $maChiNhanh))
            ->whereNotIn('o.trang_thai', ['da_huy', 'dang_chon'])
            ->when($exclude, fn($q) => $q->whereNotIn('ct.ma_mon', $exclude))
            ->selectRaw('ct.ma_mon, SUM(ct.so_luong) as sl')
            ->groupBy('ct.ma_mon')
            ->orderByDesc('sl')
            ->limit($limit)
            ->pluck('ma_mon')
            ->all();

        return $this->hydrateItems($rows, fn($ma) => ['score' => 0, 'confidence' => 0]);
    }

    /** Bổ sung tên/giá món + (callback) trường phụ; giữ đúng thứ tự. */
    private function hydrateItems(array $maMons, callable $extra): array
    {
        if (empty($maMons)) return [];

        $mons = DB::table('MON')
            ->whereIn('ma_mon', $maMons)
            ->where('trang_thai', 'active')
            ->get(['ma_mon', 'ten_mon', 'don_gia', 'hinh_anh'])
            ->keyBy('ma_mon');

        $out = [];
        foreach ($maMons as $ma) {
            if (!isset($mons[$ma])) continue;
            $m = $mons[$ma];
            $out[] = array_merge([
                'ma_mon'   => $m->ma_mon,
                'ten_mon'  => $m->ten_mon,
                'don_gia'  => (float) $m->don_gia,
                'hinh_anh' => $m->hinh_anh,
            ], $extra($ma));
        }
        return $out;
    }
}
