SET NAMES utf8mb4;
USE royaldine_5s;

-- Bổ sung bảng cho TV1 (Ca làm việc, Phân ca, Nhật ký hệ thống)
CREATE TABLE IF NOT EXISTS work_shifts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shift_name VARCHAR(100) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    allowance DECIMAL(12,2) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO work_shifts (id, shift_name, start_time, end_time, allowance) VALUES
(1, 'Ca Sáng (Morning Fine Dining)', '08:00:00', '14:00:00', 50000),
(2, 'Ca Tối (Royal Dinner & Wine)', '16:00:00', '22:30:00', 80000);

CREATE TABLE IF NOT EXISTS staff_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    shift_id INT NOT NULL,
    work_date DATE NOT NULL,
    check_in TIME DEFAULT NULL,
    check_out TIME DEFAULT NULL,
    attendance_status ENUM('Scheduled','OnTime','Late','Absent') DEFAULT 'Scheduled',
    note VARCHAR(255) DEFAULT NULL,
    UNIQUE KEY uq_user_shift_date (user_id, shift_id, work_date),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (shift_id) REFERENCES work_shifts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action_type VARCHAR(50) NOT NULL,
    module_name VARCHAR(100) NOT NULL,
    description TEXT,
    severity ENUM('INFO','WARNING','CRITICAL') DEFAULT 'INFO',
    ip_address VARCHAR(45) DEFAULT '127.0.0.1',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bổ sung bảng giao dịch thu/chi cho TV4
CREATE TABLE IF NOT EXISTS cash_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shift_id INT NOT NULL,
    type ENUM('IN', 'OUT') NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    note TEXT,
    creator VARCHAR(100),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shift_id) REFERENCES cash_shifts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Thêm cột thiếu cho suppliers, ingredients, stock_receipt_details, item_modifiers (an toàn nếu chạy nhiều lần)
SET @dbname = DATABASE();

-- suppliers.status
SET @q = (SELECT IF(COUNT(*) = 0, "ALTER TABLE suppliers ADD COLUMN status ENUM('active','inactive') DEFAULT 'active'", "SELECT 1") FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'suppliers' AND COLUMN_NAME = 'status');
PREPARE stmt FROM @q; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ingredients.group_name
SET @q = (SELECT IF(COUNT(*) = 0, "ALTER TABLE ingredients ADD COLUMN group_name VARCHAR(100) DEFAULT 'Nguyên liệu 5 sao'", "SELECT 1") FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'ingredients' AND COLUMN_NAME = 'group_name');
PREPARE stmt FROM @q; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ingredients.supplier_id
SET @q = (SELECT IF(COUNT(*) = 0, "ALTER TABLE ingredients ADD COLUMN supplier_id INT DEFAULT NULL", "SELECT 1") FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'ingredients' AND COLUMN_NAME = 'supplier_id');
PREPARE stmt FROM @q; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- stock_receipt_details.reason
SET @q = (SELECT IF(COUNT(*) = 0, "ALTER TABLE stock_receipt_details ADD COLUMN reason VARCHAR(255) DEFAULT NULL", "SELECT 1") FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'stock_receipt_details' AND COLUMN_NAME = 'reason');
PREPARE stmt FROM @q; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- item_modifiers.menu_item_id
SET @q = (SELECT IF(COUNT(*) = 0, "ALTER TABLE item_modifiers ADD COLUMN menu_item_id INT DEFAULT 2", "SELECT 1") FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'item_modifiers' AND COLUMN_NAME = 'menu_item_id');
PREPARE stmt FROM @q; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- item_modifiers.modifier_name
SET @q = (SELECT IF(COUNT(*) = 0, "ALTER TABLE item_modifiers ADD COLUMN modifier_name VARCHAR(100) DEFAULT NULL", "SELECT 1") FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'item_modifiers' AND COLUMN_NAME = 'modifier_name');
PREPARE stmt FROM @q; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Seed thêm 1 Khuyến mãi mẫu và Khách hàng VIP mẫu để test E2E ngay
INSERT IGNORE INTO customers (id, full_name, phone, email, tier, points, total_spent) VALUES
(1, 'Nguyễn Hoàng Gia (Khách VIP)', '0988888888', 'vip@royaldine.vn', 'Gold', 500, 25000000);

INSERT IGNORE INTO promotions (id, code, name, discount_type, discount_value, max_discount, min_order_value, min_tier, start_date, end_date, is_active) VALUES
(1, 'ROYAL10', 'Giảm 10% Fine Dining (Tối đa 300k)', 'Percent', 10, 300000, 500000, 'Standard', '2025-01-01 00:00:00', '2027-12-31 23:59:59', 1);
