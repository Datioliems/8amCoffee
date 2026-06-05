<?php

namespace App\Http\Controllers;

use App\Models\NguyenLieu;
use App\Services\InventoryService;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function __construct(private InventoryService $inventoryService) {}

    /** Tổng quan tồn kho */
    public function index(Request $request)
    {
        $maChiNhanh  = (string) session('ma_chi_nhanh', '');
        $keyword     = trim((string) $request->get('q', ''));
        $stockStatus = $request->get('stock_status');

        // Dùng LEFT JOIN (load tất cả NL, kể cả chưa có bản ghi TON_KHO)
        // để summary cards khớp với những gì bảng hiển thị.
        $allMaterials = NguyenLieu::with(['tonKhos' => fn($q) => $q->where('ma_chi_nhanh', $maChiNhanh)])->get();

        $totalMaterials = $allMaterials->count();
        $outOfStock = $allMaterials->filter(function ($m) {
            return ((float) ($m->tonKhos->first()?->sl_ton_kho_he_thong ?? 0)) <= 0;
        })->count();
        $lowStock = $allMaterials->filter(function ($m) {
            $ton    = (float) ($m->tonKhos->first()?->sl_ton_kho_he_thong ?? 0);
            $nguong = (float) ($m->tonKhos->first()?->nguong_canh_bao ?? 0);
            return $ton > 0 && $nguong > 0 && $ton <= $nguong;  // khớp với logic trong view
        })->count();
        $materials = NguyenLieu::with(['tonKhos' => function ($query) use ($maChiNhanh) {
                $query->where('ma_chi_nhanh', $maChiNhanh);
            }])
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query->where('ma_nl', 'like', "%{$keyword}%")
                        ->orWhere('ten_nl', 'like', "%{$keyword}%")
                        ->orWhere('don_vi', 'like', "%{$keyword}%");
                });
            })
            ->when(in_array($stockStatus, ['out', 'low', 'ok'], true), function ($query) use ($stockStatus, $maChiNhanh) {
                if ($stockStatus === 'out') {
                    // Hết hàng = không có bản ghi TON_KHO, hoặc sl_ton_kho_he_thong <= 0
                    $query->where(function ($q) use ($maChiNhanh) {
                        $q->whereDoesntHave('tonKhos', fn($s) => $s->where('ma_chi_nhanh', $maChiNhanh))
                          ->orWhereHas('tonKhos', fn($s) => $s->where('ma_chi_nhanh', $maChiNhanh)
                              ->where('sl_ton_kho_he_thong', '<=', 0));
                    });
                } elseif ($stockStatus === 'low') {
                    $query->whereHas('tonKhos', fn($q) => $q->where('ma_chi_nhanh', $maChiNhanh)
                        ->where('sl_ton_kho_he_thong', '>', 0)
                        ->whereColumn('sl_ton_kho_he_thong', '<=', 'nguong_canh_bao'));
                } else {
                    $query->whereHas('tonKhos', fn($q) => $q->where('ma_chi_nhanh', $maChiNhanh)
                        ->whereColumn('sl_ton_kho_he_thong', '>', 'nguong_canh_bao'));
                }
            })
            ->orderBy('ten_nl')
            ->paginate(10)
            ->withQueryString();

        return view('inventory.stock-overview', compact(
            'totalMaterials',
            'outOfStock',
            'lowStock',
            'materials',
            'keyword',
            'stockStatus',
        ));
    }

    /** Danh sách nguyên liệu sắp hết */
    public function lowStock()
    {
        $alerts = $this->inventoryService->getLowStockAlerts(session('ma_chi_nhanh'));
        return view('inventory.low-stock', compact('alerts'));
    }
}
