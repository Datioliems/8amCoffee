<?php

namespace App\Services;

use App\Models\PhieuKiemKe;
use App\Models\ChiTietKiemKe;
use Illuminate\Support\Facades\DB;

class StockCheckService
{
    public function createCheck(string $maChiNhanh, string $maNv, array $items, ?string $ghiChu): PhieuKiemKe
    {
        return DB::transaction(function () use ($maChiNhanh, $maNv, $items, $ghiChu) {
            $maPkk = 'PKK' . now()->format('YmdHis');

            $phieu = PhieuKiemKe::create([
                'ma_pkk'       => $maPkk,
                'ngay_kk'      => now()->toDateString(),
                'thoi_gian_kk' => now(),
                'ma_chi_nhanh' => $maChiNhanh,
                'ma_nv'        => $maNv,
                'trang_thai'   => 'nhap',
                'ghi_chu'      => $ghiChu,
            ]);

            foreach ($items as $item) {
                // Lấy tồn hệ thống hiện tại làm snapshot
                $slHt = DB::table('TON_KHO')
                    ->where('ma_chi_nhanh', $maChiNhanh)
                    ->where('ma_nl', $item['ma_nl'])
                    ->value('sl_ton_kho_he_thong') ?? 0;

                ChiTietKiemKe::create([
                    'ma_pkk'      => $maPkk,
                    'ma_nl'       => $item['ma_nl'],
                    'sl_he_thong' => $slHt,
                    'sl_thuc_te'  => $item['sl_thuc_te'],
                    'don_gia_tb'  => $item['don_gia_tb'] ?? null,
                ]);
            }

            return $phieu;
        });
    }

    /** Xác nhận → TRG_KIEM_KE_CAP_NHAT_TON tự đồng bộ */
    public function confirm(string $maPkk): void
    {
        DB::transaction(function () use ($maPkk) {
            $phieu = PhieuKiemKe::with('chiTietKiemKes')
                                ->where('ma_pkk', $maPkk)
                                ->where('trang_thai', 'nhap')
                                ->lockForUpdate()
                                ->firstOrFail();
            foreach ($phieu->chiTietKiemKes as $detail) {
                DB::table('TON_KHO')->updateOrInsert(
                    [
                        'ma_chi_nhanh' => $phieu->ma_chi_nhanh,
                        'ma_nl' => $detail->ma_nl,
                    ],
                    [
                        'sl_ton_kho_he_thong' => $detail->sl_thuc_te,
                        'sl_ton_kho_thuc_te' => $detail->sl_thuc_te,
                    ]
                );
            }
            $phieu->update(['trang_thai' => 'da_xac_nhan']);
        });
    }

    public function cancel(string $maPkk): void
    {
        PhieuKiemKe::where('ma_pkk', $maPkk)
            ->where('trang_thai', 'nhap')
            ->update(['trang_thai' => 'da_huy']);
    }
}
