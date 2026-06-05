<?php

return [
    // Mã website (Terminal ID) & chuỗi bí mật từ VNPay sandbox.
    'tmn_code'    => env('VNPAY_TMN_CODE', ''),
    'hash_secret' => env('VNPAY_HASH_SECRET', ''),

    // Endpoint cổng thanh toán (sandbox mặc định).
    'url'     => env('VNPAY_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'),
    'api_url' => env('VNPAY_API_URL', 'https://sandbox.vnpayment.vn/merchant_webapi/api/transaction'),

    // URL nhận kết quả trả về (để trống = tự dùng route payment.vnpay.return).
    'return_url' => env('VNPAY_RETURN_URL'),

    // Số phút cho phép hoàn tất thanh toán trước khi hết hạn.
    'expire_minutes' => (int) env('VNPAY_EXPIRE_MINUTES', 15),
];
