<?php
require_once __DIR__ . '/../config/Database.php';

class TableModel
{
    private PDO $db;

    public const DEPOSIT_VIP     = 5000000;  // 5.000.000đ cho Phòng VIP
    public const DEPOSIT_PREMIUM = 10000000; // 10.000.000đ cho Phòng Thượng Hạng / President
    public const ALLOWED_CAPACITIES = [4, 8, 12];
    public const ALLOWED_TYPES = ['Standard', 'VIP', 'Premium'];

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->ensureSchemaAndEncoding();
    }

    private function ensureSchemaAndEncoding(): void
    {
        try {
            $colFloor = $this->db->query("SHOW COLUMNS FROM areas LIKE 'floor_number'")->fetch();
            if (!$colFloor) {
                $this->db->exec("ALTER TABLE areas ADD COLUMN floor_number INT NOT NULL DEFAULT 1 AFTER id");
            }

            $colType = $this->db->query("SHOW COLUMNS FROM dining_tables LIKE 'table_type'")->fetch();
            if (!$colType) {
                $this->db->exec("ALTER TABLE dining_tables ADD COLUMN table_type ENUM('Standard', 'VIP', 'Premium') NOT NULL DEFAULT 'Standard' AFTER table_name");
            }

            // Sửa lỗi font chữ tiếng Việt trong bảng areas nếu có
            $corrupted = $this->db->query("SELECT id, name FROM areas WHERE name LIKE '%?%'")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($corrupted as $row) {
                $id = (int)$row['id'];
                if ($id === 1 || stripos($row['name'], 'Giao') !== false) {
                    $this->db->prepare("UPDATE areas SET name = 'Sảnh Giao Hưởng', floor_number = 1 WHERE id = ?")->execute([$id]);
                } elseif ($id === 2 || stripos($row['name'], 'VIP') !== false) {
                    $this->db->prepare("UPDATE areas SET name = 'Khu Phòng VIP Hầm Rượu', floor_number = 1 WHERE id = ?")->execute([$id]);
                } elseif (stripos($row['name'], 'Presidential') !== false || stripos($row['name'], 'Th') !== false) {
                    $this->db->prepare("UPDATE areas SET name = 'Khu President Thượng Hạng', floor_number = 1 WHERE id = ?")->execute([$id]);
                } else {
                    $clean = trim(str_replace('?', '', $row['name']));
                    $this->db->prepare("UPDATE areas SET name = ? WHERE id = ?")->execute(['Khu vực ' . $id . ' ' . $clean, $id]);
                }
            }

            $this->db->exec("UPDATE dining_tables SET table_type = 'VIP' WHERE (table_name LIKE 'VIP%' OR table_name LIKE '%VIP%') AND table_type = 'Standard'");
            $this->db->exec("UPDATE dining_tables SET table_type = 'Premium' WHERE (table_name LIKE 'PRES%' OR table_name LIKE 'SUP%' OR table_name LIKE '%PRESIDENT%') AND table_type = 'Standard'");
            $this->db->exec("UPDATE dining_tables SET capacity = 8 WHERE capacity NOT IN (4, 8, 12) AND capacity BETWEEN 5 AND 10");
            $this->db->exec("UPDATE dining_tables SET capacity = 12 WHERE capacity NOT IN (4, 8, 12) AND capacity > 10");
            $this->db->exec("UPDATE dining_tables SET capacity = 4 WHERE capacity NOT IN (4, 8, 12)");
        } catch (Throwable $e) {
            error_log('[TableModel::ensureSchemaAndEncoding] ' . $e->getMessage());
        }
    }

    public static function calculateDepositByTable(array $table, int $guestCount = 4): float
    {
        $type = $table['table_type'] ?? 'Standard';
        if ($type === 'Premium') {
            return (float)self::DEPOSIT_PREMIUM;
        }
        if ($type === 'VIP') {
            return (float)self::DEPOSIT_VIP;
        }
        return match (true) {
            $guestCount <= 4  => 50000.0,
            $guestCount <= 8  => 100000.0,
            $guestCount <= 12 => 150000.0,
            default           => 200000.0
        };
    }

    /**
     * Lấy toàn bộ danh sách bàn kèm trạng thái thực tế theo NGÀY ĐƯỢC CHỌN ($selectedDate)
     */
    public function getAllTables(?int $floor = null, ?int $areaId = null, ?string $selectedDate = null): array
    {
        $selectedDate = ($selectedDate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate))
            ? $selectedDate
            : date('Y-m-d');
        $isToday = ($selectedDate === date('Y-m-d'));

        $whereParts = [];
        $params = [];

        if ($floor !== null && $floor > 0) {
            $whereParts[] = 'a.floor_number = ?';
            $params[] = $floor;
        }
        if ($areaId !== null && $areaId > 0) {
            $whereParts[] = 'a.id = ?';
            $params[] = $areaId;
        }
        $whereSql = $whereParts ? ('WHERE ' . implode(' AND ', $whereParts)) : '';

        $sql = "
            SELECT
                a.id AS area_id,
                a.floor_number,
                a.name AS area_name,
                a.surcharge_percent,
                t.id AS table_id,
                t.table_name,
                t.table_type,
                t.capacity,
                t.status,
                o.id AS active_order_id,
                o.order_code AS active_order_code,
                o.guest_count AS active_guests,
                o.subtotal AS active_subtotal,
                o.opened_at AS active_opened_at,
                u.full_name AS waiter_name
            FROM areas a
            LEFT JOIN dining_tables t ON t.area_id = a.id
            LEFT JOIN orders o ON o.table_id = t.id AND o.status = 'Open'
            LEFT JOIN users u ON o.waiter_id = u.id
            $whereSql
            ORDER BY a.floor_number ASC, a.id ASC, FIELD(t.table_type, 'Standard', 'VIP', 'Premium'), t.table_name ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Lấy các lịch đặt bàn trong ngày $selectedDate
        $stmtRes = $this->db->prepare("
            SELECT
                r.*,
                c.full_name AS customer_name,
                c.phone AS customer_phone
            FROM reservations r
            INNER JOIN customers c ON c.id = r.customer_id
            WHERE DATE(r.booking_time) = ?
              AND r.status IN ('Pending', 'Confirmed', 'CheckedIn')
            ORDER BY FIELD(r.status, 'CheckedIn', 'Confirmed', 'Pending'), r.booking_time ASC
        ");
        $stmtRes->execute([$selectedDate]);
        $reservationsByTable = [];
        foreach ($stmtRes->fetchAll(PDO::FETCH_ASSOC) as $resRow) {
            $tid = (int)($resRow['table_id'] ?? 0);
            if ($tid > 0 && !isset($reservationsByTable[$tid])) {
                $reservationsByTable[$tid] = $resRow;
            }
        }

        // Gắn trạng thái hiển thị theo ngày (effective_status: Available | Reserved | Occupied)
        foreach ($rows as &$r) {
            $tid = (int)($r['table_id'] ?? 0);
            if ($tid <= 0) {
                continue;
            }
            $dayBooking = $reservationsByTable[$tid] ?? null;
            $r['day_booking'] = $dayBooking;

            if ($isToday && ($r['status'] === 'Occupied' || !empty($r['active_order_id']) || ($dayBooking && $dayBooking['status'] === 'CheckedIn'))) {
                $r['effective_status'] = 'Occupied'; // Đang dùng
            } elseif ($dayBooking !== null || ($isToday && $r['status'] === 'Reserved')) {
                $r['effective_status'] = 'Reserved'; // Đã đặt trong ngày này
            } else {
                $r['effective_status'] = 'Available'; // Trống trong ngày này
            }
        }
        unset($r);

        return $rows;
    }

    public function getFloorsSummary(): array
    {
        $sql = "
            SELECT
                a.floor_number,
                COUNT(DISTINCT a.id) AS area_count,
                COUNT(t.id) AS total_units,
                COALESCE(SUM(t.table_type = 'Standard'), 0) AS standard_tables,
                COALESCE(SUM(t.table_type = 'VIP'), 0) AS vip_rooms,
                COALESCE(SUM(t.table_type = 'Premium'), 0) AS premium_rooms
            FROM areas a
            LEFT JOIN dining_tables t ON t.area_id = a.id
            GROUP BY a.floor_number
            ORDER BY a.floor_number ASC
        ";
        $rows = $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        if (empty($rows)) {
            return [[
                'floor_number' => 1,
                'area_count' => 0,
                'total_units' => 0,
                'standard_tables' => 0,
                'vip_rooms' => 0,
                'premium_rooms' => 0
            ]];
        }
        return $rows;
    }

    public function syncTotalFloors(int $targetFloors): void
    {
        if ($targetFloors < 1 || $targetFloors > 20) {
            throw new InvalidArgumentException('Số lượng tầng phải từ 1 đến 20.');
        }

        for ($f = 1; $f <= $targetFloors; $f++) {
            $chk = $this->db->prepare("SELECT COUNT(*) FROM areas WHERE floor_number = ?");
            $chk->execute([$f]);
            if ((int)$chk->fetchColumn() === 0) {
                $defaultName = "Tầng $f - Sảnh Chung";
                $ins = $this->db->prepare("INSERT INTO areas (floor_number, name, surcharge_percent, description) VALUES (?, ?, 0, ?)");
                $ins->execute([$f, $defaultName, "Không gian phục vụ tại Tầng $f"]);
            }
        }

        $stmtCheck = $this->db->prepare("
            SELECT COUNT(t.id)
            FROM areas a
            INNER JOIN dining_tables t ON t.area_id = a.id
            WHERE a.floor_number > ?
        ");
        $stmtCheck->execute([$targetFloors]);
        if ((int)$stmtCheck->fetchColumn() > 0) {
            throw new InvalidArgumentException("Không thể giảm xuống $targetFloors tầng vì các tầng cao hơn đang có bàn/phòng hoạt động.");
        }

        $del = $this->db->prepare("DELETE FROM areas WHERE floor_number > ?");
        $del->execute([$targetFloors]);
    }

    public function getTableStatistics(?int $floor = null): array
    {
        $where = '';
        $params = [];
        if ($floor !== null && $floor > 0) {
            $where = 'WHERE a.floor_number = ?';
            $params[] = $floor;
        }

        $sql = "
            SELECT
                COUNT(t.id) AS total,
                COALESCE(SUM(t.status = 'Available'), 0) AS available,
                COALESCE(SUM(t.status = 'Occupied'), 0) AS occupied,
                COALESCE(SUM(t.status = 'Reserved'), 0) AS reserved,
                COALESCE(SUM(t.status = 'Cleaning'), 0) AS cleaning,
                COALESCE(SUM(t.table_type = 'Standard'), 0) AS standard_count,
                COALESCE(SUM(t.table_type = 'VIP'), 0) AS vip_count,
                COALESCE(SUM(t.table_type = 'Premium'), 0) AS premium_count
            FROM dining_tables t
            INNER JOIN areas a ON a.id = t.area_id
            $where
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
            'total' => 0, 'available' => 0, 'occupied' => 0, 'reserved' => 0, 'cleaning' => 0,
            'standard_count' => 0, 'vip_count' => 0, 'premium_count' => 0
        ];
    }

    public function getAreas(): array
    {
        $sql = "
            SELECT
                a.id,
                a.floor_number,
                a.name,
                a.surcharge_percent,
                COUNT(t.id) AS table_count,
                COALESCE(SUM(t.table_type = 'Standard'), 0) AS standard_count,
                COALESCE(SUM(t.table_type = 'VIP'), 0) AS vip_count,
                COALESCE(SUM(t.table_type = 'Premium'), 0) AS premium_count
            FROM areas a
            LEFT JOIN dining_tables t ON t.area_id = a.id
            GROUP BY a.id, a.floor_number, a.name, a.surcharge_percent
            ORDER BY a.floor_number ASC, a.id ASC
        ";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findArea(int $areaId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM areas WHERE id = ?");
        $stmt->execute([$areaId]);
        $area = $stmt->fetch(PDO::FETCH_ASSOC);
        return $area ?: null;
    }

    public function addArea(string $name, float $surcharge, int $floorNumber = 1): bool
    {
        $name = trim($name);
        if ($name === '' || $surcharge < 0 || $surcharge > 100 || $floorNumber < 1 || $floorNumber > 20) {
            throw new InvalidArgumentException('Tên khu vực, số tầng hoặc phụ thu không hợp lệ.');
        }
        $chk = $this->db->prepare("SELECT COUNT(*) FROM areas WHERE LOWER(name) = LOWER(?)");
        $chk->execute([$name]);
        if ((int)$chk->fetchColumn() > 0) {
            throw new InvalidArgumentException('Tên khu vực đã tồn tại.');
        }
        $stmt = $this->db->prepare("INSERT INTO areas (floor_number, name, surcharge_percent) VALUES (?, ?, ?)");
        return $stmt->execute([$floorNumber, $name, $surcharge]);
    }

    public function updateArea(int $id, string $name, float $surcharge, int $floorNumber = 1): bool
    {
        $name = trim($name);
        if (!$this->findArea($id)) {
            throw new InvalidArgumentException('Khu vực không tồn tại.');
        }
        if ($name === '' || $surcharge < 0 || $surcharge > 100 || $floorNumber < 1 || $floorNumber > 20) {
            throw new InvalidArgumentException('Thông tin khu vực hoặc tầng không hợp lệ.');
        }
        $chk = $this->db->prepare("SELECT COUNT(*) FROM areas WHERE LOWER(name) = LOWER(?) AND id <> ?");
        $chk->execute([$name, $id]);
        if ((int)$chk->fetchColumn() > 0) {
            throw new InvalidArgumentException('Tên khu vực đã tồn tại.');
        }
        $stmt = $this->db->prepare("UPDATE areas SET floor_number = ?, name = ?, surcharge_percent = ? WHERE id = ?");
        return $stmt->execute([$floorNumber, $name, $surcharge, $id]);
    }

    public function deleteArea(int $id): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM dining_tables WHERE area_id = ?");
        $stmt->execute([$id]);
        if ((int)$stmt->fetchColumn() > 0) {
            throw new InvalidArgumentException('Không thể xóa khu vực đang có bàn hoặc phòng liên kết.');
        }
        $stmt = $this->db->prepare("DELETE FROM areas WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function addTable(int $areaId, string $name, int $capacity, string $tableType = 'Standard'): bool
    {
        $name = trim($name);
        if (!$this->findArea($areaId)) {
            throw new InvalidArgumentException('Khu vực đã chọn không tồn tại.');
        }
        if (!in_array($tableType, self::ALLOWED_TYPES, true)) {
            throw new InvalidArgumentException('Loại bàn/phòng không hợp lệ.');
        }
        if ($name === '' || !in_array($capacity, self::ALLOWED_CAPACITIES, true)) {
            throw new InvalidArgumentException('Tên bàn không được trống và sức chứa phải thuộc 4, 8 hoặc 12 ghế.');
        }
        $dup = $this->db->prepare("SELECT COUNT(*) FROM dining_tables WHERE LOWER(table_name) = LOWER(?)");
        $dup->execute([$name]);
        if ((int)$dup->fetchColumn() > 0) {
            throw new InvalidArgumentException("Mã bàn/phòng '$name' đã tồn tại.");
        }

        $qrToken = 'QR_' . strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $name)) . '_' . random_int(1000, 9999);
        $stmt = $this->db->prepare("
            INSERT INTO dining_tables (area_id, table_name, table_type, capacity, qr_token, status)
            VALUES (?, ?, ?, ?, ?, 'Available')
        ");
        return $stmt->execute([$areaId, $name, $tableType, $capacity, $qrToken]);
    }

    public function updateTable(int $id, int $areaId, string $name, int $capacity, string $tableType = 'Standard'): bool
    {
        $name = trim($name);
        if (!$this->findTable($id)) {
            throw new InvalidArgumentException('Bàn/Phòng cần sửa không tồn tại.');
        }
        if (!$this->findArea($areaId)) {
            throw new InvalidArgumentException('Khu vực đã chọn không tồn tại.');
        }
        if (!in_array($tableType, self::ALLOWED_TYPES, true)) {
            throw new InvalidArgumentException('Loại bàn/phòng không hợp lệ.');
        }
        if ($name === '' || !in_array($capacity, self::ALLOWED_CAPACITIES, true)) {
            throw new InvalidArgumentException('Sức chứa bắt buộc là 4, 8 hoặc 12 ghế.');
        }
        $dup = $this->db->prepare("SELECT COUNT(*) FROM dining_tables WHERE LOWER(table_name) = LOWER(?) AND id <> ?");
        $dup->execute([$name, $id]);
        if ((int)$dup->fetchColumn() > 0) {
            throw new InvalidArgumentException("Mã bàn/phòng '$name' đã được sử dụng.");
        }

        $stmt = $this->db->prepare("
            UPDATE dining_tables
            SET area_id = ?, table_name = ?, table_type = ?, capacity = ?
            WHERE id = ?
        ");
        return $stmt->execute([$areaId, $name, $tableType, $capacity, $id]);
    }

    public function deleteTable(int $id): bool
    {
        $table = $this->findTable($id);
        if (!$table) {
            throw new InvalidArgumentException('Bàn/Phòng không tồn tại.');
        }
        $openOrd = $this->db->prepare("SELECT COUNT(*) FROM orders WHERE table_id = ? AND status = 'Open'");
        $openOrd->execute([$id]);
        if ((int)$openOrd->fetchColumn() > 0) {
            throw new InvalidArgumentException('Không thể xóa bàn/phòng đang có hóa đơn POS mở.');
        }
        try {
            $stmt = $this->db->prepare("DELETE FROM dining_tables WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            throw new InvalidArgumentException('Bàn/Phòng này đã có lịch sử đặt bàn hoặc hóa đơn, không thể xóa vĩnh viễn.');
        }
    }

    public function updateStatus(int $id, string $status): bool
    {
        $allowedStatuses = ['Available', 'Occupied', 'Reserved', 'Cleaning'];
        if (!in_array($status, $allowedStatuses, true)) {
            return false;
        }
        if ($status !== 'Occupied') {
            $openOrder = $this->db->prepare("SELECT COUNT(*) FROM orders WHERE table_id = ? AND status = 'Open'");
            $openOrder->execute([$id]);
            if ((int)$openOrder->fetchColumn() > 0) {
                throw new InvalidArgumentException('Bàn đang có hóa đơn POS chưa thanh toán, không thể đổi trạng thái thủ công.');
            }
        }
        $stmt = $this->db->prepare("UPDATE dining_tables SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

    public function findTable(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT t.*, a.name AS area_name, a.floor_number, a.surcharge_percent
            FROM dining_tables t
            LEFT JOIN areas a ON a.id = t.area_id
            WHERE t.id = ?
        ");
        $stmt->execute([$id]);
        $table = $stmt->fetch(PDO::FETCH_ASSOC);
        return $table ?: null;
    }
}