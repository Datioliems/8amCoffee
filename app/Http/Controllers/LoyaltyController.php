<?php

namespace App\Http\Controllers;

use App\Models\GiaoDichDiem;
use App\Models\TheThanhVien;
use App\Services\LoyaltyService;
use Illuminate\Http\Request;

/**
 * Quản trị thẻ thành viên RFID + điểm (superadmin/admin).
 * Chương trình loyalty ở cấp chuỗi nên danh sách thẻ KHÔNG lọc theo chi nhánh.
 */
class LoyaltyController extends Controller
{
    public function __construct(private LoyaltyService $loyalty) {}

    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $trangThai = $request->get('trang_thai', '');

        $query = TheThanhVien::with('khachHang')->orderByDesc('tao_luc');

        if ($q !== '') {
            $key = strtoupper($q);
            $maKh = null;
            if (preg_match('/^0[0-9]{9}$/', $q)) {
                $maKh = optional($this->loyalty->findCustomerByPhone($q))->ma_kh;
            }
            $query->where(function ($w) use ($key, $maKh) {
                $w->where('uid_rfid', $key)->orWhere('ma_the', $key);
                if ($maKh) {
                    $w->orWhere('ma_kh', $maKh);
                }
            });
        }
        if (in_array($trangThai, ['hoat_dong', 'khoa', 'mat'], true)) {
            $query->where('trang_thai', $trangThai);
        }

        $cards = $query->paginate(20)->withQueryString();

        // Thống kê nhanh
        $thongKe = [
            'tong_the'   => TheThanhVien::count(),
            'hoat_dong'  => TheThanhVien::where('trang_thai', 'hoat_dong')->count(),
            'tong_diem'  => (int) TheThanhVien::where('trang_thai', 'hoat_dong')->sum('diem_hien_tai'),
        ];

        return view('staff.loyalty.index', compact('cards', 'q', 'trangThai', 'thongKe'));
    }

    public function show(string $maThe)
    {
        $card = TheThanhVien::with('khachHang')->where('ma_the', $maThe)->firstOrFail();
        $ledger = GiaoDichDiem::where('ma_the', $maThe)->orderByDesc('thoi_gian')->orderByDesc('id')->limit(100)->get();
        $ledgerBalance = $this->loyalty->ledgerBalance($maThe);

        return view('staff.loyalty.show', compact('card', 'ledger', 'ledgerBalance'));
    }

    /** Phát thẻ thủ công từ giao diện admin (theo UID + SĐT khách). */
    public function issue(Request $request)
    {
        $request->validate([
            'uid' => 'required|string|max:32',
            'sdt' => ['required', 'string', 'regex:/^0[0-9]{9}$/'],
        ]);

        $res = $this->loyalty->issueCard(
            strtoupper(trim($request->input('uid'))),
            (string) $request->input('sdt'),
            session('ma_chi_nhanh'),
        );

        return back()->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    /** Khóa / mở / báo mất thẻ. */
    public function updateStatus(Request $request, string $maThe)
    {
        $request->validate(['trang_thai' => 'required|in:hoat_dong,khoa,mat']);
        $card = TheThanhVien::where('ma_the', $maThe)->firstOrFail();
        $card->trang_thai = $request->input('trang_thai');
        $card->save();

        return back()->with('success', 'Đã cập nhật trạng thái thẻ ' . $card->ma_the . '.');
    }

    /** Điều chỉnh điểm thủ công (có ghi sổ cái, lý do bắt buộc). */
    public function adjust(Request $request, string $maThe)
    {
        $request->validate([
            'so_diem' => 'required|integer|not_in:0',
            'ly_do'   => 'required|string|max:255',
        ]);
        $card = TheThanhVien::where('ma_the', $maThe)->firstOrFail();
        $this->loyalty->adjust($card, (int) $request->input('so_diem'), (string) $request->input('ly_do'));

        return back()->with('success', 'Đã điều chỉnh điểm cho thẻ ' . $card->ma_the . '.');
    }
}
