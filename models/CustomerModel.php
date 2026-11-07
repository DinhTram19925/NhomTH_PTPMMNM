<?php
require_once __DIR__ . '/../config/Database.php';

class CustomerModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    // Chính sách xét nâng hạng tự động theo Tổng chi tiêu lũy kế CRM
    private function calculateTier(float $totalSpent): string
    {
        if ($totalSpent >= 50000000) {
            return 'Diamond';
        }
        if ($totalSpent >= 20000000) {
            return 'Gold';
        }
        if ($totalSpent >= 5000000) {
            return 'Silver';
        }
        return 'Standard';
    }

    public function getAllCustomers(string $keyword = ''): array
    {
        $sql = "
            SELECT id, full_name, phone, email, tier, points, total_spent, created_at
            FROM customers
        ";
        $params = [];

        if ($keyword !== '') {
            $sql .= " WHERE full_name LIKE ? OR phone LIKE ? OR email LIKE ?";
            $search = '%' . $keyword . '%';
            $params = [$search, $search, $search];
        }

        $sql .= " ORDER BY total_spent DESC, id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCustomerStatistics(): array
    {
        $sql = "
            SELECT
                COUNT(*) AS total_customers,
                COALESCE(SUM(CASE WHEN tier = 'Standard' THEN 1 ELSE 0 END), 0) AS standard_count,
                COALESCE(SUM(CASE WHEN tier = 'Silver' THEN 1 ELSE 0 END), 0) AS silver_count,
                COALESCE(SUM(CASE WHEN tier = 'Gold' THEN 1 ELSE 0 END), 0) AS gold_count,
                COALESCE(SUM(CASE WHEN tier = 'Diamond' THEN 1 ELSE 0 END), 0) AS diamond_count,
                COALESCE(SUM(points), 0) AS total_points,
                COALESCE(SUM(total_spent), 0) AS total_revenue
            FROM customers
        ";
        return $this->db->query($sql)->fetch(PDO::FETCH_ASSOC);
    }

    public function findCustomer(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM customers WHERE id = ?");
        $stmt->execute([$id]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        return $customer ?: null;
    }

    public function saveCustomer(array $data, ?int $id = null): bool
    {
        $name = trim($data['full_name'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $email = trim($data['email'] ?? '');

        if ($name === '') {
            throw new InvalidArgumentException('Vui lòng nhập họ tên.');
        }
        if (!preg_match('/^[0-9]{9,11}$/', $phone)) {
            throw new InvalidArgumentException('Số điện thoại không hợp lệ.');
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Email không hợp lệ.');
        }

        $check = $this->db->prepare("SELECT id FROM customers WHERE phone = ? AND id != ?");
        $check->execute([$phone, $id ?? 0]);
        if ($check->fetch()) {
            throw new InvalidArgumentException('Số điện thoại đã tồn tại.');
        }

        if ($id !== null) {
            $stmt = $this->db->prepare("UPDATE customers SET full_name = ?, phone = ?, email = ? WHERE id = ?");
            return $stmt->execute([$name, $phone, $email !== '' ? $email : null, $id]);
        }

        $stmt = $this->db->prepare("INSERT INTO customers (full_name, phone, email) VALUES (?, ?, ?)");
        return $stmt->execute([$name, $phone, $email !== '' ? $email : null]);
    }

    // Tự động cộng điểm (10.000đ = 1 điểm) và xét nâng hạng ngay sau thanh toán
    public function recordPurchase(int $customerId, float $amount): bool
    {
        if ($amount <= 0) {
            return false;
        }

        $ownTransaction = !$this->db->inTransaction();
        if ($ownTransaction) {
            $this->db->beginTransaction();
        }

        try {
            $stmt = $this->db->prepare("SELECT total_spent FROM customers WHERE id = ? FOR UPDATE");
            $stmt->execute([$customerId]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$customer) {
                throw new RuntimeException('Không tìm thấy khách hàng.');
            }

            $points = (int)floor($amount / 10000);
            $newTotal = (float)$customer['total_spent'] + $amount;
            $newTier = $this->calculateTier($newTotal);

            $update = $this->db->prepare("
                UPDATE customers
                SET total_spent = total_spent + ?,
                    points = points + ?,
                    tier = ?
                WHERE id = ?
            ");
            $update->execute([$amount, $points, $newTier, $customerId]);

            if ($ownTransaction) {
                $this->db->commit();
            }
            return true;
        } catch (Throwable $e) {
            if ($ownTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
}