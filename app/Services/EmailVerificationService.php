<?php

namespace App\Services;

/**
 * Kiểm tra một địa chỉ email CÓ KHẢ NĂNG nhận thư không, trước khi gửi
 * thông tin nhạy cảm (validate-before-send) — không cần gửi thử:
 *
 *   1) Định dạng (cú pháp) — filter_var + whitelist ký tự.
 *   2) Bản ghi MX của tên miền — nếu domain không có MX (và không có A record)
 *      thì chắc chắn không nhận được thư → email coi như không tồn tại.
 *
 * (Việc "ping mailbox" qua SMTP thường bị nhà cung cấp chặn/greylist nên
 *  không đáng tin; ta dùng MX + bước kích hoạt qua link để xác nhận quyền sở hữu.)
 */
class EmailVerificationService
{
    /** Whitelist: chỉ chữ, số và . _ % + - @ — chặn ký tự lạ (', ", ;, <, >, &…). */
    private const WHITELIST = '/^[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}$/';

    public function validFormat(string $email): bool
    {
        $email = trim($email);
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false
            && preg_match(self::WHITELIST, $email) === 1
            && mb_strlen($email) <= 150;
    }

    /** Tên miền của email có máy chủ nhận thư (MX) hoặc ít nhất 1 A record không? */
    public function domainAcceptsMail(string $email): bool
    {
        $domain = substr(strrchr($email, '@') ?: '', 1);
        if ($domain === '') {
            return false;
        }
        // IDN → ASCII (vd: tên miền tiếng Việt) nếu có ext intl.
        if (function_exists('idn_to_ascii')) {
            $ascii = idn_to_ascii($domain);
            if ($ascii !== false) {
                $domain = $ascii;
            }
        }

        // Dùng set_error_handler để bắt PHP warning từ checkdnsrr (vd: DNS timeout,
        // mạng không khả dụng trong container) — PHP không throw exception cho trường hợp này.
        // Nếu DNS không trả lời được → fail-open (để bước kích hoạt qua link xác nhận thay).
        $dnsError = false;
        set_error_handler(static function () use (&$dnsError): bool {
            $dnsError = true;
            return true; // ngăn PHP hiển thị warning
        }, E_WARNING);

        $hasMx   = checkdnsrr($domain, 'MX');
        $hasA    = $hasMx ? false : checkdnsrr($domain, 'A');
        $hasAAAA = ($hasMx || $hasA) ? false : checkdnsrr($domain, 'AAAA');

        restore_error_handler();

        // DNS không resolve được → không chặn, để link kích hoạt xác nhận quyền sở hữu
        if ($dnsError) {
            return true;
        }

        return $hasMx || $hasA || $hasAAAA;
    }

    /**
     * Kiểm tra tổng hợp.
     * @return array{ok:bool, reason:?string}
     */
    public function check(string $email): array
    {
        if (! $this->validFormat($email)) {
            return ['ok' => false, 'reason' => 'Email sai định dạng hoặc chứa ký tự không hợp lệ.'];
        }
        try {
            if (! $this->domainAcceptsMail($email)) {
                return ['ok' => false, 'reason' => 'Tên miền email không tồn tại / không nhận được thư (không có bản ghi MX).'];
            }
        } catch (\Throwable $e) {
            // Không tra cứu được DNS (mạng) → không chặn cứng, để bước kích hoạt lo tiếp.
            return ['ok' => true, 'reason' => 'Không kiểm tra được DNS, bỏ qua bước MX.'];
        }
        return ['ok' => true, 'reason' => null];
    }
}
