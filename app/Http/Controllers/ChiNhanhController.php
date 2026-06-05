<?php

namespace App\Http\Controllers;

use App\Models\ChiNhanh;
use Illuminate\Http\Request;

class ChiNhanhController extends Controller
{
    /** Super-admin đổi chi nhánh đang xem (cập nhật session ma_chi_nhanh). */
    public function switch(Request $request)
    {
        $request->validate([
            'ma_chi_nhanh' => 'required|exists:CHI_NHANH,ma_chi_nhanh',
        ]);

        session(['ma_chi_nhanh' => $request->ma_chi_nhanh]);

        return back()->with('success', 'Đã chuyển sang chi nhánh '.$request->ma_chi_nhanh);
    }

    /** Super-admin sửa thông tin chi nhánh (tên, địa chỉ, SĐT, model 3D). */
    public function update(Request $request, string $maChiNhanh)
    {
        $data = $request->validate([
            'ten_chi_nhanh' => 'required|string|max:100',
            'dia_chi'       => 'nullable|string|max:255',
            'sdt'           => 'nullable|string|max:15',
            'model_3d'      => 'nullable|string|max:120',
        ]);

        $chiNhanh = ChiNhanh::where('ma_chi_nhanh', $maChiNhanh)->firstOrFail();
        $chiNhanh->update($data);

        return back()->with('success', "Đã cập nhật chi nhánh {$maChiNhanh}.");
    }
}
