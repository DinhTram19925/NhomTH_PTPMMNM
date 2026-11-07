SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_code VARCHAR(30) NOT NULL UNIQUE,
    role_name VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    course_type ENUM('Appetizer', 'Soup_Salad', 'Main_Course', 'Dessert', 'Beverage', 'Set_Menu') NOT NULL DEFAULT 'Main_Course',
    display_order INT DEFAULT 1,
    is_active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    item_code VARCHAR(30) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    unit VARCHAR(30) DEFAULT 'Phần',
    price DECIMAL(12,2) NOT NULL DEFAULT 0,
    image_url VARCHAR(255),
    is_combo TINYINT(1) DEFAULT 0,
    status VARCHAR(30) NOT NULL DEFAULT 'Available',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS item_modifiers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    menu_item_id INT NOT NULL DEFAULT 1,
    modifier_group VARCHAR(50) NOT NULL DEFAULT 'Tùy chọn chế biến',
    modifier_name VARCHAR(100) NOT NULL DEFAULT 'Tiêu chuẩn',
    extra_price DECIMAL(12,2) NOT NULL DEFAULT 0,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    contact_person VARCHAR(100),
    phone VARCHAR(20),
    address VARCHAR(255),
    debt_balance DECIMAL(14,2) DEFAULT 0,
    status ENUM('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ingredients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ingredient_code VARCHAR(30) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    group_name VARCHAR(100) DEFAULT 'Nguyên liệu 5 sao',
    unit VARCHAR(20) NOT NULL,
    stock_quantity DECIMAL(12,2) DEFAULT 0,
    min_stock_level DECIMAL(12,2) DEFAULT 0,
    cost_price DECIMAL(12,2) DEFAULT 0,
    supplier_id INT DEFAULT NULL,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS recipes_bom (
    id INT AUTO_INCREMENT PRIMARY KEY,
    menu_item_id INT NOT NULL,
    ingredient_id INT NOT NULL,
    quantity_required DECIMAL(10,2) NOT NULL,
    wastage_percent DECIMAL(5,2) DEFAULT 0,
    UNIQUE KEY uq_bom_menu_ingredient (menu_item_id, ingredient_id),
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE,
    FOREIGN KEY (ingredient_id) REFERENCES ingredients(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS areas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    surcharge_percent DECIMAL(5,2) DEFAULT 0,
    description VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dining_tables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    area_id INT NOT NULL,
    table_name VARCHAR(50) NOT NULL,
    capacity INT NOT NULL DEFAULT 4,
    qr_token VARCHAR(100) UNIQUE,
    status ENUM('Available', 'Occupied', 'Reserved', 'Cleaning') DEFAULT 'Available',
    FOREIGN KEY (area_id) REFERENCES areas(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL UNIQUE,
    email VARCHAR(100),
    tier ENUM('Standard', 'Silver', 'Gold', 'Diamond') DEFAULT 'Standard',
    points INT DEFAULT 0,
    total_spent DECIMAL(14,2) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS promotions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(30) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    discount_type ENUM('Percent', 'Fixed') NOT NULL,
    discount_value DECIMAL(12,2) NOT NULL,
    max_discount DECIMAL(12,2) DEFAULT NULL,
    min_order_value DECIMAL(12,2) DEFAULT 0,
    min_tier ENUM('Standard', 'Silver', 'Gold', 'Diamond') DEFAULT 'Standard',
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    is_active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_code VARCHAR(30) NOT NULL UNIQUE,
    customer_id INT NOT NULL,
    table_id INT DEFAULT NULL,
    booking_time DATETIME NOT NULL,
    guest_count INT NOT NULL,
    deposit_amount DECIMAL(12,2) DEFAULT 0,
    special_request TEXT,
    status ENUM('Pending', 'Confirmed', 'CheckedIn', 'Cancelled') DEFAULT 'Pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (table_id) REFERENCES dining_tables(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cash_shifts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cashier_id INT NOT NULL,
    opened_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    closed_at DATETIME DEFAULT NULL,
    opening_cash DECIMAL(14,2) NOT NULL DEFAULT 0,
    total_cash_sales DECIMAL(14,2) DEFAULT 0,
    total_cash_in DECIMAL(14,2) DEFAULT 0,
    total_cash_out DECIMAL(14,2) DEFAULT 0,
    expected_closing_cash DECIMAL(14,2) DEFAULT 0,
    actual_closing_cash DECIMAL(14,2) DEFAULT NULL,
    discrepancy DECIMAL(14,2) DEFAULT 0,
    note TEXT,
    status ENUM('Open', 'Closed') DEFAULT 'Open',
    FOREIGN KEY (cashier_id) REFERENCES users(id)
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

CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_code VARCHAR(30) NOT NULL UNIQUE,
    order_type ENUM('DineIn','Takeaway','Delivery') NOT NULL DEFAULT 'DineIn',
    table_id INT NOT NULL,
    reservation_id INT DEFAULT NULL,
    customer_id INT DEFAULT NULL,
    waiter_id INT NOT NULL,
    shift_id INT DEFAULT NULL,
    guest_count INT DEFAULT 2,
    subtotal DECIMAL(14,2) DEFAULT 0,
    area_surcharge_amount DECIMAL(12,2) DEFAULT 0,
    service_charge_amount DECIMAL(12,2) DEFAULT 0,
    discount_amount DECIMAL(12,2) DEFAULT 0,
    voucher_code VARCHAR(30) DEFAULT NULL,
    deposit_deducted DECIMAL(12,2) DEFAULT 0,
    vat_amount DECIMAL(12,2) DEFAULT 0,
    total_amount DECIMAL(14,2) DEFAULT 0,
    status ENUM('Open', 'PendingPayment', 'Paid', 'Cancelled') DEFAULT 'Open',
    opened_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    closed_at DATETIME DEFAULT NULL,
    FOREIGN KEY (table_id) REFERENCES dining_tables(id),
    FOREIGN KEY (reservation_id) REFERENCES reservations(id),
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (waiter_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    menu_item_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(12,2) NOT NULL,
    modifier_notes VARCHAR(255),
    course_priority INT DEFAULT 1,
    kds_status ENUM('Waiting', 'Cooking', 'Ready', 'Served', 'Cancelled') DEFAULT 'Waiting',
    cancel_reason VARCHAR(255) DEFAULT NULL,
    ordered_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    cashier_id INT NOT NULL,
    payment_method ENUM('Cash', 'VietQR', 'CreditCard') NOT NULL,
    amount DECIMAL(14,2) NOT NULL,
    transaction_ref VARCHAR(100),
    paid_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (cashier_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    user_id INT NOT NULL,
    action_type ENUM('CreateOrder', 'TransferTable', 'MergeTable', 'CancelItem', 'Checkout') NOT NULL,
    description TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stock_receipts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    receipt_code VARCHAR(30) NOT NULL UNIQUE,
    supplier_id INT DEFAULT NULL,
    user_id INT NOT NULL,
    order_id INT DEFAULT NULL,
    receipt_type ENUM('Import', 'Export_Sale', 'Export_Damage', 'Audit_Adjust') NOT NULL,
    total_amount DECIMAL(14,2) DEFAULT 0,
    note TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stock_receipt_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stock_receipt_id INT NOT NULL,
    ingredient_id INT NOT NULL,
    quantity DECIMAL(12,2) NOT NULL,
    unit_cost DECIMAL(12,2) NOT NULL,
    reason VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (stock_receipt_id) REFERENCES stock_receipts(id) ON DELETE CASCADE,
    FOREIGN KEY (ingredient_id) REFERENCES ingredients(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @dbname = DATABASE();

ALTER TABLE categories MODIFY COLUMN course_type ENUM('Appetizer', 'Soup_Salad', 'Main_Course', 'Dessert', 'Beverage', 'Set_Menu') NOT NULL DEFAULT 'Main_Course';
ALTER TABLE menu_items MODIFY COLUMN status VARCHAR(30) NOT NULL DEFAULT 'Available';

SET @q = (SELECT IF(COUNT(*) = 0, "ALTER TABLE work_shifts ADD COLUMN shift_code VARCHAR(30) DEFAULT NULL UNIQUE AFTER id", "SELECT 1") FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'work_shifts' AND COLUMN_NAME = 'shift_code');
PREPARE stmt FROM @q; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @q = (SELECT IF(COUNT(*) = 0, "ALTER TABLE work_shifts ADD COLUMN description VARCHAR(255) DEFAULT NULL", "SELECT 1") FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'work_shifts' AND COLUMN_NAME = 'description');
PREPARE stmt FROM @q; EXECUTE stmt; DEALLOCATE PREPARE stmt;

ALTER TABLE audit_logs MODIFY COLUMN module_name VARCHAR(100) NOT NULL;

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