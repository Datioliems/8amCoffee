<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class QrAnomalyMlService
{
    /**
     * Gửi một mảng feature dicts sang Python script để predict batch.
     * Load model một lần, nhanh hơn nhiều so với gọi từng IP.
     *
     * Trả về mảng cùng độ dài với $features.
     * Mỗi phần tử có dạng:
     *   ['trained' => true,  'is_anomaly' => bool, 'anomaly_score' => float,
     *    'risk_score' => int, 'risk_level' => string, 'model_version' => string]
     * Hoặc khi cold-start / lỗi:
     *   ['trained' => false]
     *
     * @param  array<int, array> $features
     * @return array<int, array>
     */
    public function predictBatch(array $features): array
    {
        if (empty($features)) {
            return [];
        }

        $python  = (string) config('qr_anomaly.python_bin', 'python3');
        $script  = base_path('ml/predict_qr_anomaly.py');
        $fallback = array_fill(0, count($features), ['trained' => false]);

        if (! file_exists($script)) {
            Log::warning('QR ML: predict script not found', ['path' => $script]);
            return $fallback;
        }

        $process = new Process([$python, $script]);
        $process->setWorkingDirectory(base_path());
        $process->setInput(json_encode(array_values($features)));
        $process->setTimeout(60);
        $process->run();

        if (! $process->isSuccessful()) {
            Log::error('QR ML: predict failed', [
                'exit_code' => $process->getExitCode(),
                'stderr'    => mb_substr($process->getErrorOutput(), 0, 500),
            ]);
            return $fallback;
        }

        $decoded = json_decode($process->getOutput(), true);

        if (! is_array($decoded) || count($decoded) !== count($features)) {
            Log::error('QR ML: unexpected output', [
                'output' => mb_substr($process->getOutput(), 0, 300),
            ]);
            return $fallback;
        }

        return $decoded;
    }
}
