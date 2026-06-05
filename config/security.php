<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Chống brute-force đăng nhập
    |--------------------------------------------------------------------------
    */
    // Số lần nhập sai mật khẩu tối đa trước khi khoá tài khoản tạm thời.
    'max_login_attempts' => (int) env('LOGIN_MAX_ATTEMPTS', 5),

    // Thời gian khoá tài khoản (phút) sau khi vượt ngưỡng.
    'lockout_minutes' => (int) env('LOGIN_LOCKOUT_MINUTES', 10),

    // Giới hạn theo IP (Laravel RateLimiter): số request /phút tới /login.
    'ip_rate_per_minute' => (int) env('LOGIN_IP_RATE', 20),

    /*
    |--------------------------------------------------------------------------
    | Xác thực 2 lớp (OTP qua email)
    |--------------------------------------------------------------------------
    */
    // Bật/tắt 2FA toàn hệ thống. Tắt khi cần demo nhanh không có email.
    'two_factor_enabled' => filter_var(env('TWO_FACTOR_ENABLED', false), FILTER_VALIDATE_BOOL),

    // Số phút OTP còn hiệu lực.
    'otp_minutes' => (int) env('OTP_MINUTES', 5),

    // Số lần nhập OTP sai tối đa trước khi huỷ phiên xác thực.
    'otp_max_attempts' => (int) env('OTP_MAX_ATTEMPTS', 5),

    // Số ký tự của OTP.
    'otp_length' => 6,

    /*
    |--------------------------------------------------------------------------
    | Kích hoạt tài khoản qua email
    |--------------------------------------------------------------------------
    */
    // Link kích hoạt trong email còn hiệu lực bao nhiêu giờ.
    'activation_hours' => (int) env('ACCOUNT_ACTIVATION_HOURS', 72),

    // Tài khoản tạo nhưng CHƯA kích hoạt quá số ngày này sẽ bị cronjob xoá.
    'purge_unconfirmed_days' => (int) env('PURGE_UNCONFIRMED_DAYS', 7),

    // Gửi email CẢNH BÁO trước khi xoá bao nhiêu ngày (thông báo trước).
    'purge_warn_days' => (int) env('PURGE_WARN_DAYS', 2),
];
