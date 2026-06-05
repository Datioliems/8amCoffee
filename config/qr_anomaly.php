<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Whitelist IP nội bộ
    |--------------------------------------------------------------------------
    | Các IP thuộc wifi chi nhánh hoặc thiết bị nhân viên sẽ được bỏ qua
    | hoặc hạ một bậc risk, tránh cảnh báo nhầm do dùng chung NAT.
    */
    'whitelist_ips' => array_filter(
        explode(',', env('QR_ANOMALY_WHITELIST_IPS', ''))
    ),

    /*
    |--------------------------------------------------------------------------
    | Python binary
    |--------------------------------------------------------------------------
    */
    'python_bin' => env('PYTHON_BIN', 'python3'),

    /*
    |--------------------------------------------------------------------------
    | Cold-start guard: số dòng tối thiểu để train model
    |--------------------------------------------------------------------------
    */
    'min_train_rows' => (int) env('QR_ANOMALY_MIN_TRAIN_ROWS', 200),

    /*
    |--------------------------------------------------------------------------
    | Giờ hoạt động của nhà hàng (dùng để tính night_scan_flag)
    |--------------------------------------------------------------------------
    */
    'operating_hours' => [
        'start' => (int) env('QR_ANOMALY_HOUR_START', 8),
        'end'   => (int) env('QR_ANOMALY_HOUR_END', 23),
    ],

    /*
    |--------------------------------------------------------------------------
    | Ngưỡng risk để lưu và cảnh báo
    |--------------------------------------------------------------------------
    | store_threshold  : >= medium (40) mới lưu vào bảng để theo dõi
    | alert_threshold  : >= high  (60) mới bật cờ is_alert = true
    */
    'store_threshold' => (int) env('QR_ANOMALY_STORE_THRESHOLD', 40),
    'alert_threshold' => (int) env('QR_ANOMALY_ALERT_THRESHOLD', 60),
];
