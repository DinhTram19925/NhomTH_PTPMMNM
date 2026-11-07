<?php
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
echo " ROYALDINE 5S — KIEM THU E2E TREN HE THONG CHINH (royaldine_5s)\n";
echo "========================================================================\n";

$passed = 0;
$failed = 0;
$execId = 'E2E_' . date('Ymd_His') . '_' . random_int(1000, 9999);
$db = Database::getConnection();

echo " Execution ID : $execId\n";
echo " Active DB    : " . Database::getDatabaseName() . "\n";
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

$tablesCount = (int)$db->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()")->fetchColumn();
assertCheck($tablesCount >= 19, "00. Kiem tra Schema CSDL chinh royaldine_5s", "$tablesCount bang");

$adminHash = password_hash('123456', PASSWORD_BCRYPT);
$stmtAdmin = $db->prepare("INSERT INTO users (role_id, full_name, username, password_hash, phone, is_active) VALUES (1, ?, ?, ?, '0909999000', 1)");
$stmtAdmin->execute(["Admin $execId", "admin_$execId", $adminHash]);
$testAdminId = (int)$db->lastInsertId();

$stmtGetAdmin = $db->prepare("SELECT u.*, r.role_code, r.role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?");
$stmtGetAdmin->execute([$testAdminId]);
$testAdmin = $stmtGetAdmin->fetch(PDO::FETCH_ASSOC);

$cleanup = [
    'payments' => [],
    'orders' => [],
    'reservations' => [],
    'promotions' => [],
    'customers' => [],
    'tables' => [],
    'areas' => [],
    'bom' => [],
    'menu_items' => [],
    'categories' => [],
    'stock_receipts' => [],
    'ingredients' => [],
    'suppliers' => [],
    'cash_shifts' => [],
    'users' => [$testAdminId]
];

