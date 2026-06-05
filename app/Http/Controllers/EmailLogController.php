<?php

namespace App\Http\Controllers;

use App\Models\EmailLog;
use Illuminate\Http\Request;

/**
 * Nhật ký email hệ thống đã gửi (chỉ superadmin).
 */
class EmailLogController extends Controller
{
    public function index(Request $request)
    {
        $loai = $request->query('loai');

        $logs = EmailLog::query()
            ->when($loai, fn($q) => $q->where('loai', $loai))
            ->orderByDesc('thoi_gian')
            ->paginate(50)
            ->withQueryString();

        $tu24h     = now()->subDay();
        $thanhCong = EmailLog::where('trang_thai', 'thanh_cong')->where('thoi_gian', '>=', $tu24h)->count();
        $thatBai   = EmailLog::where('trang_thai', 'that_bai')->where('thoi_gian', '>=', $tu24h)->count();

        return view('staff.email-log', compact('logs', 'loai', 'thanhCong', 'thatBai'));
    }

    /** Xóa thủ công một dòng nhật ký email. */
    public function destroy(string $id)
    {
        EmailLog::whereKey($id)->delete();
        return back()->with('success', 'Đã xóa dòng nhật ký email.');
    }

    /** Xóa thủ công toàn bộ email gửi THẤT BẠI (email không tồn tại/không nhận được). */
    public function clearFailed()
    {
        $n = EmailLog::where('trang_thai', 'that_bai')->delete();
        return back()->with('success', "Đã xóa {$n} dòng email thất bại.");
    }
}
