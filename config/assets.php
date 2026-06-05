<?php

return [
    // URL gốc CDN (Cloudflare R2 public). Để trống => dùng file tĩnh local.
    // Vd: https://pub-xxxxxxxx.r2.dev
    'cdn_url' => env('ASSET_CDN_URL', ''),
];
