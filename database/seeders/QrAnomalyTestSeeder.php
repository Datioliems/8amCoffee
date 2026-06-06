<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * ╔══════════════════════════════════════════════════════════════════════════╗
 * ║  Bộ dữ liệu kiểm thử: Phát hiện quét mã QR bất thường                  ║
 * ║  Targets: bảng SCAN_LOG (dữ liệu vào) + SCAN_ANOMALY_ALERT (kết quả)   ║
 * ╚══════════════════════════════════════════════════════════════════════════╝
 *
 * Cách chạy:
 *   php artisan db:seed --class=QrAnomalyTestSeeder
 *
 * Kiểm thử tích hợp (sau khi seed SCAN_LOG):
 *   php artisan scan:analyze-anomalies --minutes=60
 *
 * Cách dọn dữ liệu test:
 *   DELETE FROM SCAN_ANOMALY_FEEDBACK WHERE alert_id IN (SELECT id FROM SCAN_ANOMALY_ALERT WHERE ip_masked LIKE '192.0.2.%' OR ip_masked LIKE '198.51.100.%');
 *   DELETE FROM SCAN_ANOMALY_ALERT WHERE ip_masked LIKE '192.0.2.%' OR ip_masked LIKE '198.51.100.%';
 *   DELETE FROM SCAN_LOG WHERE ip LIKE '192.0.2.%' OR ip LIKE '198.51.100.%';
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * Ngưỡng phát hiện (config qr_anomaly):
 *   store_threshold  = 40   → lưu vào SCAN_ANOMALY_ALERT
 *   alert_threshold  = 60   → đánh dấu is_alert = true
 *
 * Bảng điểm risk (rule-based):
 *   suspicious_user_agent_flag    → +40  (bot/crawler/curl/wget/python/scrapy/postman/httpclient/empty UA)
 *   scan_count ≥ 100              → +60
 *   scan_count ≥ 50               → +40
 *   distinct_tables ≥ 20          → +40
 *   distinct_branches ≥ 2         → +30
 *   scan_count ≥ 20 + 0 orders    → +20
 *   scan_count ≥ 10 + std_gap ≤ 1 → +20  (bot timing đều đặn)
 *   night_scan_flag               → +10  (ngoài giờ config: 08–23)
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * Kịch bản kiểm thử:
 *
 * ID | IP (masked)     | Mô tả                              | Score | Lưu? | Alert?
 * TC1 192.0.2.1        Khách bình thường                      0      ✗     ✗
 * TC2 192.0.2.1x       Bot nhẹ – UA python                    40     ✓     ✗
 * TC3 192.0.2.2x       Tần suất cao, không tạo đơn            60     ✓     ✓
 * TC4 192.0.2.3x       Tấn công nhiều bàn / 2 chi nhánh       100    ✓     ✓ critical
 * TC5 192.0.2.4x       Bot Postman + timing đều               60     ✓     ✓
 * TC6 192.0.2.5x       Quét đêm nhẹ (chỉ +10)                 10     ✗     ✗
 * TC7 192.0.2.6x       Bot curl + quét đêm                    70     ✓     ✓
 * TC8 198.51.100.1     ML only detection (rule thấp)           –      ✓     ✓  method=ml
 * TC9 198.51.100.2     Hybrid (rule=65 + ML=80)                80     ✓     ✓  method=hybrid
 * TC10 198.51.100.3    False positive đã xác nhận               –      ✓     ✓  feedback=false positive
 */
class QrAnomalyTestSeeder extends Seeder
{
    // IP dùng TEST-NET ranges (RFC 5737 / RFC 3849) để tránh xung đột dữ liệu thật
    private const IPS = [
        'TC1_NORMAL'       => '192.0.2.1',
        'TC2_PYTHON'       => '192.0.2.10',
        'TC3_HIGH_FREQ'    => '192.0.2.20',
        'TC4_MULTI_TABLE'  => '192.0.2.30',
        'TC5_POSTMAN'      => '192.0.2.40',
        'TC6_NIGHT_LIGHT'  => '192.0.2.50',
        'TC7_CURL_NIGHT'   => '192.0.2.60',
        'TC8_ML'           => '198.51.100.1',
        'TC9_HYBRID'       => '198.51.100.2',
        'TC10_FALSE_POS'   => '198.51.100.3',
    ];

