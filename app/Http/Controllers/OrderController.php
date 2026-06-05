<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Services\OrderService;
use App\Models\Mon;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(private OrderService $orderService) {}

    // ── STAFF ─────────────────────────────────────────────────

    public function index(Request $request)
    {
        $allowedStatuses = ['cho_xac_nhan','da_xac_nhan','dang_pha_che','da_phuc_vu','hoan_thanh'];
        $status     = in_array($request->get('status'), $allowedStatuses, true) ? $request->get('status') : 'cho_xac_nhan';
        $maChiNhanh = (string) session('ma_chi_nhanh', '');

        $orders = Order::with(['ban', 'chiTietOrders.mon', 'chiTietOrders.options', 'khachHang', 'hoaDon'])
            ->where('ma_chi_nhanh', $maChiNhanh)
            ->where('trang_thai', $status)
            ->orderByDesc('ngay_order')
            ->orderByDesc('gio_order')
            ->paginate(12);

        $counts = $this->orderService->countByStatus($maChiNhanh);

        // Sơ đồ bàn nhúng đầu trang: bàn + số đơn đang mở từng bàn.
        $bans = \App\Models\Ban::where('ma_chi_nhanh', $maChiNhanh)->orderBy('so_ban')->get();
        $orderCounts = \App\Models\Order::where('ma_chi_nhanh', $maChiNhanh)
            ->whereIn('trang_thai', ['cho_xac_nhan','da_xac_nhan','dang_pha_che','da_phuc_vu'])
            ->selectRaw('ma_ban, COUNT(*) as cnt')
            ->groupBy('ma_ban')
            ->pluck('cnt', 'ma_ban');

        return view('staff.order-list', compact('orders', 'counts', 'status', 'bans', 'orderCounts'));
    }

    /** Trang một bàn: đơn đang mở tại bàn + form đặt món tại bàn (dine-in). */
    public function tablePanel(string $maBan)
    {
        $maChiNhanh = (string) session('ma_chi_nhanh', '');
        $ban = \App\Models\Ban::where('ma_ban', $maBan)
            ->where('ma_chi_nhanh', $maChiNhanh)
            ->firstOrFail();

        // Dọn giỏ rác (dang_chon rỗng) của bàn này trước khi hiển thị.
        $this->orderService->purgeEmptyCarts($maBan);

        // 1 order/bàn: nếu đã có đơn đang mở thì NẠP SẴN giỏ để sửa trực tiếp.
        $openOrder = $this->orderService->openOrderForTable($maBan);

        $cartSeed = [];
        $cust = ['ten' => '', 'sdt' => ''];
        $hinhThuc = 'tai_ban';
        if ($openOrder) {
            $hinhThuc = $openOrder->hinh_thuc ?? 'tai_ban';
            $cust = ['ten' => (string) ($openOrder->ten_khach ?? ''), 'sdt' => (string) ($openOrder->sdt_khach ?? '')];
            foreach ($openOrder->chiTietOrders as $ct) {
                $temp = ''; $tops = [];
                foreach ($ct->options as $op) {
                    if ($op->loai_option === 'temperature') $temp = $op->ten_lua_chon;
                    elseif ($op->loai_option === 'topping')  $tops[] = ['value' => $op->ten_lua_chon, 'price' => (int) $op->gia_them];
                }
                $cartSeed[] = [
                    'ma_mon'   => $ct->ma_mon,
                    'ten'      => $ct->mon->ten_mon ?? $ct->ma_mon,
                    'gia'      => (int) $ct->don_gia_tai_thoi_diem,
                    'qty'      => (int) $ct->so_luong,
                    'temp'     => $temp,
                    'toppings' => $tops,
                    'ghi_chu'  => (string) ($ct->ghi_chu ?? ''),
                ];
            }
        }

        $mons = Mon::with('danhMuc')
            ->where('trang_thai', 'active')
            ->orderBy('ma_danh_muc')->orderBy('ten_mon')
            ->get();
        $toppings = \App\Models\Topping::where('trang_thai', 'active')->orderBy('ten_topping')->get();

        return view('staff.order-table', compact('ban', 'openOrder', 'cartSeed', 'cust', 'hinhThuc', 'mons', 'toppings'));
    }

    /** Nhân viên tạo đơn đặt tại bàn. */
    public function storeTable(Request $request, string $maBan)
    {
        $maChiNhanh = (string) session('ma_chi_nhanh', '');
        $ban = \App\Models\Ban::where('ma_ban', $maBan)
            ->where('ma_chi_nhanh', $maChiNhanh)
            ->firstOrFail();

        $validated = $request->validate([
            'ten_kh'     => 'nullable|string|max:100',
            'sdt_kh'     => ['nullable', 'string', 'regex:/^0[0-9]{9}$/'],
            'hinh_thuc'  => 'required|in:tai_ban,mang_ve',
            'action'     => 'nullable|in:confirm,pay',
            'items' => 'required|array',
            'items.*.ma_mon' => 'nullable|exists:MON,ma_mon',
            'items.*.so_luong' => 'nullable|integer|min:0|max:99',
            'items.*.ghi_chu' => 'nullable|string|max:200',
            'items.*.options' => 'nullable|array|max:20',
            'items.*.options.*.type'  => 'nullable|string|max:30',
            'items.*.options.*.value' => 'nullable|string|max:100',
            'items.*.options.*.price' => 'nullable|integer|min:0',
        ], [
            'sdt_kh.regex' => 'Số điện thoại phải gồm đúng 10 chữ số, bắt đầu bằng 0.',
        ]);

        $items = collect($validated['items'])
            ->filter(fn($item) => !empty($item['ma_mon']) && (int) ($item['so_luong'] ?? 0) > 0)
            ->values()->all();

        if (empty($items)) {
            return back()->withInput()->withErrors(['items' => 'Vui lòng chọn ít nhất một món.']);
        }

        // 1 order/bàn: nếu đã có đơn mở thì SỬA đơn đó; nếu chưa có thì tạo mới.
        $open = $this->orderService->openOrderForTable($maBan);
        if ($open) {
            $this->orderService->syncOrderItems(
                $open->ma_order, $items,
                $validated['hinh_thuc'], $validated['ten_kh'] ?? null, $validated['sdt_kh'] ?? null
            );
            $maOrder = $open->ma_order;
            $isNew = false;
        } else {
            $maOrder = $this->orderService->createDineInOrder(
                maBan:      $maBan,
                maChiNhanh: $maChiNhanh,
                items:      $items,
                hinhThuc:   $validated['hinh_thuc'],
                tenKh:      $validated['ten_kh'] ?? null,
                sdtKh:      $validated['sdt_kh'] ?? null,
            );
            $isNew = true;
        }

        // Nút "Thanh toán" → sang trang thanh toán luôn.
        if (($validated['action'] ?? 'confirm') === 'pay') {
            return redirect()->route('payment.show', $maOrder)
                ->with('success', 'Đã lưu đơn bàn ' . $ban->so_ban . '. Tiến hành thanh toán.');
        }

        // Nút "Xác nhận" → nhảy thẳng ĐANG PHA CHẾ (bỏ bước "đã xác nhận") rồi về order board.
        if (Order::where('ma_order', $maOrder)->value('trang_thai') === 'cho_xac_nhan') {
            $this->orderService->updateStatus($maOrder, 'dang_pha_che');
        }
        return redirect()->route('orders.index')
            ->with('success', ($isNew ? 'Đã tạo & chuyển pha chế' : 'Đã cập nhật') . ' đơn cho bàn ' . $ban->so_ban . '.');
    }

    /** JSON polling cho order board — gọi bằng fetch mỗi 10s */
    public function apiList()
    {
        $maChiNhanh = (string) session('ma_chi_nhanh', '');

        $orders = Order::with(['ban', 'chiTietOrders.mon'])
            ->where('ma_chi_nhanh', $maChiNhanh)
            ->whereIn('trang_thai', ['cho_xac_nhan','da_xac_nhan','dang_pha_che','da_phuc_vu'])
            ->where('ngay_order', now()->toDateString())
            ->orderByDesc('gio_order')
            ->get()
            ->map(fn($o) => [
                'ma_order'   => $o->ma_order,
                'so_ban'     => $o->ban?->so_ban,
                'trang_thai' => $o->trang_thai,
                'gio_order'  => $o->gio_order ? Carbon::parse($o->gio_order)->format('H:i') : '',
                'total'      => $o->chiTietOrders->sum(fn($i) => $i->don_gia_tai_thoi_diem * $i->so_luong),
                'items'      => $o->chiTietOrders->map(fn($i) => [
                    'name'  => $i->mon?->ten_mon ?? '—',
                    'qty'   => $i->so_luong,
                    'price' => $i->don_gia_tai_thoi_diem,
                ]),
            ]);

        return response()->json(['orders' => $orders]);
    }

    public function confirm(string $maOrder)
    {
        // Scope theo chi nhánh của staff đang đăng nhập
        Order::where('ma_order', $maOrder)
            ->where('ma_chi_nhanh', (string) session('ma_chi_nhanh', ''))
            ->firstOrFail();

        $this->orderService->confirm($maOrder);
        if (request()->expectsJson()) return response()->json(['ok' => true]);
        return back()->with('success', 'Đã xác nhận đơn hàng.');
    }

    public function updateStatus(Request $request, string $maOrder)
    {
        $request->validate([
            'trang_thai' => 'required|in:da_xac_nhan,dang_pha_che,da_phuc_vu,hoan_thanh,da_huy',
        ]);

        // Scope theo chi nhánh
        Order::where('ma_order', $maOrder)
            ->where('ma_chi_nhanh', (string) session('ma_chi_nhanh', ''))
            ->firstOrFail();

        $this->orderService->updateStatus($maOrder, $request->trang_thai);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'trang_thai' => $request->trang_thai]);
        }
        return back()->with('success', 'Cập nhật trạng thái thành công.');
    }

    public function show(string $maOrder)
    {
        // Scope theo chi nhánh — staff không xem đơn chi nhánh khác
        $order = Order::with(['ban', 'chiTietOrders.mon', 'khachHang'])
            ->where('ma_order', $maOrder)
            ->where('ma_chi_nhanh', (string) session('ma_chi_nhanh', ''))
            ->firstOrFail();

        $mergeTargets = Order::with('ban')
            ->where('ma_chi_nhanh', $order->ma_chi_nhanh)
            ->where('ma_order', '<>', $order->ma_order)
            ->whereIn('trang_thai', ['cho_xac_nhan', 'da_xac_nhan', 'dang_pha_che', 'da_phuc_vu'])
            ->orderByDesc('ngay_order')
            ->orderByDesc('gio_order')
            ->limit(30)
            ->get();

        return view('staff.order-detail', compact('order', 'mergeTargets'));
    }

    public function createTakeaway()
    {
        $mons = Mon::with('danhMuc')
            ->where('trang_thai', 'active')
            ->orderBy('ma_danh_muc')
            ->orderBy('ten_mon')
            ->get();

        $toppings = \App\Models\Topping::where('trang_thai', 'active')
            ->orderBy('ten_topping')
            ->get();

        return view('staff.order-takeaway', compact('mons', 'toppings'));
    }

    public function storeTakeaway(Request $request)
    {
        $validated = $request->validate([
            'ten_kh' => 'nullable|string|max:100',
            'sdt_kh' => 'nullable|string|max:15',
            'items' => 'required|array',
            'items.*.ma_mon' => 'nullable|exists:MON,ma_mon',
            'items.*.so_luong' => 'nullable|integer|min:0|max:99',
            'items.*.ghi_chu' => 'nullable|string|max:200',
            'items.*.options' => 'nullable|array|max:20',
            'items.*.options.*.type'  => 'nullable|string|max:30',
            'items.*.options.*.value' => 'nullable|string|max:100',
            'items.*.options.*.price' => 'nullable|integer|min:0',
        ]);

        $items = collect($validated['items'])
            ->filter(fn($item) => !empty($item['ma_mon']) && (int) ($item['so_luong'] ?? 0) > 0)
            ->values()
            ->all();

        if (empty($items)) {
            return back()->withInput()->withErrors(['items' => 'Vui lòng chọn ít nhất một món.']);
        }

        $maOrder = $this->orderService->createTakeawayOrder(
            tenKh: $validated['ten_kh'] ?? 'Khách mang về',
            sdtKh: $validated['sdt_kh'] ?? null,
            maChiNhanh: session('ma_chi_nhanh'),
            items: $items,
        );

        return redirect()->route('payment.show', $maOrder)
            ->with('success', 'Đã tạo đơn mang về. Có thể thanh toán ngay.');
    }

    public function merge(Request $request, string $maOrder)
    {
        $request->validate(['target_order' => 'required|string']);

        // Scope: cả hai đơn phải thuộc chi nhánh đang đăng nhập
        Order::where('ma_order', $maOrder)
            ->where('ma_chi_nhanh', (string) session('ma_chi_nhanh', ''))
            ->firstOrFail();

        try {
            $this->orderService->merge($maOrder, $request->target_order);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['merge' => $e->getMessage()]);
        }

        return back()->with('success', 'Đã gộp đơn hàng.');
    }

    public function split(Request $request, string $maOrder)
    {
        $validated = $request->validate([
            'tach'   => 'required|array',
            'tach.*' => 'nullable|integer|min:0|max:999',
        ]);

        // Chỉ giữ các món có số lượng tách > 0
        $picks = collect($validated['tach'])
            ->filter(fn($q) => (int) $q > 0)
            ->map(fn($q) => (int) $q)
            ->all();

        if (empty($picks)) {
            return back()->withErrors(['split' => 'Vui lòng nhập số lượng cần tách cho ít nhất một món.']);
        }

        try {
            $maOrderMoi = $this->orderService->splitItems($maOrder, $picks);
            return back()->with('success', "Đã tách các món sang đơn mới: {$maOrderMoi}");
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['split' => $e->getMessage()]);
        }
    }

    // ── CUSTOMER ──────────────────────────────────────────────

    public function createFromQr(StoreOrderRequest $request, string $maBan)
    {
        $result = $this->orderService->createOrder(
            maBan:      $maBan,
            tenKh:      $request->ten_kh,
            sdtKh:      $request->sdt_kh,
            maChiNhanh: \App\Models\Ban::findOrFail($maBan)->ma_chi_nhanh,
        );

        $maOrder = (string) $result['ma_order'];

        // Lưu ma_order vào session để xác thực quyền sở hữu ở các bước tiếp theo
        $owned = (array) session('customer_orders', []);
        $owned[] = $maOrder;
        session()->put('customer_orders', $owned);

        // Ghi nhớ thông tin khách trong phiên trình duyệt để "Gọi món khác" không phải nhập lại
        session()->put('customer_profile', [
            'ten_kh' => $request->ten_kh,
            'sdt_kh' => $request->sdt_kh,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'ma_order' => $maOrder,
                'ma_ban'   => $maBan,
            ]);
        }

        // Không truyền ma_order qua URL (GET); menu tự suy từ đơn dang_chon của bàn.
        return redirect()->route('customer.menu', ['ma_ban' => $maBan]);
    }

    public function addItem(Request $request, string $maOrder)
    {
        $this->assertCustomerOwnsOrder($maOrder);

        $request->validate([
            'ma_mon'   => 'required|string',
            'so_luong' => 'nullable|integer|min:1',
            'ghi_chu'  => 'nullable|string|max:200',
            'options'  => 'nullable|array|max:20',
            'options.*.type' => 'required_with:options|string|max:30',
            'options.*.label' => 'nullable|string|max:50',
            'options.*.value' => 'required_with:options|string|max:100',
            'options.*.price' => 'nullable|numeric|min:0',
        ]);
        $this->orderService->addItem(
            $maOrder,
            (string) $request->ma_mon,
            (int) ($request->so_luong ?? 1),
            $request->ghi_chu !== null ? (string) $request->ghi_chu : null,
            (array) $request->input('options', [])
        );

        if ($request->expectsJson()) return response()->json(['ok' => true]);
        return back();
    }

    public function removeItem(string $maOrder, string $maMon)
    {
        $this->assertCustomerOwnsOrder($maOrder);

        $this->orderService->removeItem($maOrder, $maMon);
        if (request()->expectsJson()) return response()->json(['ok' => true]);
        return back();
    }

    public function showCart(string $maOrder, \App\Services\AnalyticsService $analytics)
    {
        $this->assertCustomerOwnsOrder($maOrder);

        $order = Order::with(['ban', 'chiTietOrders.mon', 'chiTietOrders.options'])
            ->findOrFail($maOrder);

        // Redirect về trang trạng thái nếu đơn không còn ở trạng thái chọn món
        if ($order->trang_thai !== 'dang_chon') {
            return redirect()->route('customer.status', $maOrder);
        }

        // Gợi ý món mua kèm (AI market-basket) theo giỏ hiện tại.
        $cartMons = $order->chiTietOrders->pluck('ma_mon')->all();
        $suggestions = $analytics->recommendForItems($cartMons, $order->ma_chi_nhanh, 4);

        return view('customer.checkout', compact('order', 'suggestions'));
    }

    public function confirmByCustomer(Request $request, string $maOrder)
    {
        $this->assertCustomerOwnsOrder($maOrder);

        $request->validate(['hinh_thuc' => 'nullable|in:tai_ban,mang_ve']);

        $order = Order::where('ma_order', $maOrder)->where('trang_thai', 'dang_chon')->first();
        if (!$order) return redirect()->route('customer.status', $maOrder);
        $this->orderService->submitByCustomer($maOrder, $request->input('hinh_thuc'));
        return redirect()->route('customer.status', $maOrder)
            ->with('info', 'Đơn hàng đã được gửi. Vui lòng chờ nhân viên xác nhận.');
    }

    public function status(string $maOrder)
    {
        $this->assertCustomerOwnsOrder($maOrder);

        $order = Order::findOrFail($maOrder);
        return view('customer.status', compact('order'));
    }

    /** JSON polling cho trang trạng thái khách */
    public function statusJson(string $maOrder)
    {
        $this->assertCustomerOwnsOrder($maOrder);

        $order = Order::findOrFail($maOrder);
        return response()->json([
            'ma_order'   => $order->ma_order,
            'trang_thai' => $order->trang_thai,
        ]);
    }

    /**
     * Kiểm tra khách hàng có quyền thao tác với đơn hàng này không.
     * ma_order phải được lưu trong session khi tạo đơn qua QR.
     */
    private function assertCustomerOwnsOrder(string $maOrder): void
    {
        if (!in_array($maOrder, session('customer_orders', []), true)) {
            abort(403, 'Bạn không có quyền truy cập đơn hàng này.');
        }
    }
}
