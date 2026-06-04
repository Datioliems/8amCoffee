# Thẻ thành viên RFID + Tích điểm (Arduino) — 8AM Coffee

Nhánh: `Phieu_Datio_Arduino`. Tài liệu này mô tả **lý do nghiệp vụ**, **cơ chế**, **lược đồ
dữ liệu** và **cách triển khai/đồng bộ** của module thẻ RFID + tích điểm tích hợp đầu đọc Arduino.

---

## 1. Vì sao nên dùng thẻ RFID (lý do thuyết phục)

Thẻ thành viên RFID không chỉ là "thẻ tích điểm" — nó là điểm chạm phần cứng giúp dự án
nổi bật và tạo giá trị đo lường được:

1. **Nhận diện < 1 giây tại quầy.** Khách chỉ cần *chạm thẻ*; nhanh hơn nhiều so với gõ/tra
   số điện thoại (vốn đang **mã hóa**, tra cứu phải qua blind index). Tăng thông lượng quầy
   trong giờ cao điểm.
2. **Tái sử dụng nhiều lần, bền vật lý.** Một thẻ gắn với một khách, dùng đi dùng lại cho mọi
   lần ghé quán — đúng yêu cầu "thẻ phải dùng được nhiều lần".
3. **Hiện diện thương hiệu trong ví khách** → nhắc nhớ, thúc đẩy quay lại (repeat visit) và
   tăng **giá trị vòng đời khách hàng (CLV)**.
4. **Thưởng ngay khi phát thẻ.** Điểm khởi tạo tính theo **tổng chi tiêu trước đó** của khách →
   khách thấy được tưởng thưởng tức thì cho sự trung thành đã có → tỉ lệ kích hoạt cao.
5. **Hoạt động kể cả khi khách không có điện thoại/app/mạng.** UID thẻ là đủ để nhận diện.
6. **Dữ liệu first-party có cấu trúc** về khách quen (tần suất, giỏ hàng) → bơm thẳng vào
   **module Phân tích (AI)** đã có (market-basket, dự báo doanh thu) để cá nhân hóa khuyến mãi.
7. **Tôn trọng quyền riêng tư.** UID thẻ là *mã giả danh* (pseudonymous token); PII (tên, SĐT)
   vẫn mã hóa ở bảng gốc, hệ thống điểm không phơi bày số điện thoại.
8. **Điểm cộng về kỹ thuật:** đây là tích hợp **IoT (Arduino) + cơ sở dữ liệu + loyalty** hoàn
   chỉnh, demo được phần cứng↔phần mềm — lợi thế cạnh tranh rõ rệt trong bối cảnh chấm điểm/thi.

---

## 2. Luật nghiệp vụ (cấu hình ở `config/loyalty.php`)

| Tham số | Mặc định | Ý nghĩa |
|---|---|---|
| `issue_threshold` | **500.000đ** | Tổng chi tiêu tích lũy để khách **đủ điều kiện được phát thẻ** |
| `earn_per_amount` | **1.000đ = 1 điểm** | Tỉ lệ tích điểm theo chi tiêu |
| `point_value` | **1 điểm = 50đ** | Giá trị khi đổi điểm lấy giảm giá |
| `min_redeem` | **100 điểm** | Số điểm tối thiểu mỗi lần đổi |
| `max_redeem_pct` | **50%** | Trần giảm giá trên mỗi hóa đơn khi đổi điểm |
| `tiers` | thuong/bac/vang/kim_cuong | Xếp hạng theo **tổng điểm tích lũy** |

**Giá trị hoàn lại thực tế** ≈ `earn × point_value` = (1/1.000) × 50 = **~5% chi tiêu** — mức
hấp dẫn nhưng bền vững cho quán cà phê. Mọi tham số chỉnh được qua `.env` (vd
`LOYALTY_ISSUE_THRESHOLD=300000`) không cần sửa code.

> Ví dụ: khách đã chi **750.000đ** → đủ điều kiện (≥ 500k) → phát thẻ + **750 điểm** khởi tạo.
> Sau này tích thêm; khi đổi **200 điểm** → giảm **10.000đ** (không quá 50% hóa đơn).

---

## 3. Lược đồ dữ liệu (4 bảng)

```
KHACH_HANG ──1:N── THE_THANH_VIEN ──1:N── GIAO_DICH_DIEM
                         │                      │
CHI_NHANH ──1:N─────────┤              ORDERS / HOA_DON (tham chiếu)
                         │
THIET_BI_ARDUINO ──1:N── LICH_SU_QUET_THE
```

- **`THIET_BI_ARDUINO`** — đầu đọc RFID (Arduino/ESP) theo chi nhánh; `api_key_hash` = SHA-256
  của token thiết bị (xác thực khi gọi API).
- **`THE_THANH_VIEN`** — thẻ RFID: `uid_rfid` (UID vật lý, **unique**), `ma_kh` (chủ thẻ; NULL =
  thẻ trắng), `diem_hien_tai` (số dư denormalize), `tong_diem_tich_luy` (để xếp hạng), `hang_the`,
  `trang_thai` (hoat_dong/khoa/mat).
- **`GIAO_DICH_DIEM`** — **sổ cái điểm** (nguồn sự thật): `so_diem` có dấu (`+`tích / `-`đổi),
  `loai` ∈ {khoi_tao, tich_diem, doi_diem, dieu_chinh, het_han}. **Số dư = `SUM(so_diem)`**.
- **`LICH_SU_QUET_THE`** — log mọi lần quẹt từ Arduino (kể cả thẻ lạ) để audit/debug.

