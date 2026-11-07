<?php
require_once __DIR__ . '/CustomerModel.php';
require_once __DIR__ . '/StockModel.php';
require_once __DIR__ . '/CashShiftModel.php';
require_once __DIR__ . '/../config/Database.php';

class OrderModel {
    private $db;

    const SERVICE_RATE = 0.05;
    const VAT_RATE     = 0.08;
    const TIER_DISCOUNT = ['Standard' => 0, 'Silver' => 0.03, 'Gold' => 0.05, 'Diamond' => 0.10];
    const TIER_RANK     = ['Standard' => 0, 'Silver' => 1, 'Gold' => 2, 'Diamond' => 3];
    const COURSE_PRIORITY = [
        'Appetizer' => 1, 'Soup_Salad' => 2, 'Main_Course' => 3,
        'Dessert' => 4, 'Beverage' => 1, 'Set_Menu' => 3
    ];

    const BANK_ID = 'MB';
    const BANK_ACCOUNT = '0901000003';
    const BANK_ACCOUNT_NAME = 'ROYALDINE 5S';

    public function __construct() {
        $this->db = Database::getConnection();
    }

    private function err($msg) {
        return ['status' => 'error', 'message' => $msg];
    }

    private function fail($e) {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
        if ($e instanceof PDOException) {
            error_log('[POS] ' . $e->getMessage());
            return $this->err('Lỗi cơ sở dữ liệu, vui lòng thử lại hoặc báo quản trị.');
        }
        return $this->err($e->getMessage());
    }

    private function one($sql, $params = []) {
        $st = $this->db->prepare($sql);
        $st->execute($params);
        return $st->fetch(PDO::FETCH_ASSOC);
    }

