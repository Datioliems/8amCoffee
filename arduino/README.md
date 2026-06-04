# 8AM Coffee — Đầu đọc thẻ RFID

Đầu đọc thẻ thành viên RFID, giao tiếp với hệ thống Laravel qua REST API (`/api/arduino/*`).

**Hai phương án phần cứng:**

| | A. Arduino UNO + LCD 1602 (I2C) | B. ESP32 |
|---|---|---|
| Mạng | UNO **không có WiFi** → cần **bridge** chạy trên PC (qua USB) | ESP32 **tự gọi API** qua WiFi |
| Sketch | [`8am_uno_rfid_lcd/`](8am_uno_rfid_lcd/8am_uno_rfid_lcd.ino) | [`8am_rfid_reader/`](8am_rfid_reader/8am_rfid_reader.ino) |
| Bridge | [`bridge/`](bridge/) (Node) — **bắt buộc** | không cần |

> Bạn đang dùng **Phương án A (UNO + LCD)** → đọc mục **A** ngay dưới. Mục 1–7 là cho ESP32.

---

## A. Arduino UNO + LCD 1602 I2C (qua bridge PC)

UNO không lên mạng được, nên: **UNO đọc thẻ + hiện LCD + gửi UID qua USB** → **bridge** trên PC
gọi API → trả kết quả về LCD.

### A.1 Đấu nối (đúng phần cứng đang dùng)

**RC522 (SPI) ↔ UNO**

| RC522 | UNO |
|------|-----|
| RST  | D9 |
| SDA (SS) | D10 |
| MOSI | D11 |
| MISO | D12 |
| SCK  | D13 |
| 3.3V | 3.3V (**không cấp 5V**) |
| GND  | GND |

**LCD 1602A V2.0 (I2C) ↔ UNO**

| LCD | UNO |
|-----|-----|
| GND | GND |
| VCC | 5V |
| SDA | A4 |
| SCL | A5 |

> Chân SPI của UNO ở mức 5V còn RC522 danh định 3.3V — thường vẫn chạy; dùng level shifter sẽ bền hơn.

### A.2 Nạp firmware (Arduino IDE)

1. Board: **Arduino Uno**. Thư viện (Library Manager): **MFRC522** (GithubCommunity) và
   **LiquidCrystal I2C** (Frank de Brabander).
2. Mở [`8am_uno_rfid_lcd/8am_uno_rfid_lcd.ino`](8am_uno_rfid_lcd/8am_uno_rfid_lcd.ino) → Upload.
3. Nếu LCD sáng nền nhưng **không hiện chữ** → đổi địa chỉ I2C trong sketch từ `0x27` sang `0x3F`
   (hoặc nạp sketch "I2C Scanner" để tìm địa chỉ đúng).

### A.3 Chạy bridge trên PC

```bash
cd arduino/bridge
npm install
# Windows (đổi COM3 thành cổng UNO trong Device Manager; API_BASE trỏ tới app):
set PORT=COM3 && set API_BASE=http://localhost:8000/api/arduino && npm start
```
Biến cấu hình: `PORT` (cổng COM), `BAUD` (mặc định 9600), `API_BASE`, `DEVICE_ID`, `DEVICE_TOKEN`
(khớp bản ghi `THIET_BI_ARDUINO`; xem mục **4** để đăng ký thiết bị).

**Thao tác:**
- **Quẹt thẻ** → bridge tự gọi `/quet`, hiện **tên khách + điểm** lên LCD và in ra console.
- Gõ ở cửa sổ bridge (trên thẻ vừa quẹt): `P 0901234567` (phát thẻ), `T 50000` (tích), `D 200` (đổi).

### A.4 Giao thức Serial (UNO ↔ bridge), 9600 baud

| Chiều | Bản tin | Ý nghĩa |
|---|---|---|
| UNO → PC | `READY` | đầu đọc khởi động xong |
| UNO → PC | `UID:04A1B2C3` | vừa quẹt thẻ |
| PC → UNO | `L1:<text>` / `L2:<text>` | ghi dòng 1 / dòng 2 LCD (≤16 ký tự, không dấu) |

> Việc **đổi điểm chính** vẫn nên làm ở **POS web** (màn Thanh toán → khung "Thẻ thành viên/Đổi điểm").
> Đầu đọc UNO chủ yếu để **tra nhanh số dư** tại quầy và thao tác phụ qua bridge.

### A.5 LCD không hiện chữ? (khắc phục theo thứ tự)

1. **Dò địa chỉ:** nạp [`i2c_scanner/`](i2c_scanner/i2c_scanner.ino) → mở Serial Monitor (9600).
   Nó in `0x27` hoặc `0x3F` → điền địa chỉ đó vào sketch (`LiquidCrystal_I2C lcd(0xXX, 16, 2);`).
   Nếu **không thấy thiết bị nào** → lỗi dây: kiểm tra **SDA→A4, SCL→A5, VCC→5V, GND→GND** và mối hàn.
2. **Vặn biến trở tương phản:** trên lưng LCD có **chiết áp xanh** — vặn từ từ. Sai contrast khiến
   **nền sáng mà không thấy chữ** (đây là nguyên nhân phổ biến nhất).
3. **Test riêng LCD:** nạp [`lcd_test/`](lcd_test/lcd_test.ino) (chỉ in "8AM Coffee / LCD OK!") để
   tách LCD khỏi phần RFID.
4. **Nguồn:** I2C backpack cần **5V** (cấp 3.3V thường yếu → mờ/không hiện). VCC phải vào **5V**, không phải 3.3V.
5. **Sai hàm thư viện:** nếu IDE báo lỗi `'init' was not declared` → đổi `lcd.init();` thành `lcd.begin();`
   (có 2–3 thư viện trùng tên "LiquidCrystal I2C"; cài bản của *Frank de Brabander*).
6. **Jumper đèn nền:** đảm bảo còn jumper backlight trên backpack (nếu nền không sáng).

---

## 1. (ESP32) Phần cứng & đấu nối (RC522 ↔ ESP32)

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
