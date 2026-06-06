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
        } elseif (! session('chuc_vu')) {
            // Phiên có tai_khoan_id nhưng thiếu chuc_vu (session bị ghi thiếu hoặc container restart).
            // Tải lại thông tin từ DB để khôi phục đầy đủ.
            $tk = TaiKhoan::with('nhanVien')->find(session('tai_khoan_id'));
            if (! $tk || $tk->trang_thai !== 'active') {
                $request->session()->flush();
                return redirect()->route('login')->with('error', 'Phiên không hợp lệ, vui lòng đăng nhập lại.');
            }
            $request->session()->put('ma_nv',        $tk->ma_nv);
            $request->session()->put('ten_nv',       $tk->nhanVien?->ten_nv ?? $tk->ten_tk);
            $request->session()->put('chuc_vu',      $tk->chuc_vu);
            $request->session()->put('ma_chi_nhanh', $tk->nhanVien?->ma_chi_nhanh);
            $request->session()->put('quyen',        Perm::effectiveFor($tk));
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

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