    private function all($sql, $params = []) {
        $st = $this->db->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    private function recalcSubtotal($order_id) {
        $this->db->prepare("
            UPDATE orders
            SET subtotal = (
                SELECT IFNULL(SUM(quantity * unit_price), 0)
                FROM order_details
                WHERE order_id = ? AND kds_status <> 'Cancelled'
            )
            WHERE id = ?
        ")->execute([$order_id, $order_id]);
    }

    private function addLog($order_id, $user_id, $type, $desc) {
        $this->db->prepare("
            INSERT INTO order_logs (order_id, user_id, action_type, description)
            VALUES (?, ?, ?, ?)
        ")->execute([$order_id, $user_id, $type, $desc]);
    }

    private function dateRange($col, $from, $to) {
        if ($from && $to) {
            return [" AND $col >= ? AND $col < DATE_ADD(?, INTERVAL 1 DAY)", [$from, $to]];
        }
        return ['', []];
    }

    private function releaseTableSmart(int $tableId): void {
        $upcoming = $this->one("
            SELECT COUNT(*) AS cnt
            FROM reservations
            WHERE table_id = ?
              AND status = 'Confirmed'
              AND booking_time > DATE_SUB(NOW(), INTERVAL 30 MINUTE)
        ", [$tableId]);
        $nextStatus = ((int)($upcoming['cnt'] ?? 0) > 0) ? 'Reserved' : 'Available';
        $this->db->prepare("UPDATE dining_tables SET status = ? WHERE id = ?")->execute([$nextStatus, $tableId]);
    }

    public function createOrAddOrder($table_id, $waiter_id, $items) {
        $table_id = (int)$table_id;
        if (!$table_id || !is_array($items) || !$items) {
            return $this->err('Chưa chọn bàn hoặc chưa có món nào.');
        }
        $this->db->beginTransaction();
        try {
            $table = $this->one("SELECT id, table_name, status FROM dining_tables WHERE id = ? FOR UPDATE", [$table_id]);
            if (!$table) {
                throw new Exception('Bàn không tồn tại!');
            }

            $order = $this->one("SELECT id, order_code FROM orders WHERE table_id = ? AND status = 'Open' LIMIT 1 FOR UPDATE", [$table_id]);
            if (!$order) {
                if ($table['status'] === 'Cleaning') {
                    throw new Exception("Bàn {$table['table_name']} đang dọn vệ sinh, chưa thể mở đơn mới!");
                }

                $res = $this->one("
                    SELECT id, customer_id, guest_count, deposit_amount
                    FROM reservations
                    WHERE table_id = ? AND status IN ('CheckedIn', 'Confirmed')
                    ORDER BY FIELD(status, 'CheckedIn', 'Confirmed'), booking_time DESC
                    LIMIT 1
                ", [$table_id]);

                $resId   = $res ? (int)$res['id'] : null;
                $custId  = $res ? (int)$res['customer_id'] : null;
                $guests  = $res ? (int)$res['guest_count'] : 2;
                $deposit = $res ? (float)$res['deposit_amount'] : 0;

                if ($res && $res['id']) {
                    $this->db->prepare("UPDATE reservations SET status = 'CheckedIn' WHERE id = ?")->execute([$resId]);
                }

                $order_code = 'ORD-' . strtoupper(uniqid());
                $this->db->prepare("
                    INSERT INTO orders (
                        order_code, table_id, reservation_id, customer_id, waiter_id,
                        guest_count, deposit_deducted, status, opened_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, 'Open', NOW())
                ")->execute([$order_code, $table_id, $resId, $custId, $waiter_id, $guests, $deposit]);

                $order = ['id' => $this->db->lastInsertId(), 'order_code' => $order_code];
                $this->db->prepare("UPDATE dining_tables SET status = 'Occupied' WHERE id = ?")->execute([$table_id]);
                $this->addLog($order['id'], $waiter_id, 'CreateOrder', "Tạo đơn mới: {$order_code} tại bàn {$table['table_name']}");
            }

            $ins = $this->db->prepare("
                INSERT INTO order_details (order_id, menu_item_id, quantity, unit_price, modifier_notes, course_priority, kds_status)
                VALUES (?, ?, ?, ?, ?, ?, 'Waiting')
            ");
            foreach ($items as $it) {
                $qty = (int)($it['quantity'] ?? 0);
                if ($qty < 1 || $qty > 99) {
                    throw new Exception('Số lượng món không hợp lệ (1–99).');
                }
                $mi = $this->one("
                    SELECT mi.id, mi.name, mi.price, mi.status, c.course_type
                    FROM menu_items mi
                    JOIN categories c ON mi.category_id = c.id
                    WHERE mi.id = ?
                ", [(int)($it['menu_item_id'] ?? 0)]);
                if (!$mi) {
                    throw new Exception('Có món không tồn tại trong thực đơn.');
                }
                if ($mi['status'] !== 'Available') {
                    throw new Exception("Món \"{$mi['name']}\" hiện không phục vụ ({$mi['status']}).");
                }
                $note = mb_substr(trim((string)($it['modifier_notes'] ?? '')), 0, 255);
                $ins->execute([
                    $order['id'],
                    $mi['id'],
                    $qty,
                    $mi['price'],
                    $note !== '' ? $note : null,
                    self::COURSE_PRIORITY[$mi['course_type']] ?? 1
                ]);
            }
            $this->recalcSubtotal($order['id']);
            $this->db->commit();
            return ['status' => 'success', 'order_id' => (int)$order['id'], 'message' => 'Gọi món thành công!'];
        } catch (Exception $e) {
            return $this->fail($e);
        }
    }

    public function cancelOrderItem($order_detail_id, $user_id, $reason) {
        $reason = trim((string)$reason);
        if (mb_strlen($reason) < 3) {
            return $this->err('Bắt buộc nhập lý do hủy món (ít nhất 3 ký tự).');
        }
        $this->db->beginTransaction();
        try {
            $it = $this->one("
                SELECT od.id, od.order_id, od.quantity, od.kds_status, mi.name, o.status AS order_status, dt.table_name
                FROM order_details od
                JOIN menu_items mi ON od.menu_item_id = mi.id
                JOIN orders o ON od.order_id = o.id
                JOIN dining_tables dt ON o.table_id = dt.id
                WHERE od.id = ? FOR UPDATE
            ", [(int)$order_detail_id]);
            if (!$it) throw new Exception('Không tìm thấy món!');
            if ($it['order_status'] !== 'Open') throw new Exception('Hóa đơn đã đóng, không thể hủy món!');
            if ($it['kds_status'] === 'Cooking') throw new Exception('Bếp đang chế biến món này, hãy báo bếp trưởng nếu cần dừng món!');
            if ($it['kds_status'] === 'Served') throw new Exception('Món đã phục vụ cho khách, không thể hủy.');
            if (!in_array($it['kds_status'], ['Waiting', 'Ready'], true)) throw new Exception('Món này không thể hủy!');

            if ($it['kds_status'] === 'Ready') {
                $reason = '[Đã nấu xong] ' . $reason;
            }

            $st = $this->db->prepare("
                UPDATE order_details
                SET kds_status = 'Cancelled', cancel_reason = ?
                WHERE id = ? AND kds_status = ?
            ");
            $st->execute([mb_substr($reason, 0, 255), $it['id'], $it['kds_status']]);
            if ($st->rowCount() === 0) {
                throw new Exception('Trạng thái món vừa thay đổi, hãy tải lại màn hình.');
            }

            $this->recalcSubtotal($it['order_id']);
            $this->addLog($it['order_id'], $user_id, 'CancelItem', "Hủy món ID {$it['id']} ({$it['quantity']}× {$it['name']}, Bàn {$it['table_name']}). Lý do: {$reason}");
            $this->db->commit();
            return ['status' => 'success', 'message' => 'Đã hủy món thành công'];
        } catch (Exception $e) {
            return $this->fail($e);
        }
    }

    public function getKDSList() {
        return $this->all("
            SELECT od.id, od.order_id, od.kds_status, od.quantity, od.modifier_notes, od.ordered_at,
                   mi.name AS item_name, dt.table_name, c.course_type,
                   IF(c.course_type = 'Beverage', 'Bar', 'Kitchen') AS station,
                   TIMESTAMPDIFF(MINUTE, od.ordered_at, NOW()) AS wait_minutes
            FROM order_details od
            JOIN menu_items mi ON od.menu_item_id = mi.id
            JOIN categories c ON mi.category_id = c.id
            JOIN orders o ON od.order_id = o.id AND o.status = 'Open'
            JOIN dining_tables dt ON o.table_id = dt.id
            WHERE od.kds_status IN ('Waiting', 'Cooking', 'Ready')
            ORDER BY od.ordered_at ASC, od.course_priority ASC, od.id ASC
        ");
    }

    public function updateKdsStatus($order_detail_id, $new_status, $takeaway = false) {
        $prev = ['Cooking' => 'Waiting', 'Ready' => 'Cooking', 'Served' => 'Ready'];
        if (!isset($prev[$new_status])) {
            return $this->err('Trạng thái không hợp lệ');
        }
        $set = 'od.kds_status = ?';
        if ($takeaway && $new_status === 'Served') {
            $set .= ", od.modifier_notes = LEFT(CONCAT_WS(' · ', NULLIF(od.modifier_notes, ''), 'Mang về'), 255)";
        }
        $st = $this->db->prepare("
            UPDATE order_details od
            JOIN orders o ON od.order_id = o.id
            SET $set
            WHERE od.id = ? AND od.kds_status = ? AND o.status = 'Open'
        ");
        $st->execute([$new_status, (int)$order_detail_id, $prev[$new_status]]);
        if ($st->rowCount() === 0) {
            return $this->err('Món đã đổi trạng thái hoặc đã bị hủy. Hãy tải lại màn hình.');
        }
        return ['status' => 'success', 'message' => "Đã chuyển trạng thái sang: {$new_status}"];
    }

    private function qrUrl($amount, $order_code) {
        return 'https://img.vietqr.io/image/' . self::BANK_ID . '-' . self::BANK_ACCOUNT . '-compact2.png'
            . '?amount=' . (int)round($amount)
            . '&addInfo=' . rawurlencode('THANH TOAN HD ' . $order_code)
            . '&accountName=' . rawurlencode(self::BANK_ACCOUNT_NAME);
    }

    public function calcBill($order_id, $phone = '', $voucher = '') {
        $o = $this->one("
            SELECT o.id, o.order_code, o.status, o.customer_id, o.reservation_id, o.deposit_deducted,
                   dt.table_name, a.name AS area_name, a.surcharge_percent
            FROM orders o
            JOIN dining_tables dt ON o.table_id = dt.id
            JOIN areas a ON dt.area_id = a.id
            WHERE o.id = ?
        ", [(int)$order_id]);
        if (!$o) return null;

        $items = $this->all("
            SELECT mi.name, od.quantity, od.unit_price, od.modifier_notes
            FROM order_details od
            JOIN menu_items mi ON od.menu_item_id = mi.id
            WHERE od.order_id = ? AND od.kds_status <> 'Cancelled'
            ORDER BY od.ordered_at, od.id
        ", [$o['id']]);
        $subtotal = 0;
        foreach ($items as $i) {
            $subtotal += $i['quantity'] * $i['unit_price'];
        }

        $unf = $this->one("SELECT COUNT(*) AS n FROM order_details WHERE order_id = ? AND kds_status IN ('Waiting', 'Cooking')", [$o['id']]);
        $rdy = $this->one("SELECT COUNT(*) AS n FROM order_details WHERE order_id = ? AND kds_status = 'Ready'", [$o['id']]);

        $surcharge = round($subtotal * (float)$o['surcharge_percent'] / 100);
        $service   = round(($subtotal + $surcharge) * self::SERVICE_RATE);

        $cust = null;
        $phoneErr = null;
        $digits = preg_replace('/\D/', '', (string)$phone);
        if ($digits !== '') {
            $cust = $this->one("SELECT id, full_name, tier FROM customers WHERE phone = ?", [$digits]);
            if (!$cust) {
                $phoneErr = 'Không tìm thấy khách hàng với số điện thoại này.';
            }
        } elseif ($o['customer_id']) {
            $cust = $this->one("SELECT id, full_name, tier FROM customers WHERE id = ?", [$o['customer_id']]);
        }
        $tier = $cust['tier'] ?? 'Standard';
        $tierDisc = round(($subtotal + $surcharge) * (self::TIER_DISCOUNT[$tier] ?? 0));

        $voucherDisc = 0;
        $voucherErr = null;
        $code = strtoupper(trim((string)$voucher));
        if ($code !== '') {
            $p = $this->one("SELECT *, (NOW() BETWEEN start_date AND end_date) AS in_range FROM promotions WHERE code = ?", [$code]);
            if (!$p || !$p['is_active']) $voucherErr = 'Mã voucher không tồn tại hoặc đã ngừng áp dụng.';
            elseif (!$p['in_range']) $voucherErr = 'Voucher chưa đến hoặc đã quá hạn sử dụng.';
            elseif ($subtotal < (float)$p['min_order_value']) $voucherErr = 'Đơn tối thiểu ' . number_format($p['min_order_value'], 0, ',', '.') . '₫ mới dùng được voucher này.';
            elseif ((self::TIER_RANK[$tier] ?? 0) < (self::TIER_RANK[$p['min_tier']] ?? 0)) $voucherErr = 'Voucher chỉ dành cho hạng ' . $p['min_tier'] . ' trở lên.';
            else {
                $base = $subtotal + $surcharge - $tierDisc;
                $d = $p['discount_type'] === 'Percent' ? $base * (float)$p['discount_value'] / 100 : (float)$p['discount_value'];
                if ($p['discount_type'] === 'Percent' && $p['max_discount'] !== null) $d = min($d, (float)$p['max_discount']);
                $voucherDisc = round(max(0, min($d, $base)));
            }
        }

        $deposit = (float)($o['deposit_deducted'] ?? 0);
        if ($deposit <= 0 && !empty($o['reservation_id'])) {
            $resRow = $this->one("SELECT deposit_amount FROM reservations WHERE id = ?", [$o['reservation_id']]);
            $deposit = (float)($resRow['deposit_amount'] ?? 0);
        }

        $discount = $tierDisc + $voucherDisc;
        $taxable  = max(0, $subtotal + $surcharge + $service - $discount);
        $vat      = round($taxable * self::VAT_RATE);
        $grossTotal = $taxable + $vat;
        $depositDeducted = min($deposit, $grossTotal);
        $total    = max(0, $grossTotal - $depositDeducted);

        return [
            'order_id' => (int)$o['id'], 'order_code' => $o['order_code'], 'status' => $o['status'],
            'table_name' => $o['table_name'], 'area_name' => $o['area_name'], 'surcharge_percent' => (float)$o['surcharge_percent'],
            'items' => $items, 'unfinished' => (int)$unf['n'], 'ready_unserved' => (int)$rdy['n'],
            'subtotal' => $subtotal, 'surcharge' => $surcharge, 'service' => $service,
            'tier' => $tier, 'tier_discount' => $tierDisc, 'voucher_code' => $code, 'voucher_discount' => $voucherDisc,
            'discount' => $discount, 'vat' => $vat, 'deposit_deducted' => $depositDeducted, 'total' => $total,
            'customer_id' => $cust['id'] ?? null, 'customer_name' => $cust['full_name'] ?? null,
            'phone_error' => $phoneErr, 'voucher_error' => $voucherErr,
            'vietqr_url' => $this->qrUrl($total, $o['order_code']),
        ];
    }

    public function checkoutOrder($order_id, $cashier_id, $payment_method, $phone = '', $voucher = '', $ref = '') {
        if (!in_array($payment_method, ['Cash', 'VietQR', 'CreditCard'], true)) {
            return $this->err('Phương thức thanh toán không hợp lệ.');
        }
        $this->db->beginTransaction();
        try {
            $o = $this->one("SELECT id, order_code, status, table_id, reservation_id FROM orders WHERE id = ? FOR UPDATE", [(int)$order_id]);
            if (!$o) throw new Exception('Không tìm thấy đơn hàng!');
            if ($o['status'] === 'Paid') throw new Exception('Đơn hàng đã được thanh toán trước đó.');
            if ($o['status'] !== 'Open') throw new Exception('Đơn này đã đóng, không thể thanh toán!');

            $b = $this->calcBill($o['id'], $phone, $voucher);
            if ($b['unfinished'] > 0) throw new Exception("Còn {$b['unfinished']} món Bếp/Bar chưa làm xong, chưa thể thanh toán.");
            if ($b['phone_error'])    throw new Exception($b['phone_error']);
            if ($b['voucher_error'])  throw new Exception($b['voucher_error']);
            if ($b['subtotal'] <= 0)  throw new Exception('Đơn chưa có món nào để thanh toán. Hãy dùng chức năng Đóng bàn.');

            $cashModel = new CashShiftModel();
            $openShift = $cashModel->getCurrentOpenShift($cashier_id);
            $shiftId   = $openShift ? (int)$openShift['id'] : null;

            $this->db->prepare("
                UPDATE orders
                SET customer_id = COALESCE(?, customer_id),
                    shift_id = COALESCE(?, shift_id),
                    subtotal = ?,
                    area_surcharge_amount = ?,
                    service_charge_amount = ?,
                    discount_amount = ?,
                    voucher_code = ?,
                    deposit_deducted = ?,
                    vat_amount = ?,
                    total_amount = ?,
                    status = 'Paid',
                    closed_at = NOW()
                WHERE id = ?
            ")->execute([
                $b['customer_id'],
                $shiftId,
                $b['subtotal'],
                $b['surcharge'],
                $b['service'],
                $b['discount'],
                $b['voucher_code'] !== '' ? $b['voucher_code'] : null,
                $b['deposit_deducted'],
                $b['vat'],
                $b['total'],
                $o['id']
            ]);

            $this->db->prepare("UPDATE order_details SET kds_status = 'Served' WHERE order_id = ? AND kds_status = 'Ready'")->execute([$o['id']]);

            $this->db->prepare("INSERT INTO payments (order_id, cashier_id, payment_method, amount, transaction_ref) VALUES (?, ?, ?, ?, ?)")
                ->execute([$o['id'], $cashier_id, $payment_method, $b['total'], trim((string)$ref) !== '' ? mb_substr(trim((string)$ref), 0, 100) : null]);

            $this->releaseTableSmart((int)$o['table_id']);

            if (!empty($b['customer_id']) && $b['total'] > 0) {
                $customerModel = new CustomerModel();
                $customerModel->recordPurchase((int)$b['customer_id'], (float)$b['total']);
            }

            if ($payment_method === 'Cash' && $b['total'] > 0 && $openShift) {
                $cashModel->recordSaleTransaction($cashier_id, (float)$b['total'], 'sales');
            }

            $stockModel = new StockModel();
            $bomResult = $stockModel->deductStockByOrder((int)$o['id'], (int)$cashier_id);
            if (empty($bomResult['success'])) {
                throw new Exception($bomResult['message'] ?? 'Không đủ nguyên liệu tồn kho để hoàn tất thanh toán.');
            }

            $note = 'Thanh toán thành công: ' . number_format($b['total']) . ' VND (' . $payment_method . ')';
            if ($b['tier_discount'] > 0)    $note .= ", hạng {$b['tier']} -" . number_format($b['tier_discount']);
            if ($b['voucher_discount'] > 0) $note .= ", voucher {$b['voucher_code']} -" . number_format($b['voucher_discount']);
            if ($b['deposit_deducted'] > 0) $note .= ", trừ cọc -" . number_format($b['deposit_deducted']);
            $this->addLog($o['id'], $cashier_id, 'Checkout', $note);

            $this->db->commit();
            $res = [
                'status' => 'success',
                'order_id' => (int)$o['id'],
                'total_amount' => $b['total'],
                'vietqr_url' => $b['vietqr_url']
            ];
            if (!empty($bomResult['warnings'])) {
                $res['stock_warnings'] = $bomResult['warnings'];
            }
            return $res;
        } catch (Exception $e) {
            return $this->fail($e);
        }
    }

    public function closeEmptyOrder($order_id, $user_id) {
        $this->db->beginTransaction();
        try {
            $o = $this->one("SELECT id, order_code, status, table_id, reservation_id FROM orders WHERE id = ? FOR UPDATE", [(int)$order_id]);
            if (!$o) throw new Exception('Không tìm thấy đơn hàng!');
            if ($o['status'] !== 'Open') throw new Exception('Đơn này đã đóng.');
            $n = $this->one("SELECT COUNT(*) AS n FROM order_details WHERE order_id = ? AND kds_status <> 'Cancelled'", [$o['id']]);
            if ($n['n'] > 0) throw new Exception('Đơn vẫn còn món, không thể đóng bàn. Hãy thanh toán hoặc hủy món trước.');

            $this->db->prepare("UPDATE orders SET status = 'Cancelled', subtotal = 0, total_amount = 0, closed_at = NOW() WHERE id = ?")->execute([$o['id']]);
            if (!empty($o['reservation_id'])) {
                $this->db->prepare("UPDATE reservations SET status = 'Cancelled' WHERE id = ? AND status = 'CheckedIn'")->execute([(int)$o['reservation_id']]);
            }
            $this->releaseTableSmart((int)$o['table_id']);

            $this->addLog($o['id'], $user_id, 'Checkout', "Đóng bàn không phát sinh doanh thu (đơn {$o['order_code']} đã hủy hết món)");
            $this->db->commit();
            return ['status' => 'success', 'message' => 'Đã đóng bàn, bàn sẵn sàng đón khách mới'];
        } catch (Exception $e) {
            return $this->fail($e);
        }
    }

    public function transferTableByTableId($from_table_id, $to_table_id, $user_id, $mode = '') {
        $from_id = (int)$from_table_id;
        $to_id   = (int)$to_table_id;
        if (!$from_id || !$to_id || $from_id === $to_id) return $this->err('Bàn mới phải khác bàn hiện tại!');
        $this->db->beginTransaction();
        try {
            $tsql = "SELECT dt.id, dt.table_name, dt.status, a.name AS area_name, a.surcharge_percent
                     FROM dining_tables dt JOIN areas a ON dt.area_id = a.id WHERE dt.id = ? FOR UPDATE";
            if ($from_id < $to_id) { $fromT = $this->one($tsql, [$from_id]); $toT = $this->one($tsql, [$to_id]); }
            else                   { $toT = $this->one($tsql, [$to_id]);     $fromT = $this->one($tsql, [$from_id]); }
            if (!$fromT || !$toT) throw new Exception('Bàn không tồn tại!');

            $fromOrder = $this->one("SELECT id, order_code, guest_count, customer_id, deposit_deducted FROM orders WHERE table_id = ? AND status = 'Open' ORDER BY id DESC LIMIT 1 FOR UPDATE", [$from_id]);
            if (!$fromOrder) throw new Exception('Bàn nguồn hiện không có đơn hàng nào đang mở!');
            $toOrder = $this->one("SELECT id, order_code FROM orders WHERE table_id = ? AND status = 'Open' ORDER BY id DESC LIMIT 1 FOR UPDATE", [$to_id]);
            if ($mode === '') $mode = $toOrder ? 'merge' : 'transfer';

            if ($mode === 'merge') {
                if (!$toOrder) throw new Exception('Bàn đích chưa có đơn đang mở để gộp. Hãy dùng Chuyển bàn.');
                $this->db->prepare("UPDATE order_details SET order_id = ? WHERE order_id = ?")->execute([$toOrder['id'], $fromOrder['id']]);
                $this->db->prepare("UPDATE orders SET guest_count = guest_count + ?, deposit_deducted = deposit_deducted + ?, customer_id = COALESCE(customer_id, ?) WHERE id = ?")
                    ->execute([(int)$fromOrder['guest_count'], (float)$fromOrder['deposit_deducted'], $fromOrder['customer_id'], $toOrder['id']]);
                $this->db->prepare("UPDATE orders SET status = 'Cancelled', subtotal = 0, total_amount = 0, closed_at = NOW() WHERE id = ?")->execute([$fromOrder['id']]);
                $this->releaseTableSmart($from_id);
                $this->recalcSubtotal($toOrder['id']);
                $msg = 'Gộp bàn thành công!';
                $this->addLog($toOrder['id'], $user_id, 'MergeTable',
                    "Gộp bàn {$fromT['table_name']} (đơn {$fromOrder['order_code']}) vào bàn {$toT['table_name']} (đơn {$toOrder['order_code']}). Phụ thu tính theo khu vực {$toT['area_name']} (" . (float)$toT['surcharge_percent'] . '%)');
            } else {
                if ($toOrder || $toT['status'] !== 'Available') {
                    throw new Exception("Bàn {$toT['table_name']} không trống (" . ($toOrder ? 'đang có khách' : $toT['status']) . '), chỉ chuyển đến bàn trống!');
                }
                $this->db->prepare("UPDATE orders SET table_id = ? WHERE id = ?")->execute([$to_id, $fromOrder['id']]);
                $this->releaseTableSmart($from_id);
                $this->db->prepare("UPDATE dining_tables SET status = 'Occupied' WHERE id = ?")->execute([$to_id]);
                $msg = 'Chuyển bàn thành công!';
                $this->addLog($fromOrder['id'], $user_id, 'TransferTable',
                    "Chuyển bàn {$fromT['table_name']} ({$fromT['area_name']}, phụ thu " . (float)$fromT['surcharge_percent'] . "%) sang {$toT['table_name']} ({$toT['area_name']}, phụ thu " . (float)$toT['surcharge_percent'] . '%)');
            }
            $this->db->commit();
            return ['status' => 'success', 'message' => $msg];
        } catch (Exception $e) {
            return $this->fail($e);
        }
    }

    public function getBillData($order_id) {
        $order = $this->one("SELECT o.*, dt.table_name, a.name AS area_name, u.full_name AS waiter_name, c.full_name AS customer_name
                             FROM orders o
                             JOIN dining_tables dt ON o.table_id = dt.id
                             JOIN areas a ON dt.area_id = a.id
                             LEFT JOIN users u ON o.waiter_id = u.id
                             LEFT JOIN customers c ON o.customer_id = c.id
                             WHERE o.id = ?", [(int)$order_id]);
        if (!$order) return null;
        $details = $this->all("SELECT od.*, mi.name AS item_name, mi.unit
                               FROM order_details od JOIN menu_items mi ON od.menu_item_id = mi.id
                               WHERE od.order_id = ? AND od.kds_status <> 'Cancelled' ORDER BY od.ordered_at, od.id", [$order['id']]);
        return ['order' => $order, 'details' => $details];
    }

    public function getReportByPaymentMethod($fromDate = null, $toDate = null) {
        [$w, $p] = $this->dateRange('paid_at', $fromDate, $toDate);
        return $this->all("SELECT payment_method, COUNT(id) AS transaction_count, IFNULL(SUM(amount), 0) AS total_revenue
                           FROM payments WHERE 1=1 $w GROUP BY payment_method", $p);
    }

    public function getTimeShiftReport($fromDate = null, $toDate = null) {
        [$w, $p] = $this->dateRange('paid_at', $fromDate, $toDate);
        return $this->all("SELECT CASE WHEN HOUR(paid_at) < 14 THEN 'Ca sáng (đến 14h)' WHEN HOUR(paid_at) < 18 THEN 'Ca chiều (14h–18h)' ELSE 'Ca tối (từ 18h)' END AS shift_name,
                                  COUNT(id) AS transaction_count, IFNULL(SUM(amount), 0) AS total_revenue
                           FROM payments WHERE 1=1 $w GROUP BY shift_name ORDER BY MIN(HOUR(paid_at))", $p);
    }

    public function getCancelLogsReport($fromDate = null, $toDate = null) {
        [$w, $p] = $this->dateRange('ol.created_at', $fromDate, $toDate);
        return $this->all("SELECT ol.id, ol.order_id, o.order_code, dt.table_name, u.full_name AS staff_name, ol.description, ol.created_at
                           FROM order_logs ol
                           LEFT JOIN users u ON ol.user_id = u.id
                           LEFT JOIN orders o ON ol.order_id = o.id
                           LEFT JOIN dining_tables dt ON o.table_id = dt.id
                           WHERE ol.action_type = 'CancelItem' $w ORDER BY ol.id DESC", $p);
    }

    public function getMoveLogsReport($fromDate = null, $toDate = null) {
        [$w, $p] = $this->dateRange('ol.created_at', $fromDate, $toDate);
        return $this->all("SELECT ol.id, ol.action_type, o.order_code, u.full_name AS staff_name, ol.description, ol.created_at
                           FROM order_logs ol
                           LEFT JOIN users u ON ol.user_id = u.id
                           LEFT JOIN orders o ON ol.order_id = o.id
                           WHERE ol.action_type IN ('TransferTable', 'MergeTable') $w ORDER BY ol.id DESC", $p);
    }

    public function getShiftRevenueReport($fromDate = null, $toDate = null) {
        [$w, $p] = $this->dateRange('cs.opened_at', $fromDate, $toDate);
        return $this->all("SELECT cs.id AS shift_id, u.full_name AS cashier_name, cs.opened_at, cs.closed_at, cs.opening_cash, cs.total_cash_sales, cs.status,
                  IFNULL((SELECT SUM(x.amount) FROM payments x WHERE x.paid_at >= cs.opened_at AND x.paid_at <= IFNULL(cs.closed_at, NOW())), 0) AS total_revenue,
                  IFNULL((SELECT SUM(x.amount) FROM payments x WHERE x.payment_method = 'Cash' AND x.paid_at >= cs.opened_at AND x.paid_at <= IFNULL(cs.closed_at, NOW())), 0) AS cash_revenue
                           FROM cash_shifts cs JOIN users u ON cs.cashier_id = u.id
                           WHERE 1=1 $w ORDER BY cs.id DESC", $p);
    }
}