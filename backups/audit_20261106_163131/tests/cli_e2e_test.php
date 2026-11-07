<?php
define('ROYALDINE_DB_NAME', 'royaldine_5s_e2e');

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/TableModel.php';
require_once __DIR__ . '/../models/ReservationModel.php';
require_once __DIR__ . '/../models/PromotionModel.php';
require_once __DIR__ . '/../models/OrderModel.php';
require_once __DIR__ . '/../models/StockModel.php';
require_once __DIR__ . '/../models/CashShiftModel.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$passed = 0;
$failed = 0;

function assertCheck(bool $condition, string $step, string $detail = ''): void
{
    global $passed, $failed;

    if ($condition) {
        $passed++;
        echo "[PASS] {$step}" . ($detail !== '' ? " -> {$detail}" : '') . PHP_EOL;
    } else {
        $failed++;
        echo "[FAIL] {$step}" . ($detail !== '' ? " -> {$detail}" : '') . PHP_EOL;
    }
}

function deleteFixtureIds(PDO $db, string $table, array $ids): void
{
    $allowed = [
        'payments', 'order_logs', 'order_details', 'orders', 'reservations',
        'promotions', 'recipes_bom', 'item_modifiers', 'menu_items',
        'categories', 'stock_receipt_details', 'stock_receipts',
        'cash_transactions', 'cash_shifts', 'dining_tables', 'areas',
        'customers', 'ingredients', 'suppliers', 'users', 'roles'
    ];

    if (!in_array($table, $allowed, true) || empty($ids)) {
        return;
    }

    $stmt = $db->prepare("DELETE FROM `{$table}` WHERE id = ?");
    foreach (array_unique(array_filter(array_map('intval', $ids))) as $id) {
        if ($id > 0) {
            $stmt->execute([$id]);
        }
    }
}

