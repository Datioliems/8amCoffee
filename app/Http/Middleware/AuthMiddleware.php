<?php

namespace App\Http\Middleware;

use App\Models\TaiKhoan;
use App\Support\Perm;
use Closure;
use Illuminate\Http\Request;

class AuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (! session('tai_khoan_id')) {
            // Chưa có phiên → thử khôi phục từ cookie "ghi nhớ đăng nhập".
            if (! $this->khoiPhucGhiNho($request)) {
                return redirect()->route('login')->with('error', 'Vui lòng đăng nhập.');
            }
        }

        // Buộc đổi mật khẩu lần đầu: chặn mọi route staff (trừ chính trang đổi MK + đăng xuất).
        if (session('phai_doi_mk') && ! $request->routeIs('password.force', 'password.force.update', 'logout')) {
            return redirect()->route('password.force');
        }

        return $next($request);
    }

    /** Khôi phục phiên từ cookie remember (an toàn: mọi lỗi → false). */
    private function khoiPhucGhiNho(Request $request): bool
    {
        try {
            $raw = (string) $request->cookie('8am_remember');
            if ($raw === '' || ! str_contains($raw, '|')) {
                return false;
            }
            [$maTk, $token] = explode('|', $raw, 2);

            $tk = TaiKhoan::with('nhanVien')
                ->where('ma_tai_khoan', $maTk)
                ->where('trang_thai', 'active')
                ->first();

            if (! $tk
                || ! $tk->remember_token
                || ! $tk->remember_het_han
                || $tk->remember_het_han->isPast()
                || ! hash_equals($tk->remember_token, hash('sha256', $token))) {
                return false;
            }

            $request->session()->put('tai_khoan_id', $tk->ma_tai_khoan);
            $request->session()->put('ma_nv',        $tk->ma_nv);
            $request->session()->put('ten_nv',       $tk->nhanVien?->ten_nv ?? $tk->ten_tk);
            $request->session()->put('chuc_vu',      $tk->chuc_vu);
            $request->session()->put('ma_chi_nhanh', $tk->nhanVien?->ma_chi_nhanh);
            $request->session()->put('quyen',        Perm::effectiveFor($tk));
            $request->session()->put('phai_doi_mk',  (bool) $tk->phai_doi_mk);

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
