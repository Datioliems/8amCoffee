<?php

namespace App\Http\Middleware;

use App\Support\Perm;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Chặn route theo QUYỀN: perm:key1,key2 (có 1 trong các quyền là qua).
 * superadmin luôn được qua.
 */
class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, string ...$perms): Response
    {
        if (session('chuc_vu') === 'superadmin') {
            return $next($request);
        }
        if (empty($perms) || Perm::canAny($perms)) {
            return $next($request);
        }
        abort(403, 'Bạn không có quyền truy cập chức năng này.');
    }
}
