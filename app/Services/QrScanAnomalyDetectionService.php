<?php

namespace App\Services;

use App\Models\ScanAnomalyAlert;

class QrScanAnomalyDetectionService
{
    // Ngưỡng lấy từ config — nhất quán với dashboard
    private int $alertThreshold;
    private int $storeThreshold;

    public function __construct(
        private readonly QrScanFeatureService $featureService,
        private readonly QrAnomalyMlService   $mlService,
    ) {
        $this->alertThreshold = (int) config('qr_anomaly.alert_threshold', 60);
        $this->storeThreshold = (int) config('qr_anomaly.store_threshold', 40);
    }

    /**
     * Phân tích toàn bộ IP trong cửa sổ $minutes phút gần nhất.
     * Mỗi IP bất thường (>= storeThreshold) được lưu vào SCAN_ANOMALY_ALERT.
     */
    public function analyze(int $minutes = 5): void
    {
        $features = $this->featureService->buildFeatures($minutes);
        if (empty($features)) {
            return;
        }

        // Predict batch — load Python model một lần cho tất cả IP
        $mlResults = $this->mlService->predictBatch($features);

        foreach ($features as $i => $feature) {
            $rule       = $this->evaluateRules($feature);
            $ml         = $mlResults[$i] ?? ['trained' => false];

            $ruleScore  = $rule['risk_score'];
            $mlScore    = ($ml['trained'] ?? false) ? (int) ($ml['risk_score'] ?? 0) : 0;
            $riskScore  = max($ruleScore, $mlScore);

            if ($riskScore < $this->storeThreshold) {
                continue; // dưới ngưỡng theo dõi → bỏ qua
            }

            $method = $this->resolveMethod($ruleScore, $mlScore);
            $this->saveAlert($feature, $rule, $ml, $riskScore, $method);
        }
    }

    // ── Rule-based layer ─────────────────────────────────────────────────────

    /**
     * @return array{risk_score: int, reason: string}
     */
    private function evaluateRules(array $f): array
    {
        $score   = 0;
        $reasons = [];

        if ($f['suspicious_user_agent_flag']) {
            $score   += 40;
            $reasons[] = 'User agent có dấu hiệu bot hoặc công cụ tự động.';
        }

        if ($f['scan_count'] >= 100) {
            $score   += 60;
            $reasons[] = 'IP quét QR với tần suất cực cao (>= 100 lượt).';
        } elseif ($f['scan_count'] >= 50) {
            $score   += 40;
            $reasons[] = 'IP quét QR với tần suất rất cao (>= 50 lượt).';
        }

        if ($f['distinct_tables'] >= 20) {
            $score   += 40;
            $reasons[] = 'IP quét rất nhiều bàn khác nhau (>= 20 bàn).';
        }

        if ($f['distinct_branches'] >= 2) {
            $score   += 30;
            $reasons[] = 'IP xuất hiện tại nhiều chi nhánh trong cùng cửa sổ thời gian.';
        }

        // Quét nhiều nhưng không tạo đơn (phân biệt bot vs khách thật)
        if ($f['scan_count'] >= 20 && $f['created_orders'] === 0) {
            $score   += 20;
            $reasons[] = 'Quét nhiều lần nhưng không phát sinh đơn hàng.';
        }

        // Khoảng cách quét cực kỳ đều → nghi bot
        if ($f['scan_count'] >= 10 && (float) $f['std_seconds_between_scans'] <= 1.0) {
            $score   += 20;
            $reasons[] = 'Khoảng cách giữa các lần quét gần như không đổi (nghi bot).';
        }

        if ($f['night_scan_flag']) {
            $score   += 10;
            $reasons[] = 'Có lượt quét ngoài giờ hoạt động của nhà hàng.';
        }

        return [
            'risk_score' => min($score, 100),
            'reason'     => implode(' ', $reasons),
        ];
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function resolveMethod(int $ruleScore, int $mlScore): string
    {
        $ruleHigh = $ruleScore >= $this->alertThreshold;
        $mlHigh   = $mlScore   >= $this->alertThreshold;

        if ($ruleHigh && $mlHigh) {
            return 'hybrid';
        }
        if ($mlHigh) {
            return 'ml';
        }
        if ($ruleHigh) {
            return 'rule';
        }
        // Cả hai dưới ngưỡng alert nhưng trên store — chọn nguồn cho điểm cao hơn
        return $ruleScore >= $mlScore ? 'rule' : 'ml';
    }

    private function saveAlert(array $feature, array $rule, array $ml, int $riskScore, string $method): void
    {
        // Chống spam cảnh báo cùng IP trong 10 phút
        $exists = ScanAnomalyAlert::where('ip_hash', $feature['ip_hash'])
            ->where('detected_at', '>=', now()->subMinutes(10))
            ->exists();
        if ($exists) {
            return;
        }

        $reason = $rule['reason'];
        if (($ml['is_anomaly'] ?? false) && $reason === '') {
            $reason = 'Mô hình Machine Learning phát hiện hành vi quét QR lệch chuẩn so với dữ liệu thông thường.';
        }

        $riskLevel = $this->levelFromScore($riskScore);
        $isAlert   = $riskScore >= $this->alertThreshold;

        $suggestedAction = match ($riskLevel) {
            'critical' => 'Cần kiểm tra ngay và cân nhắc giới hạn tần suất truy cập IP này.',
            'high'     => 'Nên kiểm tra lịch sử quét và theo dõi IP này.',
            'medium'   => 'Tiếp tục theo dõi trong các chu kỳ tiếp theo.',
            default    => 'Chưa cần xử lý.',
        };

        ScanAnomalyAlert::create([
            'ip_hash'            => $feature['ip_hash'],
            'ip_masked'          => $feature['ip_masked'],
            'ma_ban'             => $feature['ma_ban'],
            'ma_chi_nhanh'       => $feature['ma_chi_nhanh'],
            'scan_count'         => $feature['scan_count'],
            'distinct_tables'    => $feature['distinct_tables'],
            'distinct_branches'  => $feature['distinct_branches'],
            'unique_user_agents' => $feature['unique_user_agents'],
            'created_orders'     => $feature['created_orders'],
            'conversion_rate'    => $feature['conversion_rate'],
            'anomaly_score'      => $ml['anomaly_score'] ?? null,
            'risk_score'         => $riskScore,
            'risk_level'         => $riskLevel,
            'is_alert'           => $isAlert,
            'detection_method'   => $method,
            'reason'             => $reason,
            'suggested_action'   => $suggestedAction,
            'raw_summary'        => [
                'feature'       => $feature,
                'rule_result'   => $rule,
                'ml_result'     => $ml,
                'model_version' => $ml['model_version'] ?? null,
            ],
            'detected_at'        => now(),
        ]);
    }

    private function levelFromScore(int $score): string
    {
        return match (true) {
            $score >= 80 => 'critical',
            $score >= 60 => 'high',
            $score >= 40 => 'medium',
            default      => 'low',
        };
    }
}
