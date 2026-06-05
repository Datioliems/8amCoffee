<?php

namespace App\Console\Commands;

use App\Services\QrScanAnomalyDetectionService;
use Illuminate\Console\Command;

class AnalyzeQrScanAnomalies extends Command
{
    protected $signature = 'scan:analyze-anomalies
                            {--minutes=5 : Kích thước cửa sổ phân tích (phút)}';

    protected $description = 'Phân tích log quét QR và phát hiện hành vi bất thường (rule-based + ML hybrid)';

    public function handle(QrScanAnomalyDetectionService $service): int
    {
        $minutes = (int) $this->option('minutes');
        $this->info("Đang phân tích cửa sổ {$minutes} phút gần nhất...");

        $service->analyze($minutes);

        $this->info('Hoàn thành phân tích QR anomaly.');
        return self::SUCCESS;
    }
}
