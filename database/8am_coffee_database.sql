-- ============================================================
--  8AM COFFEE & ROASTERY - QR ORDER SYSTEM
--  SQL Server Database - Full Schema
--  Nhóm 29 - TTCN - Học viện Ngân hàng
--  Ngày tạo: 27/05/2026
-- ============================================================
-- THỨ TỰ TẠO BẢNG: tuân theo quan hệ khóa ngoại
-- 1. CHI_NHANH           8.  NGUYEN_LIEU
-- 2. NHAN_VIEN           9.  DINH_MUC
-- 3. TAI_KHOAN           10. ORDERS
-- 4. BAN                 11. CHI_TIET_ORDER
-- 5. KHACH_HANG          12. HOA_DON
-- 6. DANH_MUC            13. TON_KHO
-- 7. MON                 14. NHA_CUNG_CAP
--                        15. PHIEU_NHAP_KHO
--                        16. CHI_TIET_NHAP_KHO
--                        17. PHIEU_KIEM_KE
--                        18. CHI_TIET_KIEM_KE
-- ============================================================

USE master;
GO

IF EXISTS (SELECT name FROM sys.databases WHERE name = '8AMCoffee')
    DROP DATABASE [8AMCoffee];
GO

CREATE DATABASE [8AMCoffee]
    COLLATE Vietnamese_CI_AS;
GO

USE [8AMCoffee];
GO

-- ============================================================
-- BẢNG 1: CHI_NHANH
-- ============================================================
CREATE TABLE CHI_NHANH (
    ma_chi_nhanh    VARCHAR(10)     NOT NULL,
    ten_chi_nhanh   NVARCHAR(100)   NOT NULL,
    dia_chi         NVARCHAR(255)   NULL,
    sdt             VARCHAR(15)     NULL,

    CONSTRAINT PK_CHI_NHANH         PRIMARY KEY (ma_chi_nhanh),
    CONSTRAINT CHK_CHI_NHANH_SDT    CHECK (sdt LIKE '[0-9]%' OR sdt IS NULL),
    CONSTRAINT UQ_CHI_NHANH_TEN     UNIQUE (ten_chi_nhanh)
);
GO

-- ============================================================
-- BẢNG 2: NHAN_VIEN
-- ============================================================
CREATE TABLE NHAN_VIEN (
    ma_nv           VARCHAR(10)     NOT NULL,
    ten_nv          NVARCHAR(100)   NOT NULL,
    sdt             VARCHAR(15)     NULL,
    cccd            VARCHAR(12)     NULL,
    dia_chi         NVARCHAR(255)   NULL,
    ma_chi_nhanh    VARCHAR(10)     NOT NULL,

    CONSTRAINT PK_NHAN_VIEN         PRIMARY KEY (ma_nv),
    CONSTRAINT FK_NV_CHI_NHANH      FOREIGN KEY (ma_chi_nhanh)
        REFERENCES CHI_NHANH(ma_chi_nhanh)
        ON UPDATE CASCADE
        ON DELETE NO ACTION,
    CONSTRAINT CHK_NV_SDT           CHECK (sdt LIKE '[0-9]%' OR sdt IS NULL),
    CONSTRAINT CHK_NV_CCCD          CHECK (LEN(cccd) = 12 OR cccd IS NULL),
    CONSTRAINT UQ_NV_CCCD           UNIQUE (cccd)
);
GO

-- ============================================================
-- BẢNG 3: TAI_KHOAN
-- ============================================================
CREATE TABLE TAI_KHOAN (
    ma_tai_khoan    VARCHAR(10)     NOT NULL,
    ten_tk          VARCHAR(50)     NOT NULL,
    mat_khau        VARCHAR(255)    NOT NULL,   -- lưu hash bcrypt
    chuc_vu         VARCHAR(20)     NOT NULL,
    trang_thai      VARCHAR(10)     NOT NULL    DEFAULT 'active',
    ma_nv           VARCHAR(10)     NOT NULL,

    CONSTRAINT PK_TAI_KHOAN         PRIMARY KEY (ma_tai_khoan),
    CONSTRAINT FK_TK_NHAN_VIEN      FOREIGN KEY (ma_nv)
        REFERENCES NHAN_VIEN(ma_nv)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT CHK_TK_CHUC_VU       CHECK (chuc_vu IN ('quan_ly', 'nhan_vien', 'bartender', 'admin')),
    CONSTRAINT CHK_TK_TRANG_THAI    CHECK (trang_thai IN ('active', 'inactive', 'banned')),
    CONSTRAINT UQ_TK_TEN            UNIQUE (ten_tk),
    CONSTRAINT CHK_TK_MATKHAU_LEN  CHECK (LEN(mat_khau) >= 8)
);
GO

-- ============================================================
-- BẢNG 4: BAN
-- ============================================================
CREATE TABLE BAN (
    ma_ban          VARCHAR(10)     NOT NULL,
    so_ban          INT             NOT NULL,
    vi_tri          NVARCHAR(50)    NULL,
    trang_thai      VARCHAR(10)     NOT NULL    DEFAULT 'trong',
    ma_chi_nhanh    VARCHAR(10)     NOT NULL,

    CONSTRAINT PK_BAN               PRIMARY KEY (ma_ban),
    CONSTRAINT FK_BAN_CHI_NHANH     FOREIGN KEY (ma_chi_nhanh)
        REFERENCES CHI_NHANH(ma_chi_nhanh)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT CHK_BAN_SO_BAN       CHECK (so_ban > 0),
    CONSTRAINT CHK_BAN_TRANG_THAI   CHECK (trang_thai IN ('trong', 'co_khach', 'dat_truoc', 'dong')),
    CONSTRAINT UQ_BAN_SO_CHI_NHANH  UNIQUE (so_ban, ma_chi_nhanh)
);
GO

-- ============================================================
-- BẢNG 5: KHACH_HANG
-- ============================================================
CREATE TABLE KHACH_HANG (
    ma_kh           VARCHAR(10)     NOT NULL,
    ten_kh          NVARCHAR(100)   NOT NULL,
    sdt             VARCHAR(15)     NULL,
    ngay_tao        DATETIME        NOT NULL    DEFAULT GETDATE(),

    CONSTRAINT PK_KHACH_HANG        PRIMARY KEY (ma_kh),
    CONSTRAINT CHK_KH_SDT           CHECK (sdt LIKE '[0-9]%' OR sdt IS NULL)
);
GO

-- ============================================================
-- BẢNG 6: DANH_MUC
-- ============================================================
CREATE TABLE DANH_MUC (
    ma_danh_muc     VARCHAR(10)     NOT NULL,
    ten_danh_muc    NVARCHAR(100)   NOT NULL,

    CONSTRAINT PK_DANH_MUC          PRIMARY KEY (ma_danh_muc),
    CONSTRAINT UQ_DANH_MUC_TEN      UNIQUE (ten_danh_muc)
);
GO

-- ============================================================
-- BẢNG 7: MON
-- ============================================================
CREATE TABLE MON (
    ma_mon          VARCHAR(10)     NOT NULL,
    ten_mon         NVARCHAR(100)   NOT NULL,
    don_gia         DECIMAL(12,0)   NOT NULL,
    mo_ta           NVARCHAR(500)   NULL,
    hinh_anh        VARCHAR(255)    NULL,
    ma_danh_muc     VARCHAR(10)     NOT NULL,
    trang_thai      VARCHAR(10)     NOT NULL    DEFAULT 'active',

    CONSTRAINT PK_MON               PRIMARY KEY (ma_mon),
    CONSTRAINT FK_MON_DANH_MUC      FOREIGN KEY (ma_danh_muc)
        REFERENCES DANH_MUC(ma_danh_muc)
        ON UPDATE CASCADE
        ON DELETE NO ACTION,
    CONSTRAINT CHK_MON_DON_GIA      CHECK (don_gia > 0),
    CONSTRAINT CHK_MON_TRANG_THAI   CHECK (trang_thai IN ('active', 'het_hang', 'an'))
);
GO

-- ============================================================
-- BẢNG 8: NGUYEN_LIEU
-- ============================================================
CREATE TABLE NGUYEN_LIEU (
    ma_nl           VARCHAR(10)     NOT NULL,
    ten_nl          NVARCHAR(100)   NOT NULL,
    don_vi          NVARCHAR(20)    NOT NULL,

    CONSTRAINT PK_NGUYEN_LIEU       PRIMARY KEY (ma_nl),
    CONSTRAINT UQ_NL_TEN            UNIQUE (ten_nl),
    CONSTRAINT CHK_NL_DON_VI        CHECK (don_vi IN (
        'gram', 'kg', 'ml', 'lit', 'cai', 'goi', 'hop', 'chai', 'tui'
    ))
);
GO

