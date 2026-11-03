
<?php

require_once __DIR__ . '/../config/Database.php';

class CustomerModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    // Quy đổi hạng thành viên theo tổng chi tiêu
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

    // Lấy danh sách khách hàng
    public function getAllCustomers(string $keyword = ''): array
    {
        $sql = "
            SELECT
                id,
                full_name,
                phone,
                email,
                tier,
                points,
                total_spent,
                created_at
            FROM customers
        ";

        $params = [];

        if ($keyword !== '') {
            $sql .= "
                WHERE full_name LIKE ?
                   OR phone LIKE ?
                   OR email LIKE ?
            ";

            $search = '%' . $keyword . '%';
            $params = [$search, $search, $search];
        }

        $sql .= "
            ORDER BY total_spent DESC, id DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Thống kê hội viên
    public function getCustomerStatistics(): array
    {
        $sql = "
            SELECT
                COUNT(*) AS total_customers,
                SUM(CASE WHEN tier = 'Standard' THEN 1 ELSE 0 END) AS standard_count,
                SUM(CASE WHEN tier = 'Silver' THEN 1 ELSE 0 END) AS silver_count,
                SUM(CASE WHEN tier = 'Gold' THEN 1 ELSE 0 END) AS gold_count,
                SUM(CASE WHEN tier = 'Diamond' THEN 1 ELSE 0 END) AS diamond_count,
                COALESCE(SUM(points), 0) AS total_points,
                COALESCE(SUM(total_spent), 0) AS total_revenue
            FROM customers
        ";

        return $this->db->query($sql)->fetch(PDO::FETCH_ASSOC);
    }

    // Lấy thông tin một khách hàng
    public function findCustomer(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM customers WHERE id = ?"
        );

        $stmt->execute([$id]);

        $customer = $stmt->fetch(PDO::FETCH_ASSOC);

        return $customer ?: null;
    }

    // Thêm hoặc cập nhật khách hàng
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

        // Kiểm tra số điện thoại đã được sử dụng
        $check = $this->db->prepare(
            "SELECT id FROM customers WHERE phone = ? AND id != ?"
        );

        $check->execute([$phone, $id ?? 0]);

        if ($check->fetch()) {
            throw new InvalidArgumentException(
                'Số điện thoại đã tồn tại.'
            );
        }

        if ($id !== null) {
            $stmt = $this->db->prepare(
                "UPDATE customers
                 SET full_name = ?, phone = ?, email = ?
                 WHERE id = ?"
            );

            return $stmt->execute([
                $name,
                $phone,
                $email !== '' ? $email : null,
                $id
            ]);
        }

        $stmt = $this->db->prepare(
            "INSERT INTO customers (full_name, phone, email)
             VALUES (?, ?, ?)"
        );

        return $stmt->execute([
            $name,
            $phone,
            $email !== '' ? $email : null
        ]);
    }

    // Cập nhật hạng hội viên
    public function updateCustomerTier(int $customerId): bool
    {
        $customer = $this->findCustomer($customerId);

        if (!$customer) {
            return false;
        }

        $tier = $this->calculateTier(
            (float)$customer['total_spent']
        );

        $stmt = $this->db->prepare(
            "UPDATE customers SET tier = ? WHERE id = ?"
        );

        return $stmt->execute([$tier, $customerId]);
    }

    // Ghi nhận chi tiêu và cộng điểm sau thanh toán
    public function recordPurchase(
    int $customerId,
    float $amount
): bool {
    if ($amount <= 0) {
        return false;
    }

    // Nếu đang nằm trong transaction thanh toán thì dùng chung transaction.
    $ownTransaction = !$this->db->inTransaction();

    if ($ownTransaction) {
        $this->db->beginTransaction();
    }

    try {
        $stmt = $this->db->prepare(
            "SELECT total_spent
             FROM customers
             WHERE id = ?
             FOR UPDATE"
        );

        $stmt->execute([$customerId]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$customer) {
            throw new RuntimeException('Không tìm thấy khách hàng.');
        }

        // 10.000 đồng = 1 điểm
        $points = (int) floor($amount / 10000);

        $newTotal = (float)$customer['total_spent'] + $amount;
        $newTier = $this->calculateTier($newTotal);

        $update = $this->db->prepare(
            "UPDATE customers
             SET total_spent = total_spent + ?,
                 points = points + ?,
                 tier = ?
             WHERE id = ?"
        );

        $update->execute([
            $amount,
            $points,
            $newTier,
            $customerId
        ]);

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
