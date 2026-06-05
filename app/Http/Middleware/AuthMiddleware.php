<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!session('tai_khoan_id')) {
            return redirect()->route('login')->with('error', 'Vui lòng đăng nhập.');
        }
        return $next($request);
    }
}
