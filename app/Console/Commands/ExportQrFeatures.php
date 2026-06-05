<?php

namespace App\Console\Commands;

use App\Services\QrScanFeatureService;
use Illuminate\Console\Command;

class ExportQrFeatures extends Command
{
    protected $signature = 'scan:export-features
                            {--days=14      : Số ngày lịch sử để tổng hợp}
                            {--window=5     : Kích thước cửa sổ mỗi mẫu (phút)}
                            {--output=      : Đường dẫn file CSV đầu ra (mặc định: ml/qr_scan_features.csv)}';

    protected $description = 'Xuất đặc trưng QR scan ra CSV để train Isolation Forest';

    public function handle(QrScanFeatureService $service): int
    {
        $days   = (int) $this->option('days');
        $window = (int) $this->option('window');
        $output = $this->option('output') ?: base_path('ml/qr_scan_features.csv');

        $this->info("Đang tổng hợp đặc trưng {$days} ngày, cửa sổ {$window} phút...");

        $rows = $service->buildHistoricalFeatures(days: $days, windowMinutes: $window);

        if (empty($rows)) {
            $this->warn('Không có dữ liệu để xuất. Hãy đảm bảo SCAN_LOG có bản ghi.');
            return self::FAILURE;
        }

        // Đảm bảo thư mục tồn tại
        $dir = dirname($output);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $fp = fopen($output, 'w');
        if ($fp === false) {
            $this->error("Không mở được file: {$output}");
            return self::FAILURE;
        }

        // Header — dùng keys của dòng đầu nhưng loại bỏ các cột định danh (không phải feature ML)
        $mlColumns = [
            'scan_count', 'distinct_tables', 'distinct_branches', 'unique_user_agents',
            'avg_seconds_between_scans', 'min_seconds_between_scans',
            'max_seconds_between_scans', 'std_seconds_between_scans',
            'created_orders', 'conversion_rate',
            'suspicious_user_agent_flag', 'night_scan_flag',
        ];

        fputcsv($fp, $mlColumns);
        foreach ($rows as $row) {
            fputcsv($fp, array_map(fn ($col) => $row[$col] ?? 0, $mlColumns));
        }
        fclose($fp);

        $this->info('Đã xuất ' . count($rows) . ' dòng ra ' . $output);
        return self::SUCCESS;
    }
}
