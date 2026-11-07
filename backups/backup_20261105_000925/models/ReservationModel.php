
<?php

class ReservationModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    // Láº¥y danh sÃ¡ch Ä‘áº·t bÃ n
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

    // Láº¥y danh sÃ¡ch bÃ n
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
// Láº¥y danh sÃ¡ch bÃ n cÃ³ thá»ƒ Ä‘áº·t theo thá»i gian vÃ  sá»‘ khÃ¡ch
public function getAvailableTablesForBooking(
    string $bookingTime,
    int $guestCount
): array {
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
          AND t.capacity >= ?
          AND NOT EXISTS (
              SELECT 1
              FROM reservations r
              WHERE r.table_id = t.id
                AND r.status IN ('Pending', 'Confirmed', 'CheckedIn')
                AND r.booking_time < DATE_ADD(?, INTERVAL 120 MINUTE)
                AND DATE_ADD(r.booking_time, INTERVAL 120 MINUTE) > ?
          )
        ORDER BY a.id, t.table_name
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->execute([
        $guestCount,
        $bookingTime,
        $bookingTime
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
    // TÃ¬m hoáº·c táº¡o khÃ¡ch hÃ ng theo sá»‘ Ä‘iá»‡n thoáº¡i
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

    // Kiá»ƒm tra sá»©c chá»©a bÃ n
    public function findTable(int $tableId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM dining_tables WHERE id = ?"
        );

        $stmt->execute([$tableId]);
        $table = $stmt->fetch();

        return $table ?: null;
    }

    // Kiá»ƒm tra Ä‘áº·t bÃ n trÃ¹ng
    // Táº¡m quy Æ°á»›c má»—i lÆ°á»£t Ä‘áº·t bÃ n kÃ©o dÃ i 2 tiáº¿ng.
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

    // Táº¡o Ä‘áº·t bÃ n
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

    // Cáº­p nháº­t tráº¡ng thÃ¡i Ä‘áº·t bÃ n
    
// Táº¡o Ä‘áº·t bÃ n dÃ nh riÃªng cho khÃ¡ch hÃ ng
public function createCustomerReservation(array $data): string
{
    $this->db->beginTransaction();

    try {
        // KhÃ³a bÃ n Ä‘á»ƒ trÃ¡nh hai khÃ¡ch Ä‘áº·t cÃ¹ng má»™t bÃ n Ä‘á»“ng thá»i
        $stmt = $this->db->prepare("
            SELECT id, capacity, status
            FROM dining_tables
            WHERE id = ?
            FOR UPDATE
        ");

        $stmt->execute([$data['table_id']]);
        $table = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$table || $table['status'] !== 'Available') {
            throw new Exception('BÃ n khÃ´ng cÃ²n kháº£ dá»¥ng.');
        }

        if ((int)$table['capacity'] < (int)$data['guest_count']) {
            throw new Exception('BÃ n khÃ´ng Ä‘á»§ sá»©c chá»©a.');
        }

        // Kiá»ƒm tra lá»‹ch Ä‘áº·t trÃ¹ng trong vÃ²ng 120 phÃºt
        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM reservations
            WHERE table_id = ?
              AND status IN ('Pending', 'Confirmed', 'CheckedIn')
              AND booking_time < DATE_ADD(?, INTERVAL 120 MINUTE)
              AND DATE_ADD(booking_time, INTERVAL 120 MINUTE) > ?
        ");

        $stmt->execute([
            $data['table_id'],
            $data['booking_time'],
            $data['booking_time']
        ]);

        if ((int)$stmt->fetchColumn() > 0) {
            throw new Exception('BÃ n Ä‘Ã£ cÃ³ ngÆ°á»i Ä‘áº·t trong thá»i gian nÃ y.');
        }

        // TÃ¬m hoáº·c táº¡o khÃ¡ch hÃ ng
        $customerId = $this->findOrCreateCustomer(
            $data['full_name'],
            $data['phone'],
            $data['email'] ?? ''
        );

        // Táº¡o mÃ£ Ä‘áº·t bÃ n
        $bookingCode = 'BK' . date('YmdHis')
            . random_int(100, 999);

        // LÆ°u yÃªu cáº§u Ä‘áº·t bÃ n vá»›i tráº¡ng thÃ¡i Pending
        $stmt = $this->db->prepare("
            INSERT INTO reservations (
                booking_code,
                customer_id,
                table_id,
                booking_time,
                guest_count,
                deposit_amount,
                special_request,
                status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')
        ");

        $stmt->execute([
            $bookingCode,
            $customerId,
            $data['table_id'],
            $data['booking_time'],
            $data['guest_count'],
            $data['deposit_amount'] ?? 0,
            $data['special_request'] ?? ''
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
// Láº¥y ID cÃ¡c bÃ n cÃ³ lá»‹ch Ä‘áº·t trong tÆ°Æ¡ng lai
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

        // KhÃ³a vÃ  láº¥y thÃ´ng tin Ä‘áº·t bÃ n
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

        // Kiá»ƒm soÃ¡t luá»“ng tráº¡ng thÃ¡i
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

        // KhÃ´ng cho check-in trÆ°á»›c giá» Ä‘áº·t bÃ n
        if ($status === 'CheckedIn') {
            $bookingTime = new DateTimeImmutable(
                $reservation['booking_time']
            );

            $now = new DateTimeImmutable();

            // Cho phép Check-in sớm khi khách đến trước giờ hẹn

            // Booking pháº£i cÃ³ bÃ n
            if (empty($reservation['table_id'])) {
                $this->db->rollBack();
                return false;
            }

            // KhÃ³a bÃ n Ä‘á»ƒ kiá»ƒm tra tráº¡ng thÃ¡i
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

            // Chuyá»ƒn bÃ n thÃ nh Ä‘ang phá»¥c vá»¥
            $updateTable = $this->db->prepare(
                "UPDATE dining_tables
                 SET status = 'Occupied'
                 WHERE id = ?"
            );

            $updateTable->execute([$reservation['table_id']]);
        }

        // Cáº­p nháº­t tráº¡ng thÃ¡i Ä‘áº·t bÃ n
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

