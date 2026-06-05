# Tài liệu Hướng dẫn Nghiệp vụ — 8AM Coffee Ordering System

**Phiên bản:** Laravel 11 · MySQL 8 · Alpine.js · Three.js  
**Ngày tạo:** 2026-06-04  
**Mục tiêu:** Giải thích chi tiết 3 luồng nghiệp vụ chính của hệ thống, bao gồm code backend và frontend, theo thứ tự thực tế diễn ra tại quán.

---

## Mục lục

1. [Tổng quan kiến trúc hệ thống](#1-tổng-quan-kiến-trúc-hệ-thống)
2. [Luồng 1 — Quét mã QR](#2-luồng-1--quét-mã-qr)
3. [Luồng 2 — Đặt món và nhận món](#3-luồng-2--đặt-món-và-nhận-món)
4. [Luồng 3 — Theo dõi trạng thái đơn hàng](#4-luồng-3--theo-dõi-trạng-thái-đơn-hàng)
5. [Sơ đồ vòng đời trạng thái đơn](#5-sơ-đồ-vòng-đời-trạng-thái-đơn)
6. [Bảng tóm tắt các bảng CSDL liên quan](#6-bảng-tóm-tắt-các-bảng-csdl-liên-quan)

---

## 1. Tổng quan kiến trúc hệ thống

Hệ thống chia làm **hai nhóm người dùng** với giao diện và luồng dữ liệu riêng biệt:

| Nhóm | URL prefix | Mô tả |
|------|-----------|-------|
| **Khách hàng** | `/order/{ma_ban}/...` | Giao diện quét QR → chọn món → theo dõi |
| **Nhân viên / quản lý** | `/dashboard`, `/orders/...` | Dashboard nội bộ, xác nhận, pha chế, thanh toán |

```
Khách hàng (điện thoại)          Máy chủ Laravel          Nhân viên (máy tính quầy)
┌──────────────────┐             ┌────────────────┐         ┌─────────────────────┐
│ 1. Quét QR       │────GET────▶ │ QrController   │         │                     │
│ 2. Nhập tên      │────POST───▶ │ OrderController│         │                     │
│ 3. Chọn món      │────POST───▶ │ OrderService   │────────▶│ Order Board (poll)  │
│ 4. Xác nhận      │────POST───▶ │ (DB transaction)         │ Xác nhận / Pha chế  │
│ 5. Theo dõi      │◀──poll 5s── │ statusJson     │◀────────│ Cập nhật trạng thái │
└──────────────────┘             └────────────────┘         └─────────────────────┘
```

---

## 2. Luồng 1 — Quét mã QR

### Mô tả tổng quát

Mỗi bàn tại quán được dán một tờ poster QR. Khi khách dùng điện thoại quét mã này, hệ thống:
1. Ghi nhận lượt quét vào bảng `SCAN_LOG` (không phụ thuộc việc khách có đặt món hay không).
2. Hiển thị trang chào mừng để khách nhập tên và số điện thoại trước khi bắt đầu gọi món.

---

### 2.1 Tạo và in QR Code

**File:** `app/Http/Controllers/QrController.php`

#### Phương thức `generate()` — Tạo hình ảnh QR SVG

```php
// Route: GET /ban/{ma_ban}/qr
public function generate(string $maBan)
{
    $ban = Ban::findOrFail($maBan);             // Xác nhận bàn tồn tại
    $url = route('customer.scan', $maBan);      // Tạo URL đích: /order/{ma_ban}

    $qr = QrCode::format('svg')                // Dùng thư viện simplesoftwareio/simple-qrcode
                ->size(300)                    // Kích thước 300x300 px
                ->margin(1)                    // Viền trắng 1 module
                ->generate($url);              // Mã hóa URL vào QR

    return response($qr)->header('Content-Type', 'image/svg+xml');
}
```

**Giải thích:**
- URL được nhúng vào mã QR là `/order/B001` (ví dụ cho bàn B001).
- Định dạng SVG được chọn vì không cần thư viện `Imagick` — giảm phụ thuộc server.
- Khi khách quét bằng camera điện thoại, trình duyệt tự mở URL `/order/B001`.

#### Phương thức `poster()` — Trang poster in ấn

```php
// Route: GET /ban/{ma_ban}/qr/poster
public function poster(string $maBan)
{
    $ban = Ban::with('chiNhanh')->findOrFail($maBan);

    // Kiểm tra phạm vi chi nhánh: nhân viên chi nhánh A không in poster chi nhánh B
    abort_if(
        session('chuc_vu') !== 'superadmin' && $ban->ma_chi_nhanh !== session('ma_chi_nhanh'),
        403
    );

    $url = route('customer.scan', $maBan);
    return view('staff.qr-poster', compact('ban', 'url'));
}
```

**Giải thích:**
- Nhân viên vào trang quản lý bàn → bấm "In QR" → mở trang poster có thương hiệu.
- Poster nhúng ảnh QR (gọi `/ban/{ma_ban}/qr`) + tên bàn + logo 8AM.
- Bảo vệ: nhân viên một chi nhánh không thể in poster chi nhánh khác (dòng `abort_if`).

---

### 2.2 Khách quét QR — Ghi log và hiển thị trang chào

**File:** `app/Http/Controllers/QrController.php`

```php
// Route: GET /order/{ma_ban}
public function scan(Request $request, string $maBan)
{
    $ban = Ban::findOrFail($maBan);  // 404 nếu mã bàn không tồn tại

    // ─── BƯỚC 1: Ghi log quét ───────────────────────────────────
    DB::table('SCAN_LOG')->insert([
        'ma_ban'       => $ban->ma_ban,
        'ma_chi_nhanh' => $ban->ma_chi_nhanh,
        'ip'           => $request->ip(),                              // IP của khách
        'user_agent'   => mb_substr((string) $request->userAgent(), 0, 300), // Trình duyệt
        'thoi_gian'    => now(),
    ]);
    // Lưu ý: log được ghi NGAY LẬP TỨC, trước khi khách nhập tên hay đặt món.
    // Điều này giúp đếm chính xác số lượt khách đến quán (kể cả người chỉ xem menu).

    // ─── BƯỚC 2: Điền sẵn thông tin từ phiên ───────────────────
    // Nếu khách đã gọi món trước đó trong cùng phiên trình duyệt,
    // tên và SĐT sẽ được điền sẵn vào form (không phải nhập lại).
    $profile = (array) session('customer_profile', []);

    return view('customer.scan', compact('ban', 'profile'));
}
```

**Giải thích từng bước:**
1. `Ban::findOrFail($maBan)` — Tra cứu bàn trong CSDL. Nếu mã bàn không hợp lệ (ví dụ QR bị hỏng/giả), trả về lỗi 404 thay vì hiển thị trang trống.
2. `DB::table('SCAN_LOG')->insert(...)` — Ghi trực tiếp (không qua Eloquent model) để nhanh hơn và không cần `timestamps` tự động.
3. `session('customer_profile', [])` — Lấy thông tin khách từ phiên. Điều này quan trọng với tính năng "Gọi thêm món" — khách không phải nhập lại tên.

---

### 2.3 Bảng CSDL SCAN_LOG

**File:** `database/migrations/2026_06_05_000002_create_scan_logs_table.php`

```php
Schema::create('SCAN_LOG', function (Blueprint $table) {
    $table->id();                               // ID tự tăng
    $table->string('ma_ban', 10);               // Mã bàn được quét
    $table->string('ma_chi_nhanh', 10)->nullable(); // Chi nhánh
    $table->string('ip', 45)->nullable();       // Hỗ trợ IPv4 và IPv6
    $table->string('user_agent', 300)->nullable(); // Loại thiết bị/trình duyệt
    $table->dateTime('thoi_gian');              // Thời điểm quét

    // Index kép để query theo chi nhánh + khoảng thời gian chạy nhanh
    $table->index(['ma_chi_nhanh', 'thoi_gian']);
    $table->index('ma_ban');
});
```

**Lý do thiết kế:**
- Không có khóa ngoại đến `ORDERS` — log quét tồn tại độc lập, kể cả khi khách không đặt món.
- `ip` hỗ trợ 45 ký tự để chứa cả địa chỉ IPv6.
- Index kép `(ma_chi_nhanh, thoi_gian)` phục vụ query "xem log theo chi nhánh theo ngày" trong trang admin.

---

### 2.4 Giao diện trang chào — Frontend

**File:** `resources/views/customer/scan.blade.php`

```html
<!-- Form nhập thông tin khách hàng trước khi gọi món -->
<form method="POST" action="{{ route('customer.create', $ban->ma_ban) }}">
    @csrf
    <!-- Input tên: điền sẵn từ session nếu có -->
    <input type="text" name="ten_kh" placeholder="Nhập tên của bạn" required
           value="{{ old('ten_kh', $profile['ten_kh'] ?? '') }}"
           class="...">

    <!-- Input SĐT: chỉ cho nhập số, giới hạn 10 ký tự -->
    <input type="text" name="sdt_kh"
           value="{{ old('sdt_kh', $profile['sdt_kh'] ?? '') }}"
           inputmode="numeric" maxlength="10" pattern="0[0-9]{9}"
           oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,10)"
           ...>

    <button type="submit">Bắt đầu gọi món →</button>
</form>
```

**Giải thích từng phần:**
- `{{ route('customer.create', $ban->ma_ban) }}` — Form POST đến `/order/{ma_ban}/create`, truyền mã bàn qua URL.
- `value="{{ old('ten_kh', $profile['ten_kh'] ?? '') }}"` — Ưu tiên `old()` (khi form validation thất bại), sau đó lấy từ session, cuối cùng để trống.
- `oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,10)"` — Lọc ký tự không phải số ngay trên client-side, trước khi server xử lý.
- `pattern="0[0-9]{9}"` — Validate HTML5: số điện thoại phải bắt đầu bằng 0 và có đúng 10 chữ số.

---

### 2.5 Validation trước khi tạo đơn

**File:** `app/Http/Requests/StoreOrderRequest.php`

```php
public function authorize(): bool
{
    $maBan = $this->route('ma_ban');
    // Xác thực: bàn phải tồn tại trong CSDL
    // KHÔNG chặn khi bàn đang "có khách" — một bàn có thể có nhiều đơn liên tiếp.
    return Ban::where('ma_ban', $maBan)->exists();
}

public function rules(): array
{
    return [
        'ten_kh' => 'required|string|max:100',       // Tên bắt buộc, tối đa 100 ký tự
        'sdt_kh' => ['nullable', 'string', 'regex:/^0[0-9]{9}$/'], // SĐT 10 số, bắt đầu 0
    ];
}

public function messages(): array
{
    return [
        'ten_kh.required' => 'Vui lòng nhập tên khách hàng.',
        'sdt_kh.regex'    => 'Số điện thoại phải gồm đúng 10 chữ số, bắt đầu bằng 0.',
    ];
}
```

**Giải thích:**
- `authorize()` kiểm tra bàn tồn tại — nếu QR code dẫn đến bàn không hợp lệ, trả về lỗi 403 với thông báo "Bàn này không tồn tại."
- `sdt_kh` là `nullable` — khách không bắt buộc nhập SĐT. Khi có thì phải đúng định dạng.
- Regex `^0[0-9]{9}$` khớp với các số điện thoại Việt Nam: 0xxx xxx xxx.

---

### 2.6 Xem log quét QR (Admin)

**File:** `app/Http/Controllers/QrController.php`

```php
// Route: GET /scan-log (chỉ admin/superadmin)
public function scanLog()
{
    $maChiNhanh = (string) session('ma_chi_nhanh', '');
    $isSuper    = session('chuc_vu') === 'superadmin';

    $logs = DB::table('SCAN_LOG as s')
        ->leftJoin('BAN as b', 'b.ma_ban', '=', 's.ma_ban') 
        ->leftJoin('CHI_NHANH as c', 'c.ma_chi_nhanh', '=', 's.ma_chi_nhanh')
        // Superadmin xem tất cả; admin chi nhánh chỉ xem chi nhánh mình
        ->when(! $isSuper, fn($q) => $q->where('s.ma_chi_nhanh', $maChiNhanh))
        ->select('s.*', 'b.so_ban', 'c.ten_chi_nhanh')
        ->orderByDesc('s.thoi_gian')
        ->paginate(50);
    return view('staff.scan-log', compact('logs', 'isSuper'));
}
```

**Giải thích:**
- `->when(!$isSuper, ...)` — Query thích nghi theo vai trò: nếu không phải superadmin thì tự động lọc theo chi nhánh. Đây là pattern "scoped query" — gọn hơn hai câu `if/else` riêng biệt.
- `leftJoin` với BAN và CHI_NHANH để hiển thị "Bàn 5" thay vì "B005", "Cơ sở Q1" thay vì "CN001".
- `paginate(50)` — Tránh load hàng nghìn dòng log cùng lúc.

---

### Sơ đồ luồng Quét QR

```
Khách quét QR bằng điện thoại
           │
           ▼
GET /order/{ma_ban}  →  QrController::scan()
           │
           ├─ Tìm bàn trong CSDL (Ban::findOrFail)
           │       └─ Không tìm thấy → 404 "Bàn không tồn tại"
           │
           ├─ Ghi log vào SCAN_LOG (IP, user-agent, thời gian)
           │
           └─ Hiển thị trang customer/scan.blade.php
                    │
                    └─ Form nhập tên + SĐT
                              │
                              ▼
                    POST /order/{ma_ban}/create
                    → Sang Luồng 2: Đặt món
```

---

## 3. Luồng 2 — Đặt món và nhận món

### Mô tả tổng quát

Sau khi khách nhập tên, hệ thống:
1. **Tạo đơn hàng trống** với trạng thái `dang_chon`.
2. **Hiển thị menu** có sơ đồ 3D quán.
3. **Khách thêm/xóa món** trực tiếp trên giao diện.
4. **Khách xác nhận** → trạng thái chuyển sang `cho_xac_nhan`.
5. **Nhân viên xem đơn** trên order board và xác nhận → `dang_pha_che`.
6. **Nhân viên phục vụ** → `da_phuc_vu`.

---

### 3.1 Tạo đơn hàng khi khách bắt đầu

**File:** `app/Http/Controllers/OrderController.php`

```php
// Route: POST /order/{ma_ban}/create
public function createFromQr(StoreOrderRequest $request, string $maBan)
{
    $result = $this->orderService->createOrder(
        maBan:      $maBan,
        tenKh:      $request->ten_kh,               // Tên khách từ form
        sdtKh:      $request->sdt_kh,               // SĐT (nullable)
        maChiNhanh: \App\Models\Ban::findOrFail($maBan)->ma_chi_nhanh,
    );

    $maOrder = (string) $result['ma_order'];

    // ─── Lưu quyền sở hữu đơn vào session ──────────────────────
    // Quan trọng: chỉ người tạo đơn (trong cùng phiên trình duyệt) mới được
    // xem giỏ hàng và xác nhận đơn — tránh khách khác truy cập đơn người khác.
    $owned = (array) session('customer_orders', []);
    $owned[] = $maOrder;
    session()->put('customer_orders', $owned);

    // ─── Ghi nhớ hồ sơ khách trong phiên ───────────────────────
    // Dùng cho "Gọi món khác" — khách không phải nhập lại tên/SĐT.
    session()->put('customer_profile', [
        'ten_kh' => $request->ten_kh,
        'sdt_kh' => $request->sdt_kh,
    ]);

    // Không truyền ma_order qua URL (tránh chia sẻ link cho người khác lấy đơn)
    return redirect()->route('customer.menu', ['ma_ban' => $maBan]);
}
```

**Giải thích chi tiết:**
- Đơn được tạo với trạng thái **`dang_chon`** — đây là trạng thái "giỏ hàng", chưa gửi đến quầy.
- `session('customer_orders', [])` — Mảng các `ma_order` mà phiên trình duyệt này "sở hữu". Khi khách gọi thêm món lần 2, mảng này có 2 phần tử.
- `ma_order` **không** truyền qua URL khi redirect — trang menu tự tìm đơn `dang_chon` của bàn trong CSDL.

---

### 3.2 Logic tạo đơn trong OrderService

**File:** `app/Services/OrderService.php`

```php
public function createOrder(string $maBan, string $tenKh, ?string $sdtKh, string $maChiNhanh, string $hinhThuc = 'tai_ban'): array
{
    return DB::transaction(function () use ($maBan, $tenKh, $sdtKh, $maChiNhanh, $hinhThuc) {
        // Tên khách được lưu TẠM trên đơn — không tạo bản ghi KHACH_HANG ngay.
        // Khách chỉ được thêm vào danh sách khách hàng khi CÓ THANH TOÁN.
        $maOrder = $this->generateUniqueOrderId();
        //         ↑ Ví dụ: "ORD260604143052" + 2 số ngẫu nhiên = "ORD26060414305247"

        Order::create([
            'ma_order'     => $maOrder,
            'ma_ban'       => $maBan,
            'ma_kh'        => null,              // Chưa tạo KH — chỉ lưu tên tạm
            'ten_khach'    => $tenKh,            // Được mã hóa PII (EncryptedString cast)
            'sdt_khach'    => $sdtKh,            // Được mã hóa PII
            'ma_chi_nhanh' => $maChiNhanh,
            'trang_thai'   => 'dang_chon',       // Trạng thái ban đầu
            'hinh_thuc'    => 'tai_ban',
            'ngay_order'   => now()->toDateString(),
            'gio_order'    => now()->toTimeString(),
        ]);

        // Ghi log: hành động tạo đơn
        $this->log($maOrder, 'tao_don_nhap', null, 'dang_chon', 'Khach hang bat dau chon mon tu QR.', [
            'ma_ban' => $maBan,
            'ma_chi_nhanh' => $maChiNhanh,
        ]);

        return ['ma_order' => $maOrder, 'ma_kh' => null];
    });
}
```

**Giải thích:**
- `DB::transaction(...)` — Toàn bộ thao tác tạo đơn được bọc trong transaction. Nếu bất kỳ bước nào lỗi (ví dụ: deadlock), toàn bộ rollback.
- `ten_khach` và `sdt_khach` được lưu dưới dạng **mã hóa** (cast `EncryptedString`) — bảo vệ dữ liệu cá nhân khách hàng theo yêu cầu PDPA/GDPR.
- `generateUniqueOrderId()` tạo mã dạng `ORD` + ngày giờ + 2 số ngẫu nhiên, đảm bảo không trùng.

```php
// Hàm tạo mã đơn duy nhất
private function generateUniqueOrderId(): string
{
    do {
        $maOrder = 'ORD' . now()->format('ymdHis') . random_int(10, 99);
        //          ORD   + 26060414 + 3052         + 47  = "ORD2606041430 5247"
    } while (Order::whereKey($maOrder)->exists()); // Đảm bảo không trùng (hiếm khi xảy ra)

    return $maOrder;
}
```

---

### 3.3 Hiển thị menu — MenuController

**File:** `app/Http/Controllers/MenuController.php`

```php
// Route: GET /order/{ma_ban}/menu
public function customerMenu(string $maBan)
{
    $ban = Ban::findOrFail($maBan);
    $maChiNhanh = $ban->ma_chi_nhanh;

    // ─── Tải toàn bộ menu có options và tồn kho ─────────────────
    $danhMucs = DanhMuc::with(['mons' => function ($query) {
        $query->with([
            'danhMuc',
            'dinhMucs.nguyenLieu.tonKhos',   // Định mức nguyên liệu + tồn kho
            'options' => fn($query) => $query
                ->whereIn('loai_option', ['temperature', 'sweetness', 'topping'])
                ->where('trang_thai', 'active')
                ->orderBy('loai_option')
                ->orderBy('thu_tu'),          // Nhiệt độ → Topping (theo thứ tự)
        ])->where('trang_thai', 'active')->orderBy('ten_mon');
    }])->orderBy('ten_danh_muc')->get();

    // ─── Kiểm tra tồn kho từng món ──────────────────────────────
    // MenuAvailabilityService tính xem mỗi món có đủ nguyên liệu không,
    // gắn thuộc tính $mon->het_hang_theo_kho vào model.
    $danhMucs->each(fn($danhMuc) => $this->availabilityService->annotate($danhMuc->mons, $maChiNhanh));

    // ─── Tìm đơn đang chọn (nếu đã có) ─────────────────────────
    $maOrder = $this->resolveMaOrder($maBan);

    // ─── Lấy model 3D của chi nhánh ─────────────────────────────
    $model3d = DB::table('CHI_NHANH')->where('ma_chi_nhanh', $ban->ma_chi_nhanh)->value('model_3d') ?: 'cafe_opt.glb';

    return view('customer.menu', compact('ban', 'danhMucs', 'maOrder', 'model3d'));
}

// Tìm mã đơn hiện tại: ưu tiên query string, nếu không có thì tìm đơn dang_chon của bàn
private function resolveMaOrder(string $maBan): ?string
{
    return request('ma_order') ?: DB::table('ORDERS')
        ->where('ma_ban', $maBan)
        ->where('trang_thai', 'dang_chon')
        ->orderByDesc('ma_order')          // Nếu có nhiều đơn dang_chon, lấy cái mới nhất
        ->value('ma_order');
}
```

**Giải thích:**
- Eager loading (`with(...)`) tải toàn bộ quan hệ trong 1-2 câu query thay vì N+1 queries.
- `MenuAvailabilityService::annotate()` so sánh định mức nguyên liệu của từng món với tồn kho hiện tại. Nếu tồn kho < định mức × số lượng min, gắn `$mon->het_hang_theo_kho = true` — card món sẽ hiển thị "Tạm hết hàng".
- `resolveMaOrder` xử lý hai trường hợp: (1) khách đến từ trang scan với đơn mới, (2) khách quay lại menu từ trang checkout (có `?ma_order=ORDxxx` trên URL).

---

### 3.4 Giao diện menu và thêm món — Frontend

**File:** `resources/views/customer/menu.blade.php`

```html
<!-- Meta tag giúp JavaScript biết mã đơn hiện tại -->
@if($maOrder)
<meta name="ma-order" content="{{ $maOrder }}">
@endif

<!-- Tải file JS của sơ đồ 3D và viewer món -->
@vite(['resources/js/showroom.js', 'resources/js/mon-viewer.js'])

<!-- Sơ đồ 3D: khách xem bố cục quán và đổi bàn -->
<div id="showroom-root"
     data-model-url="{{ \App\Support\Cdn::url('models/'.$model3d) }}"
     data-tables-url="{{ route('customer.tables', $ban->ma_ban) }}"
     data-move-url="{{ route('customer.move', ['ma_ban' => $ban->ma_ban, 'to' => '__TO__']) }}"
     data-redirect-url="{{ route('customer.menu', ['ma_ban' => '__TO__']) }}"
     data-current-table="{{ $ban->ma_ban }}">
    <div id="sr-canvas" class="h-[56vh] w-full lg:col-span-3"></div>
</div>
```

**Giải thích thuộc tính `data-*`:**
- `data-model-url` — URL của file `.glb` (mô hình 3D Three.js). `Cdn::url()` có thể trả về URL CDN nếu cấu hình, hoặc URL local.
- `data-tables-url` — Endpoint JSON trả về danh sách bàn và trạng thái để showroom.js vẽ ghim màu.
- `data-move-url` — Template URL để đổi bàn; `__TO__` được thay bằng mã bàn đích khi khách chọn.
- `data-current-table` — Bàn hiện tại, để sơ đồ 3D highlight bàn đang ngồi.

---

### 3.5 Card món ăn — Component

**File:** `resources/views/components/menu-item-card.blade.php`

```html
<!-- Nút "Thêm món": gọi hàm Alpine.js addToCart() -->
<button @click="addToCart(@js([
    'ma_mon'    => $mon->ma_mon,
    'ten_mon'   => $mon->ten_mon,
    'don_gia'   => $mon->don_gia,
    'options'   => $displayOptions,  // Nhiệt độ, độ ngọt, topping có sẵn
]))"
class="...">
    Thêm món +
</button>

<!-- Nút "Tạm hết hàng": disabled khi het_hang_theo_kho = true -->
@if($hetHang)
<button disabled class="... cursor-not-allowed">
    Tạm hết hàng
</button>
@endif
```

**Giải thích `@js()`:**
- `@js(...)` là directive của Blade, tương đương `json_encode()` nhưng xuất ra JavaScript literal an toàn (escape HTML đặc biệt).
- `$displayOptions` chứa mảng các lựa chọn nhiệt độ, độ ngọt và topping — JavaScript sẽ dùng để hiển thị popup tùy chỉnh trước khi thêm vào giỏ.

---

### 3.6 Thêm món vào giỏ hàng — Backend API

**File:** `app/Http/Controllers/OrderController.php`

```php
// Route: POST /order/{ma_order}/item
public function addItem(Request $request, string $maOrder)
{
    // ─── Kiểm tra quyền sở hữu đơn ─────────────────────────────
    $this->assertCustomerOwnsOrder($maOrder);

    $request->validate([
        'ma_mon'             => 'required|string',
        'so_luong'           => 'nullable|integer|min:1',
        'ghi_chu'            => 'nullable|string|max:200',
        'options'            => 'nullable|array|max:20',
        'options.*.type'     => 'required_with:options|string|max:30',
        'options.*.value'    => 'required_with:options|string|max:100',
        'options.*.price'    => 'nullable|numeric|min:0',
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

// Phương thức bảo vệ: kiểm tra khách sở hữu đơn này
private function assertCustomerOwnsOrder(string $maOrder): void
{
    if (!in_array($maOrder, session('customer_orders', []), true)) {
        abort(403, 'Bạn không có quyền truy cập đơn hàng này.');
    }
}
```

**Giải thích bảo mật:**
- `assertCustomerOwnsOrder()` kiểm tra `$maOrder` có trong `session('customer_orders')` không. Session được lưu server-side (không thể giả mạo từ client). Khách A không thể thêm món vào đơn của khách B dù biết mã đơn.

---

### 3.7 Logic thêm món trong OrderService

**File:** `app/Services/OrderService.php`

```php
public function addItem(string $maOrder, string $maMon, int $soLuong, ?string $ghiChu = null, array $options = []): void
{
    DB::transaction(function () use ($maOrder, $maMon, $soLuong, $ghiChu, $options) {
        // Khóa đơn hàng để tránh race condition (2 request thêm món cùng lúc)
        Order::where('ma_order', $maOrder)
            ->where('trang_thai', 'dang_chon')  // Chỉ cho thêm khi đang chọn
            ->lockForUpdate()
            ->firstOrFail();

        // Kiểm tra món tồn tại và đang active
        $mon = Mon::findOrFail($maMon);
        if ($mon->trang_thai !== 'active') {
            throw ValidationException::withMessages(['ma_mon' => 'Món này hiện không thể đặt.']);
        }

        // Kiểm tra xem món này đã có trong giỏ chưa
        $existing = ChiTietOrder::where('ma_order', $maOrder)->where('ma_mon', $maMon)
            ->lockForUpdate()->first();

        if ($existing) {
            // Nếu có rồi → tăng số lượng (không tạo dòng mới)
            $existing->increment('so_luong', $soLuong);
            $action = 'tang_so_luong_mon';
        } else {
            // Nếu chưa có → tạo dòng mới, lưu giá tại thời điểm đặt
            $chiTiet = ChiTietOrder::create([
                'ma_order'              => $maOrder,
                'ma_mon'                => $maMon,
                'so_luong'              => $soLuong,
                'don_gia_tai_thoi_diem' => $mon->don_gia,  // CHỐT GIÁ ngay lúc đặt
                'ghi_chu'               => $ghiChu,
            ]);
            $action = 'them_mon';
        }

        // Lưu options (nhiệt độ, độ ngọt, topping)
        $this->syncOrderOptions($chiTietId, $maOrder, $maMon, $options);
    });
}
```

**Điểm quan trọng — Chốt giá:**
- `don_gia_tai_thoi_diem` lưu giá của món **tại thời điểm đặt**. Nếu admin sau đó tăng giá, đơn hàng cũ không bị ảnh hưởng — đây là yêu cầu nghiệp vụ quan trọng.
- `lockForUpdate()` đặt exclusive lock ở cấp hàng (row-level lock) trong MySQL để tránh tình huống 2 request cùng đọc `so_luong = 2` và cả hai tăng lên 3 thay vì 4.

---

### 3.8 Lưu options (nhiệt độ, topping)

**File:** `app/Services/OrderService.php`

```php
private function syncOrderOptions(int $chiTietId, string $maOrder, string $maMon, array $options): void
{
    if (empty($options)) {
        return;
    }

    // Xóa options cũ trước (replace hoàn toàn)
    ChiTietOrderOption::where('chi_tiet_id', $chiTietId)->delete();

    // Tạo options mới
    foreach ($options as $option) {
        ChiTietOrderOption::create([
            'chi_tiet_id'  => $chiTietId,
            'ma_order'     => $maOrder,
            'ma_mon'       => $maMon,
            'loai_option'  => (string) ($option['type'] ?? 'custom'),
            // ↑ 'temperature', 'sweetness', hoặc 'topping'
            'ten_lua_chon' => (string) ($option['value'] ?? ''),
            // ↑ Ví dụ: 'Đá', 'Ít ngọt', 'Trân châu'
            'gia_them'     => (int) ($option['price'] ?? 0),
            // ↑ Phụ thu: topping thêm 10,000đ
        ]);
    }
}
```

**Ví dụ dữ liệu options được lưu:**
```
chi_tiet_id | loai_option  | ten_lua_chon | gia_them
───────────────────────────────────────────────────
5           | temperature  | Đá           | 0
5           | sweetness    | Ít ngọt      | 0
5           | topping      | Trân châu    | 10000
```

---

### 3.9 Trang xác nhận đơn (Checkout)

**File:** `resources/views/customer/checkout.blade.php`

```php
// Tính tổng tiền: giá món + phụ thu options × số lượng
$total = $order->chiTietOrders->sum(
    fn($i) => ($i->don_gia_tai_thoi_diem + $i->options->sum('gia_them')) * $i->so_luong
);
```

```html
<!-- Danh sách món đã chọn -->
@foreach($order->chiTietOrders as $item)
<div class="flex items-center justify-between ...">
    <p>{{ $item->mon->ten_mon }}</p>
    <p>{{ $item->ghi_chu }}</p>     <!-- Ghi chú như "Ít đường" -->
    <p>Số lượng: {{ $item->so_luong }}</p>
    <!-- Giá = (giá gốc + phụ thu) × số lượng -->
    <p>{{ number_format(($item->don_gia_tai_thoi_diem + $item->options->sum('gia_them')) * $item->so_luong) }}đ</p>
</div>
@endforeach

<!-- Gợi ý món kèm (AI market-basket) -->
@if(!empty($suggestions))
@foreach($suggestions as $sg)
<form method="POST" action="{{ route('customer.addItem', $order->ma_order) }}">
    @csrf
    <input type="hidden" name="ma_mon" value="{{ $sg['ma_mon'] }}">
    <input type="hidden" name="so_luong" value="1">
    <button>{{ $sg['ten_mon'] }} +{{ $sg['don_gia'] }}đ</button>
</form>
@endforeach
@endif

<!-- Lựa chọn hình thức phục vụ -->
<div x-data="{ ht: '{{ $order->hinh_thuc ?? 'tai_ban' }}' }">
    <label><input type="radio" name="hinh_thuc" value="tai_ban" x-model="ht"> Uống tại bàn</label>
    <label><input type="radio" name="hinh_thuc" value="mang_ve" x-model="ht"> Mang về (cốc nhựa)</label>
</div>

<!-- Nút gửi đơn -->
<form method="POST" action="{{ route('customer.confirm', $order->ma_order) }}">
    @csrf
    <button>Gửi đơn cho quán</button>
</form>
```

**Giải thích:**
- `x-data="{ ht: '...' }"` và `x-model="ht"` — Alpine.js binding. Khi khách chọn "Mang về", card tự đổi màu nền (xanh → cam) mà không cần reload trang.
- Gợi ý món (`$suggestions`) được tạo bởi `AnalyticsService::recommendForItems()` — phân tích "market basket" (các món thường được mua kèm nhau).

---

### 3.10 Khách gửi đơn — Backend

**File:** `app/Http/Controllers/OrderController.php`

```php
// Route: POST /order/{ma_order}/confirm
public function confirmByCustomer(Request $request, string $maOrder)
{
    $this->assertCustomerOwnsOrder($maOrder);  // Bảo mật: kiểm tra quyền sở hữu

    $request->validate(['hinh_thuc' => 'nullable|in:tai_ban,mang_ve']);

    $order = Order::where('ma_order', $maOrder)->where('trang_thai', 'dang_chon')->first();
    if (!$order) return redirect()->route('customer.status', $maOrder); // Đơn đã gửi rồi

    $this->orderService->submitByCustomer($maOrder, $request->input('hinh_thuc'));

    return redirect()->route('customer.status', $maOrder)
        ->with('info', 'Đơn hàng đã được gửi. Vui lòng chờ nhân viên xác nhận.');
}
```

**File:** `app/Services/OrderService.php`

```php
public function submitByCustomer(string $maOrder, ?string $hinhThuc = null): void
{
    DB::transaction(function () use ($maOrder, $hinhThuc) {
        $order = Order::where('ma_order', $maOrder)
                      ->where('trang_thai', 'dang_chon')
                      ->lockForUpdate()
                      ->firstOrFail();

        $changes = ['trang_thai' => 'cho_xac_nhan'];  // Chuyển sang "Chờ xác nhận"
        if ($hinhThuc !== null) {
            $changes['hinh_thuc'] = $hinhThuc === 'mang_ve' ? 'mang_ve' : 'tai_ban';
        }
        $order->update($changes);

        // Ghi log sự kiện: khách gửi đơn
        $this->log($maOrder, 'khach_gui_don', 'dang_chon', 'cho_xac_nhan',
            'Khach hang gui don cho quan.',
            ['hinh_thuc' => $changes['hinh_thuc'] ?? $order->hinh_thuc]);
    });
}
```

**Giải thích:**
- Sau khi khách bấm "Gửi đơn", `trang_thai` đổi từ `dang_chon` → `cho_xac_nhan`.
- Lúc này đơn xuất hiện trên **order board** của nhân viên.
- `lockForUpdate()` ngăn tình huống khách bấm "Gửi" 2 lần nhanh: request thứ nhất lock hàng, request thứ hai bị block cho đến khi request thứ nhất commit.

---

### 3.11 Nhân viên xem và xác nhận đơn

**File:** `app/Http/Controllers/OrderController.php`

```php
// Route: GET /orders?status=cho_xac_nhan
public function index(Request $request)
{
    $status     = $request->get('status', 'cho_xac_nhan'); // Tab mặc định
    $maChiNhanh = (string) session('ma_chi_nhanh', '');

    $orders = Order::with(['ban', 'chiTietOrders.mon', 'chiTietOrders.options', 'khachHang', 'hoaDon'])
        ->where('ma_chi_nhanh', $maChiNhanh)         // Chỉ xem đơn chi nhánh mình
        ->where('trang_thai', $status)
        ->orderByDesc('ngay_order')
        ->orderByDesc('gio_order')
        ->paginate(12);

    $counts = $this->orderService->countByStatus($maChiNhanh); // Đếm đơn theo từng tab

    return view('staff.order-list', compact('orders', 'counts', 'status', ...));
}
```

**Trang order-list có auto-refresh:**

**File:** `resources/views/staff/order-list.blade.php`

```javascript
// Polling đơn đổi bàn mỗi 8 giây
function moveReqs() {
    return {
        list: [],
        init() {
            this.fetchList();
            setInterval(() => this.fetchList(), 8000); // Cập nhật mỗi 8 giây
        },
        async fetchList() {
            const r = await fetch('/yeu-cau-doi-ban', { headers: { Accept: 'application/json' } });
            const d = await r.json();
            this.list = d.requests || [];
        },
        async act(id, action) {
            await fetch(`/yeu-cau-doi-ban/${id}/${action}`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
            });
            window.location.reload(); // Reload sau khi duyệt/từ chối
        },
    };
}
```

---

### 3.12 Nhân viên xác nhận đơn

**File:** `app/Services/OrderService.php`

```php
public function confirm(string $maOrder): void
{
    DB::transaction(function () use ($maOrder) {
        $order = Order::where('ma_order', $maOrder)->lockForUpdate()->first();

        // Kiểm tra trạng thái: phải là "chờ xác nhận"
        if ($order->trang_thai !== 'cho_xac_nhan') {
            throw ValidationException::withMessages([
                'order' => 'Đơn này không ở trạng thái chờ xác nhận (hiện tại: '
                         . $this->statusLabel($order->trang_thai) . ').',
            ]);
        }

        // ─── Kiểm tra tồn kho trước khi xác nhận ────────────────
        $order->loadMissing('chiTietOrders.mon.dinhMucs.nguyenLieu.tonKhos');
        $stockIssues = $this->stockIssuesForOrder($order);
        if (!empty($stockIssues)) {
            throw ValidationException::withMessages([
                'stock' => 'Không thể xác nhận đơn vì thiếu nguyên liệu: '
                         . implode('; ', $stockIssues),
            ]);
        }

        // Xác nhận xong → nhảy THẲNG sang "đang pha chế" (bỏ bước "đã xác nhận")
        $order->update([
            'trang_thai'         => 'dang_pha_che',
            'thoi_gian_xac_nhan' => now(),           // Đóng dấu thời gian
        ]);

        $this->log($maOrder, 'xac_nhan_don', 'cho_xac_nhan', 'dang_pha_che',
            'Nhan vien xac nhan don -> dang pha che.', maNv: session('ma_nv'));
    });
}
```

**Giải thích nghiệp vụ:**
- Khi xác nhận, hệ thống **kiểm tra tồn kho**: nếu thiếu nguyên liệu, nhân viên nhận thông báo cụ thể "Cà phê sữa thiếu hạt trân châu" thay vì xác nhận mà sau đó không pha chế được.
- Bỏ bước trung gian `da_xac_nhan` để đơn giản hóa luồng: xác nhận xong là bắt đầu pha chế ngay.
- `thoi_gian_xac_nhan` ghi timestamp để tính thời gian phục vụ (KPI của quán).

---

### 3.13 Card đơn hàng — Giao diện nhân viên

**File:** `resources/views/components/order-card.blade.php`

```html
<div class="relative flex flex-col ...">
    <!-- Link phủ toàn thẻ → click vào đâu cũng mở trang chi tiết -->
    <a href="{{ route('orders.show', $order->ma_order) }}" class="absolute inset-0 z-0"></a>

    <!-- Header: số bàn + giờ đặt + trạng thái -->
    <div class="relative z-10 flex items-start justify-between">
        <p>{{ $order->ban ? 'Bàn ' . $order->ban->so_ban : 'Mang về' }}</p>
        <x-order-status-badge :status="$order->trang_thai" />
        <span>{{ $order->dung_coc_nhua ? 'Mang về' : 'Tại bàn' }}</span>
    </div>

    <!-- Danh sách tối đa 3 món (+ x món khác) -->
    @forelse($order->chiTietOrders->take(3) as $item)
    <p>{{ $item->mon->ten_mon ?? '—' }} x{{ $item->so_luong }} · {{ $item->don_gia_tai_thoi_diem }}đ</p>
    @endforelse
    @if($order->chiTietOrders->count() > 3)
    <p>+ {{ $order->chiTietOrders->count() - 3 }} món khác</p>
    @endif

    <!-- Mốc thời gian: đặt / phục vụ / thanh toán -->
    <div class="grid grid-cols-3 text-[10px]">
        <span>Đặt: {{ $fmt($order->gio_order) }}</span>
        <span>Phục vụ: {{ $fmt($order->thoi_gian_phuc_vu) }}</span>
        <span>TToán: {{ $fmt($order->thoi_gian_thanh_toan) }}</span>
    </div>

    <!-- Nút thao tác (z-10 để nằm trên link phủ) -->
    <div class="relative z-10 flex gap-2">
        @if($order->trang_thai === 'cho_xac_nhan')
        <form method="POST" action="{{ route('orders.confirm', $order->ma_order) }}">
            @csrf @method('PUT')
            <button class="... bg-[#E82C2A] text-white">Xác nhận</button>
        </form>
        @endif

        @if(in_array($order->trang_thai, ['da_xac_nhan','dang_pha_che','da_phuc_vu']))
        <a href="{{ route('payment.show', $order->ma_order) }}" class="... bg-[#52613B] text-white">
            Thanh toán
        </a>
        @endif
    </div>
</div>
```

**Giải thích kỹ thuật `z-index`:**
- Thẻ card có một `<a>` phủ toàn bộ diện tích (`absolute inset-0 z-0`).
- Các nút thao tác có `relative z-10` — nằm trên link phủ, nhận click ưu tiên.
- Kết quả: click vào vùng trống → mở trang chi tiết; click vào nút → thực thi action.

---

### 3.14 Cập nhật trạng thái đơn — Flow pha chế và phục vụ

**File:** `app/Http/Controllers/OrderController.php`

```php
// Route: PUT /orders/{ma_order}/status
public function updateStatus(Request $request, string $maOrder)
{
    $request->validate([
        'trang_thai' => 'required|in:da_xac_nhan,dang_pha_che,da_phuc_vu,hoan_thanh,da_huy',
    ]);

    // Kiểm tra phạm vi chi nhánh
    Order::where('ma_order', $maOrder)
        ->where('ma_chi_nhanh', (string) session('ma_chi_nhanh', ''))
        ->firstOrFail();

    $this->orderService->updateStatus($maOrder, $request->trang_thai);

    if ($request->expectsJson()) {
        return response()->json(['ok' => true, 'trang_thai' => $request->trang_thai]);
    }
    return back()->with('success', 'Cập nhật trạng thái thành công.');
}
```

**File:** `app/Services/OrderService.php`

```php
public function updateStatus(string $maOrder, string $trangThai): void
{
    DB::transaction(function () use ($maOrder, $trangThai) {
        $order     = Order::where('ma_order', $maOrder)->lockForUpdate()->firstOrFail();
        $oldStatus = $order->trang_thai;

        $changes = ['trang_thai' => $trangThai];

        // Đóng dấu timestamp khi đạt mốc phục vụ
        if ($trangThai === 'da_phuc_vu' && !$order->thoi_gian_phuc_vu) {
            $changes['thoi_gian_phuc_vu'] = now();
        }

        $order->update($changes);
        $this->log($maOrder, 'cap_nhat_trang_thai', $oldStatus, $trangThai, ..., maNv: session('ma_nv'));
    });
}
```

**Giải thích:**
- `thoi_gian_phuc_vu` chỉ được ghi lần đầu (`!$order->thoi_gian_phuc_vu`). Nếu nhân viên cập nhật trạng thái nhầm rồi sửa lại, timestamp không bị ghi đè.
- Mỗi thay đổi trạng thái đều tạo một bản ghi trong `ORDER_LOG` — toàn bộ lịch sử xử lý đơn được lưu đầy đủ.

---

### Sơ đồ luồng Đặt món và nhận món

```
Khách bấm "Bắt đầu gọi món"
           │
           ▼
POST /order/{ma_ban}/create
→ OrderService::createOrder()
→ Tạo ORDERS với trang_thai='dang_chon'
→ Lưu ma_order vào session
           │
           ▼
GET /order/{ma_ban}/menu
→ MenuController::customerMenu()
→ Hiển thị menu + sơ đồ 3D
           │
           │ [Khách chọn từng món]
           ▼
POST /order/{ma_order}/item
→ OrderService::addItem()
→ Tạo/cập nhật CHI_TIET_ORDER
→ Lưu CHI_TIET_ORDER_OPTION (nhiệt độ, topping...)
           │
           ▼
GET /order/{ma_order}/cart   (Trang checkout)
→ Hiển thị giỏ hàng + gợi ý AI
           │
           │ [Khách bấm "Gửi đơn"]
           ▼
POST /order/{ma_order}/confirm
→ OrderService::submitByCustomer()
→ trang_thai: dang_chon → cho_xac_nhan
           │
           │ [Nhân viên thấy trên order board]
           ▼
PUT /orders/{ma_order}/confirm  (Nhân viên xác nhận)
→ OrderService::confirm()
→ Kiểm tra tồn kho
→ trang_thai: cho_xac_nhan → dang_pha_che
→ Ghi thoi_gian_xac_nhan
           │
           │ [Bartender pha chế xong]
           ▼
PUT /orders/{ma_order}/status  {trang_thai: da_phuc_vu}
→ OrderService::updateStatus()
→ trang_thai: dang_pha_che → da_phuc_vu
→ Ghi thoi_gian_phuc_vu
           │
           │ [Nhân viên thu tiền]
           ▼
POST /payment/{ma_order}
→ PaymentController::process()
→ trang_thai: da_phuc_vu → hoan_thanh
→ Tạo HOA_DON
→ Tạo KHACH_HANG (nếu chưa có)
```

---

## 4. Luồng 3 — Theo dõi trạng thái đơn hàng

### Mô tả tổng quát

Sau khi khách gửi đơn, hệ thống hiển thị trang theo dõi trạng thái realtime. Trang này dùng kỹ thuật **polling** (gọi API mỗi 5 giây) thay vì WebSocket để đơn giản hóa hạ tầng mà vẫn đảm bảo trải nghiệm gần realtime.

---

### 4.1 Trang trạng thái — Backend

**File:** `app/Http/Controllers/OrderController.php`

```php
// Route: GET /order/{ma_order}/status
public function status(string $maOrder)
{
    $this->assertCustomerOwnsOrder($maOrder); // Bảo mật: chỉ khách tạo đơn mới xem được

    $order = Order::findOrFail($maOrder);
    return view('customer.status', compact('order'));
}

// Route: GET /order/{ma_order}/status.json  (API cho polling)
public function statusJson(string $maOrder)
{
    $this->assertCustomerOwnsOrder($maOrder);

    $order = Order::findOrFail($maOrder);
    return response()->json([
        'ma_order'   => $order->ma_order,
        'trang_thai' => $order->trang_thai,
        // Chỉ trả về những gì cần thiết — tránh lộ thông tin nhạy cảm
    ]);
}
```

**Giải thích:**
- Endpoint `.json` chỉ trả về `ma_order` và `trang_thai` — tối thiểu hóa dữ liệu trong response.
- Cả hai đều gọi `assertCustomerOwnsOrder()` — tránh "IDOR" (Insecure Direct Object Reference): ai đó đoán được mã đơn và theo dõi đơn người khác.

---

### 4.2 Trang trạng thái — Frontend

**File:** `resources/views/customer/status.blade.php`

```php
// Định nghĩa các bước và nội dung hiển thị
$steps = [
    'cho_xac_nhan' => ['label' => 'Chờ xác nhận',   'copy' => 'Nhân viên sẽ nhận đơn trong giây lát.', 'icon' => '01'],
    'da_xac_nhan'  => ['label' => 'Đã xác nhận',    'copy' => 'Đơn đã được chuyển tới quầy pha chế.', 'icon' => '02'],
    'dang_pha_che' => ['label' => 'Đang pha chế',   'copy' => 'Đồ uống của bạn đang được chuẩn bị.',  'icon' => '03'],
    'da_phuc_vu'   => ['label' => 'Đã phục vụ',     'copy' => 'Nhân viên đã phục vụ đơn. Vui lòng thanh toán.', 'icon' => '04'],
    'hoan_thanh'   => ['label' => 'Đơn đã thanh toán', 'copy' => 'Cảm ơn bạn đã ghé 8am.coffee.', 'icon' => '05'],
];

// Xử lý các trạng thái đặc biệt
$active = match($order->trang_thai) {
    'dang_chon' => ['label' => 'Chưa gửi đơn',  'copy' => 'Bạn có thể quay lại thực đơn.', 'icon' => '...'],
    'da_huy'    => ['label' => 'Đơn đã hủy',    'copy' => 'Bạn có thể quay lại để gọi món khác.', 'icon' => '!'],
    default     => $steps[$order->trang_thai] ?? [...],
};

// Tính vị trí hiện tại trong chuỗi các bước
$keys         = array_keys($steps);
$currentIndex = array_search($order->trang_thai, $keys, true);
```

**Hiển thị timeline (thanh tiến trình):**

```html
@foreach($steps as $status => $step)
    @php
        $idx  = array_search($status, $keys, true);
        $done = $currentIndex !== false && $idx <= $currentIndex;
        // $done = true nếu bước này đã qua hoặc đang xử lý
    @endphp
    <div class="{{ $done ? 'bg-[#1A1A1A] text-white' : 'bg-[#F6F3F2] text-[#522C25]/60' }} flex items-center gap-3 rounded-2xl px-4 py-3">
        <!-- Bước đã hoàn thành: nền đen + chữ trắng -->
        <!-- Bước chưa đến:    nền xám nhạt + chữ mờ   -->
        <span class="h-8 w-8 rounded-full {{ $done ? 'bg-white/15' : 'bg-white' }}">
            {{ $step['icon'] }}
        </span>
        <span>{{ $step['label'] }}</span>
    </div>
@endforeach
```

**Giải thích:**
- `$currentIndex` là vị trí số (0–4) của trạng thái hiện tại.
- `$idx <= $currentIndex` → bước nào có số thứ tự nhỏ hơn hoặc bằng vị trí hiện tại đều được tô đậm.
- Ví dụ: `dang_pha_che` (index 2) → bước 0, 1, 2 tô đậm; bước 3, 4 mờ.

---

### 4.3 Polling trạng thái — JavaScript

**File:** `resources/views/customer/status.blade.php`

```javascript
// Chỉ chạy khi đơn chưa hoàn tất (không poll khi đã 'hoan_thanh' hoặc 'da_huy')
@if(!in_array($order->trang_thai, ['hoan_thanh', 'da_huy']))
<script>
(function () {
    // URL và trạng thái hiện tại được nhúng từ server (Blade → JS)
    const url     = @json(route('customer.statusJson', $order->ma_order));
    //                    ↑ Xuất ra chuỗi JavaScript an toàn (không cần escape thủ công)
    const current = @json($order->trang_thai);

    async function poll() {
        try {
            const r = await fetch(url, {
                credentials: 'same-origin',          // Gửi kèm session cookie
                headers: { Accept: 'application/json' }
            });

            if (r.ok) {
                const data = await r.json();
                // Chỉ reload khi trạng thái THỰC SỰ đổi
                // Tránh reload liên tục gây nháy màn hình
                if (data.trang_thai && data.trang_thai !== current) {
                    window.location.reload();
                    return; // Dừng polling sau khi reload
                }
            }
        } catch (e) {
            // Bỏ qua lỗi mạng tạm thời (WiFi yếu, offline...)
            // Sẽ tự thử lại sau 5 giây
        }
        setTimeout(poll, 5000); // Gọi lại sau 5 giây
    }

    setTimeout(poll, 5000); // Bắt đầu sau 5 giây
})();
</script>
@endif
```

**Giải thích kỹ thuật polling:**

| Vấn đề | Giải pháp |
|--------|----------|
| Tránh reload liên tục khi trạng thái không đổi | So sánh `data.trang_thai !== current` |
| Không poll khi đơn đã kết thúc | Blade `@if(!in_array(...))` — script không được sinh ra |
| Xử lý lỗi mạng | `try/catch` — bỏ qua exception, tự retry sau 5 giây |
| Bảo mật | `credentials: 'same-origin'` — gửi session cookie (server validate quyền sở hữu) |
| Server không bị quá tải | Mỗi client poll mỗi 5 giây — 100 khách đồng thời = 20 req/s, rất nhẹ |

---

### 4.4 Nút "Gọi món khác"

```html
<!-- Khi đơn đang xử lý (chưa xong): cho phép đặt ĐƠN MỚI song song -->
@php $dangXuLy = in_array($order->trang_thai, ['cho_xac_nhan','dang_pha_che','da_phuc_vu']); @endphp

@if($order->ma_ban)
<form method="POST" action="{{ route('customer.create', $order->ma_ban) }}">
    @csrf
    <!-- Điền sẵn tên/SĐT từ session (không phải nhập lại) -->
    <input type="hidden" name="ten_kh" value="{{ session('customer_profile.ten_kh', $order->ten_khach ?: 'Khách') }}">
    <input type="hidden" name="sdt_kh" value="{{ session('customer_profile.sdt_kh', $order->sdt_khach) }}">

    <button type="submit">
        {{ $dangXuLy ? 'Đặt thêm đơn mới' : 'Gọi món khác' }}
    </button>
</form>
@endif

@if($dangXuLy)
<p class="text-xs text-gray-500">Đơn hiện tại vẫn đang được phục vụ — đơn mới sẽ là một đơn riêng.</p>
@endif
```

**Giải thích nghiệp vụ:**
- Nếu khách đang chờ đơn cũ được pha chế và muốn gọi thêm → hệ thống tạo **đơn mới riêng biệt** thay vì sửa đơn cũ (vì đơn cũ đã ở trạng thái không thể chỉnh sửa).
- Tên/SĐT được lấy từ `session('customer_profile')` — khách không nhập lại.
- Text nút thay đổi: "Gọi món khác" (khi đơn kết thúc) vs "Đặt thêm đơn mới" (khi đơn đang xử lý).

---

### 4.5 Order board của nhân viên — API polling

**File:** `app/Http/Controllers/OrderController.php`

```php
// Route: GET /orders/api/list
public function apiList()
{
    $maChiNhanh = (string) session('ma_chi_nhanh', '');

    $orders = Order::with(['ban', 'chiTietOrders.mon'])
        ->where('ma_chi_nhanh', $maChiNhanh)
        ->whereIn('trang_thai', ['cho_xac_nhan','da_xac_nhan','dang_pha_che','da_phuc_vu'])
        ->where('ngay_order', now()->toDateString())  // Chỉ lấy đơn hôm nay
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
```

**Giải thích:**
- API này phục vụ màn hình **order board** — nhân viên giữ tab này mở suốt ca làm việc, JavaScript gọi API mỗi 10 giây để hiển thị đơn mới.
- `->where('ngay_order', now()->toDateString())` — Chỉ lấy đơn ngày hôm nay, tránh load lịch sử.
- Dữ liệu trả về tối giản — không trả tên khách hay SĐT (vì không cần thiết trên màn hình board).

---

### Sơ đồ luồng Theo dõi trạng thái

```
Khách gửi đơn thành công
           │
           ▼
redirect → GET /order/{ma_order}/status
→ OrderController::status()
→ Render customer/status.blade.php
           │
           │ [Trang tải xong — JS khởi động polling]
           ▼
           ┌─────────────────────────────────┐
           │  setInterval: mỗi 5 giây        │
           │                                 │
           │  fetch /order/{ma_order}/status.json
           │  ↓                              │
           │  statusJson() → {trang_thai}    │
           │  ↓                              │
           │  trang_thai đổi? → reload       │
           │  trang_thai giống? → tiếp tục   │
           └─────────────────────────────────┘

Đồng thời trên máy nhân viên:
           │
           ▼
Order board → đơn mới xuất hiện (polling mỗi 10 giây)
           │
           ├── Nhân viên bấm "Xác nhận"
           │         PUT /orders/{ma_order}/confirm
           │         → trang_thai: cho_xac_nhan → dang_pha_che
           │
           │   [Khách polling phát hiện đổi → reload]
           │   [Thanh tiến trình: 01 tô đậm → 01+02+03 tô đậm]
           │
           ├── Nhân viên bấm "Phục vụ"
           │         PUT /orders/{ma_order}/status {da_phuc_vu}
           │         → trang_thai: dang_pha_che → da_phuc_vu
           │
           │   [Khách thấy: "Nhân viên đã phục vụ đơn. Vui lòng thanh toán."]
           │
           └── Nhân viên thanh toán
                     POST /payment/{ma_order}
                     → trang_thai: da_phuc_vu → hoan_thanh
                     → Tạo HOA_DON, tạo KHACH_HANG

                     [Polling dừng — đơn kết thúc]
                     [Khách thấy: "Cảm ơn bạn đã ghé 8am.coffee."]
```

---

## 5. Sơ đồ vòng đời trạng thái đơn

```
                        [KHÁCH TẠO ĐƠN QUA QR]
                               │
                               ▼
                          ┌──────────┐
                          │ dang_chon│  ← Khách đang chọn món
                          └──────────┘
                               │
                    [Khách bấm "Gửi đơn"]
                               │
                               ▼
                       ┌─────────────┐
                       │cho_xac_nhan │  ← Đơn hiện trên board nhân viên
                       └─────────────┘
                               │
              [Nhân viên xác nhận + kiểm tra tồn kho]
                               │
                               ▼
                       ┌──────────────┐
                       │ dang_pha_che │  ← Bartender đang pha
                       └──────────────┘
                               │
                  [Bartender phục vụ xong]
                               │
                               ▼
                       ┌─────────────┐
                       │ da_phuc_vu  │  ← Đã mang ra bàn
                       └─────────────┘
                               │
                  [Nhân viên thu tiền]
                               │
                               ▼
                       ┌──────────────┐
                       │  hoan_thanh  │  ← Đã thanh toán (kết thúc)
                       └──────────────┘

     Bất kỳ trạng thái nào (trừ hoan_thanh) đều có thể:
                               │
                        [Hủy đơn]
                               │
                               ▼
                       ┌─────────┐
                       │ da_huy  │  ← Đơn bị hủy
                       └─────────┘

   [Nhân viên tạo đơn tại quầy — không qua QR]
                    │
                    ▼
             ┌─────────────┐
             │cho_xac_nhan │  ← Bắt đầu từ đây (bỏ bước dang_chon)
             └─────────────┘
```

**Chú thích:**
- Đơn tạo qua QR bắt đầu từ `dang_chon` (khách chọn món trước).
- Đơn nhân viên tạo tại quầy (tại bàn / mang về) bắt đầu từ `cho_xac_nhan` (đã có món rồi).
- Đơn tại bàn qua QR: khi xác nhận → nhảy thẳng `dang_pha_che` (bỏ `da_xac_nhan`).

---

## 6. Bảng tóm tắt các bảng CSDL liên quan

### Bảng ORDERS

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `ma_order` | VARCHAR(20) PK | Mã đơn: `ORD260604143052xx` |
| `ma_ban` | VARCHAR(10) FK NULL | Mã bàn (null = mang về) |
| `ma_kh` | VARCHAR(10) FK NULL | Khách hàng (chỉ có sau thanh toán) |
| `ten_khach` | TEXT | Tên tạm — **mã hóa PII** |
| `sdt_khach` | TEXT | SĐT tạm — **mã hóa PII** |
| `ma_chi_nhanh` | VARCHAR(10) FK | Chi nhánh |
| `trang_thai` | VARCHAR(15) | dang_chon / cho_xac_nhan / dang_pha_che / da_phuc_vu / hoan_thanh / da_huy |
| `hinh_thuc` | VARCHAR(10) | `tai_ban` hoặc `mang_ve` |
| `ngay_order` | DATE | Ngày đặt |
| `gio_order` | TIME | Giờ đặt |
| `thoi_gian_xac_nhan` | DATETIME | Mốc nhân viên xác nhận |
| `thoi_gian_phuc_vu` | DATETIME | Mốc phục vụ xong |
| `thoi_gian_thanh_toan` | DATETIME | Mốc hoàn tất thanh toán |

### Bảng CHI_TIET_ORDER

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | INT PK | ID tự tăng |
| `ma_order` | VARCHAR(20) FK | Đơn hàng |
| `ma_mon` | VARCHAR(10) FK | Món đã chọn |
| `so_luong` | INT | Số lượng |
| `don_gia_tai_thoi_diem` | DECIMAL | Giá tại thời điểm đặt (chốt giá) |
| `ghi_chu` | VARCHAR(200) | Ghi chú riêng cho món (ít đường, không đá...) |

### Bảng CHI_TIET_ORDER_OPTION

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | INT PK | |
| `chi_tiet_id` | INT FK | Dòng chi tiết đơn |
| `loai_option` | VARCHAR(30) | `temperature`, `sweetness`, `topping` |
| `ten_lua_chon` | VARCHAR(100) | Giá trị: `Đá`, `Ít ngọt`, `Trân châu` |
| `gia_them` | INT | Phụ thu (0 hoặc số tiền) |

### Bảng SCAN_LOG

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | INT PK | |
| `ma_ban` | VARCHAR(10) | Bàn được quét |
| `ma_chi_nhanh` | VARCHAR(10) | Chi nhánh |
| `ip` | VARCHAR(45) | IP thiết bị (IPv4/IPv6) |
| `user_agent` | VARCHAR(300) | Trình duyệt / thiết bị |
| `thoi_gian` | DATETIME | Thời điểm quét |

### Bảng ORDER_LOG

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | INT PK | |
| `ma_order` | VARCHAR(20) FK | Đơn hàng |
| `hanh_dong` | VARCHAR(50) | tao_don_nhap / them_mon / xac_nhan_don / cap_nhat_trang_thai... |
| `trang_thai_cu` | VARCHAR(15) | Trạng thái trước |
| `trang_thai_moi` | VARCHAR(15) | Trạng thái sau |
| `noi_dung` | TEXT | Mô tả |
| `du_lieu` | JSON | Dữ liệu bổ sung (items, số lượng...) |
| `ma_nv` | VARCHAR(10) | Nhân viên thực hiện (null = khách) |
| `created_at` | DATETIME | Thời điểm ghi log |

---

*Tài liệu được tạo tự động từ mã nguồn dự án 8AM Coffee Ordering System — phiên bản tháng 6/2026.*
