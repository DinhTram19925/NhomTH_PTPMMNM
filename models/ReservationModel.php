<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/TableModel.php';

class ReservationModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
        new TableModel();
    }
public function countReservations(): int
    {
        return (int)$this->db->query("SELECT COUNT(*) FROM reservations")->fetchColumn();
    }

    public function getReservationsPaginated(int $limit, int $offset): array
    {
        $sql = "
            SELECT
                r.*,
                c.full_name,
                c.phone,
                c.tier,
                t.table_name,
                t.status AS table_status,
                a.name AS area_name,
                (SELECT o.status FROM orders o WHERE o.reservation_id = r.id ORDER BY o.id DESC LIMIT 1) AS linked_order_status
            FROM reservations r
            INNER JOIN customers c ON c.id = r.customer_id
            LEFT JOIN dining_tables t ON t.id = r.table_id
            LEFT JOIN areas a ON a.id = t.area_id
            ORDER BY r.booking_time DESC
            LIMIT :limit OFFSET :offset
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $stmt->bindValue(':offset', max(0, $offset), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                t.capacity AS table_capacity,
                t.status AS table_status,
                a.id AS area_id,
                a.name AS area_name,
                o.id AS active_order_id,
                o.order_code AS active_order_code,
                o.subtotal AS active_subtotal,
                (SELECT COUNT(*) FROM order_details od WHERE od.order_id = o.id AND od.kds_status <> 'Cancelled') AS total_dishes,
                (SELECT COUNT(*) FROM order_details od WHERE od.order_id = o.id AND od.kds_status = 'Waiting') AS waiting_dishes,
                (SELECT COUNT(*) FROM order_details od WHERE od.order_id = o.id AND od.kds_status = 'Cooking') AS cooking_dishes,
                (SELECT COUNT(*) FROM order_details od WHERE od.order_id = o.id AND od.kds_status = 'Ready') AS ready_dishes,
                (SELECT COUNT(*) FROM order_details od WHERE od.order_id = o.id AND od.kds_status = 'Served') AS served_dishes
            FROM reservations r
            INNER JOIN customers c ON c.id = r.customer_id
            LEFT JOIN dining_tables t ON t.id = r.table_id
            LEFT JOIN areas a ON a.id = t.area_id
            LEFT JOIN orders o ON o.table_id = r.table_id AND o.status = 'Open'
            ORDER BY
                FIELD(r.status, 'CheckedIn', 'Confirmed', 'Pending', 'Cancelled'),
                r.booking_time ASC
        ";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTableKdsProgressMap(): array
    {
        $sql = "
            SELECT
                o.table_id,
                o.id AS order_id,
                o.order_code,
                o.subtotal,
                o.guest_count,
                COUNT(CASE WHEN od.kds_status <> 'Cancelled' THEN 1 END) AS total_dishes,
                COUNT(CASE WHEN od.kds_status = 'Waiting' THEN 1 END) AS waiting_dishes,
                COUNT(CASE WHEN od.kds_status = 'Cooking' THEN 1 END) AS cooking_dishes,
                COUNT(CASE WHEN od.kds_status = 'Ready' THEN 1 END) AS ready_dishes,
                COUNT(CASE WHEN od.kds_status = 'Served' THEN 1 END) AS served_dishes
            FROM orders o
            LEFT JOIN order_details od ON od.order_id = o.id
            WHERE o.status = 'Open'
            GROUP BY o.table_id, o.id, o.order_code, o.subtotal, o.guest_count
        ";
        $rows = $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        $map = [];
        foreach ($rows as $row) {
            $map[(int)$row['table_id']] = $row;
        }
        return $map;
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
                t.table_type,
                t.capacity,
                t.status,
                a.name AS area_name,
                a.floor_number,
                a.surcharge_percent
            FROM dining_tables t
            INNER JOIN areas a ON a.id = t.area_id
            WHERE t.status IN ('Available', 'Reserved')
            ORDER BY a.floor_number ASC, a.id ASC, FIELD(t.table_type, 'Standard', 'VIP', 'Premium'), t.table_name ASC
        ";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAvailableTablesForBooking(string $bookingTime, int $guestCount): array
    {
        $sql = "
            SELECT
                t.id,
                t.table_name,
                t.table_type,
                t.capacity,
                t.status,
                a.name AS area_name,
                a.floor_number,
                a.surcharge_percent
            FROM dining_tables t
            INNER JOIN areas a ON a.id = t.area_id
            WHERE t.status IN ('Available', 'Reserved')
              AND t.capacity >= ?
              AND NOT EXISTS (
                  SELECT 1
                  FROM reservations r
                  WHERE r.table_id = t.id
                    AND r.status IN ('Pending', 'Confirmed', 'CheckedIn')
                    AND r.booking_time < DATE_ADD(?, INTERVAL 120 MINUTE)
                    AND DATE_ADD(r.booking_time, INTERVAL 120 MINUTE) > ?
              )
            ORDER BY a.floor_number ASC, a.id ASC, FIELD(t.table_type, 'Standard', 'VIP', 'Premium'), t.table_name ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$guestCount, $bookingTime, $bookingTime]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findOrCreateCustomer(string $name, string $phone, string $email = ''): int
    {
        $stmt = $this->db->prepare("SELECT id FROM customers WHERE phone = ? FOR UPDATE");
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
        $stmt = $this->db->prepare("
            SELECT t.*, a.name AS area_name, a.floor_number, a.surcharge_percent
            FROM dining_tables t
            LEFT JOIN areas a ON a.id = t.area_id
            WHERE t.id = ?
        ");
        $stmt->execute([$tableId]);
        $table = $stmt->fetch(PDO::FETCH_ASSOC);
        return $table ?: null;
    }

    public function isTableAvailable(int $tableId, string $bookingTime): bool
    {
        $table = $this->findTable($tableId);
        if (!$table || !in_array($table['status'], ['Available', 'Reserved'], true)) {
            return false;
        }
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
        $tableId = (int)($data['table_id'] ?? 0);
        $guestCount = (int)($data['guest_count'] ?? 0);
        $bookingTime = trim((string)($data['booking_time'] ?? ''));
        $phone = trim((string)($data['phone'] ?? ''));
        $name = trim((string)($data['full_name'] ?? ''));
        $email = trim((string)($data['email'] ?? ''));
        $deposit = (float)($data['deposit_amount'] ?? 0);

        if ($name === '') {
            throw new InvalidArgumentException('Vui lòng nhập họ và tên khách hàng.');
        }
        // LUỒNG LOGIC BẮT BUỘC: SĐT phải bắt đầu bằng số 0 và có từ 10 đến 11 chữ số
        if (!preg_match('/^0[0-9]{9,10}$/', $phone)) {
            throw new InvalidArgumentException('Số điện thoại không hợp lệ (Phải bắt đầu bằng số 0 và có từ 10 đến 11 chữ số).');
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Địa chỉ Email không hợp lệ.');
        }
        if ($tableId <= 0 || $guestCount < 1 || $deposit < 0 || $bookingTime === '') {
            throw new InvalidArgumentException('Vui lòng nhập đầy đủ thông tin bàn, số khách và thời gian đặt.');
        }
        if (strtotime($bookingTime) === false || strtotime($bookingTime) <= time() - 300) {
            throw new InvalidArgumentException('Thời gian đặt bàn phải nằm trong tương lai.');
        }

        $this->db->beginTransaction();
        try {
            $tStmt = $this->db->prepare("SELECT * FROM dining_tables WHERE id = ? FOR UPDATE");
            $tStmt->execute([$tableId]);
            $table = $tStmt->fetch(PDO::FETCH_ASSOC);

            if (!$table || !in_array($table['status'], ['Available', 'Reserved'], true)) {
                throw new InvalidArgumentException('Bàn/Phòng không tồn tại hoặc hiện không khả dụng để đặt.');
            }
            if ($guestCount > (int)$table['capacity']) {
                throw new InvalidArgumentException("Số lượng khách ($guestCount) vượt quá sức chứa tối đa của {$table['table_name']} ({$table['capacity']} ghế).");
            }
            if (!$this->isTableAvailable($tableId, $bookingTime)) {
                throw new InvalidArgumentException('Bàn/Phòng đã có lịch đặt trùng trong khung giờ này.');
            }

            // RULE CỌC BẮT BUỘC: VIP = 5.000.000đ, Thượng Hạng (Premium) = 10.000.000đ
            $tableType = $table['table_type'] ?? 'Standard';
            if ($tableType === 'VIP') {
                $deposit = (float)TableModel::DEPOSIT_VIP;
            } elseif ($tableType === 'Premium') {
                $deposit = (float)TableModel::DEPOSIT_PREMIUM;
            }

            $customerId = $this->findOrCreateCustomer($name, $phone, $email);
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
                $tableId,
                $bookingTime,
                $guestCount,
                $deposit,
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

    public function confirmTestDeposit(string $bookingCode): bool
    {
        $stmt = $this->db->prepare("SELECT id, special_request FROM reservations WHERE booking_code = ?");
        $stmt->execute([$bookingCode]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return false;
        }
        if (strpos((string)$row['special_request'], '[TEST MODE:') === false) {
            $note = trim(($row['special_request'] ?? '') . ' [TEST MODE: Đã xác nhận cọc mô phỏng]');
            $upd = $this->db->prepare("UPDATE reservations SET special_request = ? WHERE id = ?");
            return $upd->execute([$note, (int)$row['id']]);
        }
        return true;
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
                $tStmt = $this->db->prepare("SELECT status FROM dining_tables WHERE id = ? FOR UPDATE");
                $tStmt->execute([$tableId]);
                $tRow = $tStmt->fetch(PDO::FETCH_ASSOC);
                if (!$tRow || $tRow['status'] === 'Cleaning') {
                    $this->db->rollBack();
                    return false;
                }
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

                $tableStmt = $this->db->prepare("SELECT status, table_name, table_type FROM dining_tables WHERE id = ? FOR UPDATE");
                $tableStmt->execute([$tableId]);
                $table = $tableStmt->fetch(PDO::FETCH_ASSOC);

                if (!$table || !in_array($table['status'], ['Available', 'Reserved'], true)) {
                    $this->db->rollBack();
                    return false;
                }

                $this->db->prepare("UPDATE dining_tables SET status = 'Occupied' WHERE id = ?")->execute([$tableId]);

                $openOrderCheck = $this->db->prepare("SELECT id FROM orders WHERE table_id = ? AND status = 'Open' LIMIT 1 FOR UPDATE");
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
                        "Check-in tự động từ Booking {$reservation['booking_code']} tại {$table['table_name']} (Cọc: " . number_format((float)$reservation['deposit_amount']) . "đ)"
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