-- ============================================================
-- BẢNG 9: DINH_MUC (công thức nguyên liệu cho từng món)
-- ============================================================
CREATE TABLE DINH_MUC (
    ma_mon          VARCHAR(10)     NOT NULL,
    ma_nl           VARCHAR(10)     NOT NULL,
    so_luong_dung   DECIMAL(10,2)   NOT NULL,
    mo_ta           NVARCHAR(200)   NULL,

    CONSTRAINT PK_DINH_MUC          PRIMARY KEY (ma_mon, ma_nl),
    CONSTRAINT FK_DM_MON            FOREIGN KEY (ma_mon)
        REFERENCES MON(ma_mon)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT FK_DM_NGUYEN_LIEU    FOREIGN KEY (ma_nl)
        REFERENCES NGUYEN_LIEU(ma_nl)
        ON UPDATE CASCADE
        ON DELETE NO ACTION,
    CONSTRAINT CHK_DM_SO_LUONG      CHECK (so_luong_dung > 0)
);
GO

-- ============================================================
-- BẢNG 10: ORDERS
-- ============================================================
CREATE TABLE ORDERS (
    ma_order        VARCHAR(20)     NOT NULL,
    ma_ban          VARCHAR(10)     NOT NULL,
    ma_kh           VARCHAR(10)     NULL,
    ma_chi_nhanh    VARCHAR(10)     NOT NULL,
    trang_thai      VARCHAR(15)     NOT NULL    DEFAULT 'cho_xac_nhan',
    ngay_order      DATE            NOT NULL    DEFAULT CAST(GETDATE() AS DATE),
    gio_order       TIME            NOT NULL    DEFAULT CAST(GETDATE() AS TIME),
    ghi_chu         NVARCHAR(300)   NULL,

    CONSTRAINT PK_ORDERS            PRIMARY KEY (ma_order),
    CONSTRAINT FK_ORDERS_BAN        FOREIGN KEY (ma_ban)
        REFERENCES BAN(ma_ban)
        ON UPDATE NO ACTION
        ON DELETE NO ACTION,
    CONSTRAINT FK_ORDERS_KH         FOREIGN KEY (ma_kh)
        REFERENCES KHACH_HANG(ma_kh)
        ON UPDATE NO ACTION
        ON DELETE SET NULL,
    CONSTRAINT FK_ORDERS_CHI_NHANH  FOREIGN KEY (ma_chi_nhanh)
        REFERENCES CHI_NHANH(ma_chi_nhanh)
        ON UPDATE NO ACTION
        ON DELETE NO ACTION,
    CONSTRAINT CHK_ORDERS_TRANGTHAI CHECK (trang_thai IN (
        'cho_xac_nhan', 'da_xac_nhan', 'dang_pha_che',
        'da_phuc_vu', 'hoan_thanh', 'da_huy'
    ))
);
GO

-- ============================================================
-- BẢNG 11: CHI_TIET_ORDER
-- ============================================================
CREATE TABLE CHI_TIET_ORDER (
    ma_order        VARCHAR(20)     NOT NULL,
    ma_mon          VARCHAR(10)     NOT NULL,
    so_luong        INT             NOT NULL,
    don_gia_tai_thoi_diem DECIMAL(12,0) NOT NULL,  -- snapshot giá tại thời điểm order
    ghi_chu         NVARCHAR(200)   NULL,

    CONSTRAINT PK_CHI_TIET_ORDER    PRIMARY KEY (ma_order, ma_mon),
    CONSTRAINT FK_CTO_ORDERS        FOREIGN KEY (ma_order)
        REFERENCES ORDERS(ma_order)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT FK_CTO_MON           FOREIGN KEY (ma_mon)
        REFERENCES MON(ma_mon)
        ON UPDATE NO ACTION
        ON DELETE NO ACTION,
    CONSTRAINT CHK_CTO_SO_LUONG     CHECK (so_luong > 0),
    CONSTRAINT CHK_CTO_DON_GIA      CHECK (don_gia_tai_thoi_diem > 0)
);
GO

-- ============================================================
-- BẢNG 12: HOA_DON
-- ============================================================
CREATE TABLE HOA_DON (
    ma_hoa_don          VARCHAR(20)     NOT NULL,
    ma_order            VARCHAR(20)     NOT NULL,
    ma_kh               VARCHAR(10)     NULL,
    thoi_gian_lap       DATETIME        NOT NULL    DEFAULT GETDATE(),
    tong_tien_truoc_ck  DECIMAL(12,0)   NOT NULL    DEFAULT 0,
    chiet_khau          DECIMAL(5,2)    NOT NULL    DEFAULT 0,   -- % chiết khấu 0-100
    tong_tien_sau_ck    DECIMAL(12,0)   NOT NULL    DEFAULT 0,
    phuong_thuc_tt      VARCHAR(20)     NOT NULL    DEFAULT 'tien_mat',
    trang_thai          VARCHAR(15)     NOT NULL    DEFAULT 'cho_thanh_toan',
    ma_nv_thu_ngan      VARCHAR(10)     NULL,

    CONSTRAINT PK_HOA_DON           PRIMARY KEY (ma_hoa_don),
    CONSTRAINT FK_HD_ORDERS         FOREIGN KEY (ma_order)
        REFERENCES ORDERS(ma_order)
        ON UPDATE NO ACTION
        ON DELETE NO ACTION,
    CONSTRAINT FK_HD_KH             FOREIGN KEY (ma_kh)
        REFERENCES KHACH_HANG(ma_kh)
        ON UPDATE NO ACTION
        ON DELETE SET NULL,
    CONSTRAINT FK_HD_NV             FOREIGN KEY (ma_nv_thu_ngan)
        REFERENCES NHAN_VIEN(ma_nv)
        ON UPDATE NO ACTION
        ON DELETE SET NULL,
    CONSTRAINT CHK_HD_CHIET_KHAU    CHECK (chiet_khau BETWEEN 0 AND 100),
    CONSTRAINT CHK_HD_TONG_TIEN     CHECK (tong_tien_truoc_ck >= 0),
    CONSTRAINT CHK_HD_PTTT          CHECK (phuong_thuc_tt IN (
        'tien_mat', 'chuyen_khoan', 'the', 'vi_dien_tu', 'momo', 'vnpay'
    )),
    CONSTRAINT CHK_HD_TRANG_THAI    CHECK (trang_thai IN (
        'cho_thanh_toan', 'da_thanh_toan', 'da_huy'
    )),
    CONSTRAINT UQ_HD_ORDER          UNIQUE (ma_order)  -- 1 order chỉ có 1 hóa đơn
);
GO

-- ============================================================
-- BẢNG 13: TON_KHO
-- ============================================================
CREATE TABLE TON_KHO (
    ma_chi_nhanh            VARCHAR(10)     NOT NULL,
    ma_nl                   VARCHAR(10)     NOT NULL,
    sl_ton_kho_he_thong     DECIMAL(12,2)   NOT NULL    DEFAULT 0,  -- dự tính
    sl_ton_kho_thuc_te      DECIMAL(12,2)   NOT NULL    DEFAULT 0,  -- kiểm kê thực tế
    nguong_canh_bao         DECIMAL(12,2)   NOT NULL    DEFAULT 0,  -- mức cảnh báo hết hàng
    hao_hut_cost            DECIMAL(12,0)   NOT NULL    DEFAULT 0,

    CONSTRAINT PK_TON_KHO           PRIMARY KEY (ma_chi_nhanh, ma_nl),
    CONSTRAINT FK_TK_CHI_NHANH      FOREIGN KEY (ma_chi_nhanh)
        REFERENCES CHI_NHANH(ma_chi_nhanh)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT FK_TK_NL             FOREIGN KEY (ma_nl)
        REFERENCES NGUYEN_LIEU(ma_nl)
        ON UPDATE CASCADE
        ON DELETE NO ACTION,
    CONSTRAINT CHK_TK_SL_HT         CHECK (sl_ton_kho_he_thong >= 0),
    CONSTRAINT CHK_TK_SL_TT         CHECK (sl_ton_kho_thuc_te >= 0),
    CONSTRAINT CHK_TK_NGUONG        CHECK (nguong_canh_bao >= 0)
);
GO

-- ============================================================
-- BẢNG 14: NHA_CUNG_CAP
-- ============================================================
CREATE TABLE NHA_CUNG_CAP (
    ma_ncc          VARCHAR(10)     NOT NULL,
    ten_ncc         NVARCHAR(100)   NOT NULL,
    dia_chi         NVARCHAR(255)   NULL,
    sdt             VARCHAR(15)     NULL,
    email           VARCHAR(100)    NULL,

    CONSTRAINT PK_NHA_CUNG_CAP      PRIMARY KEY (ma_ncc),
    CONSTRAINT CHK_NCC_SDT          CHECK (sdt LIKE '[0-9]%' OR sdt IS NULL),
    CONSTRAINT CHK_NCC_EMAIL        CHECK (email LIKE '%@%.%' OR email IS NULL),
    CONSTRAINT UQ_NCC_TEN           UNIQUE (ten_ncc)
);
GO

