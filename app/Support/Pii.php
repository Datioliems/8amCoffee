<?php

namespace App\Support;

use Illuminate\Support\Facades\Crypt;

/**
 * Tiện ích bảo vệ dữ liệu cá nhân (PII):
 *  - phoneHash(): blind index tất định (HMAC-SHA256) để TRA CỨU/GỘP theo SĐT
 *    mà không cần giải mã (vì ciphertext của Laravel dùng IV ngẫu nhiên, không
 *    thể WHERE/GROUP BY trực tiếp).
 *  - encIfPlain()/tryDecrypt(): mã hóa/giải mã chịu lỗi, dùng cho migration &
 *    đọc dữ liệu (tương thích cả dữ liệu cũ chưa mã hóa).
 */
class Pii
{
    /** Khóa HMAC cho blind index. Ưu tiên PII_PEPPER, fallback APP_KEY (ổn định theo môi trường). */
    public static function pepper(): string
    {
        return (string) (env('PII_PEPPER') ?: config('app.key'));
    }

    /** Hash tất định của SĐT (đã chuẩn hóa) để dùng làm blind index. */
    public static function phoneHash(?string $sdt): ?string
    {
        if (! $sdt) return null;
        $normalized = preg_replace('/\D/', '', $sdt);
        if ($normalized === '') return null;
        return hash_hmac('sha256', $normalized, self::pepper());
    }

    /** Giải mã nếu là ciphertext; nếu là plaintext (dữ liệu cũ) thì trả nguyên. */
    public static function tryDecrypt(?string $value): ?string
    {
        if ($value === null || $value === '') return $value;
        try {
            return Crypt::decryptString($value);
        } catch (\Throwable $e) {
            return $value;
        }
    }

    /** Mã hóa nếu đang là plaintext; nếu đã mã hóa rồi thì giữ nguyên (idempotent cho migration). */
    public static function encIfPlain(?string $value): ?string
    {
        if ($value === null || $value === '') return $value;
        try {
            Crypt::decryptString($value);   // giải mã được => đã mã hóa rồi
            return $value;
        } catch (\Throwable $e) {
            return Crypt::encryptString($value);
        }
    }
}
