
<?php

class ReservationModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    // Lấy danh sách đặt bàn
    public function getAllReservations(): array
    {
        $sql = "
            SELECT
                r.*,
                c.full_name,
                c.phone,
                c.tier,
                t.table_name,
                a.name AS area_name
            FROM reservations r
            INNER JOIN customers c ON c.id = r.customer_id
            LEFT JOIN dining_tables t ON t.id = r.table_id
            LEFT JOIN areas a ON a.id = t.area_id
            ORDER BY r.booking_time DESC
        ";

        return $this->db->query($sql)->fetchAll();
        
    }

    // Thong ke dat ban theo trang thai
    public function getBookingStatistics(): array
    {
        $sql = "
            SELECT
                COUNT(*) AS total_bookings,
                SUM(status = 'Pending') AS pending,
                SUM(status = 'Confirmed') AS confirmed,
                SUM(status = 'CheckedIn') AS checked_in,
                SUM(status = 'Cancelled') AS cancelled,
                COALESCE(SUM(
                    CASE WHEN status = 'CheckedIn'
                    THEN deposit_amount ELSE 0 END
                ), 0) AS checked_in_deposit,
                COALESCE(SUM(
                    CASE WHEN status = 'Cancelled'
                    THEN deposit_amount ELSE 0 END
                ), 0) AS cancelled_deposit
            FROM reservations
        ";

        return $this->db->query($sql)->fetch(PDO::FETCH_ASSOC);
    }

    // Top 10 khach hang chi tieu cao nhat
    public function getTopVipCustomers(): array
    {
        $sql = "
            SELECT
                id,
                full_name,
                phone,
                tier,
                points,
                total_spent
            FROM customers
            ORDER BY total_spent DESC
            LIMIT 10
        ";

        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lấy danh sách bàn
    public function getAvailableTables(): array
    {
        $sql = "
            SELECT
                t.id,
                t.table_name,
                t.capacity,
                t.status,
                a.name AS area_name
            FROM dining_tables t
            INNER JOIN areas a ON a.id = t.area_id
            WHERE t.status = 'Available'
            ORDER BY a.id, t.table_name
        ";

        return $this->db->query($sql)->fetchAll();
    }

    // Tìm hoặc tạo khách hàng theo số điện thoại
    public function findOrCreateCustomer(
        string $name,
        string $phone,
        string $email = ''
    ): int {
        $stmt = $this->db->prepare(
            "SELECT id FROM customers WHERE phone = ?"
        );

        $stmt->execute([$phone]);
        $customer = $stmt->fetch();

        if ($customer) {
            $update = $this->db->prepare(
                "UPDATE customers
                 SET full_name = ?, email = ?
                 WHERE id = ?"
            );

            $update->execute([
                $name,
                $email !== '' ? $email : null,
                $customer['id']
            ]);

            return (int)$customer['id'];
        }

        $insert = $this->db->prepare(
            "INSERT INTO customers (full_name, phone, email)
             VALUES (?, ?, ?)"
        );

        $insert->execute([
            $name,
            $phone,
            $email !== '' ? $email : null
        ]);

        return (int)$this->db->lastInsertId();
    }

    // Kiểm tra sức chứa bàn
    public function findTable(int $tableId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM dining_tables WHERE id = ?"
        );

        $stmt->execute([$tableId]);
        $table = $stmt->fetch();

        return $table ?: null;
    }

    // Kiểm tra đặt bàn trùng
    // Tạm quy ước mỗi lượt đặt bàn kéo dài 2 tiếng.
    public function isTableAvailable(
        int $tableId,
        string $bookingTime
    ): bool {
        $sql = "
            SELECT COUNT(*)
            FROM reservations
            WHERE table_id = ?
              AND status IN ('Pending', 'Confirmed', 'CheckedIn')
              AND booking_time < DATE_ADD(?, INTERVAL 120 MINUTE)
              AND DATE_ADD(booking_time, INTERVAL 120 MINUTE) > ?
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            $tableId,
            $bookingTime,
            $bookingTime
        ]);

        return (int)$stmt->fetchColumn() === 0;
    }

    // Tạo đặt bàn
    public function createReservation(array $data): string
    {
        $this->db->beginTransaction();

        try {
            $customerId = $this->findOrCreateCustomer(
                $data['full_name'],
                $data['phone'],
                $data['email']
            );

            $bookingCode = 'BK' . date('YmdHis')
                . random_int(100, 999);

            $stmt = $this->db->prepare(
                "INSERT INTO reservations (
                    booking_code,
                    customer_id,
                    table_id,
                    booking_time,
                    guest_count,
                    deposit_amount,
                    special_request,
                    status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')"
            );

            $stmt->execute([
                $bookingCode,
                $customerId,
                $data['table_id'],
                $data['booking_time'],
                $data['guest_count'],
                $data['deposit_amount'],
                $data['special_request']
            ]);

            $this->db->commit();

            return $bookingCode;

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    // Cập nhật trạng thái đặt bàn
    

// Lấy ID các bàn có lịch đặt trong tương lai
public function getUpcomingReservedTableIds(): array
{
    $sql = "
        SELECT DISTINCT table_id
        FROM reservations
        WHERE table_id IS NOT NULL
          AND status IN ('Pending', 'Confirmed')
          AND booking_time > ?
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->execute([date('Y-m-d H:i:s')]);

    return array_map(
        'intval',
        $stmt->fetchAll(PDO::FETCH_COLUMN)
    );
}
public function updateReservationStatus(
    int $id,
    string $status
): bool {
    $this->db->beginTransaction();

    try {
        $allowed = [
            'Confirmed',
            'CheckedIn',
            'Cancelled'
        ];

        if (!in_array($status, $allowed, true)) {
            $this->db->rollBack();
            return false;
        }

        // Khóa và lấy thông tin đặt bàn
        $stmt = $this->db->prepare(
            "SELECT id, table_id, booking_time, status
             FROM reservations
             WHERE id = ?
             FOR UPDATE"
        );

        $stmt->execute([$id]);
        $reservation = $stmt->fetch();

        if (!$reservation) {
            $this->db->rollBack();
            return false;
        }

        // Kiểm soát luồng trạng thái
        $transitions = [
            'Pending' => ['Confirmed', 'Cancelled'],
            'Confirmed' => ['CheckedIn', 'Cancelled']
        ];

        $current = $reservation['status'];

        if (
            !isset($transitions[$current]) ||
            !in_array($status, $transitions[$current], true)
        ) {
            $this->db->rollBack();
            return false;
        }

        // Không cho check-in trước giờ đặt bàn
        if ($status === 'CheckedIn') {
            $bookingTime = new DateTimeImmutable(
                $reservation['booking_time']
            );

            $now = new DateTimeImmutable();

            if ($now < $bookingTime) {
                $this->db->rollBack();
                return false;
            }

            // Booking phải có bàn
            if (empty($reservation['table_id'])) {
                $this->db->rollBack();
                return false;
            }

            // Khóa bàn để kiểm tra trạng thái
            $tableStmt = $this->db->prepare(
                "SELECT status
                 FROM dining_tables
                 WHERE id = ?
                 FOR UPDATE"
            );

            $tableStmt->execute([$reservation['table_id']]);
            $table = $tableStmt->fetch();

            if (
                !$table ||
                !in_array(
                    $table['status'],
                    ['Available', 'Reserved'],
                    true
                )
            ) {
                $this->db->rollBack();
                return false;
            }

            // Chuyển bàn thành đang phục vụ
            $updateTable = $this->db->prepare(
                "UPDATE dining_tables
                 SET status = 'Occupied'
                 WHERE id = ?"
            );

            $updateTable->execute([$reservation['table_id']]);
        }

        // Cập nhật trạng thái đặt bàn
        $update = $this->db->prepare(
            "UPDATE reservations
             SET status = ?
             WHERE id = ?"
        );

        $update->execute([$status, $id]);

        $this->db->commit();
        return true;

    } catch (Throwable $e) {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }

        throw $e;
    }
}

}