-- ============================================================
-- BẢNG 15: PHIEU_NHAP_KHO
-- ============================================================
CREATE TABLE PHIEU_NHAP_KHO (
    ma_pnk          VARCHAR(20)     NOT NULL,
    ngay_nk         DATE            NOT NULL    DEFAULT CAST(GETDATE() AS DATE),
    ma_ncc          VARCHAR(10)     NOT NULL,
    ma_nv           VARCHAR(10)     NOT NULL,
    ma_chi_nhanh    VARCHAR(10)     NOT NULL,
    tong_gia_tri    DECIMAL(15,0)   NOT NULL    DEFAULT 0,
    trang_thai      VARCHAR(10)     NOT NULL    DEFAULT 'cho_duyet',
    ghi_chu         NVARCHAR(300)   NULL,

    CONSTRAINT PK_PHIEU_NHAP_KHO    PRIMARY KEY (ma_pnk),
    CONSTRAINT FK_PNK_NCC           FOREIGN KEY (ma_ncc)
        REFERENCES NHA_CUNG_CAP(ma_ncc)
        ON UPDATE NO ACTION
        ON DELETE NO ACTION,
    CONSTRAINT FK_PNK_NV            FOREIGN KEY (ma_nv)
        REFERENCES NHAN_VIEN(ma_nv)
        ON UPDATE NO ACTION
        ON DELETE NO ACTION,
    CONSTRAINT FK_PNK_CHI_NHANH     FOREIGN KEY (ma_chi_nhanh)
        REFERENCES CHI_NHANH(ma_chi_nhanh)
        ON UPDATE NO ACTION
        ON DELETE NO ACTION,
    CONSTRAINT CHK_PNK_TONG         CHECK (tong_gia_tri >= 0),
    CONSTRAINT CHK_PNK_TRANGTHAI    CHECK (trang_thai IN ('cho_duyet', 'da_duyet', 'da_huy')),
    CONSTRAINT CHK_PNK_NGAY         CHECK (ngay_nk <= CAST(GETDATE() AS DATE))
);
GO

-- ============================================================
-- BẢNG 16: CHI_TIET_NHAP_KHO
-- ============================================================
CREATE TABLE CHI_TIET_NHAP_KHO (
    ma_pnk          VARCHAR(20)     NOT NULL,
    ma_nl           VARCHAR(10)     NOT NULL,
    so_luong        DECIMAL(12,2)   NOT NULL,
    don_gia         DECIMAL(12,0)   NOT NULL,
    tong_tien       AS (CAST(so_luong * don_gia AS DECIMAL(15,0))),  -- computed column

    CONSTRAINT PK_CHI_TIET_NK       PRIMARY KEY (ma_pnk, ma_nl),
    CONSTRAINT FK_CTNK_PHIEU        FOREIGN KEY (ma_pnk)
        REFERENCES PHIEU_NHAP_KHO(ma_pnk)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT FK_CTNK_NL           FOREIGN KEY (ma_nl)
        REFERENCES NGUYEN_LIEU(ma_nl)
        ON UPDATE NO ACTION
        ON DELETE NO ACTION,
    CONSTRAINT CHK_CTNK_SL          CHECK (so_luong > 0),
    CONSTRAINT CHK_CTNK_GIA         CHECK (don_gia > 0)
);
GO

-- ============================================================
-- BẢNG 17: PHIEU_KIEM_KE
-- ============================================================
CREATE TABLE PHIEU_KIEM_KE (
    ma_pkk          VARCHAR(20)     NOT NULL,
    ngay_kk         DATE            NOT NULL    DEFAULT CAST(GETDATE() AS DATE),
    ma_chi_nhanh    VARCHAR(10)     NOT NULL,
    ma_nv           VARCHAR(10)     NOT NULL,
    trang_thai      VARCHAR(10)     NOT NULL    DEFAULT 'nhap',
    ghi_chu         NVARCHAR(300)   NULL,

    CONSTRAINT PK_PHIEU_KIEM_KE     PRIMARY KEY (ma_pkk),
    CONSTRAINT FK_PKK_CHI_NHANH     FOREIGN KEY (ma_chi_nhanh)
        REFERENCES CHI_NHANH(ma_chi_nhanh)
        ON UPDATE NO ACTION
        ON DELETE NO ACTION,
    CONSTRAINT FK_PKK_NV            FOREIGN KEY (ma_nv)
        REFERENCES NHAN_VIEN(ma_nv)
        ON UPDATE NO ACTION
        ON DELETE NO ACTION,
    CONSTRAINT CHK_PKK_TRANGTHAI    CHECK (trang_thai IN ('nhap', 'da_xac_nhan', 'da_huy'))
);
GO

-- ============================================================
-- BẢNG 18: CHI_TIET_KIEM_KE
-- ============================================================
CREATE TABLE CHI_TIET_KIEM_KE (
    ma_pkk          VARCHAR(20)     NOT NULL,
    ma_nl           VARCHAR(10)     NOT NULL,
    sl_he_thong     DECIMAL(12,2)   NOT NULL    DEFAULT 0,  -- snapshot hệ thống lúc kiểm kê
    sl_thuc_te      DECIMAL(12,2)   NOT NULL,
    chenh_lech      AS (sl_thuc_te - sl_he_thong),          -- computed column
    don_gia_tb      DECIMAL(12,0)   NULL,                   -- giá trung bình (để tính hao hụt cost)

    CONSTRAINT PK_CHI_TIET_KK       PRIMARY KEY (ma_pkk, ma_nl),
    CONSTRAINT FK_CTKK_PHIEU        FOREIGN KEY (ma_pkk)
        REFERENCES PHIEU_KIEM_KE(ma_pkk)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT FK_CTKK_NL           FOREIGN KEY (ma_nl)
        REFERENCES NGUYEN_LIEU(ma_nl)
        ON UPDATE NO ACTION
        ON DELETE NO ACTION,
    CONSTRAINT CHK_CTKK_SL_TT       CHECK (sl_thuc_te >= 0),
    CONSTRAINT CHK_CTKK_SL_HT       CHECK (sl_he_thong >= 0)
);
GO

-- ============================================================
-- INDEXES (tăng hiệu năng query phổ biến)
-- ============================================================

-- Orders: tìm theo trạng thái, theo bàn, theo ngày
CREATE NONCLUSTERED INDEX IX_ORDERS_TRANG_THAI
    ON ORDERS(trang_thai) INCLUDE (ma_ban, ma_chi_nhanh, ngay_order);

CREATE NONCLUSTERED INDEX IX_ORDERS_BAN_NGAY
    ON ORDERS(ma_ban, ngay_order);

CREATE NONCLUSTERED INDEX IX_ORDERS_CHI_NHANH_NGAY
    ON ORDERS(ma_chi_nhanh, ngay_order);

-- Hóa đơn: tìm theo ngày, trạng thái
CREATE NONCLUSTERED INDEX IX_HOA_DON_THOI_GIAN
    ON HOA_DON(thoi_gian_lap) INCLUDE (tong_tien_sau_ck, trang_thai);

-- Tồn kho: tìm nguyên liệu sắp hết
CREATE NONCLUSTERED INDEX IX_TON_KHO_CANH_BAO
    ON TON_KHO(ma_chi_nhanh, sl_ton_kho_he_thong, nguong_canh_bao);

-- Phiếu nhập kho: theo chi nhánh và ngày
CREATE NONCLUSTERED INDEX IX_PNK_CHINHANH_NGAY
    ON PHIEU_NHAP_KHO(ma_chi_nhanh, ngay_nk);
GO

-- ============================================================
-- TRIGGER 1: Khi order được XÁC NHẬN → trừ tồn kho dự tính
-- ============================================================
CREATE OR ALTER TRIGGER TRG_ORDER_TRU_TON_KHO
ON ORDERS
AFTER UPDATE
AS
BEGIN
    SET NOCOUNT ON;

    -- Chỉ chạy khi trạng thái chuyển sang 'da_xac_nhan'
    IF NOT EXISTS (
        SELECT 1 FROM inserted i
        JOIN deleted d ON i.ma_order = d.ma_order
        WHERE i.trang_thai = 'da_xac_nhan'
          AND d.trang_thai != 'da_xac_nhan'
    ) RETURN;

    -- Trừ tồn kho hệ thống theo định mức
    UPDATE tk
    SET tk.sl_ton_kho_he_thong = tk.sl_ton_kho_he_thong
        - (cto.so_luong * dm.so_luong_dung)
    FROM TON_KHO tk
    INNER JOIN CHI_TIET_ORDER cto
        ON cto.ma_order IN (SELECT ma_order FROM inserted WHERE trang_thai = 'da_xac_nhan')
    INNER JOIN DINH_MUC dm
        ON dm.ma_mon = cto.ma_mon AND dm.ma_nl = tk.ma_nl
    INNER JOIN ORDERS o
        ON o.ma_order = cto.ma_order AND o.ma_chi_nhanh = tk.ma_chi_nhanh;

    -- Cảnh báo nếu tồn kho hệ thống < ngưỡng cảnh báo (ghi log)
    IF EXISTS (
        SELECT 1 FROM TON_KHO
        WHERE sl_ton_kho_he_thong < nguong_canh_bao
    )
    BEGIN
        -- Có thể mở rộng: INSERT vào bảng LOG_CANH_BAO
        PRINT 'CẢNH BÁO: Một số nguyên liệu sắp hết hàng!';
    END
END;
GO

