-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: 8am_coffee
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `ban`
--

DROP TABLE IF EXISTS `ban`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ban` (
  `ma_ban` varchar(10) NOT NULL,
  `so_ban` int(10) unsigned NOT NULL,
  `so_ghe` tinyint(3) unsigned NOT NULL DEFAULT 4,
  `vi_tri` varchar(50) DEFAULT NULL,
  `trang_thai` varchar(10) NOT NULL DEFAULT 'trong',
  `ma_chi_nhanh` varchar(10) NOT NULL,
  PRIMARY KEY (`ma_ban`),
  UNIQUE KEY `uq_ban_so_chi_nhanh` (`so_ban`,`ma_chi_nhanh`),
  KEY `fk_ban_chi_nhanh` (`ma_chi_nhanh`),
  CONSTRAINT `fk_ban_chi_nhanh` FOREIGN KEY (`ma_chi_nhanh`) REFERENCES `chi_nhanh` (`ma_chi_nhanh`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ban`
--

LOCK TABLES `ban` WRITE;
/*!40000 ALTER TABLE `ban` DISABLE KEYS */;
INSERT INTO `ban` VALUES ('B001',1,4,'Tầng 1 - Cửa sổ phố','trong','CN001'),('B002',2,4,'Tầng 1 - Giữa','trong','CN001'),('B003',3,4,'Tầng 1 - Góc trong','trong','CN001'),('B004',4,4,'Tầng 1 - Quầy bar','trong','CN001'),('B005',5,4,'Tầng 2 - Ban công ngoài','trong','CN001'),('B006',6,4,'Tầng 2 - Sofa góc','trong','CN001'),('B007',7,4,'Tầng 2 - Bàn dài','trong','CN001'),('B008',8,4,'Tầng 2 - Cạnh cầu thang','trong','CN001');
/*!40000 ALTER TABLE `ban` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chi_nhanh`
--

DROP TABLE IF EXISTS `chi_nhanh`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chi_nhanh` (
  `ma_chi_nhanh` varchar(10) NOT NULL,
  `ten_chi_nhanh` varchar(100) NOT NULL,
  `dia_chi` varchar(255) DEFAULT NULL,
  `sdt` varchar(15) DEFAULT NULL,
  PRIMARY KEY (`ma_chi_nhanh`),
  UNIQUE KEY `uq_chi_nhanh_ten` (`ten_chi_nhanh`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chi_nhanh`
--

LOCK TABLES `chi_nhanh` WRITE;
/*!40000 ALTER TABLE `chi_nhanh` DISABLE KEYS */;
INSERT INTO `chi_nhanh` VALUES ('CN001','8AM Coffee & Roastery','34 Tăng Bạt Hổ, Hai Bà Trưng, Hà Nội','0241234567');
/*!40000 ALTER TABLE `chi_nhanh` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chi_tiet_kiem_ke`
--

DROP TABLE IF EXISTS `chi_tiet_kiem_ke`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chi_tiet_kiem_ke` (
  `ma_pkk` varchar(20) NOT NULL,
  `ma_nl` varchar(10) NOT NULL,
  `sl_he_thong` decimal(12,2) NOT NULL DEFAULT 0.00,
  `sl_thuc_te` decimal(12,2) NOT NULL,
  `chenh_lech` decimal(12,2) GENERATED ALWAYS AS (`sl_thuc_te` - `sl_he_thong`) STORED,
  `don_gia_tb` decimal(12,0) DEFAULT NULL,
  PRIMARY KEY (`ma_pkk`,`ma_nl`),
  KEY `fk_ctkk_nguyen_lieu` (`ma_nl`),
  CONSTRAINT `fk_ctkk_nguyen_lieu` FOREIGN KEY (`ma_nl`) REFERENCES `nguyen_lieu` (`ma_nl`),
  CONSTRAINT `fk_ctkk_phieu` FOREIGN KEY (`ma_pkk`) REFERENCES `phieu_kiem_ke` (`ma_pkk`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chi_tiet_kiem_ke`
--

LOCK TABLES `chi_tiet_kiem_ke` WRITE;
/*!40000 ALTER TABLE `chi_tiet_kiem_ke` DISABLE KEYS */;
INSERT INTO `chi_tiet_kiem_ke` VALUES ('PKK20260531192516','NL028',0.00,1312312.00,1312312.00,2112);
/*!40000 ALTER TABLE `chi_tiet_kiem_ke` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chi_tiet_nhap_kho`
--

DROP TABLE IF EXISTS `chi_tiet_nhap_kho`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chi_tiet_nhap_kho` (
  `ma_pnk` varchar(20) NOT NULL,
  `ma_nl` varchar(10) NOT NULL,
  `so_luong` decimal(12,2) NOT NULL,
  `don_gia` decimal(12,0) NOT NULL,
  `tong_tien` decimal(15,0) GENERATED ALWAYS AS (`so_luong` * `don_gia`) STORED,
  PRIMARY KEY (`ma_pnk`,`ma_nl`),
  KEY `fk_ctnk_nguyen_lieu` (`ma_nl`),
  CONSTRAINT `fk_ctnk_nguyen_lieu` FOREIGN KEY (`ma_nl`) REFERENCES `nguyen_lieu` (`ma_nl`),
  CONSTRAINT `fk_ctnk_phieu` FOREIGN KEY (`ma_pnk`) REFERENCES `phieu_nhap_kho` (`ma_pnk`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chi_tiet_nhap_kho`
--

LOCK TABLES `chi_tiet_nhap_kho` WRITE;
/*!40000 ALTER TABLE `chi_tiet_nhap_kho` DISABLE KEYS */;
INSERT INTO `chi_tiet_nhap_kho` VALUES ('PNK20260531192232','NL001',1000000.00,5000,5000000000),('PNK20260531192232','NL003',10000.00,100,1000000),('PNK20260531192232','NL004',10000.00,1000,10000000),('PNK20260531192232','NL028',100.00,500,50000),('PNK20260531193535','NL006',1000.00,20,20000),('PNK20260531193653','NL006',10000.00,12,120000);
/*!40000 ALTER TABLE `chi_tiet_nhap_kho` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `TRG_NHAP_KHO_TINH_TONG_INSERT`
AFTER INSERT ON `CHI_TIET_NHAP_KHO`
FOR EACH ROW
BEGIN
    UPDATE `PHIEU_NHAP_KHO`
    SET tong_gia_tri = (
        SELECT COALESCE(SUM(so_luong * don_gia), 0)
        FROM `CHI_TIET_NHAP_KHO`
        WHERE ma_pnk = NEW.ma_pnk
    )
    WHERE ma_pnk = NEW.ma_pnk;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `TRG_NHAP_KHO_TINH_TONG_UPDATE`
AFTER UPDATE ON `CHI_TIET_NHAP_KHO`
FOR EACH ROW
BEGIN
    UPDATE `PHIEU_NHAP_KHO`
    SET tong_gia_tri = (
        SELECT COALESCE(SUM(so_luong * don_gia), 0)
        FROM `CHI_TIET_NHAP_KHO`
        WHERE ma_pnk = NEW.ma_pnk
    )
    WHERE ma_pnk = NEW.ma_pnk;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `TRG_NHAP_KHO_TINH_TONG_DELETE`
AFTER DELETE ON `CHI_TIET_NHAP_KHO`
FOR EACH ROW
BEGIN
    UPDATE `PHIEU_NHAP_KHO`
    SET tong_gia_tri = (
        SELECT COALESCE(SUM(so_luong * don_gia), 0)
        FROM `CHI_TIET_NHAP_KHO`
        WHERE ma_pnk = OLD.ma_pnk
    )
    WHERE ma_pnk = OLD.ma_pnk;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `chi_tiet_order`
--

DROP TABLE IF EXISTS `chi_tiet_order`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chi_tiet_order` (
  `ma_order` varchar(20) NOT NULL,
  `ma_mon` varchar(10) NOT NULL,
  `so_luong` int(10) unsigned NOT NULL,
  `don_gia_tai_thoi_diem` decimal(12,0) NOT NULL,
  `ghi_chu` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`ma_order`,`ma_mon`),
  KEY `fk_cto_mon` (`ma_mon`),
  CONSTRAINT `fk_cto_mon` FOREIGN KEY (`ma_mon`) REFERENCES `mon` (`ma_mon`),
  CONSTRAINT `fk_cto_orders` FOREIGN KEY (`ma_order`) REFERENCES `orders` (`ma_order`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chi_tiet_order`
--

LOCK TABLES `chi_tiet_order` WRITE;
/*!40000 ALTER TABLE `chi_tiet_order` DISABLE KEYS */;
INSERT INTO `chi_tiet_order` VALUES ('ORD26053113581893','MON004',1,45000,'Nhiệt độ: Ít đá | Độ ngọt: 100% | Topping: Shot espresso | Topping: Kem mặn | Topping: Caramel | Topping: Sữa tươi'),('ORD26053114002470','MON004',1,45000,NULL),('ORD26053114055328','MON002',1,40000,'Nhiệt độ: Nóng | Độ ngọt: 30%'),('ORD26053114101990','MON001',1,35000,'Nhiệt độ: Đá | Độ ngọt: 30%'),('ORD26053114101990','MON004',1,45000,'Nhiệt độ: Đá | Độ ngọt: 30%'),('ORD26053114260685','MON004',1,45000,'Nhiệt độ: Đá | Độ ngọt: 30%'),('ORD26053114260685','MON005',3,50000,'Nhiệt độ: Đá | Độ ngọt: 30% | Topping: Caramel'),('ORD26053114371174','MON001',1,35000,'Nhiệt độ: Đá | Độ ngọt: 30%'),('ORD26053114371174','MON004',1,45000,'Nhiệt độ: Đá | Độ ngọt: 30% | Topping: Sữa tươi'),('ORD26053114462789','MON002',2,40000,'Nhiệt độ: Đá | Độ ngọt: 30%'),('ORD26053114594179','MON002',1,40000,'Nhiệt độ: Đá | Độ ngọt: 30%'),('ORD26053115022711','MON001',120,35000,'Nhiệt độ: Đá | Độ ngọt: 30%'),('ORD26053115143946','MON005',12,50000,'Nhiệt độ: Đá | Độ ngọt: 30%'),('ORD26053115354998','MON004',1,45000,'Nhiệt độ: Đá | Độ ngọt: 30%'),('ORD26053115372648','MON004',7,45000,'Nhiệt độ: Đá | Độ ngọt: 30%'),('ORD26053115381081','MON004',2,45000,'Nhiệt độ: Đá | Độ ngọt: 30%'),('ORD26053115385416','MON002',1,40000,'Nhiệt độ: Đá | Độ ngọt: 30%'),('ORD26053115385416','MON004',1,45000,'Nhiệt độ: Đá | Độ ngọt: 30%'),('ORD26053116010874','MON002',1,40000,'Nhiệt độ: Đá | Độ ngọt: 30% | Topping: Kem mặn'),('ORD26053116025480','MON002',2,40000,'Nhiệt độ: Đá | Độ ngọt: 30%'),('ORD26053116025480','MON004',1,45000,'Nhiệt độ: Đá | Độ ngọt: 30%'),('ORD26053119091026','MON004',140,45000,'Nhiệt độ: Đá | Độ ngọt: 30%'),('ORD26053119105160','MON004',1,45000,'Nhiệt độ: Đá | Độ ngọt: 30%');
/*!40000 ALTER TABLE `chi_tiet_order` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chi_tiet_order_option`
--

DROP TABLE IF EXISTS `chi_tiet_order_option`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chi_tiet_order_option` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ma_order` varchar(20) NOT NULL,
  `ma_mon` varchar(10) NOT NULL,
  `loai_option` varchar(30) NOT NULL,
  `ten_lua_chon` varchar(100) NOT NULL,
  `gia_them` decimal(12,0) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `chi_tiet_order_option_ma_order_ma_mon_index` (`ma_order`,`ma_mon`),
  CONSTRAINT `chi_tiet_order_option_ma_order_ma_mon_foreign` FOREIGN KEY (`ma_order`, `ma_mon`) REFERENCES `chi_tiet_order` (`ma_order`, `ma_mon`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=54 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chi_tiet_order_option`
--

LOCK TABLES `chi_tiet_order_option` WRITE;
/*!40000 ALTER TABLE `chi_tiet_order_option` DISABLE KEYS */;
INSERT INTO `chi_tiet_order_option` VALUES (1,'ORD26053113581893','MON004','temperature','Ít đá',0),(2,'ORD26053113581893','MON004','sweetness','100%',0),(3,'ORD26053113581893','MON004','topping','Shot espresso',0),(4,'ORD26053113581893','MON004','topping','Kem mặn',0),(5,'ORD26053113581893','MON004','topping','Caramel',0),(6,'ORD26053113581893','MON004','topping','Sữa tươi',0),(7,'ORD26053114055328','MON002','temperature','Nóng',0),(8,'ORD26053114055328','MON002','sweetness','30%',0),(9,'ORD26053114101990','MON004','temperature','Đá',0),(10,'ORD26053114101990','MON004','sweetness','30%',0),(11,'ORD26053114101990','MON001','temperature','Đá',0),(12,'ORD26053114101990','MON001','sweetness','30%',0),(13,'ORD26053114260685','MON005','temperature','Đá',0),(14,'ORD26053114260685','MON005','sweetness','30%',0),(15,'ORD26053114260685','MON005','topping','Caramel',0),(16,'ORD26053114260685','MON004','temperature','Đá',0),(17,'ORD26053114260685','MON004','sweetness','30%',0),(18,'ORD26053114371174','MON004','temperature','Đá',0),(19,'ORD26053114371174','MON004','sweetness','30%',0),(20,'ORD26053114371174','MON004','topping','Sữa tươi',0),(21,'ORD26053114371174','MON001','temperature','Đá',0),(22,'ORD26053114371174','MON001','sweetness','30%',0),(23,'ORD26053114462789','MON002','temperature','Đá',0),(24,'ORD26053114462789','MON002','sweetness','30%',0),(25,'ORD26053114594179','MON002','temperature','Đá',0),(26,'ORD26053114594179','MON002','sweetness','30%',0),(27,'ORD26053115022711','MON001','temperature','Đá',0),(28,'ORD26053115022711','MON001','sweetness','30%',0),(29,'ORD26053115143946','MON005','temperature','Đá',0),(30,'ORD26053115143946','MON005','sweetness','30%',0),(33,'ORD26053115354998','MON004','temperature','Đá',0),(34,'ORD26053115354998','MON004','sweetness','30%',0),(35,'ORD26053115372648','MON004','temperature','Đá',0),(36,'ORD26053115372648','MON004','sweetness','30%',0),(37,'ORD26053115381081','MON004','temperature','Đá',0),(38,'ORD26053115381081','MON004','sweetness','30%',0),(39,'ORD26053115385416','MON004','temperature','Đá',0),(40,'ORD26053115385416','MON004','sweetness','30%',0),(41,'ORD26053115385416','MON002','temperature','Đá',0),(42,'ORD26053115385416','MON002','sweetness','30%',0),(43,'ORD26053116025480','MON004','temperature','Đá',0),(44,'ORD26053116025480','MON004','sweetness','30%',0),(45,'ORD26053116010874','MON002','temperature','Đá',0),(46,'ORD26053116010874','MON002','sweetness','30%',0),(47,'ORD26053116010874','MON002','topping','Kem mặn',0),(48,'ORD26053116025480','MON002','temperature','Đá',0),(49,'ORD26053116025480','MON002','sweetness','30%',0),(50,'ORD26053119091026','MON004','temperature','Đá',0),(51,'ORD26053119091026','MON004','sweetness','30%',0),(52,'ORD26053119105160','MON004','temperature','Đá',0),(53,'ORD26053119105160','MON004','sweetness','30%',0);
/*!40000 ALTER TABLE `chi_tiet_order_option` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `danh_muc`
--

DROP TABLE IF EXISTS `danh_muc`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `danh_muc` (
  `ma_danh_muc` varchar(10) NOT NULL,
  `ten_danh_muc` varchar(100) NOT NULL,
  PRIMARY KEY (`ma_danh_muc`),
  UNIQUE KEY `uq_danh_muc_ten` (`ten_danh_muc`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `danh_muc`
--

LOCK TABLES `danh_muc` WRITE;
/*!40000 ALTER TABLE `danh_muc` DISABLE KEYS */;
INSERT INTO `danh_muc` VALUES ('DM001','Arabica Base'),('DM004','Cold Brew'),('DM007','Eats'),('DM005','Fine Robusta Base'),('DM003','Hand Brew'),('DM006','Not-café'),('DM002','Signature');
/*!40000 ALTER TABLE `danh_muc` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dinh_muc`
--

DROP TABLE IF EXISTS `dinh_muc`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dinh_muc` (
  `ma_mon` varchar(10) NOT NULL,
  `ma_nl` varchar(10) NOT NULL,
  `so_luong_dung` decimal(10,2) NOT NULL,
  `mo_ta` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`ma_mon`,`ma_nl`),
  KEY `fk_dm_nguyen_lieu` (`ma_nl`),
  CONSTRAINT `fk_dm_mon` FOREIGN KEY (`ma_mon`) REFERENCES `mon` (`ma_mon`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_dm_nguyen_lieu` FOREIGN KEY (`ma_nl`) REFERENCES `nguyen_lieu` (`ma_nl`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dinh_muc`
--

LOCK TABLES `dinh_muc` WRITE;
/*!40000 ALTER TABLE `dinh_muc` DISABLE KEYS */;
INSERT INTO `dinh_muc` VALUES ('MON001','NL001',18.00,'2 shot espresso Arabica'),('MON001','NL005',30.00,'Nước pha espresso'),('MON002','NL001',18.00,'2 shot espresso'),('MON002','NL005',150.00,'Nước nóng pha loãng'),('MON003','NL001',18.00,'1 shot espresso'),('MON003','NL003',180.00,'Sữa tươi steam'),('MON004','NL001',18.00,'1 shot espresso'),('MON004','NL003',120.00,'Sữa tươi'),('MON004','NL004',20.00,'Sữa đặc'),('MON004','NL006',30.00,'Kem béo'),('MON005','NL001',18.00,'1 shot espresso'),('MON005','NL003',150.00,'Sữa tươi'),('MON005','NL006',30.00,'Kem topping');
/*!40000 ALTER TABLE `dinh_muc` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hoa_don`
--

DROP TABLE IF EXISTS `hoa_don`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hoa_don` (
  `ma_hoa_don` varchar(20) NOT NULL,
  `ma_order` varchar(20) NOT NULL,
  `ma_kh` varchar(10) DEFAULT NULL,
  `thoi_gian_lap` datetime NOT NULL DEFAULT current_timestamp(),
  `tong_tien_truoc_ck` decimal(12,0) NOT NULL DEFAULT 0,
  `chiet_khau` decimal(5,2) NOT NULL DEFAULT 0.00,
  `tong_tien_sau_ck` decimal(12,0) NOT NULL DEFAULT 0,
  `phuong_thuc_tt` varchar(20) NOT NULL DEFAULT 'tien_mat',
  `trang_thai` varchar(15) NOT NULL DEFAULT 'cho_thanh_toan',
  `ma_nv_thu_ngan` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`ma_hoa_don`),
  UNIQUE KEY `uq_hd_order` (`ma_order`),
  KEY `ix_hoa_don_thoi_gian` (`thoi_gian_lap`,`trang_thai`),
  KEY `fk_hd_khach_hang` (`ma_kh`),
  KEY `fk_hd_nhan_vien` (`ma_nv_thu_ngan`),
  CONSTRAINT `fk_hd_khach_hang` FOREIGN KEY (`ma_kh`) REFERENCES `khach_hang` (`ma_kh`) ON DELETE SET NULL,
  CONSTRAINT `fk_hd_nhan_vien` FOREIGN KEY (`ma_nv_thu_ngan`) REFERENCES `nhan_vien` (`ma_nv`) ON DELETE SET NULL,
  CONSTRAINT `fk_hd_orders` FOREIGN KEY (`ma_order`) REFERENCES `orders` (`ma_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hoa_don`
--

LOCK TABLES `hoa_don` WRITE;
/*!40000 ALTER TABLE `hoa_don` DISABLE KEYS */;
INSERT INTO `hoa_don` VALUES ('HD20260531140122','ORD26053113581893','KH000001','2026-05-31 14:01:22',45000,0.00,45000,'chuyen_khoan','da_thanh_toan','NV001'),('HD20260531140131','ORD26053114002470','KH000001','2026-05-31 14:01:31',45000,0.00,45000,'momo','da_thanh_toan','NV001'),('HD20260531140143','ORD26053113581654','KH000001','2026-05-31 14:01:43',0,0.00,0,'momo','da_thanh_toan','NV001'),('HD20260531140702','ORD26053114055328','KH000001','2026-05-31 14:07:02',40000,0.00,40000,'tien_mat','da_thanh_toan','NV001'),('HD20260531142514','ORD26053114101990','KH000001','2026-05-31 14:25:14',80000,0.00,80000,'tien_mat','da_thanh_toan','NV001'),('HD20260531142725','ORD26053114260685','KH000001','2026-05-31 14:27:25',195000,0.00,195000,'tien_mat','da_thanh_toan','NV001'),('HD202605311438383434','ORD26053114371174','KH000002','2026-05-31 14:38:38',80000,0.00,80000,'vnpay','da_thanh_toan','NV001'),('HD202605311444461314','ORD26053114101952','KH000001','2026-05-31 14:44:46',0,0.00,0,'tien_mat','da_thanh_toan','NV001'),('HD202605311501493221','ORD26053114594179','KH000004','2026-05-31 15:01:49',40000,0.00,40000,'tien_mat','da_thanh_toan','NV001'),('HD202605311503244478','ORD26053115022711','KH000001','2026-05-31 15:03:24',4200000,0.00,4200000,'chuyen_khoan','da_thanh_toan','NV001'),('HD202605311518284334','ORD26053115143946','KH000005','2026-05-31 15:18:28',600000,0.00,600000,'chuyen_khoan','da_thanh_toan','NV001'),('HD202605311537093794','ORD26053115354998','KH000006','2026-05-31 15:37:09',45000,0.00,45000,'chuyen_khoan','da_thanh_toan','NV001'),('HD202605311538163688','ORD26053115372648','KH000004','2026-05-31 15:38:16',315000,0.00,315000,'tien_mat','da_thanh_toan','NV001'),('HD202605311538265740','ORD26053115381081','KH000004','2026-05-31 15:38:26',90000,0.00,90000,'tien_mat','da_thanh_toan','NV001'),('HD202605311547259905','ORD26053115385416','KH000001','2026-05-31 15:47:25',85000,0.00,85000,'tien_mat','da_thanh_toan','NV001'),('HD202605311602284030','ORD26053116010874','KH000001','2026-05-31 16:02:28',40000,0.00,40000,'tien_mat','da_thanh_toan','NV001'),('HD202605311907542375','ORD26053116025480','KH000007','2026-05-31 19:07:54',125000,0.00,125000,'chuyen_khoan','da_thanh_toan','NV001'),('HD202605311909563166','ORD26053119091026','KH000008','2026-05-31 19:09:56',6300000,0.00,6300000,'momo','da_thanh_toan','NV001');
/*!40000 ALTER TABLE `hoa_don` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `khach_hang`
--

DROP TABLE IF EXISTS `khach_hang`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `khach_hang` (
  `ma_kh` varchar(10) NOT NULL,
  `ten_kh` varchar(100) NOT NULL,
  `sdt` varchar(15) DEFAULT NULL,
  `ngay_tao` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`ma_kh`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `khach_hang`
--

LOCK TABLES `khach_hang` WRITE;
/*!40000 ALTER TABLE `khach_hang` DISABLE KEYS */;
INSERT INTO `khach_hang` VALUES ('KH000001','Quang','0945843588','2026-05-31 13:58:16'),('KH000002','Đạt','0948543556','2026-05-31 14:37:11'),('KH000003','àipasdv','0943123214','2026-05-31 14:47:17'),('KH000004','Đạt','123821','2026-05-31 14:59:41'),('KH000005','dvudhsd','2374234732','2026-05-31 15:14:39'),('KH000006','Quang','0945853588','2026-05-31 15:35:49'),('KH000007','h',NULL,'2026-05-31 16:02:54'),('KH000008','Quang',NULL,'2026-05-31 19:09:10'),('KH000009','Quang',NULL,'2026-05-31 19:10:51');
/*!40000 ALTER TABLE `khach_hang` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2026_05_27_000000_create_8am_coffee_mysql_schema',1),(2,'2026_05_31_000001_create_menu_option_tables',2),(3,'2026_05_31_000002_create_order_logs_table',3),(4,'2026_05_31_000003_add_so_ghe_to_ban_table',4),(5,'2026_05_31_000004_update_stock_status_view_out_of_stock',5);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mon`
--

DROP TABLE IF EXISTS `mon`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mon` (
  `ma_mon` varchar(10) NOT NULL,
  `ten_mon` varchar(100) NOT NULL,
  `don_gia` decimal(12,0) NOT NULL,
  `mo_ta` varchar(500) DEFAULT NULL,
  `hinh_anh` varchar(255) DEFAULT NULL,
  `ma_danh_muc` varchar(10) NOT NULL,
  `trang_thai` varchar(10) NOT NULL DEFAULT 'active',
  PRIMARY KEY (`ma_mon`),
  KEY `fk_mon_danh_muc` (`ma_danh_muc`),
  CONSTRAINT `fk_mon_danh_muc` FOREIGN KEY (`ma_danh_muc`) REFERENCES `danh_muc` (`ma_danh_muc`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mon`
--

LOCK TABLES `mon` WRITE;
/*!40000 ALTER TABLE `mon` DISABLE KEYS */;
INSERT INTO `mon` VALUES ('MON001','Espresso',35000,'Double shot espresso',NULL,'DM001','active'),('MON002','Americano',40000,'Espresso, nước',NULL,'DM001','active'),('MON003','Latte',45000,'Espresso, sữa tươi',NULL,'DM001','active'),('MON004',':am ấm',45000,'Espresso, sữa tươi, sữa đặc, kem béo',NULL,'DM001','active'),('MON005','Salted Caramel',50000,'Espresso, sữa tươi, caramel, kem mặn',NULL,'DM001','active'),('MON045','CẶc',1000,NULL,'sadawd','DM001','an');
/*!40000 ALTER TABLE `mon` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mon_option`
--

DROP TABLE IF EXISTS `mon_option`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mon_option` (
  `ma_option` varchar(10) NOT NULL,
  `ma_mon` varchar(10) DEFAULT NULL,
  `loai_option` varchar(30) NOT NULL,
  `ten_option` varchar(100) NOT NULL,
  `gia_them` decimal(12,0) NOT NULL DEFAULT 0,
  `bat_buoc` tinyint(1) NOT NULL DEFAULT 0,
  `thu_tu` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `trang_thai` varchar(10) NOT NULL DEFAULT 'active',
  PRIMARY KEY (`ma_option`),
  KEY `mon_option_ma_mon_loai_option_trang_thai_index` (`ma_mon`,`loai_option`,`trang_thai`),
  CONSTRAINT `mon_option_ma_mon_foreign` FOREIGN KEY (`ma_mon`) REFERENCES `mon` (`ma_mon`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mon_option`
--

LOCK TABLES `mon_option` WRITE;
/*!40000 ALTER TABLE `mon_option` DISABLE KEYS */;
/*!40000 ALTER TABLE `mon_option` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `nguyen_lieu`
--

DROP TABLE IF EXISTS `nguyen_lieu`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `nguyen_lieu` (
  `ma_nl` varchar(10) NOT NULL,
  `ten_nl` varchar(100) NOT NULL,
  `don_vi` varchar(20) NOT NULL,
  PRIMARY KEY (`ma_nl`),
  UNIQUE KEY `uq_nl_ten` (`ten_nl`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nguyen_lieu`
--

LOCK TABLES `nguyen_lieu` WRITE;
/*!40000 ALTER TABLE `nguyen_lieu` DISABLE KEYS */;
INSERT INTO `nguyen_lieu` VALUES ('NL001','Cà phê hạt Arabica','gram'),('NL002','Cà phê hạt Robusta Fine','gram'),('NL003','Sữa tươi nguyên kem','ml'),('NL004','Sữa đặc','ml'),('NL005','Nước lọc','ml'),('NL006','Kem béo (heavy cream)','ml'),('NL007','Kem mặn (salted cream)','ml'),('NL008','Caramel syrup','ml'),('NL009','Mứt hồng','gram'),('NL010','Trứng gà','cai'),('NL011','Cold Brew concentrate','ml'),('NL012','Tiramisu mix','gram'),('NL013','Rượu Bailey','ml'),('NL014','Bột gừng','gram'),('NL015','Quả mơ (mứt mơ)','gram'),('NL016','Me (mứt me)','gram'),('NL017','Tonic water','ml'),('NL018','Mứt đào','gram'),('NL019','Chanh leo tươi','gram'),('NL020','Sữa chua không đường','gram'),('NL021','Ca cao nguyên chất','gram'),('NL022','Chanh tươi','gram'),('NL023','Xí muội','gram'),('NL024','Mứt chanh leo (Tiramisu)','gram'),('NL025','Lục trà (green tea)','gram'),('NL026','Mứt ổi','gram'),('NL027','Đá viên','gram'),('NL028','Bánh sừng bò plain','cai'),('NL029','Bánh sừng bò socola','cai'),('NL030','Hạt sen sấy','gram');
/*!40000 ALTER TABLE `nguyen_lieu` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `nha_cung_cap`
--

DROP TABLE IF EXISTS `nha_cung_cap`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `nha_cung_cap` (
  `ma_ncc` varchar(10) NOT NULL,
  `ten_ncc` varchar(100) NOT NULL,
  `dia_chi` varchar(255) DEFAULT NULL,
  `sdt` varchar(15) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`ma_ncc`),
  UNIQUE KEY `uq_ncc_ten` (`ten_ncc`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nha_cung_cap`
--

LOCK TABLES `nha_cung_cap` WRITE;
/*!40000 ALTER TABLE `nha_cung_cap` DISABLE KEYS */;
INSERT INTO `nha_cung_cap` VALUES ('NCC001','Phúc Sinh Corporation','TP. Hồ Chí Minh','0281234567','info@phucsinh.com'),('NCC002','Dalat Milk','Đà Lạt, Lâm Đồng','0263456789','dalatmilk@dl.vn'),('NCC003','Khánh Hòa Salanganes','Khánh Hòa','0258765432',NULL),('NCC004','Thực phẩm Đức Việt','Hà Nội','0241112222','ducviet@hn.vn');
/*!40000 ALTER TABLE `nha_cung_cap` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `nhan_vien`
--

DROP TABLE IF EXISTS `nhan_vien`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `nhan_vien` (
  `ma_nv` varchar(10) NOT NULL,
  `ten_nv` varchar(100) NOT NULL,
  `sdt` varchar(15) DEFAULT NULL,
  `cccd` varchar(12) DEFAULT NULL,
  `dia_chi` varchar(255) DEFAULT NULL,
  `ma_chi_nhanh` varchar(10) NOT NULL,
  PRIMARY KEY (`ma_nv`),
  UNIQUE KEY `uq_nv_cccd` (`cccd`),
  KEY `fk_nv_chi_nhanh` (`ma_chi_nhanh`),
  CONSTRAINT `fk_nv_chi_nhanh` FOREIGN KEY (`ma_chi_nhanh`) REFERENCES `chi_nhanh` (`ma_chi_nhanh`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nhan_vien`
--

LOCK TABLES `nhan_vien` WRITE;
/*!40000 ALTER TABLE `nhan_vien` DISABLE KEYS */;
INSERT INTO `nhan_vien` VALUES ('NV001','Nguyễn Văn An','0901234567','001234567890','Hà Nội','CN001'),('NV002','Trần Thị Bình','0912345678','001234567891','Hà Nội','CN001'),('NV003','Lê Văn Cường','0923456789','001234567892','Hà Nội','CN001');
/*!40000 ALTER TABLE `nhan_vien` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_logs`
--

DROP TABLE IF EXISTS `order_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ma_order` varchar(20) NOT NULL,
  `hanh_dong` varchar(50) NOT NULL,
  `trang_thai_cu` varchar(15) DEFAULT NULL,
  `trang_thai_moi` varchar(15) DEFAULT NULL,
  `noi_dung` varchar(500) DEFAULT NULL,
  `du_lieu` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`du_lieu`)),
  `ma_nv` varchar(10) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `order_logs_ma_order_created_at_index` (`ma_order`,`created_at`),
  KEY `order_logs_hanh_dong_created_at_index` (`hanh_dong`,`created_at`),
  CONSTRAINT `order_logs_ma_order_foreign` FOREIGN KEY (`ma_order`) REFERENCES `orders` (`ma_order`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=103 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_logs`
--

LOCK TABLES `order_logs` WRITE;
/*!40000 ALTER TABLE `order_logs` DISABLE KEYS */;
INSERT INTO `order_logs` VALUES (1,'ORD26053114101990','xac_nhan_don','cho_xac_nhan','da_xac_nhan','Nhan vien xac nhan don hang.',NULL,'NV001','2026-05-31 14:21:53'),(2,'ORD26053114101952','xac_nhan_don','cho_xac_nhan','da_xac_nhan','Nhan vien xac nhan don hang.',NULL,'NV001','2026-05-31 14:21:56'),(3,'ORD26053114101990','them_mon',NULL,NULL,NULL,'{\"ma_mon\":\"MON004\",\"so_luong\":1,\"ghi_chu\":\"Nhi\\u1ec7t \\u0111\\u1ed9: \\u0110\\u00e1 | \\u0110\\u1ed9 ng\\u1ecdt: 30%\",\"options\":[{\"type\":\"temperature\",\"label\":\"Nhi\\u1ec7t \\u0111\\u1ed9\",\"value\":\"\\u0110\\u00e1\",\"price\":0},{\"type\":\"sweetness\",\"label\":\"\\u0110\\u1ed9 ng\\u1ecdt\",\"value\":\"30%\",\"price\":0}]}','NV001','2026-05-31 14:24:12'),(4,'ORD26053114101990','them_mon',NULL,NULL,NULL,'{\"ma_mon\":\"MON001\",\"so_luong\":1,\"ghi_chu\":\"Nhi\\u1ec7t \\u0111\\u1ed9: \\u0110\\u00e1 | \\u0110\\u1ed9 ng\\u1ecdt: 30%\",\"options\":[{\"type\":\"temperature\",\"label\":\"Nhi\\u1ec7t \\u0111\\u1ed9\",\"value\":\"\\u0110\\u00e1\",\"price\":0},{\"type\":\"sweetness\",\"label\":\"\\u0110\\u1ed9 ng\\u1ecdt\",\"value\":\"30%\",\"price\":0}]}','NV001','2026-05-31 14:24:13'),(5,'ORD26053114101990','thanh_toan','da_xac_nhan','hoan_thanh','Don hang da thanh toan va tao hoa don.','{\"ma_hoa_don\":\"HD20260531142514\",\"tong_tien_truoc_ck\":80000,\"chiet_khau\":0,\"tong_tien_sau_ck\":80000,\"phuong_thuc_tt\":\"tien_mat\"}','NV001','2026-05-31 14:25:14'),(6,'ORD26053114260685','tao_don',NULL,'cho_xac_nhan','Khach hang tao don tu QR.','{\"ma_ban\":\"B001\",\"ma_kh\":\"KH000001\",\"ma_chi_nhanh\":\"CN001\"}','NV001','2026-05-31 14:26:06'),(7,'ORD26053114260685','them_mon',NULL,NULL,NULL,'{\"ma_mon\":\"MON005\",\"so_luong\":3,\"ghi_chu\":\"Nhi\\u1ec7t \\u0111\\u1ed9: \\u0110\\u00e1 | \\u0110\\u1ed9 ng\\u1ecdt: 30% | Topping: Caramel\",\"options\":[{\"type\":\"temperature\",\"label\":\"Nhi\\u1ec7t \\u0111\\u1ed9\",\"value\":\"\\u0110\\u00e1\",\"price\":0},{\"type\":\"sweetness\",\"label\":\"\\u0110\\u1ed9 ng\\u1ecdt\",\"value\":\"30%\",\"price\":0},{\"type\":\"topping\",\"label\":\"Topping\",\"value\":\"Caramel\",\"price\":0}]}','NV001','2026-05-31 14:26:17'),(8,'ORD26053114260685','them_mon',NULL,NULL,NULL,'{\"ma_mon\":\"MON004\",\"so_luong\":1,\"ghi_chu\":\"Nhi\\u1ec7t \\u0111\\u1ed9: \\u0110\\u00e1 | \\u0110\\u1ed9 ng\\u1ecdt: 30%\",\"options\":[{\"type\":\"temperature\",\"label\":\"Nhi\\u1ec7t \\u0111\\u1ed9\",\"value\":\"\\u0110\\u00e1\",\"price\":0},{\"type\":\"sweetness\",\"label\":\"\\u0110\\u1ed9 ng\\u1ecdt\",\"value\":\"30%\",\"price\":0}]}','NV001','2026-05-31 14:26:26'),(9,'ORD26053114260685','khach_gui_don','cho_xac_nhan','cho_xac_nhan','Khach hang gui don va cho nhan vien xac nhan.',NULL,'NV001','2026-05-31 14:26:41'),(10,'ORD26053114260685','xac_nhan_don','cho_xac_nhan','da_xac_nhan','Nhan vien xac nhan don hang.',NULL,'NV001','2026-05-31 14:26:59'),(11,'ORD26053114260685','cap_nhat_trang_thai','da_xac_nhan','dang_pha_che','Cap nhat trang thai don hang.',NULL,'NV001','2026-05-31 14:27:12'),(12,'ORD26053114260685','cap_nhat_trang_thai','dang_pha_che','da_phuc_vu','Cap nhat trang thai don hang.',NULL,'NV001','2026-05-31 14:27:14'),(13,'ORD26053114260685','thanh_toan','da_phuc_vu','hoan_thanh','Don hang da thanh toan va tao hoa don.','{\"ma_hoa_don\":\"HD20260531142725\",\"tong_tien_truoc_ck\":195000,\"chiet_khau\":0,\"tong_tien_sau_ck\":195000,\"phuong_thuc_tt\":\"tien_mat\"}','NV001','2026-05-31 14:27:25'),(14,'ORD26053114371174','tao_don_nhap',NULL,'dang_chon','Khach hang bat dau chon mon tu QR.','{\"ma_ban\":\"B002\",\"ma_kh\":\"KH000002\",\"ma_chi_nhanh\":\"CN001\"}','NV001','2026-05-31 14:37:11'),(15,'ORD26053114371174','them_mon',NULL,NULL,NULL,'{\"ma_mon\":\"MON004\",\"so_luong\":1,\"ghi_chu\":\"Nhi\\u1ec7t \\u0111\\u1ed9: \\u0110\\u00e1 | \\u0110\\u1ed9 ng\\u1ecdt: 30% | Topping: S\\u1eefa t\\u01b0\\u01a1i\",\"options\":[{\"type\":\"temperature\",\"label\":\"Nhi\\u1ec7t \\u0111\\u1ed9\",\"value\":\"\\u0110\\u00e1\",\"price\":0},{\"type\":\"sweetness\",\"label\":\"\\u0110\\u1ed9 ng\\u1ecdt\",\"value\":\"30%\",\"price\":0},{\"type\":\"topping\",\"label\":\"Topping\",\"value\":\"S\\u1eefa t\\u01b0\\u01a1i\",\"price\":0}]}','NV001','2026-05-31 14:37:31'),(16,'ORD26053114371174','them_mon',NULL,NULL,NULL,'{\"ma_mon\":\"MON001\",\"so_luong\":1,\"ghi_chu\":\"Nhi\\u1ec7t \\u0111\\u1ed9: \\u0110\\u00e1 | \\u0110\\u1ed9 ng\\u1ecdt: 30%\",\"options\":[{\"type\":\"temperature\",\"label\":\"Nhi\\u1ec7t \\u0111\\u1ed9\",\"value\":\"\\u0110\\u00e1\",\"price\":0},{\"type\":\"sweetness\",\"label\":\"\\u0110\\u1ed9 ng\\u1ecdt\",\"value\":\"30%\",\"price\":0}]}','NV001','2026-05-31 14:37:31'),(17,'ORD26053114371174','khach_gui_don','dang_chon','cho_xac_nhan','Khach hang gui don cho quan.',NULL,'NV001','2026-05-31 14:37:45'),(18,'ORD26053114371174','xac_nhan_don','cho_xac_nhan','da_xac_nhan','Nhan vien xac nhan don hang.',NULL,'NV001','2026-05-31 14:38:03'),(19,'ORD26053114371174','cap_nhat_trang_thai','da_xac_nhan','dang_pha_che','Cap nhat trang thai don hang.',NULL,'NV001','2026-05-31 14:38:05'),(20,'ORD26053114371174','cap_nhat_trang_thai','dang_pha_che','da_phuc_vu','Cap nhat trang thai don hang.',NULL,'NV001','2026-05-31 14:38:09'),(21,'ORD26053114371174','thanh_toan','da_phuc_vu','hoan_thanh','Don hang da thanh toan va tao hoa don.','{\"ma_hoa_don\":\"HD202605311438383434\",\"tong_tien_truoc_ck\":80000,\"chiet_khau\":0,\"tong_tien_sau_ck\":80000,\"phuong_thuc_tt\":\"vnpay\"}','NV001','2026-05-31 14:38:38'),(22,'ORD26053114101952','cap_nhat_trang_thai','da_xac_nhan','dang_pha_che','Cap nhat trang thai don hang.',NULL,'NV001','2026-05-31 14:44:15'),(23,'ORD26053114101952','cap_nhat_trang_thai','dang_pha_che','da_phuc_vu','Cap nhat trang thai don hang.',NULL,'NV001','2026-05-31 14:44:17'),(24,'ORD26053114101952','thanh_toan','da_phuc_vu','hoan_thanh','Don hang da thanh toan va tao hoa don.','{\"ma_hoa_don\":\"HD202605311444461314\",\"tong_tien_truoc_ck\":0,\"chiet_khau\":0,\"tong_tien_sau_ck\":0,\"phuong_thuc_tt\":\"tien_mat\"}','NV001','2026-05-31 14:44:46'),(25,'ORD26053114462789','tao_don_nhap',NULL,'dang_chon','Khach hang bat dau chon mon tu QR.','{\"ma_ban\":\"B003\",\"ma_kh\":\"KH000001\",\"ma_chi_nhanh\":\"CN001\"}','NV001','2026-05-31 14:46:27'),(26,'ORD26053114462789','them_mon',NULL,NULL,NULL,'{\"ma_mon\":\"MON002\",\"so_luong\":2,\"ghi_chu\":\"Nhi\\u1ec7t \\u0111\\u1ed9: \\u0110\\u00e1 | \\u0110\\u1ed9 ng\\u1ecdt: 30%\",\"options\":[{\"type\":\"temperature\",\"label\":\"Nhi\\u1ec7t \\u0111\\u1ed9\",\"value\":\"\\u0110\\u00e1\",\"price\":0},{\"type\":\"sweetness\",\"label\":\"\\u0110\\u1ed9 ng\\u1ecdt\",\"value\":\"30%\",\"price\":0}]}','NV001','2026-05-31 14:46:35'),(27,'ORD26053114471776','tao_don_nhap',NULL,'dang_chon','Khach hang bat dau chon mon tu QR.','{\"ma_ban\":\"B003\",\"ma_kh\":\"KH000003\",\"ma_chi_nhanh\":\"CN001\"}','NV001','2026-05-31 14:47:17'),(28,'ORD26053114594179','tao_don_nhap',NULL,'dang_chon','Khach hang bat dau chon mon tu QR.','{\"ma_ban\":\"B004\",\"ma_kh\":\"KH000004\",\"ma_chi_nhanh\":\"CN001\"}','NV001','2026-05-31 14:59:41'),(29,'ORD26053114594179','them_mon',NULL,NULL,NULL,'{\"ma_mon\":\"MON002\",\"so_luong\":1,\"ghi_chu\":\"Nhi\\u1ec7t \\u0111\\u1ed9: \\u0110\\u00e1 | \\u0110\\u1ed9 ng\\u1ecdt: 30%\",\"options\":[{\"type\":\"temperature\",\"label\":\"Nhi\\u1ec7t \\u0111\\u1ed9\",\"value\":\"\\u0110\\u00e1\",\"price\":0},{\"type\":\"sweetness\",\"label\":\"\\u0110\\u1ed9 ng\\u1ecdt\",\"value\":\"30%\",\"price\":0}]}','NV001','2026-05-31 14:59:51'),(30,'ORD26053114594179','khach_gui_don','dang_chon','cho_xac_nhan','Khach hang gui don cho quan.',NULL,'NV001','2026-05-31 14:59:54'),(31,'ORD26053114594179','xac_nhan_don','cho_xac_nhan','da_xac_nhan','Nhan vien xac nhan don hang.',NULL,'NV001','2026-05-31 15:00:28'),(32,'ORD26053114594179','cap_nhat_trang_thai','da_xac_nhan','dang_pha_che','Cap nhat trang thai don hang.',NULL,'NV001','2026-05-31 15:00:31'),(33,'ORD26053114594179','cap_nhat_trang_thai','dang_pha_che','da_phuc_vu','Cap nhat trang thai don hang.',NULL,'NV001','2026-05-31 15:00:33'),(34,'ORD26053114594179','thanh_toan','da_phuc_vu','hoan_thanh','Don hang da thanh toan va tao hoa don.','{\"ma_hoa_don\":\"HD202605311501493221\",\"tong_tien_truoc_ck\":40000,\"chiet_khau\":0,\"tong_tien_sau_ck\":40000,\"phuong_thuc_tt\":\"tien_mat\"}','NV001','2026-05-31 15:01:49'),(35,'ORD26053115020519','tao_don_nhap',NULL,'dang_chon','Khach hang bat dau chon mon tu QR.','{\"ma_ban\":\"B004\",\"ma_kh\":\"KH000003\",\"ma_chi_nhanh\":\"CN001\"}','NV001','2026-05-31 15:02:05'),(36,'ORD26053115022711','tao_don_nhap',NULL,'dang_chon','Khach hang bat dau chon mon tu QR.','{\"ma_ban\":\"B005\",\"ma_kh\":\"KH000001\",\"ma_chi_nhanh\":\"CN001\"}','NV001','2026-05-31 15:02:27'),(37,'ORD26053115022711','them_mon',NULL,NULL,NULL,'{\"ma_mon\":\"MON001\",\"so_luong\":120,\"ghi_chu\":\"Nhi\\u1ec7t \\u0111\\u1ed9: \\u0110\\u00e1 | \\u0110\\u1ed9 ng\\u1ecdt: 30%\",\"options\":[{\"type\":\"temperature\",\"label\":\"Nhi\\u1ec7t \\u0111\\u1ed9\",\"value\":\"\\u0110\\u00e1\",\"price\":0},{\"type\":\"sweetness\",\"label\":\"\\u0110\\u1ed9 ng\\u1ecdt\",\"value\":\"30%\",\"price\":0}]}','NV001','2026-05-31 15:03:03'),(38,'ORD26053115022711','khach_gui_don','dang_chon','cho_xac_nhan','Khach hang gui don cho quan.',NULL,'NV001','2026-05-31 15:03:06'),(39,'ORD26053115022711','xac_nhan_don','cho_xac_nhan','da_xac_nhan','Nhan vien xac nhan don hang.',NULL,'NV001','2026-05-31 15:03:14'),(40,'ORD26053115022711','thanh_toan','da_xac_nhan','hoan_thanh','Don hang da thanh toan va tao hoa don.','{\"ma_hoa_don\":\"HD202605311503244478\",\"tong_tien_truoc_ck\":4200000,\"chiet_khau\":0,\"tong_tien_sau_ck\":4200000,\"phuong_thuc_tt\":\"chuyen_khoan\"}','NV001','2026-05-31 15:03:24'),(41,'ORD26053115143946','tao_don_nhap',NULL,'dang_chon','Khach hang bat dau chon mon tu QR.','{\"ma_ban\":\"B001\",\"ma_kh\":\"KH000005\",\"ma_chi_nhanh\":\"CN001\"}','NV001','2026-05-31 15:14:39'),(42,'ORD26053115143946','them_mon',NULL,NULL,NULL,'{\"ma_mon\":\"MON005\",\"so_luong\":12,\"ghi_chu\":\"Nhi\\u1ec7t \\u0111\\u1ed9: \\u0110\\u00e1 | \\u0110\\u1ed9 ng\\u1ecdt: 30%\",\"options\":[{\"type\":\"temperature\",\"label\":\"Nhi\\u1ec7t \\u0111\\u1ed9\",\"value\":\"\\u0110\\u00e1\",\"price\":0},{\"type\":\"sweetness\",\"label\":\"\\u0110\\u1ed9 ng\\u1ecdt\",\"value\":\"30%\",\"price\":0}]}','NV001','2026-05-31 15:14:53'),(43,'ORD26053115143946','khach_gui_don','dang_chon','cho_xac_nhan','Khach hang gui don cho quan.',NULL,'NV001','2026-05-31 15:14:55'),(44,'ORD26053115143946','xac_nhan_don','cho_xac_nhan','da_xac_nhan','Nhan vien xac nhan don hang.',NULL,'NV001','2026-05-31 15:15:05'),(45,'ORD26053115143946','cap_nhat_trang_thai','da_xac_nhan','dang_pha_che','Cap nhat trang thai don hang.',NULL,'NV001','2026-05-31 15:15:08'),(46,'ORD26053115143946','cap_nhat_trang_thai','dang_pha_che','da_phuc_vu','Cap nhat trang thai don hang.',NULL,'NV001','2026-05-31 15:15:11'),(47,'ORD26053115143946','tach_don_goc',NULL,NULL,NULL,'{\"ma_order_moi\":\"ORD26053115152980\",\"ma_mon\":\"MON005\",\"so_luong_tach\":1}','NV001','2026-05-31 15:15:29'),(48,'ORD26053115152980','tach_don_moi',NULL,'da_phuc_vu','Tao don moi tu thao tac tach don.','{\"ma_order_goc\":\"ORD26053115143946\",\"ma_mon\":\"MON005\",\"so_luong_tach\":1}','NV001','2026-05-31 15:15:29'),(49,'ORD26053115143946','gop_don_nhan',NULL,NULL,NULL,'{\"ma_order_gop\":\"ORD26053115152980\"}','NV001','2026-05-31 15:15:35'),(50,'ORD26053115152980','gop_don_huy','da_phuc_vu','da_huy','Don duoc gop vao don khac.','{\"ma_order_nhan\":\"ORD26053115143946\"}','NV001','2026-05-31 15:15:35'),(51,'ORD26053115143946','thanh_toan','da_phuc_vu','hoan_thanh','Don hang da thanh toan va tao hoa don.','{\"ma_hoa_don\":\"HD202605311518284334\",\"tong_tien_truoc_ck\":600000,\"chiet_khau\":0,\"tong_tien_sau_ck\":600000,\"phuong_thuc_tt\":\"chuyen_khoan\"}','NV001','2026-05-31 15:18:28'),(52,'ORD26053115284055','tao_don_nhap',NULL,'dang_chon','Khach hang bat dau chon mon tu QR.','{\"ma_ban\":\"B001\",\"ma_kh\":\"KH000002\",\"ma_chi_nhanh\":\"CN001\"}','NV001','2026-05-31 15:28:40'),(53,'ORD26053115354998','tao_don_nhap',NULL,'dang_chon','Khach hang bat dau chon mon tu QR.','{\"ma_ban\":\"B001\",\"ma_kh\":\"KH000006\",\"ma_chi_nhanh\":\"CN001\"}','NV001','2026-05-31 15:35:49'),(54,'ORD26053115354998','them_mon',NULL,NULL,NULL,'{\"ma_mon\":\"MON004\",\"so_luong\":1,\"ghi_chu\":\"Nhi\\u1ec7t \\u0111\\u1ed9: \\u0110\\u00e1 | \\u0110\\u1ed9 ng\\u1ecdt: 30%\",\"options\":[{\"type\":\"temperature\",\"label\":\"Nhi\\u1ec7t \\u0111\\u1ed9\",\"value\":\"\\u0110\\u00e1\",\"price\":0},{\"type\":\"sweetness\",\"label\":\"\\u0110\\u1ed9 ng\\u1ecdt\",\"value\":\"30%\",\"price\":0}]}','NV001','2026-05-31 15:35:59'),(55,'ORD26053115354998','khach_gui_don','dang_chon','cho_xac_nhan','Khach hang gui don cho quan.',NULL,'NV001','2026-05-31 15:36:02'),(56,'ORD26053115354998','xac_nhan_don','cho_xac_nhan','da_xac_nhan','Nhan vien xac nhan don hang.',NULL,'NV001','2026-05-31 15:36:22'),(57,'ORD26053115354998','cap_nhat_trang_thai','da_xac_nhan','dang_pha_che','Cap nhat trang thai don hang.',NULL,'NV001','2026-05-31 15:36:24'),(58,'ORD26053115354998','cap_nhat_trang_thai','dang_pha_che','da_phuc_vu','Cap nhat trang thai don hang.',NULL,'NV001','2026-05-31 15:36:41'),(59,'ORD26053115354998','thanh_toan','da_phuc_vu','hoan_thanh','Don hang da thanh toan va tao hoa don.','{\"ma_hoa_don\":\"HD202605311537093794\",\"tong_tien_truoc_ck\":45000,\"chiet_khau\":0,\"tong_tien_sau_ck\":45000,\"phuong_thuc_tt\":\"chuyen_khoan\"}','NV001','2026-05-31 15:37:09'),(60,'ORD26053115372648','tao_don_nhap',NULL,'dang_chon','Khach hang bat dau chon mon tu QR.','{\"ma_ban\":\"B001\",\"ma_kh\":\"KH000004\",\"ma_chi_nhanh\":\"CN001\"}','NV001','2026-05-31 15:37:26'),(61,'ORD26053115372648','them_mon',NULL,NULL,NULL,'{\"ma_mon\":\"MON004\",\"so_luong\":9,\"ghi_chu\":\"Nhi\\u1ec7t \\u0111\\u1ed9: \\u0110\\u00e1 | \\u0110\\u1ed9 ng\\u1ecdt: 30%\",\"options\":[{\"type\":\"temperature\",\"label\":\"Nhi\\u1ec7t \\u0111\\u1ed9\",\"value\":\"\\u0110\\u00e1\",\"price\":0},{\"type\":\"sweetness\",\"label\":\"\\u0110\\u1ed9 ng\\u1ecdt\",\"value\":\"30%\",\"price\":0}]}','NV001','2026-05-31 15:37:38'),(62,'ORD26053115372648','khach_gui_don','dang_chon','cho_xac_nhan','Khach hang gui don cho quan.',NULL,'NV001','2026-05-31 15:37:41'),(63,'ORD26053115372648','xac_nhan_don','cho_xac_nhan','da_xac_nhan','Nhan vien xac nhan don hang.',NULL,'NV001','2026-05-31 15:37:51'),(64,'ORD26053115372648','cap_nhat_trang_thai','da_xac_nhan','dang_pha_che','Cap nhat trang thai don hang.',NULL,'NV001','2026-05-31 15:37:59'),(65,'ORD26053115372648','cap_nhat_trang_thai','dang_pha_che','da_phuc_vu','Cap nhat trang thai don hang.',NULL,'NV001','2026-05-31 15:38:02'),(66,'ORD26053115372648','tach_don_goc',NULL,NULL,NULL,'{\"ma_order_moi\":\"ORD26053115381081\",\"ma_mon\":\"MON004\",\"so_luong_tach\":2}','NV001','2026-05-31 15:38:10'),(67,'ORD26053115381081','tach_don_moi',NULL,'da_phuc_vu','Tao don moi tu thao tac tach don.','{\"ma_order_goc\":\"ORD26053115372648\",\"ma_mon\":\"MON004\",\"so_luong_tach\":2}','NV001','2026-05-31 15:38:10'),(68,'ORD26053115372648','thanh_toan','da_phuc_vu','hoan_thanh','Don hang da thanh toan va tao hoa don.','{\"ma_hoa_don\":\"HD202605311538163688\",\"tong_tien_truoc_ck\":315000,\"chiet_khau\":0,\"tong_tien_sau_ck\":315000,\"phuong_thuc_tt\":\"tien_mat\"}','NV001','2026-05-31 15:38:16'),(69,'ORD26053115381081','thanh_toan','da_phuc_vu','hoan_thanh','Don hang da thanh toan va tao hoa don.','{\"ma_hoa_don\":\"HD202605311538265740\",\"tong_tien_truoc_ck\":90000,\"chiet_khau\":0,\"tong_tien_sau_ck\":90000,\"phuong_thuc_tt\":\"tien_mat\"}','NV001','2026-05-31 15:38:26'),(70,'ORD26053115385416','tao_don_nhap',NULL,'dang_chon','Khach hang bat dau chon mon tu QR.','{\"ma_ban\":\"B001\",\"ma_kh\":\"KH000001\",\"ma_chi_nhanh\":\"CN001\"}','NV001','2026-05-31 15:38:54'),(71,'ORD26053115385416','them_mon',NULL,NULL,NULL,'{\"ma_mon\":\"MON004\",\"so_luong\":1,\"ghi_chu\":\"Nhi\\u1ec7t \\u0111\\u1ed9: \\u0110\\u00e1 | \\u0110\\u1ed9 ng\\u1ecdt: 30%\",\"options\":[{\"type\":\"temperature\",\"label\":\"Nhi\\u1ec7t \\u0111\\u1ed9\",\"value\":\"\\u0110\\u00e1\",\"price\":0},{\"type\":\"sweetness\",\"label\":\"\\u0110\\u1ed9 ng\\u1ecdt\",\"value\":\"30%\",\"price\":0}]}','NV001','2026-05-31 15:45:59'),(72,'ORD26053115385416','them_mon',NULL,NULL,NULL,'{\"ma_mon\":\"MON002\",\"so_luong\":1,\"ghi_chu\":\"Nhi\\u1ec7t \\u0111\\u1ed9: \\u0110\\u00e1 | \\u0110\\u1ed9 ng\\u1ecdt: 30%\",\"options\":[{\"type\":\"temperature\",\"label\":\"Nhi\\u1ec7t \\u0111\\u1ed9\",\"value\":\"\\u0110\\u00e1\",\"price\":0},{\"type\":\"sweetness\",\"label\":\"\\u0110\\u1ed9 ng\\u1ecdt\",\"value\":\"30%\",\"price\":0}]}','NV001','2026-05-31 15:46:20'),(73,'ORD26053115385416','khach_gui_don','dang_chon','cho_xac_nhan','Khach hang gui don cho quan.',NULL,'NV001','2026-05-31 15:46:54'),(74,'ORD26053115385416','xac_nhan_don','cho_xac_nhan','da_xac_nhan','Nhan vien xac nhan don hang.',NULL,'NV001','2026-05-31 15:47:03'),(75,'ORD26053115385416','cap_nhat_trang_thai','da_xac_nhan','dang_pha_che','Cap nhat trang thai don hang.',NULL,'NV001','2026-05-31 15:47:17'),(76,'ORD26053115385416','cap_nhat_trang_thai','dang_pha_che','da_phuc_vu','Cap nhat trang thai don hang.',NULL,'NV001','2026-05-31 15:47:20'),(77,'ORD26053115385416','thanh_toan','da_phuc_vu','hoan_thanh','Don hang da thanh toan va tao hoa don.','{\"ma_hoa_don\":\"HD202605311547259905\",\"tong_tien_truoc_ck\":85000,\"chiet_khau\":0,\"tong_tien_sau_ck\":85000,\"phuong_thuc_tt\":\"tien_mat\"}','NV001','2026-05-31 15:47:25'),(78,'ORD26053115524445','tao_don_nhap',NULL,'dang_chon','Khach hang bat dau chon mon tu QR.','{\"ma_ban\":\"B001\",\"ma_kh\":\"KH000002\",\"ma_chi_nhanh\":\"CN001\"}','NV001','2026-05-31 15:52:44'),(79,'ORD26053115524445','them_mon',NULL,NULL,NULL,'{\"ma_mon\":\"MON004\",\"so_luong\":1,\"ghi_chu\":\"Nhi\\u1ec7t \\u0111\\u1ed9: \\u0110\\u00e1 | \\u0110\\u1ed9 ng\\u1ecdt: 30%\",\"options\":[{\"type\":\"temperature\",\"label\":\"Nhi\\u1ec7t \\u0111\\u1ed9\",\"value\":\"\\u0110\\u00e1\",\"price\":0},{\"type\":\"sweetness\",\"label\":\"\\u0110\\u1ed9 ng\\u1ecdt\",\"value\":\"30%\",\"price\":0}]}','NV001','2026-05-31 15:52:54'),(80,'ORD26053115524445','khach_gui_don','dang_chon','cho_xac_nhan','Khach hang gui don cho quan.',NULL,'NV001','2026-05-31 15:52:58'),(81,'ORD26053115524445','xac_nhan_don','cho_xac_nhan','da_xac_nhan','Nhan vien xac nhan don hang.',NULL,'NV001','2026-05-31 15:53:05'),(82,'ORD26053116010874','tao_don_nhap',NULL,'dang_chon','Khach hang bat dau chon mon tu QR.','{\"ma_ban\":\"B002\",\"ma_kh\":\"KH000001\",\"ma_chi_nhanh\":\"CN001\"}','NV001','2026-05-31 16:01:08'),(83,'ORD26053116010874','them_mon',NULL,NULL,NULL,'{\"ma_mon\":\"MON002\",\"so_luong\":1,\"ghi_chu\":\"Nhi\\u1ec7t \\u0111\\u1ed9: \\u0110\\u00e1 | \\u0110\\u1ed9 ng\\u1ecdt: 30% | Topping: Kem m\\u1eb7n\",\"options\":[{\"type\":\"temperature\",\"label\":\"Nhi\\u1ec7t \\u0111\\u1ed9\",\"value\":\"\\u0110\\u00e1\",\"price\":0},{\"type\":\"sweetness\",\"label\":\"\\u0110\\u1ed9 ng\\u1ecdt\",\"value\":\"30%\",\"price\":0},{\"type\":\"topping\",\"label\":\"Topping\",\"value\":\"Kem m\\u1eb7n\",\"price\":0}]}','NV001','2026-05-31 16:01:29'),(84,'ORD26053116010874','khach_gui_don','dang_chon','cho_xac_nhan','Khach hang gui don cho quan.',NULL,'NV001','2026-05-31 16:01:34'),(85,'ORD26053116010874','xac_nhan_don','cho_xac_nhan','da_xac_nhan','Nhan vien xac nhan don hang.',NULL,'NV001','2026-05-31 16:01:42'),(86,'ORD26053116010874','cap_nhat_trang_thai','da_xac_nhan','dang_pha_che','Cap nhat trang thai don hang.',NULL,'NV001','2026-05-31 16:01:54'),(87,'ORD26053116010874','cap_nhat_trang_thai','dang_pha_che','da_phuc_vu','Cap nhat trang thai don hang.',NULL,'NV001','2026-05-31 16:01:58'),(88,'ORD26053116010874','thanh_toan','da_phuc_vu','hoan_thanh','Don hang da thanh toan va tao hoa don.','{\"ma_hoa_don\":\"HD202605311602284030\",\"tong_tien_truoc_ck\":40000,\"chiet_khau\":0,\"tong_tien_sau_ck\":40000,\"phuong_thuc_tt\":\"tien_mat\"}','NV001','2026-05-31 16:02:28'),(89,'ORD26053116025480','tao_don_nhap',NULL,'dang_chon','Khach hang bat dau chon mon tu QR.','{\"ma_ban\":\"B002\",\"ma_kh\":\"KH000007\",\"ma_chi_nhanh\":\"CN001\"}','NV001','2026-05-31 16:02:54'),(90,'ORD26053116025480','them_mon',NULL,NULL,NULL,'{\"ma_mon\":\"MON002\",\"so_luong\":2,\"ghi_chu\":\"Nhi\\u1ec7t \\u0111\\u1ed9: \\u0110\\u00e1 | \\u0110\\u1ed9 ng\\u1ecdt: 30%\",\"options\":[{\"type\":\"temperature\",\"label\":\"Nhi\\u1ec7t \\u0111\\u1ed9\",\"value\":\"\\u0110\\u00e1\",\"price\":0},{\"type\":\"sweetness\",\"label\":\"\\u0110\\u1ed9 ng\\u1ecdt\",\"value\":\"30%\",\"price\":0}]}','NV001','2026-05-31 19:06:35'),(91,'ORD26053116025480','khach_gui_don','dang_chon','cho_xac_nhan','Khach hang gui don cho quan.',NULL,'NV001','2026-05-31 19:06:37'),(92,'ORD26053116025480','xac_nhan_don','cho_xac_nhan','da_xac_nhan','Nhan vien xac nhan don hang.',NULL,'NV001','2026-05-31 19:06:51'),(93,'ORD26053116025480','gop_don_nhan',NULL,NULL,NULL,'{\"ma_order_gop\":\"ORD26053115524445\"}','NV001','2026-05-31 19:07:06'),(94,'ORD26053115524445','gop_don_huy','da_xac_nhan','da_huy','Don duoc gop vao don khac.','{\"ma_order_nhan\":\"ORD26053116025480\"}','NV001','2026-05-31 19:07:06'),(95,'ORD26053116025480','thanh_toan','da_xac_nhan','hoan_thanh','Don hang da thanh toan va tao hoa don.','{\"ma_hoa_don\":\"HD202605311907542375\",\"tong_tien_truoc_ck\":125000,\"chiet_khau\":0,\"tong_tien_sau_ck\":125000,\"phuong_thuc_tt\":\"chuyen_khoan\"}','NV001','2026-05-31 19:07:54'),(96,'ORD26053119091026','tao_don_nhap',NULL,'dang_chon','Khach hang bat dau chon mon tu QR.','{\"ma_ban\":\"B002\",\"ma_kh\":\"KH000008\",\"ma_chi_nhanh\":\"CN001\"}','NV001','2026-05-31 19:09:10'),(97,'ORD26053119091026','them_mon',NULL,NULL,NULL,'{\"ma_mon\":\"MON004\",\"so_luong\":140,\"ghi_chu\":\"Nhi\\u1ec7t \\u0111\\u1ed9: \\u0110\\u00e1 | \\u0110\\u1ed9 ng\\u1ecdt: 30%\",\"options\":[{\"type\":\"temperature\",\"label\":\"Nhi\\u1ec7t \\u0111\\u1ed9\",\"value\":\"\\u0110\\u00e1\",\"price\":0},{\"type\":\"sweetness\",\"label\":\"\\u0110\\u1ed9 ng\\u1ecdt\",\"value\":\"30%\",\"price\":0}]}','NV001','2026-05-31 19:09:37'),(98,'ORD26053119091026','khach_gui_don','dang_chon','cho_xac_nhan','Khach hang gui don cho quan.',NULL,'NV001','2026-05-31 19:09:39'),(99,'ORD26053119091026','xac_nhan_don','cho_xac_nhan','da_xac_nhan','Nhan vien xac nhan don hang.',NULL,'NV001','2026-05-31 19:09:47'),(100,'ORD26053119091026','thanh_toan','da_xac_nhan','hoan_thanh','Don hang da thanh toan va tao hoa don.','{\"ma_hoa_don\":\"HD202605311909563166\",\"tong_tien_truoc_ck\":6300000,\"chiet_khau\":0,\"tong_tien_sau_ck\":6300000,\"phuong_thuc_tt\":\"momo\"}','NV001','2026-05-31 19:09:56'),(101,'ORD26053119105160','tao_don_nhap',NULL,'dang_chon','Khach hang bat dau chon mon tu QR.','{\"ma_ban\":\"B002\",\"ma_kh\":\"KH000009\",\"ma_chi_nhanh\":\"CN001\"}','NV001','2026-05-31 19:10:51'),(102,'ORD26053119105160','them_mon',NULL,NULL,NULL,'{\"ma_mon\":\"MON004\",\"so_luong\":1,\"ghi_chu\":\"Nhi\\u1ec7t \\u0111\\u1ed9: \\u0110\\u00e1 | \\u0110\\u1ed9 ng\\u1ecdt: 30%\",\"options\":[{\"type\":\"temperature\",\"label\":\"Nhi\\u1ec7t \\u0111\\u1ed9\",\"value\":\"\\u0110\\u00e1\",\"price\":0},{\"type\":\"sweetness\",\"label\":\"\\u0110\\u1ed9 ng\\u1ecdt\",\"value\":\"30%\",\"price\":0}]}','NV001','2026-05-31 19:11:01');
/*!40000 ALTER TABLE `order_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders` (
  `ma_order` varchar(20) NOT NULL,
  `ma_ban` varchar(10) NOT NULL,
  `ma_kh` varchar(10) DEFAULT NULL,
  `ma_chi_nhanh` varchar(10) NOT NULL,
  `trang_thai` varchar(15) NOT NULL DEFAULT 'cho_xac_nhan',
  `ngay_order` date NOT NULL DEFAULT curdate(),
  `gio_order` time NOT NULL DEFAULT curtime(),
  `ghi_chu` varchar(300) DEFAULT NULL,
  PRIMARY KEY (`ma_order`),
  KEY `ix_orders_status_branch_date` (`trang_thai`,`ma_chi_nhanh`,`ngay_order`),
  KEY `ix_orders_ban_ngay` (`ma_ban`,`ngay_order`),
  KEY `fk_orders_khach_hang` (`ma_kh`),
  KEY `fk_orders_chi_nhanh` (`ma_chi_nhanh`),
  CONSTRAINT `fk_orders_ban` FOREIGN KEY (`ma_ban`) REFERENCES `ban` (`ma_ban`),
  CONSTRAINT `fk_orders_chi_nhanh` FOREIGN KEY (`ma_chi_nhanh`) REFERENCES `chi_nhanh` (`ma_chi_nhanh`),
  CONSTRAINT `fk_orders_khach_hang` FOREIGN KEY (`ma_kh`) REFERENCES `khach_hang` (`ma_kh`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES ('ORD26053113581654','B001','KH000001','CN001','hoan_thanh','2026-05-31','13:58:16',NULL),('ORD26053113581893','B001','KH000001','CN001','hoan_thanh','2026-05-31','13:58:18',NULL),('ORD26053114002470','B001','KH000001','CN001','hoan_thanh','2026-05-31','14:00:24',NULL),('ORD26053114055328','B001','KH000001','CN001','hoan_thanh','2026-05-31','14:05:53',NULL),('ORD26053114101952','B001','KH000001','CN001','hoan_thanh','2026-05-31','14:10:19',NULL),('ORD26053114101990','B001','KH000001','CN001','hoan_thanh','2026-05-31','14:10:19',NULL),('ORD26053114260685','B001','KH000001','CN001','hoan_thanh','2026-05-31','14:26:06',NULL),('ORD26053114371174','B002','KH000002','CN001','hoan_thanh','2026-05-31','14:37:11',NULL),('ORD26053114462789','B003','KH000001','CN001','dang_chon','2026-05-31','14:46:27',NULL),('ORD26053114471776','B003','KH000003','CN001','dang_chon','2026-05-31','14:47:17',NULL),('ORD26053114594179','B004','KH000004','CN001','hoan_thanh','2026-05-31','14:59:41',NULL),('ORD26053115020519','B004','KH000003','CN001','dang_chon','2026-05-31','15:02:05',NULL),('ORD26053115022711','B005','KH000001','CN001','hoan_thanh','2026-05-31','15:02:27',NULL),('ORD26053115143946','B001','KH000005','CN001','hoan_thanh','2026-05-31','15:14:39',NULL),('ORD26053115152980','B001','KH000005','CN001','da_huy','2026-05-31','15:15:29',NULL),('ORD26053115284055','B001','KH000002','CN001','dang_chon','2026-05-31','15:28:40',NULL),('ORD26053115354998','B001','KH000006','CN001','hoan_thanh','2026-05-31','15:35:49',NULL),('ORD26053115372648','B001','KH000004','CN001','hoan_thanh','2026-05-31','15:37:26',NULL),('ORD26053115381081','B001','KH000004','CN001','hoan_thanh','2026-05-31','15:38:10',NULL),('ORD26053115385416','B001','KH000001','CN001','hoan_thanh','2026-05-31','15:38:54',NULL),('ORD26053115524445','B001','KH000002','CN001','da_huy','2026-05-31','15:52:44',NULL),('ORD26053116010874','B002','KH000001','CN001','hoan_thanh','2026-05-31','16:01:08',NULL),('ORD26053116025480','B002','KH000007','CN001','hoan_thanh','2026-05-31','16:02:54',NULL),('ORD26053119091026','B002','KH000008','CN001','hoan_thanh','2026-05-31','19:09:10',NULL),('ORD26053119105160','B002','KH000009','CN001','dang_chon','2026-05-31','19:10:51',NULL);
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `TRG_ORDER_TRU_TON_KHO`
AFTER UPDATE ON `ORDERS`
FOR EACH ROW
BEGIN
    IF NEW.trang_thai = 'da_xac_nhan' AND OLD.trang_thai <> 'da_xac_nhan' THEN
        UPDATE `TON_KHO` tk
        JOIN `DINH_MUC` dm ON dm.ma_nl = tk.ma_nl
        JOIN `CHI_TIET_ORDER` cto ON cto.ma_mon = dm.ma_mon
        SET tk.sl_ton_kho_he_thong = tk.sl_ton_kho_he_thong - (cto.so_luong * dm.so_luong_dung)
        WHERE cto.ma_order = NEW.ma_order
          AND tk.ma_chi_nhanh = NEW.ma_chi_nhanh;
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `TRG_ORDER_CAP_NHAT_TRANG_THAI_BAN`
AFTER UPDATE ON `ORDERS`
FOR EACH ROW
BEGIN
    IF NEW.trang_thai IN ('cho_xac_nhan', 'da_xac_nhan', 'dang_pha_che', 'da_phuc_vu') THEN
        UPDATE `BAN` SET trang_thai = 'co_khach' WHERE ma_ban = NEW.ma_ban;
    END IF;

    IF NEW.trang_thai IN ('hoan_thanh', 'da_huy') THEN
        IF NOT EXISTS (
            SELECT 1 FROM `ORDERS`
            WHERE ma_ban = NEW.ma_ban
              AND trang_thai IN ('cho_xac_nhan', 'da_xac_nhan', 'dang_pha_che', 'da_phuc_vu')
        ) THEN
            UPDATE `BAN` SET trang_thai = 'trong' WHERE ma_ban = NEW.ma_ban;
        END IF;
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `phieu_kiem_ke`
--

DROP TABLE IF EXISTS `phieu_kiem_ke`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `phieu_kiem_ke` (
  `ma_pkk` varchar(20) NOT NULL,
  `ngay_kk` date NOT NULL DEFAULT curdate(),
  `ma_chi_nhanh` varchar(10) NOT NULL,
  `ma_nv` varchar(10) NOT NULL,
  `trang_thai` varchar(15) NOT NULL DEFAULT 'nhap',
  `ghi_chu` varchar(300) DEFAULT NULL,
  PRIMARY KEY (`ma_pkk`),
  KEY `fk_pkk_chi_nhanh` (`ma_chi_nhanh`),
  KEY `fk_pkk_nv` (`ma_nv`),
  CONSTRAINT `fk_pkk_chi_nhanh` FOREIGN KEY (`ma_chi_nhanh`) REFERENCES `chi_nhanh` (`ma_chi_nhanh`),
  CONSTRAINT `fk_pkk_nv` FOREIGN KEY (`ma_nv`) REFERENCES `nhan_vien` (`ma_nv`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `phieu_kiem_ke`
--

LOCK TABLES `phieu_kiem_ke` WRITE;
/*!40000 ALTER TABLE `phieu_kiem_ke` DISABLE KEYS */;
INSERT INTO `phieu_kiem_ke` VALUES ('PKK20260531192516','2026-05-31','CN001','NV001','da_xac_nhan',NULL);
/*!40000 ALTER TABLE `phieu_kiem_ke` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `TRG_KIEM_KE_CAP_NHAT_TON`
AFTER UPDATE ON `PHIEU_KIEM_KE`
FOR EACH ROW
BEGIN
    IF NEW.trang_thai = 'da_xac_nhan' AND OLD.trang_thai <> 'da_xac_nhan' THEN
        UPDATE `TON_KHO` tk
        JOIN `CHI_TIET_KIEM_KE` ct ON ct.ma_nl = tk.ma_nl
        SET tk.sl_ton_kho_thuc_te = ct.sl_thuc_te,
            tk.sl_ton_kho_he_thong = ct.sl_thuc_te,
            tk.hao_hut_cost = CASE
                WHEN ct.sl_thuc_te < ct.sl_he_thong
                THEN (ct.sl_he_thong - ct.sl_thuc_te) * COALESCE(ct.don_gia_tb, 0)
                ELSE tk.hao_hut_cost
            END
        WHERE ct.ma_pkk = NEW.ma_pkk
          AND tk.ma_chi_nhanh = NEW.ma_chi_nhanh;
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `phieu_nhap_kho`
--

DROP TABLE IF EXISTS `phieu_nhap_kho`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `phieu_nhap_kho` (
  `ma_pnk` varchar(20) NOT NULL,
  `ngay_nk` date NOT NULL DEFAULT curdate(),
  `ma_ncc` varchar(10) NOT NULL,
  `ma_nv` varchar(10) NOT NULL,
  `ma_chi_nhanh` varchar(10) NOT NULL,
  `tong_gia_tri` decimal(15,0) NOT NULL DEFAULT 0,
  `trang_thai` varchar(10) NOT NULL DEFAULT 'cho_duyet',
  `ghi_chu` varchar(300) DEFAULT NULL,
  PRIMARY KEY (`ma_pnk`),
  KEY `ix_pnk_chi_nhanh_ngay` (`ma_chi_nhanh`,`ngay_nk`),
  KEY `fk_pnk_ncc` (`ma_ncc`),
  KEY `fk_pnk_nv` (`ma_nv`),
  CONSTRAINT `fk_pnk_chi_nhanh` FOREIGN KEY (`ma_chi_nhanh`) REFERENCES `chi_nhanh` (`ma_chi_nhanh`),
  CONSTRAINT `fk_pnk_ncc` FOREIGN KEY (`ma_ncc`) REFERENCES `nha_cung_cap` (`ma_ncc`),
  CONSTRAINT `fk_pnk_nv` FOREIGN KEY (`ma_nv`) REFERENCES `nhan_vien` (`ma_nv`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `phieu_nhap_kho`
--

LOCK TABLES `phieu_nhap_kho` WRITE;
/*!40000 ALTER TABLE `phieu_nhap_kho` DISABLE KEYS */;
INSERT INTO `phieu_nhap_kho` VALUES ('PNK20260531192232','2026-05-31','NCC002','NV001','CN001',5011050000,'da_duyet','Ăn cứt'),('PNK20260531193535','2026-05-31','NCC001','NV001','CN001',20000,'da_duyet',NULL),('PNK20260531193653','2026-05-31','NCC002','NV001','CN001',120000,'da_duyet',NULL);
/*!40000 ALTER TABLE `phieu_nhap_kho` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `TRG_NHAP_KHO_CAP_NHAT_TON`
AFTER UPDATE ON `PHIEU_NHAP_KHO`
FOR EACH ROW
BEGIN
    IF NEW.trang_thai = 'da_duyet' AND OLD.trang_thai <> 'da_duyet' THEN
        INSERT INTO `TON_KHO` (`ma_chi_nhanh`, `ma_nl`, `sl_ton_kho_he_thong`, `sl_ton_kho_thuc_te`, `nguong_canh_bao`, `hao_hut_cost`)
        SELECT NEW.ma_chi_nhanh, ct.ma_nl, ct.so_luong, ct.so_luong, 0, 0
        FROM `CHI_TIET_NHAP_KHO` ct
        WHERE ct.ma_pnk = NEW.ma_pnk
        ON DUPLICATE KEY UPDATE
            sl_ton_kho_he_thong = sl_ton_kho_he_thong + VALUES(sl_ton_kho_he_thong),
            sl_ton_kho_thuc_te = sl_ton_kho_thuc_te + VALUES(sl_ton_kho_thuc_te);
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `tai_khoan`
--

DROP TABLE IF EXISTS `tai_khoan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tai_khoan` (
  `ma_tai_khoan` varchar(10) NOT NULL,
  `ten_tk` varchar(50) NOT NULL,
  `mat_khau` varchar(255) NOT NULL,
  `chuc_vu` varchar(20) NOT NULL,
  `trang_thai` varchar(10) NOT NULL DEFAULT 'active',
  `ma_nv` varchar(10) NOT NULL,
  PRIMARY KEY (`ma_tai_khoan`),
  UNIQUE KEY `uq_tk_ten` (`ten_tk`),
  KEY `fk_tk_nhan_vien` (`ma_nv`),
  CONSTRAINT `fk_tk_nhan_vien` FOREIGN KEY (`ma_nv`) REFERENCES `nhan_vien` (`ma_nv`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tai_khoan`
--

LOCK TABLES `tai_khoan` WRITE;
/*!40000 ALTER TABLE `tai_khoan` DISABLE KEYS */;
INSERT INTO `tai_khoan` VALUES ('TK001','admin_8am','$2y$10$VgT3NEZK0qik2K/KwvvKqeusp9Dmv4KaxsKVD6XgQzOfDH24xbUWO','quan_ly','active','NV001'),('TK002','bartender01','$2y$10$VgT3NEZK0qik2K/KwvvKqeusp9Dmv4KaxsKVD6XgQzOfDH24xbUWO','bartender','active','NV002'),('TK003','staff01','$2y$10$VgT3NEZK0qik2K/KwvvKqeusp9Dmv4KaxsKVD6XgQzOfDH24xbUWO','nhan_vien','active','NV003');
/*!40000 ALTER TABLE `tai_khoan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ton_kho`
--

DROP TABLE IF EXISTS `ton_kho`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ton_kho` (
  `ma_chi_nhanh` varchar(10) NOT NULL,
  `ma_nl` varchar(10) NOT NULL,
  `sl_ton_kho_he_thong` decimal(12,2) NOT NULL DEFAULT 0.00,
  `sl_ton_kho_thuc_te` decimal(12,2) NOT NULL DEFAULT 0.00,
  `nguong_canh_bao` decimal(12,2) NOT NULL DEFAULT 0.00,
  `hao_hut_cost` decimal(12,0) NOT NULL DEFAULT 0,
  PRIMARY KEY (`ma_chi_nhanh`,`ma_nl`),
  KEY `ix_ton_kho_canh_bao` (`ma_chi_nhanh`,`sl_ton_kho_he_thong`,`nguong_canh_bao`),
  KEY `fk_tk_nguyen_lieu` (`ma_nl`),
  CONSTRAINT `fk_tk_chi_nhanh` FOREIGN KEY (`ma_chi_nhanh`) REFERENCES `chi_nhanh` (`ma_chi_nhanh`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_tk_nguyen_lieu` FOREIGN KEY (`ma_nl`) REFERENCES `nguyen_lieu` (`ma_nl`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ton_kho`
--

LOCK TABLES `ton_kho` WRITE;
/*!40000 ALTER TABLE `ton_kho` DISABLE KEYS */;
INSERT INTO `ton_kho` VALUES ('CN001','NL001',999708.00,1005000.00,500.00,0),('CN001','NL002',3000.00,3000.00,300.00,0),('CN001','NL003',4360.00,25000.00,1500.00,0),('CN001','NL004',11860.00,15000.00,500.00,0),('CN001','NL005',45470.00,50000.00,5000.00,0),('CN001','NL006',8930.00,14000.00,300.00,0),('CN001','NL028',1312312.00,1312312.00,0.00,0);
/*!40000 ALTER TABLE `ton_kho` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `topping`
--

DROP TABLE IF EXISTS `topping`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `topping` (
  `ma_topping` varchar(10) NOT NULL,
  `ten_topping` varchar(100) NOT NULL,
  `gia_them` decimal(12,0) NOT NULL DEFAULT 0,
  `canh_bao` varchar(255) DEFAULT NULL,
  `trang_thai` varchar(10) NOT NULL DEFAULT 'active',
  PRIMARY KEY (`ma_topping`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `topping`
--

LOCK TABLES `topping` WRITE;
/*!40000 ALTER TABLE `topping` DISABLE KEYS */;
/*!40000 ALTER TABLE `topping` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `vw_menu_hien_thi`
--

DROP TABLE IF EXISTS `vw_menu_hien_thi`;
/*!50001 DROP VIEW IF EXISTS `vw_menu_hien_thi`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `vw_menu_hien_thi` AS SELECT
 1 AS `ma_mon`,
  1 AS `ten_mon`,
  1 AS `don_gia`,
  1 AS `mo_ta`,
  1 AS `hinh_anh`,
  1 AS `trang_thai`,
  1 AS `ten_danh_muc` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `vw_order_dashboard`
--

DROP TABLE IF EXISTS `vw_order_dashboard`;
/*!50001 DROP VIEW IF EXISTS `vw_order_dashboard`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `vw_order_dashboard` AS SELECT
 1 AS `ma_order`,
  1 AS `so_ban`,
  1 AS `ten_chi_nhanh`,
  1 AS `ten_kh`,
  1 AS `trang_thai`,
  1 AS `ngay_order`,
  1 AS `gio_order`,
  1 AS `tong_tien` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `vw_ton_kho_tong_quan`
--

DROP TABLE IF EXISTS `vw_ton_kho_tong_quan`;
/*!50001 DROP VIEW IF EXISTS `vw_ton_kho_tong_quan`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `vw_ton_kho_tong_quan` AS SELECT
 1 AS `ten_chi_nhanh`,
  1 AS `ten_nl`,
  1 AS `don_vi`,
  1 AS `sl_ton_kho_he_thong`,
  1 AS `sl_ton_kho_thuc_te`,
  1 AS `nguong_canh_bao`,
  1 AS `hao_hut_cost`,
  1 AS `trang_thai_kho` */;
