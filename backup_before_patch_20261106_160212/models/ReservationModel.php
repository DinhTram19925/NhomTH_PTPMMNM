<?php
require_once __DIR__ . '/../config/Database.php';

class ReservationModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

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
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getBookingStatistics(): array
    {
        $sql = "
            SELECT
                COUNT(*) AS total_bookings,
                COALESCE(SUM(status = 'Pending'), 0) AS pending,
                COALESCE(SUM(status = 'Confirmed'), 0) AS confirmed,
                COALESCE(SUM(status = 'CheckedIn'), 0) AS checked_in,
                COALESCE(SUM(status = 'Cancelled'), 0) AS cancelled,
                COALESCE(SUM(CASE WHEN status = 'CheckedIn' THEN deposit_amount ELSE 0 END), 0) AS checked_in_deposit,
                COALESCE(SUM(CASE WHEN status = 'Cancelled' THEN deposit_amount ELSE 0 END), 0) AS cancelled_deposit
            FROM reservations
        ";
        return $this->db->query($sql)->fetch(PDO::FETCH_ASSOC);
    }

    public function getTopVipCustomers(): array
    {
        $sql = "
            SELECT id, full_name, phone, tier, points, total_spent
            FROM customers
            ORDER BY total_spent DESC
            LIMIT 10
        ";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

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
            WHERE t.status IN ('Available', 'Reserved')
            ORDER BY a.id, t.table_name
        ";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAvailableTablesForBooking(string $bookingTime, int $guestCount): array
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
            WHERE t.status != 'Occupied'
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
        $stmt->execute([$guestCount, $bookingTime, $bookingTime]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findOrCreateCustomer(string $name, string $phone, string $email = ''): int
    {
        $stmt = $this->db->prepare("SELECT id FROM customers WHERE phone = ?");
        $stmt->execute([$phone]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($customer) {
            $update = $this->db->prepare("
                UPDATE customers
                SET full_name = ?, email = COALESCE(?, email)
                WHERE id = ?
            ");
            $update->execute([$name, $email !== '' ? $email : null, $customer['id']]);
            return (int)$customer['id'];
        }

        $insert = $this->db->prepare("
            INSERT INTO customers (full_name, phone, email)
            VALUES (?, ?, ?)
        ");
        $insert->execute([$name, $phone, $email !== '' ? $email : null]);
        return (int)$this->db->lastInsertId();
    }

    public function findTable(int $tableId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM dining_tables WHERE id = ?");
        $stmt->execute([$tableId]);
        $table = $stmt->fetch(PDO::FETCH_ASSOC);
        return $table ?: null;
    }

    public function isTableAvailable(int $tableId, string $bookingTime): bool
    {
        $sql = "
            SELECT COUNT(*)
            FROM reservations
            WHERE table_id = ?
              AND status IN ('Pending', 'Confirmed', 'CheckedIn')
              AND booking_time < DATE_ADD(?, INTERVAL 120 MINUTE)
              AND DATE_ADD(booking_time, INTERVAL 120 MINUTE) > ?
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$tableId, $bookingTime, $bookingTime]);
        return (int)$stmt->fetchColumn() === 0;
    }

    public function createReservation(array $data): string
    {
        $this->db->beginTransaction();
        try {
            $customerId = $this->findOrCreateCustomer(
                $data['full_name'],
                $data['phone'],
                $data['email'] ?? ''
            );

            $bookingCode = 'BK' . date('YmdHis') . random_int(100, 999);

            $stmt = $this->db->prepare("
                INSERT INTO reservations (
                    booking_code, customer_id, table_id, booking_time,
                    guest_count, deposit_amount, special_request, status
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

    public function getUpcomingReservedTableIds(): array
    {
        $sql = "
            SELECT DISTINCT table_id
            FROM reservations
            WHERE table_id IS NOT NULL
              AND status IN ('Pending', 'Confirmed')
              AND booking_time > DATE_SUB(NOW(), INTERVAL 30 MINUTE)
        ";
        $stmt = $this->db->query($sql);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function updateReservationStatus(int $id, string $status, int $actorUserId = 1): bool
    {
        $this->db->beginTransaction();
        try {
            $allowed = ['Confirmed', 'CheckedIn', 'Cancelled'];
            if (!in_array($status, $allowed, true)) {
                $this->db->rollBack();
                return false;
            }

            $stmt = $this->db->prepare("
                SELECT id, booking_code, customer_id, table_id, booking_time, guest_count, deposit_amount, status
                FROM reservations
                WHERE id = ?
                FOR UPDATE
            ");
            $stmt->execute([$id]);
            $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$reservation) {
                $this->db->rollBack();
                return false;
            }

            $transitions = [
                'Pending'   => ['Confirmed', 'Cancelled'],
                'Confirmed' => ['CheckedIn', 'Cancelled']
            ];
            $current = $reservation['status'];

            if (!isset($transitions[$current]) || !in_array($status, $transitions[$current], true)) {
                $this->db->rollBack();
                return false;
            }

            $tableId = (int)($reservation['table_id'] ?? 0);

            if ($status === 'Confirmed' && $tableId > 0) {
                $this->db->prepare("UPDATE dining_tables SET status = 'Reserved' WHERE id = ? AND status = 'Available'")->execute([$tableId]);
            } elseif ($status === 'Cancelled' && $tableId > 0) {
                $otherActive = $this->db->prepare("SELECT COUNT(*) FROM reservations WHERE table_id = ? AND id <> ? AND status IN ('Pending', 'Confirmed')");
                $otherActive->execute([$tableId, $id]);
                if ((int)$otherActive->fetchColumn() === 0) {
                    $this->db->prepare("UPDATE dining_tables SET status = 'Available' WHERE id = ? AND status = 'Reserved'")->execute([$tableId]);
                }
            } elseif ($status === 'CheckedIn') {
                if ($tableId <= 0) {
                    $this->db->rollBack();
                    return false;
                }

                $tableStmt = $this->db->prepare("SELECT status, table_name FROM dining_tables WHERE id = ? FOR UPDATE");
                $tableStmt->execute([$tableId]);
                $table = $tableStmt->fetch(PDO::FETCH_ASSOC);

                if (!$table || !in_array($table['status'], ['Available', 'Reserved'], true)) {
                    $this->db->rollBack();
                    return false;
                }

                // 1. Chuyển bàn sang Occupied
                $this->db->prepare("UPDATE dining_tables SET status = 'Occupied' WHERE id = ?")->execute([$tableId]);

                // 2. Tự động tạo Order Open trên POS liên kết với Booking & Tiền đặt cọc
                $openOrderCheck = $this->db->prepare("SELECT id FROM orders WHERE table_id = ? AND status = 'Open' LIMIT 1");
                $openOrderCheck->execute([$tableId]);
                if (!$openOrderCheck->fetch()) {
                    $orderCode = 'ORD-' . strtoupper(uniqid());
                    $insOrder = $this->db->prepare("
                        INSERT INTO orders (
                            order_code, table_id, reservation_id, customer_id, waiter_id,
                            guest_count, deposit_deducted, status, opened_at
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'Open', NOW())
                    ");
                    $insOrder->execute([
                        $orderCode,
                        $tableId,
                        (int)$reservation['id'],
                        (int)$reservation['customer_id'],
                        $actorUserId,
                        (int)$reservation['guest_count'],
                        (float)$reservation['deposit_amount']
                    ]);
                    $newOrderId = (int)$this->db->lastInsertId();

                    $insLog = $this->db->prepare("
                        INSERT INTO order_logs (order_id, user_id, action_type, description)
                        VALUES (?, ?, 'CreateOrder', ?)
                    ");
                    $insLog->execute([
                        $newOrderId,
                        $actorUserId,
                        "Check-in tự động từ Booking {$reservation['booking_code']} tại bàn {$table['table_name']} (Cọc: " . number_format((float)$reservation['deposit_amount']) . "đ)"
                    ]);
                }
            }

            $update = $this->db->prepare("UPDATE reservations SET status = ? WHERE id = ?");
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