if (Database::getDatabaseName() !== 'royaldine_5s_e2e') {
    fwrite(STDERR, "SAFETY STOP: cli_e2e_test.php chi duoc phep chay tren royaldine_5s_e2e.
");
    exit(2);
}

$db = Database::getConnection();
$execId = 'E2E_' . date('Ymd_His') . '_' . strtoupper(bin2hex(random_bytes(3)));
$cleanup = [
    'payments' => [],
    'order_logs' => [],
    'order_details' => [],
    'orders' => [],
    'reservations' => [],
    'promotions' => [],
    'recipes_bom' => [],
    'menu_items' => [],
    'categories' => [],
    'stock_receipts' => [],
    'cash_transactions' => [],
    'cash_shifts' => [],
    'tables' => [],
    'areas' => [],
    'customers' => [],
    'ingredients' => [],
    'suppliers' => [],
    'users' => [],
    'roles' => []
];

try {
    // 00. Verify that the isolated database and required schema are available.
    $requiredTables = [
        'roles', 'users', 'areas', 'dining_tables', 'customers', 'reservations',
        'categories', 'menu_items', 'ingredients', 'suppliers', 'recipes_bom',
        'orders', 'order_details', 'payments', 'order_logs', 'promotions',
        'stock_receipts', 'stock_receipt_details', 'cash_shifts', 'cash_transactions'
    ];

    $schemaCheck = $db->prepare("
        SELECT COUNT(*)
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
    ");
    $missingTables = [];
    foreach ($requiredTables as $tableName) {
        $schemaCheck->execute([$tableName]);
        if ((int)$schemaCheck->fetchColumn() !== 1) {
            $missingTables[] = $tableName;
        }
    }
    assertCheck(empty($missingTables), '00. Required E2E schema exists', empty($missingTables) ? Database::getDatabaseName() : implode(', ', $missingTables));
    if ($missingTables) {
        throw new RuntimeException('Thiếu bảng trong database E2E. Hãy chạy migration cho royaldine_5s_e2e.');
    }

    // Recover only abandoned E2E shifts from interrupted runs in this disposable database.
    // Never touch shifts belonging to normal application users.
    $staleShiftIds = $db->query("
        SELECT cs.id
        FROM cash_shifts cs
        INNER JOIN users u ON u.id = cs.cashier_id
        WHERE cs.status = 'Open' AND LEFT(u.username, 4) = 'e2e_'
    ")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($staleShiftIds as $staleShiftId) {
        $db->prepare("DELETE FROM cash_transactions WHERE shift_id = ?")->execute([(int)$staleShiftId]);
        $db->prepare("DELETE FROM cash_shifts WHERE id = ? AND status = 'Open'")->execute([(int)$staleShiftId]);
    }

    // 01. Create an isolated role and user for this execution.
    $roleCode = 'E2E_' . strtoupper(substr(bin2hex(random_bytes(5)), 0, 10));
    $db->prepare("INSERT INTO roles (role_code, role_name) VALUES (?, ?)")
        ->execute([$roleCode, 'E2E Test Role']);
    $roleId = (int)$db->lastInsertId();
    $cleanup['roles'][] = $roleId;

    $username = 'e2e_' . strtolower(substr(bin2hex(random_bytes(5)), 0, 10));
    $passwordHash = password_hash('E2E_TEST_ONLY', PASSWORD_DEFAULT);
    $db->prepare("
        INSERT INTO users (role_id, full_name, username, password_hash, phone, is_active)
        VALUES (?, ?, ?, ?, ?, 1)
    ")->execute([$roleId, "E2E User {$execId}", $username, $passwordHash, '0900000000']);
    $userId = (int)$db->lastInsertId();
    $cleanup['users'][] = $userId;
    $_SESSION['user'] = [
        'id' => $userId,
        'full_name' => "E2E User {$execId}",
        'username' => $username,
        'role_code' => $roleCode
    ];
    assertCheck($userId > 0 && password_verify('E2E_TEST_ONLY', $passwordHash), '01. Create isolated test user', "user_id={$userId}");

    $tableModel = new TableModel();
    $reservationModel = new ReservationModel();
    $promotionModel = new PromotionModel();
    $orderModel = new OrderModel();
    $stockModel = new StockModel();
    $cashModel = new CashShiftModel();

    // 02-03. Create an area and two test tables through the actual model.
    $areaName = 'E2E Area ' . substr($execId, -8);
    assertCheck($tableModel->addArea($areaName, 10.0), '02. Create test area');
    $areaId = (int)$db->lastInsertId();
    $cleanup['areas'][] = $areaId;

    $tableName = 'E2E-' . substr($execId, -8);
    assertCheck($tableModel->addTable($areaId, $tableName, 6), '03. Create test dining table');
    $tableId = (int)$db->lastInsertId();
    $cleanup['tables'][] = $tableId;

    $tableRow = $tableModel->findTable($tableId);
    assertCheck($tableRow && $tableRow['status'] === 'Available', '04. Verify newly created table is Available', "table_id={$tableId}");

    // 05. Open a cash shift for the test user only.
    $shiftOpened = $cashModel->openShift($userId, 500000, "E2E shift {$execId}");
    $shift = $cashModel->getCurrentOpenShift($userId);
    $shiftId = (int)($shift['id'] ?? 0);
    if ($shiftId > 0) {
        $cleanup['cash_shifts'][] = $shiftId;
    }
    assertCheck($shiftOpened && $shiftId > 0, '05. Open isolated cash shift', "shift_id={$shiftId}");

    $cashInOk = $cashModel->createTransaction($userId, 'IN', 10000, 'E2E cash in');
    $shiftAfterCashIn = $cashModel->getCurrentOpenShift($userId);
    assertCheck(
        $cashInOk
        && (float)($shiftAfterCashIn['total_cash_in'] ?? 0) === 10000.0
        && (float)($shiftAfterCashIn['expected_closing_cash'] ?? 0) === 510000.0,
        '05A. Cash-in updates shift totals exactly once'
    );

    $cashOutOk = $cashModel->createTransaction($userId, 'OUT', 2500, 'E2E cash out');
    $shiftAfterCashOut = $cashModel->getCurrentOpenShift($userId);
    assertCheck(
        $cashOutOk
        && (float)($shiftAfterCashOut['total_cash_out'] ?? 0) === 2500.0
        && (float)($shiftAfterCashOut['expected_closing_cash'] ?? 0) === 507500.0,
        '05B. Cash-out updates shift totals exactly once'
    );

    // 06-10. Booking: availability -> create -> confirm -> check-in -> linked POS order.
    $testPhone = '09' . (string)random_int(10000000, 99999999);
    $testEmail = strtolower(substr(bin2hex(random_bytes(5)), 0, 10)) . '@royaldine.test';
    $bookingTime = date('Y-m-d H:i:s', strtotime('+3 hours'));

    $availableTables = $reservationModel->getAvailableTablesForBooking($bookingTime, 4);
    $availableIds = array_map(static fn($row) => (int)$row['id'], $availableTables);
    assertCheck(in_array($tableId, $availableIds, true), '06. Table is returned by booking availability query', "table_id={$tableId}");

    $bookingCode = $reservationModel->createReservation([
        'full_name' => "E2E Customer {$execId}",
        'phone' => $testPhone,
        'email' => $testEmail,
        'table_id' => $tableId,
        'guest_count' => 4,
        'booking_time' => $bookingTime,
        'deposit_amount' => 50000,
        'special_request' => "E2E fixture {$execId}"
    ]);

    $stmtReservation = $db->prepare("SELECT id, customer_id, status FROM reservations WHERE booking_code = ?");
    $stmtReservation->execute([$bookingCode]);
    $reservationRow = $stmtReservation->fetch(PDO::FETCH_ASSOC) ?: [];
    $reservationId = (int)($reservationRow['id'] ?? 0);
    $customerId = (int)($reservationRow['customer_id'] ?? 0);
    if ($reservationId > 0) $cleanup['reservations'][] = $reservationId;
    if ($customerId > 0) $cleanup['customers'][] = $customerId;

    assertCheck($reservationId > 0 && ($reservationRow['status'] ?? '') === 'Pending', '07. Create future reservation', $bookingCode);

    $confirmed = $reservationModel->updateReservationStatus($reservationId, 'Confirmed', $userId);
    $tableAfterConfirm = $tableModel->findTable($tableId);
    assertCheck($confirmed && ($tableAfterConfirm['status'] ?? '') === 'Reserved', '08. Confirm reservation and reserve the table');

    $checkedIn = $reservationModel->updateReservationStatus($reservationId, 'CheckedIn', $userId);
    $tableAfterCheckin = $tableModel->findTable($tableId);
    $stmtOrder = $db->prepare("
        SELECT id, deposit_deducted, status
        FROM orders
        WHERE reservation_id = ? AND table_id = ? AND status = 'Open'
        ORDER BY id DESC LIMIT 1
    ");
    $stmtOrder->execute([$reservationId, $tableId]);
    $orderRow = $stmtOrder->fetch(PDO::FETCH_ASSOC) ?: [];
    $orderId = (int)($orderRow['id'] ?? 0);
    if ($orderId > 0) $cleanup['orders'][] = $orderId;

    assertCheck($checkedIn, '09. Check in reservation');
    assertCheck(($tableAfterCheckin['status'] ?? '') === 'Occupied', '10. Check-in changes table to Occupied');
    assertCheck($orderId > 0 && (float)$orderRow['deposit_deducted'] === 50000.0, '11. Check-in creates linked POS order with deposit', "order_id={$orderId}");

    // 12-15. Create isolated supplier, ingredient, import receipt, menu item and BOM.
    $supplierPhone = '08' . (string)random_int(10000000, 99999999);
    $stockModel->insertSupplier([
        'name' => "E2E Supplier {$execId}",
        'contact_person' => 'E2E Contact',
        'phone' => $supplierPhone,
        'address' => 'E2E Test Address',
        'debt_balance' => 0,
        'status' => 'active'
    ]);
    $supplierId = (int)$db->lastInsertId();
    $cleanup['suppliers'][] = $supplierId;
    assertCheck($supplierId > 0, '12. Create isolated supplier');

    $ingredientCode = 'E2E' . substr(strtoupper(bin2hex(random_bytes(4))), 0, 8);
    $stockModel->insertIngredient([
        'ingredient_code' => $ingredientCode,
        'name' => "E2E Ingredient {$execId}",
        'group_name' => 'E2E Test',
        'unit' => 'g',
        'min_stock_level' => 100,
        'cost_price' => 2000,
        'supplier_id' => $supplierId
    ]);
    $ingredientId = (int)$db->lastInsertId();
    $cleanup['ingredients'][] = $ingredientId;
    assertCheck($ingredientId > 0, '13. Create isolated ingredient');

    $importCode = 'E2EIN' . substr(strtoupper(bin2hex(random_bytes(4))), 0, 8);
    $importOk = $stockModel->createImportReceipt([
        'receipt_code' => $importCode,
        'supplier_id' => $supplierId,
        'user_id' => $userId,
        'total_amount' => 2000000,
        'note' => "E2E stock import {$execId}",
        'created_at' => date('Y-m-d H:i:s')
    ], [
        ['ingredient_id' => $ingredientId, 'quantity' => 1000, 'unit_cost' => 2000]
    ]);
    $importReceipt = $stockModel->getReceiptByCode($importCode);
    if ($importReceipt) $cleanup['stock_receipts'][] = (int)$importReceipt['id'];
    $ingredientAfterImport = $stockModel->getIngredientById($ingredientId);
    assertCheck($importOk && (float)$ingredientAfterImport['stock_quantity'] === 1000.0, '14. Import stock increases ingredient quantity', '1000g');

    $categoryName = 'E2E Category ' . substr($execId, -8);
    $db->prepare("INSERT INTO categories (name, course_type) VALUES (?, 'Main_Course')")
        ->execute([$categoryName]);
    $categoryId = (int)$db->lastInsertId();
    $cleanup['categories'][] = $categoryId;

    $menuCode = 'E2EM' . substr(strtoupper(bin2hex(random_bytes(4))), 0, 8);
    $db->prepare("
        INSERT INTO menu_items (category_id, item_code, name, unit, price, status)
        VALUES (?, ?, ?, 'Phần', 100000, 'Available')
    ")->execute([$categoryId, $menuCode, "E2E Dish {$execId}"]);
    $menuItemId = (int)$db->lastInsertId();
    $cleanup['menu_items'][] = $menuItemId;

    $db->prepare("
        INSERT INTO recipes_bom (menu_item_id, ingredient_id, quantity_required, wastage_percent)
        VALUES (?, ?, 200, 0)
    ")->execute([$menuItemId, $ingredientId]);
    $cleanup['recipes_bom'][] = (int)$db->lastInsertId();
    assertCheck($menuItemId > 0, '15. Create test menu item and BOM');

    // 16-18. POS and KDS lifecycle.
    $addResult = $orderModel->createOrAddOrder($tableId, $userId, [
        ['menu_item_id' => $menuItemId, 'quantity' => 1, 'modifier_notes' => 'E2E test']
    ]);
    $detailStmt = $db->prepare("SELECT id, kds_status FROM order_details WHERE order_id = ? ORDER BY id DESC LIMIT 1");
    $detailStmt->execute([$orderId]);
    $detail = $detailStmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $detailId = (int)($detail['id'] ?? 0);
    if ($detailId > 0) $cleanup['order_details'][] = $detailId;
    assertCheck(($addResult['status'] ?? '') === 'success' && (int)($addResult['order_id'] ?? 0) === $orderId, '16. Add menu item to booking-linked POS order');

    $kdsList = $orderModel->getKDSList();
    $kdsFound = false;
    foreach ($kdsList as $kdsRow) {
        if ((int)$kdsRow['id'] === $detailId && (int)$kdsRow['order_id'] === $orderId) {
            $kdsFound = true;
            break;
        }
    }
    assertCheck($kdsFound && ($detail['kds_status'] ?? '') === 'Waiting', '17. POS item appears on KDS in Waiting state');

    $kdsCooking = $orderModel->updateKdsStatus($detailId, 'Cooking');
    $kdsReady = $orderModel->updateKdsStatus($detailId, 'Ready');
    $kdsServed = $orderModel->updateKdsStatus($detailId, 'Served');
    assertCheck(
        ($kdsCooking['status'] ?? '') === 'success'
        && ($kdsReady['status'] ?? '') === 'success'
        && ($kdsServed['status'] ?? '') === 'success',
        '18. KDS transitions Waiting -> Cooking -> Ready -> Served'
    );

    // 19-21. Voucher, bill calculation, checkout, payment, CRM and inventory integration.
    $promoCode = 'E2EP' . substr(strtoupper(bin2hex(random_bytes(4))), 0, 8);
    $promotionModel->savePromotion([
        'code' => $promoCode,
        'name' => "E2E Promotion {$execId}",
        'discount_type' => 'Percent',
        'discount_value' => 10,
        'max_discount' => 20000,
        'min_order_value' => 50000,
        'min_tier' => 'Standard',
        'start_date' => date('Y-m-d H:i:s', strtotime('-1 day')),
        'end_date' => date('Y-m-d H:i:s', strtotime('+2 days')),
        'is_active' => 1
    ]);
    $promotionId = (int)$db->lastInsertId();
    $cleanup['promotions'][] = $promotionId;

    $voucherCheck = $promotionModel->validateVoucher($promoCode, 100000, 'Standard');
    assertCheck(!empty($voucherCheck['valid']), '19. Validate active promotion');

    $bill = $orderModel->calcBill($orderId, $testPhone, $promoCode);
    assertCheck(
        $bill !== null
        && (float)$bill['subtotal'] === 100000.0
        && (float)$bill['deposit_deducted'] === 50000.0
        && (float)$bill['voucher_discount'] > 0,
        '20. Calculate bill with voucher and reservation deposit'
    );

    $paymentRef = 'E2E_TX_' . substr(strtoupper(bin2hex(random_bytes(5))), 0, 10);
    $checkout = $orderModel->checkoutOrder($orderId, $userId, 'Cash', $testPhone, $promoCode, $paymentRef);
    assertCheck(($checkout['status'] ?? '') === 'success', '21. Complete POS checkout', 'paid=' . ($checkout['total_amount'] ?? 0));

    $paymentStmt = $db->prepare("SELECT id, amount FROM payments WHERE order_id = ? ORDER BY id DESC LIMIT 1");
    $paymentStmt->execute([$orderId]);
    $payment = $paymentStmt->fetch(PDO::FETCH_ASSOC) ?: [];
    if (!empty($payment['id'])) $cleanup['payments'][] = (int)$payment['id'];

    $orderStatusStmt = $db->prepare("SELECT status FROM orders WHERE id = ?");
    $orderStatusStmt->execute([$orderId]);
    $paidStatus = $orderStatusStmt->fetchColumn();

    $tableAfterPaid = $tableModel->findTable($tableId);
    $customerStmt = $db->prepare("SELECT points, total_spent FROM customers WHERE id = ?");
    $customerStmt->execute([$customerId]);
    $customerAfter = $customerStmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $ingredientAfterSale = $stockModel->getIngredientById($ingredientId);
    assertCheck(!empty($payment) && (float)$payment['amount'] > 0, '22. Payment transaction is persisted');
    assertCheck($paidStatus === 'Paid', '23. Order status becomes Paid');
    assertCheck(($tableAfterPaid['status'] ?? '') === 'Available', '24. Table is released after successful checkout');
    assertCheck((float)($customerAfter['total_spent'] ?? 0) > 0, '25. Customer CRM spending history is updated');
    assertCheck((float)($ingredientAfterSale['stock_quantity'] ?? -1) === 800.0, '26. BOM deducts 200g from inventory', '1000g -> 800g');

    $duplicateDeduction = $stockModel->deductStockByOrder($orderId, $userId);
    $ingredientAfterDuplicate = $stockModel->getIngredientById($ingredientId);
    assertCheck(
        !empty($duplicateDeduction['duplicate'])
        && (float)$ingredientAfterDuplicate['stock_quantity'] === 800.0,
        '27. Repeated stock deduction is idempotent'
    );

    $shiftAfterSale = $cashModel->getCurrentOpenShift($userId);
    $salesRecorded = (float)($shiftAfterSale['total_cash_sales'] ?? 0);
    assertCheck($salesRecorded > 0, '28. Cash shift records POS cash sales');

    // 29. Validate duplicate/invalid operations are rejected without corrupting the fixture.
    $duplicateCheckout = $orderModel->checkoutOrder($orderId, $userId, 'Cash', $testPhone, '', 'E2E_DUPLICATE');
    assertCheck(($duplicateCheckout['status'] ?? '') === 'error', '29. Reject duplicate checkout on an already Paid order');

    $invalidKds = $orderModel->updateKdsStatus($detailId, 'Cooking');
    assertCheck(($invalidKds['status'] ?? '') === 'error', '30. Reject invalid KDS transition Served -> Cooking');

    $overlapRejected = false;
    try {
        $reservationModel->createReservation([
            'full_name' => "E2E Overlap {$execId}",
            'phone' => $testPhone,
            'email' => $testEmail,
            'table_id' => $tableId,
            'guest_count' => 2,
            'booking_time' => date('Y-m-d H:i:s', strtotime($bookingTime . ' +1 hour')),
            'deposit_amount' => 0,
            'special_request' => 'Negative overlapping reservation test'
        ]);
    } catch (InvalidArgumentException $e) {
        $overlapRejected = true;
    }
    assertCheck($overlapRejected, '31. Reject overlapping reservation on the same table');

    $capacityRejected = false;
    try {
        $reservationModel->createReservation([
            'full_name' => "E2E Capacity {$execId}",
            'phone' => $testPhone,
            'email' => $testEmail,
            'table_id' => $tableId,
            'guest_count' => 99,
            'booking_time' => date('Y-m-d H:i:s', strtotime('+8 hours')),
            'deposit_amount' => 0,
            'special_request' => 'Negative capacity test'
        ]);
    } catch (InvalidArgumentException $e) {
        $capacityRejected = true;
    }
    assertCheck($capacityRejected, '32. Reject reservation exceeding table capacity');

    $invalidTableRejected = false;
    try {
        $reservationModel->createReservation([
            'full_name' => "E2E Invalid Table {$execId}",
            'phone' => $testPhone,
            'email' => $testEmail,
            'table_id' => 999999999,
            'guest_count' => 2,
            'booking_time' => date('Y-m-d H:i:s', strtotime('+9 hours')),
            'deposit_amount' => 0
        ]);
    } catch (InvalidArgumentException $e) {
        $invalidTableRejected = true;
    }
    assertCheck($invalidTableRejected, '33. Reject reservation with invalid table ID');

    // 34. Close the test shift and reject a second close.
    $shiftToClose = $cashModel->getCurrentOpenShift($userId);
    $expectedCash = (float)($shiftToClose['opening_cash'] ?? 0)
        + (float)($shiftToClose['total_cash_sales'] ?? 0)
        + (float)($shiftToClose['total_cash_in'] ?? 0)
        - (float)($shiftToClose['total_cash_out'] ?? 0);
    $closed = $cashModel->closeShift($shiftId, $expectedCash, 'E2E close');
    $closedAgain = $cashModel->closeShift($shiftId, $expectedCash, 'E2E duplicate close');
    assertCheck($closed === true && $closedAgain === false, '34. Close cash shift once and reject duplicate close');

    // 35. Verify payment reporting sees the completed test payment.
    $reportRows = $orderModel->getReportByPaymentMethod(date('Y-m-d'), date('Y-m-d'));
    assertCheck(is_array($reportRows), '35. POS payment report query executes');
} catch (Throwable $e) {
    assertCheck(false, 'Unhandled exception during integrated E2E', $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
} finally {
    // Cleanup only IDs recorded by this execution, in foreign-key-safe order.
    try {
        if ($db->inTransaction()) {
            $db->rollBack();
        }

        // Include any stock receipts created by the BOM deduction during checkout.
        if (!empty($orderId)) {
            $stmtReceipts = $db->prepare("SELECT id FROM stock_receipts WHERE order_id = ?");
            $stmtReceipts->execute([(int)$orderId]);
            foreach ($stmtReceipts->fetchAll(PDO::FETCH_COLUMN) as $receiptId) {
                $cleanup['stock_receipts'][] = (int)$receiptId;
            }
        }

        deleteFixtureIds($db, 'payments', $cleanup['payments']);
        deleteFixtureIds($db, 'order_logs', $cleanup['order_logs']);
        deleteFixtureIds($db, 'order_details', $cleanup['order_details']);

        if (!empty($orderId)) {
            $db->prepare("DELETE FROM order_logs WHERE order_id = ?")->execute([(int)$orderId]);
            $db->prepare("DELETE FROM order_details WHERE order_id = ?")->execute([(int)$orderId]);
            $db->prepare("DELETE FROM orders WHERE id = ?")->execute([(int)$orderId]);
        }
        deleteFixtureIds($db, 'orders', $cleanup['orders']);
        deleteFixtureIds($db, 'reservations', $cleanup['reservations']);
        deleteFixtureIds($db, 'promotions', $cleanup['promotions']);
        deleteFixtureIds($db, 'recipes_bom', $cleanup['recipes_bom']);

        // Remove receipt details before receipts and ingredients.
        foreach (array_unique(array_filter(array_map('intval', $cleanup['stock_receipts']))) as $receiptId) {
            $db->prepare("DELETE FROM stock_receipt_details WHERE stock_receipt_id = ?")->execute([$receiptId]);
            $db->prepare("DELETE FROM stock_receipts WHERE id = ?")->execute([$receiptId]);
        }

        deleteFixtureIds($db, 'item_modifiers', []);
        if (!empty($menuItemId)) {
            $db->prepare("DELETE FROM item_modifiers WHERE menu_item_id = ?")->execute([(int)$menuItemId]);
            $db->prepare("DELETE FROM menu_items WHERE id = ?")->execute([(int)$menuItemId]);
        }
        deleteFixtureIds($db, 'menu_items', $cleanup['menu_items']);
        deleteFixtureIds($db, 'categories', $cleanup['categories']);
        deleteFixtureIds($db, 'reservations', []);

        if (!empty($shiftId)) {
            $db->prepare("DELETE FROM cash_transactions WHERE shift_id = ?")->execute([(int)$shiftId]);
            $db->prepare("DELETE FROM cash_shifts WHERE id = ?")->execute([(int)$shiftId]);
        }
        deleteFixtureIds($db, 'cash_shifts', $cleanup['cash_shifts']);
        deleteFixtureIds($db, 'dining_tables', $cleanup['tables']);
        deleteFixtureIds($db, 'areas', $cleanup['areas']);
        deleteFixtureIds($db, 'customers', $cleanup['customers']);
        deleteFixtureIds($db, 'ingredients', $cleanup['ingredients']);
        deleteFixtureIds($db, 'suppliers', $cleanup['suppliers']);
        deleteFixtureIds($db, 'users', $cleanup['users']);
        deleteFixtureIds($db, 'roles', $cleanup['roles']);

        assertCheck(true, '36. Cleanup only test fixture records', $execId);
    } catch (Throwable $cleanupError) {
        assertCheck(false, '36. Cleanup test fixture records', $cleanupError->getMessage());
    }
}

echo str_repeat('=', 72) . PHP_EOL;
echo "E2E RESULT: PASS={$passed} | FAIL={$failed}" . PHP_EOL;
echo str_repeat('=', 72) . PHP_EOL;
exit($failed > 0 ? 1 : 0);