<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class QrScanFeatureService
{
    /**
     * Tổng hợp đặc trưng cho tất cả IP trong cửa sổ $minutes phút gần nhất.
     * Bỏ qua IP whitelist (wifi chi nhánh, thiết bị nhân viên).
     *
     * @return array<int, array>
     */
    public function buildFeatures(int $minutes = 5): array
    {
        $from      = now()->subMinutes($minutes);
        $whitelist = config('qr_anomaly.whitelist_ips', []);

        // Lấy toàn bộ log trong cửa sổ một lần (tránh N+1), group theo IP trong PHP.
        $logs = DB::table('scan_log')
            ->where('thoi_gian', '>=', $from)
            ->whereNotNull('ip')
            ->orderBy('thoi_gian')
            ->get()
            ->groupBy('ip');

        $features = [];
        foreach ($logs as $ip => $ipLogs) {
            if (in_array($ip, $whitelist, true)) {
                continue;
            }
            $features[] = $this->buildForIp((string) $ip, $ipLogs, $from, $minutes);
        }

        return $features;
    }

    /**
     * Xuất đặc trưng theo nhiều cửa sổ trong N ngày gần đây — dùng để tạo CSV train model.
     *
     * @return array<int, array>
     */
    public function buildHistoricalFeatures(int $days = 14, int $windowMinutes = 5): array
    {
        $whitelist = config('qr_anomaly.whitelist_ips', []);

        // Lấy toàn bộ log trong khoảng lịch sử một lần
        $allLogs = DB::table('scan_log')
            ->where('thoi_gian', '>=', now()->subDays($days))
            ->whereNotNull('ip')
            ->orderBy('thoi_gian')
            ->get();

        if ($allLogs->isEmpty()) {
            return [];
        }

        // Chia thành các cửa sổ $windowMinutes phút
        $earliest  = Carbon::parse($allLogs->first()->thoi_gian);
        $latest    = Carbon::parse($allLogs->last()->thoi_gian);
        $features  = [];
        $cursor    = $earliest->copy()->floorMinutes($windowMinutes);

        while ($cursor->lte($latest)) {
            $windowEnd   = $cursor->copy()->addMinutes($windowMinutes);
            $windowLogs  = $allLogs->filter(
                fn ($l) => Carbon::parse($l->thoi_gian)->between($cursor, $windowEnd)
            )->groupBy('ip');

            foreach ($windowLogs as $ip => $ipLogs) {
                if (in_array($ip, $whitelist, true)) {
                    continue;
                }
                $features[] = $this->buildForIp((string) $ip, $ipLogs, $cursor, $windowMinutes);
            }

            $cursor->addMinutes($windowMinutes);
        }

        return $features;
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function buildForIp(string $ip, Collection $logs, $from, int $minutes): array
    {
        $scanCount        = $logs->count();
        $distinctTables   = $logs->pluck('ma_ban')->unique()->count();
        $distinctBranches = $logs->pluck('ma_chi_nhanh')->unique()->count();
        $uniqueUserAgents = $logs->pluck('user_agent')->unique()->count();

        // Khoảng cách giữa các lần quét (giây)
        $gaps   = [];
        $values = $logs->values();
        for ($i = 1; $i < $values->count(); $i++) {
            $diff   = strtotime($values[$i]->thoi_gian) - strtotime($values[$i - 1]->thoi_gian);
            $gaps[] = max(0, $diff);
        }

        $avgGap = count($gaps) ? array_sum($gaps) / count($gaps) : 0;
        $minGap = count($gaps) ? min($gaps) : 0;
        $maxGap = count($gaps) ? max($gaps) : 0;
        $stdGap = $this->stdDev($gaps, $avgGap);

        // Đếm đơn thật — join bảng ORDERS theo bàn + chi nhánh + khoảng thời gian
        $tables  = $logs->pluck('ma_ban')->unique()->filter()->values();
        $branch  = $logs->first()->ma_chi_nhanh ?? null;
        $createdOrders = 0;
        if ($tables->isNotEmpty()) {
            $createdOrders = DB::table('orders')
                ->whereIn('ma_ban', $tables)
                ->when($branch, fn ($q) => $q->where('ma_chi_nhanh', $branch))
                ->where(DB::raw("CONCAT(ngay_order, ' ', gio_order)"), '>=', $from)
                ->count();
        }

        $conversionRate = $scanCount > 0 ? round($createdOrders / $scanCount, 4) : 0.0;
        $firstLog       = $logs->first();

        return [
            'ip_hash'                    => $this->hashIp($ip),
            'ip_masked'                  => $this->maskIp($ip),
            'ma_ban'                     => $firstLog->ma_ban ?? null,
            'ma_chi_nhanh'               => $firstLog->ma_chi_nhanh ?? null,
            'window_minutes'             => $minutes,
            'scan_count'                 => $scanCount,
            'distinct_tables'            => $distinctTables,
            'distinct_branches'          => $distinctBranches,
            'unique_user_agents'         => $uniqueUserAgents,
            'avg_seconds_between_scans'  => round($avgGap, 4),
            'min_seconds_between_scans'  => $minGap,
            'max_seconds_between_scans'  => $maxGap,
            'std_seconds_between_scans'  => $stdGap,
            'created_orders'             => $createdOrders,
            'conversion_rate'            => $conversionRate,
            'suspicious_user_agent_flag' => $this->hasSuspiciousAgent($logs->pluck('user_agent')->toArray()) ? 1 : 0,
            'night_scan_flag'            => $this->hasNightScan($logs) ? 1 : 0,
        ];
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function stdDev(array $values, float $mean): float
    {
        $n = count($values);
        if ($n < 2) {
            return 0.0;
        }
        $sum = 0.0;
        foreach ($values as $v) {
            $sum += ($v - $mean) ** 2;
        }
        return round(sqrt($sum / $n), 4);
    }

    private function hasSuspiciousAgent(array $agents): bool
    {
        $keywords = ['bot', 'crawler', 'spider', 'curl', 'wget', 'python', 'scrapy', 'postman', 'httpclient'];
        foreach ($agents as $agent) {
            $lower = strtolower((string) $agent);
            if ($lower === '') {
                return true; // user-agent rỗng — rất đáng ngờ
            }
            foreach ($keywords as $kw) {
                if (str_contains($lower, $kw)) {
                    return true;
                }
            }
        }
        return false;
    }

    private function hasNightScan(Collection $logs): bool
    {
        $start = (int) config('qr_anomaly.operating_hours.start', 8);
        $end   = (int) config('qr_anomaly.operating_hours.end', 23);
        $tz    = config('app.timezone', 'Asia/Ho_Chi_Minh');

        foreach ($logs as $log) {
            $hour = (int) Carbon::parse($log->thoi_gian)->setTimezone($tz)->format('H');
            if ($hour < $start || $hour >= $end) {
                return true;
            }
        }
        return false;
    }

    private function maskIp(string $ip): string
    {
        if (str_contains($ip, ':')) {
            // IPv6 — giữ 2 nhóm đầu
            $parts = explode(':', $ip);
            return implode(':', array_slice($parts, 0, 2)) . ':xxxx';
        }
        // IPv4 — che octet cuối
        return (string) preg_replace('/\.\d+$/', '.xxx', $ip);
    }

    private function hashIp(string $ip): string
    {
        return hash_hmac('sha256', $ip, (string) config('app.key'));
    }
}
