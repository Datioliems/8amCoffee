<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LichSuQuetThe;
use App\Models\TheThanhVien;
use App\Models\ThietBiArduino;
use App\Services\LoyaltyService;
use App\Support\Pii;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * API cho đầu đọc Arduino (ESP32 + RC522). Tất cả route đã qua middleware
 * 'arduino.device' nên $request->attributes->get('device') luôn có thiết bị hợp lệ.
 */
class ArduinoController extends Controller
{
    public function __construct(private LoyaltyService $loyalty) {}

    /** Thiết bị đã xác thực (gắn bởi middleware). */
    private function device(Request $request): ThietBiArduino
    {
        return $request->attributes->get('device');
    }

    /** Chuẩn hóa UID gửi lên (hex, viết hoa). */
    private function uid(Request $request): string
    {
        return strtoupper(trim((string) $request->input('uid')));
    }

    /**
     * Nhận diện thẻ khi quẹt. Trả thông tin chủ thẻ + số dư điểm.
     * Body: { uid }
     */
    public function quet(Request $request): JsonResponse
    {
        $request->validate(['uid' => 'required|string|max:32']);
        $device = $this->device($request);
        $uid = $this->uid($request);

        $card = TheThanhVien::with('khachHang')->where('uid_rfid', $uid)->first();

        if (! $card) {
            LichSuQuetThe::ghi([
                'uid_rfid' => $uid, 'ma_thiet_bi' => $device->ma_thiet_bi,
                'ma_chi_nhanh' => $device->ma_chi_nhanh, 'hanh_dong' => 'khong_xac_dinh',
                'ket_qua' => 'that_bai', 'chi_tiet' => 'The chua dang ky', 'ip' => $request->ip(),
            ]);
            return response()->json([
                'ok' => true, 'found' => false, 'blank' => true,
                'message' => 'Thẻ chưa đăng ký — có thể dùng để phát thẻ cho khách.',
            ]);
        }

        LichSuQuetThe::ghi([
            'uid_rfid' => $uid, 'ma_the' => $card->ma_the, 'ma_thiet_bi' => $device->ma_thiet_bi,
            'ma_chi_nhanh' => $device->ma_chi_nhanh, 'hanh_dong' => 'nhan_dien',
            'ket_qua' => 'thanh_cong', 'ip' => $request->ip(),
        ]);

        return response()->json([
            'ok'         => true,
            'found'      => true,
            'ma_the'     => $card->ma_the,
            'ma_kh'      => $card->ma_kh,
            'ten_kh'     => $card->khachHang ? $card->khachHang->ten_kh : null, // cast tự giải mã
            'diem'       => (int) $card->diem_hien_tai,
            'gia_tri'    => $card->giaTriTien(),
            'hang_the'   => $card->hang_the,
            'trang_thai' => $card->trang_thai,
        ]);
    }

    /**
     * Phát thẻ cho khách (định danh qua SĐT) + điểm khởi tạo theo chi tiêu.
     * Body: { uid, sdt }
     */
    public function phatThe(Request $request): JsonResponse
    {
        $request->validate([
            'uid' => 'required|string|max:32',
            'sdt' => ['required', 'string', 'regex:/^0[0-9]{9}$/'],
        ]);
        $device = $this->device($request);

        $res = $this->loyalty->issueCard(
            $this->uid($request),
            (string) $request->input('sdt'),
            $device->ma_chi_nhanh,
            $device->ma_thiet_bi,
        );

        if (! ($res['ok'] ?? false)) {
            return response()->json(['ok' => false, 'message' => $res['message']], 422);
        }

        return response()->json([
            'ok'      => true,
            'message' => $res['message'],
            'ma_the'  => $res['card']->ma_the,
            'ten_kh'  => $res['ten_kh'] ?? null,
            'diem'    => $res['diem'] ?? 0,
            'spend'   => $res['spend'] ?? 0,
        ]);
    }

    /**
     * Tích điểm thủ công (vd quẹt tại quầy theo số tiền hóa đơn tiền mặt).
     * Body: { uid, so_tien, ma_order? }
     */
    public function tichDiem(Request $request): JsonResponse
    {
        $request->validate([
            'uid'      => 'required|string|max:32',
            'so_tien'  => 'required|numeric|min:0',
            'ma_order' => 'nullable|string|max:20',
        ]);
        $device = $this->device($request);
        $card = $this->findActiveCard($request);
        if (! $card instanceof TheThanhVien) {
            return $card; // JsonResponse lỗi
        }

        $diem = $this->loyalty->earn(
            $card,
            (float) $request->input('so_tien'),
            $request->input('ma_order'),
            null,
            $device->ma_thiet_bi,
        );
        $card->refresh();

        return response()->json([
            'ok' => true, 'message' => "Đã cộng {$diem} điểm.",
            'diem_cong' => $diem, 'diem' => (int) $card->diem_hien_tai, 'hang_the' => $card->hang_the,
        ]);
    }

    /**
     * Đổi điểm lấy giảm giá. Trả về SỐ TIỀN giảm.
     * Body: { uid, so_diem, tong_hoa_don?, ma_order? }
     */
    public function doiDiem(Request $request): JsonResponse
    {
        $request->validate([
            'uid'          => 'required|string|max:32',
            'so_diem'      => 'required|integer|min:1',
            'tong_hoa_don' => 'nullable|numeric|min:0',
            'ma_order'     => 'nullable|string|max:20',
        ]);
        $device = $this->device($request);
        $card = $this->findActiveCard($request);
        if (! $card instanceof TheThanhVien) {
            return $card;
        }

        // redeem() ném ValidationException (→ 422 JSON) khi không hợp lệ.
        $tienGiam = $this->loyalty->redeem(
            $card,
            (int) $request->input('so_diem'),
            $request->filled('tong_hoa_don') ? (float) $request->input('tong_hoa_don') : null,
            $request->input('ma_order'),
            $device->ma_thiet_bi,
        );
        $card->refresh();

        return response()->json([
            'ok' => true, 'message' => "Đã đổi điểm, giảm " . number_format($tienGiam, 0, ',', '.') . "đ.",
            'tien_giam' => $tienGiam, 'diem' => (int) $card->diem_hien_tai,
        ]);
    }

    /** Báo sống / đồng bộ giờ cho thiết bị. */
    public function heartbeat(Request $request): JsonResponse
    {
        $device = $this->device($request);
        return response()->json([
            'ok' => true,
            'device' => $device->ma_thiet_bi,
            'chi_nhanh' => $device->ma_chi_nhanh,
            'server_time' => now()->toIso8601String(),
        ]);
    }

    /** Tìm thẻ đang hoạt động theo UID; trả JsonResponse lỗi nếu không hợp lệ. */
    private function findActiveCard(Request $request): TheThanhVien|JsonResponse
    {
        $card = TheThanhVien::where('uid_rfid', $this->uid($request))->first();
        if (! $card) {
            return response()->json(['ok' => false, 'message' => 'Thẻ chưa đăng ký.'], 404);
        }
        if (! $card->dangHoatDong()) {
            return response()->json(['ok' => false, 'message' => 'Thẻ đang bị khóa hoặc báo mất.'], 422);
        }
        return $card;
    }
}
