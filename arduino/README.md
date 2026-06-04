# 8AM Coffee — Đầu đọc thẻ RFID (ESP32 + RC522)

Firmware Arduino IDE cho đầu đọc thẻ thành viên RFID, giao tiếp với hệ thống Laravel
qua REST API (`/api/arduino/*`). Sketch: [`8am_rfid_reader/8am_rfid_reader.ino`](8am_rfid_reader/8am_rfid_reader.ino).

> **Lưu ý phần cứng:** dùng **ESP32** (có WiFi). Arduino UNO/Nano **không có WiFi** nên không
> chạy được firmware này (nếu bắt buộc UNO thì cần thêm shield ESP8266/Ethernet — không khuyến nghị).

---

## 1. Phần cứng & đấu nối (RC522 ↔ ESP32)

| RC522 | ESP32 | Ghi chú |
|------|-------|---------|
| SDA (SS) | GPIO **21** | `SS_PIN` |
| SCK  | GPIO **18** | SPI clock |
| MOSI | GPIO **23** | |
| MISO | GPIO **19** | |
| RST  | GPIO **22** | `RST_PIN` |
| 3.3V | **3V3** | **KHÔNG cấp 5V** — RC522 chạy 3.3V |
| GND  | GND | |

(Tùy chọn) LED báo dùng LED onboard GPIO **2**; còi buzzer gán vào `BUZZER` nếu có.

---

## 2. Cài đặt Arduino IDE

1. **Thêm board ESP32:** File → Preferences → *Additional Boards Manager URLs* dán:
   `https://raw.githubusercontent.com/espressif/arduino-esp32/gh-pages/package_esp32_index.json`
   → Boards Manager → cài **esp32 (Espressif)** → chọn board **ESP32 Dev Module**.
2. **Thư viện** (Library Manager):
   - **MFRC522** (tác giả *GithubCommunity*)
   - **ArduinoJson** (tác giả *Benoit Blanchon*, **v7**)

---

## 3. Cấu hình firmware (sửa đầu file `.ino`)

```cpp
const char* WIFI_SSID = "TEN_WIFI";
const char* WIFI_PASS = "MAT_KHAU_WIFI";
const char* API_BASE  = "https://your-app.up.railway.app/api/arduino"; // KHÔNG có / cuối
const char* DEVICE_ID    = "ARD001";
const char* DEVICE_TOKEN = "ARD-DEMO-KEY-001";
```

- `API_BASE`: domain Railway (HTTPS) hoặc tunnel Cloudflare. Firmware dùng `setInsecure()` (bỏ
  kiểm tra chứng chỉ) cho tiện demo — **production nên pin root CA** và **đổi token**.
- `DEVICE_ID` / `DEVICE_TOKEN` phải khớp một bản ghi trong bảng `THIET_BI_ARDUINO`.

---

## 4. Đăng ký thiết bị trong hệ thống

Server **không lưu token thô** — chỉ lưu `api_key_hash = SHA-256(token)`. Tạo thiết bị:

**Cách A — đã chạy `database/arduino_rfid_loyalty.sql`:** sẵn có `ARD001` với token demo
`ARD-DEMO-KEY-001` (đúng giá trị mặc định trong firmware) → dùng được ngay.

**Cách B — DB chạy bằng `php artisan migrate` (chưa có thiết bị):** chèn 1 dòng (đổi chi nhánh nếu cần):
```sql
INSERT INTO THIET_BI_ARDUINO (ma_thiet_bi, ten_thiet_bi, ma_chi_nhanh, vi_tri, api_key_hash, trang_thai)
VALUES ('ARD001','Dau doc quay thu ngan','CN001','Quay thu ngan', SHA2('ARD-DEMO-KEY-001',256), 'hoat_dong');
```
Đổi token → tính lại `SHA2('<token-moi>',256)` và cập nhật `api_key_hash`.

---

## 5. API thiết bị (tham chiếu nhanh)

Mọi request gửi 2 header: `X-Device-Id`, `X-Device-Token`. Base: `/api/arduino`.

| Method & path | Body (JSON) | Trả về |
|---|---|---|
| `POST /quet` | `{ "uid": "04A1B2C3" }` | `{ found, ma_the, ten_kh, diem, gia_tri, hang_the }` (found=false nếu thẻ trắng) |
| `POST /phat-the` | `{ "uid", "sdt": "0901234567" }` | phát thẻ + điểm khởi tạo theo chi tiêu |
| `POST /tich-diem` | `{ "uid", "so_tien": 50000, "ma_order"? }` | `{ diem_cong, diem }` |
| `POST /doi-diem` | `{ "uid", "so_diem": 200, "tong_hoa_don"?, "ma_order"? }` | `{ tien_giam, diem }` |
| `POST /heartbeat` | – | `{ device, chi_nhanh, server_time }` |

Lỗi xác thực thiết bị → HTTP **401**; dữ liệu/nghiệp vụ không hợp lệ → **422**; thẻ chưa đăng ký → **404**.

---

## 6. Quy trình demo

1. Mở **Serial Monitor** ở **115200 baud**.
2. **Quẹt thẻ trắng** → firmware in `The TRANG (chua dang ky)`.
3. Gõ `P 0901234567` (SĐT của một khách **đã có lịch sử chi tiêu ≥ mốc**, mặc định 500.000đ)
   → server phát thẻ + cộng điểm khởi tạo (= tổng chi tiêu ÷ 1.000).
4. **Quẹt lại thẻ** → hiển thị tên khách + số điểm + hạng thẻ.
5. `T 50000` → tích điểm theo 50.000đ. `D 200` → đổi 200 điểm lấy giảm giá (1 điểm = 50đ).

> Tích điểm còn xảy ra **tự động** khi thu ngân thanh toán trên web (hook trong `PaymentService`),
> nên `/tich-diem` ở đây chủ yếu cho kịch bản quẹt thủ công tại quầy.

---

## 7. Bảo mật

- Token thiết bị chỉ nằm trong firmware + ở dạng **băm SHA-256** trong DB (lộ DB không lộ token).
- Đổi `DEVICE_TOKEN` định kỳ; cập nhật `api_key_hash` tương ứng.
- Production: thay `client.setInsecure()` bằng **pin root CA** (ISRG Root X1 cho Let's Encrypt /
  CA của Cloudflare) để chống giả mạo máy chủ.
- API đã có **rate limit** (120 req/phút/IP) và xác thực thiết bị bắt buộc.
