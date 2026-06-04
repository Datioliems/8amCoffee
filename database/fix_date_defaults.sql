-- =====================================================================
--  FIX lỗi 1364 "Field 'ngay_nk' doesn't have a default value" (và tương tự)
--
--  Nguyên nhân: cột DATE/TIME tạo bằng useCurrent() → MySQL không cho
--  CURRENT_TIMESTAMP làm default của DATE/TIME → trên DB strict-mode (Railway)
--  cột không có default → insert thiếu cột là lỗi.
--
--  CÁCH CHẠY (chạy trên CẢ 2 DB: DB chính 'railway'/'8amcoffee' VÀ DB arduino):
--    mysql -u <user> -p <ten_db> < database/fix_date_defaults.sql
--  hoặc dán vào phpMyAdmin / Adminer / Railway Query.
--
--  Yêu cầu: MySQL 8.0.13+ hoặc MariaDB 10.2.7+ (hỗ trợ default biểu thức).
-- =====================================================================

SET NAMES utf8mb4;

ALTER TABLE `ORDERS`         MODIFY `ngay_order` DATE NULL DEFAULT (CURDATE());
ALTER TABLE `ORDERS`         MODIFY `gio_order`  TIME NULL DEFAULT (CURTIME());
ALTER TABLE `PHIEU_NHAP_KHO` MODIFY `ngay_nk`    DATE NULL DEFAULT (CURDATE());
ALTER TABLE `PHIEU_KIEM_KE`  MODIFY `ngay_kk`    DATE NULL DEFAULT (CURDATE());

-- ---------------------------------------------------------------------
-- Nếu engine CŨ báo lỗi cú pháp ở "DEFAULT (CURDATE())", chạy bản nới NULL
-- (bỏ comment 4 dòng dưới, ứng dụng đã tự điền ngày khi tạo phiếu):
-- ALTER TABLE `ORDERS`         MODIFY `ngay_order` DATE NULL;
-- ALTER TABLE `ORDERS`         MODIFY `gio_order`  TIME NULL;
-- ALTER TABLE `PHIEU_NHAP_KHO` MODIFY `ngay_nk`    DATE NULL;
-- ALTER TABLE `PHIEU_KIEM_KE`  MODIFY `ngay_kk`    DATE NULL;
