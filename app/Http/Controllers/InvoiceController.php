<?php

namespace App\Http\Controllers;

use App\Models\HoaDon;
use App\Models\PhieuNhapKho;

class InvoiceController extends Controller
{
    /** Hóa đơn bán hàng (in) — theo mã đơn. */
    public function sale(string $maOrder)
    {
        $hoaDon = HoaDon::with([
                'order.chiTietOrders.mon',
                'order.chiTietOrders.options',
                'order.ban',
                'order.chiNhanh',
                'khachHang',
            ])
            ->where('ma_order', $maOrder)
            ->firstOrFail();

        // Phạm vi chi nhánh: nhân viên chỉ in hóa đơn chi nhánh mình
        abort_if(
            session('chuc_vu') !== 'superadmin' && $hoaDon->order?->ma_chi_nhanh !== session('ma_chi_nhanh'),
            403
        );

        return view('invoice.sale', compact('hoaDon'));
    }

    /** Phiếu/hóa đơn nhập nguyên liệu (in). */
    public function import(string $id)
    {
        $import = PhieuNhapKho::with(['nhaCungCap', 'nhanVien', 'chiTietNhapKhos.nguyenLieu'])
            ->findOrFail($id);

        abort_if(
            session('chuc_vu') !== 'superadmin' && $import->ma_chi_nhanh !== session('ma_chi_nhanh'),
            403
        );

        $chiNhanh = \App\Models\ChiNhanh::find($import->ma_chi_nhanh);

        return view('invoice.import', compact('import', 'chiNhanh'));
    }
}
