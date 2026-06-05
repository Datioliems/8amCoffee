<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $chucVu = session('chuc_vu');
        // Super-admin (chủ chuỗi) bỏ qua mọi kiểm tra vai trò
        if ($chucVu === 'superadmin') {
            return $next($request);
        }
        if (!in_array($chucVu, $roles)) {
            abort(403, 'Bạn không có quyền truy cập chức năng này.');
        }
        return $next($request);
    }
}
