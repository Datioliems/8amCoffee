<?php

namespace App\Http\Controllers;

use App\Models\CauHinhHang;
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

        // Khách đủ điều kiện phát thẻ (chọn trong dropdown khi phát thẻ).
        $eligible = $this->loyalty->eligibleForCard();

        return view('staff.loyalty.index', compact('cards', 'q', 'trangThai', 'thongKe', 'eligible'));
    }

    public function show(string $maThe)
    {
        $card = TheThanhVien::with('khachHang')->where('ma_the', $maThe)->firstOrFail();
        $ledger = GiaoDichDiem::where('ma_the', $maThe)->orderByDesc('thoi_gian')->orderByDesc('id')->limit(100)->get();
        $ledgerBalance = $this->loyalty->ledgerBalance($maThe);

        // Quyền lợi của hạng hiện tại (hệ số tích điểm + ưu đãi giảm giá).
        $tier = [
            'nhan'  => $this->loyalty->tierLabel($card->hang_the),
            'he_so' => $this->loyalty->earnMultiplier($card->hang_the),
            'giam'  => $this->loyalty->tierDiscount($card->hang_the),
        ];

        return view('staff.loyalty.show', compact('card', 'ledger', 'ledgerBalance', 'tier'));
    }

    /** Phát thẻ thủ công: UID + (chọn khách trong danh sách `ma_kh` HOẶC nhập SĐT). */
    public function issue(Request $request)
    {
        $request->validate([
            'uid'   => 'required|string|max:32',
            'ma_kh' => 'nullable|string|max:10',
            'sdt'   => ['nullable', 'string', 'regex:/^0[0-9]{9}$/'],
        ]);

        if (! $request->filled('ma_kh') && ! $request->filled('sdt')) {
            return back()->with('error', 'Hãy chọn khách trong danh sách hoặc nhập số điện thoại.');
        }

        $uid = strtoupper(trim($request->input('uid')));
        $maChiNhanh = session('ma_chi_nhanh');

        $res = $request->filled('ma_kh')
            ? $this->loyalty->issueCardByMaKh($uid, (string) $request->input('ma_kh'), $maChiNhanh)
            : $this->loyalty->issueCard($uid, (string) $request->input('sdt'), $maChiNhanh);

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

    /** Trang cấu hình HẠNG hội viên (hệ số tích điểm + ưu đãi giảm). */
    public function tiersConfig()
    {
        try {
            $tiers = CauHinhHang::orderBy('nguong')->get();
        } catch (\Throwable $e) {
            $tiers = collect();
        }

        // Fallback hiển thị từ config nếu bảng chưa có/trống (chưa migrate).
        $chuaMigrate = $tiers->isEmpty();
        if ($chuaMigrate) {
            $tiers = collect(config('loyalty.tiers', []))
                ->map(fn ($c, $ma) => (object) array_merge(['ma_hang' => $ma], $c))
                ->values();
        }

        return view('staff.loyalty.tiers', compact('tiers', 'chuaMigrate'));
    }

    /** Lưu cấu hình hạng (sửa hệ số / loại giảm / mức giảm / ngưỡng / nhãn). */
    public function tiersUpdate(Request $request)
    {
        $data = $request->validate([
            'hang'                  => 'required|array',
            'hang.*.nhan'           => 'required|string|max:50',
            'hang.*.nguong'         => 'required|integer|min:0',
            'hang.*.he_so'          => 'required|numeric|min:0|max:99',
            'hang.*.giam_loai'      => 'required|in:phan_tram,tien',
            'hang.*.giam_gia_tri'   => 'required|numeric|min:0',
        ]);

        try {
            foreach ($data['hang'] as $maHang => $row) {
                $giaTri = (float) $row['giam_gia_tri'];
                if ($row['giam_loai'] === 'phan_tram') {
                    $giaTri = min(100, $giaTri);   // % không quá 100
                }
                CauHinhHang::updateOrCreate(
                    ['ma_hang' => $maHang],
                    [
                        'nhan'         => $row['nhan'],
                        'nguong'       => (int) $row['nguong'],
                        'he_so'        => (float) $row['he_so'],
                        'giam_loai'    => $row['giam_loai'],
                        'giam_gia_tri' => $giaTri,
                    ]
                );
            }
        } catch (\Throwable $e) {
            return back()->with('error', 'Chưa lưu được — hãy chạy "php artisan migrate" để tạo bảng CAU_HINH_HANG.');
        }

        return back()->with('success', 'Đã lưu cấu hình hạng hội viên.');
    }
}