-- ============================================================
-- TRIGGER 2: Khi order BỊ HỦY → hoàn lại tồn kho dự tính
-- ============================================================
CREATE OR ALTER TRIGGER TRG_ORDER_HOAN_TON_KHO
ON ORDERS
AFTER UPDATE
AS
BEGIN
    SET NOCOUNT ON;

    -- Chỉ chạy khi trạng thái chuyển sang 'da_huy' từ 'da_xac_nhan' trở đi
    IF NOT EXISTS (
        SELECT 1 FROM inserted i
        JOIN deleted d ON i.ma_order = d.ma_order
        WHERE i.trang_thai = 'da_huy'
          AND d.trang_thai IN ('da_xac_nhan', 'dang_pha_che')
    ) RETURN;

    -- Hoàn lại tồn kho hệ thống
    UPDATE tk
    SET tk.sl_ton_kho_he_thong = tk.sl_ton_kho_he_thong
        + (cto.so_luong * dm.so_luong_dung)
    FROM TON_KHO tk
    INNER JOIN CHI_TIET_ORDER cto
        ON cto.ma_order IN (
            SELECT i.ma_order FROM inserted i
            JOIN deleted d ON i.ma_order = d.ma_order
            WHERE i.trang_thai = 'da_huy'
              AND d.trang_thai IN ('da_xac_nhan', 'dang_pha_che')
        )
    INNER JOIN DINH_MUC dm
        ON dm.ma_mon = cto.ma_mon AND dm.ma_nl = tk.ma_nl
    INNER JOIN ORDERS o
        ON o.ma_order = cto.ma_order AND o.ma_chi_nhanh = tk.ma_chi_nhanh;
END;
GO

-- ============================================================
-- TRIGGER 3: Khi phiếu nhập kho được DUYỆT → cộng tồn kho
-- ============================================================
CREATE OR ALTER TRIGGER TRG_NHAP_KHO_CAP_NHAT_TON
ON PHIEU_NHAP_KHO
AFTER UPDATE
AS
BEGIN
    SET NOCOUNT ON;

    -- Chỉ chạy khi trang_thai chuyển sang 'da_duyet'
    IF NOT EXISTS (
        SELECT 1 FROM inserted i
        JOIN deleted d ON i.ma_pnk = d.ma_pnk
        WHERE i.trang_thai = 'da_duyet'
          AND d.trang_thai = 'cho_duyet'
    ) RETURN;

    DECLARE @ma_pnk_list TABLE (ma_pnk VARCHAR(20));
    INSERT INTO @ma_pnk_list
    SELECT i.ma_pnk FROM inserted i
    JOIN deleted d ON i.ma_pnk = d.ma_pnk
    WHERE i.trang_thai = 'da_duyet' AND d.trang_thai = 'cho_duyet';

    -- Cộng vào tồn kho HỆ THỐNG và THỰC TẾ (cả hai vì đây là nhập thực)
    MERGE TON_KHO AS target
    USING (
        SELECT pnk.ma_chi_nhanh, ct.ma_nl, SUM(ct.so_luong) AS tong_nhap
        FROM CHI_TIET_NHAP_KHO ct
        JOIN PHIEU_NHAP_KHO pnk ON pnk.ma_pnk = ct.ma_pnk
        WHERE ct.ma_pnk IN (SELECT ma_pnk FROM @ma_pnk_list)
        GROUP BY pnk.ma_chi_nhanh, ct.ma_nl
    ) AS source ON target.ma_chi_nhanh = source.ma_chi_nhanh
               AND target.ma_nl = source.ma_nl
    WHEN MATCHED THEN
        UPDATE SET
            sl_ton_kho_he_thong = target.sl_ton_kho_he_thong + source.tong_nhap,
            sl_ton_kho_thuc_te  = target.sl_ton_kho_thuc_te  + source.tong_nhap
    WHEN NOT MATCHED THEN
        INSERT (ma_chi_nhanh, ma_nl, sl_ton_kho_he_thong, sl_ton_kho_thuc_te)
        VALUES (source.ma_chi_nhanh, source.ma_nl, source.tong_nhap, source.tong_nhap);
END;
GO

-- ============================================================
-- TRIGGER 4: Khi phiếu nhập kho được duyệt → cập nhật tổng giá trị phiếu
-- ============================================================
CREATE OR ALTER TRIGGER TRG_NHAP_KHO_TINH_TONG
ON CHI_TIET_NHAP_KHO
AFTER INSERT, UPDATE, DELETE
AS
BEGIN
    SET NOCOUNT ON;

    -- Lấy danh sách phiếu bị ảnh hưởng
    DECLARE @affected TABLE (ma_pnk VARCHAR(20));
    INSERT INTO @affected SELECT DISTINCT ma_pnk FROM inserted;
    INSERT INTO @affected SELECT DISTINCT ma_pnk FROM deleted
        WHERE ma_pnk NOT IN (SELECT ma_pnk FROM @affected);

    UPDATE pnk
    SET tong_gia_tri = ISNULL((
        SELECT SUM(ct.so_luong * ct.don_gia)
        FROM CHI_TIET_NHAP_KHO ct
        WHERE ct.ma_pnk = pnk.ma_pnk
    ), 0)
    FROM PHIEU_NHAP_KHO pnk
    WHERE pnk.ma_pnk IN (SELECT ma_pnk FROM @affected);
END;
GO

-- ============================================================
-- TRIGGER 5: Khi phiếu kiểm kê XÁC NHẬN → đồng bộ tồn thực tế & tính hao hụt
-- ============================================================
CREATE OR ALTER TRIGGER TRG_KIEM_KE_CAP_NHAT_TON
ON PHIEU_KIEM_KE
AFTER UPDATE
AS
BEGIN
    SET NOCOUNT ON;

    IF NOT EXISTS (
        SELECT 1 FROM inserted i
        JOIN deleted d ON i.ma_pkk = d.ma_pkk
        WHERE i.trang_thai = 'da_xac_nhan'
          AND d.trang_thai = 'nhap'
    ) RETURN;

    DECLARE @pkk_list TABLE (ma_pkk VARCHAR(20), ma_chi_nhanh VARCHAR(10));
    INSERT INTO @pkk_list
    SELECT i.ma_pkk, i.ma_chi_nhanh FROM inserted i
    JOIN deleted d ON i.ma_pkk = d.ma_pkk
    WHERE i.trang_thai = 'da_xac_nhan' AND d.trang_thai = 'nhap';

    -- Cập nhật tồn kho thực tế theo kết quả kiểm kê
    UPDATE tk
    SET
        tk.sl_ton_kho_thuc_te = ctk.sl_thuc_te,
        -- Hao hụt cost = chênh lệch âm * giá trung bình
        tk.hao_hut_cost = tk.hao_hut_cost +
            CASE WHEN (ctk.sl_thuc_te - ctk.sl_he_thong) < 0
                 THEN ABS(ctk.sl_thuc_te - ctk.sl_he_thong) * ISNULL(ctk.don_gia_tb, 0)
                 ELSE 0
            END
    FROM TON_KHO tk
    INNER JOIN CHI_TIET_KIEM_KE ctk
        ON ctk.ma_nl = tk.ma_nl
    INNER JOIN @pkk_list pk
        ON pk.ma_pkk = ctk.ma_pkk AND pk.ma_chi_nhanh = tk.ma_chi_nhanh;
END;
GO

-- ============================================================
-- TRIGGER 6: Khi thêm CHI_TIET_ORDER → snapshot giá từ bảng MON
-- (ngăn người dùng truyền giá sai)
-- ============================================================
CREATE OR ALTER TRIGGER TRG_CTO_SNAPSHOT_GIA
ON CHI_TIET_ORDER
AFTER INSERT
AS
BEGIN
    SET NOCOUNT ON;

    -- Ghi đè don_gia_tai_thoi_diem bằng giá hiện tại của món
    UPDATE cto
    SET cto.don_gia_tai_thoi_diem = m.don_gia
    FROM CHI_TIET_ORDER cto
    INNER JOIN inserted i ON i.ma_order = cto.ma_order AND i.ma_mon = cto.ma_mon
    INNER JOIN MON m ON m.ma_mon = cto.ma_mon;
END;
GO

-- ============================================================
-- TRIGGER 7: Khi hóa đơn được tạo → tự tính tổng tiền sau chiết khấu
-- ============================================================
CREATE OR ALTER TRIGGER TRG_HOA_DON_TINH_TONG
ON HOA_DON
AFTER INSERT, UPDATE
AS
BEGIN
    SET NOCOUNT ON;

    -- Chỉ tính lại tổng nếu tong_tien_truoc_ck hoặc chiet_khau thay đổi
    UPDATE hd
    SET
        hd.tong_tien_truoc_ck = ISNULL((
            SELECT SUM(cto.so_luong * cto.don_gia_tai_thoi_diem)
            FROM CHI_TIET_ORDER cto
            WHERE cto.ma_order = hd.ma_order
        ), 0),
        hd.tong_tien_sau_ck = hd.tong_tien_truoc_ck * (1 - hd.chiet_khau / 100.0)
    FROM HOA_DON hd
    INNER JOIN inserted i ON i.ma_hoa_don = hd.ma_hoa_don;
END;
GO

