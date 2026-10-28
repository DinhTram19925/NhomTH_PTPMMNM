SET NAMES utf8mb4;
USE royaldine_5s;

-- Mở rộng cột status của menu_items để hỗ trợ linh hoạt Đang bán / Tạm hết
ALTER TABLE menu_items MODIFY COLUMN status VARCHAR(30) DEFAULT 'Available';

-- Chuẩn hóa bảng Sở thích phục vụ (item_modifiers)
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS item_modifiers;
CREATE TABLE item_modifiers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    menu_item_id INT NOT NULL,
    modifier_group VARCHAR(50) NOT NULL DEFAULT 'Tùy chọn chế biến',
    modifier_name VARCHAR(100) NOT NULL,
    extra_price DECIMAL(12,2) NOT NULL DEFAULT 0,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SET FOREIGN_KEY_CHECKS = 1;

-- Dữ liệu mẫu Sở thích phục vụ chuẩn Fine Dining 5 sao cho món số 1 (Bò Wagyu)
INSERT INTO item_modifiers (menu_item_id, modifier_group, modifier_name, extra_price) VALUES
(1, 'Độ chín (Doneness)', 'Rare (Tái đỏ 25%)', 0),
(1, 'Độ chín (Doneness)', 'Medium Rare (Tái chín 50% - Khuyên dùng)', 0),
(1, 'Độ chín (Doneness)', 'Well Done (Chín kỹ 100%)', 0),
(1, 'Sốt dùng kèm', 'Sốt nấm Truffle đen thượng hạng', 65000),
(1, 'Sốt dùng kèm', 'Sốt rượu vang đỏ Bordeaux', 0),
(1, 'Lưu ý dị ứng', 'Không dùng bơ sữa (Dairy-free)', 0);
