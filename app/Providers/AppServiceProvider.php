<?php

namespace App\Providers;

use App\Models\Order;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
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

        // Rate limiter cho nhóm route API (thiết bị Arduino gọi vào).
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)->by($request->ip()));

        // Blade: @perm('key') ... @endperm — ẩn/hiện theo quyền.
        Blade::if('perm', fn (string $key) => \App\Support\Perm::can($key));

        // Giỏ hàng khách: chia sẻ "các đơn đã đặt trong phiên" cho MỌI trang khách
        // (hiển thị dạng card trong sidebar giỏ hàng, kể cả trang quét QR/landing).
        View::composer('layouts.customer', function ($view) {
            $codes = (array) session('customer_orders', []);
            $view->with('cartOrders', empty($codes) ? collect()
                : Order::with(['chiTietOrders.mon', 'chiTietOrders.options'])
                    ->whereIn('ma_order', $codes)
                    ->orderByDesc('ngay_order')->orderByDesc('gio_order')
                    ->get());
        });
    }
}
