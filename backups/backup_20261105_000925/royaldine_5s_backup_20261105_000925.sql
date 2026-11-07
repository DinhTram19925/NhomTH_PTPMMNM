-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: royaldine_5s
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
-- Table structure for table `areas`
--

DROP TABLE IF EXISTS `areas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `areas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `surcharge_percent` decimal(5,2) DEFAULT 0.00,
  `description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `areas`
--

LOCK TABLES `areas` WRITE;
/*!40000 ALTER TABLE `areas` DISABLE KEYS */;
INSERT INTO `areas` VALUES (1,'Sảnh的交 hưởng (Symphony Hall)',0.00,'Không gian mở có nhạc công Piano'),(2,'Phòng VIP Hầm Rượu (Royal Cellar VIP)',10.00,'Phòng riêng tư, phụ thu 10% tổng hóa đơn');
/*!40000 ALTER TABLE `areas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `module_name` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `severity` enum('INFO','WARNING','CRITICAL') DEFAULT 'INFO',
  `ip_address` varchar(45) DEFAULT '127.0.0.1',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (1,1,'INIT_RBAC','Phân quyền & Hệ thống','Khởi tạo ma trận phân quyền 5 vai trò chuẩn RoyalDine 5S','INFO','127.0.0.1','2026-10-28 03:12:21'),(2,1,'PRICE_CONTROL','Kiểm soát sai lệch','Bật chế độ bắt buộc nhập lý do khi Hủy món hoặc Sửa hóa đơn trên POS','WARNING','127.0.0.1','2026-10-28 03:12:21'),(3,1,'CHECK_OUT','Chấm công','Chấm công TAN CA (11:00:23) cho Đinh Thị Mai Trâm (Ca Sáng (Chuẩn bị & Bữa trưa))','INFO','::1','2026-10-28 04:00:23'),(4,1,'CHECK_OUT','Chấm công','Chấm công TAN CA (11:00:26) cho Đoàn Bùi Khánh Vy (Ca Tối (Fine Dining VIP))','INFO','::1','2026-10-28 04:00:26'),(5,1,'CHECK_IN','Chấm công','Chấm công VÀO CA (11:00:30 - ĐÚNG GIỜ) cho La Thị Trà My (Ca Tối (Fine Dining VIP))','INFO','::1','2026-10-28 04:00:30'),(6,1,'ASSIGN_SHIFT','Phân ca làm việc','Phân ca ID #3 ngày 2026-10-28 cho nhân viên ID #4','INFO','::1','2026-10-28 04:01:04'),(7,1,'ASSIGN_SHIFT','Phân ca làm việc','Phân ca ID #2 ngày 2026-10-28 cho nhân viên ID #4','INFO','::1','2026-10-28 04:01:13'),(8,1,'MARK_ABSENT','Chấm công','Ghi nhận VẮNG MẶT ca Ca Tối (Fine Dining VIP) đối với Bếp trưởng RoyalDine','CRITICAL','::1','2026-10-28 04:01:21'),(9,1,'CHECK_IN','Chấm công','Chấm công VÀO CA (11:01:26 - ĐI TRỄ) cho Bếp trưởng RoyalDine (Ca Gãy (Cao điểm Trưa & Tối))','WARNING','::1','2026-10-28 04:01:26'),(10,1,'ASSIGN_SHIFT','Phân ca làm việc','Phân ca ID #3 ngày 2026-10-07 cho nhân viên ID #3','INFO','::1','2026-10-28 04:01:43'),(11,1,'INIT_RBAC','Phân quyền & Hệ thống','Khởi tạo ma trận phân quyền 5 vai trò chuẩn RoyalDine 5S','INFO','127.0.0.1','2026-11-04 16:26:15'),(12,1,'PRICE_CONTROL','Kiểm soát sai lệch','Bật chế độ bắt buộc nhập lý do khi Hủy món hoặc Sửa hóa đơn trên POS','WARNING','127.0.0.1','2026-11-04 16:26:15');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cash_shifts`
--

DROP TABLE IF EXISTS `cash_shifts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cash_shifts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cashier_id` int(11) NOT NULL,
  `opened_at` datetime DEFAULT current_timestamp(),
  `closed_at` datetime DEFAULT NULL,
  `opening_cash` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total_cash_sales` decimal(14,2) DEFAULT 0.00,
  `total_cash_in` decimal(14,2) DEFAULT 0.00,
  `total_cash_out` decimal(14,2) DEFAULT 0.00,
  `expected_closing_cash` decimal(14,2) DEFAULT 0.00,
  `actual_closing_cash` decimal(14,2) DEFAULT NULL,
  `discrepancy` decimal(14,2) DEFAULT 0.00,
  `note` text DEFAULT NULL,
  `status` enum('Open','Closed') DEFAULT 'Open',
  PRIMARY KEY (`id`),
  KEY `cashier_id` (`cashier_id`),
  CONSTRAINT `cash_shifts_ibfk_1` FOREIGN KEY (`cashier_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cash_shifts`
--

LOCK TABLES `cash_shifts` WRITE;
/*!40000 ALTER TABLE `cash_shifts` DISABLE KEYS */;
/*!40000 ALTER TABLE `cash_shifts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cash_transactions`
--

DROP TABLE IF EXISTS `cash_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cash_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `shift_id` int(11) NOT NULL,
  `type` enum('IN','OUT') NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `note` text DEFAULT NULL,
  `creator` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `shift_id` (`shift_id`),
  CONSTRAINT `cash_transactions_ibfk_1` FOREIGN KEY (`shift_id`) REFERENCES `cash_shifts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cash_transactions`
--

LOCK TABLES `cash_transactions` WRITE;
/*!40000 ALTER TABLE `cash_transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `cash_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `course_type` enum('Appetizer','Soup_Salad','Main_Course','Dessert','Beverage','Set_Menu') NOT NULL,
  `display_order` int(11) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Khai vị Tinh hoa (Appetizers)','Appetizer',1,1),(2,'Món chính Thượng hạng (Main Courses)','Main_Course',2,1),(3,'Tráng miệng Hoàng gia (Desserts)','Dessert',3,1),(4,'Rượu vang & Cocktail (Fine Wines)','Beverage',4,1);
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `tier` enum('Standard','Silver','Gold','Diamond') DEFAULT 'Standard',
  `points` int(11) DEFAULT 0,
  `total_spent` decimal(14,2) DEFAULT 0.00,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `phone` (`phone`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
INSERT INTO `customers` VALUES (1,'Nguyễn Hoàng Gia (Khách VIP)','0988888888','vip@royaldine.vn','Gold',500,25000000.00,'2026-11-04 23:20:51'),(2,'Mai Trâm Đinh Thị','1234567890','tram.dtm.65cntt@ntu.edu.vn','Standard',0,0.00,'2026-11-04 23:28:24');
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dining_tables`
--

DROP TABLE IF EXISTS `dining_tables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dining_tables` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `area_id` int(11) NOT NULL,
  `table_name` varchar(50) NOT NULL,
  `capacity` int(11) NOT NULL DEFAULT 4,
  `qr_token` varchar(100) DEFAULT NULL,
  `status` enum('Available','Occupied','Reserved','Cleaning') DEFAULT 'Available',
  PRIMARY KEY (`id`),
  UNIQUE KEY `qr_token` (`qr_token`),
  KEY `area_id` (`area_id`),
  CONSTRAINT `dining_tables_ibfk_1` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dining_tables`
--

LOCK TABLES `dining_tables` WRITE;
/*!40000 ALTER TABLE `dining_tables` DISABLE KEYS */;
INSERT INTO `dining_tables` VALUES (1,1,'T-101',4,'QR_T101_RD5S','Occupied'),(2,1,'T-102',6,'QR_T102_RD5S','Available'),(3,2,'VIP-01',8,'QR_VIP01_RD5S','Available');
/*!40000 ALTER TABLE `dining_tables` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ingredients`
--

DROP TABLE IF EXISTS `ingredients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ingredients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ingredient_code` varchar(30) NOT NULL,
  `name` varchar(150) NOT NULL,
  `unit` varchar(20) NOT NULL,
  `stock_quantity` decimal(12,2) DEFAULT 0.00,
  `min_stock_level` decimal(12,2) DEFAULT 0.00,
  `cost_price` decimal(12,2) DEFAULT 0.00,
  `group_name` varchar(100) DEFAULT 'Nguyên liệu 5 sao',
  `supplier_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ingredient_code` (`ingredient_code`),
  KEY `fk_ingredient_supplier` (`supplier_id`),
  CONSTRAINT `fk_ingredient_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ingredients`
--

LOCK TABLES `ingredients` WRITE;
/*!40000 ALTER TABLE `ingredients` DISABLE KEYS */;
INSERT INTO `ingredients` VALUES (1,'NVL01','Thăn bò Wagyu A5 Nhật Bản','g',5000.00,1000.00,4500.00,'Thịt Cao Cấp',1),(2,'NVL02','Sò điệp Hokkaido tươi','g',3000.00,500.00,1200.00,'Hải Sản Tươi',2),(3,'NVL03','Bơ lạt Pháp President','g',2000.00,300.00,350.00,'Bơ Sữa & Gia Vị',1),(4,'NVL04','Rượu vang Château Margaux','ml',4500.00,750.00,2200.00,'Đồ Uống Có Cồn',3),(5,'NVL05','Nấm Truffle đen Pháp','g',500.00,100.00,45000.00,'Nấm & Rau củ',1),(6,'NVL06','Tôm hùm Alaska sống','kg',15.00,5.00,1200000.00,'Hải Sản Tươi',2),(7,'NVL07','Trứng cá tầm Caviar Beluga','g',100.00,20.00,85000.00,'Hải Sản Tươi',2),(8,'NVL08','Cá hồi Na Uy phi lê','kg',12.00,5.00,450000.00,'Hải Sản Tươi',2),(9,'NVL09','Đùi heo muối Iberico Bellota','g',3000.00,500.00,2500.00,'Thịt Cao Cấp',1),(10,'NVL10','Nhụy hoa nghệ tây Saffron','g',50.00,10.00,450000.00,'Bơ Sữa & Gia Vị',1),(11,'NVL11','Phô mai Burrata Ý','g',2000.00,500.00,800.00,'Bơ Sữa & Gia Vị',1),(12,'NVL12','Rượu Champagne Dom Pérignon','ml',3000.00,750.00,6500.00,'Đồ Uống Có Cồn',3),(13,'NVL13','Sườn cừu Úc có xương','kg',10.00,3.00,650000.00,'Thịt Cao Cấp',1),(14,'NVL14','Nước khoáng có gas Perrier','ml',10000.00,2000.00,150.00,'Đồ Uống Không Cồn',3),(15,'NVL15','Lá vàng thực phẩm 24K','Lá',50.00,10.00,120000.00,'Bơ Sữa & Gia Vị',1);
/*!40000 ALTER TABLE `ingredients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `item_modifiers`
--

DROP TABLE IF EXISTS `item_modifiers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `item_modifiers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `menu_item_id` int(11) NOT NULL,
  `modifier_group` varchar(50) NOT NULL DEFAULT 'Tùy chọn chế biến',
  `modifier_name` varchar(100) NOT NULL,
  `extra_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `menu_item_id` (`menu_item_id`),
  CONSTRAINT `item_modifiers_ibfk_1` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `item_modifiers`
--

LOCK TABLES `item_modifiers` WRITE;
/*!40000 ALTER TABLE `item_modifiers` DISABLE KEYS */;
INSERT INTO `item_modifiers` VALUES (1,1,'Độ chín (Doneness)','Rare (Tái đỏ 25%)',0.00),(2,1,'Độ chín (Doneness)','Medium Rare (Tái chín 50% - Khuyên dùng)',0.00),(3,1,'Độ chín (Doneness)','Well Done (Chín kỹ 100%)',0.00),(4,1,'Sốt dùng kèm','Sốt nấm Truffle đen thượng hạng',65000.00),(5,1,'Sốt dùng kèm','Sốt rượu vang đỏ Bordeaux',0.00),(6,1,'Lưu ý dị ứng','Không dùng bơ sữa (Dairy-free)',0.00);
/*!40000 ALTER TABLE `item_modifiers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `menu_items`
--

DROP TABLE IF EXISTS `menu_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `item_code` varchar(30) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `unit` varchar(30) DEFAULT 'Phần',
  `price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `image_url` varchar(255) DEFAULT NULL,
  `is_combo` tinyint(1) DEFAULT 0,
  `status` varchar(30) DEFAULT 'Available',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `item_code` (`item_code`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `menu_items_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `menu_items`
--

LOCK TABLES `menu_items` WRITE;
/*!40000 ALTER TABLE `menu_items` DISABLE KEYS */;
INSERT INTO `menu_items` VALUES (1,1,'RD-APP01','Sò điệp Hokkaido áp chảo sốt bơ chanh',NULL,'Đĩa',450000.00,NULL,0,'Available','2026-10-28 09:28:00'),(2,2,'RD-MAIN01','Thăn ngoại bò Wagyu A5 nướng đá muối',NULL,'Phần',1850000.00,NULL,0,'Available','2026-10-28 09:28:00'),(4,4,'RD-WINE01','Rượu vang đỏ Château Margaux (Ly)',NULL,'Ly',650000.00,NULL,0,'Available','2026-10-28 09:28:00');
/*!40000 ALTER TABLE `menu_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_details`
--

DROP TABLE IF EXISTS `order_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `menu_item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(12,2) NOT NULL,
  `modifier_notes` varchar(255) DEFAULT NULL,
  `course_priority` int(11) DEFAULT 1,
  `kds_status` enum('Waiting','Cooking','Ready','Served','Cancelled') DEFAULT 'Waiting',
  `cancel_reason` varchar(255) DEFAULT NULL,
  `ordered_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `menu_item_id` (`menu_item_id`),
  CONSTRAINT `order_details_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_details_ibfk_2` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_details`
--

LOCK TABLES `order_details` WRITE;
/*!40000 ALTER TABLE `order_details` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_logs`
--

DROP TABLE IF EXISTS `order_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action_type` enum('CreateOrder','TransferTable','MergeTable','CancelItem','Checkout') NOT NULL,
  `description` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `order_logs_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  CONSTRAINT `order_logs_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_logs`
--

LOCK TABLES `order_logs` WRITE;
/*!40000 ALTER TABLE `order_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_code` varchar(30) NOT NULL,
  `table_id` int(11) NOT NULL,
  `reservation_id` int(11) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `waiter_id` int(11) NOT NULL,
  `guest_count` int(11) DEFAULT 2,
  `subtotal` decimal(14,2) DEFAULT 0.00,
  `area_surcharge_amount` decimal(12,2) DEFAULT 0.00,
  `service_charge_amount` decimal(12,2) DEFAULT 0.00,
  `discount_amount` decimal(12,2) DEFAULT 0.00,
  `deposit_deducted` decimal(12,2) DEFAULT 0.00,
  `vat_amount` decimal(12,2) DEFAULT 0.00,
  `total_amount` decimal(14,2) DEFAULT 0.00,
  `status` enum('Open','PendingPayment','Paid','Cancelled') DEFAULT 'Open',
  `opened_at` datetime DEFAULT current_timestamp(),
  `closed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_code` (`order_code`),
  KEY `table_id` (`table_id`),
  KEY `reservation_id` (`reservation_id`),
  KEY `customer_id` (`customer_id`),
  KEY `waiter_id` (`waiter_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`table_id`) REFERENCES `dining_tables` (`id`),
  CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`),
  CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `orders_ibfk_4` FOREIGN KEY (`waiter_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `cashier_id` int(11) NOT NULL,
  `payment_method` enum('Cash','VietQR','CreditCard') NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `transaction_ref` varchar(100) DEFAULT NULL,
  `paid_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `cashier_id` (`cashier_id`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`cashier_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `promotions`
--

DROP TABLE IF EXISTS `promotions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `promotions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(30) NOT NULL,
  `name` varchar(150) NOT NULL,
  `discount_type` enum('Percent','Fixed') NOT NULL,
  `discount_value` decimal(12,2) NOT NULL,
  `max_discount` decimal(12,2) DEFAULT NULL,
  `min_order_value` decimal(12,2) DEFAULT 0.00,
  `min_tier` enum('Standard','Silver','Gold','Diamond') DEFAULT 'Standard',
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `promotions`
--

LOCK TABLES `promotions` WRITE;
/*!40000 ALTER TABLE `promotions` DISABLE KEYS */;
INSERT INTO `promotions` VALUES (1,'ROYAL10','Giảm 10% Fine Dining (Tối đa 300k)','Percent',10.00,300000.00,500000.00,'Standard','2025-01-01 00:00:00','2027-12-31 23:59:59',1);
/*!40000 ALTER TABLE `promotions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recipes_bom`
--

DROP TABLE IF EXISTS `recipes_bom`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `recipes_bom` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `menu_item_id` int(11) NOT NULL,
  `ingredient_id` int(11) NOT NULL,
  `quantity_required` decimal(10,2) NOT NULL,
  `wastage_percent` decimal(5,2) DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `menu_item_id` (`menu_item_id`),
  KEY `ingredient_id` (`ingredient_id`),
  CONSTRAINT `recipes_bom_ibfk_1` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `recipes_bom_ibfk_2` FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recipes_bom`
--

LOCK TABLES `recipes_bom` WRITE;
/*!40000 ALTER TABLE `recipes_bom` DISABLE KEYS */;
INSERT INTO `recipes_bom` VALUES (1,2,1,200.00,5.00),(2,2,3,20.00,0.00),(3,4,3,200.00,1.00);
/*!40000 ALTER TABLE `recipes_bom` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reservations`
--

DROP TABLE IF EXISTS `reservations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reservations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_code` varchar(30) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `table_id` int(11) DEFAULT NULL,
  `booking_time` datetime NOT NULL,
  `guest_count` int(11) NOT NULL,
  `deposit_amount` decimal(12,2) DEFAULT 0.00,
  `special_request` text DEFAULT NULL,
  `status` enum('Pending','Confirmed','CheckedIn','Cancelled') DEFAULT 'Pending',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `booking_code` (`booking_code`),
  KEY `customer_id` (`customer_id`),
  KEY `table_id` (`table_id`),
  CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`table_id`) REFERENCES `dining_tables` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reservations`
--

LOCK TABLES `reservations` WRITE;
/*!40000 ALTER TABLE `reservations` DISABLE KEYS */;
INSERT INTO `reservations` VALUES (1,'BK20261104232824275',2,1,'2026-11-05 23:28:00',3,1000000.00,'','CheckedIn','2026-11-04 23:28:24');
/*!40000 ALTER TABLE `reservations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_code` varchar(30) NOT NULL,
  `role_name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_code` (`role_code`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'ADMIN','Quản lý nhà hàng (Leader)'),(2,'HOSTESS','Lễ tân & Đặt bàn VIP'),(3,'WAITER','Phục vụ & Thu ngân POS'),(4,'CHEF','Bếp trưởng điều phối KDS'),(5,'STOCK','Thủ kho & Kế toán');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff_schedules`
--

DROP TABLE IF EXISTS `staff_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `staff_schedules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `shift_id` int(11) NOT NULL,
  `work_date` date NOT NULL,
  `check_in` time DEFAULT NULL,
  `check_out` time DEFAULT NULL,
  `attendance_status` enum('Scheduled','OnTime','Late','Absent') DEFAULT 'Scheduled',
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_shift_date` (`user_id`,`shift_id`,`work_date`),
  KEY `shift_id` (`shift_id`),
  CONSTRAINT `staff_schedules_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `staff_schedules_ibfk_2` FOREIGN KEY (`shift_id`) REFERENCES `work_shifts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff_schedules`
--

LOCK TABLES `staff_schedules` WRITE;
/*!40000 ALTER TABLE `staff_schedules` DISABLE KEYS */;
INSERT INTO `staff_schedules` VALUES (1,1,1,'2026-10-28','06:25:00','11:00:23','OnTime','Quản lý kiểm tra đầu ca','2026-10-28 03:12:21'),(2,2,2,'2026-10-28','14:40:00','11:00:26','Late','Lễ tân trực sảnh VIP (Trễ 10 phút)','2026-10-28 03:12:21'),(3,3,2,'2026-10-28','11:00:30',NULL,'OnTime','Thu ngân trực POS ca tối','2026-10-28 03:12:21'),(4,4,3,'2026-10-28','11:01:26',NULL,'Late','','2026-10-28 04:01:04'),(5,4,2,'2026-10-28',NULL,NULL,'Absent','','2026-10-28 04:01:13'),(6,3,3,'2026-10-07',NULL,NULL,'Scheduled','','2026-10-28 04:01:43');
/*!40000 ALTER TABLE `staff_schedules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_receipt_details`
--

DROP TABLE IF EXISTS `stock_receipt_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_receipt_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `stock_receipt_id` int(11) NOT NULL,
  `ingredient_id` int(11) NOT NULL,
  `quantity` decimal(12,2) NOT NULL,
  `unit_cost` decimal(12,2) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_receipt_id` (`stock_receipt_id`),
  KEY `ingredient_id` (`ingredient_id`),
  CONSTRAINT `stock_receipt_details_ibfk_1` FOREIGN KEY (`stock_receipt_id`) REFERENCES `stock_receipts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_receipt_details_ibfk_2` FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_receipt_details`
--

LOCK TABLES `stock_receipt_details` WRITE;
/*!40000 ALTER TABLE `stock_receipt_details` DISABLE KEYS */;
INSERT INTO `stock_receipt_details` VALUES (1,1,1,1000.00,4500.00,NULL),(2,1,3,500.00,350.00,NULL);
/*!40000 ALTER TABLE `stock_receipt_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_receipts`
--

DROP TABLE IF EXISTS `stock_receipts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_receipts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `receipt_code` varchar(30) NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `receipt_type` enum('Import','Export_Sale','Export_Damage','Audit_Adjust') NOT NULL,
  `total_amount` decimal(14,2) DEFAULT 0.00,
  `note` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `receipt_code` (`receipt_code`),
  KEY `supplier_id` (`supplier_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `stock_receipts_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  CONSTRAINT `stock_receipts_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_receipts`
--

LOCK TABLES `stock_receipts` WRITE;
/*!40000 ALTER TABLE `stock_receipts` DISABLE KEYS */;
INSERT INTO `stock_receipts` VALUES (1,'PN261029150000',1,5,'Import',12500000.00,'Nhập bổ sung nguyên liệu cao cấp mùng 1 đầu tháng','2026-11-04 23:20:51');
/*!40000 ALTER TABLE `stock_receipts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `debt_balance` decimal(14,2) DEFAULT 0.00,
  `status` enum('active','inactive') DEFAULT 'active',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suppliers`
--

LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
INSERT INTO `suppliers` VALUES (1,'Công ty Thực phẩm Nhập khẩu Gofood','Mr. Tuấn','0987654321','Quận 1, TP.HCM',0.00,'active'),(2,'Vựa Hải sản Cao cấp Biển Đông','Ms. Hương','0912345678','Quận 3, TP.HCM',5000000.00,'active'),(3,'Đại lý Rượu vang Đa Quốc Gia','Mr. Long','0909090909','Quận 7, TP.HCM',0.00,'active');
/*!40000 ALTER TABLE `suppliers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,1,'Đinh Thị Mai Trâm','admin','$2y$10$HKjEDZJJxJ.U.XKOzNoK8e7Md/b7Ts08b/VkUTrh6CU3/Uay56zhq','0901000001',1,'2026-10-28 09:28:00'),(2,2,'Đoàn Bùi Khánh Vy','hostess','$2y$10$HKjEDZJJxJ.U.XKOzNoK8e7Md/b7Ts08b/VkUTrh6CU3/Uay56zhq','0901000002',1,'2026-10-28 09:28:00'),(3,3,'La Thị Trà My','cashier','$2y$10$HKjEDZJJxJ.U.XKOzNoK8e7Md/b7Ts08b/VkUTrh6CU3/Uay56zhq','0901000003',1,'2026-10-28 09:28:00'),(4,4,'Bếp trưởng RoyalDine','chef','$2y$10$HKjEDZJJxJ.U.XKOzNoK8e7Md/b7Ts08b/VkUTrh6CU3/Uay56zhq','0901000004',1,'2026-10-28 09:28:00'),(5,5,'Quảng Thị Kim Hiền','stock','$2y$10$HKjEDZJJxJ.U.XKOzNoK8e7Md/b7Ts08b/VkUTrh6CU3/Uay56zhq','0901000005',1,'2026-10-28 09:28:00'),(6,5,'Nguyễn Văn Tứ','tunv','$2y$10$5.QIwI.9lYqDO81BZK8a8ORTQtFsp90mG9ZNR8HNmkx1fS5I0zqly','0869052577',0,'2026-10-28 09:39:32');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `work_shifts`
--

DROP TABLE IF EXISTS `work_shifts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `work_shifts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `shift_code` varchar(20) NOT NULL,
  `shift_name` varchar(100) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `allowance` decimal(12,2) DEFAULT 0.00,
  `description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `shift_code` (`shift_code`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `work_shifts`
--

LOCK TABLES `work_shifts` WRITE;
/*!40000 ALTER TABLE `work_shifts` DISABLE KEYS */;
INSERT INTO `work_shifts` VALUES (1,'SHIFT_MORNING','Ca Sáng (Chuẩn bị & Bữa trưa)','06:30:00','14:30:00',50000.00,'Chuẩn bị nguyên liệu, đón khách ăn trưa'),(2,'SHIFT_EVENING','Ca Tối (Fine Dining VIP)','14:30:00','22:30:00',80000.00,'Phục vụ tiệc tối 5 sao, đóng quầy'),(3,'SHIFT_SPLIT','Ca Gãy (Cao điểm Trưa & Tối)','10:00:00','21:30:00',100000.00,'Nghỉ giữa ca từ 14:00 - 17:00');
/*!40000 ALTER TABLE `work_shifts` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-05  0:09:30
