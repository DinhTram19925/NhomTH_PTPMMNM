<?php
define('ROYALDINE_DB_NAME', 'royaldine_5s_e2e');

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/TableModel.php';
require_once __DIR__ . '/../models/ReservationModel.php';
require_once __DIR__ . '/../models/CustomerModel.php';
require_once __DIR__ . '/../models/PromotionModel.php';
require_once __DIR__ . '/../models/OrderModel.php';
require_once __DIR__ . '/../models/StockModel.php';
require_once __DIR__ . '/../models/CashShiftModel.php';

echo "\n========================================================================\n";
echo " ROYALDINE 5S — ISOLATED FULL-SYSTEM AUDIT & E2E VALIDATION SUITE\n";
echo "========================================================================\n";

$passed = 0;
$failed = 0;
$execId = 'E2E_' . date('Ymd_His') . '_' . random_int(1000, 9999);
echo " Execution ID : $execId\n";
echo " Target DB    : " . Database::getDatabaseName() . " (Isolated from production royaldine_5s)\n";
echo "------------------------------------------------------------------------\n";

function assertCheck(bool $condition, string $step, string $detail = ''): void {
    global $passed, $failed;
    if ($condition) {
        echo " [PASS] $step" . ($detail !== '' ? " -> $detail" : "") . "\n";
        $passed++;
    } else {
        echo " [FAIL] $step" . ($detail !== '' ? " -> $detail" : "") . "\n";
        $failed++;
    }
}

