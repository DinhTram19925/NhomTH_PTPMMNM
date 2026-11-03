
<?php

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
                t.status
            FROM areas a
            LEFT JOIN dining_tables t ON t.area_id = a.id
            ORDER BY a.id, t.id
        ";

        return $this->db->query($sql)->fetchAll();
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

        return $this->db->query($sql)->fetch();
    }
// Lấy danh sách khu vực và số bàn
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

// Thêm khu vực
public function addArea(string $name, float $surcharge): bool
{
    $stmt = $this->db->prepare(
        "INSERT INTO areas (name, surcharge_percent)
         VALUES (?, ?)"
    );

    return $stmt->execute([$name, $surcharge]);
}

// Sửa khu vực
public function updateArea(
    int $id,
    string $name,
    float $surcharge
): bool {
    $stmt = $this->db->prepare(
        "UPDATE areas
         SET name = ?, surcharge_percent = ?
         WHERE id = ?"
    );

    return $stmt->execute([$name, $surcharge, $id]);
}

// Xóa khu vực nếu chưa có bàn
public function deleteArea(int $id): bool
{
    $stmt = $this->db->prepare(
        "SELECT COUNT(*) FROM dining_tables WHERE area_id = ?"
    );

    $stmt->execute([$id]);

    if ((int)$stmt->fetchColumn() > 0) {
        throw new InvalidArgumentException(
            'Không thể xóa khu vực đang có bàn liên kết.'
        );
    }

    $stmt = $this->db->prepare(
        "DELETE FROM areas WHERE id = ?"
    );

    return $stmt->execute([$id]);
}

    public function addTable(
        int $areaId,
        string $name,
        int $capacity
    ): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO dining_tables (area_id, table_name, capacity, status)
             VALUES (?, ?, ?, 'Available')"
        );

        return $stmt->execute([$areaId, $name, $capacity]);
    }

    public function updateTable(
        int $id,
        int $areaId,
        string $name,
        int $capacity
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE dining_tables
             SET area_id = ?, table_name = ?, capacity = ?
             WHERE id = ?"
        );

        return $stmt->execute([$areaId, $name, $capacity, $id]);
    }

    public function updateStatus(int $id, string $status): bool
    {
        $allowedStatuses = [
            'Available', 'Occupied', 'Reserved', 'Cleaning'
        ];

        if (!in_array($status, $allowedStatuses, true)) {
            return false;
        }

        $stmt = $this->db->prepare(
            "UPDATE dining_tables SET status = ? WHERE id = ?"
        );

        return $stmt->execute([$status, $id]);
    }

    public function findTable(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM dining_tables WHERE id = ?"
        );

        $stmt->execute([$id]);
        $table = $stmt->fetch();

        return $table ?: null;
    }
}
