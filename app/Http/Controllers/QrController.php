<?php

namespace App\Http\Controllers;

use App\Models\Ban;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrController extends Controller
{
    /** Hiển thị trang QR cho khách */
    public function scan(Request $request, string $maBan)
    {
        $ban = Ban::findOrFail($maBan);

        // Ghi log quét mã: số bàn, chi nhánh, thời gian (độc lập với việc có đặt món hay không)
        DB::table('SCAN_LOG')->insert([
            'ma_ban'       => $ban->ma_ban,
            'ma_chi_nhanh' => $ban->ma_chi_nhanh,
            'ip'           => $request->ip(),
            'user_agent'   => mb_substr((string) $request->userAgent(), 0, 300),
            'thoi_gian'    => now(),
        ]);

        // Điền sẵn thông tin khách đã nhập trong phiên (để "Gọi món khác" không phải nhập lại)
        $profile = (array) session('customer_profile', []);
        return view('customer.scan', compact('ban', 'profile'));
    }

    /** Trang xem log quét QR (admin/superadmin). */
    public function scanLog()
    {
        $maChiNhanh = (string) session('ma_chi_nhanh', '');
        $isSuper = session('chuc_vu') === 'superadmin';

        $logs = DB::table('SCAN_LOG as s')
            ->leftJoin('BAN as b', 'b.ma_ban', '=', 's.ma_ban')
            ->leftJoin('CHI_NHANH as c', 'c.ma_chi_nhanh', '=', 's.ma_chi_nhanh')
            ->when(! $isSuper, fn($q) => $q->where('s.ma_chi_nhanh', $maChiNhanh))
            ->select('s.*', 'b.so_ban', 'c.ten_chi_nhanh')
            ->orderByDesc('s.thoi_gian')
            ->paginate(50);

        return view('staff.scan-log', compact('logs', 'isSuper'));
    }

    /** Tạo QR SVG cho màn hình quản lý bàn, không phụ thuộc Imagick. */
    public function generate(string $maBan)
    {
        $ban = Ban::findOrFail($maBan);
        $url = route('customer.scan', $maBan);

        $qr = QrCode::format('svg')
                    ->size(300)
                    ->margin(1)
                    ->generate($url);

        return response($qr)->header('Content-Type', 'image/svg+xml');
    }

    /** Trang poster QR có thương hiệu (in/dán tại bàn). */
    public function poster(string $maBan)
    {
        $ban = Ban::with('chiNhanh')->findOrFail($maBan);
        // Phạm vi chi nhánh (trừ superadmin)
        abort_if(
            session('chuc_vu') !== 'superadmin' && $ban->ma_chi_nhanh !== session('ma_chi_nhanh'),
            403
        );

        $url = route('customer.scan', $maBan);

        return view('staff.qr-poster', compact('ban', 'url'));
    }
}