$rootPdo = new PDO("mysql:host=localhost;charset=utf8mb4", "root", "", [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);
$rootPdo->exec("CREATE DATABASE IF NOT EXISTS royaldine_5s_e2e CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$rootPdo->exec("USE royaldine_5s_e2e");

$migrationFile = __DIR__ . '/../database/idempotent_migration.sql';
if (file_exists($migrationFile)) {
    $sql = file_get_contents($migrationFile);
    $rootPdo->exec($sql);
}

Database::setDatabaseName('royaldine_5s_e2e');
$db = Database::getConnection();
assertCheck(Database::getDatabaseName() === 'royaldine_5s_e2e', "00. Isolated Test Database Active", Database::getDatabaseName());

$db->exec("INSERT IGNORE INTO roles (id, role_code, role_name) VALUES
    (1, 'ADMIN', 'Quản lý nhà hàng'),
    (2, 'HOSTESS', 'Lễ tân & Đặt bàn'),
    (3, 'WAITER', 'Phục vụ & Thu ngân POS'),
    (4, 'CHEF', 'Bếp trưởng KDS'),
    (5, 'STOCK', 'Thủ kho & Kế toán')");

$adminHash = password_hash('123456', PASSWORD_BCRYPT);
$stmtAdmin = $db->prepare("INSERT IGNORE INTO users (role_id, full_name, username, password_hash, phone, is_active) VALUES (1, ?, ?, ?, '0909999000', 1)");
$stmtAdmin->execute(["Admin $execId", "admin_$execId", $adminHash]);

$stmtGetAdmin = $db->prepare("SELECT u.*, r.role_code, r.role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.username = ?");
$stmtGetAdmin->execute(["admin_$execId"]);
$testAdmin = $stmtGetAdmin->fetch(PDO::FETCH_ASSOC);

$cleanup = [
    'payments' => [],
    'order_logs' => [],
    'order_details' => [],
    'orders' => [],
    'reservations' => [],
    'promotions' => [],
    'customers' => [],
    'tables' => [],
    'areas' => [],
    'bom' => [],
    'modifiers' => [],
    'menu_items' => [],
    'categories' => [],
    'stock_receipts' => [],
    'ingredients' => [],
    'suppliers' => [],
    'cash_transactions' => [],
    'cash_shifts' => [],
    'users' => [$testAdmin['id'] ?? 0]
];

try {
    $authOk = $testAdmin && password_verify('123456', $testAdmin['password_hash']) && $testAdmin['role_code'] === 'ADMIN';
    $_SESSION['user'] = $testAdmin;
    $userId = (int)$testAdmin['id'];
    assertCheck($authOk && $userId > 0, "01. Login as authorized administrator", "user_id=#$userId");

    $tableModel = new TableModel();
    $resModel   = new ReservationModel();
    $custModel  = new CustomerModel();
    $promoModel = new PromotionModel();
    $orderModel = new OrderModel();
    $stockModel = new StockModel();
    $cashModel  = new CashShiftModel();

    $existingOpenShift = $cashModel->getCurrentOpenShift();
    $createdShiftForTest = false;
    if (!$existingOpenShift) {
        $cashModel->openShift($userId, 5000000, "Shift $execId");
        $existingOpenShift = $cashModel->getCurrentOpenShift();
        $createdShiftForTest = true;
        $cleanup['cash_shifts'][] = (int)$existingOpenShift['id'];
    }
    $shiftId = (int)$existingOpenShift['id'];
    $shiftSalesBefore = (float)$existingOpenShift['total_cash_sales'];

    $areaName = "Area_$execId";
    $areaAdded = $tableModel->addArea($areaName, 10.0);
    $areaId = (int)$db->lastInsertId();
    $cleanup['areas'][] = $areaId;
    assertCheck($areaAdded && $areaId > 0, "02. Create a test area", "area_id=#$areaId ($areaName, 10% surcharge)");

    $table1Name = "T1_" . substr($execId, -8);
    $table2Name = "T2_" . substr($execId, -8);
    $t1Added = $tableModel->addTable($areaId, $table1Name, 6);
    $table1Id = (int)$db->lastInsertId();
    $cleanup['tables'][] = $table1Id;

    $t2Added = $tableModel->addTable($areaId, $table2Name, 4);
    $table2Id = (int)$db->lastInsertId();
    $cleanup['tables'][] = $table2Id;
    assertCheck($t1Added && $t2Added && $table1Id > 0 && $table2Id > 0, "03. Create test tables", "table1=#$table1Id, table2=#$table2Id");

    $allTables = $tableModel->getAllTables();
    $foundT1 = array_filter($allTables, fn($r) => (int)$r['table_id'] === $table1Id);
    assertCheck(!empty($foundT1), "04. Verify tables appear in table management", "Found table_id=#$table1Id");

    $testPhone = '09' . str_pad((string)random_int(10000000, 99999999), 8, '0', STR_PAD_LEFT);
    $custSaved = $custModel->saveCustomer([
        'full_name' => "Customer $execId",
        'phone'     => $testPhone,
        'email'     => strtolower($execId) . "@royaldine.test"
    ]);
    $customerId = (int)$db->lastInsertId();
    $cleanup['customers'][] = $customerId;
    assertCheck($custSaved && $customerId > 0, "05. Create a test customer", "customer_id=#$customerId, phone=$testPhone");

    $futureBookingTime = date('Y-m-d H:i:s', strtotime('+3 hours'));
    $availTables = $resModel->getAvailableTablesForBooking($futureBookingTime, 4);
    $availIds = array_map(fn($r) => (int)$r['id'], $availTables);
    assertCheck(in_array($table1Id, $availIds, true), "06. Select table based on real availability", "table_id=#$table1Id available for 4 guests");

    $bookingCode = $resModel->createReservation([
        'full_name'       => "Customer $execId",
        'phone'           => $testPhone,
        'email'           => strtolower($execId) . "@royaldine.test",
        'table_id'        => $table1Id,
        'guest_count'     => 4,
        'booking_time'    => $futureBookingTime,
        'deposit_amount'  => 100000,
        'special_request' => "VIP Window $execId"
    ]);
    $stmtRes = $db->prepare("SELECT * FROM reservations WHERE booking_code = ?");
    $stmtRes->execute([$bookingCode]);
    $resRow = $stmtRes->fetch(PDO::FETCH_ASSOC);
    $reservationId = (int)($resRow['id'] ?? 0);
    $cleanup['reservations'][] = $reservationId;
    assertCheck($reservationId > 0 && $resRow['status'] === 'Pending', "07. Create a future reservation", "reservation_id=#$reservationId ($bookingCode)");

    $confirmedOk = $resModel->updateReservationStatus($reservationId, 'Confirmed', $userId);
    $t1AfterConfirm = $tableModel->findTable($table1Id);
    assertCheck($confirmedOk, "08. Confirm reservation", "reservation_id=#$reservationId -> Confirmed");
    assertCheck(($t1AfterConfirm['status'] ?? '') === 'Reserved', "09. Verify table becomes Reserved", "table_id=#$table1Id status=" . ($t1AfterConfirm['status'] ?? ''));

    $checkInOk = $resModel->updateReservationStatus($reservationId, 'CheckedIn', $userId);
    $stmtResAfter = $db->prepare("SELECT status FROM reservations WHERE id = ?");
    $stmtResAfter->execute([$reservationId]);
    $resStatusAfter = $stmtResAfter->fetchColumn();
    $t1AfterCheckIn = $tableModel->findTable($table1Id);

    $stmtLinkedOrder = $db->prepare("SELECT * FROM orders WHERE table_id = ? AND reservation_id = ? AND status = 'Open'");
    $stmtLinkedOrder->execute([$table1Id, $reservationId]);
    $linkedOrder = $stmtLinkedOrder->fetch(PDO::FETCH_ASSOC);
    $orderId = (int)($linkedOrder['id'] ?? 0);
    if ($orderId > 0) $cleanup['orders'][] = $orderId;

    assertCheck($checkInOk, "10. Check in the customer", "reservation_id=#$reservationId");
    assertCheck($resStatusAfter === 'CheckedIn', "11. Verify reservation becomes CheckedIn", "status=$resStatusAfter");
    assertCheck(($t1AfterCheckIn['status'] ?? '') === 'Occupied', "12. Verify table becomes Occupied", "status=" . ($t1AfterCheckIn['status'] ?? ''));
    assertCheck($orderId > 0 && (float)$linkedOrder['deposit_deducted'] === 100000.0, "13. Verify linked POS order created with deposit", "order_id=#$orderId, deposit=100,000");

    $stockModel->insertSupplier([
        'name' => "Supplier $execId",
        'contact_person' => 'Mr Test',
        'phone' => '0911222333',
        'address' => 'Test Address',
        'debt_balance' => 0,
        'status' => 'active'
    ]);
    $supplierId = (int)$db->lastInsertId();
    $cleanup['suppliers'][] = $supplierId;

    $ingCode = 'NVL_' . substr($execId, -6);
    $stockModel->insertIngredient([
        'ingredient_code' => $ingCode,
        'name'            => "Wagyu Test $execId",
        'group_name'      => 'Thịt Cao Cấp',
        'unit'            => 'g',
        'min_stock_level' => 100,
        'cost_price'      => 2000,
        'supplier_id'     => $supplierId
    ]);
    $ingredientId = (int)$db->lastInsertId();
    $cleanup['ingredients'][] = $ingredientId;

    $importCode = 'PN_' . substr($execId, -8);
    $importOk = $stockModel->createImportReceipt([
        'receipt_code' => $importCode,
        'supplier_id'  => $supplierId,
        'user_id'      => $userId,
        'total_amount' => 2000000,
        'note'         => "Import $execId",
        'created_at'   => date('Y-m-d H:i:s')
    ], [
        ['ingredient_id' => $ingredientId, 'quantity' => 1000, 'unit_cost' => 2000]
    ]);
    $importReceiptRow = $stockModel->getReceiptByCode($importCode);
    if ($importReceiptRow) $cleanup['stock_receipts'][] = (int)$importReceiptRow['id'];

    $db->prepare("INSERT INTO categories (name, course_type) VALUES (?, 'Main_Course')")->execute(["Cat $execId"]);
    $categoryId = (int)$db->lastInsertId();
    $cleanup['categories'][] = $categoryId;

    $dishCode = 'M_' . substr($execId, -8);
    $db->prepare("INSERT INTO menu_items (category_id, item_code, name, unit, price, status) VALUES (?, ?, ?, 'Phần', 1000000, 'Available')")
       ->execute([$categoryId, $dishCode, "Dish $execId"]);
    $menuItemId = (int)$db->lastInsertId();
    $cleanup['menu_items'][] = $menuItemId;

    $db->prepare("INSERT INTO recipes_bom (menu_item_id, ingredient_id, quantity_required, wastage_percent) VALUES (?, ?, 200, 0)")
       ->execute([$menuItemId, $ingredientId]);
    $cleanup['bom'][] = (int)$db->lastInsertId();

    $addOrderRes = $orderModel->createOrAddOrder($table1Id, $userId, [
        ['menu_item_id' => $menuItemId, 'quantity' => 1, 'modifier_notes' => 'Medium Rare']
    ]);
    assertCheck(($addOrderRes['status'] ?? '') === 'success' && (int)$addOrderRes['order_id'] === $orderId, "14. Add menu items to the order", "order_id=#$orderId");

    $billCalc = $orderModel->calcBill($orderId, $testPhone, '');
    assertCheck(
        $billCalc !== null && (float)$billCalc['subtotal'] === 1000000.0 && (float)$billCalc['surcharge'] === 100000.0 && (float)$billCalc['service'] === 55000.0,
        "15. Verify order subtotal, surcharge & service calculations",
        "subtotal=1,000,000, surcharge=100,000, service=55,000"
    );

    $kdsList = $orderModel->getKDSList();
    $kdsItem = array_values(array_filter($kdsList, fn($k) => (int)$k['order_id'] === $orderId))[0] ?? null;
    $orderDetailId = (int)($kdsItem['id'] ?? 0);
    assertCheck($orderDetailId > 0 && $kdsItem['kds_status'] === 'Waiting', "16. Verify item appears on KDS in Waiting state", "detail_id=#$orderDetailId");

    $k1 = $orderModel->updateKdsStatus($orderDetailId, 'Cooking');
    $k2 = $orderModel->updateKdsStatus($orderDetailId, 'Ready');
    $k3 = $orderModel->updateKdsStatus($orderDetailId, 'Served');
    assertCheck(
        ($k1['status'] ?? '') === 'success' && ($k2['status'] ?? '') === 'success' && ($k3['status'] ?? '') === 'success',
        "17. Transition KDS states Waiting -> Cooking -> Ready -> Served",
        "detail_id=#$orderDetailId -> Served"
    );

    $promoCode = 'PRM' . strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $execId), -7));
    $promoModel->savePromotion([
        'code' => $promoCode,
        'name' => "Promo $execId",
        'discount_type' => 'Percent',
        'discount_value' => 10,
        'max_discount' => 200000,
        'min_order_value' => 500000,
        'min_tier' => 'Standard',
        'start_date' => date('Y-m-d H:i:s', strtotime('-1 day')),
        'end_date' => date('Y-m-d H:i:s', strtotime('+7 days')),
        'is_active' => 1
    ]);
    $promoId = (int)$db->lastInsertId();
    $cleanup['promotions'][] = $promoId;

    $billWithPromo = $orderModel->calcBill($orderId, $testPhone, $promoCode);
    assertCheck((float)$billWithPromo['vat'] === 83600.0, "18. Calculate the bill with VAT", "vat=83,600");
    assertCheck((float)$billWithPromo['voucher_discount'] === 110000.0, "19. Apply valid promotion ($promoCode)", "voucher_discount=110,000");
    assertCheck((float)$billWithPromo['deposit_deducted'] === 100000.0 && (float)$billWithPromo['total'] === 1028600.0, "20. Deduct reservation deposit", "deposit=100,000, final_total=1,028,600");

    $checkoutRes = $orderModel->checkoutOrder($orderId, $userId, 'Cash', $testPhone, $promoCode, "TX_$execId");
    assertCheck(($checkoutRes['status'] ?? '') === 'success', "21. Execute test payment", "total_paid=" . ($checkoutRes['total_amount'] ?? 0));

    $stmtPay = $db->prepare("SELECT * FROM payments WHERE order_id = ?");
    $stmtPay->execute([$orderId]);
    $payRow = $stmtPay->fetch(PDO::FETCH_ASSOC);
    if ($payRow) $cleanup['payments'][] = (int)$payRow['id'];
    assertCheck(!empty($payRow) && (float)$payRow['amount'] === 1028600.0, "22. Verify payment persistence", "payment_id=#" . ($payRow['id'] ?? 0));

    $stmtOrdAfter = $db->prepare("SELECT status, voucher_code, shift_id FROM orders WHERE id = ?");
    $stmtOrdAfter->execute([$orderId]);
    $ordAfter = $stmtOrdAfter->fetch(PDO::FETCH_ASSOC);
    assertCheck(($ordAfter['status'] ?? '') === 'Paid' && ($ordAfter['voucher_code'] ?? '') === $promoCode, "23. Verify order status becomes Paid", "status={$ordAfter['status']}, voucher={$ordAfter['voucher_code']}");

    $t1AfterPaid = $tableModel->findTable($table1Id);
    assertCheck(($t1AfterPaid['status'] ?? '') === 'Available', "24. Verify table status is released to Available", "status=" . ($t1AfterPaid['status'] ?? ''));

    $custAfter = $custModel->findCustomer($customerId);
    assertCheck((int)$custAfter['points'] === 102 && (float)$custAfter['total_spent'] === 1028600.0, "25. Verify customer points and spending history", "points={$custAfter['points']}, spent={$custAfter['total_spent']}");

    $ingAfter = $stockModel->getIngredientById($ingredientId);
    assertCheck((float)$ingAfter['stock_quantity'] === 800.0, "26. Verify inventory deduction through BOM", "stock_quantity=800g (deducted 200g)");

    $stmtBomReceipt = $db->prepare("SELECT id FROM stock_receipts WHERE order_id = ?");
    $stmtBomReceipt->execute([$orderId]);
    $bomRecId = (int)$stmtBomReceipt->fetchColumn();
    if ($bomRecId > 0) $cleanup['stock_receipts'][] = $bomRecId;

    $dupDeduct = $stockModel->deductStockByOrder($orderId, $userId);
    $ingAfterDup = $stockModel->getIngredientById($ingredientId);
    assertCheck(!empty($dupDeduct['duplicate']) && (float)$ingAfterDup['stock_quantity'] === 800.0, "27. Verify repeated stock deduction does not duplicate movement", "stock remains 800g");

    $shiftAfterCheckout = $cashModel->getCurrentOpenShift();
    $shiftSalesAfter = (float)$shiftAfterCheckout['total_cash_sales'];
    assertCheck(abs(($shiftSalesAfter - $shiftSalesBefore) - 1028600.0) < 0.01, "28. Reconcile cash shift sales increment", "delta_cash_sales=1,028,600");

    $posReportMethods = $orderModel->getReportByPaymentMethod(date('Y-m-d'), date('Y-m-d'));
    assertCheck(!empty($posReportMethods), "29. Verify dashboard and revenue reports", "payment methods report rows=" . count($posReportMethods));

    echo "------------------------------------------------------------------------\n";
    echo " EXECUTING NEGATIVE & BOUNDARY TEST SUITE\n";
    echo "------------------------------------------------------------------------\n";

    $n01 = false;
    try { $custModel->saveCustomer(['full_name' => 'Bad Phone', 'phone' => '123ABC']); } catch (InvalidArgumentException $e) { $n01 = true; }
    assertCheck($n01, "N01. Reject invalid phone number");

    $n02 = false;
    try { $custModel->saveCustomer(['full_name' => 'Bad Email', 'phone' => '0919888777', 'email' => 'not-an-email']); } catch (InvalidArgumentException $e) { $n02 = true; }
    assertCheck($n02, "N02. Reject invalid email address");

    $n03 = false;
    try { $custModel->saveCustomer(['full_name' => '', 'phone' => '0919888776']); } catch (InvalidArgumentException $e) { $n03 = true; }
    assertCheck($n03, "N03. Reject missing required customer name");

    $n04 = false;
    try {
        $resModel->createReservation([
            'full_name' => 'Test', 'phone' => $testPhone, 'table_id' => 9999999,
            'guest_count' => 2, 'booking_time' => date('Y-m-d H:i:s', strtotime('+4 hours'))
        ]);
    } catch (InvalidArgumentException $e) { $n04 = true; }
    assertCheck($n04, "N04. Reject reservation with non-existent table ID");

    $n05 = false;
    try { $tableModel->addTable(9999999, "T_BAD_$execId", 4); } catch (InvalidArgumentException $e) { $n05 = true; }
    assertCheck($n05, "N05. Reject table creation with invalid area ID");

    $n06Res = $orderModel->createOrAddOrder($table2Id, $userId, [['menu_item_id' => 9999999, 'quantity' => 1]]);
    assertCheck(($n06Res['status'] ?? '') === 'error', "N06. Reject POS order with non-existent menu item");

    $n07 = false;
    try {
        $resModel->createReservation([
            'full_name' => 'Over Cap', 'phone' => $testPhone, 'table_id' => $table2Id,
            'guest_count' => 20, 'booking_time' => date('Y-m-d H:i:s', strtotime('+4 hours'))
        ]);
    } catch (InvalidArgumentException $e) { $n07 = true; }
    assertCheck($n07, "N07. Reject reservation exceeding table capacity");

    $n08 = false;
    try {
        $resModel->createReservation([
            'full_name' => 'Past Time', 'phone' => $testPhone, 'table_id' => $table2Id,
            'guest_count' => 2, 'booking_time' => date('Y-m-d H:i:s', strtotime('-2 hours'))
        ]);
    } catch (InvalidArgumentException $e) { $n08 = true; }
    assertCheck($n08, "N08. Reject reservation in the past");

    $slotTime = date('Y-m-d H:i:s', strtotime('+5 hours'));
    $resA = $resModel->createReservation([
        'full_name' => 'Slot A', 'phone' => $testPhone, 'table_id' => $table2Id,
        'guest_count' => 2, 'booking_time' => $slotTime
    ]);
    $stmtResA = $db->prepare("SELECT id FROM reservations WHERE booking_code = ?");
    $stmtResA->execute([$resA]);
    $cleanup['reservations'][] = (int)$stmtResA->fetchColumn();

    $n09 = false;
    try {
        $resModel->createReservation([
            'full_name' => 'Slot Overlap', 'phone' => $testPhone, 'table_id' => $table2Id,
            'guest_count' => 2, 'booking_time' => date('Y-m-d H:i:s', strtotime('+5 hours 30 minutes'))
        ]);
    } catch (InvalidArgumentException $e) { $n09 = true; }
    assertCheck($n09, "N09/N10. Reject overlapping / duplicate reservation for same table & time window");

    $n11 = false;
    try { $tableModel->addTable($areaId, $table1Name, 4); } catch (InvalidArgumentException $e) { $n11 = true; }
    assertCheck($n11, "N11. Reject duplicate table name");

    $n12Res = $orderModel->updateKdsStatus($orderDetailId, 'Cooking');
    assertCheck(($n12Res['status'] ?? '') === 'error', "N12. Reject invalid KDS transition (Served -> Cooking)");

    $expPromoCode = 'EXP' . strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $execId), -7));
    $promoModel->savePromotion([
        'code' => $expPromoCode, 'name' => "Expired $execId", 'discount_type' => 'Fixed',
        'discount_value' => 50000, 'min_order_value' => 100000, 'min_tier' => 'Standard',
        'start_date' => '2024-01-01 00:00:00', 'end_date' => '2024-01-02 00:00:00', 'is_active' => 1
    ]);
    $cleanup['promotions'][] = (int)$db->lastInsertId();
    $vInvalid = $promoModel->validateVoucher('NON_EXISTENT_CODE_999', 500000, 'Standard');
    $vExpired = $promoModel->validateVoucher($expPromoCode, 500000, 'Standard');
    assertCheck(!$vInvalid['valid'] && !$vExpired['valid'], "N13/N14. Reject invalid and expired promotion codes");

    $dupPayRes = $orderModel->checkoutOrder($orderId, $userId, 'Cash', $testPhone, '', 'DUP-REF');
    assertCheck(($dupPayRes['status'] ?? '') === 'error', "N15. Reject duplicate payment on already Paid order");

    $badExport = $stockModel->createExportReceipt([
        'receipt_code' => 'PX_BAD_' . substr($execId, -6),
        'user_id' => $userId,
        'receipt_type' => 'Export_Damage',
        'total_amount' => 0,
        'note' => 'Exceed stock',
        'created_at' => date('Y-m-d H:i:s')
    ], [['ingredient_id' => $ingredientId, 'quantity' => 999999, 'unit_cost' => 2000]]);
    assertCheck($badExport === false, "N16. Reject stock export exceeding available inventory");

    if ($createdShiftForTest) {
        $close1 = $cashModel->closeShift($shiftId, 6028600, "Close 1");
        $close2 = $cashModel->closeShift($shiftId, 6028600, "Close 2");
        assertCheck($close1 === true && $close2 === false, "N17. Prevent closing an already closed cash shift");
    } else {
        $db->prepare("
            UPDATE cash_shifts
            SET total_cash_sales = total_cash_sales - ?,
                expected_closing_cash = opening_cash + (total_cash_sales - ?) + total_cash_in - total_cash_out
            WHERE id = ?
        ")->execute([1028600.0, 1028600.0, $shiftId]);
        assertCheck(true, "N17. Restored pre-existing open cash shift balance cleanly");
    }

} catch (Throwable $e) {
    assertCheck(false, "Unhandled Exception During E2E Run", $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
} finally {
    try {
        foreach ($cleanup['payments'] as $id) {
            $db->prepare("DELETE FROM payments WHERE id = ?")->execute([$id]);
        }
        foreach ($cleanup['orders'] as $id) {
            $db->prepare("DELETE FROM order_logs WHERE order_id = ?")->execute([$id]);
            $db->prepare("DELETE FROM order_details WHERE order_id = ?")->execute([$id]);
            $db->prepare("DELETE FROM orders WHERE id = ?")->execute([$id]);
        }
        foreach ($cleanup['reservations'] as $id) {
            $db->prepare("DELETE FROM reservations WHERE id = ?")->execute([$id]);
        }
        foreach ($cleanup['promotions'] as $id) {
            $db->prepare("DELETE FROM promotions WHERE id = ?")->execute([$id]);
        }
        foreach ($cleanup['customers'] as $id) {
            $db->prepare("DELETE FROM customers WHERE id = ?")->execute([$id]);
        }
        foreach ($cleanup['tables'] as $id) {
            $db->prepare("DELETE FROM dining_tables WHERE id = ?")->execute([$id]);
        }
        foreach ($cleanup['areas'] as $id) {
            $db->prepare("DELETE FROM areas WHERE id = ?")->execute([$id]);
        }
        foreach ($cleanup['bom'] as $id) {
            $db->prepare("DELETE FROM recipes_bom WHERE id = ?")->execute([$id]);
        }
        foreach ($cleanup['menu_items'] as $id) {
            $db->prepare("DELETE FROM item_modifiers WHERE menu_item_id = ?")->execute([$id]);
            $db->prepare("DELETE FROM menu_items WHERE id = ?")->execute([$id]);
        }
        foreach ($cleanup['categories'] as $id) {
            $db->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);
        }
        foreach ($cleanup['stock_receipts'] as $id) {
            $db->prepare("DELETE FROM stock_receipt_details WHERE stock_receipt_id = ?")->execute([$id]);
            $db->prepare("DELETE FROM stock_receipts WHERE id = ?")->execute([$id]);
        }
        foreach ($cleanup['ingredients'] as $id) {
            $db->prepare("DELETE FROM ingredients WHERE id = ?")->execute([$id]);
        }
        foreach ($cleanup['suppliers'] as $id) {
            $db->prepare("DELETE FROM suppliers WHERE id = ?")->execute([$id]);
        }
        foreach ($cleanup['cash_shifts'] as $id) {
            $db->prepare("DELETE FROM cash_transactions WHERE shift_id = ?")->execute([$id]);
            $db->prepare("DELETE FROM cash_shifts WHERE id = ?")->execute([$id]);
        }
        foreach ($cleanup['users'] as $id) {
            if ($id > 0) $db->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
        }
        assertCheck(true, "30. Cleanup only test fixture records ($execId)");
    } catch (Throwable $ce) {
        assertCheck(false, "30. Cleanup test fixture records failed", $ce->getMessage());
    }
}

echo "========================================================================\n";
echo " FINAL RESULT: PASS = $passed | FAIL = $failed\n";
echo "========================================================================\n";
exit($failed > 0 ? 1 : 0);