SET character_set_client = @saved_cs_client;

--
-- Dumping events for database '8am_coffee'
--

--
-- Dumping routines for database '8am_coffee'
--

--
-- Final view structure for view `vw_menu_hien_thi`
--

/*!50001 DROP VIEW IF EXISTS `vw_menu_hien_thi`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_menu_hien_thi` AS select `m`.`ma_mon` AS `ma_mon`,`m`.`ten_mon` AS `ten_mon`,`m`.`don_gia` AS `don_gia`,`m`.`mo_ta` AS `mo_ta`,`m`.`hinh_anh` AS `hinh_anh`,`m`.`trang_thai` AS `trang_thai`,`dm`.`ten_danh_muc` AS `ten_danh_muc` from (`mon` `m` join `danh_muc` `dm` on(`dm`.`ma_danh_muc` = `m`.`ma_danh_muc`)) where `m`.`trang_thai` = 'active' */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_order_dashboard`
--

/*!50001 DROP VIEW IF EXISTS `vw_order_dashboard`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_order_dashboard` AS select `o`.`ma_order` AS `ma_order`,`b`.`so_ban` AS `so_ban`,`cn`.`ten_chi_nhanh` AS `ten_chi_nhanh`,`k`.`ten_kh` AS `ten_kh`,`o`.`trang_thai` AS `trang_thai`,`o`.`ngay_order` AS `ngay_order`,`o`.`gio_order` AS `gio_order`,coalesce(sum(`cto`.`so_luong` * `cto`.`don_gia_tai_thoi_diem`),0) AS `tong_tien` from ((((`orders` `o` join `ban` `b` on(`b`.`ma_ban` = `o`.`ma_ban`)) join `chi_nhanh` `cn` on(`cn`.`ma_chi_nhanh` = `o`.`ma_chi_nhanh`)) left join `khach_hang` `k` on(`k`.`ma_kh` = `o`.`ma_kh`)) left join `chi_tiet_order` `cto` on(`cto`.`ma_order` = `o`.`ma_order`)) where `o`.`trang_thai` in ('cho_xac_nhan','da_xac_nhan','dang_pha_che') group by `o`.`ma_order`,`b`.`so_ban`,`cn`.`ten_chi_nhanh`,`k`.`ten_kh`,`o`.`trang_thai`,`o`.`ngay_order`,`o`.`gio_order` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_ton_kho_tong_quan`
--

/*!50001 DROP VIEW IF EXISTS `vw_ton_kho_tong_quan`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_ton_kho_tong_quan` AS select `cn`.`ten_chi_nhanh` AS `ten_chi_nhanh`,`nl`.`ten_nl` AS `ten_nl`,`nl`.`don_vi` AS `don_vi`,`tk`.`sl_ton_kho_he_thong` AS `sl_ton_kho_he_thong`,`tk`.`sl_ton_kho_thuc_te` AS `sl_ton_kho_thuc_te`,`tk`.`nguong_canh_bao` AS `nguong_canh_bao`,`tk`.`hao_hut_cost` AS `hao_hut_cost`,case when `tk`.`sl_ton_kho_he_thong` <= 0 then 'HET_HANG' when `tk`.`sl_ton_kho_he_thong` < `tk`.`nguong_canh_bao` then 'SAP_HET' else 'DU_HANG' end AS `trang_thai_kho` from ((`ton_kho` `tk` join `chi_nhanh` `cn` on(`cn`.`ma_chi_nhanh` = `tk`.`ma_chi_nhanh`)) join `nguyen_lieu` `nl` on(`nl`.`ma_nl` = `tk`.`ma_nl`)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-31 20:06:05