    private const UA_NORMAL  = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36';
    private const UA_PYTHON  = 'python-requests/2.28.0';
    private const UA_POSTMAN = 'PostmanRuntime/7.32.3';
    private const UA_CURL    = 'curl/7.88.1';

    public function run(): void
    {
        $this->command->info('Seeding SCAN_LOG...');
        $this->seedScanLog();

        $this->command->info('Seeding SCAN_ANOMALY_ALERT...');
        $this->seedAlerts();

        $this->command->info('Seeding SCAN_ANOMALY_FEEDBACK...');
        $this->seedFeedbacks();

        $this->command->info('✅ Hoàn tất seeding dữ liệu kiểm thử QR anomaly.');
        $this->printSummary();
    }

    // =========================================================================
    // SCAN_LOG — dữ liệu quét QR thô (input cho QrScanFeatureService)
    // =========================================================================

    private function seedScanLog(): void
    {
        $recentBase = now()->subMinutes(55); // tất cả nằm trong cửa sổ --minutes=60

        // ── TC1: Khách bình thường ──────────────────────────────────────────
        // 4 lần quét, B001–B002, CN001, UA trình duyệt thật, cách nhau 60–90 giây
        // Tạo 2 đơn hàng thật → conversion_rate = 0.5
        // Score: 0 → KHÔNG lưu (dưới store_threshold=40)
        $this->insertScans(self::IPS['TC1_NORMAL'], self::UA_NORMAL, 'CN001',
            [
                ['ma_ban' => 'B001', 'offset_sec' => 3300],
                ['ma_ban' => 'B001', 'offset_sec' => 3240],
                ['ma_ban' => 'B002', 'offset_sec' => 3150],
                ['ma_ban' => 'B002', 'offset_sec' => 3060],
            ]
        );

        // ── TC2: Bot nhẹ – UA python ────────────────────────────────────────
        // 5 lần quét, python-requests UA, cách nhau ~12 giây
        // Score: +40 (suspicious UA) = 40 → Lưu, KHÔNG alert
        $this->insertScans(self::IPS['TC2_PYTHON'], self::UA_PYTHON, 'CN001',
            collect(range(0, 4))->map(fn ($i) => [
                'ma_ban'     => 'B001',
                'offset_sec' => 3250 - $i * 12,
            ])->all()
        );

        // ── TC3: Tần suất cao, không tạo đơn ───────────────────────────────
        // 55 lần quét, B002–B004, UA bình thường, cách nhau 1 giây
        // Score: +40 (≥50 scans) + 20 (≥20 scans + 0 orders) = 60 → Lưu + ALERT
        $tc3Scans = collect(range(0, 54))->map(fn ($i) => [
            'ma_ban'     => 'B00' . (($i % 3) + 2), // B002, B003, B004
            'offset_sec' => 3200 - $i,
        ])->all();
        $this->insertScans(self::IPS['TC3_HIGH_FREQ'], self::UA_NORMAL, 'CN001', $tc3Scans);

        // ── TC4: Tấn công nhiều bàn + 2 chi nhánh ──────────────────────────
        // 110 lần quét, 20 bàn khác nhau (B001–B020), CN001+CN002
        // Score: +60 (≥100) + 40 (≥20 tables) + 30 (≥2 branches) + 20 (≥20 scans + 0 orders)
        //      = 150 → capped 100 → critical → ALERT
        $tc4Scans = collect(range(0, 109))->map(fn ($i) => [
            'ma_ban'       => 'B' . str_pad((string) (($i % 20) + 1), 3, '0', STR_PAD_LEFT),
            'offset_sec'   => 3180 - $i,
            'ma_chi_nhanh' => ($i % 5 === 0) ? 'CN002' : 'CN001', // 22 lần CN002, 88 lần CN001
        ])->all();
        $this->insertScansWithBranch(self::IPS['TC4_MULTI_TABLE'], self::UA_NORMAL, $tc4Scans);

        // ── TC5: Postman – timing đều như đồng hồ ──────────────────────────
        // 12 lần quét, cách nhau 0.5 giây → std_gap ≈ 0 → nghi bot
        // Score: +40 (Postman UA) + 20 (≥10 scans + std≤1.0) = 60 → ALERT
        // Lưu ý: Carbon chỉ có độ chính xác giây → dùng 1 giây thay cho 0.5 giây
        // để đơn giản hóa; std_gap = 0 khi tất cả bằng nhau
        $this->insertScans(self::IPS['TC5_POSTMAN'], self::UA_POSTMAN, 'CN001',
            collect(range(0, 11))->map(fn ($i) => [
                'ma_ban'     => 'B001',
                'offset_sec' => 3100 - $i, // cách đúng 1 giây → std=0
            ])->all()
        );

        // ── TC6: Quét đêm nhẹ ──────────────────────────────────────────────
        // 5 lần quét lúc 02:30 sáng, UA bình thường
        // Score: +10 (night scan) = 10 → KHÔNG lưu (dưới store_threshold=40)
        // → Dùng để verify hệ thống bỏ qua đúng các trường hợp thấp điểm
        $nightBase = Carbon::today()->setTime(2, 30, 0);
        $tc6Scans = collect(range(0, 4))->map(fn ($i) => [
            'ma_ban'     => 'B001',
            'thoi_gian'  => $nightBase->copy()->addSeconds($i * 60)->toDateTimeString(),
        ])->all();
        $this->insertScansAbsolute(self::IPS['TC6_NIGHT_LIGHT'], self::UA_NORMAL, 'CN001', $tc6Scans);

        // ── TC7: curl + quét đêm + timing đều ──────────────────────────────
        // 15 lần quét lúc 02:30 sáng, curl UA, 1 giây/lần
        // Score: +40 (curl UA) + 20 (≥10 scans + std=0) + 10 (night) = 70 → ALERT
        $tc7Scans = collect(range(0, 14))->map(fn ($i) => [
            'ma_ban'    => 'B001',
            'thoi_gian' => $nightBase->copy()->addSeconds($i)->toDateTimeString(),
        ])->all();
        $this->insertScansAbsolute(self::IPS['TC7_CURL_NIGHT'], self::UA_CURL, 'CN001', $tc7Scans);
    }

