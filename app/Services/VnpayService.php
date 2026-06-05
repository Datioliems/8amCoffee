<?php

namespace App\Services;

use App\Models\ThanhToanOnline;
use Carbon\Carbon;

/**
 * Tích hợp cổng thanh toán VNPay (chuẩn 2.1.0).
 *
 *  - buildPaymentUrl(): tạo URL chuyển hướng sang VNPay (ký HMAC-SHA512).
 *  - validateChecksum(): xác minh chữ ký dữ liệu VNPay trả về (return / IPN).
 *
 * Tài liệu: https://sandbox.vnpayment.vn/apis/docs/thanh-toan-pay/pay.html
 */
class VnpayService
{
    public function configured(): bool
    {
        return !empty(config('vnpay.tmn_code')) && !empty(config('vnpay.hash_secret'));
    }

    /**
     * Sinh mã giao dịch (vnp_TxnRef) duy nhất.
     */
    public function newTxnRef(): string
    {
        do {
            $ref = now()->format('YmdHis') . random_int(100, 999);
        } while (ThanhToanOnline::where('ma_giao_dich', $ref)->exists());
        return $ref;
    }

    /**
     * Tạo URL thanh toán VNPay.
     *
     * @param int    $amountVnd Số tiền (VND, chưa nhân 100).
     */
    public function buildPaymentUrl(string $txnRef, int $amountVnd, string $orderInfo, string $ipAddr, string $returnUrl): string
    {
        $now = Carbon::now();

        $data = [
            'vnp_Version'    => '2.1.0',
            'vnp_Command'    => 'pay',
            'vnp_TmnCode'    => config('vnpay.tmn_code'),
            'vnp_Amount'     => $amountVnd * 100,          // VNPay tính theo đơn vị x100
            'vnp_CreateDate' => $now->format('YmdHis'),
            'vnp_CurrCode'   => 'VND',
            'vnp_IpAddr'     => $ipAddr,
            'vnp_Locale'     => 'vn',
            'vnp_OrderInfo'  => $orderInfo,
            'vnp_OrderType'  => 'other',
            'vnp_ReturnUrl'  => $returnUrl,
            'vnp_TxnRef'     => $txnRef,
            'vnp_ExpireDate' => $now->copy()->addMinutes(config('vnpay.expire_minutes'))->format('YmdHis'),
        ];

        ksort($data);

        $hashData = [];
        $query    = [];
        foreach ($data as $key => $value) {
            $hashData[] = urlencode($key) . '=' . urlencode((string) $value);
            $query[]    = urlencode($key) . '=' . urlencode((string) $value);
        }
        $hashData = implode('&', $hashData);

        $secureHash = hash_hmac('sha512', $hashData, (string) config('vnpay.hash_secret'));

        return config('vnpay.url') . '?' . implode('&', $query) . '&vnp_SecureHash=' . $secureHash;
    }

    /**
     * Xác minh chữ ký dữ liệu trả về từ VNPay.
     *
     * @param array<string,string> $params Toàn bộ query VNPay gửi về.
     */
    public function validateChecksum(array $params): bool
    {
        if (empty($params['vnp_SecureHash'])) return false;

        $received = $params['vnp_SecureHash'];
        unset($params['vnp_SecureHash'], $params['vnp_SecureHashType']);

        // Chỉ giữ các tham số vnp_*
        $params = array_filter(
            $params,
            fn($k) => str_starts_with($k, 'vnp_'),
            ARRAY_FILTER_USE_KEY
        );

        ksort($params);

        $hashData = [];
        foreach ($params as $key => $value) {
            $hashData[] = urlencode($key) . '=' . urlencode((string) $value);
        }
        $hashData = implode('&', $hashData);

        $calc = hash_hmac('sha512', $hashData, (string) config('vnpay.hash_secret'));

        return hash_equals($calc, $received);
    }
}
