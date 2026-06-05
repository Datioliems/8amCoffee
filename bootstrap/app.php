<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Tin tưởng reverse proxy / tunnel / PaaS (Cloudflare, Railway, Nginx…)
        // để Laravel nhận đúng HTTPS, host, IP thật từ header X-Forwarded-*.
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'auth.staff' => \App\Http\Middleware\AuthMiddleware::class,
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Phiên hết hạn (CSRF 419): không hiện trang lỗi "Page Expired".
        // - Logout / thao tác staff: đưa về trang đăng nhập.
        // - Yêu cầu JSON (giỏ hàng khách): trả 419 để client tự làm mới token & retry.
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Phiên đã hết hạn, vui lòng thử lại.'], 419);
            }
            if ($request->is('logout') || str_contains((string) $request->path(), 'logout')) {
                return redirect()->route('login');
            }
            return redirect()->back()->withInput($request->except('_token'))
                ->with('error', 'Phiên làm việc đã hết hạn. Vui lòng thử lại.');
        });
    })->create();
