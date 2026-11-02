<?php
require_once __DIR__ . '/CustomerModel.php';
require_once __DIR__ . '/../config/Database.php';

class OrderModel {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    // 1. Tạo đơn & Gọi món
    public function createOrAddOrder($table_id, $waiter_id, $items) {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("SELECT id FROM orders WHERE table_id = ? AND status = 'Open' LIMIT 1");
            $stmt->execute([$table_id]);
            $order = $stmt->fetch();

            if (!$order) {
                $order_code = 'ORD-' . strtoupper(uniqid());
                // Tìm phiếu đặt bàn đã check-in để liên kết khách hàng với đơn hàng.
$reservationStmt = $this->db->prepare(
    "SELECT id, customer_id
     FROM reservations
     WHERE table_id = ?
       AND status = 'CheckedIn'
     ORDER BY id DESC
     LIMIT 1"
);
$reservationStmt->execute([$table_id]);
$reservation = $reservationStmt->fetch(PDO::FETCH_ASSOC);

$reservationId = $reservation['id'] ?? null;
$customerId = $reservation['customer_id'] ?? null;

$order_code = 'ORD-' . strtoupper(uniqid());

$stmtInsert = $this->db->prepare(
    "INSERT INTO orders
        (order_code, table_id, waiter_id, reservation_id, customer_id,
         status, opened_at)
     VALUES (?, ?, ?, ?, ?, 'Open', NOW())"
);

$stmtInsert->execute([
    $order_code,
    $table_id,
    $waiter_id,
    $reservationId,
    $customerId
]);


                $order_id = $this->db->lastInsertId();

                $this->db->prepare("UPDATE dining_tables SET status = 'Occupied' WHERE id = ?")->execute([$table_id]);

                $logStmt = $this->db->prepare("INSERT INTO order_logs (order_id, user_id, action_type, description) VALUES (?, ?, 'CreateOrder', ?)");
                $logStmt->execute([$order_id, $waiter_id, "Tạo đơn mới: $order_code tại bàn ID $table_id"]);
            } else {
                $order_id = $order['id'];
            }

            $stmtDetail = $this->db->prepare("INSERT INTO order_details (order_id, menu_item_id, quantity, unit_price, modifier_notes, kds_status) VALUES (?, ?, ?, ?, ?, 'Waiting')");
            foreach ($items as $item) {
                $stmtDetail->execute([
                    $order_id,
                    $item['menu_item_id'],
                    $item['quantity'],
                    $item['unit_price'],
                    $item['modifier_notes'] ?? null
                ]);
            }

            $this->db->prepare("UPDATE orders SET subtotal = (SELECT IFNULL(SUM(quantity * unit_price), 0) FROM order_details WHERE order_id = ? AND kds_status != 'Cancelled') WHERE id = ?")
                ->execute([$order_id, $order_id]);

            $this->db->commit();
            return ['status' => 'success', 'order_id' => $order_id, 'message' => 'Gọi món thành công!'];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    // 2. Hủy món (Chống gian lận)
    public function cancelOrderItem($order_detail_id, $user_id, $reason) {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("SELECT order_id, kds_status FROM order_details WHERE id = ?");
            $stmt->execute([$order_detail_id]);
            $item = $stmt->fetch();

            if (!$item) throw new Exception("Không tìm thấy món!");
            if ($item['kds_status'] !== 'Waiting') {
                throw new Exception("Bếp đã chế biến, không thể hủy món!");
            }

            $this->db->prepare("UPDATE order_details SET kds_status = 'Cancelled', cancel_reason = ? WHERE id = ?")
                ->execute([$reason, $order_detail_id]);

            $order_id = $item['order_id'];
            $this->db->prepare("UPDATE orders SET subtotal = (SELECT IFNULL(SUM(quantity * unit_price), 0) FROM order_details WHERE order_id = ? AND kds_status != 'Cancelled') WHERE id = ?")
                ->execute([$order_id, $order_id]);

            $this->db->prepare("INSERT INTO order_logs (order_id, user_id, action_type, description) VALUES (?, ?, 'CancelItem', ?)")
                ->execute([$order_id, $user_id, "Hủy món ID $order_detail_id. Lý do: $reason"]);

            $this->db->commit();
            return ['status' => 'success', 'message' => 'Đã hủy món thành công'];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    // 3. Danh sách cho KDS
    public function getKDSList() {
        $sql = "SELECT od.id, od.order_id, od.kds_status, od.quantity, od.modifier_notes, od.ordered_at,
                       mi.name AS item_name, dt.table_name
                FROM order_details od
                JOIN menu_items mi ON od.menu_item_id = mi.id
                JOIN orders o ON od.order_id = o.id
                JOIN dining_tables dt ON o.table_id = dt.id
                WHERE od.kds_status IN ('Waiting', 'Cooking', 'Ready')
                ORDER BY od.ordered_at ASC";
        return $this->db->query($sql)->fetchAll();
    }

    // 4. Cập nhật KDS
    public function updateKDSStatus($order_detail_id, $new_status) {
        $stmt = $this->db->prepare("UPDATE order_details SET kds_status = ? WHERE id = ?");
        $stmt->execute([$new_status, $order_detail_id]);
        return ['status' => 'success', 'message' => 'Đã cập nhật KDS'];
    }

    // 5. Thanh toán & VietQR
    public function checkoutOrder($order_id, $cashier_id, $payment_method, $discount_amount = 0) {
        $this->db->beginTransaction();
        try {
           $sql = "SELECT o.*,
               a.surcharge_percent,
               r.deposit_amount,
               COALESCE(o.customer_id, r.customer_id) AS crm_customer_id
        FROM orders o
        JOIN dining_tables dt ON o.table_id = dt.id
        JOIN areas a ON dt.area_id = a.id
        LEFT JOIN reservations r ON o.reservation_id = r.id
        WHERE o.id = ?
        FOR UPDATE";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$order_id]);
            $order = $stmt->fetch();
            if (!$order) {
    throw new Exception("Không tìm thấy đơn hàng.");
}

if ($order['status'] === 'Paid') {
    throw new Exception("Đơn hàng đã được thanh toán trước đó.");
}
            $subtotal = $order['subtotal'];
            $area_surcharge_percent = $order['surcharge_percent'] ?? 0;
            $deposit_deducted = $order['deposit_amount'] ?? 0;

            
            $area_surcharge_amount = $subtotal * ($area_surcharge_percent / 100);
            $service_charge_amount = ($subtotal + $area_surcharge_amount) * 0.05;
            $taxable_amount = $subtotal + $area_surcharge_amount + $service_charge_amount - $discount_amount;
            $vat_amount = max(0, $taxable_amount * 0.08);
            $total_amount = $taxable_amount + $vat_amount - $deposit_deducted;

            $stmtUpdate = $this->db->prepare("UPDATE orders SET 
                area_surcharge_amount = ?, service_charge_amount = ?, discount_amount = ?, 
                deposit_deducted = ?, vat_amount = ?, total_amount = ?, status = 'Paid', closed_at = NOW() 
                WHERE id = ?");
            $stmtUpdate->execute([$area_surcharge_amount, $service_charge_amount, $discount_amount, $deposit_deducted, $vat_amount, $total_amount, $order_id]);

            $stmtPay = $this->db->prepare("INSERT INTO payments (order_id, cashier_id, payment_method, amount) VALUES (?, ?, ?, ?)");
            $stmtPay->execute([$order_id, $cashier_id, $payment_method, $total_amount]);
if (!empty($order['crm_customer_id'])) {
    $customerModel = new CustomerModel();

    $loyaltyAmount = max(0, $taxable_amount + $vat_amount);

    $customerModel->recordPurchase(
        (int)$order['crm_customer_id'],
        $loyaltyAmount
    );
}
            $this->db->prepare("UPDATE dining_tables SET status = 'Available' WHERE id = ?")->execute([$order['table_id']]);

            $this->db->prepare("INSERT INTO order_logs (order_id, user_id, action_type, description) VALUES (?, ?, 'Checkout', ?)")
                ->execute([$order_id, $cashier_id, "Thanh toán thành công: " . number_format($total_amount) . " VND"]);

            $this->db->commit();

            return [
                'status' => 'success',
                'total_amount' => $total_amount,
                'vietqr_url' => "https://img.vietqr.io/image/MB-0901000003-compact2.png?amount={$total_amount}&addInfo=THANH%20TOAN%20HD%20{$order['order_code']}"
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    // 6. Chuyển bàn chuẩn theo ID Bàn 
    public function transferTableByTableId($from_table_id, $to_table_id, $user_id) {
        $this->db->beginTransaction();
        try {
            // Lấy đơn Open mới nhất của bàn nguồn
            $stmt = $this->db->prepare("SELECT id, order_code FROM orders WHERE table_id = ? AND status = 'Open' ORDER BY id DESC LIMIT 1");
            $stmt->execute([$from_table_id]);
            $fromOrder = $stmt->fetch();

            if (!$fromOrder) throw new Exception("Bàn nguồn hiện không có đơn hàng nào đang mở!");
            if ($from_table_id == $to_table_id) throw new Exception("Bàn mới phải khác bàn hiện tại!");

            // Kiểm tra bàn đích xem có đơn Open nào chưa
            $stmtTo = $this->db->prepare("SELECT id FROM orders WHERE table_id = ? AND status = 'Open' ORDER BY id DESC LIMIT 1");
            $stmtTo->execute([$to_table_id]);
            $toOrder = $stmtTo->fetch();

            if ($toOrder) {
                // Nếu bàn đích ĐÃ CÓ đơn -> Tự động chuyển thành GỘP BÀN
                $to_order_id = $toOrder['id'];
                $from_order_id = $fromOrder['id'];

                $this->db->prepare("UPDATE order_details SET order_id = ? WHERE order_id = ?")->execute([$to_order_id, $from_order_id]);
                $this->db->prepare("UPDATE orders SET status = 'Cancelled', closed_at = NOW() WHERE id = ?")->execute([$from_order_id]);
                $this->db->prepare("UPDATE dining_tables SET status = 'Available' WHERE id = ?")->execute([$from_table_id]);
                
                // Tính lại tổng tiền
                $this->db->prepare("UPDATE orders SET subtotal = (SELECT IFNULL(SUM(quantity * unit_price), 0) FROM order_details WHERE order_id = ? AND kds_status != 'Cancelled') WHERE id = ?")
                    ->execute([$to_order_id, $to_order_id]);

                $msg = "Gộp bàn thành công!";
            } else {
                // Nếu bàn đích TRỐNG -> CHUYỂN BÀN
                $this->db->prepare("UPDATE orders SET table_id = ? WHERE id = ?")->execute([$to_table_id, $fromOrder['id']]);
                $this->db->prepare("UPDATE dining_tables SET status = 'Available' WHERE id = ?")->execute([$from_table_id]);
                $this->db->prepare("UPDATE dining_tables SET status = 'Occupied' WHERE id = ?")->execute([$to_table_id]);

                $msg = "Chuyển bàn thành công!";
            }

            // Ghi nhật ký hệ thống
            $this->db->prepare("INSERT INTO order_logs (order_id, user_id, action_type, description) VALUES (?, ?, 'TransferTable', ?)")
                ->execute([$fromOrder['id'], $user_id, $msg . " Từ bàn ID {$from_table_id} sang bàn ID {$to_table_id}"]);

            $this->db->commit();
            return ['status' => 'success', 'message' => $msg];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
// Lấy dữ liệu in hóa đơn 
    public function getBillData($order_id) {
        $sqlOrder = "SELECT o.*, dt.table_name, a.name AS area_name, u.full_name AS waiter_name, c.full_name AS customer_name
                     FROM orders o
                     JOIN dining_tables dt ON o.table_id = dt.id
                     JOIN areas a ON dt.area_id = a.id
                     LEFT JOIN users u ON o.waiter_id = u.id
                     LEFT JOIN customers c ON o.customer_id = c.id
                     WHERE o.id = ?";
        $stmt = $this->db->prepare($sqlOrder);
        $stmt->execute([$order_id]);
        $order = $stmt->fetch();

        if (!$order) return null;

        $sqlDetails = "SELECT od.*, mi.name AS item_name, mi.unit 
                       FROM order_details od
                       JOIN menu_items mi ON od.menu_item_id = mi.id
                       WHERE od.order_id = ? AND od.kds_status != 'Cancelled'";
        $stmtDetails = $this->db->prepare($sqlDetails);
        $stmtDetails->execute([$order_id]);
        $details = $stmtDetails->fetchAll();

        return [
            'order' => $order,
            'details' => $details
        ];
    }
   
}