try {
    $authOk = $testAdmin && password_verify('123456', $testAdmin['password_hash']) && $testAdmin['role_code'] === 'ADMIN';
    $_SESSION['user'] = $testAdmin;
    $userId = (int)$testAdmin['id'];
    assertCheck($authOk && $userId > 0, "01. Dang nhap Admin hop le", "user_id=#$userId");

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
    assertCheck($areaAdded && $areaId > 0, "02. Tao khu vuc moi", "area_id=#$areaId ($areaName, 10% phu thu)");

    $table1Name = "T1_" . substr($execId, -8);
    $table2Name = "T2_" . substr($execId, -8);
    $t1Added = $tableModel->addTable($areaId, $table1Name, 6);
    $table1Id = (int)$db->lastInsertId();
    $cleanup['tables'][] = $table1Id;

    $t2Added = $tableModel->addTable($areaId, $table2Name, 4);
    $table2Id = (int)$db->lastInsertId();
    $cleanup['tables'][] = $table2Id;
    assertCheck($t1Added && $t2Added && $table1Id > 0 && $table2Id > 0, "03. Tao ban an moi", "table1=#$table1Id, table2=#$table2Id");

    $allTables = $tableModel->getAllTables();
    $foundT1 = array_filter($allTables, fn($r) => (int)$r['table_id'] === $table1Id);
    assertCheck(!empty($foundT1), "04. Kiem tra ban hien thi tren So do ban", "table_id=#$table1Id");

    $testPhone = '09' . str_pad((string)random_int(10000000, 99999999), 8, '0', STR_PAD_LEFT);
    $custSaved = $custModel->saveCustomer([
        'full_name' => "Customer $execId",
        'phone'     => $testPhone,
        'email'     => strtolower($execId) . "@royaldine.test"
    ]);
    $customerId = (int)$db->lastInsertId();
    $cleanup['customers'][] = $customerId;
    assertCheck($custSaved && $customerId > 0, "05. Tao ho so khach hang CRM", "customer_id=#$customerId, phone=$testPhone");

    $futureBookingTime = date('Y-m-d H:i:s', strtotime('+3 hours'));
    $availTables = $resModel->getAvailableTablesForBooking($futureBookingTime, 4);
    $availIds = array_map(fn($r) => (int)$r['id'], $availTables);
    assertCheck(in_array($table1Id, $availIds, true), "06. Kiem tra ban trong kha dung khi dat ban", "table_id=#$table1Id");

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
    assertCheck($reservationId > 0 && $resRow['status'] === 'Pending', "07. Tao phieu dat ban (Pending)", "reservation_id=#$reservationId ($bookingCode)");

    $confirmedOk = $resModel->updateReservationStatus($reservationId, 'Confirmed', $userId);
    $t1AfterConfirm = $tableModel->findTable($table1Id);
    assertCheck($confirmedOk, "08. Xac nhan phieu dat ban (Confirmed)", "reservation_id=#$reservationId");
    assertCheck(($t1AfterConfirm['status'] ?? '') === 'Reserved', "09. Trang thai ban chuyen sang Reserved", "status=" . ($t1AfterConfirm['status'] ?? ''));

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

    assertCheck($checkInOk, "10. Check-in khach nhan ban", "reservation_id=#$reservationId");
    assertCheck($resStatusAfter === 'CheckedIn', "11. Trang thai phieu dat ban chuyen sang CheckedIn", "status=$resStatusAfter");
    assertCheck(($t1AfterCheckIn['status'] ?? '') === 'Occupied', "12. Trang thai ban chuyen sang Occupied", "status=" . ($t1AfterCheckIn['status'] ?? ''));
    assertCheck($orderId > 0 && (float)$linkedOrder['deposit_deducted'] === 100000.0, "13. Tu dong tao Order POS kem tien coc 100k", "order_id=#$orderId");

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
    $stockModel->createImportReceipt([
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
    assertCheck(($addOrderRes['status'] ?? '') === 'success' && (int)$addOrderRes['order_id'] === $orderId, "14. Goi mon tren POS vao Order vua Check-in", "order_id=#$orderId");

    $billCalc = $orderModel->calcBill($orderId, $testPhone, '');
    assertCheck(
        $billCalc !== null && (float)$billCalc['subtotal'] === 1000000.0 && (float)$billCalc['surcharge'] === 100000.0 && (float)$billCalc['service'] === 55000.0,
        "15. Tinh tam tinh, phu thu VIP 10% & phi phuc vu 5%",
        "subtotal=1,000,000, surcharge=100,000, service=55,000"
    );

    $kdsList = $orderModel->getKDSList();
    $kdsItem = array_values(array_filter($kdsList, fn($k) => (int)$k['order_id'] === $orderId))[0] ?? null;
    $orderDetailId = (int)($kdsItem['id'] ?? 0);
    assertCheck($orderDetailId > 0 && $kdsItem['kds_status'] === 'Waiting', "16. Mon hien thi tren man hinh bep KDS (Waiting)", "detail_id=#$orderDetailId");

    $k1 = $orderModel->updateKdsStatus($orderDetailId, 'Cooking');
    $k2 = $orderModel->updateKdsStatus($orderDetailId, 'Ready');
    $k3 = $orderModel->updateKdsStatus($orderDetailId, 'Served');
    assertCheck(
        ($k1['status'] ?? '') === 'success' && ($k2['status'] ?? '') === 'success' && ($k3['status'] ?? '') === 'success',
        "17. Chuyen trang thai KDS: Waiting -> Cooking -> Ready -> Served",
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
    assertCheck((float)$billWithPromo['vat'] === 83600.0, "18. Tinh thue VAT 8%", "vat=83,600");
    assertCheck((float)$billWithPromo['voucher_discount'] === 110000.0, "19. Ap dung ma khuyen mai ($promoCode)", "voucher_discount=110,000");
    assertCheck((float)$billWithPromo['deposit_deducted'] === 100000.0 && (float)$billWithPromo['total'] === 1028600.0, "20. Khau tru tien dat coc 100k", "final_total=1,028,600");

    $checkoutRes = $orderModel->checkoutOrder($orderId, $userId, 'Cash', $testPhone, $promoCode, "TX_$execId");
    assertCheck(($checkoutRes['status'] ?? '') === 'success', "21. Thanh toan hoa don POS (Checkout)", "total_paid=" . ($checkoutRes['total_amount'] ?? 0));

    $stmtPay = $db->prepare("SELECT * FROM payments WHERE order_id = ?");
    $stmtPay->execute([$orderId]);
    $payRow = $stmtPay->fetch(PDO::FETCH_ASSOC);
    if ($payRow) $cleanup['payments'][] = (int)$payRow['id'];
    assertCheck(!empty($payRow) && (float)$payRow['amount'] === 1028600.0, "22. Luu giao dich thanh toan vao bang payments", "payment_id=#" . ($payRow['id'] ?? 0));

    $stmtOrdAfter = $db->prepare("SELECT status, voucher_code, shift_id FROM orders WHERE id = ?");
    $stmtOrdAfter->execute([$orderId]);
    $ordAfter = $stmtOrdAfter->fetch(PDO::FETCH_ASSOC);
    assertCheck(($ordAfter['status'] ?? '') === 'Paid' && ($ordAfter['voucher_code'] ?? '') === $promoCode, "23. Cap nhat trang thai Order = Paid & luu voucher_code", "status={$ordAfter['status']}, voucher={$ordAfter['voucher_code']}");

    $t1AfterPaid = $tableModel->findTable($table1Id);
    assertCheck(($t1AfterPaid['status'] ?? '') === 'Available', "24. Giai phong ban ve Available sau thanh toan", "status=" . ($t1AfterPaid['status'] ?? ''));

    $custAfter = $custModel->findCustomer($customerId);
    assertCheck((int)$custAfter['points'] === 102 && (float)$custAfter['total_spent'] === 1028600.0, "25. Cong diem tich luy & chi tieu CRM", "points={$custAfter['points']}, spent={$custAfter['total_spent']}");

    $ingAfter = $stockModel->getIngredientById($ingredientId);
    assertCheck((float)$ingAfter['stock_quantity'] === 800.0, "26. Tu dong tru kho theo dinh luong BOM", "stock_quantity=800g (da tru 200g)");

    $stmtBomReceipt = $db->prepare("SELECT id FROM stock_receipts WHERE order_id = ?");
    $stmtBomReceipt->execute([$orderId]);
    $bomRecId = (int)$stmtBomReceipt->fetchColumn();
    if ($bomRecId > 0) $cleanup['stock_receipts'][] = $bomRecId;

    $dupDeduct = $stockModel->deductStockByOrder($orderId, $userId);
    $ingAfterDup = $stockModel->getIngredientById($ingredientId);
    assertCheck(!empty($dupDeduct['duplicate']) && (float)$ingAfterDup['stock_quantity'] === 800.0, "27. Chong tru kho 2 lan cho cung 1 hoa don", "stock=800g");

    $shiftAfterCheckout = $cashModel->getCurrentOpenShift();
    $shiftSalesAfter = (float)$shiftAfterCheckout['total_cash_sales'];
    assertCheck(abs(($shiftSalesAfter - $shiftSalesBefore) - 1028600.0) < 0.01, "28. Doi soat doanh thu tien mat trong Ca thu ngan", "delta_cash_sales=1,028,600");

    $posReportMethods = $orderModel->getReportByPaymentMethod(date('Y-m-d'), date('Y-m-d'));
    assertCheck(!empty($posReportMethods), "29. Kiem tra bao cao doanh thu POS", "rows=" . count($posReportMethods));

    if (!$createdShiftForTest) {
        $db->prepare("
            UPDATE cash_shifts
            SET total_cash_sales = total_cash_sales - ?,
                expected_closing_cash = opening_cash + (total_cash_sales - ?) + total_cash_in - total_cash_out
            WHERE id = ?
        ")->execute([1028600.0, 1028600.0, $shiftId]);
    }

} catch (Throwable $e) {
    assertCheck(false, "Ngoai le trong qua trinh E2E", $e->getMessage() . " tai " . $e->getFile() . ":" . $e->getLine());
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
        assertCheck(true, "30. Don dep sach ban ghi test tam thoi ($execId)");
    } catch (Throwable $ce) {
        assertCheck(false, "30. Loi don dep ban ghi test", $ce->getMessage());
    }
}

echo "========================================================================\n";
echo " KET QUA: PASS = $passed | FAIL = $failed\n";
echo "========================================================================\n";
exit($failed > 0 ? 1 : 0);