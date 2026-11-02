SET NAMES utf8mb4;
USE royaldine_5s;

ALTER TABLE `suppliers` 
ADD COLUMN `status` ENUM('active', 'inactive') DEFAULT 'active' COMMENT 'Trạng thái: active (đang giao dịch), inactive (ngừng)' AFTER `debt_balance`;

ALTER TABLE `ingredients` 
ADD COLUMN `group_name` VARCHAR(100) DEFAULT NULL COMMENT 'Nhóm phân loại nguyên vật liệu' AFTER `name`,
ADD COLUMN `supplier_id` INT DEFAULT NULL COMMENT 'ID Nhà cung cấp chính' AFTER `cost_price`;

ALTER TABLE `ingredients`
ADD CONSTRAINT `fk_ingredient_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`) ON DELETE SET NULL;

ALTER TABLE `stock_receipt_details` 
ADD COLUMN `reason` VARCHAR(255) DEFAULT NULL COMMENT 'Lý do chênh lệch kiểm kê' AFTER `unit_cost`;

DELETE FROM `suppliers` WHERE `id` IN (1, 2, 3);


INSERT INTO `suppliers` (`id`, `name`, `contact_person`, `phone`, `address`, `debt_balance`, `status`) VALUES
(1, 'Công ty Thực phẩm Nhập khẩu Gofood', 'Mr. Tuấn', '0987654321', 'Quận 1, TP.HCM', 0.00, 'active'),
(2, 'Vựa Hải sản Cao cấp Biển Đông', 'Ms. Hương', '0912345678', 'Quận 3, TP.HCM', 5000000.00, 'active'),
(3, 'Đại lý Rượu vang Đa Quốc Gia', 'Mr. Long', '0909090909', 'Quận 7, TP.HCM', 0.00, 'active');

UPDATE `ingredients` SET `group_name` = 'Thịt Cao Cấp', `supplier_id` = 1 WHERE `ingredient_code` = 'NVL01';
UPDATE `ingredients` SET `group_name` = 'Hải Sản Tươi', `supplier_id` = 2 WHERE `ingredient_code` = 'NVL02';
UPDATE `ingredients` SET `group_name` = 'Bơ Sữa & Gia Vị', `supplier_id` = 1 WHERE `ingredient_code` = 'NVL03';
UPDATE `ingredients` SET `group_name` = 'Đồ Uống Có Cồn', `supplier_id` = 3 WHERE `ingredient_code` = 'NVL04';

INSERT INTO `ingredients` (`ingredient_code`, `name`, `unit`, `stock_quantity`, `min_stock_level`, `cost_price`, `group_name`, `supplier_id`) VALUES
('NVL05', 'Nấm Truffle đen Pháp', 'g', 500.00, 100.00, 45000.00, 'Nấm & Rau củ', 1),
('NVL06', 'Tôm hùm Alaska sống', 'kg', 15.00, 5.00, 1200000.00, 'Hải Sản Tươi', 2),
('NVL07', 'Trứng cá tầm Caviar Beluga', 'g', 100.00, 20.00, 85000.00, 'Hải Sản Tươi', 2),
('NVL08', 'Cá hồi Na Uy phi lê', 'kg', 12.00, 5.00, 450000.00, 'Hải Sản Tươi', 2),
('NVL09', 'Đùi heo muối Iberico Bellota', 'g', 3000.00, 500.00, 2500.00, 'Thịt Cao Cấp', 1),
('NVL10', 'Nhụy hoa nghệ tây Saffron', 'g', 50.00, 10.00, 450000.00, 'Bơ Sữa & Gia Vị', 1),
('NVL11', 'Phô mai Burrata Ý', 'g', 2000.00, 500.00, 800.00, 'Bơ Sữa & Gia Vị', 1),
('NVL12', 'Rượu Champagne Dom Pérignon', 'ml', 3000.00, 750.00, 6500.00, 'Đồ Uống Có Cồn', 3),
('NVL13', 'Sườn cừu Úc có xương', 'kg', 10.00, 3.00, 650000.00, 'Thịt Cao Cấp', 1),
('NVL14', 'Nước khoáng có gas Perrier', 'ml', 10000.00, 2000.00, 150.00, 'Đồ Uống Không Cồn', 3),
('NVL15', 'Lá vàng thực phẩm 24K', 'Lá', 50.00, 10.00, 120000.00, 'Bơ Sữa & Gia Vị', 1);

INSERT INTO `stock_receipts` (`receipt_code`, `supplier_id`, `user_id`, `receipt_type`, `total_amount`, `note`, `created_at`) VALUES
('PN261029150000', 1, 5, 'Import', 12500000.00, 'Nhập bổ sung nguyên liệu cao cấp mùng 1 đầu tháng', NOW());

INSERT INTO `stock_receipt_details` (`stock_receipt_id`, `ingredient_id`, `quantity`, `unit_cost`) VALUES
(1, 1, 1000.00, 4500.00),
(1, 3, 500.00, 350.00);

UPDATE stock_receipts 
SET receipt_type = 'Export_Sale' 
WHERE receipt_code LIKE 'VX%' AND (receipt_type IS NULL OR receipt_type = '');