    // =========================================================================
    // SCAN_ANOMALY_ALERT — kết quả phân tích đã tính sẵn (test dashboard ngay)
    // =========================================================================

    private function seedAlerts(): void
    {
        $now   = now()->toDateTimeString();
        $today = now()->subMinutes(30)->toDateTimeString();
        $night = Carbon::today()->setTime(2, 35, 0)->toDateTimeString();

        // TC2 – Bot nhẹ (python UA, score=40, medium, NO alert)
        $tc2 = $this->makeAlert(
            ip: self::IPS['TC2_PYTHON'],
            maBan: 'B001', maChiNhanh: 'CN001',
            scanCount: 5, tables: 1, branches: 1, agents: 1, orders: 0,
            convRate: 0.00,
            anomalyScore: null, riskScore: 40, riskLevel: 'medium',
            isAlert: false, method: 'rule',
            reason: 'User agent có dấu hiệu bot hoặc công cụ tự động.',
            suggested: 'Tiếp tục theo dõi trong các chu kỳ tiếp theo.',
            feature: ['scan_count' => 5, 'distinct_tables' => 1, 'suspicious_user_agent_flag' => 1, 'user_agent_sample' => self::UA_PYTHON],
            detectedAt: $today
        );

        // TC3 – Tần suất cao (55 scans, no orders, score=60, high, ALERT)
        $tc3 = $this->makeAlert(
            ip: self::IPS['TC3_HIGH_FREQ'],
            maBan: 'B002', maChiNhanh: 'CN001',
            scanCount: 55, tables: 3, branches: 1, agents: 1, orders: 0,
            convRate: 0.00,
            anomalyScore: null, riskScore: 60, riskLevel: 'high',
            isAlert: true, method: 'rule',
            reason: 'IP quét QR với tần suất rất cao (>= 50 lượt). Quét nhiều lần nhưng không phát sinh đơn hàng.',
            suggested: 'Nên kiểm tra lịch sử quét và theo dõi IP này.',
            feature: ['scan_count' => 55, 'distinct_tables' => 3, 'created_orders' => 0, 'suspicious_user_agent_flag' => 0],
            detectedAt: $today
        );

        // TC4 – Tấn công nhiều bàn (110 scans, 20 tables, 2 branches, score=100, critical, ALERT)
        $tc4 = $this->makeAlert(
            ip: self::IPS['TC4_MULTI_TABLE'],
            maBan: 'B001', maChiNhanh: 'CN001',
            scanCount: 110, tables: 20, branches: 2, agents: 1, orders: 0,
            convRate: 0.00,
            anomalyScore: null, riskScore: 100, riskLevel: 'critical',
            isAlert: true, method: 'rule',
            reason: 'IP quét QR với tần suất cực cao (>= 100 lượt). IP quét rất nhiều bàn khác nhau (>= 20 bàn). IP xuất hiện tại nhiều chi nhánh trong cùng cửa sổ thời gian. Quét nhiều lần nhưng không phát sinh đơn hàng.',
            suggested: 'Cần kiểm tra ngay và cân nhắc giới hạn tần suất truy cập IP này.',
            feature: ['scan_count' => 110, 'distinct_tables' => 20, 'distinct_branches' => 2, 'created_orders' => 0, 'suspicious_user_agent_flag' => 0],
            detectedAt: $today
        );

        // TC5 – Bot Postman timing đều (12 scans, std=0, score=60, high, ALERT)
        $tc5 = $this->makeAlert(
            ip: self::IPS['TC5_POSTMAN'],
            maBan: 'B001', maChiNhanh: 'CN001',
            scanCount: 12, tables: 1, branches: 1, agents: 1, orders: 0,
            convRate: 0.00,
            anomalyScore: null, riskScore: 60, riskLevel: 'high',
            isAlert: true, method: 'rule',
            reason: 'User agent có dấu hiệu bot hoặc công cụ tự động. Khoảng cách giữa các lần quét gần như không đổi (nghi bot).',
            suggested: 'Nên kiểm tra lịch sử quét và theo dõi IP này.',
            feature: ['scan_count' => 12, 'std_seconds_between_scans' => 0.0, 'suspicious_user_agent_flag' => 1, 'user_agent_sample' => self::UA_POSTMAN],
            detectedAt: $today
        );

        // TC7 – curl + night (15 scans, std=0, score=70, high, ALERT)
        $tc7 = $this->makeAlert(
            ip: self::IPS['TC7_CURL_NIGHT'],
            maBan: 'B001', maChiNhanh: 'CN001',
            scanCount: 15, tables: 1, branches: 1, agents: 1, orders: 0,
            convRate: 0.00,
            anomalyScore: null, riskScore: 70, riskLevel: 'high',
            isAlert: true, method: 'rule',
            reason: 'User agent có dấu hiệu bot hoặc công cụ tự động. Khoảng cách giữa các lần quét gần như không đổi (nghi bot). Có lượt quét ngoài giờ hoạt động của nhà hàng.',
            suggested: 'Nên kiểm tra lịch sử quét và theo dõi IP này.',
            feature: ['scan_count' => 15, 'std_seconds_between_scans' => 0.0, 'night_scan_flag' => 1, 'suspicious_user_agent_flag' => 1, 'user_agent_sample' => self::UA_CURL],
            detectedAt: $night
        );

        // TC8 – ML only detection (rule thấp, ML cao; score=75, high, ALERT)
        // Trường hợp mà rule-based bỏ sót nhưng ML phát hiện được.
        // Phục vụ kiểm thử logic resolveMethod() → 'ml'
        $tc8 = $this->makeAlert(
            ip: self::IPS['TC8_ML'],
            maBan: 'B003', maChiNhanh: 'CN001',
            scanCount: 22, tables: 2, branches: 1, agents: 3, orders: 0,
            convRate: 0.00,
            anomalyScore: -0.8512, riskScore: 75, riskLevel: 'high',
            isAlert: true, method: 'ml',
            reason: 'Mô hình Machine Learning phát hiện hành vi quét QR lệch chuẩn so với dữ liệu thông thường.',
            suggested: 'Nên kiểm tra lịch sử quét và theo dõi IP này.',
            feature: ['scan_count' => 22, 'distinct_tables' => 2, 'unique_user_agents' => 3, 'created_orders' => 0, 'suspicious_user_agent_flag' => 0],
            detectedAt: $today,
            mlResult: ['trained' => true, 'is_anomaly' => true, 'anomaly_score' => -0.8512, 'risk_score' => 75, 'risk_level' => 'high', 'model_version' => 'iforest_v1.2.0']
        );

        // TC9 – Hybrid (rule=65, ML=80 → method=hybrid, score=80, high, ALERT)
        $tc9 = $this->makeAlert(
            ip: self::IPS['TC9_HYBRID'],
            maBan: 'B005', maChiNhanh: 'CN002',
            scanCount: 58, tables: 4, branches: 1, agents: 2, orders: 0,
            convRate: 0.00,
            anomalyScore: -0.9103, riskScore: 80, riskLevel: 'high',
            isAlert: true, method: 'hybrid',
            reason: 'IP quét QR với tần suất rất cao (>= 50 lượt). Quét nhiều lần nhưng không phát sinh đơn hàng. Mô hình Machine Learning xác nhận hành vi lệch chuẩn.',
            suggested: 'Nên kiểm tra lịch sử quét và theo dõi IP này.',
            feature: ['scan_count' => 58, 'distinct_tables' => 4, 'created_orders' => 0, 'suspicious_user_agent_flag' => 0],
            detectedAt: $today,
            mlResult: ['trained' => true, 'is_anomaly' => true, 'anomaly_score' => -0.9103, 'risk_score' => 80, 'risk_level' => 'high', 'model_version' => 'iforest_v1.2.0']
        );

        // TC10 – False positive (đã xác nhận không nguy hiểm, dùng để train model)
        $tc10 = $this->makeAlert(
            ip: self::IPS['TC10_FALSE_POS'],
            maBan: 'B002', maChiNhanh: 'CN001',
            scanCount: 52, tables: 2, branches: 1, agents: 1, orders: 1,
            convRate: 0.0192,
            anomalyScore: null, riskScore: 60, riskLevel: 'high',
            isAlert: true, method: 'rule',
            reason: 'IP quét QR với tần suất rất cao (>= 50 lượt). Quét nhiều lần nhưng không phát sinh đơn hàng.',
            suggested: 'Nên kiểm tra lịch sử quét và theo dõi IP này.',
            feature: ['scan_count' => 52, 'distinct_tables' => 2, 'created_orders' => 1, 'note' => 'Thực tế là màn hình quảng cáo của cửa hàng quét liên tục để kiểm tra kết nối.'],
            detectedAt: now()->subHours(2)->toDateTimeString()
        );

        // Insert tất cả
        foreach ([$tc2, $tc3, $tc4, $tc5, $tc7, $tc8, $tc9, $tc10] as $alert) {
            DB::table('SCAN_ANOMALY_ALERT')->insertOrIgnore([$alert]);
        }
    }

