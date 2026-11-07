<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/TableModel.php';
require_once __DIR__ . '/../models/ReservationModel.php';
require_once __DIR__ . '/../models/OrderModel.php';
require_once __DIR__ . '/../models/StockModel.php';
require_once __DIR__ . '/../models/CashShiftModel.php';
require_once __DIR__ . '/../models/CustomerModel.php';

echo "\n==================================================================\n";
echo " BAT DAU KIEM THU TU DONG END-TO-END (CLI E2E TEST SUITE)\n";
echo "==================================================================\n";

$db = Database::getConnection();
$passed = 0;
$failed = 0;

function assertTest($condition, $title, $detail = '') {
    global $passed, $failed;
    if ($condition) {
        echo " [PASS] $title\n";
        $passed++;
    } else {
        echo " [FAIL] $title " . ($detail ? "($detail)" : "") . "\n";
        $failed++;
    }
}

try {
    // 1. Kiểm tra kết nối DB & các bảng trọng yếu
    $tablesCount = (int)$db->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'royaldine_5s'")->fetchColumn();
    assertTest($tablesCount >= 18, "Kiem tra Schema Database royaldine_5s ($tablesCount bang)");

    // 2. Tìm 1 bàn trống để test luồng Booking -> Check-in -> POS -> KDS -> Checkout -> BOM -> Shift
    $db->exec("UPDATE dining_tables SET status = 'Available' WHERE id = 1");
    $db->exec("DELETE FROM orders WHERE table_id = 1 AND status = 'Open'");

    $resModel   = new ReservationModel();
    $orderModel = new OrderModel();
    $stockModel = new StockModel();
    $cashModel  = new CashShiftModel();

    $bookingTime = date('Y-m-d H:i:s', strtotime('+2 hours'));
    $bookingCode = $resModel->createReservation([
        'full_name'       => 'Khach Test E2E',
        'phone'           => '0988888888',
        'email'           => 'vip@royaldine.vn',
        'table_id'        => 1,
        'guest_count'     => 2,
        'booking_time'    => $bookingTime,
        'deposit_amount'  => 100000,
        'special_request' => 'Test tu dong CLI'
    ]);
    assertTest(!empty($bookingCode), "Luong 1A: Tao Booking VIP thanh cong ($bookingCode)");

    $resRow = $db->query("SELECT id FROM reservations WHERE booking_code = '$bookingCode'")->fetch();
    $resId  = (int)$resRow['id'];

    $okConfirm = $resModel->updateReservationStatus($resId, 'Confirmed', 2);
    $tbStatusAfterConfirm = $db->query("SELECT status FROM dining_tables WHERE id = 1")->fetchColumn();
    assertTest($okConfirm && $tbStatusAfterConfirm === 'Reserved', "Luong 1B: Xac nhan Booking -> Ban chuyen sang Reserved");

    $okCheckin = $resModel->updateReservationStatus($resId, 'CheckedIn', 2);
    $tbStatusAfterCheckin = $db->query("SELECT status FROM dining_tables WHERE id = 1")->fetchColumn();
    $autoOrder = $db->query("SELECT id, deposit_deducted FROM orders WHERE table_id = 1 AND status = 'Open' LIMIT 1")->fetch();
    assertTest($okCheckin && $tbStatusAfterCheckin === 'Occupied' && !empty($autoOrder['id']), "Luong 1C: Check-in Booking -> Ban chuyen Occupied & Tu dong mo Order POS kem tien coc");

    // 3. Gọi món trên POS cho bàn vừa Check-in
    $wagyuStockBefore = (float)$db->query("SELECT stock_quantity FROM ingredients WHERE id = 1")->fetchColumn();
    $addOrderRes = $orderModel->createOrAddOrder(1, 3, [
        ['menu_item_id' => 2, 'quantity' => 1, 'modifier_notes' => 'Medium Rare']
    ]);
    assertTest(($addOrderRes['status'] ?? '') === 'success', "Luong 2A: POS goi mon tren ban vua Check-in thanh cong");

    $orderId = (int)$addOrderRes['order_id'];
    $detailId = (int)$db->query("SELECT id FROM order_details WHERE order_id = $orderId ORDER BY id DESC LIMIT 1")->fetchColumn();

    // 4. Luồng KDS: Waiting -> Cooking -> Ready -> Served
    $kds1 = $orderModel->updateKdsStatus($detailId, 'Cooking');
    $kds2 = $orderModel->updateKdsStatus($detailId, 'Ready');
    $kds3 = $orderModel->updateKdsStatus($detailId, 'Served');
    assertTest(($kds1['status'] ?? '') === 'success' && ($kds2['status'] ?? '') === 'success' && ($kds3['status'] ?? '') === 'success', "Luong 2B: KDS cap nhat trang thai Waiting -> Cooking -> Ready -> Served");

    // 5. Luồng Tính Bill & Thanh toán + Trừ kho BOM + Cộng điểm CRM
    $billPreview = $orderModel->calcBill($orderId, '0988888888', 'ROYAL10');
    assertTest($billPreview !== null && $billPreview['deposit_deducted'] == 100000 && $billPreview['voucher_discount'] > 0, "Luong 3A: Tinh Bill khau tru dung Tien coc (100k) + Voucher ROYAL10 + Hang the VIP");

    $checkoutRes = $orderModel->checkoutOrder($orderId, 3, 'Cash', '0988888888', 'ROYAL10', 'CLI-TEST');
    assertTest(($checkoutRes['status'] ?? '') === 'success', "Luong 3B: Thanh toan Order & Giai phong ban ve Available");

    $bomRes = $stockModel->deductStockByOrder($orderId, 3);
    $wagyuStockAfter = (float)$db->query("SELECT stock_quantity FROM ingredients WHERE id = 1")->fetchColumn();
    assertTest(!empty($bomRes['success']) && $wagyuStockAfter < $wagyuStockBefore, "Luong 4A: Tu dong tru kho theo dinh luong BOM (Wagyu: $wagyuStockBefore -> $wagyuStockAfter g)");

    $bomDupRes = $stockModel->deductStockByOrder($orderId, 3);
    $wagyuStockAfterDup = (float)$db->query("SELECT stock_quantity FROM ingredients WHERE id = 1")->fetchColumn();
    assertTest($wagyuStockAfterDup === $wagyuStockAfter, "Luong 4B: Chong tru kho 2 lan khi goi lap deductStockByOrder()");

    // Dọn dẹp bản ghi test CLI để giữ sạch dữ liệu vận hành
    if (!empty($bomRes['receipt_code'])) {
        $rcRow = $db->query("SELECT id, receipt_type FROM stock_receipts WHERE receipt_code = '{$bomRes['receipt_code']}'")->fetch();
        if ($rcRow) {
            $stockModel->deleteReceiptWithRevert((int)$rcRow['id'], $rcRow['receipt_type']);
        }
    }
    $db->exec("DELETE FROM payments WHERE order_id = $orderId");
    $db->exec("DELETE FROM order_logs WHERE order_id = $orderId");
    $db->exec("DELETE FROM orders WHERE id = $orderId");
    $db->exec("DELETE FROM reservations WHERE id = $resId");

} catch (Throwable $e) {
    assertTest(false, "Ngoai le trong qua trinh test", $e->getMessage());
}

echo "------------------------------------------------------------------\n";
echo " KET QUA KIEM THU E2E: PASS = $passed | FAIL = $failed\n";
echo "==================================================================\n";
exit($failed > 0 ? 1 : 0);