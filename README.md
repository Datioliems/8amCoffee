# ☕ 8AM Coffee & Roastery — Hệ thống quản lý quán & đặt món
**Nhóm 29 | TTCN | Học viện Ngân hàng**

Ứng dụng Laravel 11 quản lý chuỗi quán cà phê: đặt món qua QR, quản lý đơn,
kho hàng, thực đơn, **sơ đồ bàn 3D (Three.js)** và **quản lý đa chi nhánh**.

---

## 1. Yêu cầu môi trường

| Thành phần | Phiên bản đã kiểm thử |
|---|---|
| PHP        | 8.2+ (8.2.12) |
| Composer   | 2.x |
| Node.js    | 18+ (đã test 24.15) |
| npm        | 9+ (đã test 11.12) |
| MySQL/MariaDB | 8.0+ / 10.4+ |

Extension PHP cần bật: `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `gd`.

---

## 2. Cài đặt lần đầu

```bash
# 1) Cài thư viện PHP & JS
composer install
npm install

# 2) Tạo file cấu hình
copy .env.example .env        # Windows  (Linux/Mac: cp .env.example .env)
php artisan key:generate

# 3) Sửa thông tin DB trong .env
#    DB_DATABASE=8amcoffee
#    DB_USERNAME=root
#    DB_PASSWORD=...   (mật khẩu MySQL của bạn)
```

Tạo database rỗng tên `8amcoffee` trong MySQL trước khi migrate:

```sql
CREATE DATABASE 8amcoffee CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

```bash
# 4) Tạo bảng + dữ liệu mẫu
php artisan migrate          # chạy toàn bộ migration
php artisan db:seed          # nạp dữ liệu mẫu + tài khoản (idempotent)

# 5) Build giao diện (CSS/JS, gồm cả module 3D)
npm run build
```

> **Lưu ý:** mỗi khi sửa file trong `resources/js` (vd `showroom.js`, `floorplan.js`)
> phải chạy lại `npm run build`, hoặc dùng `npm run dev` khi phát triển.

---

## 3. Chạy ứng dụng

```bash
# Cách 1 — server PHP tích hợp (mặc định cổng 8000)
php artisan serve
#  → mở http://localhost:8000

# Cách 2 — phát triển realtime (Vite hot-reload) chạy song song
npm run dev
php artisan serve
```

Trang đăng nhập nhân viên: **http://localhost:8000/login**

---

## 4. Tài khoản mẫu

| Tên đăng nhập | Mật khẩu   | Vai trò (`chuc_vu`) | Chi nhánh | Phạm vi |
|---|---|---|---|---|
| `superadmin`  | `Admin@123` | **admin** (chủ chuỗi) | mọi CN | Toàn quyền + đổi chi nhánh + quản lý tài khoản |
| `admin_8am`   | `Admin@123` | quan_ly | CN001 | Quản lý CN001 |
| `manager_hcm` | `Admin@123` | quan_ly | CN002 | Quản lý CN002 |
| `bartender01` | `Admin@123` | bartender | CN001 | Pha chế |
| `staff01`     | `Admin@123` | nhan_vien | CN001 | Phục vụ |

> Đăng nhập `superadmin` để thấy **bộ chọn chi nhánh** ở sidebar và menu
> **"Nhân viên & quyền"** (`/nhan-vien`) để tạo tài khoản, phân quyền, khoá tài khoản.

### Quy tắc phân quyền (trang `/nhan-vien`)
- **admin**: tạo/sửa tài khoản ở **mọi** chi nhánh, gán **mọi** vai trò, chuyển nhân viên giữa các chi nhánh.
- **quan_ly**: chỉ thao tác trong chi nhánh của mình, chỉ gán vai trò *Pha chế / Phục vụ*; không sửa được admin/quản lý.
- Không ai tự sửa/khoá tài khoản đang đăng nhập. Khoá (`inactive`) chặn đăng nhập ngay.

---

## 5. Sơ đồ bàn 3D & đa chi nhánh

- Map 3D nhúng trong menu khách (Three.js + GLTFLoader + Draco). File model ở
  `public/models/`, chọn theo cột `CHI_NHANH.model_3d`:
  - **CN001** → `cafe_opt.glb` (mô hình quán đầy đủ, bàn `BAN_B001…B017`)
  - **CN002** → `cafe_CN002.glb` (map giả lập, bàn `BAN_B101…B106`)
- Khách bấm vào bàn → xem trạng thái (trống / có khách / đặt trước / đang chọn) +
  số ghế + ảnh bàn, có thể đổi bàn (chuyển toàn bộ đơn sang bàn mới).
- Nhân viên upload ảnh từng bàn ở **Bàn & QR** (`/ban`).
- Tạo map giả lập cho chi nhánh mới (không cần Blender): `node build_cn002_map.mjs`.

---

## 6. Thay đổi Database trong phiên phát triển này

Migration **mới** (so với schema gốc `2026_05_27..._create_8am_coffee_mysql_schema`):

| Migration | Bảng | Thay đổi |
|---|---|---|
| `2026_06_01_000000_add_so_ghe_to_ban` | `BAN` | thêm cột `so_ghe` (số ghế mỗi bàn) |
| `2026_05_31_000006_allow_takeaway_orders_without_table` | `ORDERS` | `ma_ban` cho phép `NULL` (đơn mang đi) |
| `2026_06_01_000001_add_line_id_to_chi_tiet_order` | `CHI_TIET_ORDER` | thêm `line_id` (tách dòng món trùng khác topping) |
| `2026_06_02_000000_add_anh_to_ban` | `BAN` | thêm cột `anh` (ảnh bàn nhân viên tải lên) |
| `2026_06_03_000000_add_model_3d_to_chi_nhanh` | `CHI_NHANH` | thêm cột `model_3d` (file GLB map từng chi nhánh) |

Seeder mới/cập nhật (đều **idempotent** — chạy lại an toàn):
- `SuperAdminSeeder` — tài khoản `superadmin`.
- `ChiNhanh2DemoSeeder` — chi nhánh **CN002** + 6 bàn (`B101…B106`) + tài khoản `manager_hcm`.
- `BanSeeder` — 17 bàn + số ghế.

Tất cả đã nối vào `DatabaseSeeder`, nên chỉ cần:

```bash
php artisan migrate      # nếu DB cũ chưa có các cột trên
php artisan db:seed      # bổ sung superadmin + CN002 (không ghi đè dữ liệu cũ)
```

> Làm lại từ đầu hoàn toàn: `php artisan migrate:fresh --seed` (⚠️ xoá sạch dữ liệu).

---

## 7. Cấu trúc thư mục chính

```
app/Http/Controllers/
  NhanVienController.php    ← quản lý tài khoản & phân quyền (mới)
  ChiNhanhController.php    ← super-admin đổi chi nhánh (mới)
  BanController.php         ← sơ đồ bàn, đổi bàn, upload ảnh bàn
resources/js/
  showroom.js              ← viewer 3D trong menu khách
  floorplan.js             ← sơ đồ bàn cho nhân viên
resources/views/staff/
  nhanvien-list.blade.php  ← UI quản lý nhân viên (mới)
public/models/             ← các file .glb (map 3D)
build_cn002_map.mjs        ← script tạo map giả lập cho chi nhánh mới
```

---

## 8. Lệnh hữu ích

```bash
php artisan route:list            # liệt kê route
php artisan migrate:status        # trạng thái migration
php artisan optimize:clear        # xoá cache config/route/view
npm run build                     # build asset production
```