Không bảng nào lưu PII trực tiếp; danh tính tham chiếu qua `KHACH_HANG` (tên/SĐT vẫn mã hóa).

---

## 4. Các luồng chính

### 4.1. Phát thẻ (điểm khởi tạo từ chi tiêu)
```
Nhân viên quẹt THẺ TRẮNG + nhập SĐT khách
   → LoyaltyService::issueCard(uid, sdt)
       → tìm khách qua blind index: KHACH_HANG.sdt_hash = HMAC-SHA256(sdt)   (không giải mã)
       → spend = SUM(HOA_DON.tong_tien_sau_ck WHERE ma_kh)
       → nếu spend ≥ issue_threshold:
            điểm khởi tạo = floor(spend / earn_per_amount)
            gán uid→ma_kh, set số dư, ghi GIAO_DICH_DIEM(loai='khoi_tao')
```
CLI: `php artisan loyalty:issue-card 04A1B2C3 0901234567 --branch=CN001 --device=ARD001`

### 4.2. Tích điểm khi thanh toán (đã nối sẵn)
`PaymentService::createInvoice()` sau khi tạo hóa đơn sẽ gọi
`LoyaltyService::earnForCustomer($order->ma_kh, $tongSau, ...)` (bọc try/catch — lỗi loyalty
không làm hỏng thanh toán). Nếu khách có thẻ đang hoạt động → cộng `floor(tongSau/1.000)` điểm.

### 4.3. Đổi điểm lấy giảm giá
`LoyaltyService::redeem($card, $soDiem, $tongHoaDon)` — kiểm tra ngưỡng tối thiểu, số dư, và
trần `max_redeem_pct`; trả về **số tiền giảm** = `soDiem × point_value`; ghi sổ cái `doi_diem`.

---

## 5. Kiến trúc Arduino ↔ Hệ thống (REST API — đã hiện thực)

Phần cứng đề xuất: **ESP32 + đầu đọc RC522 (RFID 13.56MHz)**. Vì RC522 dùng SPI và cần Wi-Fi để
gọi HTTPS, **ESP32** là lựa chọn thực tế nhất.

```
[Thẻ RFID] --tap--> [ESP32 + RC522] --HTTPS POST {uid} + header thiết bị--> [Laravel API]
     → middleware 'arduino.device' xác thực (sha256(token) == api_key_hash)
     → tra THE_THANH_VIEN theo uid → trả tên khách (giải mã), số dư điểm, hạng thẻ
     → tích/đổi điểm theo thao tác tại quầy → ghi GIAO_DICH_DIEM + LICH_SU_QUET_THE
```

Endpoint (đã làm — nhóm route `/api/arduino`, header `X-Device-Id` + `X-Device-Token`):
| Path | Chức năng |
|---|---|
| `POST /api/arduino/quet` | Nhận diện thẻ (tên khách, điểm, hạng) |
| `POST /api/arduino/phat-the` | Phát thẻ + điểm khởi tạo theo chi tiêu |
| `POST /api/arduino/tich-diem` | Tích điểm thủ công |
| `POST /api/arduino/doi-diem` | Đổi điểm lấy giảm giá |
| `POST /api/arduino/heartbeat` | Báo sống / đồng bộ giờ |

Mã nguồn: controller `app/Http/Controllers/Api/ArduinoController.php`, middleware
`app/Http/Middleware/ArduinoDeviceAuth.php`, routes `routes/api.php`. **Firmware ESP32** +
hướng dẫn đấu nối: thư mục **`arduino/`** (`arduino/8am_rfid_reader/8am_rfid_reader.ino`,
`arduino/README.md`).

---

## 6. Triển khai & đồng bộ DB

**Cách 1 — Laravel migration (khuyến nghị, đồng bộ schema mới nhất):**
```
php artisan migrate        # chạy 2026_06_13_000000_create_arduino_rfid_loyalty_tables.php
```
Migration tự tạo 4 bảng + khóa ngoại; chạy được trên mọi môi trường (local/Railway) và đưa DB
lên đúng schema mới nhất *kèm* phần Arduino.

**Cách 2 — Import SQL vào DB Arduino riêng:**
```
mysql -u <user> -p 8amcoffee_arduino < database/arduino_rfid_loyalty.sql
```
File này `CREATE TABLE IF NOT EXISTS` + seed 1 đầu đọc demo (`ARD001`) và 3 thẻ trắng để test.
**Lưu ý:** DB đích phải có sẵn các bảng gốc `CHI_NHANH, KHACH_HANG, ORDERS, HOA_DON` và nên ở
schema mới nhất (chạy `php artisan migrate` trước nếu DB còn cũ).

---

## 7. Bảo mật & toàn vẹn

- **PII:** module không thêm cột PII mới; tra khách qua **blind index** `sdt_hash` (không giải
  mã SĐT để tìm). Tên khách chỉ giải mã khi cần *hiển thị*.
- **Chống lạm dụng:** đổi điểm có ngưỡng tối thiểu + trần %/hóa đơn; mọi biến động ghi **sổ cái**
  bất biến (`GIAO_DICH_DIEM`) → đối soát `diem_hien_tai` với `SUM(so_diem)` bất cứ lúc nào.
- **Thiết bị:** token đầu đọc lưu dạng **băm SHA-256** (`api_key_hash`), token thật chỉ nằm ở
  thiết bị — lộ DB không lộ token.
- **Khóa giao dịch:** phát/tích/đổi điểm bọc `DB::transaction` + `lockForUpdate` chống đua (race).
```
