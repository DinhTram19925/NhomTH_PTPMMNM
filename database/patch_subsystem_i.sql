SET NAMES utf8mb4;
USE royaldine_5s;

-- 1. Bảng Danh mục Ca làm việc (Work Shifts)
CREATE TABLE IF NOT EXISTS work_shifts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shift_code VARCHAR(20) NOT NULL UNIQUE,
    shift_name VARCHAR(100) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    allowance DECIMAL(12,2) DEFAULT 0,
    description VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO work_shifts (id, shift_code, shift_name, start_time, end_time, allowance, description) VALUES
(1, 'SHIFT_MORNING', 'Ca Sáng (Chuẩn bị & Bữa trưa)', '06:30:00', '14:30:00', 50000, 'Chuẩn bị nguyên liệu, đón khách ăn trưa'),
(2, 'SHIFT_EVENING', 'Ca Tối (Fine Dining VIP)', '14:30:00', '22:30:00', 80000, 'Phục vụ tiệc tối 5 sao, đóng quầy'),
(3, 'SHIFT_SPLIT', 'Ca Gãy (Cao điểm Trưa & Tối)', '10:00:00', '21:30:00', 100000, 'Nghỉ giữa ca từ 14:00 - 17:00');

-- 2. Bảng Phân ca & Chấm công nhân viên (Staff Schedules & Attendance)
CREATE TABLE IF NOT EXISTS staff_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    shift_id INT NOT NULL,
    work_date DATE NOT NULL,
    check_in TIME DEFAULT NULL,
    check_out TIME DEFAULT NULL,
    attendance_status ENUM('Scheduled', 'OnTime', 'Late', 'Absent') DEFAULT 'Scheduled',
    note VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (shift_id) REFERENCES work_shifts(id) ON DELETE CASCADE,
    UNIQUE KEY uk_user_shift_date (user_id, shift_id, work_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Bảng Lịch sử thao tác & Kiểm soát sai lệch (Audit Logs)
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action_type VARCHAR(50) NOT NULL,
    module_name VARCHAR(50) NOT NULL,
    description TEXT NOT NULL,
    severity ENUM('INFO', 'WARNING', 'CRITICAL') DEFAULT 'INFO',
    ip_address VARCHAR(45) DEFAULT '127.0.0.1',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dữ liệu mẫu Phân ca & Nhật ký thao tác
INSERT IGNORE INTO staff_schedules (id, user_id, shift_id, work_date, check_in, check_out, attendance_status, note) VALUES
(1, 1, 1, CURDATE(), '06:25:00', NULL, 'OnTime', 'Quản lý kiểm tra đầu ca'),
(2, 2, 2, CURDATE(), '14:40:00', NULL, 'Late', 'Lễ tân trực sảnh VIP (Trễ 10 phút)'),
(3, 3, 2, CURDATE(), NULL, NULL, 'Scheduled', 'Thu ngân trực POS ca tối');

INSERT IGNORE INTO audit_logs (user_id, action_type, module_name, description, severity) VALUES
(1, 'INIT_RBAC', 'Phân quyền & Hệ thống', 'Khởi tạo ma trận phân quyền 5 vai trò chuẩn RoyalDine 5S', 'INFO'),
(1, 'PRICE_CONTROL', 'Kiểm soát sai lệch', 'Bật chế độ bắt buộc nhập lý do khi Hủy món hoặc Sửa hóa đơn trên POS', 'WARNING');
