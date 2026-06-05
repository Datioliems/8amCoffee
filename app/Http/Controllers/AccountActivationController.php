<?php

namespace App\Http\Controllers;

use App\Models\TaiKhoan;
use Illuminate\Http\Request;

/**
 * Kích hoạt tài khoản nhân viên qua link trong email — xác nhận email tồn tại & thuộc sở hữu.
 */
class AccountActivationController extends Controller
{
    public function activate(string $token)
    {
        // So khớp token bằng query có tham số hoá (chống SQLi).
        $tk = TaiKhoan::where('kich_hoat_token', $token)->first();

        if (! $tk || $tk->trang_thai === 'active') {
            // Đã kích hoạt rồi hoặc token sai/đã dùng.
            return redirect()->route('login')->with(
                $tk && $tk->trang_thai === 'active' ? 'success' : 'error',
                $tk && $tk->trang_thai === 'active'
                    ? 'Tài khoản đã được kích hoạt. Vui lòng đăng nhập.'
                    : 'Liên kết kích hoạt không hợp lệ hoặc đã được sử dụng.'
            );
        }

        if (! $tk->kich_hoat_het_han || $tk->kich_hoat_het_han->isPast()) {
            return redirect()->route('login')->with('error',
                'Liên kết kích hoạt đã hết hạn. Vui lòng liên hệ quản lý để gửi lại.');
        }

        $tk->update([
            'trang_thai'         => 'active',
            'email_xac_thuc_luc' => now(),
            'kich_hoat_token'    => null,
            'kich_hoat_het_han'  => null,
        ]);

        return redirect()->route('login')->with('success',
            'Kích hoạt tài khoản thành công! Bạn có thể đăng nhập bằng tài khoản & mật khẩu trong email.');
    }
}
