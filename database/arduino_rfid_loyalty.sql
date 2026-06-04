-- =====================================================================
--  8AM COFFEE — Cơ chế ARDUINO + RFID + TÍCH ĐIỂM (loyalty)
--  File bổ sung để ĐỒNG BỘ vào DB "hệ thống + arduino" hiện có.
--
--  Cách dùng:
--    mysql -u <user> -p 8amcoffee_arduino < database/arduino_rfid_loyalty.sql
--  (hoặc import qua phpMyAdmin / Adminer)
--
--  Yêu cầu: DB đích đã có các bảng gốc CHI_NHANH, KHACH_HANG, ORDERS, HOA_DON
--  (đây là phần "đồng bộ schema mới nhất"). Nếu DB của bạn còn thiếu các bảng
--  mới hơn (THANH_TOAN_ONLINE, EMAIL_LOG, YEU_CAU_DOI_BAN, NHAT_KY_DANG_NHAP,
--  SCAN_LOG, cột bảo mật/2FA, mã hóa PII...) thì chạy `php artisan migrate`
--  trên DB đó trước để lên đúng schema mới nhất, rồi mới chạy file này.
--
--  Engine InnoDB + utf8mb4 để khớp toàn hệ thống. Khóa ngoại bật đầy đủ.
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- 1) THIET_BI_ARDUINO — đầu đọc RFID (Arduino/ESP) tại quầy/chi nhánh
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `THIET_BI_ARDUINO` (
    `ma_thiet_bi`      VARCHAR(20)  NOT NULL,
    `ten_thiet_bi`     VARCHAR(100) NOT NULL,
    `ma_chi_nhanh`     VARCHAR(10)  NOT NULL,
    `vi_tri`           VARCHAR(100) NULL,
    `api_key_hash`     CHAR(64)     NULL COMMENT 'sha256(token) — thiết bị gửi token để xác thực',
    `trang_thai`       VARCHAR(20)  NOT NULL DEFAULT 'hoat_dong' COMMENT 'hoat_dong | tam_dung',
    `lan_ket_noi_cuoi` DATETIME     NULL,
    `ip_cuoi`          VARCHAR(45)  NULL,
    `tao_luc`          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`ma_thiet_bi`),
    KEY `idx_tbard_cn` (`ma_chi_nhanh`),
    CONSTRAINT `fk_tbard_cn` FOREIGN KEY (`ma_chi_nhanh`) REFERENCES `CHI_NHANH` (`ma_chi_nhanh`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 2) THE_THANH_VIEN — thẻ RFID thành viên (tái sử dụng nhiều lần)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `THE_THANH_VIEN` (
    `ma_the`             VARCHAR(20) NOT NULL,
    `uid_rfid`           VARCHAR(32) NOT NULL COMMENT 'UID vật lý thẻ (hex) do đầu đọc gửi',
    `ma_kh`              VARCHAR(10) NULL COMMENT 'chủ thẻ; NULL = thẻ trắng chưa phát',
    `diem_hien_tai`      INT         NOT NULL DEFAULT 0,
    `tong_diem_tich_luy` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `hang_the`           VARCHAR(20) NOT NULL DEFAULT 'thuong' COMMENT 'thuong | bac | vang | kim_cuong',
    `trang_thai`         VARCHAR(20) NOT NULL DEFAULT 'hoat_dong' COMMENT 'hoat_dong | khoa | mat',
    `ngay_phat`          DATE        NULL,
    `ma_chi_nhanh`       VARCHAR(10) NULL,
    `ghi_chu`            VARCHAR(255) NULL,
    `tao_luc`            DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`ma_the`),
    UNIQUE KEY `uq_the_uid` (`uid_rfid`),
    KEY `idx_the_kh` (`ma_kh`),
    KEY `idx_the_tt` (`trang_thai`),
    CONSTRAINT `fk_the_kh` FOREIGN KEY (`ma_kh`) REFERENCES `KHACH_HANG` (`ma_kh`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_the_cn` FOREIGN KEY (`ma_chi_nhanh`) REFERENCES `CHI_NHANH` (`ma_chi_nhanh`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 3) GIAO_DICH_DIEM — sổ cái điểm (số dư = SUM(so_diem))
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `GIAO_DICH_DIEM` (
    `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ma_the`            VARCHAR(20) NOT NULL,
    `ma_kh`             VARCHAR(10) NULL,
    `loai`              VARCHAR(20) NOT NULL COMMENT 'khoi_tao | tich_diem | doi_diem | dieu_chinh | het_han',
    `so_diem`           INT         NOT NULL COMMENT 'CÓ DẤU: + tích / - đổi',
    `so_diem_sau`       INT         NULL,
    `so_tien_lien_quan` DECIMAL(12,0) NULL,
    `ma_order`          VARCHAR(20) NULL,
    `ma_hoa_don`        VARCHAR(20) NULL,
    `ma_thiet_bi`       VARCHAR(20) NULL,
    `mo_ta`             VARCHAR(255) NULL,
    `thoi_gian`         DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_gd_the_tg` (`ma_the`, `thoi_gian`),
    KEY `idx_gd_kh` (`ma_kh`),
    KEY `idx_gd_loai` (`loai`),
    CONSTRAINT `fk_gd_the` FOREIGN KEY (`ma_the`) REFERENCES `THE_THANH_VIEN` (`ma_the`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_gd_kh` FOREIGN KEY (`ma_kh`) REFERENCES `KHACH_HANG` (`ma_kh`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_gd_order` FOREIGN KEY (`ma_order`) REFERENCES `ORDERS` (`ma_order`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_gd_hd` FOREIGN KEY (`ma_hoa_don`) REFERENCES `HOA_DON` (`ma_hoa_don`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_gd_tb` FOREIGN KEY (`ma_thiet_bi`) REFERENCES `THIET_BI_ARDUINO` (`ma_thiet_bi`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 4) LICH_SU_QUET_THE — log mọi lần quẹt thẻ từ Arduino (kể cả thẻ lạ)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `LICH_SU_QUET_THE` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uid_rfid`     VARCHAR(32) NOT NULL,
    `ma_the`       VARCHAR(20) NULL,
    `ma_thiet_bi`  VARCHAR(20) NULL,
    `ma_chi_nhanh` VARCHAR(10) NULL,
    `hanh_dong`    VARCHAR(30) NOT NULL DEFAULT 'nhan_dien' COMMENT 'nhan_dien | tich_diem | doi_diem | phat_the | khong_xac_dinh',
    `ket_qua`      VARCHAR(20) NOT NULL DEFAULT 'thanh_cong' COMMENT 'thanh_cong | that_bai',
    `chi_tiet`     VARCHAR(255) NULL,
    `ip`           VARCHAR(45) NULL,
    `thoi_gian`    DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_qt_tb_tg` (`ma_thiet_bi`, `thoi_gian`),
    KEY `idx_qt_uid` (`uid_rfid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- DỮ LIỆU MẪU (demo) — 1 đầu đọc + 3 thẻ trắng để test quẹt
--   * api_key_hash = SHA2('ARD-DEMO-KEY-001', 256) — token thật chỉ giữ ở thiết bị.
--   * Thẻ trắng (ma_kh = NULL): quẹt lần đầu sẽ dùng để PHÁT THẺ cho khách.
-- ---------------------------------------------------------------------
INSERT INTO `THIET_BI_ARDUINO` (`ma_thiet_bi`,`ten_thiet_bi`,`ma_chi_nhanh`,`vi_tri`,`api_key_hash`,`trang_thai`)
SELECT 'ARD001','Đầu đọc RFID quầy thu ngân', (SELECT `ma_chi_nhanh` FROM `CHI_NHANH` ORDER BY `ma_chi_nhanh` LIMIT 1),
       'Quầy thu ngân', SHA2('ARD-DEMO-KEY-001', 256), 'hoat_dong'
WHERE NOT EXISTS (SELECT 1 FROM `THIET_BI_ARDUINO` WHERE `ma_thiet_bi`='ARD001');

INSERT INTO `THE_THANH_VIEN` (`ma_the`,`uid_rfid`,`ma_kh`,`hang_the`,`trang_thai`,`ghi_chu`)
SELECT * FROM (
    SELECT 'TV000001' AS a,'04A1B2C3' AS b, NULL AS c,'thuong' AS d,'hoat_dong' AS e,'Thẻ trắng demo' AS f
    UNION ALL SELECT 'TV000002','04D4E5F6', NULL,'thuong','hoat_dong','Thẻ trắng demo'
    UNION ALL SELECT 'TV000003','0411AA22', NULL,'thuong','hoat_dong','Thẻ trắng demo'
) t
WHERE NOT EXISTS (SELECT 1 FROM `THE_THANH_VIEN` WHERE `ma_the` IN ('TV000001','TV000002','TV000003'));

-- Hết. Sau khi import: dùng `php artisan loyalty:issue-card <uid> <sdt>` để phát thẻ
-- (tự tính điểm khởi tạo từ tổng chi tiêu của khách qua blind index sdt_hash).
