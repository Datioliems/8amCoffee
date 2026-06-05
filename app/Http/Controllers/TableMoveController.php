<?php

namespace App\Http\Controllers;

use App\Models\YeuCauDoiBan;
use Illuminate\Support\Facades\DB;

/**
 * Nhân viên duyệt yêu cầu đổi bàn của khách (hiển thị/poll trên Order board).
 */
class TableMoveController extends Controller
{
    private const ACTIVE = ['cho_xac_nhan', 'da_xac_nhan', 'dang_pha_che', 'da_phuc_vu'];

    /** Danh sách yêu cầu đang chờ duyệt (JSON) — order board poll mỗi vài giây. */
    public function index()
    {
        $maChiNhanh = (string) session('ma_chi_nhanh', '');
        $soBan = DB::table('BAN')->where('ma_chi_nhanh', $maChiNhanh)->pluck('so_ban', 'ma_ban');

        $reqs = YeuCauDoiBan::where('ma_chi_nhanh', $maChiNhanh)
            ->where('trang_thai', 'cho_duyet')
            ->orderBy('thoi_gian_tao')
            ->get()
            ->map(fn($r) => [
                'id'       => $r->id,
                'ma_order' => $r->ma_order,
                'ban_cu'   => $soBan[$r->ma_ban_cu] ?? $r->ma_ban_cu,
                'ban_moi'  => $soBan[$r->ma_ban_moi] ?? $r->ma_ban_moi,
                'luc'      => optional($r->thoi_gian_tao)->format('H:i'),
            ]);

        return response()->json(['requests' => $reqs]);
    }

    /** Duyệt: chuyển đơn sang bàn mới. */
    public function approve(string $id)
    {
        $maChiNhanh = (string) session('ma_chi_nhanh', '');
        $req = YeuCauDoiBan::where('id', $id)->where('ma_chi_nhanh', $maChiNhanh)->firstOrFail();

        if ($req->trang_thai !== 'cho_duyet') {
            return back()->with('error', 'Yêu cầu đã được xử lý.');
        }

        $dest = DB::table('BAN')->where('ma_ban', $req->ma_ban_moi)->first();
        if (! $dest || $dest->trang_thai !== 'trong') {
            $req->update(['trang_thai' => 'tu_choi', 'ma_nv_xu_ly' => session('ma_nv'), 'thoi_gian_xu_ly' => now()]);
            return back()->with('error', 'Bàn đích không còn trống — đã từ chối yêu cầu.');
        }

        DB::transaction(function () use ($req) {
            DB::table('ORDERS')->where('ma_order', $req->ma_order)->update(['ma_ban' => $req->ma_ban_moi]);
            DB::table('BAN')->where('ma_ban', $req->ma_ban_moi)->update(['trang_thai' => 'co_khach']);

            $conLai = DB::table('ORDERS')->where('ma_ban', $req->ma_ban_cu)
                ->whereIn('trang_thai', self::ACTIVE)->count();
            DB::table('BAN')->where('ma_ban', $req->ma_ban_cu)
                ->update(['trang_thai' => $conLai ? 'co_khach' : 'trong']);

            $req->update(['trang_thai' => 'da_duyet', 'ma_nv_xu_ly' => session('ma_nv'), 'thoi_gian_xu_ly' => now()]);
        });

        return back()->with('success', "Đã duyệt đổi bàn cho đơn {$req->ma_order}.");
    }

    /** Từ chối yêu cầu. */
    public function reject(string $id)
    {
        $maChiNhanh = (string) session('ma_chi_nhanh', '');
        $req = YeuCauDoiBan::where('id', $id)->where('ma_chi_nhanh', $maChiNhanh)->firstOrFail();
        if ($req->trang_thai === 'cho_duyet') {
            $req->update(['trang_thai' => 'tu_choi', 'ma_nv_xu_ly' => session('ma_nv'), 'thoi_gian_xu_ly' => now()]);
        }
        return back()->with('success', 'Đã từ chối yêu cầu đổi bàn.');
    }
}
