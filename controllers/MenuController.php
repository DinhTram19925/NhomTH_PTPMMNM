<?php
class MenuController extends Controller {

    // Hàm ghi nhận Lịch sử thao tác & Kiểm soát sai lệch (Audit Log)
    private function logAction($actionType, $moduleName, $description, $severity = 'INFO') {
        if (!isset($_SESSION['user']['id'])) return;
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmt = $this->db->prepare("
            INSERT INTO audit_logs (user_id, action_type, module_name, description, severity, ip_address)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$_SESSION['user']['id'], $actionType, $moduleName, $description, $severity,$ip]);
    }

    public function dashboard() {
        $this->requireRole();
        $totalItems =$this->db->query("SELECT COUNT(*) FROM menu_items")->fetchColumn();
        $totalTables =$this->db->query("SELECT COUNT(*) FROM dining_tables")->fetchColumn();
        $totalUsers =$this->db->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $this->render('menu/dashboard', compact('totalItems', 'totalTables', 'totalUsers'));
    }

    public function index() {
        $this->requireRole(['ADMIN', 'CHEF']);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $stmt =$this->db->prepare("INSERT INTO menu_items (category_id, item_code, name, unit, price, status) VALUES (?, ?, ?, ?, ?, 'Available')");
            $stmt->execute([$_POST['category_id'], $_POST['item_code'],$_POST['name'], $_POST['unit'],$_POST['price']]);
            $this->logAction('CREATE_MENU', 'Thực đơn & BOM', "Thêm món mới: {$_POST['name']} ({$_POST['item_code']}) - Giá: " . number_format($_POST['price']) . "đ", 'INFO');
            header("Location: index.php?c=menu&a=index");
            exit;
        }
        $items =$this->db->query("
            SELECT m.*, c.name AS category_name,
            (SELECT GROUP_CONCAT(CONCAT(i.name, ' (', b.quantity_required, i.unit, ')') SEPARATOR ', ')
             FROM recipes_bom b JOIN ingredients i ON b.ingredient_id = i.id WHERE b.menu_item_id = m.id) AS bom_list
            FROM menu_items m JOIN categories c ON m.category_id = c.id ORDER BY m.id DESC
        ")->fetchAll();
        $categories =$this->db->query("SELECT * FROM categories")->fetchAll();
        $this->render('menu/index', compact('items', 'categories'));
    }

    // ==================== PHÂN HỆ I: NHÂN VIÊN, CA LÀM VIỆC & PHÂN QUYỀN ====================
    public function users() {
        $this->requireRole(['ADMIN']);

        // 1. Danh sách nhân viên + Vai trò
        $users =$this->db->query("
            SELECT u.*, r.role_code, r.role_name 
            FROM users u 
            JOIN roles r ON u.role_id = r.id 
            ORDER BY u.id ASC
        ")->fetchAll();

        // 2. Danh sách Vai trò (RBAC)
        $roles =$this->db->query("SELECT * FROM roles ORDER BY id ASC")->fetchAll();

        // 3. Danh mục Ca làm việc
        $shifts =$this->db->query("SELECT * FROM work_shifts ORDER BY start_time ASC")->fetchAll();

        // 4. Bảng phân ca & chấm công (Lọc theo ngày, mặc định hôm nay)
        $filterDate =$_GET['date'] ?? date('Y-m-d');
        $stmtSched =$this->db->prepare("
            SELECT s.*, u.full_name, u.username, r.role_code, w.shift_name, w.start_time, w.end_time, w.allowance
            FROM staff_schedules s
            JOIN users u ON s.user_id = u.id
            JOIN roles r ON u.role_id = r.id
            JOIN work_shifts w ON s.shift_id = w.id
            WHERE s.work_date = ?
            ORDER BY w.start_time ASC, u.full_name ASC
        ");
        $stmtSched->execute([$filterDate]);
        $schedules =$stmtSched->fetchAll();

        // 5. Nhật ký thao tác & Kiểm soát sai lệch (Lấy 30 log mới nhất)
        $severityFilter =$_GET['severity'] ?? '';
        if ($severityFilter && in_array($severityFilter, ['INFO', 'WARNING', 'CRITICAL'])) {
            $stmtLog =$this->db->prepare("
                SELECT l.*, u.full_name, u.username, r.role_code
                FROM audit_logs l
                JOIN users u ON l.user_id = u.id
                JOIN roles r ON u.role_id = r.id
                WHERE l.severity = ?
                ORDER BY l.id DESC LIMIT 30
            ");
            $stmtLog->execute([$severityFilter]);
            $auditLogs =$stmtLog->fetchAll();
        } else {
            $auditLogs =$this->db->query("
                SELECT l.*, u.full_name, u.username, r.role_code
                FROM audit_logs l
                JOIN users u ON l.user_id = u.id
                JOIN roles r ON u.role_id = r.id
                ORDER BY l.id DESC LIMIT 30
            ")->fetchAll();
        }

        $this->render('menu/users', compact('users', 'roles', 'shifts', 'schedules', 'auditLogs', 'filterDate', 'severityFilter'));
    }

    // Thêm mới hoặc Cập nhật Hồ sơ nhân viên
    public function saveUser() {
        $this->requireRole(['ADMIN']);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {$id = !empty($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
            $fullName = trim($_POST['full_name']);
            $username = trim($_POST['username']);
            $phone = trim($_POST['phone']);
            $roleId = (int)$_POST['role_id'];

            if ($id > 0) {
                // Cập nhật hồ sơ & vai trò
                $stmt =$this->db->prepare("UPDATE users SET full_name = ?, phone = ?, role_id = ? WHERE id = ?");
                $stmt->execute([$fullName, $phone,$roleId, $id]);$this->logAction('UPDATE_USER', 'Hồ sơ nhân viên', "Cập nhật thông tin/vai trò cho nhân viên: $fullName (ID: #$id)", 'WARNING');
                $_SESSION['flash_msg'] = "Đã cập nhật hồ sơ nhân viên $fullName thành công!";
            } else {
                // Thêm nhân viên mới
                $password = !empty($_POST['password']) ?$_POST['password'] : '123456';
                $hash = password_hash($password, PASSWORD_BCRYPT);
                try {
                    $stmt =$this->db->prepare("
                        INSERT INTO users (role_id, username, password_hash, full_name, phone, is_active)
                        VALUES (?, ?, ?, ?, ?, 1)
                    ");
                    $stmt->execute([$roleId,$username, $hash,$fullName, $phone]);$this->logAction('CREATE_USER', 'Hồ sơ nhân viên', "Tạo mới tài khoản '$username' cho nhân viên $fullName", 'INFO');
                    $_SESSION['flash_msg'] = "Đã thêm nhân viên mới $fullName ($username) thành công!";
                } catch (PDOException $e) {
                    $_SESSION['flash_err'] = "Lỗi: Tên đăng nhập '$username' đã tồn tại trong hệ thống!";
                }
            }
        }
        header("Location: index.php?c=menu&a=users&tab=staff");
        exit;
    }

    // Khóa / Mở khóa tài khoản nhân viên (Kiểm soát truy cập)
    public function toggleUserStatus() {
        $this->requireRole(['ADMIN']);
        $id = (int)($_GET['id'] ?? 0);
        if ($id === (int)$_SESSION['user']['id']) {$_SESSION['flash_err'] = "Không thể tự khóa tài khoản Quản trị viên đang đăng nhập!";
            header("Location: index.php?c=menu&a=users&tab=staff");
            exit;
        }
        $stmt =$this->db->prepare("SELECT full_name, username, is_active FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $u =$stmt->fetch();
        if ($u) {
            $newStatus =$u['is_active'] ? 0 : 1;
            $this->db->prepare("UPDATE users SET is_active = ? WHERE id = ?")->execute([$newStatus,$id]);
            $statusText =$newStatus ? "MỞ KHÓA" : "KHÓA TRUY CẬP";
            $severity =$newStatus ? 'WARNING' : 'CRITICAL';
            $this->logAction('TOGGLE_STATUS', 'Bảo mật tài khoản', "$statusText tài khoản '{$u['username']}' ({$u['full_name']})", $severity);
            $_SESSION['flash_msg'] = "Đã $statusText tài khoản {$u['full_name']}!";
        }
        header("Location: index.php?c=menu&a=users&tab=staff");
        exit;
    }

    // Cấp lại mật khẩu mặc định (123456)
    public function resetPassword() {
        $this->requireRole(['ADMIN']);
        $id = (int)($_GET['id'] ?? 0);
        $stmt =$this->db->prepare("SELECT full_name, username FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $u =$stmt->fetch();
        if ($u) {$hash = password_hash('123456', PASSWORD_BCRYPT);
            $this->db->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$hash, $id]);$this->logAction('RESET_PASSWORD', 'Bảo mật tài khoản', "Reset mật khẩu của '{$u['username']}' ({$u['full_name']}) về mặc định 123456", 'WARNING');
            $_SESSION['flash_msg'] = "Đã đặt lại mật khẩu của {$u['full_name']} về '123456'!";
        }
        header("Location: index.php?c=menu&a=users&tab=staff");
        exit;
    }

    // Thêm Ca làm việc mới hoặc Phân ca cho nhân viên
    public function assignShift() {
        $this->requireRole(['ADMIN']);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = (int)$_POST['user_id'];
            $shiftId = (int)$_POST['shift_id'];
            $workDate =$_POST['work_date'];
            $note = trim($_POST['note'] ?? '');

            try {
                $stmt =$this->db->prepare("
                    INSERT INTO staff_schedules (user_id, shift_id, work_date, attendance_status, note)
                    VALUES (?, ?, ?, 'Scheduled', ?)
                ");
                $stmt->execute([$userId, $shiftId,$workDate, $note]);
                $this->logAction('ASSIGN_SHIFT', 'Phân ca làm việc', "Phân ca ID #$shiftId ngày $workDate cho nhân viên ID #$userId", 'INFO');
                $_SESSION['flash_msg'] = "Đã phân ca làm việc ngày $workDate thành công!";
            } catch (PDOException $e) {$_SESSION['flash_err'] = "Nhân viên này đã được xếp vào ca làm việc này trong ngày $workDate!";
            }
            header("Location: index.php?c=menu&a=users&tab=shifts&date=$workDate");
            exit;
        }
    }

    // Chấm công Vào ca (Check-in) / Tan ca (Check-out) / Báo vắng
    public function attendance() {
        $this->requireRole(['ADMIN']);
        $id = (int)($_GET['id'] ?? 0);
        $type =$_GET['type'] ?? 'in';
        $date =$_GET['date'] ?? date('Y-m-d');

        $stmt =$this->db->prepare("
            SELECT s.*, u.full_name, w.shift_name, w.start_time 
            FROM staff_schedules s
            JOIN users u ON s.user_id = u.id
            JOIN work_shifts w ON s.shift_id = w.id
            WHERE s.id = ?
        ");
        $stmt->execute([$id]);
        $sched =$stmt->fetch();

        if ($sched) {$now = date('H:i:s');
            if ($type === 'in') {
                // So sánh giờ chấm công thực tế với giờ bắt đầu ca (cho phép trễ tối đa 5 phút)
                $status = (strtotime($now) > strtotime($sched['start_time']) + 300) ? 'Late' : 'OnTime';$this->db->prepare("UPDATE staff_schedules SET check_in = ?, attendance_status = ? WHERE id = ?")
                         ->execute([$now, $status,$id]);
                $statusLabel = ($status === 'Late') ? 'ĐI TRỄ' : 'ĐÚNG GIỜ';
                $sev = ($status === 'Late') ? 'WARNING' : 'INFO';
                $this->logAction('CHECK_IN', 'Chấm công', "Chấm công VÀO CA ($now - $statusLabel) cho {$sched['full_name']} ({$sched['shift_name']})", $sev);
                $_SESSION['flash_msg'] = "Đã chấm công VÀO CA lúc $now cho {$sched['full_name']} ($statusLabel)!";
            } elseif ($type === 'out') {$this->db->prepare("UPDATE staff_schedules SET check_out = ? WHERE id = ?")
                         ->execute([$now, $id]);$this->logAction('CHECK_OUT', 'Chấm công', "Chấm công TAN CA ($now) cho {$sched['full_name']} ({$sched['shift_name']})", 'INFO');
                $_SESSION['flash_msg'] = "Đã ghi nhận giờ TAN CA lúc $now cho {$sched['full_name']}!";
            } elseif ($type === 'absent') {$this->db->prepare("UPDATE staff_schedules SET attendance_status = 'Absent' WHERE id = ?")
                         ->execute([$id]);$this->logAction('MARK_ABSENT', 'Chấm công', "Ghi nhận VẮNG MẶT ca {$sched['shift_name']} đối với {$sched['full_name']}", 'CRITICAL');
                $_SESSION['flash_err'] = "Đã đánh dấu VẮNG MẶT đối với {$sched['full_name']}!";
            }
        }
        header("Location: index.php?c=menu&a=users&tab=shifts&date=$date");
        exit;
    }
}
