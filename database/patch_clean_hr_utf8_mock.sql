SET NAMES utf8mb4;
USE royaldine_5s;

-- 1. Đảm bảo cột đơn giá lương theo giờ tồn tại
ALTER TABLE roles ADD COLUMN IF NOT EXISTS hourly_rate DECIMAL(12,2) NOT NULL DEFAULT 35000 AFTER role_name;
ALTER TABLE users ADD COLUMN IF NOT EXISTS hourly_rate DECIMAL(12,2) DEFAULT NULL AFTER phone;

-- 2. Chuẩn hóa tên Vai trò (Role) không lỗi font, không dấu ngoặc đơn & cập nhật đơn giá chuẩn
UPDATE roles SET role_name = 'Quản lý nhà hàng', hourly_rate = 65000 WHERE role_code = 'ADMIN';
UPDATE roles SET role_name = 'Lễ tân và Đặt bàn VIP', hourly_rate = 38000 WHERE role_code = 'HOSTESS';
UPDATE roles SET role_name = 'Phục vụ và Thu ngân POS', hourly_rate = 35000 WHERE role_code = 'WAITER';
UPDATE roles SET role_name = 'Bếp trưởng điều phối KDS', hourly_rate = 55000 WHERE role_code = 'CHEF';
UPDATE roles SET role_name = 'Thủ kho và Kế toán', hourly_rate = 42000 WHERE role_code = 'STOCK';

-- 3. Chuẩn hóa Nhân viên mẫu (6 nhân sự) và đồng bộ đơn giá giờ
UPDATE users SET full_name = 'Đinh Thị Mai Trâm', hourly_rate = 65000 WHERE username = 'admin';
UPDATE users SET full_name = 'Đoàn Bùi Khánh Vy', hourly_rate = 38000 WHERE username = 'hostess';
UPDATE users SET full_name = 'La Thị Trà My', hourly_rate = 35000 WHERE username = 'cashier';
UPDATE users SET full_name = 'Bếp trưởng RoyalDine', hourly_rate = 55000 WHERE username = 'chef';
UPDATE users SET full_name = 'Quảng Thị Kim Hiền', hourly_rate = 42000 WHERE username = 'stock';

INSERT INTO users (id, role_id, full_name, username, password_hash, phone, hourly_rate, is_active)
VALUES (6, 5, 'Nguyễn Văn Tứ', 'tunv', '$2y$10$HKjEDZJJxJ.U.XKOzNoK8e7Md/b7Ts08b/VkUTrh6CU3/Uay56zhq', '0901000006', 42000, 1)
ON DUPLICATE KEY UPDATE full_name = 'Nguyễn Văn Tứ', hourly_rate = 42000, is_active = 1;

-- 4. Sửa lại khung giờ Ca làm việc chuẩn (6 giờ/ca), xóa lỗi font & bỏ dấu ngoặc đơn
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE staff_schedules;
TRUNCATE TABLE work_shifts;
SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO work_shifts (id, shift_code, shift_name, start_time, end_time, allowance, description) VALUES
(1, 'SHIFT_MORNING', 'Ca Sáng', '08:00:00', '14:00:00', 50000, 'Phục vụ khung giờ sáng và trưa'),
(2, 'SHIFT_AFTERNOON', 'Ca Chiều', '14:00:00', '20:00:00', 60000, 'Phục vụ khung giờ chiều và tối sớm'),
(3, 'SHIFT_EVENING', 'Ca Tối VIP', '16:00:00', '22:00:00', 80000, 'Phục vụ tiệc tối Fine Dining');

-- 5. Nạp dữ liệu ảo chấm công tháng 2026-11 để kiểm tra luồng cộng dồn giờ thực tế
INSERT INTO staff_schedules (user_id, shift_id, work_date, check_in, check_out, attendance_status, note) VALUES
-- Đinh Thị Mai Trâm (65.000đ/h): Làm 2 ca (6.0h + 6.5h = 12.5 giờ), Phụ cấp 100.000đ -> Lương = 912.500đ
(1, 1, '2026-11-05', '08:00:00', '14:00:00', 'OnTime', 'Quản lý ca sáng'),
(1, 1, '2026-11-07', '08:00:00', '14:30:00', 'OnTime', 'Quản lý ca sáng'),

-- Đoàn Bùi Khánh Vy (38.000đ/h): Làm 1 ca (16:00 -> 22:00 = 6.0 giờ), Phụ cấp 80.000đ -> Lương = 308.000đ
(2, 3, '2026-11-06', '16:00:00', '22:00:00', 'OnTime', 'Đón khách VIP'),

