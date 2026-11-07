<?php
class MenuController extends Controller {

    private function logAction($actionType, $moduleName, $description, $severity = 'INFO') {
        $this->writeAuditLog($actionType, $moduleName, $description, $severity);
    }

    public function dashboard() {
        $this->requireRole();

        $selectedDate = $_GET['date'] ?? date('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate) || strtotime($selectedDate) === false) {
            $selectedDate = date('Y-m-d');
        }
        $prevDate = date('Y-m-d', strtotime($selectedDate . ' -1 day'));

        // 1. Chỉ số cơ sở dữ liệu thực tế
        $totalItems  = (int)$this->db->query("SELECT COUNT(*) FROM menu_items")->fetchColumn();
        $totalTables = (int)$this->db->query("SELECT COUNT(*) FROM dining_tables")->fetchColumn();
        $totalUsers  = (int)$this->db->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetchColumn();
        $bomCount    = (int)$this->db->query("SELECT COUNT(DISTINCT menu_item_id) FROM recipes_bom")->fetchColumn();

        // 2. Tình trạng bàn thực tế
        $tableStats = ['Empty' => 0, 'Occupied' => 0, 'Reserved' => 0, 'Cleaning' => 0];
        $rows = $this->db->query("SELECT status, COUNT(*) AS cnt FROM dining_tables GROUP BY status")->fetchAll();
        foreach ($rows as $r) {
            $st = $r['status'];
            if ($st === 'Occupied') $tableStats['Occupied'] += (int)$r['cnt'];
            elseif ($st === 'Reserved') $tableStats['Reserved'] += (int)$r['cnt'];
            elseif ($st === 'Cleaning') $tableStats['Cleaning'] += (int)$r['cnt'];
            else $tableStats['Empty'] += (int)$r['cnt'];
        }
        $occupancyRate = $totalTables > 0
            ? round((($tableStats['Occupied'] + $tableStats['Reserved']) / $totalTables) * 100, 1)
            : 0;

        // 3. Đơn hàng và Đặt bàn theo ngày đang chọn
        $stmtOrdersDate = $this->db->prepare("
            SELECT
                COUNT(*) AS total_orders,
                COALESCE(SUM(status = 'Paid'), 0) AS paid_orders,
                COALESCE(SUM(status = 'Open'), 0) AS open_orders,
                COALESCE(SUM(CASE WHEN status = 'Paid' THEN total_amount ELSE 0 END), 0) AS paid_revenue
            FROM orders
            WHERE DATE(COALESCE(closed_at, opened_at)) = ?
        ");
        $stmtOrdersDate->execute([$selectedDate]);
        $orderDaySummary = $stmtOrdersDate->fetch(PDO::FETCH_ASSOC);

        $todayOrdersCount     = (int)($orderDaySummary['total_orders'] ?? 0);
        $todayPaidOrdersCount = (int)($orderDaySummary['paid_orders'] ?? 0);
        $openOrdersCount      = (int)$this->db->query("SELECT COUNT(*) FROM orders WHERE status = 'Open'")->fetchColumn();

        $stmtPrevRev = $this->db->prepare("
            SELECT COALESCE(SUM(total_amount), 0)
            FROM orders
            WHERE DATE(COALESCE(closed_at, opened_at)) = ? AND status = 'Paid'
        ");
        $stmtPrevRev->execute([$prevDate]);
        $yesterdayRevenue = (float)$stmtPrevRev->fetchColumn();

        $stmtBookingsDate = $this->db->prepare("
            SELECT
                COUNT(*) AS total_bookings,
                COALESCE(SUM(status IN ('Pending', 'Confirmed')), 0) AS upcoming_bookings,
                COALESCE(SUM(status = 'CheckedIn'), 0) AS checkedin_bookings
            FROM reservations
            WHERE DATE(booking_time) = ?
        ");
        $stmtBookingsDate->execute([$selectedDate]);
        $bookingDaySummary = $stmtBookingsDate->fetch(PDO::FETCH_ASSOC);
        $todayBookingsCount    = (int)($bookingDaySummary['total_bookings'] ?? 0);
        $upcomingBookingsCount = (int)($bookingDaySummary['upcoming_bookings'] ?? 0);

        // 4. Chuỗi doanh thu & số đơn 7 ngày tính đến ngày đang chọn
        $dailyLabels  = [];
        $dailyRevenue = [];
        $dailyOrders  = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime($selectedDate . " -$i days"));
            $dailyLabels[] = date('d/m', strtotime($d));

            $stmtD = $this->db->prepare("
                SELECT
                    COALESCE(SUM(CASE WHEN status = 'Paid' THEN total_amount ELSE 0 END), 0) AS rev,
                    COALESCE(SUM(status = 'Paid'), 0) AS cnt
                FROM orders
                WHERE DATE(COALESCE(closed_at, opened_at)) = ?
            ");
            $stmtD->execute([$d]);
            $rowD = $stmtD->fetch(PDO::FETCH_ASSOC);
            $dailyRevenue[] = (float)($rowD['rev'] ?? 0);
            $dailyOrders[]  = (int)($rowD['cnt'] ?? 0);
        }
        $todayRevenue = end($dailyRevenue) ?: 0;
        $weekRevenue  = array_sum($dailyRevenue);
        $weekPaidOrders = array_sum($dailyOrders);
        $avgOrderValue = $todayPaidOrdersCount > 0
            ? round($todayRevenue / $todayPaidOrdersCount)
            : ($weekPaidOrders > 0 ? round($weekRevenue / $weekPaidOrders) : 0);

        // 5. Doanh thu 6 tháng gần nhất
        $monthlyLabels  = [];
        $monthlyRevenue = [];
        for ($m = 5; $m >= 0; $m--) {
            $ym = date('Y-m', strtotime(date('Y-m-01', strtotime($selectedDate)) . " -$m months"));
            $monthlyLabels[] = 'Tháng ' . date('m/Y', strtotime($ym . '-01'));
            $stmtM = $this->db->prepare("
                SELECT COALESCE(SUM(total_amount), 0)
                FROM orders
                WHERE DATE_FORMAT(COALESCE(closed_at, opened_at), '%Y-%m') = ? AND status = 'Paid'
            ");
            $stmtM->execute([$ym]);
            $monthlyRevenue[] = (float)$stmtM->fetchColumn();
        }

        // 6. Tỷ trọng phương thức thanh toán thực tế
        $paymentBreakdown = $this->db->query("
            SELECT payment_method, COUNT(*) AS tx_count, COALESCE(SUM(amount), 0) AS total_amount
            FROM payments
            GROUP BY payment_method
            ORDER BY total_amount DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        // 7. Top 5 món ăn theo doanh thu thực tế
        $topDishes = $this->db->query("
            SELECT m.item_code, m.name, m.unit, c.name AS category_name, m.price,
                   COALESCE(SUM(CASE WHEN o.status = 'Paid' AND od.kds_status <> 'Cancelled' THEN od.quantity ELSE 0 END), 0) AS total_qty,
                   COALESCE(SUM(CASE WHEN o.status = 'Paid' AND od.kds_status <> 'Cancelled' THEN od.quantity * od.unit_price ELSE 0 END), 0) AS total_rev
            FROM menu_items m
            JOIN categories c ON m.category_id = c.id
            LEFT JOIN order_details od ON m.id = od.menu_item_id
            LEFT JOIN orders o ON od.order_id = o.id
            GROUP BY m.id
            ORDER BY total_rev DESC, m.price DESC
            LIMIT 5
        ")->fetchAll();

        // 8. Cảnh báo vận hành thực tế
        $lowStockItems = $this->db->query("
            SELECT * FROM ingredients
            WHERE stock_quantity <= min_stock_level
            ORDER BY (stock_quantity - min_stock_level) ASC
            LIMIT 6
        ")->fetchAll();

        $pendingKdsCount = (int)$this->db->query("
            SELECT COUNT(*)
            FROM order_details od
            JOIN orders o ON od.order_id = o.id
            WHERE o.status = 'Open' AND od.kds_status IN ('Waiting', 'Cooking')
        ")->fetchColumn();

        $openShift = $this->db->query("
            SELECT cs.*, u.full_name AS cashier_name
            FROM cash_shifts cs
            LEFT JOIN users u ON cs.cashier_id = u.id
            WHERE cs.status = 'Open'
            ORDER BY cs.id DESC LIMIT 1
        ")->fetch(PDO::FETCH_ASSOC);

        // 9. Hoạt động gần đây
        $recentLogs = $this->db->query("
            SELECT l.*, u.full_name, r.role_code
            FROM audit_logs l
            JOIN users u ON l.user_id = u.id
            JOIN roles r ON u.role_id = r.id
            ORDER BY l.id DESC LIMIT 6
        ")->fetchAll();

        $this->render('menu/dashboard', compact(
            'selectedDate', 'prevDate', 'totalItems', 'totalTables', 'totalUsers', 'bomCount',
            'tableStats', 'occupancyRate',
            'todayOrdersCount', 'todayPaidOrdersCount', 'openOrdersCount',
            'todayBookingsCount', 'upcomingBookingsCount',
            'dailyLabels', 'dailyRevenue', 'dailyOrders', 'todayRevenue', 'yesterdayRevenue', 'weekRevenue', 'avgOrderValue',
            'monthlyLabels', 'monthlyRevenue',
            'paymentBreakdown', 'topDishes', 'lowStockItems', 'pendingKdsCount', 'openShift', 'recentLogs'
        ));
    }

    public function index() {
        $this->requireRole(['ADMIN', 'CHEF']);

        $catFilter = isset($_GET['cat_id']) ? (int)$_GET['cat_id'] : 0;
        $params = [];
        $whereSql = "";
        if ($catFilter > 0) {
            $whereSql = "WHERE m.category_id = ?";
            $params[] = $catFilter;
        }

        $stmtItems = $this->db->prepare("
            SELECT m.*, c.name AS category_name,
            (SELECT COUNT(*) FROM recipes_bom b WHERE b.menu_item_id = m.id) AS bom_count,
            (SELECT GROUP_CONCAT(CONCAT(i.name, ' (', b.quantity_required, ' ', i.unit, ')') SEPARATOR ' • ')
             FROM recipes_bom b JOIN ingredients i ON b.ingredient_id = i.id WHERE b.menu_item_id = m.id) AS bom_list,
            (SELECT GROUP_CONCAT(CONCAT('[', im.modifier_group, '] ', im.modifier_name, IF(im.extra_price > 0, CONCAT(' (+', FORMAT(im.extra_price, 0), 'đ)'), '')) SEPARATOR ' • ')
             FROM item_modifiers im WHERE im.menu_item_id = m.id) AS modifier_list
            FROM menu_items m 
            JOIN categories c ON m.category_id = c.id 
            $whereSql
            ORDER BY m.id DESC
        ");
        $stmtItems->execute($params);
        $items = $stmtItems->fetchAll();

        $categories = $this->db->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll();
        $ingredients = $this->db->query("SELECT * FROM ingredients ORDER BY name ASC")->fetchAll();

        $bomRaw = $this->db->query("
            SELECT b.menu_item_id, b.ingredient_id, b.quantity_required, i.name AS ingredient_name, i.unit
            FROM recipes_bom b
            JOIN ingredients i ON b.ingredient_id = i.id
            ORDER BY i.name ASC
        ")->fetchAll();
        $bomByItem = [];
        foreach ($bomRaw as $row) {
            $bomByItem[$row['menu_item_id']][] = $row;
        }

        $modRaw = $this->db->query("SELECT * FROM item_modifiers ORDER BY modifier_group ASC, id ASC")->fetchAll();
        $modByItem = [];
        foreach ($modRaw as $row) {
            $modByItem[$row['menu_item_id']][] = $row;
        }

        $this->render('menu/index', compact('items', 'categories', 'ingredients', 'bomByItem', 'modByItem', 'catFilter'));
    }

    public function saveCategory() {
        $this->requireRole(['ADMIN', 'CHEF']);
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty(trim($_POST['category_name'] ?? ''))) {
            $catName = trim($_POST['category_name']);
            $courseType = $_POST['course_type'] ?? 'Main_Course';
            $allowedCourses = ['Appetizer', 'Soup_Salad', 'Main_Course', 'Dessert', 'Beverage', 'Set_Menu'];
            if (!in_array($courseType, $allowedCourses, true)) {
                $courseType = 'Main_Course';
            }
            $stmt = $this->db->prepare("INSERT INTO categories (name, course_type) VALUES (?, ?)");
            $stmt->execute([$catName, $courseType]);
            $this->logAction('CREATE_CATEGORY', 'Thực đơn & BOM', "Thêm nhóm thực đơn mới: $catName", 'INFO');
            $_SESSION['flash_msg'] = "Đã thêm nhóm thực đơn '$catName' thành công!";
        }
        header("Location: index.php?c=menu&a=index");
        exit;
    }

    public function saveMenuItem() {
        $this->requireRole(['ADMIN', 'CHEF']);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = !empty($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
            $categoryId = (int)($_POST['category_id'] ?? 0);
            $itemCode = trim($_POST['item_code'] ?? '');
            $name = trim($_POST['name'] ?? '');
            $unit = trim($_POST['unit'] ?? 'Phần') ?: 'Phần';
            $price = (float)($_POST['price'] ?? 0);
            $status = $_POST['status'] ?? 'Available';
            if (!in_array($status, ['Available', 'Unavailable', 'OutOfStock', 'Discontinued'], true)) {
                $status = 'Available';
            }

            if ($categoryId <= 0 || $itemCode === '' || $name === '' || $price < 0) {
                $_SESSION['flash_err'] = "Vui lòng nhập đầy đủ thông tin món ăn hợp lệ!";
                header("Location: index.php?c=menu&a=index");
                exit;
            }

            $imageUrl = trim($_POST['image_url'] ?? '');
            if ($id > 0 && $imageUrl === '') {
                $stmtOld = $this->db->prepare("SELECT image_url FROM menu_items WHERE id = ?");
                $stmtOld->execute([$id]);
                $imageUrl = (string)$stmtOld->fetchColumn();
            }

            if (!empty($_FILES['image_file']['name']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../public/uploads/menu/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $ext = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
                $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                if (in_array($ext, $allowedExts, true)) {
                    $safeCode = preg_replace('/[^A-Za-z0-9_-]/', '_', $itemCode);
                    $fileName = 'dish_' . $safeCode . '_' . time() . '.' . $ext;
                    if (move_uploaded_file($_FILES['image_file']['tmp_name'], $uploadDir . $fileName)) {
                        $imageUrl = 'public/uploads/menu/' . $fileName;
                    }
                }
            }

            if ($id > 0) {
                try {
                    $stmt = $this->db->prepare("
                        UPDATE menu_items 
                        SET category_id = ?, item_code = ?, name = ?, unit = ?, price = ?, image_url = ?, status = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$categoryId, $itemCode, $name, $unit, $price, $imageUrl !== '' ? $imageUrl : null, $status, $id]);
                    $this->logAction('UPDATE_MENU', 'Thực đơn & BOM', "Cập nhật món '$name' ($itemCode)", 'WARNING');
                    $_SESSION['flash_msg'] = "Đã cập nhật món '$name' thành công!";
                } catch (PDOException $e) {
                    $_SESSION['flash_err'] = "Lỗi: Mã món '$itemCode' đã tồn tại!";
                }
            } else {
                try {
                    $stmt = $this->db->prepare("
                        INSERT INTO menu_items (category_id, item_code, name, unit, price, image_url, status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$categoryId, $itemCode, $name, $unit, $price, $imageUrl !== '' ? $imageUrl : null, $status]);
                    $this->logAction('CREATE_MENU', 'Thực đơn & BOM', "Thêm món mới '$name' ($itemCode)", 'INFO');
                    $_SESSION['flash_msg'] = "Đã thêm món '$name' vào thực đơn!";
                } catch (PDOException $e) {
                    $_SESSION['flash_err'] = "Lỗi: Mã món '$itemCode' đã tồn tại!";
                }
            }
        }
        header("Location: index.php?c=menu&a=index");
        exit;
    }

    public function toggleMenuStatus() {
        $this->requireRole(['ADMIN', 'CHEF']);
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $this->db->prepare("SELECT name, item_code, status FROM menu_items WHERE id = ?");
        $stmt->execute([$id]);
        $item = $stmt->fetch();
        if ($item) {
            $newStatus = ($item['status'] === 'Available') ? 'Unavailable' : 'Available';
            $this->db->prepare("UPDATE menu_items SET status = ? WHERE id = ?")->execute([$newStatus, $id]);
            $_SESSION['flash_msg'] = "Đã cập nhật trạng thái món {$item['name']}!";
        }
        header("Location: index.php?c=menu&a=index");
        exit;
    }

    public function deleteMenuItem() {
        $this->requireRole(['ADMIN']);
        $id = (int)($_GET['id'] ?? 0);
        try {
            $this->db->beginTransaction();
            $this->db->prepare("DELETE FROM recipes_bom WHERE menu_item_id = ?")->execute([$id]);
            $this->db->prepare("DELETE FROM item_modifiers WHERE menu_item_id = ?")->execute([$id]);
            $this->db->prepare("DELETE FROM menu_items WHERE id = ?")->execute([$id]);
            $this->db->commit();
            $_SESSION['flash_msg'] = "Đã xóa món thành công!";
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $_SESSION['flash_err'] = "Không thể xóa món này vì đã phát sinh trong hóa đơn!";
        }
        header("Location: index.php?c=menu&a=index");
        exit;
    }

    public function addBom() {
        $this->requireRole(['ADMIN', 'CHEF']);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $menuItemId = (int)($_POST['menu_item_id'] ?? 0);
            $ingredientId = (int)($_POST['ingredient_id'] ?? 0);
            $qty = (float)($_POST['quantity_required'] ?? 0);

            if ($menuItemId > 0 && $ingredientId > 0 && $qty > 0) {
                $check = $this->db->prepare("SELECT COUNT(*) FROM recipes_bom WHERE menu_item_id = ? AND ingredient_id = ?");
                $check->execute([$menuItemId, $ingredientId]);
                if ($check->fetchColumn() > 0) {
                    $stmt = $this->db->prepare("UPDATE recipes_bom SET quantity_required = ? WHERE menu_item_id = ? AND ingredient_id = ?");
                    $stmt->execute([$qty, $menuItemId, $ingredientId]);
                } else {
                    $stmt = $this->db->prepare("INSERT INTO recipes_bom (menu_item_id, ingredient_id, quantity_required) VALUES (?, ?, ?)");
                    $stmt->execute([$menuItemId, $ingredientId, $qty]);
                }
                $_SESSION['flash_msg'] = "Đã cập nhật định lượng BOM thành công!";
            }
            header("Location: index.php?c=menu&a=index&open_bom=$menuItemId");
            exit;
        }
    }

    public function deleteBom() {
        $this->requireRole(['ADMIN', 'CHEF']);
        $menuItemId = (int)($_GET['menu_item_id'] ?? 0);
        $ingredientId = (int)($_GET['ingredient_id'] ?? 0);
        if ($menuItemId > 0 && $ingredientId > 0) {
            $this->db->prepare("DELETE FROM recipes_bom WHERE menu_item_id = ? AND ingredient_id = ?")->execute([$menuItemId, $ingredientId]);
            $_SESSION['flash_msg'] = "Đã xóa nguyên liệu khỏi BOM!";
        }
        header("Location: index.php?c=menu&a=index&open_bom=$menuItemId");
        exit;
    }

    public function addModifier() {
        $this->requireRole(['ADMIN', 'CHEF']);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $menuItemId = (int)($_POST['menu_item_id'] ?? 0);
            $group = trim($_POST['modifier_group'] ?? 'Tùy chọn') ?: 'Tùy chọn';
            $name = trim($_POST['modifier_name'] ?? '');
            $extraPrice = max(0, (float)($_POST['extra_price'] ?? 0));

            if ($menuItemId > 0 && $name !== '') {
                $stmt = $this->db->prepare("INSERT INTO item_modifiers (menu_item_id, modifier_group, modifier_name, extra_price) VALUES (?, ?, ?, ?)");
                $stmt->execute([$menuItemId, $group, $name, $extraPrice]);
                $_SESSION['flash_msg'] = "Đã thêm tùy chọn phục vụ thành công!";
            }
            header("Location: index.php?c=menu&a=index&open_mod=$menuItemId");
            exit;
        }
    }

    public function deleteModifier() {
        $this->requireRole(['ADMIN', 'CHEF']);
        $id = (int)($_GET['id'] ?? 0);
        $menuItemId = (int)($_GET['menu_item_id'] ?? 0);
        if ($id > 0) {
            $this->db->prepare("DELETE FROM item_modifiers WHERE id = ?")->execute([$id]);
            $_SESSION['flash_msg'] = "Đã xóa tùy chọn phục vụ!";
        }
        header("Location: index.php?c=menu&a=index&open_mod=$menuItemId");
        exit;
    }

// ==================== PHÂN HỆ I: NHÂN SỰ, CA LÀM VIỆC & PHÂN QUYỀN ====================
    public function users() {
        $this->requireRole();

        $currentUserId = (int)($_SESSION['user']['id'] ?? 0);
        $currentUserRole = strtoupper($_SESSION['user']['role_code'] ?? '');
        $isAdmin = ($currentUserRole === 'ADMIN');

        try {
            $this->db->exec("ALTER TABLE roles ADD COLUMN IF NOT EXISTS hourly_rate DECIMAL(12,2) NOT NULL DEFAULT 35000 AFTER role_name");
            $this->db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS hourly_rate DECIMAL(12,2) DEFAULT NULL AFTER phone");
        } catch (Throwable $e) {}

        $users = $this->db->query("
            SELECT u.*, r.role_code, r.role_name,
                   COALESCE(NULLIF(u.hourly_rate, 0), r.hourly_rate, 35000) AS effective_hourly_rate
            FROM users u 
            JOIN roles r ON u.role_id = r.id 
            ORDER BY u.id ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $roles = $this->db->query("SELECT * FROM roles ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
        $shifts = $this->db->query("SELECT * FROM work_shifts ORDER BY start_time ASC")->fetchAll(PDO::FETCH_ASSOC);

        $filterDate = $_GET['date'] ?? date('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $filterDate) || strtotime($filterDate) === false) {
            $filterDate = date('Y-m-d');
        }
        $selectedMonth = $_GET['month'] ?? date('Y-m', strtotime($filterDate));

        $ts = strtotime($filterDate);
        $dayOfWeek = (int)date('N', $ts);
        $mondayTs = strtotime('-' . ($dayOfWeek - 1) . ' days', $ts);
        $weekStart = date('Y-m-d', $mondayTs);
        $weekEnd   = date('Y-m-d', strtotime('+6 days', $mondayTs));

        $weekDays = [];
        $dayNamesVi = [1 => 'Thứ 2', 2 => 'Thứ 3', 3 => 'Thứ 4', 4 => 'Thứ 5', 5 => 'Thứ 6', 6 => 'Thứ 7', 7 => 'Chủ nhật'];
        for ($i = 0; $i < 7; $i++) {
            $dTs = strtotime("+$i days", $mondayTs);
            $dStr = date('Y-m-d', $dTs);
            $weekDays[] = [
                'date'      => $dStr,
                'label'     => $dayNamesVi[$i + 1],
                'day_num'   => date('d/m', $dTs),
                'is_today'  => ($dStr === date('Y-m-d'))
            ];
        }

        $stmtSched = $this->db->prepare("
            SELECT s.*, u.full_name, u.username, r.role_code, r.role_name,
                   COALESCE(NULLIF(u.hourly_rate, 0), r.hourly_rate, 35000) AS hourly_rate,
                   w.shift_name, w.start_time, w.end_time, w.allowance
            FROM staff_schedules s
            JOIN users u ON s.user_id = u.id
            JOIN roles r ON u.role_id = r.id
            JOIN work_shifts w ON s.shift_id = w.id
            WHERE s.work_date = ?
            ORDER BY w.start_time ASC, u.full_name ASC
        ");
        $stmtSched->execute([$filterDate]);
        $schedules = $stmtSched->fetchAll(PDO::FETCH_ASSOC);

        $stmtWeek = $this->db->prepare("
            SELECT s.*, u.full_name, u.username, r.role_code, r.role_name,
                   COALESCE(NULLIF(u.hourly_rate, 0), r.hourly_rate, 35000) AS hourly_rate,
                   w.shift_name, w.start_time, w.end_time, w.allowance
            FROM staff_schedules s
            JOIN users u ON s.user_id = u.id
            JOIN roles r ON u.role_id = r.id
            JOIN work_shifts w ON s.shift_id = w.id
            WHERE s.work_date BETWEEN ? AND ?
            ORDER BY s.work_date ASC, w.start_time ASC, u.id ASC
        ");
        $stmtWeek->execute([$weekStart, $weekEnd]);
        $weekSchedulesRaw = $stmtWeek->fetchAll(PDO::FETCH_ASSOC);

        $weekGrid = [];
        foreach ($weekSchedulesRaw as $ws) {
            $weekGrid[$ws['shift_id']][$ws['work_date']][] = $ws;
        }

        $todayStr = date('Y-m-d');
        $stmtMyToday = $this->db->prepare("
            SELECT s.*, w.shift_name, w.start_time, w.end_time, w.allowance
            FROM staff_schedules s
            JOIN work_shifts w ON s.shift_id = w.id
            WHERE s.user_id = ? AND s.work_date = ?
            ORDER BY w.start_time ASC
        ");
        $stmtMyToday->execute([$currentUserId, $todayStr]);
        $myTodayShifts = $stmtMyToday->fetchAll(PDO::FETCH_ASSOC);

        $stmtMyNext = $this->db->prepare("
            SELECT s.*, w.shift_name, w.start_time, w.end_time
            FROM staff_schedules s
            JOIN work_shifts w ON s.shift_id = w.id
            WHERE s.user_id = ? AND s.work_date > ?
            ORDER BY s.work_date ASC, w.start_time ASC
            LIMIT 1
        ");
        $stmtMyNext->execute([$currentUserId, $todayStr]);
        $myNextShift = $stmtMyNext->fetch(PDO::FETCH_ASSOC);

        $myWeekHours = 0.0;
        $myWeekRegistered = 0;
        $myWeekCompleted = 0;
        foreach ($weekSchedulesRaw as $ws) {
            if ((int)$ws['user_id'] === $currentUserId && $ws['attendance_status'] !== 'Absent') {
                $myWeekRegistered++;
                if (!empty($ws['check_in']) && !empty($ws['check_out'])) {
                    $hrs = $this->calcActualWorkedHours($ws['check_in'], $ws['check_out']);
                    if ($hrs > 0) {
                        $myWeekHours += $hrs;
                        $myWeekCompleted++;
                    }
                }
            }
        }

        $payrollSummary = [
            'month'        => $selectedMonth,
            'total_amount' => 0,
            'paid_amount'  => 0,
            'unpaid_amount'=> 0,
            'total_hours'  => 0,
            'staff_rows'   => []
        ];

        if ($isAdmin) {
            $stmtMonthSched = $this->db->prepare("
                SELECT s.*, w.start_time, w.end_time, w.allowance
                FROM staff_schedules s
                JOIN work_shifts w ON s.shift_id = w.id
                WHERE DATE_FORMAT(s.work_date, '%Y-%m') = ?
            ");
            $stmtMonthSched->execute([$selectedMonth]);
            $monthScheds = $stmtMonthSched->fetchAll(PDO::FETCH_ASSOC);

            $byUserMonth = [];
            foreach ($monthScheds as $ms) {
                $byUserMonth[(int)$ms['user_id']][] = $ms;
            }

            foreach ($users as $u) {
                $uid = (int)$u['id'];
                $rate = (float)$u['effective_hourly_rate'];
                $uScheds = $byUserMonth[$uid] ?? [];

                $totalShiftsCount = 0;
                $workedShiftsCount = 0;
                $workedHours = 0.0;
                $scheduledHours = 0.0;
                $allowanceTotal = 0.0;
                $pendingScheduledHours = 0.0;

                foreach ($uScheds as $ms) {
                    if ($ms['attendance_status'] === 'Absent') {
                        continue;
                    }
                    $totalShiftsCount++;
                    $stdHours = $this->calcShiftHours($ms['start_time'], $ms['end_time']);
                    $scheduledHours += $stdHours;

                    // Chỉ cộng dồn giờ thực tế khi có chấm công vào ca và tan ca
                    if (!empty($ms['check_in']) && !empty($ms['check_out'])) {
                        $actualHrs = $this->calcActualWorkedHours($ms['check_in'], $ms['check_out']);
                        if ($actualHrs > 0) {
                            $workedShiftsCount++;
                            $workedHours += $actualHrs;
                            $allowanceTotal += (float)$ms['allowance'];
                        }
                    } else {
                        $pendingScheduledHours += $stdHours;
                    }
                }

                $workedHours = round($workedHours, 2);
                $scheduledHours = round($scheduledHours, 2);

                // Không có ca làm hoặc chưa chấm công -> 0 giờ, 0đ
                $earnedPay = $workedHours > 0 ? round(($workedHours * $rate) + $allowanceTotal) : 0;
                $unpaidExpected = $pendingScheduledHours > 0 ? round($pendingScheduledHours * $rate) : 0;
                $finalMonthPay = $earnedPay;

                $payrollSummary['total_amount']  += $finalMonthPay;
                $payrollSummary['paid_amount']   += $earnedPay;
                $payrollSummary['unpaid_amount'] += $unpaidExpected;
                $payrollSummary['total_hours']   += $workedHours;

                $payrollSummary['staff_rows'][] = [
                    'user_id'           => $uid,
                    'full_name'         => $u['full_name'],
                    'username'          => $u['username'],
                    'role_code'         => $u['role_code'],
                    'role_name'         => $u['role_name'],
                    'hourly_rate'       => $rate,
                    'total_shifts'      => $totalShiftsCount,
                    'worked_shifts'     => $workedShiftsCount,
                    'worked_hours'      => $workedHours,
                    'scheduled_hours'   => $scheduledHours,
                    'allowance_total'   => $allowanceTotal,
                    'earned_pay'        => $earnedPay,
                    'total_month_pay'   => $finalMonthPay
                ];
            }
            $payrollSummary['total_hours'] = round($payrollSummary['total_hours'], 2);
        }

        $severityFilter = $_GET['severity'] ?? '';
        if ($severityFilter && in_array($severityFilter, ['INFO', 'WARNING', 'CRITICAL'], true)) {
            $stmtLog = $this->db->prepare("
                SELECT l.*, u.full_name, u.username, r.role_code
                FROM audit_logs l
                JOIN users u ON l.user_id = u.id
                JOIN roles r ON u.role_id = r.id
                WHERE l.severity = ?
                ORDER BY l.id DESC LIMIT 30
            ");
            $stmtLog->execute([$severityFilter]);
            $auditLogs = $stmtLog->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $auditLogs = $this->db->query("
                SELECT l.*, u.full_name, u.username, r.role_code
                FROM audit_logs l
                JOIN users u ON l.user_id = u.id
                JOIN roles r ON u.role_id = r.id
                ORDER BY l.id DESC LIMIT 30
            ")->fetchAll(PDO::FETCH_ASSOC);
        }

        $this->render('menu/users', compact(
            'isAdmin', 'currentUserId', 'users', 'roles', 'shifts',
            'schedules', 'weekDays', 'weekStart', 'weekEnd', 'weekGrid',
            'myTodayShifts', 'myNextShift', 'myWeekHours', 'myWeekRegistered', 'myWeekCompleted',
            'payrollSummary', 'selectedMonth', 'auditLogs', 'filterDate', 'severityFilter'
        ));
    }

    // Tính số giờ chuẩn của khung ca
    private function calcShiftHours($startTime, $endTime): float {
        if (empty($startTime) || empty($endTime)) {
            return 0.0;
        }
        $start = strtotime($startTime);
        $end   = strtotime($endTime);
        if ($start === false || $end === false) {
            return 0.0;
        }
        if ($end <= $start) {
            $end += 86400;
        }
        return round(($end - $start) / 3600, 2);
    }

    // Tính số giờ thực tế làm việc từ giờ vào ca (check_in) đến giờ tan ca (check_out)
    private function calcActualWorkedHours(?string $checkIn, ?string $checkOut): float {
        if (empty($checkIn) || empty($checkOut)) {
            return 0.0;
        }
        $start = strtotime($checkIn);
        $end   = strtotime($checkOut);
        if ($start === false || $end === false) {
            return 0.0;
        }
        if ($end < $start) {
            $end += 86400;
        }
        $hours = ($end - $start) / 3600;
        return round(max(0.0, $hours), 2);
    }

    public function saveUser() {
        $this->requireRole(['ADMIN']);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = !empty($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
            $fullName = trim($_POST['full_name'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $roleId = (int)($_POST['role_id'] ?? 0);
            $hourlyRate = !empty($_POST['hourly_rate']) ? max(0, (float)$_POST['hourly_rate']) : null;

            if ($fullName === '' || $roleId <= 0 || ($id === 0 && $username === '')) {
                $_SESSION['flash_err'] = "Vui lòng nhập đầy đủ thông tin!";
                header("Location: index.php?c=menu&a=users&tab=staff");
                exit;
            }

            if ($id > 0) {
                $stmtChk = $this->db->prepare("SELECT u.id, u.username, u.role_id, r.role_code FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?");
                $stmtChk->execute([$id]);
                $targetUser = $stmtChk->fetch(PDO::FETCH_ASSOC);

                if ($targetUser && ($targetUser['id'] == 1 || strtolower($targetUser['username']) === 'admin' || strtoupper($targetUser['role_code']) === 'ADMIN')) {
                    $roleId = (int)$targetUser['role_id'];
                }

                $stmt = $this->db->prepare("UPDATE users SET full_name = ?, phone = ?, role_id = ?, hourly_rate = ? WHERE id = ?");
                $stmt->execute([$fullName, $phone, $roleId, $hourlyRate, $id]);
                $_SESSION['flash_msg'] = "Đã cập nhật hồ sơ nhân viên thành công!";
            } else {
                $password = !empty($_POST['password']) ? $_POST['password'] : '123456';
                $hash = password_hash($password, PASSWORD_BCRYPT);
                try {
                    $stmt = $this->db->prepare("
                        INSERT INTO users (role_id, username, password_hash, full_name, phone, hourly_rate, is_active)
                        VALUES (?, ?, ?, ?, ?, ?, 1)
                    ");
                    $stmt->execute([$roleId, $username, $hash, $fullName, $phone, $hourlyRate]);
                    $_SESSION['flash_msg'] = "Đã thêm nhân viên mới thành công!";
                } catch (PDOException $e) {
                    $_SESSION['flash_err'] = "Lỗi: Tên đăng nhập đã tồn tại!";
                }
            }
        }
        header("Location: index.php?c=menu&a=users&tab=staff");
        exit;
    }

    public function saveRoleRate() {
        $this->requireRole(['ADMIN']);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $roleId = (int)($_POST['role_id'] ?? 0);
            $hourlyRate = max(0, (float)($_POST['hourly_rate'] ?? 0));
            $syncUsers = !empty($_POST['sync_users']);

            if ($roleId > 0 && $hourlyRate > 0) {
                $this->db->prepare("UPDATE roles SET hourly_rate = ? WHERE id = ?")->execute([$hourlyRate, $roleId]);
                if ($syncUsers) {
                    $this->db->prepare("UPDATE users SET hourly_rate = ? WHERE role_id = ?")->execute([$hourlyRate, $roleId]);
                }
                $_SESSION['flash_msg'] = "Đã cập nhật đơn giá lương theo giờ thành công!";
            }
        }
        header("Location: index.php?c=menu&a=users&tab=payroll");
        exit;
    }

    public function saveShift() {
        $this->requireRole(['ADMIN']);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $shiftCode = strtoupper(trim($_POST['shift_code'] ?? ('SHIFT_' . uniqid())));
            $shiftName = trim($_POST['shift_name'] ?? '');
            $startTime = trim($_POST['start_time'] ?? '08:00:00');
            $endTime   = trim($_POST['end_time'] ?? '14:00:00');
            $allowance = max(0, (float)($_POST['allowance'] ?? 0));
            $desc      = trim($_POST['description'] ?? '');

            if ($shiftName !== '') {
                try {
                    $stmt = $this->db->prepare("
                        INSERT INTO work_shifts (shift_code, shift_name, start_time, end_time, allowance, description)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$shiftCode, $shiftName, $startTime, $endTime, $allowance, $desc]);
                    $_SESSION['flash_msg'] = "Đã thêm khung ca làm việc thành công!";
                } catch (PDOException $e) {
                    $_SESSION['flash_err'] = "Mã ca làm việc đã tồn tại!";
                }
            }
        }
        header("Location: index.php?c=menu&a=users&tab=shifts");
        exit;
    }

    public function toggleUserStatus() {
        $this->requireRole(['ADMIN']);
        $id = (int)($_GET['id'] ?? 0);

        $stmt = $this->db->prepare("
            SELECT u.id, u.full_name, u.username, u.is_active, r.role_code
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE u.id = ?
        ");
        $stmt->execute([$id]);
        $u = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$u || $id === 1 || strtolower($u['username']) === 'admin' || strtoupper($u['role_code']) === 'ADMIN' || $id === (int)$_SESSION['user']['id']) {
            $_SESSION['flash_err'] = "Không thể khóa tài khoản Quản trị viên!";
            header("Location: index.php?c=menu&a=users&tab=staff");
            exit;
        }

        $newStatus = $u['is_active'] ? 0 : 1;
        $this->db->prepare("UPDATE users SET is_active = ? WHERE id = ?")->execute([$newStatus, $id]);
        $_SESSION['flash_msg'] = "Đã cập nhật trạng thái tài khoản!";
        header("Location: index.php?c=menu&a=users&tab=staff");
        exit;
    }

    public function resetPassword() {
        $this->requireRole(['ADMIN']);
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $this->db->prepare("SELECT full_name FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $u = $stmt->fetch();
        if ($u) {
            $hash = password_hash('123456', PASSWORD_BCRYPT);
            $this->db->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$hash, $id]);
            $_SESSION['flash_msg'] = "Đã đặt lại mật khẩu về 123456!";
        }
        header("Location: index.php?c=menu&a=users&tab=staff");
        exit;
    }

    public function assignShift() {
        $this->requireRole(['ADMIN']);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = (int)($_POST['user_id'] ?? 0);
            $shiftId = (int)($_POST['shift_id'] ?? 0);
            $workDate = $_POST['work_date'] ?? date('Y-m-d');
            $note = trim($_POST['note'] ?? '');

            try {
                $stmt = $this->db->prepare("
                    INSERT INTO staff_schedules (user_id, shift_id, work_date, attendance_status, note)
                    VALUES (?, ?, ?, 'Scheduled', ?)
                ");
                $stmt->execute([$userId, $shiftId, $workDate, $note]);
                $_SESSION['flash_msg'] = "Đã phân ca thành công!";
            } catch (PDOException $e) {
                $_SESSION['flash_err'] = "Nhân viên này đã có ca làm việc này trong ngày!";
            }
            header("Location: index.php?c=menu&a=users&tab=shifts&date=$workDate");
            exit;
        }
    }

    public function deleteSchedule() {
        $this->requireRole(['ADMIN']);
        $id = (int)($_GET['id'] ?? 0);
        $date = $_GET['date'] ?? date('Y-m-d');
        if ($id > 0) {
            $this->db->prepare("DELETE FROM staff_schedules WHERE id = ?")->execute([$id]);
            $_SESSION['flash_msg'] = "Đã gỡ lịch phân ca!";
        }
        header("Location: index.php?c=menu&a=users&tab=shifts&date=$date");
        exit;
    }

    public function attendance() {
        $this->requireRole();
        $id = (int)($_REQUEST['id'] ?? 0);
        $type = $_REQUEST['type'] ?? 'in';
        $date = $_REQUEST['date'] ?? date('Y-m-d');
        $currentUserId = (int)($_SESSION['user']['id'] ?? 0);
        $isAdmin = (strtoupper($_SESSION['user']['role_code'] ?? '') === 'ADMIN');

        $stmt = $this->db->prepare("
            SELECT s.*, u.full_name, w.shift_name, w.start_time, w.end_time 
            FROM staff_schedules s
            JOIN users u ON s.user_id = u.id
            JOIN work_shifts w ON s.shift_id = w.id
            WHERE s.id = ?
        ");
        $stmt->execute([$id]);
        $sched = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($sched) {
            if (!$isAdmin && (int)$sched['user_id'] !== $currentUserId) {
                $_SESSION['flash_err'] = "Bạn chỉ có thể chấm công cho ca làm việc của chính mình!";
                header("Location: index.php?c=menu&a=users");
                exit;
            }

            $now = date('H:i:s');
            if ($type === 'save_time' && $isAdmin) {
                $inTime  = trim($_POST['check_in'] ?? '');
                $outTime = trim($_POST['check_out'] ?? '');
                $inVal   = $inTime !== '' ? (strlen($inTime) === 5 ? $inTime . ':00' : $inTime) : null;
                $outVal  = $outTime !== '' ? (strlen($outTime) === 5 ? $outTime . ':00' : $outTime) : null;

                $status = 'Scheduled';
                if ($inVal !== null) {
                    $status = (strtotime($inVal) > strtotime($sched['start_time']) + 300) ? 'Late' : 'OnTime';
                }
                $this->db->prepare("UPDATE staff_schedules SET check_in = ?, check_out = ?, attendance_status = ? WHERE id = ?")
                         ->execute([$inVal, $outVal, $status, $id]);
                $_SESSION['flash_msg'] = "Đã cập nhật giờ chấm công thực tế!";
            } elseif ($type === 'in') {
                $status = (strtotime($now) > strtotime($sched['start_time']) + 300) ? 'Late' : 'OnTime';
                $this->db->prepare("UPDATE staff_schedules SET check_in = ?, attendance_status = ? WHERE id = ?")
                         ->execute([$now, $status, $id]);
                $_SESSION['flash_msg'] = "Đã chấm công vào ca lúc $now!";
            } elseif ($type === 'out') {
                $this->db->prepare("UPDATE staff_schedules SET check_out = ? WHERE id = ?")
                         ->execute([$now, $id]);
                $_SESSION['flash_msg'] = "Đã ghi nhận tan ca lúc $now!";
            } elseif ($type === 'absent' && $isAdmin) {
                $this->db->prepare("UPDATE staff_schedules SET check_in = NULL, check_out = NULL, attendance_status = 'Absent' WHERE id = ?")
                         ->execute([$id]);
                $_SESSION['flash_msg'] = "Đã đánh dấu vắng mặt!";
            }
        }
        header("Location: index.php?c=menu&a=users&tab=shifts&date=$date");
        exit;
    }
}