# 8AM Coffee — Tính năng nâng cao & Đối chiếu tiêu chí

Tài liệu mô tả các tính năng nâng cao đã bổ sung và cách chúng đáp ứng 4 tiêu chí
đánh giá. Kèm hướng dẫn cấu hình + demo.

---

## Đối chiếu tiêu chí

| Tiêu chí | Tính năng đáp ứng | Vị trí code |
|---|---|---|
| **1. Công nghệ AI / ML / IoT** | Dự báo doanh thu (hồi quy tuyến tính) + Gợi ý món mua kèm (luật kết hợp market-basket) | `app/Services/AnalyticsService.php`, trang `/phan-tich`, gợi ý ở giỏ khách |
| **2. UX nâng cao + API phức tạp** | Tích hợp cổng thanh toán **VNPay** (sandbox): tạo URL ký HMAC-SHA512, xử lý return + IPN, đối soát số tiền | `app/Services/VnpayService.php`, `PaymentController`, bảng `THANH_TOAN_ONLINE` |
| **3. Triển khai lên Host** | Docker + Railway (MySQL private network, HTTPS, migrate tự động) | `Dockerfile`, `docker/entrypoint.sh`, `railway.json`, `DEPLOY.md` |
| **4. An toàn thông tin** | Chống brute-force (rate-limit IP + khoá tài khoản), **xác thực 2 lớp OTP email**, **nhật ký đăng nhập** (audit log) | `AuthController`, `config/security.php`, bảng `NHAT_KY_DANG_NHAP`, trang `/nhat-ky-dang-nhap` |

---

## 1) AI — Dự báo & Gợi ý món

**Dự báo doanh thu** dùng hồi quy tuyến tính (least squares) trên doanh thu 30 ngày
gần nhất, dự báo 7 ngày tới, có hệ số R² đánh giá độ khớp. Hiển thị biểu đồ
lịch sử (nét liền) + dự báo (nét đứt) tại trang **Phân tích AI** (`/phan-tich`).

**Gợi ý món mua kèm** dùng luật kết hợp (market-basket): tính `support`,
`confidence`, `lift` cho từng cặp món từ dữ liệu đơn thật. Ứng dụng:
- Trang **Phân tích AI**: bảng "món hay đi cùng nhau".
- **Giỏ hàng khách** (`customer/checkout`): khối "Có thể bạn cũng thích" gợi ý
  món theo giỏ hiện tại; khi chưa đủ dữ liệu sẽ fallback sang món bán chạy.

> Tất cả tính trên dữ liệu thật trong DB — không có dữ liệu giả.

**Demo:** đăng nhập admin/superadmin → menu **Phân tích AI**. Để bảng gợi ý có dữ
liệu, cần vài đơn nhiều món (tạo đơn ở giao diện khách rồi thanh toán).

---

## 2) VNPay (sandbox)

**Luồng:** Trang thanh toán → nút *"Thanh toán online qua VNPay"* → tạo bản ghi
`THANH_TOAN_ONLINE` (trạng thái `cho_xu_ly`) → chuyển hướng sang cổng VNPay với
URL được ký **HMAC-SHA512** → VNPay xử lý → trả về `payment.vnpay.return`
(và gọi IPN `payment.vnpay.ipn` server-to-server) → **xác minh chữ ký + đối soát
số tiền** → nếu hợp lệ thì tạo hóa đơn (`phuong_thuc_tt = vnpay`) và đánh dấu đơn
hoàn thành. Xử lý **idempotent** (return + IPN không tạo hóa đơn 2 lần).

**Cấu hình** (`.env`):
```
VNPAY_TMN_CODE=xxxxxxxx
VNPAY_HASH_SECRET=xxxxxxxxxxxxxxxx
# URL/return giữ mặc định cho sandbox
```
Lấy TmnCode + HashSecret tại https://sandbox.vnpayment.vn (đăng ký merchant test).
Nếu chưa cấu hình, nút VNPay tự ẩn (vẫn thanh toán tiền mặt/CK bình thường).

**Thẻ test sandbox** (VNPay cung cấp): NH **NCB**, số thẻ `9704198526191432198`,
tên `NGUYEN VAN A`, ngày phát hành `07/15`, OTP `123456`.

**Demo:** tạo đơn → trang thanh toán → bấm *Thanh toán online qua VNPay* →
nhập thẻ test → quay về thấy "Đã thanh toán" + in hóa đơn.

---

## 3) Triển khai (xem `DEPLOY.md`)

Lưu ý quan trọng đã chốt khi deploy Railway:
- `DB_HOST=mysql.railway.internal`, `DB_PORT=3306`, `DB_DATABASE=railway` (bật Outbound IPv6).
- `SESSION_DRIVER=file` (project **không** có bảng `sessions`; dùng `database` sẽ lỗi).
- Sau lần seed đầu: đặt `RUN_SEED=false`, `APP_DEBUG=false`.

---

## 4) An toàn thông tin

