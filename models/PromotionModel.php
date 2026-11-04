<?php
require_once __DIR__ . '/../config/Database.php';

class PromotionModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getAllPromotions(string $keyword = ''): array
    {
        $sql = "SELECT * FROM promotions";
        $params = [];

        if ($keyword !== '') {
            $sql .= " WHERE code LIKE ? OR name LIKE ?";
            $params = ["%$keyword%", "%$keyword%"];
        }

        $sql .= " ORDER BY id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findPromotion(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM promotions WHERE id = ?"
        );
        $stmt->execute([$id]);

        $promotion = $stmt->fetch(PDO::FETCH_ASSOC);

        return $promotion ?: null;
    }

    public function savePromotion(array $data, ?int $id = null): bool
    {
        $code = strtoupper(trim($data['code'] ?? ''));
        $name = trim($data['name'] ?? '');
        $type = $data['discount_type'] ?? '';
        $value = (float)($data['discount_value'] ?? 0);
        $maxDiscount = $data['max_discount'] ?? null;
        $minOrder = (float)($data['min_order_value'] ?? 0);
        $minTier = $data['min_tier'] ?? 'Standard';
        $startDate = $data['start_date'] ?? '';
        $endDate = $data['end_date'] ?? '';
        $isActive = isset($data['is_active']) ? 1 : 0;

        if ($code === '' || $name === '') {
            throw new InvalidArgumentException('Vui lòng nhập mã và tên voucher.');
        }

        if (!preg_match('/^[A-Z0-9_-]{3,30}$/', $code)) {
            throw new InvalidArgumentException('Mã voucher phải có 3-30 ký tự, chỉ gồm chữ, số, dấu gạch ngang hoặc gạch dưới.');
        }

        if (!in_array($type, ['Percent', 'Fixed'], true)) {
            throw new InvalidArgumentException('Loại giảm giá không hợp lệ.');
        }

        if ($value <= 0 || ($type === 'Percent' && $value > 100)) {
            throw new InvalidArgumentException('Giá trị giảm giá không hợp lệ.');
        }

        if ($maxDiscount !== null && $maxDiscount !== '' && (float)$maxDiscount < 0) {
            throw new InvalidArgumentException('Mức giảm tối đa không hợp lệ.');
        }

        if ($minOrder < 0) {
            throw new InvalidArgumentException('Đơn tối thiểu không hợp lệ.');
        }

        if (!in_array($minTier, ['Standard', 'Silver', 'Gold', 'Diamond'], true)) {
            throw new InvalidArgumentException('Hạng thành viên không hợp lệ.');
        }

        if (!$startDate || !$endDate || strtotime($endDate) < strtotime($startDate)) {
            throw new InvalidArgumentException('Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.');
        }

        $checkSql = "SELECT COUNT(*) FROM promotions WHERE code = ?";
        $checkParams = [$code];

        if ($id !== null) {
            $checkSql .= " AND id <> ?";
            $checkParams[] = $id;
        }

        $check = $this->db->prepare($checkSql);
        $check->execute($checkParams);

        if ((int)$check->fetchColumn() > 0) {
            throw new InvalidArgumentException('Mã voucher đã tồn tại.');
        }

        $maxDiscount = ($maxDiscount === '' || $maxDiscount === null)
            ? null
            : (float)$maxDiscount;

        if ($id !== null) {
            $sql = "UPDATE promotions SET
                    code = ?, name = ?, discount_type = ?,
                    discount_value = ?, max_discount = ?,
                    min_order_value = ?, min_tier = ?,
                    start_date = ?, end_date = ?, is_active = ?
                    WHERE id = ?";

            $params = [
                $code, $name, $type, $value, $maxDiscount,
                $minOrder, $minTier, $startDate, $endDate,
                $isActive, $id
            ];
        } else {
            $sql = "INSERT INTO promotions
                    (code, name, discount_type, discount_value,
                     max_discount, min_order_value, min_tier,
                     start_date, end_date, is_active)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $params = [
                $code, $name, $type, $value, $maxDiscount,
                $minOrder, $minTier, $startDate, $endDate,
                $isActive
            ];
        }

        $stmt = $this->db->prepare($sql);

        return $stmt->execute($params);
    }

    public function deletePromotion(int $id): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM promotions WHERE id = ?"
        );

        return $stmt->execute([$id]);
    }

    public function togglePromotion(int $id): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE promotions
             SET is_active = IF(is_active = 1, 0, 1)
             WHERE id = ?"
        );

        return $stmt->execute([$id]);
    }

    public function validateVoucher(
        string $code,
        float $orderValue,
        string $customerTier = 'Standard'
    ): array {
        $stmt = $this->db->prepare(
            "SELECT * FROM promotions
             WHERE code = ?
             AND is_active = 1
             AND start_date <= CURDATE()
             AND end_date >= CURDATE()
             LIMIT 1"
        );

        $stmt->execute([strtoupper(trim($code))]);
        $voucher = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$voucher) {
            return ['valid' => false, 'message' => 'Voucher không tồn tại, hết hạn hoặc đã bị tắt.'];
        }

        if ($orderValue < (float)$voucher['min_order_value']) {
            return ['valid' => false, 'message' => 'Đơn hàng chưa đạt giá trị tối thiểu.'];
        }

        $tiers = ['Standard' => 0, 'Silver' => 1, 'Gold' => 2, 'Diamond' => 3];

        if (($tiers[$customerTier] ?? 0) < ($tiers[$voucher['min_tier']] ?? 0)) {
            return ['valid' => false, 'message' => 'Hạng thành viên chưa đủ điều kiện sử dụng voucher.'];
        }

        if ($voucher['discount_type'] === 'Percent') {
            $discount = $orderValue * (float)$voucher['discount_value'] / 100;

            if ($voucher['max_discount'] !== null) {
                $discount = min($discount, (float)$voucher['max_discount']);
            }
        } else {
            $discount = (float)$voucher['discount_value'];

            if ($voucher['max_discount'] !== null) {
                $discount = min($discount, (float)$voucher['max_discount']);
            }
        }

        $discount = min($discount, $orderValue);

        return [
            'valid' => true,
            'message' => 'Áp dụng voucher thành công.',
            'voucher' => $voucher,
            'discount' => $discount
        ];
    }
}
