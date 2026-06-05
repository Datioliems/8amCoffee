<?php

namespace App\Services;

use App\Models\HoaDon;
use App\Models\Order;
use App\Models\OrderLog;
use App\Services\OrderService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    private const PAYABLE_STATUSES = ['cho_xac_nhan', 'da_xac_nhan', 'dang_pha_che', 'da_phuc_vu'];

    public function __construct(private OrderService $orderService) {}

    public function createInvoice(string $maOrder, float $chietKhau, string $phuongThuc, string $maNvThuNgan): string
    {
        return DB::transaction(function () use ($maOrder, $chietKhau, $phuongThuc, $maNvThuNgan) {
            $order = Order::with(['chiTietOrders.options', 'hoaDon'])
                ->where('ma_order', $maOrder)
                ->lockForUpdate()
                ->firstOrFail();

            // Chặn thanh toán lại nếu đã có hóa đơn
            if ($order->hoaDon) {
                return $order->hoaDon->ma_hoa_don;
            }

            // Chỉ cho thanh toán khi đơn ở trạng thái hợp lệ
            if (!in_array($order->trang_thai, self::PAYABLE_STATUSES, true)) {
                throw ValidationException::withMessages([
                    'trang_thai' => 'Đơn hàng ở trạng thái "' . $order->trang_thai . '" không thể thanh toán.',
                ]);
            }

            // Phát sinh giao dịch → lưu khách hàng vào danh sách (theo SĐT)
            $this->orderService->ensureCustomerForOrder($order);
            $order->refresh();

            // Tổng tiền = (giá món + phụ thu options) × số lượng
            $tongTruoc = $order->chiTietOrders->sum(
                fn($i) => ($i->don_gia_tai_thoi_diem + $i->options->sum('gia_them')) * $i->so_luong
            );
            $tongSau = $tongTruoc * (1 - $chietKhau / 100);

            do {
                $maHoaDon = 'HD' . now()->format('YmdHis') . random_int(1000, 9999);
            } while (HoaDon::whereKey($maHoaDon)->exists());

            HoaDon::create([
                'ma_hoa_don'         => $maHoaDon,
                'ma_order'           => $maOrder,
                'ma_kh'              => $order->ma_kh,
                'tong_tien_truoc_ck' => $tongTruoc,
                'chiet_khau'         => $chietKhau,
                'tong_tien_sau_ck'   => $tongSau,
                'phuong_thuc_tt'     => $phuongThuc,
                'trang_thai'         => 'da_thanh_toan',
                'ma_nv_thu_ngan'     => $maNvThuNgan,
            ]);

            $oldStatus = $order->trang_thai;
            $order->update([
                'trang_thai'           => 'hoan_thanh',
                'thoi_gian_thanh_toan' => now(),
                'thoi_gian_phuc_vu'    => $order->thoi_gian_phuc_vu ?? now(),
            ]);
            OrderLog::create([
                'ma_order'       => $maOrder,
                'hanh_dong'      => 'thanh_toan',
                'trang_thai_cu'  => $oldStatus,
                'trang_thai_moi' => 'hoan_thanh',
                'noi_dung'       => 'Don hang da thanh toan va tao hoa don.',
                'du_lieu'        => [
                    'ma_hoa_don'         => $maHoaDon,
                    'tong_tien_truoc_ck' => $tongTruoc,
                    'chiet_khau'         => $chietKhau,
                    'tong_tien_sau_ck'   => $tongSau,
                    'phuong_thuc_tt'     => $phuongThuc,
                ],
                'ma_nv'      => $maNvThuNgan,
                'created_at' => now(),
            ]);

            return $maHoaDon;
        });
    }
}