    // =========================================================================
    // SCAN_ANOMALY_FEEDBACK — phản hồi người dùng (dữ liệu training ML)
    // =========================================================================

    private function seedFeedbacks(): void
    {
        // Lấy ID của TC8 và TC10 vừa insert
        $tc8Id  = DB::table('SCAN_ANOMALY_ALERT')->where('ip_masked', $this->maskIp(self::IPS['TC8_ML']))->value('id');
        $tc10Id = DB::table('SCAN_ANOMALY_ALERT')->where('ip_masked', $this->maskIp(self::IPS['TC10_FALSE_POS']))->value('id');

        if ($tc8Id) {
            DB::table('SCAN_ANOMALY_FEEDBACK')->insertOrIgnore([[
                'alert_id'         => $tc8Id,
                'is_true_positive' => true,
                'reviewed_by'      => 'NV001',
                'note'             => 'Đã xác nhận bot, IP từ datacenter nước ngoài.',
                'created_at'       => now()->subHours(1)->toDateTimeString(),
                'updated_at'       => now()->subHours(1)->toDateTimeString(),
            ]]);
        }

        if ($tc10Id) {
            DB::table('SCAN_ANOMALY_FEEDBACK')->insertOrIgnore([[
                'alert_id'         => $tc10Id,
                'is_true_positive' => false,
                'reviewed_by'      => 'NV002',
                'note'             => 'False positive – màn hình quảng cáo nội bộ của quán, đã thêm IP vào whitelist.',
                'created_at'       => now()->subMinutes(45)->toDateTimeString(),
                'updated_at'       => now()->subMinutes(45)->toDateTimeString(),
            ]]);
        }
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /** Insert scan_log entries theo relative offset (seconds trước now) */
    private function insertScans(string $ip, string $ua, string $chiNhanh, array $scans): void
    {
        $rows = array_map(fn ($s) => [
            'ma_ban'       => $s['ma_ban'],
            'ma_chi_nhanh' => $chiNhanh,
            'ip'           => $ip,
            'user_agent'   => $ua,
            'thoi_gian'    => now()->subSeconds($s['offset_sec'])->toDateTimeString(),
        ], $scans);

        DB::table('SCAN_LOG')->insertOrIgnore($rows);
    }

    /** Cho TC4: mỗi row có thể có ma_chi_nhanh riêng */
    private function insertScansWithBranch(string $ip, string $ua, array $scans): void
    {
        $rows = array_map(fn ($s) => [
            'ma_ban'       => $s['ma_ban'],
            'ma_chi_nhanh' => $s['ma_chi_nhanh'],
            'ip'           => $ip,
            'user_agent'   => $ua,
            'thoi_gian'    => now()->subSeconds($s['offset_sec'])->toDateTimeString(),
        ], $scans);

        DB::table('SCAN_LOG')->insertOrIgnore($rows);
    }

    /** Insert scan_log với timestamp cụ thể (dùng cho night scans) */
    private function insertScansAbsolute(string $ip, string $ua, string $chiNhanh, array $scans): void
    {
        $rows = array_map(fn ($s) => [
            'ma_ban'       => $s['ma_ban'],
            'ma_chi_nhanh' => $chiNhanh,
            'ip'           => $ip,
            'user_agent'   => $ua,
            'thoi_gian'    => $s['thoi_gian'],
        ], $scans);

        DB::table('SCAN_LOG')->insertOrIgnore($rows);
    }

    /** Tạo row data cho SCAN_ANOMALY_ALERT */
    private function makeAlert(
        string  $ip,
        string  $maBan,
        string  $maChiNhanh,
        int     $scanCount,
        int     $tables,
        int     $branches,
        int     $agents,
        int     $orders,
        float   $convRate,
        ?float  $anomalyScore,
        int     $riskScore,
        string  $riskLevel,
        bool    $isAlert,
        string  $method,
        string  $reason,
        string  $suggested,
        array   $feature,
        string  $detectedAt,
        array   $mlResult = ['trained' => false]
    ): array {
        $ruleResult = ['risk_score' => ($method === 'ml' ? 0 : $riskScore), 'reason' => $reason];

        return [
            'ip_hash'            => hash_hmac('sha256', $ip, 'test-key'),
            'ip_masked'          => $this->maskIp($ip),
            'ma_ban'             => $maBan,
            'ma_chi_nhanh'       => $maChiNhanh,
            'scan_count'         => $scanCount,
            'distinct_tables'    => $tables,
            'distinct_branches'  => $branches,
            'unique_user_agents' => $agents,
            'created_orders'     => $orders,
            'conversion_rate'    => $convRate,
            'anomaly_score'      => $anomalyScore,
            'risk_score'         => $riskScore,
            'risk_level'         => $riskLevel,
            'is_alert'           => $isAlert,
            'detection_method'   => $method,
            'reason'             => $reason,
            'suggested_action'   => $suggested,
            'raw_summary'        => json_encode([
                'feature'       => array_merge($feature, [
                    'ip_masked'       => $this->maskIp($ip),
                    'ma_chi_nhanh'    => $maChiNhanh,
                    'window_minutes'  => 60,
                ]),
                'rule_result'   => $ruleResult,
                'ml_result'     => $mlResult,
                'model_version' => $mlResult['model_version'] ?? null,
            ], JSON_UNESCAPED_UNICODE),
            'detected_at'        => $detectedAt,
            'created_at'         => $detectedAt,
            'updated_at'         => $detectedAt,
        ];
    }

    private function maskIp(string $ip): string
    {
        return (string) preg_replace('/\.\d+$/', '.xxx', $ip);
    }

    private function printSummary(): void
    {
        $this->command->line('');
        $this->command->table(
            ['TC', 'IP (masked)', 'Scans', 'Risk', 'Score', 'Alert?', 'Feedback', 'Mô tả'],
            [
                ['TC1',  '192.0.2.xxx',   '4',   '—',        '0',   '✗',  '—',               'Khách bình thường → không lưu'],
                ['TC2',  '192.0.2.1x',    '5',   'medium',  '40',  '✗',  '—',               'python UA → lưu, chưa alert'],
                ['TC3',  '192.0.2.2x',    '55',  'high',    '60',  '✓',  '—',               '55 lần, 0 đơn → alert'],
                ['TC4',  '192.0.2.3x',    '110', 'critical', '100', '✓',  '—',               '20 bàn + 2 CN → critical'],
                ['TC5',  '192.0.2.4x',    '12',  'high',    '60',  '✓',  '—',               'Postman + std=0 → alert'],
                ['TC6',  '192.0.2.5x',    '5',   '—',        '10',  '✗',  '—',               'Đêm 02:30 nhẹ → không lưu'],
                ['TC7',  '192.0.2.6x',    '15',  'high',    '70',  '✓',  '—',               'curl + đêm + std=0 → alert'],
                ['TC8',  '198.51.100.xxx', '22',  'high',    '75',  '✓',  '✓ True positive',  'ML phát hiện, rule bỏ sót'],
                ['TC9',  '198.51.100.xxx', '58',  'high',    '80',  '✓',  '—',               'Hybrid: rule=65 + ML=80'],
                ['TC10', '198.51.100.xxx', '52',  'high',    '60',  '✓',  '✓ False positive', 'Màn hình quảng cáo → FP'],
            ]
        );

        $this->command->line('');
        $this->command->info('Lệnh kiểm thử tích hợp (chạy ngay sau seeder):');
        $this->command->line('  php artisan scan:analyze-anomalies --minutes=60');
        $this->command->line('');
        $this->command->info('Truy vấn kiểm tra kết quả:');
        $this->command->line("  -- Xem tất cả alerts:");
        $this->command->line("  SELECT ip_masked, scan_count, risk_level, risk_score, is_alert, detection_method, detected_at");
        $this->command->line("    FROM SCAN_ANOMALY_ALERT ORDER BY risk_score DESC;");
        $this->command->line('');
        $this->command->line("  -- Xem cả entries đang theo dõi (show_all=1 trong dashboard):");
        $this->command->line("  SELECT ip_masked, risk_score, risk_level, is_alert FROM SCAN_ANOMALY_ALERT WHERE ip_masked LIKE '192.0.2.%' OR ip_masked LIKE '198.51.100.%';");
        $this->command->line('');
        $this->command->line("  -- Xem feedback:");
        $this->command->line("  SELECT a.ip_masked, f.is_true_positive, f.reviewed_by, f.note FROM SCAN_ANOMALY_FEEDBACK f JOIN SCAN_ANOMALY_ALERT a ON a.id=f.alert_id;");
        $this->command->line('');
        $this->command->line("  -- Verify TC6 không được lưu (score=10 < store_threshold=40):");
        $this->command->line("  SELECT COUNT(*) FROM SCAN_ANOMALY_ALERT WHERE ip_masked='192.0.2.xxx'; -- phải = 0");
        $this->command->line('');
        $this->command->line("  -- Stats dashboard:");
        $this->command->line("  SELECT risk_level, COUNT(*) as cnt FROM SCAN_ANOMALY_ALERT WHERE is_alert=1 GROUP BY risk_level;");
    }
}
