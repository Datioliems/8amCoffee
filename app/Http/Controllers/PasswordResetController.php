<?php

namespace App\Http\Controllers;

use App\Models\EmailLog;
use App\Models\NhanVien;
use App\Models\NhatKyDangNhap;
use App\Models\TaiKhoan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Quên / đặt lại mật khẩu qua email (link 1 lần, có hạn).
 * Tài khoản nhận diện theo TÊN ĐĂNG NHẬP hoặc EMAIL của nhân viên.
 */
class PasswordResetController extends Controller
{
    public function showRequest()
    {
        return view('staff.password.forgot');
    }

    public function sendLink(Request $request)
    {
        $request->validate(
            ['tai_khoan' => 'required|string|max:150'],
            ['tai_khoan.required' => 'Vui lòng nhập tên đăng nhập hoặc email.']
        );

        $key = 'pwreset-ip:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, (int) config('security.reset_rate_per_minute', 5))) {
            $giay = RateLimiter::availableIn($key);
            return back()->withErrors(['tai_khoan' => "Thử lại sau {$giay} giây."]);
        }
        RateLimiter::hit($key, 60);

        $tk = $this->timTaiKhoan(trim($request->input('tai_khoan')));

        // Có tài khoản + có email → gửi link. Luôn trả thông báo CHUNG để không lộ tài khoản.
        if ($tk && $tk->nhanVien && ! empty($tk->nhanVien->email)) {
            $raw  = Str::random(64);
            $phut = (int) config('security.reset_minutes', 60);
            $tk->update([
                'reset_token'   => hash('sha256', $raw),
                'reset_het_han' => now()->addMinutes($phut),
            ]);

            $url   = route('password.reset', ['token' => $raw]);
            $email = $tk->nhanVien->email;
            $ten   = $tk->nhanVien->ten_nv ?? $tk->ten_tk;
            $noiDung = "Xin chao {$ten},\n\n"
                . "Ban (hoac ai do) vua yeu cau dat lai mat khau tai khoan 8AM Coffee.\n"
                . "Nhan vao link sau de dat lai (het han sau {$phut} phut):\n{$url}\n\n"
                . "Neu khong phai ban, hay bo qua email nay.\n\n-- 8AM Coffee";

            try {
                Mail::raw($noiDung, function ($m) use ($email) {
                    $m->to($email)->subject('Dat lai mat khau - 8AM Coffee');
                });
                EmailLog::ghi('reset_mat_khau', $email, true, 'Đặt lại mật khẩu', $tk->ma_tai_khoan);
            } catch (\Throwable $e) {
                report($e);
                EmailLog::ghi('reset_mat_khau', $email, false, 'Đặt lại mật khẩu', $tk->ma_tai_khoan, $e->getMessage());
            }

            NhatKyDangNhap::ghi('quen_mat_khau', [
                'ten_tk' => $tk->ten_tk, 'ma_tai_khoan' => $tk->ma_tai_khoan, 'ma_nv' => $tk->ma_nv,
                'thanh_cong' => true, 'dia_chi_ip' => $request->ip(),
                'chi_tiet' => 'Gui link dat lai mat khau',
            ]);
        }

        return back()->with('success', 'Nếu thông tin hợp lệ, link đặt lại mật khẩu đã được gửi tới email của tài khoản.');
    }

    public function showReset(string $token)
    {
        if (! $this->timTheoToken($token)) {
            return redirect()->route('login')->withErrors(['ten_tk' => 'Link đặt lại không hợp lệ hoặc đã hết hạn.']);
        }
        return view('staff.password.reset', ['token' => $token]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'token'    => 'required|string',
            'mat_khau' => 'required|string|min:6|confirmed',
        ], [
            'mat_khau.required'  => 'Vui lòng nhập mật khẩu mới.',
            'mat_khau.min'       => 'Mật khẩu tối thiểu 6 ký tự.',
            'mat_khau.confirmed' => 'Xác nhận mật khẩu không khớp.',
        ]);

        $tk = $this->timTheoToken((string) $request->input('token'));
        if (! $tk) {
            return redirect()->route('login')->withErrors(['ten_tk' => 'Link đặt lại không hợp lệ hoặc đã hết hạn.']);
        }

        $tk->update([
            'mat_khau'         => Hash::make($request->input('mat_khau')),
            'reset_token'      => null,
            'reset_het_han'    => null,
            'dang_nhap_sai'    => 0,
            'khoa_den'         => null,
            'remember_token'   => null,   // huỷ mọi phiên "ghi nhớ" cũ cho an toàn
            'remember_het_han' => null,
        ]);

        NhatKyDangNhap::ghi('dat_lai_mat_khau', [
            'ten_tk' => $tk->ten_tk, 'ma_tai_khoan' => $tk->ma_tai_khoan, 'ma_nv' => $tk->ma_nv,
            'thanh_cong' => true, 'dia_chi_ip' => $request->ip(), 'chi_tiet' => 'Dat lai mat khau thanh cong',
        ]);

        return redirect()->route('login')->with('success', 'Đặt lại mật khẩu thành công. Vui lòng đăng nhập bằng mật khẩu mới.');
    }

    /** Tìm tài khoản theo tên đăng nhập, hoặc email nhân viên (quét + giải mã). */
    private function timTaiKhoan(string $input): ?TaiKhoan
    {
        $tk = TaiKhoan::with('nhanVien')->where('ten_tk', $input)->first();
        if ($tk) {
            return $tk;
        }
        if (str_contains($input, '@')) {
            $emailLower = mb_strtolower($input);
            foreach (NhanVien::all() as $nv) {              // số nhân viên ít → chấp nhận quét
                if (mb_strtolower((string) $nv->email) === $emailLower) {
                    return TaiKhoan::with('nhanVien')->where('ma_nv', $nv->ma_nv)->first();
                }
            }
        }
        return null;
    }

    /** Lấy tài khoản theo token hợp lệ & chưa hết hạn. */
    private function timTheoToken(string $token): ?TaiKhoan
    {
        if ($token === '') {
            return null;
        }
        $tk = TaiKhoan::where('reset_token', hash('sha256', $token))->first();
        if (! $tk || ! $tk->reset_het_han || $tk->reset_het_han->isPast()) {
            return null;
        }
        return $tk;
    }
}
