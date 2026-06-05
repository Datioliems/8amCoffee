<?php

namespace App\Support;

/**
 * Tạo URL tài nguyên tĩnh (model 3D, ảnh nặng) — ưu tiên CDN (Cloudflare R2)
 * nếu cấu hình ASSET_CDN_URL, ngược lại dùng file local qua asset().
 */
class Cdn
{
    public static function url(string $path): string
    {
        $base = rtrim((string) config('assets.cdn_url'), '/');
        $path = ltrim($path, '/');
        return $base !== '' ? $base . '/' . $path : asset($path);
    }
}