-- ============================================================
-- TRIGGER 8: Cập nhật trạng thái bàn khi order thay đổi
-- ============================================================
CREATE OR ALTER TRIGGER TRG_ORDER_CAP_NHAT_TRANG_THAI_BAN
ON ORDERS
AFTER INSERT, UPDATE
AS
BEGIN
    SET NOCOUNT ON;

    -- Khi order mới tạo → bàn chuyển thành 'co_khach'
    UPDATE b SET b.trang_thai = 'co_khach'
    FROM BAN b
    INNER JOIN inserted i ON i.ma_ban = b.ma_ban
    WHERE i.trang_thai NOT IN ('hoan_thanh', 'da_huy');

    -- Khi order hoàn thành/hủy → kiểm tra nếu không còn order active thì bàn về 'trong'
    UPDATE b SET b.trang_thai = 'trong'
    FROM BAN b
    INNER JOIN inserted i ON i.ma_ban = b.ma_ban
    WHERE i.trang_thai IN ('hoan_thanh', 'da_huy')
      AND NOT EXISTS (
          SELECT 1 FROM ORDERS o
          WHERE o.ma_ban = b.ma_ban
            AND o.trang_thai NOT IN ('hoan_thanh', 'da_huy')
      );
END;
GO

-- ============================================================
-- STORED PROCEDURE 1: Tạo đơn hàng mới từ QR
-- ============================================================
CREATE OR ALTER PROCEDURE SP_TAO_ORDER
    @ma_ban         VARCHAR(10),
    @ma_chi_nhanh   VARCHAR(10),
    @ten_kh         NVARCHAR(100),
    @sdt_kh         VARCHAR(15) = NULL,
    @ma_order       VARCHAR(20) OUTPUT,
    @ma_kh          VARCHAR(10) OUTPUT
AS
BEGIN
    SET NOCOUNT ON;
    BEGIN TRANSACTION;
    BEGIN TRY
        -- Tạo hoặc lấy khách hàng
        SET @ma_kh = 'KH' + FORMAT(NEXT VALUE FOR SEQ_KHACH_HANG, '000000');

        -- Kiểm tra KH theo SĐT nếu có
        IF @sdt_kh IS NOT NULL
        BEGIN
            SELECT @ma_kh = ma_kh FROM KHACH_HANG
            WHERE sdt = @sdt_kh;

            IF @ma_kh IS NULL
            BEGIN
                SET @ma_kh = 'KH' + CONVERT(VARCHAR, (SELECT COUNT(*)+1 FROM KHACH_HANG));
                INSERT INTO KHACH_HANG (ma_kh, ten_kh, sdt)
                VALUES (@ma_kh, @ten_kh, @sdt_kh);
            END
        END
        ELSE
        BEGIN
            SET @ma_kh = 'KH' + CONVERT(VARCHAR, (SELECT COUNT(*)+1 FROM KHACH_HANG));
            INSERT INTO KHACH_HANG (ma_kh, ten_kh)
            VALUES (@ma_kh, @ten_kh);
        END

        -- Tạo mã order theo timestamp
        SET @ma_order = 'ORD' + FORMAT(GETDATE(), 'yyyyMMddHHmmss')
            + RIGHT(CONVERT(VARCHAR, ABS(CHECKSUM(NEWID()))), 4);

        INSERT INTO ORDERS (ma_order, ma_ban, ma_kh, ma_chi_nhanh)
        VALUES (@ma_order, @ma_ban, @ma_kh, @ma_chi_nhanh);

        COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        ROLLBACK TRANSACTION;
        THROW;
    END CATCH
END;
GO

-- ============================================================
-- STORED PROCEDURE 2: Xác nhận đơn (trigger tự trừ kho)
-- ============================================================
CREATE OR ALTER PROCEDURE SP_XAC_NHAN_ORDER
    @ma_order   VARCHAR(20),
    @ma_nv      VARCHAR(10)
AS
BEGIN
    SET NOCOUNT ON;
    BEGIN TRANSACTION;
    BEGIN TRY
        -- Kiểm tra order tồn tại và đang ở trạng thái cho_xac_nhan
        IF NOT EXISTS (SELECT 1 FROM ORDERS WHERE ma_order = @ma_order
                       AND trang_thai = 'cho_xac_nhan')
            THROW 50001, 'Order không tồn tại hoặc không thể xác nhận.', 1;

        -- Kiểm tra tồn kho đủ không
        IF EXISTS (
            SELECT 1
            FROM CHI_TIET_ORDER cto
            JOIN DINH_MUC dm ON dm.ma_mon = cto.ma_mon
            JOIN ORDERS o ON o.ma_order = cto.ma_order
            JOIN TON_KHO tk ON tk.ma_nl = dm.ma_nl
                            AND tk.ma_chi_nhanh = o.ma_chi_nhanh
            WHERE cto.ma_order = @ma_order
              AND tk.sl_ton_kho_he_thong < (cto.so_luong * dm.so_luong_dung)
        )
            THROW 50002, 'Không đủ nguyên liệu trong kho để thực hiện đơn này.', 1;

        UPDATE ORDERS
        SET trang_thai = 'da_xac_nhan'
        WHERE ma_order = @ma_order;
        -- Trigger TRG_ORDER_TRU_TON_KHO sẽ tự động trừ kho

        COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        ROLLBACK TRANSACTION;
        THROW;
    END CATCH
END;
GO

-- ============================================================
-- STORED PROCEDURE 3: Lập hóa đơn và thanh toán
-- ============================================================
CREATE OR ALTER PROCEDURE SP_THANH_TOAN
    @ma_order           VARCHAR(20),
    @chiet_khau         DECIMAL(5,2)    = 0,
    @phuong_thuc_tt     VARCHAR(20)     = 'tien_mat',
    @ma_nv_thu_ngan     VARCHAR(10)     = NULL,
    @ma_hoa_don         VARCHAR(20) OUTPUT
AS
BEGIN
    SET NOCOUNT ON;
    BEGIN TRANSACTION;
    BEGIN TRY
        IF NOT EXISTS (SELECT 1 FROM ORDERS WHERE ma_order = @ma_order
                       AND trang_thai IN ('da_xac_nhan', 'dang_pha_che', 'da_phuc_vu'))
            THROW 50003, 'Order không ở trạng thái có thể thanh toán.', 1;

        DECLARE @ma_kh VARCHAR(10);
        SELECT @ma_kh = ma_kh FROM ORDERS WHERE ma_order = @ma_order;

        SET @ma_hoa_don = 'HD' + FORMAT(GETDATE(), 'yyyyMMddHHmmss');

        -- Tính tổng tiền
        DECLARE @tong_truoc DECIMAL(12,0);
        SELECT @tong_truoc = SUM(so_luong * don_gia_tai_thoi_diem)
        FROM CHI_TIET_ORDER WHERE ma_order = @ma_order;

        INSERT INTO HOA_DON (
            ma_hoa_don, ma_order, ma_kh,
            tong_tien_truoc_ck, chiet_khau,
            tong_tien_sau_ck,
            phuong_thuc_tt, trang_thai, ma_nv_thu_ngan
        )
        VALUES (
            @ma_hoa_don, @ma_order, @ma_kh,
            @tong_truoc, @chiet_khau,
            @tong_truoc * (1 - @chiet_khau / 100.0),
            @phuong_thuc_tt, 'da_thanh_toan', @ma_nv_thu_ngan
        );

        -- Cập nhật trạng thái order
        UPDATE ORDERS SET trang_thai = 'hoan_thanh'
        WHERE ma_order = @ma_order;

        COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        ROLLBACK TRANSACTION;
        THROW;
    END CATCH
END;
GO

-- ============================================================
-- STORED PROCEDURE 4: Báo cáo doanh thu theo chi nhánh / kỳ
-- ============================================================
CREATE OR ALTER PROCEDURE SP_BAO_CAO_DOANH_THU
    @ma_chi_nhanh   VARCHAR(10),
    @tu_ngay        DATE,
    @den_ngay       DATE
AS
BEGIN
    SET NOCOUNT ON;

    SELECT
        cn.ten_chi_nhanh,
        CAST(hd.thoi_gian_lap AS DATE)  AS ngay,
        COUNT(hd.ma_hoa_don)            AS so_hoa_don,
        SUM(hd.tong_tien_sau_ck)        AS doanh_thu,
        SUM(hd.chiet_khau * hd.tong_tien_truoc_ck / 100.0) AS tong_chiet_khau,
        AVG(hd.tong_tien_sau_ck)        AS trung_binh_hd
    FROM HOA_DON hd
    JOIN ORDERS o ON o.ma_order = hd.ma_order
    JOIN CHI_NHANH cn ON cn.ma_chi_nhanh = o.ma_chi_nhanh
    WHERE o.ma_chi_nhanh = @ma_chi_nhanh
      AND hd.trang_thai = 'da_thanh_toan'
      AND CAST(hd.thoi_gian_lap AS DATE) BETWEEN @tu_ngay AND @den_ngay
    GROUP BY cn.ten_chi_nhanh, CAST(hd.thoi_gian_lap AS DATE)
    ORDER BY ngay;
END;
GO

-- ============================================================
-- STORED PROCEDURE 5: Kiểm tra nguyên liệu sắp hết
-- ============================================================
CREATE OR ALTER PROCEDURE SP_CANH_BAO_NGUYEN_LIEU
    @ma_chi_nhanh   VARCHAR(10) = NULL   -- NULL = tất cả chi nhánh
