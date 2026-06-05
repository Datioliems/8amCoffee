<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('about-8am', function () {
    $this->info('8AM Coffee QR Order System');
})->purpose('Show project information');

// Mỗi ngày dọn tài khoản nhân viên chưa kích hoạt (email không tồn tại / chưa xác nhận).
Schedule::command('accounts:purge-unconfirmed')->dailyAt('03:00');

// ── QR Anomaly Detection ──────────────────────────────────────────────────
// Phân tích bất thường mỗi 5 phút — rule-based (luôn chạy) + ML (khi đã có model).
Schedule::command('scan:analyze-anomalies --minutes=5')->everyFiveMinutes();

// Mỗi đêm: export CSV feature → re-train Isolation Forest (tránh data drift).
Schedule::command('scan:export-features --days=14 --window=5')->dailyAt('02:00');
Schedule::exec(config('qr_anomaly.python_bin', 'python3') . ' ' . base_path('ml/train_qr_anomaly_model.py'))
         ->dailyAt('02:10');

