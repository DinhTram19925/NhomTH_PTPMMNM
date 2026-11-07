<?php
require_once __DIR__ . '/../config/Database.php';

class TableModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getAllTables(): array
    {
        $sql = "
            SELECT
                a.id AS area_id,
                a.name AS area_name,
                a.surcharge_percent,
                t.id AS table_id,
                t.table_name,
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
            ORDER BY a.id, t.id
        ";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTableStatistics(): array
    {
        $sql = "
            SELECT
                COUNT(*) AS total,
                COALESCE(SUM(status = 'Available'), 0) AS available,
                COALESCE(SUM(status = 'Occupied'), 0) AS occupied,
                COALESCE(SUM(status = 'Reserved'), 0) AS reserved,
                COALESCE(SUM(status = 'Cleaning'), 0) AS cleaning
            FROM dining_tables
        ";
        return $this->db->query($sql)->fetch(PDO::FETCH_ASSOC);
    }

    public function getAreas(): array
    {
        $sql = "
            SELECT
                a.id,
                a.name,
                a.surcharge_percent,
                COUNT(t.id) AS table_count
            FROM areas a
            LEFT JOIN dining_tables t ON t.area_id = a.id
            GROUP BY a.id, a.name, a.surcharge_percent
            ORDER BY a.id
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

    public function addArea(string $name, float $surcharge): bool
    {
        $name = trim($name);
        if ($name === '' || $surcharge < 0 || $surcharge > 100) {
            throw new InvalidArgumentException('Tên khu vực hoặc phụ thu không hợp lệ.');
        }
        $chk = $this->db->prepare("SELECT COUNT(*) FROM areas WHERE LOWER(name) = LOWER(?)");
        $chk->execute([$name]);
        if ((int)$chk->fetchColumn() > 0) {
            throw new InvalidArgumentException('Tên khu vực đã tồn tại.');
        }
        $stmt = $this->db->prepare("INSERT INTO areas (name, surcharge_percent) VALUES (?, ?)");
        return $stmt->execute([$name, $surcharge]);
    }

    public function updateArea(int $id, string $name, float $surcharge): bool
    {
        $name = trim($name);
        if (!$this->findArea($id)) {
            throw new InvalidArgumentException('Khu vực không tồn tại.');
        }
        $chk = $this->db->prepare("SELECT COUNT(*) FROM areas WHERE LOWER(name) = LOWER(?) AND id <> ?");
        $chk->execute([$name, $id]);
        if ((int)$chk->fetchColumn() > 0) {
            throw new InvalidArgumentException('Tên khu vực đã tồn tại.');
        }
        $stmt = $this->db->prepare("UPDATE areas SET name = ?, surcharge_percent = ? WHERE id = ?");
        return $stmt->execute([$name, $surcharge, $id]);
    }

    public function deleteArea(int $id): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM dining_tables WHERE area_id = ?");
        $stmt->execute([$id]);
        if ((int)$stmt->fetchColumn() > 0) {
            throw new InvalidArgumentException('Không thể xóa khu vực đang có bàn liên kết.');
        }
        $stmt = $this->db->prepare("DELETE FROM areas WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function addTable(int $areaId, string $name, int $capacity): bool
    {
        $name = trim($name);
        if (!$this->findArea($areaId)) {
            throw new InvalidArgumentException('Khu vực đã chọn không tồn tại.');
        }
        if ($name === '' || $capacity < 1 || $capacity > 100) {
            throw new InvalidArgumentException('Tên bàn hoặc sức chứa không hợp lệ.');
        }
        $dup = $this->db->prepare("SELECT COUNT(*) FROM dining_tables WHERE LOWER(table_name) = LOWER(?)");
        $dup->execute([$name]);
        if ((int)$dup->fetchColumn() > 0) {
            throw new InvalidArgumentException("Tên bàn '$name' đã tồn tại trong hệ thống.");
        }

        $qrToken = 'QR_' . strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $name)) . '_' . random_int(1000, 9999);
        $stmt = $this->db->prepare("
            INSERT INTO dining_tables (area_id, table_name, capacity, qr_token, status)
            VALUES (?, ?, ?, ?, 'Available')
        ");
        return $stmt->execute([$areaId, $name, $capacity, $qrToken]);
    }

    public function updateTable(int $id, int $areaId, string $name, int $capacity): bool
    {
        $name = trim($name);
        if (!$this->findTable($id)) {
            throw new InvalidArgumentException('Bàn cần sửa không tồn tại.');
        }
        if (!$this->findArea($areaId)) {
            throw new InvalidArgumentException('Khu vực đã chọn không tồn tại.');
        }
        $dup = $this->db->prepare("SELECT COUNT(*) FROM dining_tables WHERE LOWER(table_name) = LOWER(?) AND id <> ?");
        $dup->execute([$name, $id]);
        if ((int)$dup->fetchColumn() > 0) {
            throw new InvalidArgumentException("Tên bàn '$name' đã được sử dụng bởi bàn khác.");
        }

        $stmt = $this->db->prepare("
            UPDATE dining_tables
            SET area_id = ?, table_name = ?, capacity = ?
            WHERE id = ?
        ");
        return $stmt->execute([$areaId, $name, $capacity, $id]);
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
        $stmt = $this->db->prepare("SELECT * FROM dining_tables WHERE id = ?");
        $stmt->execute([$id]);
        $table = $stmt->fetch(PDO::FETCH_ASSOC);
        return $table ?: null;
    }
}