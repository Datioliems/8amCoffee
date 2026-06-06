<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\ThanhToanOnline;
use App\Services\OrderService;
use App\Services\PaymentService;
use App\Services\VnpayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentService $paymentService,
        private VnpayService $vnpay,
        private OrderService $orderService,
    ) {}

    public function show(string $maOrder)
    {
        // Scope theo chi nhánh của staff đang đăng nhập
        $order = Order::with(['ban', 'chiTietOrders.mon', 'chiTietOrders.options', 'hoaDon'])
            ->where('ma_order', $maOrder)
            ->where('ma_chi_nhanh', (string) session('ma_chi_nhanh', ''))
            ->firstOrFail();

        // ── Tự động gộp đơn cùng bàn + cùng tên khách (chưa thanh toán) ──────
        // Chỉ áp dụng cho đơn tại bàn (ma_ban không null) và chưa thanh toán.
        if ($order->ma_ban && ! $order->hoaDon) {
            $tenKhachGoc = mb_strtolower(trim($order->ten_khach ?? ''));

            $siblings = Order::where('ma_chi_nhanh', $order->ma_chi_nhanh)
                ->where('ma_ban', $order->ma_ban)
                ->where('ma_order', '<>', $order->ma_order)
                ->whereIn('trang_thai', ['cho_xac_nhan', 'da_xac_nhan', 'dang_pha_che', 'da_phuc_vu'])
                ->get()
                ->filter(fn($s) => mb_strtolower(trim($s->ten_khach ?? '')) === $tenKhachGoc);

            if ($siblings->isNotEmpty()) {
                $merged = 0;
                foreach ($siblings as $sib) {
                    try {
                        $this->orderService->merge($order->ma_order, $sib->ma_order);
                        $merged++;
                    } catch (\Throwable) {
                        // Bỏ qua nếu gộp thất bại (VD: đơn đã bị hủy)
                    }
                }
                if ($merged > 0) {
                    return redirect()->route('payment.show', $maOrder)
                        ->with('info', "Đã tự động gộp {$merged} đơn cùng bàn của khách \"{$order->ten_khach}\".");
                }
            }
        }
        // ─────────────────────────────────────────────────────────────────────

        $tongTien = $this->tinhTongTien($order);

        $mergeTargets = Order::with('ban')
            ->where('ma_chi_nhanh', $order->ma_chi_nhanh)
            ->where('ma_order', '<>', $order->ma_order)
            ->whereIn('trang_thai', ['cho_xac_nhan', 'da_xac_nhan', 'dang_pha_che', 'da_phuc_vu'])
            ->orderByDesc('ngay_order')
            ->orderByDesc('gio_order')
            ->limit(30)
            ->get();

        $vnpayReady = $this->vnpay->configured();

        // Danh sách thẻ đang hoạt động cho autocomplete + tra cứu nhanh ở POS.
        $cardHolders = app(\App\Services\LoyaltyService::class)->activeCardHolders();

        return view('staff.payment', compact('order', 'tongTien', 'mergeTargets', 'vnpayReady', 'cardHolders'));
    }

    public function process(Request $request, string $maOrder)
    {
        // Scope theo chi nhánh
        Order::where('ma_order', $maOrder)
            ->where('ma_chi_nhanh', (string) session('ma_chi_nhanh', ''))
            ->firstOrFail();

        $request->validate([
            'chiet_khau'     => 'required|numeric|min:0|max:100',
            'phuong_thuc_tt' => 'required|in:tien_mat,chuyen_khoan,the,vi_dien_tu,momo,vnpay',
            'ma_the'         => 'nullable|string|max:20',
            'so_diem_doi'    => 'nullable|integer|min:0',
        ]);

        $chietKhau   = (float)  $request->input('chiet_khau', 0);
        $phuongThuc  = (string) $request->input('phuong_thuc_tt', '');
        $maNv        = (string) (session('ma_nv') ?? '');
        $maThe       = $request->filled('ma_the') ? (string) $request->input('ma_the') : null;
        $soDiemDoi   = (int) $request->input('so_diem_doi', 0);

        try {
            $maHoaDon = $this->paymentService->createInvoice(
                maOrder:     $maOrder,
                chietKhau:   $chietKhau,
                phuongThuc:  $phuongThuc,
                maNvThuNgan: $maNv,
                maThe:       $maThe,
                soDiemDoi:   $soDiemDoi,
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        // Ở lại trang thanh toán để hiện kết quả + in hóa đơn (không nhảy về danh sách đơn)
        return redirect()->route('payment.show', $maOrder)
                         ->with('success', "Thanh toán thành công! Hóa đơn: {$maHoaDon}");
    }

    /**
     * Tra cứu thẻ thành viên ở màn POS (AJAX). Nhận `q` = UID thẻ hoặc SĐT khách.
     * Trả JSON số dư điểm + cấu hình quy đổi để tính giảm giá phía client.
     */
    public function cardLookup(Request $request, string $maOrder)
    {
        $q = strtoupper(trim((string) $request->query('q', '')));
        if ($q === '') {
            return response()->json(['ok' => false, 'message' => 'Nhập UID thẻ hoặc số điện thoại.'], 422);
        }

        $loyalty = app(\App\Services\LoyaltyService::class);

        // Thử theo UID trước, sau đó theo SĐT (qua blind index).
        $card = \App\Models\TheThanhVien::with('khachHang')->where('uid_rfid', $q)->first();
        if (! $card && preg_match('/^0[0-9]{9}$/', $q)) {
            $kh = $loyalty->findCustomerByPhone($q);
            if ($kh) {
                $card = \App\Models\TheThanhVien::with('khachHang')
                    ->where('ma_kh', $kh->ma_kh)->where('trang_thai', 'hoat_dong')->first();
            }
        }

        if (! $card) {
            return response()->json(['ok' => false, 'message' => 'Không tìm thấy thẻ thành viên.'], 404);
        }
        if (! $card->dangHoatDong()) {
            return response()->json(['ok' => false, 'message' => 'Thẻ đang bị khóa hoặc báo mất.'], 422);
        }

        $uuDai = $loyalty->tierDiscount($card->hang_the);

        return response()->json([
            'ok'             => true,
            'ma_the'         => $card->ma_the,
            'ten_kh'         => $card->khachHang?->ten_kh,
            'diem'           => (int) $card->diem_hien_tai,
            'hang_the'       => $card->hang_the,
            'hang_nhan'      => $loyalty->tierLabel($card->hang_the),
            'he_so'          => $loyalty->earnMultiplier($card->hang_the),
            'giam_loai'      => $uuDai['loai'],         // 'phan_tram' | 'tien'
            'giam_gia_tri'   => $uuDai['gia_tri'],      // % hoặc đồng
            'point_value'    => (int) config('loyalty.point_value', 50),
            'min_redeem'     => (int) config('loyalty.min_redeem', 100),
            'max_redeem_pct' => (int) config('loyalty.max_redeem_pct', 50),
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // VNPay sandbox
    // ─────────────────────────────────────────────────────────────

    /** Khởi tạo thanh toán VNPay → chuyển hướng sang cổng. */
    public function payVnpay(Request $request, string $maOrder)
    {
        if (!$this->vnpay->configured()) {
            return back()->with('error', 'Chưa cấu hình VNPay (VNPAY_TMN_CODE / VNPAY_HASH_SECRET).');
        }

        $order = Order::with(['chiTietOrders.options', 'hoaDon'])
            ->where('ma_order', $maOrder)
            ->where('ma_chi_nhanh', (string) session('ma_chi_nhanh', ''))
            ->firstOrFail();

        if ($order->hoaDon) {
            return redirect()->route('payment.show', $maOrder)->with('error', 'Đơn này đã thanh toán.');
        }

        $request->validate(['chiet_khau' => 'required|numeric|min:0|max:100']);
        $chietKhau = (float) $request->input('chiet_khau', 0);

        $tongTruoc = $this->tinhTongTien($order);
        $soTien    = (int) round($tongTruoc * (1 - $chietKhau / 100));

        if ($soTien < 1000) {
            return back()->with('error', 'Số tiền tối thiểu để thanh toán VNPay là 1.000đ.');
        }

        $txnRef = $this->vnpay->newTxnRef();

        ThanhToanOnline::create([
            'ma_giao_dich' => $txnRef,
            'ma_order'     => $maOrder,
            'cong'         => 'vnpay',
            'so_tien'      => $soTien,
            'chiet_khau'   => $chietKhau,
            'trang_thai'   => 'cho_xu_ly',
            'ma_nv'        => (string) (session('ma_nv') ?? ''),
            'thoi_gian_tao' => now(),
        ]);

        $returnUrl = config('vnpay.return_url') ?: route('payment.vnpay.return');
        $url = $this->vnpay->buildPaymentUrl(
            txnRef:    $txnRef,
            amountVnd: $soTien,
            orderInfo: 'Thanh toan don ' . $maOrder,
            ipAddr:    $request->ip() ?: '127.0.0.1',
            returnUrl: $returnUrl,
        );

        return redirect()->away($url);
    }

    /** Khách/nhân viên được VNPay chuyển hướng về sau khi thanh toán. */
    public function vnpayReturn(Request $request)
    {
        $params = $request->query();

        if (!$this->vnpay->validateChecksum($params)) {
            return $this->ketQuaVnpay(null, false, 'Chữ ký không hợp lệ (dữ liệu có thể bị giả mạo).');
        }

        $gd = ThanhToanOnline::where('ma_giao_dich', $params['vnp_TxnRef'] ?? '')->first();
        if (!$gd) {
            return $this->ketQuaVnpay(null, false, 'Không tìm thấy giao dịch.');
        }

        $responseCode = $params['vnp_ResponseCode'] ?? '';
        $thanhCong    = $responseCode === '00';

        // Đối soát số tiền (VNPay trả x100).
        $soTienVnpay = (int) round(((int) ($params['vnp_Amount'] ?? 0)) / 100);
        $khopTien    = $soTienVnpay === (int) $gd->so_tien;

        return DB::transaction(function () use ($gd, $params, $thanhCong, $khopTien, $responseCode) {
            // Idempotent: nếu đã xử lý thành công rồi thì trả luôn.
            if ($gd->trang_thai === 'thanh_cong' && $gd->ma_hoa_don) {
                return $this->ketQuaVnpay($gd->ma_order, true, "Hóa đơn {$gd->ma_hoa_don}.");
            }

            $gd->du_lieu = $params;
            $gd->ma_gd_cong = $params['vnp_TransactionNo'] ?? null;
            $gd->ma_phan_hoi = $responseCode;
            $gd->thoi_gian_cap_nhat = now();

            if ($thanhCong && $khopTien) {
                try {
                    $maHoaDon = $this->paymentService->createInvoice(
                        maOrder:     $gd->ma_order,
                        chietKhau:   (float) $gd->chiet_khau,
                        phuongThuc:  'vnpay',
                        maNvThuNgan: (string) $gd->ma_nv,
                    );
                    $gd->ma_hoa_don = $maHoaDon;
                    $gd->trang_thai = 'thanh_cong';
                    $gd->save();
                    return $this->ketQuaVnpay($gd->ma_order, true, "Thanh toán VNPay thành công! Hóa đơn: {$maHoaDon}");
                } catch (ValidationException $e) {
                    $gd->trang_thai = 'that_bai';
                    $gd->save();
                    Log::warning('VNPay createInvoice fail', ['ma_order' => $gd->ma_order, 'err' => $e->getMessage()]);
                    return $this->ketQuaVnpay($gd->ma_order, false, 'Thanh toán thành công nhưng không tạo được hóa đơn: ' . $e->validator->errors()->first());
                }
            }

            $gd->trang_thai = 'that_bai';
            $gd->save();
            $ly = !$khopTien ? 'Số tiền không khớp.' : ('VNPay trả mã lỗi ' . $responseCode . '.');
            return $this->ketQuaVnpay($gd->ma_order, false, 'Thanh toán không thành công. ' . $ly);
        });
    }

    /**
     * IPN (server-to-server) — VNPay gọi để xác nhận kết quả. Trả JSON theo chuẩn.
     * Đây là nguồn đối soát tin cậy nhất (không phụ thuộc trình duyệt khách).
     */
    public function vnpayIpn(Request $request)
    {
        $params = $request->query();

        if (!$this->vnpay->validateChecksum($params)) {
            return response()->json(['RspCode' => '97', 'Message' => 'Invalid checksum']);
        }

        $gd = ThanhToanOnline::where('ma_giao_dich', $params['vnp_TxnRef'] ?? '')->first();
        if (!$gd) {
            return response()->json(['RspCode' => '01', 'Message' => 'Order not found']);
        }

        $soTienVnpay = (int) round(((int) ($params['vnp_Amount'] ?? 0)) / 100);
        if ($soTienVnpay !== (int) $gd->so_tien) {
            return response()->json(['RspCode' => '04', 'Message' => 'Invalid amount']);
        }

        if ($gd->trang_thai === 'thanh_cong') {
            return response()->json(['RspCode' => '02', 'Message' => 'Order already confirmed']);
        }

        $responseCode = $params['vnp_ResponseCode'] ?? '';
        try {
            DB::transaction(function () use ($gd, $params, $responseCode) {
                $gd->du_lieu = $params;
                $gd->ma_gd_cong = $params['vnp_TransactionNo'] ?? null;
                $gd->ma_phan_hoi = $responseCode;
                $gd->thoi_gian_cap_nhat = now();

                if ($responseCode === '00') {
                    $maHoaDon = $this->paymentService->createInvoice(
                        maOrder:     $gd->ma_order,
                        chietKhau:   (float) $gd->chiet_khau,
                        phuongThuc:  'vnpay',
                        maNvThuNgan: (string) $gd->ma_nv,
                    );
                    $gd->ma_hoa_don = $maHoaDon;
                    $gd->trang_thai = 'thanh_cong';
                } else {
                    $gd->trang_thai = 'that_bai';
                }
                $gd->save();
            });
        } catch (\Throwable $e) {
            Log::error('VNPay IPN error', ['err' => $e->getMessage()]);
            return response()->json(['RspCode' => '99', 'Message' => 'Unknown error']);
        }

        return response()->json(['RspCode' => '00', 'Message' => 'Confirm Success']);
    }

    // ─────────────────────────────────────────────────────────────

    /** Tổng tiền đơn (gộp phụ thu options), trước chiết khấu. */
    private function tinhTongTien(Order $order): float
    {
        return (float) $order->chiTietOrders->sum(
            fn($i) => ($i->don_gia_tai_thoi_diem + $i->options->sum('gia_them')) * $i->so_luong
        );
    }

    /** Chuyển về trang thanh toán kèm thông báo (hoặc trang đăng nhập nếu mất session). */
    private function ketQuaVnpay(?string $maOrder, bool $ok, string $msg)
    {
        if (!$maOrder) {
            return redirect()->route('login')->with($ok ? 'success' : 'error', $msg);
        }
        // Nếu còn session staff → về trang thanh toán; nếu không → trang đăng nhập.
        $route = session('tai_khoan_id') ? redirect()->route('payment.show', $maOrder) : redirect()->route('login');
        return $route->with($ok ? 'success' : 'error', $msg);
    }
}
