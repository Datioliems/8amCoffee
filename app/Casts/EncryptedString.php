<?php

namespace App\Casts;

use App\Support\Pii;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Facades\Crypt;

/**
 * Cast PII mã hóa CHỊU LỖI:
 *  - get(): giải mã; nếu gặp giá trị plaintext (vd do seeder chèn thẳng) thì trả
 *    nguyên thay vì ném DecryptException → tránh 500.
 *  - set(): luôn mã hóa khi ghi.
 */
class EncryptedString implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes)
    {
        return Pii::tryDecrypt($value);
    }

    public function set($model, string $key, $value, array $attributes)
    {
        return $value === null || $value === '' ? $value : Crypt::encryptString((string) $value);
    }
}
