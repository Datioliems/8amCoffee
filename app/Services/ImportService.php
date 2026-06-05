<?php

namespace App\Services;

use App\Models\PhieuNhapKho;
use App\Models\ChiTietNhapKho;
use Illuminate\Support\Facades\DB;

class ImportService
{
    public function createImport(string $maChiNhanh, string $maNv, string $maNcc, array $items, ?string $ghiChu): PhieuNhapKho
    {
        return DB::transaction(function () use ($maChiNhanh, $maNv, $maNcc, $items, $ghiChu) {
            $maPnk = 'PNK' . now()->format('YmdHis');

            $phieu = PhieuNhapKho::create([
                'ma_pnk'       => $maPnk,
                'ma_ncc'       => $maNcc,
                'ma_nv'        => $maNv,
                'ma_chi_nhanh' => $maChiNhanh,
                'trang_thai'   => 'cho_duyet',
                'ghi_chu'      => $ghiChu,
            ]);

            $mergedItems = collect($items)
                ->groupBy('ma_nl')
                ->map(fn($group) => [
                    'ma_nl'    => $group->first()['ma_nl'],
                    'so_luong' => $group->sum('so_luong'),
                    'don_gia'  => $group->last()['don_gia'],
                ])
                ->values()
                ->all();

            foreach ($mergedItems as $item) {
                ChiTietNhapKho::create([
                    'ma_pnk'    => $maPnk,
                    'ma_nl'     => $item['ma_nl'],
                    'so_luong'  => $item['so_luong'],
                    'don_gia'   => $item['don_gia'],
                ]);
            }
            // TRG_NHAP_KHO_TINH_TONG sẽ tự tính tổng giá trị phiếu

            return $phieu;
        });
    }

    /** Duyệt phiếu → TRG_NHAP_KHO_CAP_NHAT_TON tự cộng kho */
    public function approve(string $maPnk): void
    {
        DB::transaction(function () use ($maPnk) {
            $phieu = PhieuNhapKho::where('ma_pnk', $maPnk)
                                 ->where('trang_thai', 'cho_duyet')
                                 ->lockForUpdate()
                                 ->firstOrFail();
            $phieu->update(['trang_thai' => 'da_duyet']);
        });
    }

    public function cancel(string $maPnk): void
    {
        PhieuNhapKho::where('ma_pnk', $maPnk)
                    ->where('trang_thai', 'cho_duyet')
                    ->update(['trang_thai' => 'da_huy']);
    }
}
