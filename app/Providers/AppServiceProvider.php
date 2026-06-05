<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Khi chạy sau tunnel/PaaS có HTTPS: ép sinh URL https để tránh lỗi
        // mixed-content (asset CSS/JS), redirect sai và QR trỏ về http.
        // Bật bằng cách đặt FORCE_HTTPS=true trong .env.
        if (filter_var(env('FORCE_HTTPS', false), FILTER_VALIDATE_BOOL)) {
            URL::forceScheme('https');
        }
    }
}
