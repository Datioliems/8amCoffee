# 8AM Coffee — Tài liệu Cơ sở dữ liệu (Data Dictionary)

> Sinh tự động từ DB `8amcoffee`. Tổng **30 bảng**, **28 khóa ngoại**.
> 🔐 = cột mã hóa (AES-256, Eloquent `encrypted` cast) · 🔑 = blind index (HMAC) tra cứu.

## Mục lục
- [`ban`](#ban) — Bàn + trạng thái + QR
- [`chi_nhanh`](#chi-nhanh) — Chi nhánh quán
- [`chi_tiet_kiem_ke`](#chi-tiet-kiem-ke) — Chi tiết kiểm kê
- [`chi_tiet_nhap_kho`](#chi-tiet-nhap-kho) — Chi tiết nhập kho
- [`chi_tiet_order`](#chi-tiet-order) — Dòng món trong đơn
- [`chi_tiet_order_option`](#chi-tiet-order-option) — Tùy chọn theo dòng món
- [`danh_muc`](#danh-muc) — Danh mục món
- [`dinh_muc`](#dinh-muc) — Định mức nguyên liệu/món
- [`email_log`](#email-log) — Nhật ký email đã gửi
- [`hoa_don`](#hoa-don) — Hóa đơn thanh toán
- [`khach_hang`](#khach-hang) — Khách hàng có giao dịch (PII mã hóa + blind index)
- [`migrations`](#migrations) — (hệ thống) lịch sử migration
- [`mon`](#mon) — Món/sản phẩm
- [`mon_option`](#mon-option) — Tùy chọn món (size/đá/đường/topping)
- [`nguyen_lieu`](#nguyen-lieu) — Nguyên liệu kho
- [`nha_cung_cap`](#nha-cung-cap) — Nhà cung cấp (liên hệ mã hóa)
- [`nhan_vien`](#nhan-vien) — Hồ sơ nhân viên (PII mã hóa)
- [`nhat_ky_dang_nhap`](#nhat-ky-dang-nhap) — Nhật ký đăng nhập (audit)
- [`order_logs`](#order-logs) — 
- [`orders`](#orders) — Đơn hàng (PII khách tạm mã hóa, hình thức tại bàn/mang về)
- [`phieu_kiem_ke`](#phieu-kiem-ke) — Phiếu kiểm kê
- [`phieu_nhap_kho`](#phieu-nhap-kho) — Phiếu nhập kho
- [`scan_log`](#scan-log) — Nhật ký quét QR
- [`tai_khoan`](#tai-khoan) — Tài khoản đăng nhập (NV/quản lý) + bảo mật/2FA/kích hoạt
- [`thanh_toan_online`](#thanh-toan-online) — Giao dịch VNPay (đối soát)
- [`ton_kho`](#ton-kho) — Tồn kho theo chi nhánh
- [`topping`](#topping) — Topping
- [`vw_menu_hien_thi`](#vw-menu-hien-thi) — 
- [`vw_order_dashboard`](#vw-order-dashboard) — 
- [`vw_ton_kho_tong_quan`](#vw-ton-kho-tong-quan) — 

---

## `ban`
Bàn + trạng thái + QR

| Cột | Kiểu | Null | Khóa | Ghi chú |
|---|---|---|---|---|
| `ma_ban` | varchar(10) |  | PK |  |
| `so_ban` | int(10) unsigned |  |  |  |
| `so_ghe` | tinyint(3) unsigned |  |  |  |
| `anh` | varchar(255) | ✓ |  |  |
| `vi_tri` | varchar(50) | ✓ |  |  |
| `trang_thai` | varchar(10) |  |  |  |
| `ma_chi_nhanh` | varchar(10) |  | FK→chi_nhanh.ma_chi_nhanh |  |

## `chi_nhanh`
Chi nhánh quán

| Cột | Kiểu | Null | Khóa | Ghi chú |
|---|---|---|---|---|
| `ma_chi_nhanh` | varchar(10) |  | PK |  |
| `ten_chi_nhanh` | varchar(100) |  | UQ |  |
| `dia_chi` | varchar(255) | ✓ |  |  |
| `sdt` | varchar(15) | ✓ |  |  |
| `model_3d` | varchar(120) | ✓ |  |  |

## `chi_tiet_kiem_ke`
Chi tiết kiểm kê

| Cột | Kiểu | Null | Khóa | Ghi chú |
|---|---|---|---|---|
| `ma_pkk` | varchar(20) |  | PK, FK→phieu_kiem_ke.ma_pkk |  |
| `ma_nl` | varchar(10) |  | PK, FK→nguyen_lieu.ma_nl |  |
| `sl_he_thong` | decimal(12,2) |  |  |  |
| `sl_thuc_te` | decimal(12,2) |  |  |  |
| `chenh_lech` | decimal(12,2) | ✓ |  | STORED GENERATED |
| `don_gia_tb` | decimal(12,0) | ✓ |  |  |

## `chi_tiet_nhap_kho`
Chi tiết nhập kho

| Cột | Kiểu | Null | Khóa | Ghi chú |
|---|---|---|---|---|
| `ma_pnk` | varchar(20) |  | PK, FK→phieu_nhap_kho.ma_pnk |  |
| `ma_nl` | varchar(10) |  | PK, FK→nguyen_lieu.ma_nl |  |
| `so_luong` | decimal(12,2) |  |  |  |
| `don_gia` | decimal(12,0) |  |  |  |
| `tong_tien` | decimal(15,0) | ✓ |  | STORED GENERATED |

## `chi_tiet_order`
Dòng món trong đơn

| Cột | Kiểu | Null | Khóa | Ghi chú |
|---|---|---|---|---|
| `id` | bigint(20) unsigned |  | PK | auto_increment |
| `ma_order` | varchar(20) |  | FK→orders.ma_order |  |
| `ma_mon` | varchar(10) |  | FK→mon.ma_mon |  |
| `so_luong` | int(10) unsigned |  |  |  |
| `don_gia_tai_thoi_diem` | decimal(12,0) |  |  |  |
| `ghi_chu` | varchar(200) | ✓ |  |  |

## `chi_tiet_order_option`
Tùy chọn theo dòng món

| Cột | Kiểu | Null | Khóa | Ghi chú |
|---|---|---|---|---|
| `id` | bigint(20) unsigned |  | PK | auto_increment |
| `chi_tiet_id` | bigint(20) unsigned |  | FK→chi_tiet_order.id |  |
| `ma_order` | varchar(20) |  |  |  |
| `ma_mon` | varchar(10) |  |  |  |
| `loai_option` | varchar(30) |  |  |  |
| `ten_lua_chon` | varchar(100) |  |  |  |
| `gia_them` | decimal(12,0) |  |  |  |

## `danh_muc`
Danh mục món

| Cột | Kiểu | Null | Khóa | Ghi chú |
|---|---|---|---|---|
| `ma_danh_muc` | varchar(10) |  | PK |  |
| `ten_danh_muc` | varchar(100) |  | UQ |  |

## `dinh_muc`
Định mức nguyên liệu/món

| Cột | Kiểu | Null | Khóa | Ghi chú |
|---|---|---|---|---|
| `ma_mon` | varchar(10) |  | PK, FK→mon.ma_mon |  |
| `ma_nl` | varchar(10) |  | PK, FK→nguyen_lieu.ma_nl |  |
| `so_luong_dung` | decimal(10,2) |  |  |  |
| `mo_ta` | varchar(200) | ✓ |  |  |

## `email_log`
Nhật ký email đã gửi

| Cột | Kiểu | Null | Khóa | Ghi chú |
|---|---|---|---|---|
| `id` | bigint(20) unsigned |  | PK | auto_increment |
| `loai` | varchar(30) |  |  |  |
| `email` | varchar(150) |  |  |  |
| `tieu_de` | varchar(200) | ✓ |  |  |
| `ma_tham_chieu` | varchar(30) | ✓ |  |  |
| `trang_thai` | varchar(20) |  |  |  |
| `loi` | varchar(500) | ✓ |  |  |
| `thoi_gian` | datetime |  |  |  |

## `hoa_don`
Hóa đơn thanh toán

| Cột | Kiểu | Null | Khóa | Ghi chú |
|---|---|---|---|---|
| `ma_hoa_don` | varchar(20) |  | PK |  |
| `ma_order` | varchar(20) |  | FK→orders.ma_order, UQ |  |
| `ma_kh` | varchar(10) | ✓ | FK→khach_hang.ma_kh |  |
| `thoi_gian_lap` | datetime |  |  |  |
| `tong_tien_truoc_ck` | decimal(12,0) |  |  |  |
| `chiet_khau` | decimal(5,2) |  |  |  |
| `tong_tien_sau_ck` | decimal(12,0) |  |  |  |
| `phuong_thuc_tt` | varchar(20) |  |  |  |
| `trang_thai` | varchar(15) |  |  |  |
| `ma_nv_thu_ngan` | varchar(10) | ✓ | FK→nhan_vien.ma_nv |  |

## `khach_hang`
Khách hàng có giao dịch (PII mã hóa + blind index)

| Cột | Kiểu | Null | Khóa | Ghi chú |
|---|---|---|---|---|
| `ma_kh` | varchar(10) |  | PK |  |
| `ten_kh` | text |  |  | 🔐 mã hóa |
| `sdt` | text | ✓ |  | 🔐 mã hóa |
| `sdt_hash` | char(64) | ✓ |  | 🔑 blind index |
| `ngay_tao` | datetime |  |  |  |

## `migrations`
(hệ thống) lịch sử migration

| Cột | Kiểu | Null | Khóa | Ghi chú |
|---|---|---|---|---|
| `id` | int(10) unsigned |  | PK | auto_increment |
| `migration` | varchar(255) |  |  |  |
| `batch` | int(11) |  |  |  |

## `mon`
Món/sản phẩm

| Cột | Kiểu | Null | Khóa | Ghi chú |
|---|---|---|---|---|
| `ma_mon` | varchar(10) |  | PK |  |
| `ten_mon` | varchar(100) |  |  |  |
| `don_gia` | decimal(12,0) |  |  |  |
| `mo_ta` | varchar(500) | ✓ |  |  |
| `hinh_anh` | varchar(255) | ✓ |  |  |
| `ma_danh_muc` | varchar(10) |  | FK→danh_muc.ma_danh_muc |  |
| `trang_thai` | varchar(10) |  |  |  |

## `mon_option`
Tùy chọn món (size/đá/đường/topping)

| Cột | Kiểu | Null | Khóa | Ghi chú |
|---|---|---|---|---|
| `ma_option` | varchar(10) |  | PK |  |
| `ma_mon` | varchar(10) | ✓ | FK→mon.ma_mon |  |
| `loai_option` | varchar(30) |  |  |  |
| `ten_option` | varchar(100) |  |  |  |
| `gia_them` | decimal(12,0) |  |  |  |
| `bat_buoc` | tinyint(1) |  |  |  |
| `thu_tu` | tinyint(3) unsigned |  |  |  |
| `trang_thai` | varchar(10) |  |  |  |

## `nguyen_lieu`
Nguyên liệu kho

| Cột | Kiểu | Null | Khóa | Ghi chú |
|---|---|---|---|---|
| `ma_nl` | varchar(10) |  | PK |  |
| `ten_nl` | varchar(100) |  | UQ |  |
| `don_vi` | varchar(20) |  |  |  |

## `nha_cung_cap`
Nhà cung cấp (liên hệ mã hóa)

| Cột | Kiểu | Null | Khóa | Ghi chú |
|---|---|---|---|---|
| `ma_ncc` | varchar(10) |  | PK |  |
| `ten_ncc` | varchar(100) |  | UQ |  |
| `dia_chi` | text | ✓ |  | 🔐 mã hóa |
| `sdt` | text | ✓ |  | 🔐 mã hóa |
| `email` | text | ✓ |  | 🔐 mã hóa |

## `nhan_vien`
Hồ sơ nhân viên (PII mã hóa)

| Cột | Kiểu | Null | Khóa | Ghi chú |
|---|---|---|---|---|
| `ma_nv` | varchar(10) |  | PK |  |
| `ten_nv` | varchar(100) |  |  |  |
| `sdt` | text | ✓ |  | 🔐 mã hóa |
| `email` | text | ✓ |  | 🔐 mã hóa |
| `cccd` | text | ✓ | UQ | 🔐 mã hóa |
| `dia_chi` | text | ✓ |  | 🔐 mã hóa |
| `ma_chi_nhanh` | varchar(10) |  | FK→chi_nhanh.ma_chi_nhanh |  |

## `nhat_ky_dang_nhap`
Nhật ký đăng nhập (audit)

| Cột | Kiểu | Null | Khóa | Ghi chú |
|---|---|---|---|---|
| `id` | bigint(20) unsigned |  | PK | auto_increment |
| `ten_tk` | varchar(50) | ✓ |  |  |
| `ma_tai_khoan` | varchar(10) | ✓ |  |  |
| `ma_nv` | varchar(10) | ✓ |  |  |
| `hanh_dong` | varchar(30) |  |  |  |
| `thanh_cong` | tinyint(1) |  |  |  |
| `dia_chi_ip` | varchar(45) | ✓ |  |  |
| `user_agent` | varchar(255) | ✓ |  |  |
| `chi_tiet` | varchar(255) | ✓ |  |  |
| `thoi_gian` | datetime |  |  |  |

## `order_logs`


| Cột | Kiểu | Null | Khóa | Ghi chú |
|---|---|---|---|---|
| `id` | bigint(20) unsigned |  | PK | auto_increment |
| `ma_order` | varchar(20) |  | FK→orders.ma_order |  |
| `hanh_dong` | varchar(50) |  |  |  |
| `trang_thai_cu` | varchar(15) | ✓ |  |  |
| `trang_thai_moi` | varchar(15) | ✓ |  |  |
| `noi_dung` | varchar(500) | ✓ |  |  |
| `du_lieu` | longtext | ✓ |  |  |
| `ma_nv` | varchar(10) | ✓ |  |  |
| `created_at` | datetime |  |  |  |

## `orders`
Đơn hàng (PII khách tạm mã hóa, hình thức tại bàn/mang về)

| Cột | Kiểu | Null | Khóa | Ghi chú |
|---|---|---|---|---|
| `ma_order` | varchar(20) |  | PK |  |
| `ma_ban` | varchar(10) | ✓ | FK→ban.ma_ban |  |
| `ma_kh` | varchar(10) | ✓ | FK→khach_hang.ma_kh |  |
| `ten_khach` | text | ✓ |  | 🔐 mã hóa |
| `sdt_khach` | text | ✓ |  | 🔐 mã hóa |
| `ma_chi_nhanh` | varchar(10) |  | FK→chi_nhanh.ma_chi_nhanh |  |
| `trang_thai` | varchar(15) |  |  |  |
| `ngay_order` | date |  |  |  |
| `gio_order` | time |  |  |  |
| `thoi_gian_xac_nhan` | datetime | ✓ |  |  |
| `thoi_gian_phuc_vu` | datetime | ✓ |  |  |
| `thoi_gian_thanh_toan` | datetime | ✓ |  |  |
| `ghi_chu` | varchar(300) | ✓ |  |  |
| `hinh_thuc` | varchar(10) |  |  |  |

## `phieu_kiem_ke`
Phiếu kiểm kê

| Cột | Kiểu | Null | Khóa | Ghi chú |
|---|---|---|---|---|
| `ma_pkk` | varchar(20) |  | PK |  |
| `ngay_kk` | date |  |  |  |
| `thoi_gian_kk` | datetime | ✓ |  |  |
| `ma_chi_nhanh` | varchar(10) |  | FK→chi_nhanh.ma_chi_nhanh |  |
| `ma_nv` | varchar(10) |  | FK→nhan_vien.ma_nv |  |
| `trang_thai` | varchar(15) |  |  |  |
| `ghi_chu` | varchar(300) | ✓ |  |  |

## `phieu_nhap_kho`
Phiếu nhập kho

| Cột | Kiểu | Null | Khóa | Ghi chú |
|---|---|---|---|---|
| `ma_pnk` | varchar(20) |  | PK |  |
| `ngay_nk` | date |  |  |  |
| `ma_ncc` | varchar(10) |  | FK→nha_cung_cap.ma_ncc |  |
| `ma_nv` | varchar(10) |  | FK→nhan_vien.ma_nv |  |
| `ma_chi_nhanh` | varchar(10) |  | FK→chi_nhanh.ma_chi_nhanh |  |
| `tong_gia_tri` | decimal(15,0) |  |  |  |
| `trang_thai` | varchar(10) |  |  |  |
| `ghi_chu` | varchar(300) | ✓ |  |  |

## `scan_log`
Nhật ký quét QR

| Cột | Kiểu | Null | Khóa | Ghi chú |
|---|---|---|---|---|
| `id` | bigint(20) unsigned |  | PK | auto_increment |
| `ma_ban` | varchar(10) |  |  |  |
| `ma_chi_nhanh` | varchar(10) | ✓ |  |  |
| `ip` | varchar(45) | ✓ |  |  |
| `user_agent` | varchar(300) | ✓ |  |  |
| `thoi_gian` | datetime |  |  |  |

## `tai_khoan`
Tài khoản đăng nhập (NV/quản lý) + bảo mật/2FA/kích hoạt

| Cột | Kiểu | Null | Khóa | Ghi chú |
|---|---|---|---|---|
| `ma_tai_khoan` | varchar(10) |  | PK |  |
| `ten_tk` | varchar(50) |  | UQ |  |
| `mat_khau` | varchar(255) |  |  |  |
| `chuc_vu` | varchar(20) |  |  |  |
| `trang_thai` | varchar(10) |  |  |  |
| `dang_nhap_sai` | int(10) unsigned |  |  |  |
| `khoa_den` | datetime | ✓ |  |  |
| `xac_thuc_2_lop` | tinyint(1) |  |  |  |
| `otp_ma` | varchar(255) | ✓ |  |  |
| `otp_het_han` | datetime | ✓ |  |  |
| `otp_sai` | int(10) unsigned |  |  |  |
| `lan_dang_nhap_cuoi` | datetime | ✓ |  |  |
| `ip_dang_nhap_cuoi` | varchar(45) | ✓ |  |  |
| `email_xac_thuc_luc` | datetime | ✓ |  |  |
| `kich_hoat_token` | varchar(64) | ✓ |  |  |
| `kich_hoat_het_han` | datetime | ✓ |  |  |
| `tao_luc` | datetime | ✓ |  |  |
| `ma_nv` | varchar(10) |  | FK→nhan_vien.ma_nv |  |

## `thanh_toan_online`
Giao dịch VNPay (đối soát)

| Cột | Kiểu | Null | Khóa | Ghi chú |
|---|---|---|---|---|
| `id` | bigint(20) unsigned |  | PK | auto_increment |
| `ma_giao_dich` | varchar(50) |  | UQ |  |
| `ma_order` | varchar(20) |  |  |  |
| `cong` | varchar(20) |  |  |  |
| `so_tien` | decimal(12,0) |  |  |  |
| `chiet_khau` | decimal(5,2) |  |  |  |
| `trang_thai` | varchar(20) |  |  |  |
| `ma_gd_cong` | varchar(50) | ✓ |  |  |
| `ma_phan_hoi` | varchar(10) | ✓ |  |  |
| `ma_nv` | varchar(10) | ✓ |  |  |
| `ma_hoa_don` | varchar(30) | ✓ |  |  |
| `du_lieu` | longtext | ✓ |  |  |
| `thoi_gian_tao` | datetime |  |  |  |
| `thoi_gian_cap_nhat` | datetime | ✓ |  |  |

## `ton_kho`
Tồn kho theo chi nhánh

| Cột | Kiểu | Null | Khóa | Ghi chú |
|---|---|---|---|---|
| `ma_chi_nhanh` | varchar(10) |  | PK, FK→chi_nhanh.ma_chi_nhanh |  |
| `ma_nl` | varchar(10) |  | PK, FK→nguyen_lieu.ma_nl |  |
| `sl_ton_kho_he_thong` | decimal(12,2) |  |  |  |
| `sl_ton_kho_thuc_te` | decimal(12,2) |  |  |  |
| `nguong_canh_bao` | decimal(12,2) |  |  |  |
| `hao_hut_cost` | decimal(12,0) |  |  |  |

## `topping`
Topping

| Cột | Kiểu | Null | Khóa | Ghi chú |
|---|---|---|---|---|
| `ma_topping` | varchar(10) |  | PK |  |
| `ten_topping` | varchar(100) |  |  |  |
| `gia_them` | decimal(12,0) |  |  |  |
| `canh_bao` | varchar(255) | ✓ |  |  |
| `trang_thai` | varchar(10) |  |  |  |

## `vw_menu_hien_thi`


| Cột | Kiểu | Null | Khóa | Ghi chú |
|---|---|---|---|---|
| `ma_mon` | varchar(10) |  |  |  |
| `ten_mon` | varchar(100) |  |  |  |
| `don_gia` | decimal(12,0) |  |  |  |
| `mo_ta` | varchar(500) | ✓ |  |  |
| `hinh_anh` | varchar(255) | ✓ |  |  |
| `trang_thai` | varchar(10) |  |  |  |
| `ten_danh_muc` | varchar(100) |  |  |  |

## `vw_order_dashboard`


| Cột | Kiểu | Null | Khóa | Ghi chú |
|---|---|---|---|---|
| `ma_order` | varchar(20) |  |  |  |
| `so_ban` | int(10) unsigned |  |  |  |
| `ten_chi_nhanh` | varchar(100) |  |  |  |
| `ten_kh` | text | ✓ |  |  |
| `trang_thai` | varchar(15) |  |  |  |
| `ngay_order` | date |  |  |  |
| `gio_order` | time |  |  |  |
| `tong_tien` | decimal(44,0) | ✓ |  |  |

## `vw_ton_kho_tong_quan`


| Cột | Kiểu | Null | Khóa | Ghi chú |
|---|---|---|---|---|
| `ten_chi_nhanh` | varchar(100) |  |  |  |
| `ten_nl` | varchar(100) |  |  |  |
| `don_vi` | varchar(20) |  |  |  |
| `sl_ton_kho_he_thong` | decimal(12,2) |  |  |  |
| `sl_ton_kho_thuc_te` | decimal(12,2) |  |  |  |
| `nguong_canh_bao` | decimal(12,2) |  |  |  |
| `hao_hut_cost` | decimal(12,0) |  |  |  |
| `trang_thai_kho` | varchar(8) |  |  |  |

---

## Khóa ngoại (quan hệ)

| Bảng con | Cột | → Bảng cha | Cột |
|---|---|---|---|
| `ban` | ma_chi_nhanh | `chi_nhanh` | ma_chi_nhanh |
| `chi_tiet_kiem_ke` | ma_pkk | `phieu_kiem_ke` | ma_pkk |
| `chi_tiet_kiem_ke` | ma_nl | `nguyen_lieu` | ma_nl |
| `chi_tiet_nhap_kho` | ma_nl | `nguyen_lieu` | ma_nl |
| `chi_tiet_nhap_kho` | ma_pnk | `phieu_nhap_kho` | ma_pnk |
| `chi_tiet_order` | ma_order | `orders` | ma_order |
| `chi_tiet_order` | ma_mon | `mon` | ma_mon |
| `chi_tiet_order_option` | chi_tiet_id | `chi_tiet_order` | id |
| `dinh_muc` | ma_nl | `nguyen_lieu` | ma_nl |
| `dinh_muc` | ma_mon | `mon` | ma_mon |
| `hoa_don` | ma_order | `orders` | ma_order |
| `hoa_don` | ma_nv_thu_ngan | `nhan_vien` | ma_nv |
| `hoa_don` | ma_kh | `khach_hang` | ma_kh |
| `mon` | ma_danh_muc | `danh_muc` | ma_danh_muc |
| `mon_option` | ma_mon | `mon` | ma_mon |
| `nhan_vien` | ma_chi_nhanh | `chi_nhanh` | ma_chi_nhanh |
| `orders` | ma_kh | `khach_hang` | ma_kh |
| `orders` | ma_chi_nhanh | `chi_nhanh` | ma_chi_nhanh |
| `orders` | ma_ban | `ban` | ma_ban |
| `order_logs` | ma_order | `orders` | ma_order |
| `phieu_kiem_ke` | ma_nv | `nhan_vien` | ma_nv |
| `phieu_kiem_ke` | ma_chi_nhanh | `chi_nhanh` | ma_chi_nhanh |
| `phieu_nhap_kho` | ma_nv | `nhan_vien` | ma_nv |
| `phieu_nhap_kho` | ma_ncc | `nha_cung_cap` | ma_ncc |
| `phieu_nhap_kho` | ma_chi_nhanh | `chi_nhanh` | ma_chi_nhanh |
| `tai_khoan` | ma_nv | `nhan_vien` | ma_nv |
| `ton_kho` | ma_nl | `nguyen_lieu` | ma_nl |
| `ton_kho` | ma_chi_nhanh | `chi_nhanh` | ma_chi_nhanh |

---

## Ghi chú bảo mật dữ liệu
- Cột 🔐 lưu **ciphertext AES-256-GCM** (Laravel Crypt) — đọc/ghi qua Eloquent `encrypted` cast hoặc `App\Support\Pii`.
- **Không** `WHERE`/`GROUP BY` trực tiếp trên cột mã hóa (IV ngẫu nhiên). Tra cứu SĐT khách qua `khach_hang.sdt_hash` (🔑 HMAC).
- **APP_KEY là chìa khóa giải mã** — không đổi trên môi trường có dữ liệu đã mã hóa.
- Xem thêm `docs/DATA_PRIVACY_ANALYTICS.md` và biểu đồ `docs/physical-data-model.png`.
