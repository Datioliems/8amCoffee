<?php

namespace App\Http\Controllers;

use App\Models\NguyenLieu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NguyenLieuController extends Controller
{
    public function index(Request $request)
    {
        $keyword = trim((string) $request->get('q', ''));
        $maChiNhanh = session('ma_chi_nhanh');

        $nguyenLieus = NguyenLieu::query()
            ->with(['tonKhos' => function ($query) use ($maChiNhanh) {
                if ($maChiNhanh) {
                    $query->where('ma_chi_nhanh', $maChiNhanh);
                }
            }])
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query->where('ma_nl', 'like', "%{$keyword}%")
                        ->orWhere('ten_nl', 'like', "%{$keyword}%")
                        ->orWhere('don_vi', 'like', "%{$keyword}%");
                });
            })
            ->orderBy('ten_nl')
            ->paginate(20)
            ->withQueryString();

        return view('inventory.nguyen-lieu-list', compact('nguyenLieus', 'keyword'));
    }

    public function create()
    {
        $nextMaNl = $this->generateMaterialCode();

        return view('inventory.nguyen-lieu-form', compact('nextMaNl'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ten_nl'          => 'required|string|max:100|unique:NGUYEN_LIEU,ten_nl',
            'don_vi'          => 'required|in:gram,kg,ml,lit,cai,goi,hop,chai,tui',
            'nguong_canh_bao' => 'nullable|numeric|min:0',
        ]);

        $nguongCanhBao = isset($validated['nguong_canh_bao']) ? (float) $validated['nguong_canh_bao'] : null;
        $maChiNhanh    = (string) session('ma_chi_nhanh', '');
        unset($validated['nguong_canh_bao']);

        DB::transaction(function () use (&$validated, $nguongCanhBao, $maChiNhanh) {
            $validated['ma_nl'] = $this->generateMaterialCode(true);
            NguyenLieu::create($validated);

            if ($nguongCanhBao !== null && $maChiNhanh !== '') {
                DB::table('TON_KHO')->updateOrInsert(
                    ['ma_chi_nhanh' => $maChiNhanh, 'ma_nl' => $validated['ma_nl']],
                    ['nguong_canh_bao' => $nguongCanhBao]
                );
            }
        });

        return redirect()->route('inventory.materials.index')
            ->with('success', 'Đã thêm nguyên liệu: '.$validated['ten_nl']);
    }

    public function edit(string $material)
    {
        $maChiNhanh    = (string) session('ma_chi_nhanh', '');
        $nguyenLieu    = NguyenLieu::findOrFail($material);
        $tonKho        = DB::table('TON_KHO')
            ->where('ma_chi_nhanh', $maChiNhanh)
            ->where('ma_nl', $material)
            ->first();
        $nguongCanhBao = $tonKho?->nguong_canh_bao ?? 0;

        return view('inventory.nguyen-lieu-form', compact('nguyenLieu', 'nguongCanhBao'));
    }

    public function update(Request $request, string $material)
    {
        $nguyenLieu = NguyenLieu::findOrFail($material);
        $maChiNhanh = (string) session('ma_chi_nhanh', '');

        $validated = $request->validate([
            'ten_nl'          => 'required|string|max:100|unique:NGUYEN_LIEU,ten_nl,'.$material.',ma_nl',
            'don_vi'          => 'required|in:gram,kg,ml,lit,cai,goi,hop,chai,tui',
            'nguong_canh_bao' => 'nullable|numeric|min:0',
        ]);

        $nguongCanhBao = isset($validated['nguong_canh_bao']) ? (float) $validated['nguong_canh_bao'] : null;
        unset($validated['nguong_canh_bao']);

        $nguyenLieu->update($validated);

        if ($nguongCanhBao !== null && $maChiNhanh !== '') {
            DB::table('TON_KHO')->updateOrInsert(
                ['ma_chi_nhanh' => $maChiNhanh, 'ma_nl' => $material],
                ['nguong_canh_bao' => $nguongCanhBao]
            );
        }

        return redirect()->route('inventory.materials.index')
            ->with('success', 'Đã cập nhật: '.$nguyenLieu->ten_nl);
    }

    public function destroy(string $material)
    {
        $nguyenLieu = NguyenLieu::findOrFail($material);

        $referenceTables = [
            'DINH_MUC' => 'định mức món',
            'TON_KHO' => 'tồn kho',
            'CHI_TIET_NHAP_KHO' => 'phiếu nhập kho',
            'CHI_TIET_KIEM_KE' => 'phiếu kiểm kê',
        ];

        foreach ($referenceTables as $table => $label) {
            if (DB::table($table)->where('ma_nl', $nguyenLieu->ma_nl)->exists()) {
                return back()->with('error', "Không thể xóa nguyên liệu vì đang có dữ liệu {$label}. Hãy chỉnh các liên kết liên quan trước khi xóa.");
            }
        }

        $nguyenLieu->delete();

        return redirect()->route('inventory.materials.index')
            ->with('success', 'Đã xóa nguyên liệu.');
    }

    private function generateMaterialCode(bool $lock = false): string
    {
        $query = DB::table('NGUYEN_LIEU');
        if ($lock) {
            $query->lockForUpdate();
        }

        $maxNumber = $query
            ->selectRaw("MAX(CAST(SUBSTRING(ma_nl, 3) AS UNSIGNED)) as max_code")
            ->value('max_code') ?? 0;

        do {
            $maxNumber++;
            $code = 'NL' . str_pad((string) $maxNumber, 3, '0', STR_PAD_LEFT);
        } while (NguyenLieu::whereKey($code)->exists());

        return $code;
    }
}