AS
BEGIN
    SET NOCOUNT ON;

    SELECT
        cn.ten_chi_nhanh,
        nl.ten_nl,
        nl.don_vi,
        tk.sl_ton_kho_he_thong  AS ton_du_tinh,
        tk.sl_ton_kho_thuc_te   AS ton_thuc_te,
        tk.nguong_canh_bao,
        CASE
            WHEN tk.sl_ton_kho_he_thong = 0 THEN 'HẾT HÀNG'
            WHEN tk.sl_ton_kho_he_thong < tk.nguong_canh_bao THEN 'SẮP HẾT'
            ELSE 'ĐỦ HÀNG'
        END AS trang_thai_kho
    FROM TON_KHO tk
    JOIN CHI_NHANH cn ON cn.ma_chi_nhanh = tk.ma_chi_nhanh
    JOIN NGUYEN_LIEU nl ON nl.ma_nl = tk.ma_nl
    WHERE (@ma_chi_nhanh IS NULL OR tk.ma_chi_nhanh = @ma_chi_nhanh)
      AND tk.sl_ton_kho_he_thong <= tk.nguong_canh_bao
    ORDER BY trang_thai_kho, cn.ten_chi_nhanh, nl.ten_nl;
END;
GO

-- ============================================================
-- VIEWS hỗ trợ query nhanh
-- ============================================================

-- View: đơn hàng đang chờ xử lý (dùng cho màn hình dashboard bartender)
CREATE OR ALTER VIEW VW_ORDER_DANG_XU_LY AS
SELECT
    o.ma_order,
    b.so_ban,
    cn.ten_chi_nhanh,
    k.ten_kh,
    o.trang_thai,
    o.gio_order,
    COUNT(cto.ma_mon)               AS so_mon,
    SUM(cto.so_luong)               AS tong_ly
FROM ORDERS o
JOIN BAN b ON b.ma_ban = o.ma_ban
JOIN CHI_NHANH cn ON cn.ma_chi_nhanh = o.ma_chi_nhanh
LEFT JOIN KHACH_HANG k ON k.ma_kh = o.ma_kh
LEFT JOIN CHI_TIET_ORDER cto ON cto.ma_order = o.ma_order
WHERE o.trang_thai IN ('cho_xac_nhan', 'da_xac_nhan', 'dang_pha_che')
GROUP BY o.ma_order, b.so_ban, cn.ten_chi_nhanh, k.ten_kh,
         o.trang_thai, o.gio_order;
GO

-- View: menu public (chỉ món active)
CREATE OR ALTER VIEW VW_MENU_HIEN_THI AS
SELECT
    m.ma_mon,
    m.ten_mon,
    m.don_gia,
    m.mo_ta,
    m.hinh_anh,
    m.trang_thai,
    dm.ten_danh_muc
FROM MON m
JOIN DANH_MUC dm ON dm.ma_danh_muc = m.ma_danh_muc
WHERE m.trang_thai = 'active';
GO

-- View: tổng quan tồn kho theo chi nhánh
CREATE OR ALTER VIEW VW_TON_KHO_TONG_QUAN AS
SELECT
    cn.ten_chi_nhanh,
    nl.ten_nl,
    nl.don_vi,
    tk.sl_ton_kho_he_thong,
    tk.sl_ton_kho_thuc_te,
    tk.nguong_canh_bao,
    tk.hao_hut_cost,
    CASE
        WHEN tk.sl_ton_kho_he_thong = 0 THEN 'HẾT HÀNG'
        WHEN tk.sl_ton_kho_he_thong < tk.nguong_canh_bao THEN 'SẮP HẾT'
        ELSE 'ĐỦ HÀNG'
    END AS trang_thai_kho
FROM TON_KHO tk
JOIN CHI_NHANH cn ON cn.ma_chi_nhanh = tk.ma_chi_nhanh
JOIN NGUYEN_LIEU nl ON nl.ma_nl = tk.ma_nl;
GO

-- ============================================================
-- ============================================================
-- DỮ LIỆU MẪU (SEED DATA) - Dựa theo menu thực tế 8AM Coffee & Roastery
-- Địa chỉ: 34 Tăng Bạt Hổ, Hà Nội | Giờ: 8:00am - 6:00pm
-- ============================================================

-- Chi nhánh
INSERT INTO CHI_NHANH VALUES
('CN001', N'8AM Coffee & Roastery', N'34 Tăng Bạt Hổ, Hai Bà Trưng, Hà Nội', '0241234567');

-- ============================================================
-- DANH MỤC - Đúng theo phân nhóm trên menu thực tế
-- ============================================================
INSERT INTO DANH_MUC VALUES
('DM001', N'Arabica Base'),
('DM002', N'Signature'),
('DM003', N'Hand Brew'),
('DM004', N'Cold Brew'),
('DM005', N'Fine Robusta Base'),
('DM006', N'Not-café'),
('DM007', N'Eats');

-- ============================================================
-- NGUYÊN LIỆU
-- ============================================================
INSERT INTO NGUYEN_LIEU VALUES
('NL001', N'Cà phê hạt Arabica',       'gram'),
('NL002', N'Cà phê hạt Robusta Fine',  'gram'),
('NL003', N'Sữa tươi nguyên kem',       'ml'),
('NL004', N'Sữa đặc',                   'ml'),
('NL005', N'Nước lọc',                  'ml'),
('NL006', N'Kem béo (heavy cream)',      'ml'),
('NL007', N'Kem mặn (salted cream)',     'ml'),
('NL008', N'Caramel syrup',             'ml'),
('NL009', N'Mứt hồng',                  'gram'),
('NL010', N'Trứng gà',                  'cai'),
('NL011', N'Cold Brew concentrate',     'ml'),
('NL012', N'Tiramisu mix',              'gram'),
('NL013', N'Rượu Bailey',              'ml'),
('NL014', N'Bột gừng',                  'gram'),
('NL015', N'Quả mơ (mứt mơ)',          'gram'),
('NL016', N'Me (mứt me)',               'gram'),
('NL017', N'Tonic water',               'ml'),
('NL018', N'Mứt đào',                   'gram'),
('NL019', N'Chanh leo tươi',            'gram'),
('NL020', N'Sữa chua không đường',      'gram'),
('NL021', N'Ca cao nguyên chất',        'gram'),
('NL022', N'Chanh tươi',                'gram'),
('NL023', N'Xí muội',                   'gram'),
('NL024', N'Mứt chanh leo (Tiramisu)',  'gram'),
('NL025', N'Lục trà (green tea)',       'gram'),
('NL026', N'Mứt ổi',                    'gram'),
('NL027', N'Đá viên',                   'gram'),
('NL028', N'Bánh sừng bò plain',        'cai'),
('NL029', N'Bánh sừng bò socola',       'cai'),
('NL030', N'Hạt sen sấy',              'gram');

-- ============================================================
-- MÓN - 28 món đúng theo menu thực tế (giá x1000 VND)
-- ============================================================

-- DM001: Arabica Base
INSERT INTO MON VALUES
('MON001', N'Espresso',         35000, N'Double shot espresso',                                         NULL, 'DM001', 'active'),
('MON002', N'Americano',        40000, N'Espresso, nước',                                               NULL, 'DM001', 'active'),
('MON003', N'Latte',            45000, N'Espresso, sữa tươi',                                           NULL, 'DM001', 'active'),
('MON004', N':am ấm',           45000, N'Espresso, sữa tươi, sữa đặc, kem béo',                        NULL, 'DM001', 'active'),
('MON005', N'Salted Caramel',   50000, N'Espresso, sữa tươi, caramel, kem mặn',                        NULL, 'DM001', 'active'),
('MON006', N'Cà phê muối',      50000, N'Espresso, sữa tươi, mứt hồng, kem mặn, sữa đặc',             NULL, 'DM001', 'active');

-- DM002: Signature
INSERT INTO MON VALUES
('MON007', N'Cà phê trứng',    45000, N'Espresso, kem trứng, sữa đặc',                                 NULL, 'DM002', 'active'),
('MON008', N'Lady Sweet',       60000, N'Cold Brew, tiramisu, rượu Bailey, kem mặn',                   NULL, 'DM002', 'active'),
('MON009', N'Ginger Latte',     45000, N'Espresso, sữa tươi, bột gừng',                                NULL, 'DM002', 'active');

-- DM003: Hand Brew
INSERT INTO MON VALUES
('MON010', N'V60 Kenya',        60000, N'Pour over V60 - hạt Kenya, vị trái cây, chua dịu',            NULL, 'DM003', 'active'),
('MON011', N'V60 Ethiopia',     60000, N'Pour over V60 - hạt Ethiopia, hương hoa, bergamot',           NULL, 'DM003', 'active'),
('MON012', N'Origami Kenya',    60000, N'Pour over Origami - hạt Kenya',                               NULL, 'DM003', 'active'),
('MON013', N'Origami Ethiopia', 60000, N'Pour over Origami - hạt Ethiopia',                            NULL, 'DM003', 'active');

