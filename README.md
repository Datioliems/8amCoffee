# ☕ 8AM Coffee & Roastery — Hệ thống quản lý chuỗi quán & đặt món QR

**Nhóm 29 | TTCN | Học viện Ngân hàng**

> Nền tảng quản lý toàn diện cho chuỗi quán cà phê đa chi nhánh: khách đặt món qua mã QR trên bàn, nhân viên quản lý đơn hàng trên sơ đồ bàn 3D, tích hợp kho hàng — thanh toán VNPay — thẻ thành viên RFID — phân tích dữ liệu AI.

---

## Mục lục

1. [Giới thiệu sản phẩm](#1-giới-thiệu-sản-phẩm)
2. [Công nghệ nổi bật](#2-công-nghệ-nổi-bật)
3. [Yêu cầu môi trường](#3-yêu-cầu-môi-trường)
4. [Cài đặt từ đầu](#4-cài-đặt-từ-đầu)
5. [Chạy ứng dụng](#5-chạy-ứng-dụng)
6. [Tài khoản mẫu](#6-tài-khoản-mẫu)
7. [Kiểm thử thủ công](#7-kiểm-thử-thủ-công)
8. [Cấu trúc dự án](#8-cấu-trúc-dự-án)
9. [Lệnh hữu ích](#9-lệnh-hữu-ích)

---

## 1. Giới thiệu sản phẩm

8AM Coffee & Roastery là hệ thống quản lý vận hành chuỗi quán cà phê, xây dựng trên **Laravel 11**, phục vụ hai nhóm người dùng chính:

### Phía khách hàng

| Tính năng | Mô tả |
|-----------|-------|
| **Đặt món qua QR** | Quét mã QR trên bàn → chọn món → gửi đơn — không cần cài app, không cần đăng nhập |
| **Sơ đồ bàn 3D** | Xem trạng thái bàn trống / có khách theo mô hình không gian 3D tương tác (Three.js + GLB) |
| **Đổi bàn** | Gửi yêu cầu chuyển bàn — nhân viên duyệt, toàn bộ đơn chuyển sang bàn mới tức thì |
| **Theo dõi đơn hàng** | Xem tiến trình đơn cập nhật liên tục qua polling JSON |
| **Gợi ý dùng kèm** | Gợi ý món hay đặt cùng nhau dựa trên lịch sử đơn thật (Market Basket Analysis) |
| **Thanh toán VNPay** | Thanh toán online qua cổng VNPAY — hỗ trợ thẻ nội địa & quốc tế |

### Phía nhân viên & quản lý

| Tính năng | Mô tả |
|-----------|-------|
| **Bảng đơn hàng** | Xem, xác nhận, cập nhật trạng thái đơn; lọc theo trạng thái và ngày |
| **Sơ đồ bàn 3D nhân viên** | Kéo-thả sắp xếp bàn trên mô hình 3D, hiển thị số đơn hoạt động từng bàn |
| **Quản lý kho** | Phiếu nhập kho, phiếu kiểm kê, cảnh báo nguyên liệu sắp hết; trigger tự trừ kho khi xác nhận đơn |
| **Thực đơn** | CRUD món, danh mục, topping; ẩn món hết nguyên liệu tự động |
| **Thẻ thành viên RFID** | Phát thẻ, tích điểm, đổi điểm qua đầu đọc Arduino/ESP32; phân hạng Thường / Bạc / Vàng / Kim cương |
| **Phân tích dữ liệu** | Dự báo doanh thu (hồi quy tuyến tính), luật kết hợp sản phẩm, biểu đồ Chart.js |
| **Phát hiện bất thường QR** | Phát hiện quét QR spam / bot bằng engine luật + mô hình ML Python; điểm rủi ro 0–100 |
| **Phân quyền chi tiết** | Role-based (superadmin / admin / nhan_vien) + fine-grained override từng tài khoản |
| **Nhật ký kiểm toán** | Ghi log toàn bộ hành động nghiệp vụ, đăng nhập, email gửi đi |
| **Đa chi nhánh** | Superadmin chuyển chi nhánh tức thì; dữ liệu cô lập hoàn toàn theo chi nhánh |

---

## 2. Công nghệ nổi bật

### Backend — Laravel 11 (PHP 8.2)

| Công nghệ | Ứng dụng trong dự án |
|-----------|----------------------|
| **Eloquent ORM** | 34 model, quan hệ đa cấp (`hasMany`, `belongsToMany`, morphic), soft delete, Eager Loading chống N+1 |
| **Service Layer** | 13 service class — business logic tách khỏi controller (thin controller pattern) |
| **Middleware chain** | `AuthMiddleware → RoleMiddleware → PermissionMiddleware → ArduinoDeviceAuth` |
| **Fine-grained Permissions** | `config/permissions.php` + `Perm::effectiveFor()` — override quyền từng tài khoản, không phụ thuộc package ngoài |
| **2FA OTP Email** | Nhân viên đăng nhập bắt buộc xác minh OTP gửi qua email; model `TaiKhoan` lưu `otp_code`, `otp_expires_at` |
| **PII Encryption** | Trường nhạy cảm (tên, SĐT, địa chỉ khách) mã hoá AES-256-CBC tự động qua Eloquent Cast `EncryptedString`; fault-tolerant với plaintext cũ |
| **Blind Index (HMAC-SHA256)** | `Pii::phoneHash()` — tìm kiếm theo SĐT mà không cần giải mã; pepper từ `PII_PEPPER` env |
| **DB Transaction + Row Lock** | `DB::transaction()` + `lockForUpdate()` trên toàn bộ thao tác tích điểm / đổi điểm / phát thẻ — ngăn race condition |
| **Database View & Trigger** | `V_TRANG_THAI_KHO` (trạng thái tồn kho tổng hợp), `TR_DEDUCT_STOCK` (tự trừ kho khi xác nhận đơn) |
| **Audit Logging** | `NhatKyHanhDong::ghi()` ghi nhật ký mọi thao tác; `NhatKyDangNhap` lưu lịch sử đăng nhập |
| **Config-driven Loyalty Tiers** | Hạng thành viên cấu hình động trong DB (`CauHinhHang`), không hardcode |

### Frontend

| Công nghệ | Phiên bản | Ứng dụng |
|-----------|-----------|----------|
| **Three.js** | 0.184 | Sơ đồ bàn 3D — load `.glb` theo chi nhánh, render bàn theo màu trạng thái, xoay/zoom tự do |
| **GLTFLoader + Draco** | bundled | Giải nén mô hình 3D tối ưu cho web |
| **Tailwind CSS** | v4 | Toàn bộ giao diện (mobile-first, accent `#E82C2A`) |
| **Alpine.js** | v3 | Modal, dropdown, form validation — reactive mà không cần build thêm |
| **Chart.js** | v4 | Biểu đồ doanh thu, đường dự báo, thống kê phân tích |
| **Vite + Laravel Plugin** | v1 | Build pipeline: CSS purge + JS bundle + asset fingerprint |
| **@gltf-transform/core** | v4 | Tối ưu / đóng gói file `.glb` trước khi đưa vào `public/models/` |

### Tích hợp phần cứng & dịch vụ ngoài

| Tích hợp | Chi tiết kỹ thuật |
|----------|-------------------|
| **Arduino / ESP32 + RFID** | REST API với xác thực token SHA-256 per-device (`ThietBiArduino`); firmware C++ tại `arduino/`; bridge Node.js giao tiếp Serial → HTTP |
| **VNPay Gateway v2.1.0** | Tạo URL thanh toán + xác thực IPN callback bằng **HMAC-SHA512**; sandbox & production qua env |
| **SMTP Email** | Gửi OTP đăng nhập, link kích hoạt tài khoản; tự fallback sang `log` driver khi SMTP chưa cấu hình |
| **QR Code** | `simplesoftwareio/simple-qrcode` — sinh QR theo bàn, in poster A4 |
| **Python ML Subprocess** | `ml/predict_qr_anomaly.py` — mô hình phát hiện bất thường, gọi qua `proc_open`, trả kết quả JSON |

### Thuật toán & Phân tích dữ liệu

| Thuật toán | Mô tả |
|------------|-------|
| **Market Basket Analysis** | Tính **support / confidence / lift** cho mọi cặp món trong 120 ngày gần nhất; gợi ý dùng kèm tại trang thanh toán khách; dashboard luật kết hợp tại `/phan-tich` |
| **Hồi quy tuyến tính (Least Squares)** | Dự báo doanh thu 7 ngày tới từ 30 ngày lịch sử; trả về slope, intercept, R², nhãn xu hướng tăng / giảm / ổn định |
| **Phát hiện bất thường QR (Hybrid)** | Tầng luật (tần suất ≥ 100, đa chi nhánh, non-converting, timing đều đặn, quét đêm) + tầng ML Python; điểm rủi ro 0–100; cảnh báo lưu `ScanAnomalyAlert` |

---

## 3. Yêu cầu môi trường

| Thành phần | Phiên bản |
|---|---|
| PHP | **8.2+** (đã kiểm thử 8.2.12) |
| Composer | 2.x |
| Node.js | **18+** (đã kiểm thử 24.15) |
| npm | 9+ (đã kiểm thử 11.12) |
| MySQL / MariaDB | 8.0+ / 10.4+ |

**PHP extensions cần bật:** `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `gd`

> **Windows:** Mở `php.ini`, bỏ dấu `;` trước các dòng `extension=pdo_mysql`, `extension=mbstring`, v.v.

---

## 4. Cài đặt từ đầu

### Bước 1 — Tải source code

```bash
git clone https://github.com/Datioliems/8amCoffee.git
cd 8amCoffee
```

### Bước 2 — Cài dependencies

```bash
composer install
npm install
```

### Bước 3 — Tạo file cấu hình

```bash
# Windows
copy .env.example .env

# Linux / macOS
cp .env.example .env

php artisan key:generate
```

### Bước 4 — Cấu hình database trong `.env`

Mở `.env` và sửa các dòng sau:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=8amcoffee
DB_USERNAME=root
DB_PASSWORD=YOUR_MYSQL_PASSWORD
```

Tạo database rỗng trong MySQL:

```sql
CREATE DATABASE `8amcoffee`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
```

### Bước 5 — (Tuỳ chọn) Cấu hình email & thanh toán

```env
# SMTP — gửi OTP đăng nhập & link kích hoạt tài khoản
# Nếu bỏ qua, OTP/link sẽ xuất hiện trong storage/logs/laravel.log
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=your@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_FROM_ADDRESS=no-reply@8am.coffee
MAIL_FROM_NAME="8AM Coffee"

# VNPay (chỉ cần nếu test thanh toán online)
VNPAY_TMN_CODE=YOUR_TMN_CODE
VNPAY_HASH_SECRET=YOUR_HASH_SECRET
VNPAY_URL=https://sandbox.vnpayment.vn/paymentv2/vpcpay.html
VNPAY_RETURN_URL="${APP_URL}/payment/vnpay/return"

# PII pepper — bảo vệ hash SĐT khách hàng (chuỗi ngẫu nhiên bất kỳ)
PII_PEPPER=change_this_to_a_random_32char_string
```

### Bước 6 — Migrate & Seed dữ liệu mẫu

```bash
# Tạo toàn bộ bảng
php artisan migrate

# Nạp dữ liệu mẫu: chi nhánh, tài khoản, menu, tồn kho, bàn, nhà cung cấp
php artisan db:seed
```

> **Làm lại từ đầu hoàn toàn** ⚠️ xoá sạch dữ liệu:
> ```bash
> php artisan migrate:fresh --seed
> ```

### Bước 7 — Build giao diện (CSS / JS / 3D)

```bash
npm run build
```

---

## 5. Chạy ứng dụng

```bash
php artisan serve
# → Mở http://localhost:8000
```

**Khi phát triển** — chạy song song hai terminal để có hot-reload:

```bash
# Terminal 1
npm run dev

# Terminal 2
php artisan serve
```

| URL | Mô tả |
|-----|-------|
| `http://localhost:8000/login` | Đăng nhập nhân viên |
| `http://localhost:8000/dashboard` | Dashboard nhân viên |
| `http://localhost:8000/order/BAN_B001` | Trang đặt món khách hàng (bàn B001) |
| `http://localhost:8000/floorplan` | Sơ đồ bàn 3D (nhân viên) |
| `http://localhost:8000/phan-tich` | Phân tích dữ liệu & luật kết hợp |

---

## 6. Tài khoản mẫu

| Tên đăng nhập | Mật khẩu | Vai trò | Chi nhánh | Phạm vi |
|---|---|---|---|---|
| `superadmin` | `Admin@123` | superadmin | CN001 | Toàn quyền — đổi chi nhánh, quản lý mọi tài khoản |
| `admin_8am` | `Admin@123` | admin | CN001 | Quản lý toàn bộ CN001 |
| `bartender01` | `Admin@123` | nhan_vien | CN001 | Pha chế |
| `staff01` | `Admin@123` | nhan_vien | CN001 | Phục vụ |

> **2FA OTP:** Mặc định hệ thống gửi OTP qua email khi đăng nhập. Nếu chưa cấu hình SMTP, mở `storage/logs/laravel.log` và tìm dòng `Subject: Mã OTP` để lấy mã.

### Phân quyền chi tiết

- **superadmin** — bypass mọi kiểm tra quyền; chuyển chi nhánh tức thì qua bộ chọn ở sidebar.
- **admin** — tạo / sửa tài khoản ở **mọi chi nhánh**, gán mọi vai trò.
- **nhan_vien / bartender** — chỉ thao tác trong chi nhánh của mình; không sửa được tài khoản quản lý.
- Không ai có thể tự khoá tài khoản đang đăng nhập.
- **Xoá tài khoản bị vô hiệu hoá:** hệ thống không cho xoá — chỉ có thể chuyển sang `inactive` để giữ toàn bộ lịch sử.

---

## 7. Kiểm thử thủ công

### 7.1 Luồng đặt món (khách hàng)

1. Đăng nhập `superadmin` → **Bàn & QR** (`/ban`) → click **Xem QR** bàn `BAN_B001`.
2. Mở URL `http://localhost:8000/order/BAN_B001` trong tab mới (giả lập khách quét QR).
3. Chọn món → **Thêm vào giỏ** → kiểm tra gợi ý dùng kèm → **Gửi đơn**.
4. Xác nhận đơn xuất hiện ở `/orders` phía nhân viên.

### 7.2 Xử lý đơn hàng (nhân viên)

1. Đăng nhập `admin_8am` → **Đơn hàng** (`/orders`).
2. Xác nhận đơn → cập nhật trạng thái theo luồng:
   ```
   cho_xac_nhan → da_xac_nhan → dang_pha_che → da_phuc_vu → hoan_thanh
   ```
3. Thanh toán: click **Thanh toán** → chọn tiền mặt hoặc VNPay sandbox.

### 7.3 Gợi ý dùng kèm (Market Basket Analysis)

1. Hoàn thành ít nhất **3–5 đơn hàng** với các tổ hợp món khác nhau.
2. Mở giỏ hàng khách — mục **"Gợi ý dùng kèm"** xuất hiện dưới dạng pill button.
3. Xem bảng luật kết hợp đầy đủ tại `/phan-tich` (cần quyền `analytics.view`).

### 7.4 Kho hàng & nhập hàng

1. **Kho hàng** (`/inventory`) → xem danh sách nguyên liệu + cảnh báo sắp hết.
2. **Phiếu nhập kho** (`/inventory/import/create`) → chọn nguyên liệu, nhập số lượng → Lưu → Duyệt.
3. **Phiếu kiểm kê** (`/inventory/stockcheck/create`) → nhập số lượng thực tế → Xác nhận.

### 7.5 Phân quyền & vòng đời tài khoản nhân viên

1. Đăng nhập `superadmin` → **Nhân viên & quyền** (`/nhan-vien`).
2. **Tạo tài khoản mới** → điền email → link kích hoạt gửi qua mail (hoặc xem log).
3. **Vô hiệu hoá**: click nút amber "Vô hiệu hoá" — tài khoản chuyển `inactive`, không thể đăng nhập.
4. **Giáng cấp** (ví dụ admin → nhan_vien): popup xác nhận hiện ra; sau xác nhận tài khoản tự vô hiệu hoá.
5. **Thử xoá**: hệ thống chặn — trả về thông báo lỗi, không xoá dữ liệu.

### 7.6 Sơ đồ bàn 3D

1. Đăng nhập nhân viên → **Sơ đồ bàn** (`/floorplan`).
2. Xoay, zoom, click bàn để xem trạng thái + số đơn đang chạy.
3. Phía khách: vào `/order/BAN_B001` → tab **Chọn bàn** → tương tác mô hình 3D.

### 7.7 Thẻ thành viên RFID (không cần phần cứng)

1. **Thẻ thành viên** (`/the-thanh-vien`) → nhập SĐT khách đủ điều kiện → **Phát thẻ**.
2. Xem lịch sử giao dịch điểm, điều chỉnh điểm thủ công, thay đổi trạng thái thẻ.
3. Cấu hình hạng thành viên tại **Cài đặt hạng** (`/the-thanh-vien/cau-hinh-hang`).

### 7.8 Nhật ký & kiểm toán

| Trang | Mô tả |
|-------|-------|
| `/nhat-ky-hanh-dong` | Toàn bộ thao tác nghiệp vụ (tạo đơn, duyệt phiếu, đổi quyền…) |
| `/nhat-ky-dang-nhap` | Lịch sử đăng nhập + IP + user agent |
| `/scan-anomaly-alerts` | Cảnh báo quét QR bất thường |
| `/nhat-ky-email` | Trạng thái email gửi đi (OTP, kích hoạt, v.v.) |

---

## 8. Cấu trúc dự án

```
app/
├── Http/
│   ├── Controllers/                  ← 30 controller (thin — gọi Service)
│   │   └── Api/ArduinoController.php ← REST API cho thiết bị ESP32/Arduino
│   └── Middleware/                   ← Auth, Role, Permission, ArduinoDeviceAuth
├── Models/                           ← 34 Eloquent model
├── Services/
│   ├── AnalyticsService.php          ← Market Basket Analysis + Linear Regression
│   ├── LoyaltyService.php            ← RFID card, tích điểm, đổi điểm, hạng
│   ├── VnpayService.php              ← VNPay HMAC-SHA512
│   ├── OrderService.php              ← Vòng đời đơn hàng
│   ├── QrScanAnomalyDetectionService.php  ← Rule + ML hybrid anomaly
│   ├── QrAnomalyMlService.php        ← Python subprocess ML model
│   └── ...                           ← 13 service tổng cộng
└── Support/
    ├── Perm.php                      ← Fine-grained permission engine
    └── Pii.php                       ← AES encryption + HMAC blind index
arduino/
├── 8am_rfid_reader.ino               ← Firmware ESP32 đọc thẻ RFID
├── 8am_uno_rfid_lcd.ino              ← Firmware với màn hình LCD 2004
└── bridge/8am-rfid-bridge.mjs        ← Node.js bridge: Serial → HTTP API
ml/
└── predict_qr_anomaly.py             ← Mô hình ML phát hiện bất thường QR
resources/
├── js/
│   ├── showroom.js                   ← Three.js viewer 3D (trang đặt món khách)
│   └── floorplan.js                  ← Three.js sơ đồ bàn (nhân viên)
└── views/
    ├── layouts/                      ← app.blade.php (staff), customer.blade.php
    ├── customer/                     ← Giao diện đặt món QR
    └── staff/                        ← Dashboard, orders, inventory, analytics…
public/models/                        ← File .glb (mô hình 3D từng chi nhánh)
database/
├── migrations/                       ← 33+ migration incremental
└── seeders/                          ← 15 seeder idempotent
config/
└── permissions.php                   ← Bảng quyền toàn hệ thống
```

---

## 9. Lệnh hữu ích

```bash
# Phát triển
php artisan serve                     # Khởi động server :8000
npm run dev                           # Vite dev server (hot-reload CSS/JS)
npm run build                         # Build production assets

# Database
php artisan migrate                   # Áp dụng migration mới
php artisan migrate:fresh --seed      # Reset toàn bộ DB + seed lại  ⚠️ xoá hết dữ liệu
php artisan db:seed                   # Chỉ chạy seeder (không reset bảng)
php artisan migrate:status            # Kiểm tra trạng thái migration

# Bảo trì & debug
php artisan optimize:clear            # Xoá cache config / route / view
php artisan route:list                # Xem danh sách route
php artisan config:cache              # Cache config (production)
php artisan route:cache               # Cache route (production)
```

---

<p align="center">Made with ☕ by <strong>Nhóm 29 — Học viện Ngân hàng</strong></p>
