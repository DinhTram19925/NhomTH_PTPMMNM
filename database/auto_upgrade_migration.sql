SET NAMES utf8mb4;
CREATE DATABASE IF NOT EXISTS royaldine_5s CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE royaldine_5s;

CREATE TABLE IF NOT EXISTS work_shifts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shift_code VARCHAR(30) DEFAULT NULL UNIQUE,
    shift_name VARCHAR(100) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    allowance DECIMAL(12,2) DEFAULT 0,
    description VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS staff_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    shift_id INT NOT NULL,
    work_date DATE NOT NULL,
    check_in TIME DEFAULT NULL,
    check_out TIME DEFAULT NULL,
    attendance_status ENUM('Scheduled','OnTime','Late','Absent') DEFAULT 'Scheduled',
    note VARCHAR(255) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_shift_date (user_id, shift_id, work_date),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (shift_id) REFERENCES work_shifts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action_type VARCHAR(50) NOT NULL,
    module_name VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    severity ENUM('INFO','WARNING','CRITICAL') DEFAULT 'INFO',
    ip_address VARCHAR(45) DEFAULT '127.0.0.1',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cash_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shift_id INT NOT NULL,
    type ENUM('IN', 'OUT') NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    note TEXT,
    creator VARCHAR(100),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shift_id) REFERENCES cash_shifts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @dbname = DATABASE();

ALTER TABLE categories MODIFY COLUMN course_type ENUM('Appetizer', 'Soup_Salad', 'Main_Course', 'Dessert', 'Beverage', 'Set_Menu') NOT NULL DEFAULT 'Main_Course';
ALTER TABLE menu_items MODIFY COLUMN status VARCHAR(30) NOT NULL DEFAULT 'Available';
ALTER TABLE audit_logs MODIFY COLUMN module_name VARCHAR(100) NOT NULL;

SET @q = (SELECT IF(COUNT(*) = 0, "ALTER TABLE work_shifts ADD COLUMN shift_code VARCHAR(30) DEFAULT NULL UNIQUE AFTER id", "SELECT 1") FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'work_shifts' AND COLUMN_NAME = 'shift_code');
PREPARE stmt FROM @q; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @q = (SELECT IF(COUNT(*) = 0, "ALTER TABLE work_shifts ADD COLUMN description VARCHAR(255) DEFAULT NULL", "SELECT 1") FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'work_shifts' AND COLUMN_NAME = 'description');
PREPARE stmt FROM @q; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @q = (SELECT IF(COUNT(*) = 0, "ALTER TABLE suppliers ADD COLUMN status ENUM('active','inactive') DEFAULT 'active'", "SELECT 1") FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'suppliers' AND COLUMN_NAME = 'status');
PREPARE stmt FROM @q; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @q = (SELECT IF(COUNT(*) = 0, "ALTER TABLE ingredients ADD COLUMN group_name VARCHAR(100) DEFAULT 'Nguyên liệu 5 sao'", "SELECT 1") FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'ingredients' AND COLUMN_NAME = 'group_name');
PREPARE stmt FROM @q; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @q = (SELECT IF(COUNT(*) = 0, "ALTER TABLE ingredients ADD COLUMN supplier_id INT DEFAULT NULL", "SELECT 1") FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'ingredients' AND COLUMN_NAME = 'supplier_id');
PREPARE stmt FROM @q; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @q = (SELECT IF(COUNT(*) = 0, "ALTER TABLE ingredients ADD CONSTRAINT fk_ingredient_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL", "SELECT 1") FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'ingredients' AND COLUMN_NAME = 'supplier_id' AND REFERENCED_TABLE_NAME = 'suppliers');
PREPARE stmt FROM @q; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @q = (SELECT IF(COUNT(*) = 0, "ALTER TABLE stock_receipts ADD COLUMN order_id INT DEFAULT NULL AFTER user_id", "SELECT 1") FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'stock_receipts' AND COLUMN_NAME = 'order_id');
PREPARE stmt FROM @q; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @q = (SELECT IF(COUNT(*) = 0, "ALTER TABLE stock_receipt_details ADD COLUMN reason VARCHAR(255) DEFAULT NULL", "SELECT 1") FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'stock_receipt_details' AND COLUMN_NAME = 'reason');
PREPARE stmt FROM @q; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @q = (SELECT IF(COUNT(*) = 0, "ALTER TABLE item_modifiers ADD COLUMN menu_item_id INT NOT NULL DEFAULT 1 AFTER id", "SELECT 1") FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'item_modifiers' AND COLUMN_NAME = 'menu_item_id');
PREPARE stmt FROM @q; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @q = (SELECT IF(COUNT(*) = 0, "ALTER TABLE item_modifiers ADD COLUMN modifier_name VARCHAR(100) NOT NULL DEFAULT 'Tiêu chuẩn'", "SELECT 1") FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'item_modifiers' AND COLUMN_NAME = 'modifier_name');
PREPARE stmt FROM @q; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_legacy_mod_name = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'item_modifiers' AND COLUMN_NAME = 'name');
SET @q = IF(@has_legacy_mod_name > 0, "UPDATE item_modifiers SET modifier_name = name WHERE modifier_name = 'Tiêu chuẩn' AND name IS NOT NULL AND name <> ''", "SELECT 1");
PREPARE stmt FROM @q; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @q = IF(@has_legacy_mod_name > 0, "ALTER TABLE item_modifiers DROP COLUMN name", "SELECT 1");
PREPARE stmt FROM @q; EXECUTE stmt; DEALLOCATE PREPARE stmt;