-- DM004: Cold Brew
INSERT INTO MON VALUES
('MON014', N'Cold Brew Original', 50000, N'Specialty coffee beans around the world, ủ lạnh 12h',      NULL, 'DM004', 'active'),
('MON015', N'Cold Brew Mơ',      55000, N'Cold Brew, mơ',                                              NULL, 'DM004', 'active'),
('MON016', N'Cold Brew Me',      55000, N'Cold Brew, me',                                              NULL, 'DM004', 'active'),
('MON017', N'Cold Brew Tonic',   55000, N'Cold Brew, tonic',                                           NULL, 'DM004', 'active'),
('MON018', N'Cold Brew Nhiệt Đới',55000, N'Cold Brew, mứt đào, chanh leo tươi',                       NULL, 'DM004', 'active');

-- DM005: Fine Robusta Base
INSERT INTO MON VALUES
('MON019', N'Cà phê đen',       30000, N'Double shot Espresso Robusta',                                NULL, 'DM005', 'active'),
('MON020', N'Cà phê nâu',       35000, N'Espresso Robusta, sữa đặc',                                   NULL, 'DM005', 'active'),
('MON021', N'Bạc xỉu',          40000, N'Espresso Robusta, sữa tươi, sữa đặc',                        NULL, 'DM005', 'active'),
('MON022', N'Sữa chua cà phê',  40000, N'Espresso Robusta, sữa chua, sữa đặc',                        NULL, 'DM005', 'active');

-- DM006: Not-café
INSERT INTO MON VALUES
('MON023', N'Ca cao',           40000, N'Ca cao nguyên chất',                                          NULL, 'DM006', 'active'),
('MON024', N'Chanh xí muội',    50000, N'Chanh, xí muội',                                              NULL, 'DM006', 'active'),
('MON025', N'Mứt chanh leo',    40000, N'Chanh leo, mứt Tiramisu, kem béo',                           NULL, 'DM006', 'active'),
('MON026', N'Trà ổi hồng',      40000, N'Lục trà, mứt ổi',                                            NULL, 'DM006', 'active'),
('MON027', N'Trà chanh đào',    40000, N'Lục trà, chanh, mứt đào',                                    NULL, 'DM006', 'active');

-- DM007: Eats
INSERT INTO MON VALUES
('MON028', N'Bánh sừng bò',          25000, N'Croissant bơ truyền thống',                             NULL, 'DM007', 'active'),
('MON029', N'Bánh sừng bò socola',   25000, N'Croissant nhân socola',                                 NULL, 'DM007', 'active'),
('MON030', N'Hạt sen sấy',           50000, N'Hạt sen sấy giòn nguyên chất',                          NULL, 'DM007', 'active');

-- ============================================================
-- ĐỊNH MỨC NGUYÊN LIỆU (gram / ml mỗi phần)
-- ============================================================

-- MON001 Espresso: 18g Arabica + 30ml nước
INSERT INTO DINH_MUC VALUES
('MON001','NL001', 18.00, N'2 shot espresso Arabica'),
('MON001','NL005', 30.00, N'nước pha espresso');

-- MON002 Americano: 18g Arabica + 150ml nước
INSERT INTO DINH_MUC VALUES
('MON002','NL001', 18.00, N'2 shot espresso'),
('MON002','NL005', 150.00, N'nước nóng pha loãng');

-- MON003 Latte: 18g Arabica + 180ml sữa tươi
INSERT INTO DINH_MUC VALUES
('MON003','NL001', 18.00, N'1 shot espresso'),
('MON003','NL003', 180.00, N'sữa tươi steam');

-- MON004 :am ấm: 18g Arabica + 120ml sữa tươi + 20ml sữa đặc + 30ml kem béo
INSERT INTO DINH_MUC VALUES
('MON004','NL001', 18.00, N'1 shot espresso'),
('MON004','NL003', 120.00, N'sữa tươi'),
('MON004','NL004', 20.00, N'sữa đặc'),
('MON004','NL006', 30.00, N'kem béo');

-- MON005 Salted Caramel: 18g Arabica + 150ml sữa tươi + 15ml caramel + 30ml kem mặn
INSERT INTO DINH_MUC VALUES
('MON005','NL001', 18.00, N'1 shot espresso'),
('MON005','NL003', 150.00, N'sữa tươi'),
('MON005','NL008', 15.00, N'caramel syrup'),
('MON005','NL007', 30.00, N'kem mặn topping');

-- MON006 Cà phê muối: 18g Arabica + 120ml sữa tươi + 20g mứt hồng + 30ml kem mặn + 20ml sữa đặc
INSERT INTO DINH_MUC VALUES
('MON006','NL001', 18.00, N'1 shot espresso'),
('MON006','NL003', 120.00, N'sữa tươi'),
('MON006','NL009', 20.00, N'mứt hồng'),
('MON006','NL007', 30.00, N'kem mặn'),
('MON006','NL004', 20.00, N'sữa đặc');

-- MON007 Cà phê trứng: 18g Arabica + 1 trứng + 20ml sữa đặc
INSERT INTO DINH_MUC VALUES
('MON007','NL001', 18.00, N'espresso'),
('MON007','NL010', 1.00,  N'lòng đỏ trứng đánh kem'),
('MON007','NL004', 20.00, N'sữa đặc trong kem trứng');

-- MON008 Lady Sweet: 150ml cold brew + 20g tiramisu + 15ml Bailey + 30ml kem mặn
INSERT INTO DINH_MUC VALUES
('MON008','NL011', 150.00, N'cold brew concentrate'),
('MON008','NL012', 20.00,  N'tiramisu mix'),
('MON008','NL013', 15.00,  N'rượu Bailey'),
('MON008','NL007', 30.00,  N'kem mặn topping');

-- MON009 Ginger Latte: 18g Arabica + 180ml sữa tươi + 5g bột gừng
INSERT INTO DINH_MUC VALUES
('MON009','NL001', 18.00,  N'1 shot espresso'),
('MON009','NL003', 180.00, N'sữa tươi steam'),
('MON009','NL014', 5.00,   N'bột gừng');

-- MON010 V60 Kenya: 15g Arabica + 230ml nước
INSERT INTO DINH_MUC VALUES
('MON010','NL001', 15.00,  N'cà phê Kenya xay thô'),
('MON010','NL005', 230.00, N'nước 93°C');

-- MON011 V60 Ethiopia: 15g Arabica + 230ml nước
INSERT INTO DINH_MUC VALUES
('MON011','NL001', 15.00,  N'cà phê Ethiopia xay thô'),
('MON011','NL005', 230.00, N'nước 93°C');

-- MON012 Origami Kenya: 15g Arabica + 230ml nước
INSERT INTO DINH_MUC VALUES
('MON012','NL001', 15.00,  N'cà phê Kenya xay vừa'),
('MON012','NL005', 230.00, N'nước 93°C');

-- MON013 Origami Ethiopia: 15g Arabica + 230ml nước
INSERT INTO DINH_MUC VALUES
('MON013','NL001', 15.00,  N'cà phê Ethiopia xay vừa'),
('MON013','NL005', 230.00, N'nước 93°C');

-- MON014 Cold Brew Original: 80g Arabica + 300ml nước (ủ 12h)
INSERT INTO DINH_MUC VALUES
('MON014','NL001', 80.00,  N'cà phê xay thô ủ lạnh 12h'),
('MON014','NL005', 300.00, N'nước lạnh ủ cold brew');

-- MON015 Cold Brew Mơ: 150ml cold brew + 25g mứt mơ
INSERT INTO DINH_MUC VALUES
('MON015','NL011', 150.00, N'cold brew'),
('MON015','NL015', 25.00,  N'mứt mơ');

-- MON016 Cold Brew Me: 150ml cold brew + 20g mứt me
INSERT INTO DINH_MUC VALUES
('MON016','NL011', 150.00, N'cold brew'),
('MON016','NL016', 20.00,  N'mứt me');

-- MON017 Cold Brew Tonic: 100ml cold brew + 150ml tonic
INSERT INTO DINH_MUC VALUES
('MON017','NL011', 100.00, N'cold brew'),
('MON017','NL017', 150.00, N'tonic water');

-- MON018 Cold Brew Nhiệt Đới: 120ml cold brew + 20g mứt đào + 30g chanh leo
INSERT INTO DINH_MUC VALUES
('MON018','NL011', 120.00, N'cold brew'),
('MON018','NL018', 20.00,  N'mứt đào'),
('MON018','NL019', 30.00,  N'chanh leo tươi');

-- MON019 Cà phê đen: 18g Robusta + 30ml nước
INSERT INTO DINH_MUC VALUES
('MON019','NL002', 18.00, N'2 shot espresso Robusta'),
('MON019','NL005', 30.00, N'nước pha');

-- MON020 Cà phê nâu: 18g Robusta + 25ml sữa đặc
INSERT INTO DINH_MUC VALUES
('MON020','NL002', 18.00, N'shot espresso Robusta'),
('MON020','NL004', 25.00, N'sữa đặc');

-- MON021 Bạc xỉu: 12g Robusta + 150ml sữa tươi + 20ml sữa đặc
INSERT INTO DINH_MUC VALUES
('MON021','NL002', 12.00,  N'shot Robusta nhẹ'),
('MON021','NL003', 150.00, N'sữa tươi'),
('MON021','NL004', 20.00,  N'sữa đặc');