-- La Thị Trà My (35.000đ/h): Xếp 3 ca, đã chấm công 2 ca (6.0h + 6.0h = 12.0 giờ), 1 ca chưa chấm công -> Lương = 530.000đ
(3, 1, '2026-11-04', '08:00:00', '14:00:00', 'OnTime', 'Thu ngân ca sáng'),
(3, 2, '2026-11-06', '14:00:00', '20:00:00', 'OnTime', 'Thu ngân ca chiều'),
(3, 3, '2026-11-07', NULL, NULL, 'Scheduled', 'Chờ vào ca tối'),

-- Bếp trưởng RoyalDine (55.000đ/h): Làm 1 ca (07:30 -> 14:00 = 6.5 giờ), Phụ cấp 50.000đ -> Lương = 407.500đ
(4, 1, '2026-11-07', '07:30:00', '14:00:00', 'OnTime', 'Điều phối bếp chính'),

-- Quảng Thị Kim Hiền (42.000đ/h): Làm 1 ca (14:15 -> 20:15 = 6.0 giờ), Phụ cấp 60.000đ -> Lương = 312.000đ
(5, 2, '2026-11-05', '14:15:00', '20:15:00', 'Late', 'Kiểm kê kho chiều'),

-- Nguyễn Văn Tứ (42.000đ/h): Xếp 1 ca nhưng CHƯA chấm công (0/1 ca) -> Giờ thực tế = 0 giờ, Lương = 0đ
(6, 2, '2026-11-08', NULL, NULL, 'Scheduled', 'Ca dự kiến chưa làm');

-- 6. Sửa triệt để lỗi font UTF-8 ở các bảng Khu vực, Thực đơn, Nguyên liệu, Nhà cung cấp
UPDATE areas SET name = 'Sảnh Giao Hưởng', description = 'Không gian mở tầng 1 có nhạc công Piano' WHERE id = 1;
UPDATE areas SET name = 'Phòng VIP Hầm Rượu', description = 'Không gian phòng VIP riêng tư tầng 2' WHERE id = 2;
UPDATE areas SET name = 'Sảnh Chính Hoàng Gia', description = 'Sảnh tiêu chuẩn trung tâm tầng 1' WHERE id = 3;
UPDATE areas SET name = 'Khu Thượng Hạng', description = 'Tầng 3 biệt lập dành cho Phòng Thượng Hạng' WHERE id = 4;

UPDATE categories SET name = 'Khai vị Tinh hoa' WHERE id = 1;
UPDATE categories SET name = 'Món chính Thượng hạng' WHERE id = 2;
UPDATE categories SET name = 'Tráng miệng Hoàng gia' WHERE id = 3;
UPDATE categories SET name = 'Rượu vang và Cocktail' WHERE id = 4;

UPDATE menu_items SET name = 'Sò điệp Hokkaido áp chảo sốt bơ chanh', unit = 'Đĩa' WHERE item_code = 'RD-APP01';
UPDATE menu_items SET name = 'Thăn ngoại bò Wagyu A5 nướng đá muối', unit = 'Phần' WHERE item_code = 'RD-MAIN01';
UPDATE menu_items SET name = 'Bánh Tiramisu phủ vàng 24K', unit = 'Phần' WHERE item_code = 'RD-DES01';
UPDATE menu_items SET name = 'Rượu vang đỏ Château Margaux', unit = 'Ly' WHERE item_code = 'RD-WINE01';
UPDATE menu_items SET unit = 'Phần' WHERE unit LIKE '%Ph%n%' OR unit LIKE '%?%';

UPDATE ingredients SET name = 'Thăn bò Wagyu A5 Nhật Bản', group_name = 'Thịt Cao Cấp', unit = 'g' WHERE ingredient_code = 'NVL01';
UPDATE ingredients SET name = 'Sò điệp Hokkaido tươi', group_name = 'Hải Sản Tươi', unit = 'g' WHERE ingredient_code = 'NVL02';
UPDATE ingredients SET name = 'Bơ lạt Pháp President', group_name = 'Bơ Sữa và Gia Vị', unit = 'g' WHERE ingredient_code = 'NVL03';
UPDATE ingredients SET name = 'Rượu vang Château Margaux', group_name = 'Đồ Uống Có Cồn', unit = 'ml' WHERE ingredient_code = 'NVL04';

UPDATE suppliers SET name = 'Công ty Thực phẩm Nhập khẩu Gofood', contact_person = 'Anh Tuấn', address = 'Quận 1, TP.HCM' WHERE id = 1;
UPDATE suppliers SET name = 'Vựa Hải sản Cao cấp Biển Đông', contact_person = 'Chị Hương', address = 'Quận 3, TP.HCM' WHERE id = 2;
UPDATE suppliers SET name = 'Đại lý Rượu vang Đa Quốc Gia', contact_person = 'Anh Long', address = 'Quận 7, TP.HCM' WHERE id = 3;