ALTER TABLE item_modifiers MODIFY COLUMN modifier_group VARCHAR(50) NOT NULL DEFAULT 'Tùy chọn chế biến';

SET @q = (SELECT IF(COUNT(*) = 0, "ALTER TABLE orders ADD COLUMN order_type ENUM('DineIn','Takeaway','Delivery') NOT NULL DEFAULT 'DineIn' AFTER order_code", "SELECT 1") FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'order_type');
PREPARE stmt FROM @q; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @q = (SELECT IF(COUNT(*) = 0, "ALTER TABLE orders ADD COLUMN shift_id INT DEFAULT NULL AFTER waiter_id", "SELECT 1") FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'shift_id');
PREPARE stmt FROM @q; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @q = (SELECT IF(COUNT(*) = 0, "ALTER TABLE orders ADD COLUMN voucher_code VARCHAR(30) DEFAULT NULL AFTER discount_amount", "SELECT 1") FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'voucher_code');
PREPARE stmt FROM @q; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT IGNORE INTO roles (id, role_code, role_name) VALUES
(1, 'ADMIN', 'Quản lý nhà hàng (Leader)'),
(2, 'HOSTESS', 'Lễ tân & Đặt bàn VIP'),
(3, 'WAITER', 'Phục vụ & Thu ngân POS'),
(4, 'CHEF', 'Bếp trưởng điều phối KDS'),
(5, 'STOCK', 'Thủ kho & Kế toán'),
(6, 'MANAGER', 'Quản lý vận hành nhà hàng'),
(7, 'CASHIER', 'Thu ngân chuyên trách'),
(8, 'BAR', 'Pha chế Bar / Sommelier');

INSERT IGNORE INTO users (id, role_id, full_name, username, password_hash, phone, is_active) VALUES
(1, 1, 'Đinh Thị Mai Trâm', 'admin', '$2y$10$HKjEDZJJxJ.U.XKOzNoK8e7Md/b7Ts08b/VkUTrh6CU3/Uay56zhq', '0901000001', 1),
(2, 2, 'Đoàn Bùi Khánh Vy', 'hostess', '$2y$10$HKjEDZJJxJ.U.XKOzNoK8e7Md/b7Ts08b/VkUTrh6CU3/Uay56zhq', '0901000002', 1),
(3, 3, 'La Thị Trà My', 'cashier', '$2y$10$HKjEDZJJxJ.U.XKOzNoK8e7Md/b7Ts08b/VkUTrh6CU3/Uay56zhq', '0901000003', 1),
(4, 4, 'Bếp trưởng RoyalDine', 'chef', '$2y$10$HKjEDZJJxJ.U.XKOzNoK8e7Md/b7Ts08b/VkUTrh6CU3/Uay56zhq', '0901000004', 1),
(5, 5, 'Quảng Thị Kim Hiền', 'stock', '$2y$10$HKjEDZJJxJ.U.XKOzNoK8e7Md/b7Ts08b/VkUTrh6CU3/Uay56zhq', '0901000005', 1),
(6, 6, 'Nguyễn Quản Lý', 'manager', '$2y$10$HKjEDZJJxJ.U.XKOzNoK8e7Md/b7Ts08b/VkUTrh6CU3/Uay56zhq', '0901000006', 1);

INSERT IGNORE INTO work_shifts (id, shift_code, shift_name, start_time, end_time, allowance, description) VALUES
(1, 'SHIFT_MORNING', 'Ca Sáng (Morning Fine Dining)', '08:00:00', '14:00:00', 50000, 'Chuẩn bị nguyên liệu & đón khách trưa'),
(2, 'SHIFT_EVENING', 'Ca Tối (Royal Dinner & Wine)', '16:00:00', '22:30:00', 80000, 'Phục vụ tiệc tối 5 sao, đóng quầy');

INSERT IGNORE INTO customers (id, full_name, phone, email, tier, points, total_spent) VALUES
(1, 'Nguyễn Hoàng Gia (Khách VIP)', '0988888888', 'vip@royaldine.vn', 'Gold', 500, 25000000);

INSERT IGNORE INTO promotions (id, code, name, discount_type, discount_value, max_discount, min_order_value, min_tier, start_date, end_date, is_active) VALUES
(1, 'ROYAL10', 'Giảm 10% Fine Dining (Tối đa 300k)', 'Percent', 10, 300000, 500000, 'Standard', '2025-01-01 00:00:00', '2027-12-31 23:59:59', 1);

INSERT INTO cash_shifts (cashier_id, opened_at, opening_cash, expected_closing_cash, note, status)
SELECT 3, NOW(), 5000000, 5000000, 'Ca mặc định khởi tạo tự động', 'Open'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM cash_shifts WHERE status = 'Open');