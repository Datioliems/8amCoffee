<?php

namespace App\Http\Controllers;

use App\Mail\OtpMail;
use App\Models\EmailLog;
use App\Models\NhatKyDangNhap;
use App\Models\TaiKhoan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (session('tai_khoan_id')) {
            return redirect()->route('orders.index');
        }
        return view('staff.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'ten_tk'   => 'required|string',
            'mat_khau' => 'required|string',
        ], [
            'ten_tk.required'   => 'Vui lòng nhập tên đăng nhập.',
            'mat_khau.required' => 'Vui lòng nhập mật khẩu.',
        ]);

        $tenTk = (string) $request->input('ten_tk');
        $ip    = $request->ip();

        // ── (1) Chặn brute-force theo IP (Laravel RateLimiter) ──────────
        $ipKey = 'login-ip:' . $ip;
        if (RateLimiter::tooManyAttempts($ipKey, config('security.ip_rate_per_minute'))) {
            $giay = RateLimiter::availableIn($ipKey);
            $this->log('dang_nhap_that_bai', $request, ['ten_tk' => $tenTk, 'chi_tiet' => "Chan IP: qua nhieu yeu cau ($giay s)"]);
            return back()->withErrors([
                'ten_tk' => "Quá nhiều lần thử. Vui lòng đợi {$giay} giây rồi thử lại.",
            ])->withInput($request->only('ten_tk'));
        }
        RateLimiter::hit($ipKey, 60);

        // ── (2) Tìm tài khoản theo tên đăng nhập (mọi trạng thái) ───────
        $taiKhoan = TaiKhoan::with('nhanVien')
            ->where('ten_tk', $tenTk)
            ->first();

        // ── (3) Đang bị khoá tạm thời? ─────────────────────────────────
        if ($taiKhoan && $taiKhoan->dangBiKhoa()) {
            $giay = max(0, $taiKhoan->khoa_den->getTimestamp() - now()->getTimestamp());
            $phut = max(1, (int) ceil($giay / 60));
            $this->log('tai_khoan_bi_khoa', $request, [
                'ten_tk' => $tenTk, 'ma_tai_khoan' => $taiKhoan->ma_tai_khoan, 'ma_nv' => $taiKhoan->ma_nv,
                'chi_tiet' => 'Dang nhap khi tai khoan dang bi khoa',
            ]);
            return back()->withErrors([
                'ten_tk' => "Tài khoản tạm khoá do đăng nhập sai nhiều lần. Thử lại sau {$phut} phút.",
            ])->withInput($request->only('ten_tk'));
        }

        // ── (4) Sai tài khoản / mật khẩu ────────────────────────────────
        if (!$taiKhoan || !Hash::check($request->mat_khau, $taiKhoan->mat_khau)) {
            $chiTiet = 'Sai thong tin dang nhap';
            if ($taiKhoan) {
                // Tăng đếm sai + khoá nếu vượt ngưỡng.
                $taiKhoan->increment('dang_nhap_sai');
                $max = config('security.max_login_attempts');
                if ($taiKhoan->dang_nhap_sai >= $max) {
                    $taiKhoan->update([
                        'khoa_den'      => now()->addMinutes(config('security.lockout_minutes')),
                        'dang_nhap_sai' => 0,
                    ]);
                    $chiTiet = 'Vuot nguong sai -> khoa tai khoan';
                }
            }
            $this->log('dang_nhap_that_bai', $request, [
                'ten_tk' => $tenTk,
                'ma_tai_khoan' => $taiKhoan->ma_tai_khoan ?? null,
                'ma_nv' => $taiKhoan->ma_nv ?? null,
                'chi_tiet' => $chiTiet,
            ]);

            // Thông báo còn lại bao nhiêu lần (không lộ tài khoản tồn tại hay không thì
            // chỉ hiện khi có tài khoản — cân bằng UX & bảo mật).
            $msg = 'Tên đăng nhập hoặc mật khẩu không đúng.';
            if ($taiKhoan && $taiKhoan->dang_nhap_sai > 0) {
                $conLai = config('security.max_login_attempts') - $taiKhoan->dang_nhap_sai;
                if ($conLai > 0) $msg .= " (Còn {$conLai} lần trước khi bị khoá tạm thời.)";
            }
            return back()->withErrors(['ten_tk' => $msg])->withInput($request->only('ten_tk'));
        }

        // ── (5) Đúng mật khẩu → kiểm tra trạng thái tài khoản ───────────
        if ($taiKhoan->trang_thai !== 'active') {
            $msg = $taiKhoan->trang_thai === 'cho_xac_minh'
                ? 'Tài khoản chưa kích hoạt. Vui lòng mở email và bấm link kích hoạt (hoặc nhờ quản lý gửi lại).'
                : 'Tài khoản đã bị vô hiệu hoá. Vui lòng liên hệ quản lý.';
            $this->log('dang_nhap_that_bai', $request, $this->ctx($taiKhoan, 'trang_thai=' . $taiKhoan->trang_thai));
            return back()->withErrors(['ten_tk' => $msg])->withInput($request->only('ten_tk'));
        }

        RateLimiter::clear($ipKey);
        $taiKhoan->update(['dang_nhap_sai' => 0, 'khoa_den' => null]);

        // ── (6) Bật 2FA? → gửi OTP, chuyển sang bước xác thực ───────────
        if ($this->canDuse2fa($taiKhoan)) {
            return $this->guiOtpVaChuyenHuong($taiKhoan, $request);
        }

        // ── (7) Không 2FA → đăng nhập luôn ──────────────────────────────
        return $this->hoanTatDangNhap($taiKhoan, $request);
    }

    /** Trang nhập OTP. */
    public function showOtp(Request $request)
    {
        $pending = session('otp_pending');
        if (!$pending) {
            return redirect()->route('login');
        }
        return view('staff.otp', [
            'email_mask' => $pending['email_mask'] ?? '',
        ]);
    }

    /** Xác thực OTP. */
    public function verifyOtp(Request $request)
    {
        $request->validate(['otp' => 'required|string'], [
            'otp.required' => 'Vui lòng nhập mã OTP.',
        ]);

        $pending = session('otp_pending');
        if (!$pending) {
            return redirect()->route('login')->withErrors(['ten_tk' => 'Phiên xác thực đã hết hạn, vui lòng đăng nhập lại.']);
        }

        $taiKhoan = TaiKhoan::with('nhanVien')->find($pending['ma_tai_khoan']);
        if (!$taiKhoan) {
            session()->forget('otp_pending');
            return redirect()->route('login');
        }

        // Hết hạn?
        if (!$taiKhoan->otp_ma || !$taiKhoan->otp_het_han || $taiKhoan->otp_het_han->isPast()) {
            $this->log('otp_sai', $request, $this->ctx($taiKhoan, 'OTP het han'));
            return back()->withErrors(['otp' => 'Mã OTP đã hết hạn. Bấm "Gửi lại mã".']);
        }

        // Sai mã?
        if (!Hash::check(trim((string) $request->otp), $taiKhoan->otp_ma)) {
            $taiKhoan->increment('otp_sai');
            $max = config('security.otp_max_attempts');
            if ($taiKhoan->otp_sai >= $max) {
                $taiKhoan->update(['otp_ma' => null, 'otp_het_han' => null, 'otp_sai' => 0]);
                session()->forget('otp_pending');
                $this->log('otp_sai', $request, $this->ctx($taiKhoan, 'Sai OTP qua nhieu lan -> huy phien'));
                return redirect()->route('login')->withErrors(['ten_tk' => 'Nhập sai OTP quá nhiều lần. Vui lòng đăng nhập lại.']);
            }
            $this->log('otp_sai', $request, $this->ctx($taiKhoan, 'Sai OTP'));
            $conLai = $max - $taiKhoan->otp_sai;
            return back()->withErrors(['otp' => "Mã OTP không đúng. Còn {$conLai} lần thử."]);
        }

        // Đúng → xoá OTP, hoàn tất.
        $taiKhoan->update(['otp_ma' => null, 'otp_het_han' => null, 'otp_sai' => 0]);
        session()->forget('otp_pending');
        $this->log('otp_thanh_cong', $request, $this->ctx($taiKhoan, 'Xac thuc OTP thanh cong'), true);

        return $this->hoanTatDangNhap($taiKhoan, $request, alreadyLogged: true);
    }

    /** Gửi lại OTP (có throttle). */
    public function resendOtp(Request $request)
    {
        $pending = session('otp_pending');
        if (!$pending) return redirect()->route('login');

        $key = 'otp-resend:' . $pending['ma_tai_khoan'];
        if (RateLimiter::tooManyAttempts($key, 3)) {  // tối đa 3 lần / phút
            $giay = RateLimiter::availableIn($key);
            return back()->withErrors(['otp' => "Gửi lại quá nhanh. Đợi {$giay} giây."]);
        }
        RateLimiter::hit($key, 60);

        $taiKhoan = TaiKhoan::with('nhanVien')->find($pending['ma_tai_khoan']);
        if (!$taiKhoan) return redirect()->route('login');

        return $this->guiOtpVaChuyenHuong($taiKhoan, $request, resend: true);
    }

    public function logout(Request $request)
    {
        $this->log('dang_xuat', $request, [
            'ten_tk'       => session('ten_nv'),
            'ma_tai_khoan' => session('tai_khoan_id'),
            'ma_nv'        => session('ma_nv'),
        ], true);

        $request->session()->flush();
        $request->session()->regenerate();
        return redirect()->route('login');
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────

    /** Có đủ điều kiện dùng 2FA không (bật toàn cục + tài khoản bật + có email)? */
    private function canDuse2fa(TaiKhoan $tk): bool
    {
        return config('security.two_factor_enabled')
            && $tk->xac_thuc_2_lop
            && !empty($tk->nhanVien?->email);
    }

    /** Sinh OTP, lưu hash, gửi email, đặt session pending rồi chuyển sang trang OTP. */
    private function guiOtpVaChuyenHuong(TaiKhoan $tk, Request $request, bool $resend = false)
    {
        $len = config('security.otp_length');
        $otp = str_pad((string) random_int(0, (10 ** $len) - 1), $len, '0', STR_PAD_LEFT);
        $phut = config('security.otp_minutes');

        $tk->update([
            'otp_ma'      => Hash::make($otp),
            'otp_het_han' => now()->addMinutes($phut),
            'otp_sai'     => 0,
        ]);

        $email = $tk->nhanVien->email;
        try {
            Mail::to($email)->send(new OtpMail($tk->nhanVien->ten_nv ?? $tk->ten_tk, $otp, $phut));
            EmailLog::ghi('otp', $email, true, 'Mã OTP đăng nhập', $tk->ma_tai_khoan);
        } catch (\Throwable $e) {
            report($e);
            EmailLog::ghi('otp', $email, false, 'Mã OTP đăng nhập', $tk->ma_tai_khoan, $e->getMessage());
            return back()->withErrors(['ten_tk' => 'Không gửi được email OTP. Vui lòng liên hệ quản trị.']);
        }

        session(['otp_pending' => [
            'ma_tai_khoan' => $tk->ma_tai_khoan,
            'email_mask'   => $this->maskEmail($email),
        ]]);

        $this->log('otp_gui', $request, $this->ctx($tk, $resend ? 'Gui lai OTP' : 'Gui OTP'), true);

        return redirect()->route('otp.show')
            ->with('success', ($resend ? 'Đã gửi lại' : 'Đã gửi') . ' mã OTP tới email ' . $this->maskEmail($email));
    }

    /** Đặt session đăng nhập + chuyển về trang đích. */
    private function hoanTatDangNhap(TaiKhoan $tk, Request $request, bool $alreadyLogged = false)
    {
        $request->session()->regenerate();

        $request->session()->put('tai_khoan_id', $tk->ma_tai_khoan);
        $request->session()->put('ma_nv',        $tk->ma_nv);
        $request->session()->put('ten_nv',       $tk->nhanVien?->ten_nv ?? $tk->ten_tk);
        $request->session()->put('chuc_vu',      $tk->chuc_vu);
        $request->session()->put('ma_chi_nhanh', $tk->nhanVien?->ma_chi_nhanh);

        $tk->update(['lan_dang_nhap_cuoi' => now(), 'ip_dang_nhap_cuoi' => $request->ip()]);

        $this->log('dang_nhap', $request, $this->ctx($tk, $alreadyLogged ? 'Dang nhap (qua 2FA)' : 'Dang nhap thanh cong'), true);

        $intendedUrl = $request->session()->pull('url.intended');
        $safeUrl = ($intendedUrl && !str_ends_with($intendedUrl, '.html'))
            ? $intendedUrl
            : route('orders.index');

        return redirect($safeUrl);
    }

    /** Context chung cho audit log từ một TaiKhoan. */
    private function ctx(TaiKhoan $tk, string $chiTiet): array
    {
        return [
            'ten_tk'       => $tk->ten_tk,
            'ma_tai_khoan' => $tk->ma_tai_khoan,
            'ma_nv'        => $tk->ma_nv,
            'chi_tiet'     => $chiTiet,
        ];
    }

    /** Ghi audit log. */
    private function log(string $hanhDong, Request $request, array $data = [], bool $thanhCong = false): void
    {
        NhatKyDangNhap::ghi($hanhDong, array_merge([
            'thanh_cong' => $thanhCong,
            'dia_chi_ip' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
        ], $data));
    }

    /** Che email: datng28092005@gmail.com -> da******05@gmail.com */
    private function maskEmail(string $email): string
    {
        [$name, $domain] = array_pad(explode('@', $email, 2), 2, '');
        if ($name === '' || $domain === '') return $email;
        $keep = min(2, strlen($name));
        $masked = substr($name, 0, $keep) . str_repeat('*', max(2, strlen($name) - $keep - 2)) . substr($name, -1);
        return $masked . '@' . $domain;
    }
}
