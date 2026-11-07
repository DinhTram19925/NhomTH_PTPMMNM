SET NAMES utf8mb4;
DROP DATABASE IF EXISTS royaldine_5s;
CREATE DATABASE IF NOT EXISTS royaldine_5s CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE royaldine_5s;

-- MAI TRÂM: QUẢN TRỊ HỆ THỐNG, NHÂN SỰ, THỰC ĐƠN & ĐỊNH LƯỢNG BOM

CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_code VARCHAR(30) NOT NULL UNIQUE,
    role_name VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id)
) ENGINE=InnoDB;

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    course_type ENUM('Appetizer', 'Soup_Salad', 'Main_Course', 'Dessert', 'Beverage', 'Set_Menu') NOT NULL,
    display_order INT DEFAULT 1,
    is_active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    item_code VARCHAR(30) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    unit VARCHAR(30) DEFAULT 'Phần',
    price DECIMAL(12,2) NOT NULL DEFAULT 0,
    image_url VARCHAR(255),
    is_combo TINYINT(1) DEFAULT 0,
    status ENUM('Available', 'OutOfStock', 'Discontinued') DEFAULT 'Available',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
) ENGINE=InnoDB;

CREATE TABLE item_modifiers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    modifier_group ENUM('Doneness', 'Sauce', 'Extra', 'Allergy_Note') NOT NULL,
    name VARCHAR(100) NOT NULL,
    extra_price DECIMAL(12,2) DEFAULT 0
) ENGINE=InnoDB;

-- =========================================================================
-- PHÂN HỆ 4 (KIM HIỀN - Danh mục gốc để nối với BOM của Mai Trâm)
-- =========================================================================

CREATE TABLE suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    contact_person VARCHAR(100),
    phone VARCHAR(20),
    address VARCHAR(255),
    debt_balance DECIMAL(14,2) DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE ingredients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ingredient_code VARCHAR(30) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    unit VARCHAR(20) NOT NULL,
    stock_quantity DECIMAL(12,2) DEFAULT 0,
    min_stock_level DECIMAL(12,2) DEFAULT 0,
    cost_price DECIMAL(12,2) DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE recipes_bom (
    id INT AUTO_INCREMENT PRIMARY KEY,
    menu_item_id INT NOT NULL,
    ingredient_id INT NOT NULL,
    quantity_required DECIMAL(10,2) NOT NULL,
    wastage_percent DECIMAL(5,2) DEFAULT 0,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE,
    FOREIGN KEY (ingredient_id) REFERENCES ingredients(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- KHÁNH VY: SƠ ĐỒ BÀN, ĐẶT BÀN VIP (BOOKING) & CRM

CREATE TABLE areas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    surcharge_percent DECIMAL(5,2) DEFAULT 0,
    description VARCHAR(255)
) ENGINE=InnoDB;

CREATE TABLE dining_tables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    area_id INT NOT NULL,
    table_name VARCHAR(50) NOT NULL,
    capacity INT NOT NULL DEFAULT 4,
    qr_token VARCHAR(100) UNIQUE,
    status ENUM('Available', 'Occupied', 'Reserved', 'Cleaning') DEFAULT 'Available',
    FOREIGN KEY (area_id) REFERENCES areas(id)
) ENGINE=InnoDB;

CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL UNIQUE,
    email VARCHAR(100),
    tier ENUM('Standard', 'Silver', 'Gold', 'Diamond') DEFAULT 'Standard',
    points INT DEFAULT 0,
    total_spent DECIMAL(14,2) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE promotions (
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
) ENGINE=InnoDB;

CREATE TABLE reservations (
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
) ENGINE=InnoDB;

-- TRÀ MY: BÁN HÀNG (POS/QR), ĐIỀU PHỐI BẾP (KDS) & THANH TOÁN

CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_code VARCHAR(30) NOT NULL UNIQUE,
    table_id INT NOT NULL,
    reservation_id INT DEFAULT NULL,
    customer_id INT DEFAULT NULL,
    waiter_id INT NOT NULL,
    guest_count INT DEFAULT 2,
    subtotal DECIMAL(14,2) DEFAULT 0,
    area_surcharge_amount DECIMAL(12,2) DEFAULT 0,
    service_charge_amount DECIMAL(12,2) DEFAULT 0,
    discount_amount DECIMAL(12,2) DEFAULT 0,
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
) ENGINE=InnoDB;

CREATE TABLE order_details (
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
) ENGINE=InnoDB;

CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    cashier_id INT NOT NULL,
    payment_method ENUM('Cash', 'VietQR', 'CreditCard') NOT NULL,
    amount DECIMAL(14,2) NOT NULL,
    transaction_ref VARCHAR(100),
    paid_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (cashier_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE order_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    user_id INT NOT NULL,
    action_type ENUM('CreateOrder', 'TransferTable', 'MergeTable', 'CancelItem', 'Checkout') NOT NULL,
    description TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- KIM HIỀN : NHẬP XUẤT KHO & BÀN GIAO CA THU NGÂN

CREATE TABLE stock_receipts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    receipt_code VARCHAR(30) NOT NULL UNIQUE,
    supplier_id INT DEFAULT NULL,
    user_id INT NOT NULL,
    receipt_type ENUM('Import', 'Export_Sale', 'Export_Damage', 'Audit_Adjust') NOT NULL,
    total_amount DECIMAL(14,2) DEFAULT 0,
    note TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE stock_receipt_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stock_receipt_id INT NOT NULL,
    ingredient_id INT NOT NULL,
    quantity DECIMAL(12,2) NOT NULL,
    unit_cost DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (stock_receipt_id) REFERENCES stock_receipts(id) ON DELETE CASCADE,
    FOREIGN KEY (ingredient_id) REFERENCES ingredients(id)
) ENGINE=InnoDB;

CREATE TABLE cash_shifts (
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
) ENGINE=InnoDB;

-- SEED DATA

INSERT INTO roles (role_code, role_name) VALUES
('ADMIN', 'Quản lý nhà hàng (Leader)'),
('HOSTESS', 'Lễ tân & Đặt bàn VIP'),
('WAITER', 'Phục vụ & Thu ngân POS'),
('CHEF', 'Bếp trưởng điều phối KDS'),
('STOCK', 'Thủ kho & Kế toán');

-- Mật khẩu mặc định của mọi tài khoản là: 123456
INSERT INTO users (role_id, full_name, username, password_hash, phone) VALUES
(1, 'Đinh Thị Mai Trâm', 'admin', '$2y$10$HKjEDZJJxJ.U.XKOzNoK8e7Md/b7Ts08b/VkUTrh6CU3/Uay56zhq', '0901000001'),
(2, 'Đoàn Bùi Khánh Vy', 'hostess', '$2y$10$HKjEDZJJxJ.U.XKOzNoK8e7Md/b7Ts08b/VkUTrh6CU3/Uay56zhq', '0901000002'),
(3, 'La Thị Trà My', 'cashier', '$2y$10$HKjEDZJJxJ.U.XKOzNoK8e7Md/b7Ts08b/VkUTrh6CU3/Uay56zhq', '0901000003'),
(4, 'Bếp trưởng RoyalDine', 'chef', '$2y$10$HKjEDZJJxJ.U.XKOzNoK8e7Md/b7Ts08b/VkUTrh6CU3/Uay56zhq', '0901000004'),
(5, 'Quảng Thị Kim Hiền', 'stock', '$2y$10$HKjEDZJJxJ.U.XKOzNoK8e7Md/b7Ts08b/VkUTrh6CU3/Uay56zhq', '0901000005');

INSERT INTO categories (name, course_type, display_order) VALUES
('Khai vị Tinh hoa (Appetizers)', 'Appetizer', 1),
('Món chính Thượng hạng (Main Courses)', 'Main_Course', 2),
('Tráng miệng Hoàng gia (Desserts)', 'Dessert', 3),
('Rượu vang & Cocktail (Fine Wines)', 'Beverage', 4);

INSERT INTO menu_items (category_id, item_code, name, unit, price, status) VALUES
(1, 'RD-APP01', 'Sò điệp Hokkaido áp chảo sốt bơ chanh', 'Đĩa', 450000, 'Available'),
(2, 'RD-MAIN01', 'Thăn ngoại bò Wagyu A5 nướng đá muối', 'Phần', 1850000, 'Available'),
(3, 'RD-DES01', 'Bánh Tiramisu phủ vàng 24K', 'Phần', 290000, 'Available'),
(4, 'RD-WINE01', 'Rượu vang đỏ Château Margaux (Ly)', 'Ly', 650000, 'Available');

INSERT INTO ingredients (ingredient_code, name, unit, stock_quantity, min_stock_level, cost_price) VALUES
('NVL01', 'Thăn bò Wagyu A5 Nhật Bản', 'g', 5000, 1000, 4500),
('NVL02', 'Sò điệp Hokkaido tươi', 'g', 3000, 500, 1200),
('NVL03', 'Bơ lạt Pháp President', 'g', 2000, 300, 350),
('NVL04', 'Rượu vang Château Margaux', 'ml', 4500, 750, 2200);

INSERT INTO recipes_bom (menu_item_id, ingredient_id, quantity_required, wastage_percent) VALUES
(2, 1, 200, 5.0),
(2, 3, 20, 0.0);

INSERT INTO areas (name, surcharge_percent, description) VALUES
('Sảnh的交 hưởng (Symphony Hall)', 0, 'Không gian mở có nhạc công Piano'),
('Phòng VIP Hầm Rượu (Royal Cellar VIP)', 10, 'Phòng riêng tư, phụ thu 10% tổng hóa đơn');

INSERT INTO dining_tables (area_id, table_name, capacity, qr_token, status) VALUES
(1, 'T-101', 4, 'QR_T101_RD5S', 'Available'),
(1, 'T-102', 6, 'QR_T102_RD5S', 'Available'),
(2, 'VIP-01', 8, 'QR_VIP01_RD5S', 'Available');