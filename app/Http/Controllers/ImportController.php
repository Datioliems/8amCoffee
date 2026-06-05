<?php

namespace App\Http\Controllers;

use App\Services\ImportService;
use App\Models\PhieuNhapKho;
use App\Models\NhaCungCap;
use App\Models\NguyenLieu;
use Illuminate\Http\Request;

class ImportController extends Controller
{
    public function __construct(private ImportService $importService) {}

    public function index()
    {
        $imports = PhieuNhapKho::with('nhaCungCap')
            ->where('ma_chi_nhanh', session('ma_chi_nhanh'))
            ->orderByDesc('ngay_nk')
            ->paginate(15);
        return view('inventory.import-list', compact('imports'));
    }

    public function create(Request $request)
    {
        $nhaCungCaps   = NhaCungCap::orderBy('ten_ncc')->get();
        $nguyenLieus   = NguyenLieu::orderBy('ten_nl')->get();
        $preselectedNl = (string) $request->query('nl', '');
        return view('inventory.import-form', compact('nhaCungCaps', 'nguyenLieus', 'preselectedNl'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ma_ncc'           => 'required|exists:NHA_CUNG_CAP,ma_ncc',
            'items'            => 'required|array|min:1',
            'items.*.ma_nl'    => 'required|exists:NGUYEN_LIEU,ma_nl',
            'items.*.so_luong' => 'required|numeric|min:0.01',
            'items.*.don_gia'  => 'required|integer|min:1',
            'ghi_chu'          => 'nullable|string|max:300',
        ]);

        $this->importService->createImport(
            maChiNhanh: session('ma_chi_nhanh'),
            maNv:       session('ma_nv'),
            maNcc:      $validated['ma_ncc'],
            items:      $validated['items'],
            ghiChu:     $validated['ghi_chu'] ?? null,
        );

        return redirect()->route('inventory.import.index')
                         ->with('success', 'Tạo phiếu nhập kho thành công.');
    }

    public function show(string $id)
    {
        $import = PhieuNhapKho::with(['nhaCungCap','chiTietNhapKhos.nguyenLieu'])->findOrFail($id);
        return view('inventory.import-detail', compact('import'));
    }

    /** Duyệt phiếu — Trigger SQL sẽ cộng tồn kho tự động */
    public function approve(string $id)
    {
        $this->importService->approve($id);
        return back()->with('success', 'Đã duyệt phiếu nhập. Tồn kho đã được cập nhật.');
    }

    /** Hủy phiếu chờ duyệt */
    public function cancel(string $id)
    {
        $this->importService->cancel($id);
        return back()->with('success', 'Đã hủy phiếu nhập.');
    }
}
