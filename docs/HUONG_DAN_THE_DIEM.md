# Hướng dẫn sử dụng Thẻ thành viên & Điểm — từ đầu

Tính năng gồm 3 phần: **quản trị thẻ/điểm** (web), **đổi điểm ở POS** (màn thanh toán),
và **đầu đọc Arduino** (tùy chọn). Dưới đây là quy trình dùng từ con số 0.

---

## 0. Tổng quan cơ chế

- Khách chi tiêu đạt **mốc** (mặc định **500.000đ** tích lũy) → được **phát thẻ RFID**.
- **Điểm khởi tạo** = tổng chi tiêu ÷ 1.000 (vd 750.000đ → 750 điểm).
- Mỗi hóa đơn sau đó **tự tích điểm** (1.000đ = 1 điểm).
- Khách **đổi điểm lấy giảm giá**: 1 điểm = 50đ (tối thiểu 100 điểm, tối đa giảm 50% hóa đơn).
- Số dư denormalize ở thẻ; **nguồn sự thật** là sổ cái `GIAO_DICH_DIEM` (số dư = tổng `so_diem`).

Cấu hình tỉ lệ ở `config/loyalty.php` (chỉnh nhanh qua `.env`): `LOYALTY_ISSUE_THRESHOLD`,
`LOYALTY_EARN_PER_AMOUNT`, `LOYALTY_POINT_VALUE`, `LOYALTY_MIN_REDEEM`, `LOYALTY_MAX_REDEEM_PCT`.

---

## 1. Cập nhật cơ sở dữ liệu

```bash
php artisan migrate
```
Tạo 4 bảng (`THE_THANH_VIEN`, `GIAO_DICH_DIEM`, `THIET_BI_ARDUINO`, `LICH_SU_QUET_THE`) và
thêm cột vào `HOA_DON` (`ma_the`, `diem_su_dung`, `giam_gia_diem`).

> Dùng DB Arduino riêng? Import thêm `database/arduino_rfid_loyalty.sql`.

---

## 2. (Tùy chọn) Đăng ký đầu đọc Arduino

Chỉ cần nếu dùng phần cứng ESP32. Xem `arduino/README.md`. Đăng ký nhanh 1 đầu đọc:
```sql
INSERT INTO THIET_BI_ARDUINO (ma_thiet_bi, ten_thiet_bi, ma_chi_nhanh, vi_tri, api_key_hash, trang_thai)
VALUES ('ARD001','Dau doc quay','CN001','Quay thu ngan', SHA2('ARD-DEMO-KEY-001',256), 'hoat_dong');
```

---

## 3. Phát thẻ cho khách (3 cách)

Điều kiện: khách đã có **lịch sử chi tiêu ≥ mốc**. Hệ thống tra khách theo SĐT (qua blind index).

**Cách 1 — Màn quản trị (khuyến nghị):** menu **Thẻ thành viên** → khung *Phát thẻ mới* →
nhập **UID thẻ** + **SĐT khách** → *Phát thẻ*.

**Cách 2 — Dòng lệnh:**
```bash
php artisan loyalty:issue-card 04A1B2C3 0901234567 --branch=CN001
```

**Cách 3 — Đầu đọc Arduino:** quẹt thẻ trắng → ở Serial gõ `P 0901234567`.

Kết quả: thẻ gắn với khách, cộng điểm khởi tạo, ghi sổ cái `khoi_tao`.

---

## 4. Tích điểm

- **Tự động:** mỗi khi thu ngân **thanh toán** đơn của khách có thẻ đang hoạt động,
  hệ thống cộng `floor(tiền_thực_thu / 1.000)` điểm (hook trong `PaymentService`).
- **Thủ công (Arduino):** quẹt thẻ → `T 50000` (tích theo 50.000đ).

---

## 5. Đổi điểm ở POS (màn Thanh toán)

1. Mở **Đơn hàng** → chọn đơn cần thu → **Thanh toán**.
2. Ở khung **“Thẻ thành viên / Đổi điểm”**: nhập **UID thẻ hoặc SĐT** → bấm **Tra cứu**.
   - Hiện tên khách + số dư điểm + hạng thẻ.
3. Nhập **số điểm muốn đổi** (hoặc bấm **Đổi tối đa**). Dòng **“Giảm: …đ”** hiển thị số tiền giảm.
   - Hệ thống tự chặn: tối thiểu 100 điểm, không quá số dư, không quá 50% hóa đơn.
4. Chọn **chiết khấu (%)** và **phương thức** như thường → **Xác nhận thanh toán**.
   - Hóa đơn ghi `giam_gia_diem`, trừ điểm vào sổ cái, và **vẫn tích điểm** trên số tiền thực thu.
5. Muốn bỏ áp dụng thẻ: bấm **“Bỏ áp dụng thẻ”**.

> Thứ tự tính tiền: `Tổng → trừ chiết khấu % → trừ tiền đổi điểm = Số thực thu`.

---

## 6. Quản trị thẻ & điểm (menu **Thẻ thành viên**)

- **Danh sách:** xem mọi thẻ, số dư, hạng, trạng thái; lọc theo trạng thái; tìm theo UID/mã thẻ/SĐT.
- **Chi tiết thẻ** (nút *Xem*):
  - **Trạng thái:** *Hoạt động / Khóa / Báo mất* (khóa thẻ mất để chặn dùng).
  - **Điều chỉnh điểm:** cộng/trừ thủ công kèm **lý do** (ghi sổ cái `dieu_chinh`).
  - **Lịch sử điểm:** toàn bộ biến động (khởi tạo/tích/đổi/điều chỉnh) + liên kết hóa đơn.
  - Cảnh báo nếu số dư thẻ lệch sổ cái (đối soát).

---

## 7. Phân quyền & bảo mật

- Màn quản trị + tra cứu thẻ: chỉ **superadmin/admin**.
- PII không lộ: tra khách qua **blind index** (không giải mã SĐT); tên chỉ giải mã để hiển thị.
- Mọi thao tác điểm đều ghi **sổ cái bất biến** + khóa giao dịch (`lockForUpdate`) chống đua.

---

## 8. Kiểm thử nhanh (smoke test)

```bash
# 1) tạo bảng
php artisan migrate
# 2) phát thẻ cho 1 khách đã chi tiêu nhiều
php artisan loyalty:issue-card 04A1B2C3 <sdt_khach_co_lich_su>
# 3) vào web: Thẻ thành viên → thấy thẻ + điểm
# 4) tạo đơn cho đúng khách đó → Thanh toán → Tra cứu thẻ → Đổi điểm → xác nhận
# 5) mở lại chi tiết thẻ → sổ cái có dòng 'doi_diem' và 'tich_diem'
```
