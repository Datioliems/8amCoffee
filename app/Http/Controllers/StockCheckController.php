<?php

namespace App\Http\Controllers;

use App\Models\NguyenLieu;
use App\Models\PhieuKiemKe;
use App\Services\StockCheckService;
use Illuminate\Http\Request;

class StockCheckController extends Controller
{
    public function __construct(private StockCheckService $service) {}

    public function index()
    {
        $checks = PhieuKiemKe::with('nhanVien')
            ->where('ma_chi_nhanh', session('ma_chi_nhanh'))
            ->orderByDesc('ngay_kk')
            ->paginate(10);

        return view('inventory.stockcheck-list', compact('checks'));
    }

    public function create()
    {
        $nguyenLieus = NguyenLieu::orderBy('ten_nl')->get();

        return view('inventory.stockcheck-form', compact('nguyenLieus'));
    }

    public function show(string $id)
    {
        $check = PhieuKiemKe::with(['nhanVien', 'chiTietKiemKes.nguyenLieu'])
            ->where('ma_chi_nhanh', session('ma_chi_nhanh'))
            ->findOrFail($id);

        return view('inventory.stockcheck-detail', compact('check'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'items'              => 'required|array|min:1',
            'items.*.ma_nl'      => 'required|exists:NGUYEN_LIEU,ma_nl',
            'items.*.sl_thuc_te' => 'required|numeric|min:0',
            'ghi_chu'            => 'nullable|string|max:300',
        ]);

        $this->service->createCheck(
            maChiNhanh: session('ma_chi_nhanh'),
            maNv: session('ma_nv'),
            items: $validated['items'],
            ghiChu: $validated['ghi_chu'] ?? null,
        );

        return redirect()->route('inventory.stockcheck.index')
            ->with('success', 'Tạo phiếu kiểm kê thành công.');
    }

    public function confirm(string $id)
    {
        $this->service->confirm($id);

        return back()->with('success', 'Đã xác nhận kiểm kê. Tồn kho thực tế đã được cập nhật.');
    }

    public function cancel(string $id)
    {
        $this->service->cancel($id);

        return back()->with('success', 'Đã hủy phiếu kiểm kê.');
    }
}
