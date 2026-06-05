<?php

namespace App\Http\Controllers;

use App\Models\TaiKhoan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Bắt buộc đổi mật khẩu trong lần đăng nhập ĐẦU TIÊN.
 * Cổng chặn nằm ở AuthMiddleware: khi session('phai_doi_mk') = true thì mọi route
 * staff đều bị ép về trang này (trừ chính trang này + logout).
 */
class ForcePasswordController extends Controller
{
    public function show()
    {
        if (! session('tai_khoan_id')) {
            return redirect()->route('login');
        }
        if (! session('phai_doi_mk')) {
            return redirect()->route('orders.index');
        }
        return view('staff.password.force');
    }

    public function update(Request $request)
    {
        if (! session('tai_khoan_id')) {
            return redirect()->route('login');
        }

        $request->validate([
            'mat_khau_cu'  => ['required', 'string'],
            'mat_khau_moi' => ['required', 'string', 'min:8', 'confirmed', 'different:mat_khau_cu'],
        ], [
            'mat_khau_cu.required'   => 'Vui lòng nhập mật khẩu hiện tại.',
            'mat_khau_moi.required'  => 'Vui lòng nhập mật khẩu mới.',
            'mat_khau_moi.min'       => 'Mật khẩu mới phải có ít nhất 8 ký tự.',
            'mat_khau_moi.confirmed' => 'Nhập lại mật khẩu mới không khớp.',
            'mat_khau_moi.different' => 'Mật khẩu mới phải khác mật khẩu hiện tại.',
        ]);

        $tk = TaiKhoan::find(session('tai_khoan_id'));
        if (! $tk) {
            return redirect()->route('login');
        }

        if (! Hash::check($request->mat_khau_cu, $tk->mat_khau)) {
            return back()->withErrors(['mat_khau_cu' => 'Mật khẩu hiện tại không đúng.']);
        }

        $tk->update([
            'mat_khau'    => Hash::make($request->mat_khau_moi),
            'phai_doi_mk' => false,
        ]);
        session()->forget('phai_doi_mk');

        return redirect()->route('orders.index')
            ->with('success', 'Đã đổi mật khẩu thành công. Chào mừng bạn đến với 8AM Coffee!');
    }
}