-- MON022 Sữa chua cà phê: 18g Robusta + 80g sữa chua + 20ml sữa đặc
INSERT INTO DINH_MUC VALUES
('MON022','NL002', 18.00, N'shot espresso Robusta'),
('MON022','NL020', 80.00, N'sữa chua không đường'),
('MON022','NL004', 20.00, N'sữa đặc');

-- MON023 Ca cao: 20g bột ca cao + 200ml sữa tươi
INSERT INTO DINH_MUC VALUES
('MON023','NL021', 20.00,  N'ca cao nguyên chất'),
('MON023','NL003', 200.00, N'sữa tươi nóng/lạnh');

-- MON024 Chanh xí muội: 50g chanh + 15g xí muội
INSERT INTO DINH_MUC VALUES
('MON024','NL022', 50.00, N'chanh tươi vắt'),
('MON024','NL023', 15.00, N'xí muội');

-- MON025 Mứt chanh leo: 40g mứt chanh leo/tiramisu + 30ml kem béo
INSERT INTO DINH_MUC VALUES
('MON025','NL024', 40.00, N'mứt chanh leo kiểu Tiramisu'),
('MON025','NL006', 30.00, N'kem béo');

-- MON026 Trà ổi hồng: 5g lục trà + 30g mứt ổi + 200ml nước
INSERT INTO DINH_MUC VALUES
('MON026','NL025', 5.00,   N'lục trà pha'),
('MON026','NL026', 30.00,  N'mứt ổi hồng'),
('MON026','NL005', 200.00, N'nước pha trà');

-- MON027 Trà chanh đào: 5g lục trà + 30g chanh + 25g mứt đào + 200ml nước
INSERT INTO DINH_MUC VALUES
('MON027','NL025', 5.00,   N'lục trà'),
('MON027','NL022', 30.00,  N'chanh tươi'),
('MON027','NL018', 25.00,  N'mứt đào'),
('MON027','NL005', 200.00, N'nước pha trà');

-- MON028 Bánh sừng bò: 1 cái
INSERT INTO DINH_MUC VALUES
('MON028','NL028', 1.00, N'1 chiếc bánh sừng bò bơ');

-- MON029 Bánh sừng bò socola: 1 cái
INSERT INTO DINH_MUC VALUES
('MON029','NL029', 1.00, N'1 chiếc bánh sừng bò socola');

-- MON030 Hạt sen sấy: 50g/phần
INSERT INTO DINH_MUC VALUES
('MON030','NL030', 50.00, N'hạt sen sấy giòn 1 phần');

-- ============================================================
-- BÀN
-- ============================================================
INSERT INTO BAN VALUES
('B001', 1,  N'Tầng 1 - Cửa sổ phố',      'trong', 'CN001'),
('B002', 2,  N'Tầng 1 - Giữa',             'trong', 'CN001'),
('B003', 3,  N'Tầng 1 - Góc trong',        'trong', 'CN001'),
('B004', 4,  N'Tầng 1 - Quầy bar',         'trong', 'CN001'),
('B005', 5,  N'Tầng 2 - Ban công ngoài',   'trong', 'CN001'),
('B006', 6,  N'Tầng 2 - Sofa góc',         'trong', 'CN001'),
('B007', 7,  N'Tầng 2 - Bàn dài',         'trong', 'CN001'),
('B008', 8,  N'Tầng 2 - Cạnh cầu thang',  'trong', 'CN001');

-- ============================================================
-- TỒN KHO BAN ĐẦU (CN001)
-- ============================================================
INSERT INTO TON_KHO (ma_chi_nhanh, ma_nl, sl_ton_kho_he_thong, sl_ton_kho_thuc_te, nguong_canh_bao)
VALUES
('CN001','NL001', 5000.00,  5000.00,  500.00),   -- Arabica
('CN001','NL002', 3000.00,  3000.00,  300.00),   -- Robusta
('CN001','NL003', 15000.00, 15000.00, 1500.00),  -- Sữa tươi
('CN001','NL004', 5000.00,  5000.00,  500.00),   -- Sữa đặc
('CN001','NL005', 50000.00, 50000.00, 5000.00),  -- Nước lọc
('CN001','NL006', 3000.00,  3000.00,  300.00),   -- Kem béo
('CN001','NL007', 3000.00,  3000.00,  300.00),   -- Kem mặn
('CN001','NL008', 2000.00,  2000.00,  200.00),   -- Caramel
('CN001','NL009', 1000.00,  1000.00,  100.00),   -- Mứt hồng
('CN001','NL010', 100.00,   100.00,   20.00),    -- Trứng gà (cái)
('CN001','NL011', 10000.00, 10000.00, 1000.00),  -- Cold Brew concentrate
('CN001','NL012', 2000.00,  2000.00,  200.00),   -- Tiramisu mix
('CN001','NL013', 1000.00,  1000.00,  100.00),   -- Bailey
('CN001','NL014', 500.00,   500.00,   50.00),    -- Bột gừng
('CN001','NL015', 2000.00,  2000.00,  200.00),   -- Mứt mơ
('CN001','NL016', 1500.00,  1500.00,  150.00),   -- Mứt me
('CN001','NL017', 5000.00,  5000.00,  500.00),   -- Tonic water
('CN001','NL018', 2000.00,  2000.00,  200.00),   -- Mứt đào
('CN001','NL019', 1500.00,  1500.00,  150.00),   -- Chanh leo tươi
('CN001','NL020', 5000.00,  5000.00,  500.00),   -- Sữa chua
('CN001','NL021', 2000.00,  2000.00,  200.00),   -- Ca cao
('CN001','NL022', 3000.00,  3000.00,  300.00),   -- Chanh tươi
('CN001','NL023', 1000.00,  1000.00,  100.00),   -- Xí muội
('CN001','NL024', 2000.00,  2000.00,  200.00),   -- Mứt chanh leo tiramisu
('CN001','NL025', 500.00,   500.00,   50.00),    -- Lục trà
('CN001','NL026', 1500.00,  1500.00,  150.00),   -- Mứt ổi
('CN001','NL027', 10000.00, 10000.00, 1000.00),  -- Đá viên
('CN001','NL028', 50.00,    50.00,    10.00),    -- Bánh sừng bò (cái)
('CN001','NL029', 50.00,    50.00,    10.00),    -- Bánh sừng bò socola (cái)
('CN001','NL030', 3000.00,  3000.00,  300.00);   -- Hạt sen sấy

-- ============================================================
-- NHÀ CUNG CẤP
-- ============================================================
INSERT INTO NHA_CUNG_CAP VALUES
('NCC001', N'Phúc Sinh Corporation',  N'TP. Hồ Chí Minh', '0281234567', 'info@phucsinh.com'),
('NCC002', N'Dalat Milk',             N'Đà Lạt, Lâm Đồng', '0263456789', 'dalatmilk@dl.vn'),
('NCC003', N'Khánh Hòa Salanganes',   N'Khánh Hòa', '0258765432', NULL),
('NCC004', N'Thực phẩm Đức Việt',     N'Hà Nội', '0241112222', 'ducviet@hn.vn');

-- Nhân viên mẫu
INSERT INTO NHAN_VIEN VALUES
('NV001', N'Nguyễn Văn An',  '0901234567', '001234567890', N'Hà Nội', 'CN001'),
('NV002', N'Trần Thị Bình',  '0912345678', '001234567891', N'Hà Nội', 'CN001'),
('NV003', N'Lê Văn Cường',   '0923456789', '001234567892', N'Hà Nội', 'CN001');

-- Tài khoản mẫu (mật khẩu hash bcrypt của "Admin@123")
INSERT INTO TAI_KHOAN VALUES
('TK001', 'admin_8am',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'quan_ly',    'active', 'NV001'),
('TK002', 'bartender01',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'bartender',  'active', 'NV002'),
('TK003', 'staff01',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'nhan_vien',  'active', 'NV003');

PRINT N'';
PRINT N'✅ Database 8AMCoffee đã được tạo thành công!';
PRINT N'';
PRINT N'📋 Thống kê:';
PRINT N'   - 18 bảng với đầy đủ ràng buộc (CHECK, FK, UNIQUE, DEFAULT)';
PRINT N'   - 8 TRIGGERS tự động (tồn kho, trạng thái bàn, tính tiền)';
PRINT N'   - 5 STORED PROCEDURES (tạo order, xác nhận, thanh toán, báo cáo, cảnh báo kho)';
PRINT N'   - 3 VIEWS (dashboard order, menu public, tổng quan kho)';
PRINT N'   - 6 INDEXES tối ưu hiệu năng';
PRINT N'';
PRINT N'🍵 Menu thực tế 8AM Coffee & Roastery:';
PRINT N'   - 30 món | 7 danh mục | 30 nguyên liệu';
PRINT N'   - Arabica Base (6 món) | Signature (3 món)';
PRINT N'   - Hand Brew (4 món) | Cold Brew (5 món)';
PRINT N'   - Fine Robusta Base (4 món) | Not-café (5 món) | Eats (3 món)';
PRINT N'   - Định mức nguyên liệu đầy đủ cho 30 món';
GO