**Chống brute-force**
- Giới hạn theo IP qua `RateLimiter` (`LOGIN_IP_RATE`/phút).
- Đếm số lần sai mật khẩu mỗi tài khoản; vượt `LOGIN_MAX_ATTEMPTS` → khoá
  `LOGIN_LOCKOUT_MINUTES` phút.

**Xác thực 2 lớp (2FA) qua email OTP**
- Bật bằng `TWO_FACTOR_ENABLED=true` (cần `MAIL_*` hoạt động và nhân viên có email).
- Sau khi đúng mật khẩu → gửi OTP 6 số (hết hạn `OTP_MINUTES`) tới email →
  trang `/otp` nhập mã. OTP lưu dạng **hash**, giới hạn số lần sai, có "Gửi lại mã".

**Nhật ký đăng nhập (audit log)** — bảng `NHAT_KY_DANG_NHAP`, trang
`/nhat-ky-dang-nhap` (chỉ superadmin): ghi đăng nhập, thất bại, khoá tài khoản,
gửi/xác thực OTP, đăng xuất — kèm IP, user-agent, thời gian.

**Checklist OWASP đã đáp ứng**
- A01 Phân quyền: middleware `auth.staff` + `role` (superadmin/admin/nhân viên), scope chi nhánh.
- A02 Mật khẩu băm `bcrypt`; OTP băm; cookie `Secure` (`SESSION_SECURE_COOKIE`); ép HTTPS.
- A03 Injection: dùng Eloquent/Query Builder (tham số hoá), Blade auto-escape chống XSS.
- A04 Khoá tài khoản + rate-limit chống dò mật khẩu.
- A05 Bí mật trong `.env` (không commit); `APP_DEBUG=false` ở production.
- A07 2FA, đăng xuất regenerate session, chống CSRF (token Laravel).
- A09 Ghi log truy cập (audit) phục vụ điều tra sự cố.

**Demo 2FA:** đặt `TWO_FACTOR_ENABLED=true` + cấu hình `MAIL_*` → đăng nhập →
nhận OTP qua email → nhập tại `/otp`. Xem nhật ký tại `/nhat-ky-dang-nhap`.

---

## 5) Xác thực email khi tạo tài khoản nhân viên + EMAIL_LOG

**Kiểm tra email tồn tại (validate-before-send)** — `EmailVerificationService`:
- Định dạng (whitelist ký tự, chống chèn mã).
- Bản ghi **MX** của tên miền (`checkdnsrr`, fallback A/AAAA) → domain không nhận thư thì từ chối tạo ngay.

**Kích hoạt qua email (xác nhận mailbox tồn tại & thuộc sở hữu):**
- Tạo tài khoản → trạng thái **`cho_xac_minh`** + token kích hoạt (hết hạn `ACCOUNT_ACTIVATION_HOURS`, mặc định 72h).
- Email gửi kèm **link kích hoạt** (`/kich-hoat/{token}`). Bấm link → `active`, mới đăng nhập được.
- Đăng nhập khi chưa kích hoạt → báo rõ "Tài khoản chưa kích hoạt".
- Nút **"Gửi lại email kích hoạt"** ở trang Nhân viên (sinh token + mật khẩu tạm mới).

**EMAIL_LOG** (bảng `EMAIL_LOG`, trang `/nhat-ky-email` cho superadmin): ghi mọi email
(credentials / kích hoạt / OTP) — loại, người nhận, tiêu đề, thành/bại, lỗi.

**Cronjob dọn tài khoản chưa kích hoạt:** lệnh `php artisan accounts:purge-unconfirmed`
(xoá `TAI_KHOAN` + `NHAN_VIEN` ở trạng thái `cho_xac_minh` quá `PURGE_UNCONFIRMED_DAYS`
ngày, mặc định 7) — đã lên lịch chạy hằng ngày 03:00 trong `routes/console.php`.
> Trên server cần bật scheduler: thêm cron `* * * * * php artisan schedule:run` (hoặc chạy thủ công lệnh trên).

## 6) Chống SQL Injection (và XSS, theo OWASP)

- **Parameterized queries**: toàn bộ truy vấn dùng Eloquent / Query Builder (bind `?`),
  `whereRaw(... = ?, [$value])`. Không nối chuỗi input người dùng vào SQL.
- **Whitelist validation** đầu vào tạo nhân viên: regex cho họ tên / email / SĐT
  (từ chối `' " ; < > &`…). Định danh bảng/cột trong sinh mã được guard bằng regex.
- **Output encoding**: Blade `{{ }}` tự động escape HTML → chống XSS khi hiển thị
  email/tên trong trang quản trị.
- **Least privilege** (khuyến nghị triển khai): tài khoản DB của ứng dụng chỉ cấp
  `SELECT/INSERT/UPDATE/DELETE` trên schema cần thiết, không cấp `DROP`.

**Cấu hình thêm (`.env`):**
```
ACCOUNT_ACTIVATION_HOURS=72
PURGE_UNCONFIRMED_DAYS=7
```
