<?php
require_once __DIR__ . '/_style.php';
require_once __DIR__ . '/../../config/Database.php';

$db = Database::getConnection();

// Nhận tham số lọc ngày từ URL (mặc định từ đầu tháng đến hôm nay)
$fromDate = $_GET['from_date'] ?? date('Y-m-01');
$toDate   = $_GET['to_date']   ?? date('Y-m-d');

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromDate) || strtotime($fromDate) === false) {
    $fromDate = date('Y-m-01');
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $toDate) || strtotime($toDate) === false) {
    $toDate = date('Y-m-d');
}

// 1. Doanh thu theo phương thức thanh toán
$stmtPm = $db->prepare("
    SELECT payment_method, COUNT(id) AS transaction_count, IFNULL(SUM(amount), 0) AS total_revenue
    FROM payments
    WHERE paid_at >= ? AND paid_at < DATE_ADD(?, INTERVAL 1 DAY)
    GROUP BY payment_method
");
$stmtPm->execute([$fromDate, $toDate]);
$paymentMethods = $stmtPm->fetchAll(PDO::FETCH_ASSOC);

// 2. Doanh thu theo ca trong ngày
$stmtTs = $db->prepare("
    SELECT 
        CASE 
            WHEN HOUR(paid_at) < 14 THEN 'Ca sáng (đến 14h)'
            WHEN HOUR(paid_at) < 18 THEN 'Ca chiều (14h–18h)'
            ELSE 'Ca tối (từ 18h)'
        END AS shift_name,
        COUNT(id) AS transaction_count,
        IFNULL(SUM(amount), 0) AS total_revenue
    FROM payments
    WHERE paid_at >= ? AND paid_at < DATE_ADD(?, INTERVAL 1 DAY)
    GROUP BY shift_name
    ORDER BY MIN(HOUR(paid_at))
");
$stmtTs->execute([$fromDate, $toDate]);
$timeShifts = $stmtTs->fetchAll(PDO::FETCH_ASSOC);

// 3. Danh sách hóa đơn đã thanh toán (kết nối đầy đủ Bàn, Khu vực, Khách hàng, Thu ngân, Món ăn)
$stmtPaid = $db->prepare("
    SELECT 
        o.id AS order_id,
        o.order_code,
        dt.table_name,
        a.name AS area_name,
        COALESCE(c.full_name, 'Khách lẻ') AS customer_name,
        c.phone AS customer_phone,
        u.full_name AS waiter_name,
        cu.full_name AS cashier_name,
        COALESCE(p.payment_method, 'Cash') AS payment_method,
        p.transaction_ref,
        o.subtotal,
        o.area_surcharge_amount,
        o.service_charge_amount,
        o.discount_amount,
        o.voucher_code,
        o.deposit_deducted,
        o.vat_amount,
        o.total_amount,
        COALESCE(p.paid_at, o.closed_at, o.opened_at) AS paid_at,
        (
            SELECT GROUP_CONCAT(CONCAT(od.quantity, '× ', mi.name) SEPARATOR ', ')
            FROM order_details od
            JOIN menu_items mi ON od.menu_item_id = mi.id
            WHERE od.order_id = o.id AND od.kds_status <> 'Cancelled'
        ) AS items_summary
    FROM orders o
    JOIN dining_tables dt ON o.table_id = dt.id
    JOIN areas a ON dt.area_id = a.id
    LEFT JOIN customers c ON o.customer_id = c.id
    LEFT JOIN users u ON o.waiter_id = u.id
    LEFT JOIN payments p ON p.id = (
        SELECT p2.id FROM payments p2 WHERE p2.order_id = o.id ORDER BY p2.id DESC LIMIT 1
    )
    LEFT JOIN users cu ON p.cashier_id = cu.id
    WHERE o.status = 'Paid'
      AND COALESCE(p.paid_at, o.closed_at, o.opened_at) >= ?
      AND COALESCE(p.paid_at, o.closed_at, o.opened_at) < DATE_ADD(?, INTERVAL 1 DAY)
    ORDER BY COALESCE(p.paid_at, o.closed_at, o.opened_at) DESC, o.id DESC
");
$stmtPaid->execute([$fromDate, $toDate]);
$paidOrders = $stmtPaid->fetchAll(PDO::FETCH_ASSOC);

// 4. Bàn giao ca thu ngân
$stmtShifts = $db->prepare("
    SELECT 
        cs.id AS shift_id,
        u.full_name AS cashier_name,
        cs.opened_at,
        cs.closed_at,
        cs.opening_cash,
        cs.total_cash_sales,
        cs.status,
        IFNULL((SELECT SUM(x.amount) FROM payments x WHERE x.paid_at >= cs.opened_at AND x.paid_at <= IFNULL(cs.closed_at, NOW())), 0) AS total_revenue,
        IFNULL((SELECT SUM(x.amount) FROM payments x WHERE x.payment_method = 'Cash' AND x.paid_at >= cs.opened_at AND x.paid_at <= IFNULL(cs.closed_at, NOW())), 0) AS cash_revenue
    FROM cash_shifts cs
    JOIN users u ON cs.cashier_id = u.id
    WHERE cs.opened_at >= ? AND cs.opened_at < DATE_ADD(?, INTERVAL 1 DAY)
    ORDER BY cs.id DESC
");
$stmtShifts->execute([$fromDate, $toDate]);
$shifts = $stmtShifts->fetchAll(PDO::FETCH_ASSOC);

// 5. Nhật ký hủy món
$stmtCancel = $db->prepare("
    SELECT ol.id, ol.order_id, o.order_code, dt.table_name, u.full_name AS staff_name, ol.description, ol.created_at
    FROM order_logs ol
    LEFT JOIN users u ON ol.user_id = u.id
    LEFT JOIN orders o ON ol.order_id = o.id
    LEFT JOIN dining_tables dt ON o.table_id = dt.id
    WHERE ol.action_type = 'CancelItem'
      AND ol.created_at >= ? AND ol.created_at < DATE_ADD(?, INTERVAL 1 DAY)
    ORDER BY ol.id DESC
");
$stmtCancel->execute([$fromDate, $toDate]);
$cancelLogs = $stmtCancel->fetchAll(PDO::FETCH_ASSOC);

// 6. Nhật ký chuyển bàn / gộp bàn
$stmtMove = $db->prepare("
    SELECT ol.id, ol.action_type, o.order_code, u.full_name AS staff_name, ol.description, ol.created_at
    FROM order_logs ol
    LEFT JOIN users u ON ol.user_id = u.id
    LEFT JOIN orders o ON ol.order_id = o.id
    WHERE ol.action_type IN ('TransferTable', 'MergeTable')
      AND ol.created_at >= ? AND ol.created_at < DATE_ADD(?, INTERVAL 1 DAY)
    ORDER BY ol.id DESC
");
$stmtMove->execute([$fromDate, $toDate]);
$moveLogs = $stmtMove->fetchAll(PDO::FETCH_ASSOC);

// Tính tổng KPI
$totalRevenue = 0;
$totalTx = 0;
foreach ($paymentMethods as $pm) {
    $totalRevenue += (float)($pm['total_revenue'] ?? 0);
    $totalTx      += (int)($pm['transaction_count'] ?? 0);
}
if ($totalRevenue <= 0 && !empty($paidOrders)) {
    foreach ($paidOrders as $po) {
        $totalRevenue += (float)($po['total_amount'] ?? 0);
    }
    $totalTx = count($paidOrders);
}
$avgOrder = $totalTx > 0 ? round($totalRevenue / $totalTx) : 0;

if (!function_exists('posMethodLabel')) {
    function posMethodLabel(string $m): string {
        return match ($m) {
            'VietQR'     => 'VietQR',
            'CreditCard' => 'Thẻ',
            default      => 'Tiền mặt'
        };
    }
}
?>

<style>
.pos-report-wrap {
    max-width: 1400px;
    margin: 0 auto;
    padding: 16px 24px 48px;
}
.pos-rep-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    padding: 20px 22px;
    margin-bottom: 20px;
    box-shadow: 0 2px 6px rgba(15, 23, 42, 0.03);
}
.pos-rep-title {
    font-size: 16px;
    font-weight: 800;
    color: #111827;
    margin: 0 0 14px 0;
}
.pos-kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: 20px;
}
@media (max-width: 992px) {
    .pos-kpi-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
.pos-kpi-box {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    padding: 18px 20px;
}
.pos-kpi-label {
    font-size: 11.5px;
    font-weight: 700;
    text-transform: uppercase;
    color: #6b7280;
    margin-bottom: 6px;
}
.pos-kpi-val {
    font-size: 26px;
    font-weight: 800;
    color: #b48a3e;
}
.pos-kpi-val.danger {
    color: #b91c1c;
}
.pos-kpi-val.dark {
    color: #17243b;
}
.pos-bar-row {
    margin-bottom: 14px;
}
.pos-bar-head {
    display: flex;
    justify-content: space-between;
    font-size: 13.5px;
    margin-bottom: 6px;
}
.pos-bar-track {
    height: 7px;
    background: #f3f4f6;
    border-radius: 999px;
    overflow: hidden;
}
.pos-bar-fill {
    height: 100%;
    background: #b48a3e;
    border-radius: 999px;
}
.pos-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}
.pos-table th {
    text-align: left;
    font-size: 11.5px;
    font-weight: 700;
    color: #6b7280;
    padding: 10px 12px;
    border-bottom: 1px solid #e5e7eb;
    white-space: nowrap;
}
.pos-table td {
    padding: 12px;
    border-bottom: 1px solid #f1f5f9;
    color: #1f2937;
    vertical-align: middle;
}
.pos-table tbody tr:hover {
    background: #f9fafb;
}
.badge-method {
    display: inline-block;
    padding: 3px 9px;
    border-radius: 999px;
    font-size: 11.5px;
    font-weight: 700;
}
.badge-Cash { background: #fef3c7; color: #92400e; }
.badge-VietQR { background: #dcfce7; color: #166534; }
.badge-CreditCard { background: #dbeafe; color: #1e40af; }
.btn-reprint {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 12px;
    font-size: 12px;
    font-weight: 700;
    color: #17243b;
    background: #fffbeb;
    border: 1px solid #d4af37;
    border-radius: 8px;
    cursor: pointer;
    text-decoration: none;
    transition: 0.15s;
    white-space: nowrap;
}
.btn-reprint:hover {
    background: #b48a3e;
    color: #fff;
    border-color: #b48a3e;
}
</style>

<div class="pos-report-wrap">
    <!-- Nút quay lại POS nằm gọn bên phải -->
    <div class="d-flex justify-content-end mb-2">
        <a href="index.php?c=pos&a=index" class="btn btn-outline-secondary btn-sm px-3 py-2 rounded-3 bg-white fw-semibold">
            ← Quay lại POS
        </a>
    </div>

    <!-- Bộ lọc thời gian -->
    <div class="pos-rep-card">
        <form method="GET" action="index.php" id="filterForm" class="row g-3 align-items-end">
            <input type="hidden" name="c" value="pos">
            <input type="hidden" name="a" value="report">
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Từ ngày</label>
                <input type="date" name="from_date" id="fromDate" class="form-control" value="<?= htmlspecialchars($fromDate) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Đến ngày</label>
                <input type="date" name="to_date" id="toDate" class="form-control" value="<?= htmlspecialchars($toDate) ?>">
            </div>
            <div class="col-md-6 d-flex flex-wrap gap-2 justify-content-between">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-sm px-3 py-2 fw-bold text-white" style="background:#b48a3e; border-radius:8px;">Lọc dữ liệu</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm px-3 rounded-3" onclick="quickRange(0)">Hôm nay</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm px-3 rounded-3" onclick="quickRange(6)">7 ngày</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm px-3 rounded-3" onclick="quickRange('month')">Tháng này</button>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-3 rounded-3" onclick="window.print()">In</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm px-3 rounded-3" onclick="exportPaidOrdersCsv()">Xuất Excel (CSV)</button>
                </div>
            </div>
        </form>
    </div>

    <!-- 4 Ô KPI Tổng quan -->
    <div class="pos-kpi-grid">
        <div class="pos-kpi-box">
            <div class="pos-kpi-label">Tổng doanh thu</div>
            <div class="pos-kpi-val"><?= number_format($totalRevenue, 0, ',', '.') ?>đ</div>
        </div>
        <div class="pos-kpi-box">
            <div class="pos-kpi-label">Số giao dịch thanh toán</div>
            <div class="pos-kpi-val"><?= (int)$totalTx ?></div>
        </div>
        <div class="pos-kpi-box">
            <div class="pos-kpi-label">Trung bình / Hóa đơn</div>
            <div class="pos-kpi-val dark"><?= number_format($avgOrder, 0, ',', '.') ?>đ</div>
        </div>
        <div class="pos-kpi-box">
            <div class="pos-kpi-label">Số lần hủy món</div>
            <div class="pos-kpi-val danger"><?= count($cancelLogs) ?></div>
        </div>
    </div>

    <!-- Biểu đồ ngang: Phương thức & Ca trong ngày -->
    <div class="row g-3 mb-1">
        <div class="col-md-6">
            <div class="pos-rep-card h-100">
                <h5 class="pos-rep-title">Doanh thu theo phương thức</h5>
                <?php if (empty($paymentMethods)): ?>
                    <div class="text-muted small py-2">Chưa có giao dịch thanh toán trong khoảng ngày này.</div>
                <?php else: ?>
                    <?php foreach ($paymentMethods as $pm):
                        $rev = (float)($pm['total_revenue'] ?? 0);
                        $pct = $totalRevenue > 0 ? round(($rev / $totalRevenue) * 100) : 0;
                    ?>
                        <div class="pos-bar-row">
                            <div class="pos-bar-head">
                                <span><b><?= htmlspecialchars(posMethodLabel($pm['payment_method'] ?? 'Cash')) ?></b> <span class="text-muted">(<?= (int)$pm['transaction_count'] ?> giao dịch)</span></span>
                                <b><?= number_format($rev, 0, ',', '.') ?>đ</b>
                            </div>
                            <div class="pos-bar-track"><div class="pos-bar-fill" style="width:<?= $pct ?>%"></div></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-md-6">
            <div class="pos-rep-card h-100">
                <h5 class="pos-rep-title">Doanh thu theo ca trong ngày</h5>
                <?php if (empty($timeShifts)): ?>
                    <div class="text-muted small py-2">Chưa có dữ liệu theo khung giờ.</div>
                <?php else: ?>
                    <?php foreach ($timeShifts as $ts):
                        $rev = (float)($ts['total_revenue'] ?? 0);
                        $pct = $totalRevenue > 0 ? round(($rev / $totalRevenue) * 100) : 0;
                    ?>
                        <div class="pos-bar-row">
                            <div class="pos-bar-head">
                                <span><b><?= htmlspecialchars($ts['shift_name'] ?? '') ?></b> <span class="text-muted">(<?= (int)$ts['transaction_count'] ?> giao dịch)</span></span>
                                <b><?= number_format($rev, 0, ',', '.') ?>đ</b>
                            </div>
                            <div class="pos-bar-track"><div class="pos-bar-fill" style="width:<?= $pct ?>%"></div></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- DANH SÁCH HÓA ĐƠN ĐÃ THANH TOÁN & IN LẠI HÓA ĐƠN -->
    <div class="pos-rep-card">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h5 class="pos-rep-title mb-0">Danh sách hóa đơn đã thanh toán (<span id="paidOrdersCount"><?= count($paidOrders) ?></span>)</h5>
                <small class="text-muted">Tra cứu chi tiết từng hóa đơn và nhấn "In lại hóa đơn" để mở cửa sổ in nhiệt giống màn hình POS</small>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <input type="text" id="orderSearchInput" class="form-control form-control-sm" style="min-width: 240px;" placeholder="Tìm mã HD, bàn, khách hàng, món ăn..." oninput="filterPaidOrdersTable()">
                <select id="orderMethodFilter" class="form-select form-select-sm" style="width: 160px;" onchange="filterPaidOrdersTable()">
                    <option value="ALL">Tất cả PTTT</option>
                    <option value="VietQR">VietQR</option>
                    <option value="Cash">Tiền mặt</option>
                    <option value="CreditCard">Thẻ</option>
                </select>
            </div>
        </div>

        <div class="table-responsive">
            <table class="pos-table" id="paidOrdersTable">
                <thead>
                    <tr>
                        <th>Mã hóa đơn</th>
                        <th>Thời gian TT</th>
                        <th>Bàn / Khu vực</th>
                        <th>Khách hàng</th>
                        <th>Thu ngân / Phục vụ</th>
                        <th>Món đã gọi</th>
                        <th class="text-end">Tạm tính</th>
                        <th class="text-end">Giảm / Cọc</th>
                        <th class="text-end">Thực thu</th>
                        <th>PTTT</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($paidOrders)): ?>
                        <tr>
                            <td colspan="11" class="text-center text-muted py-4">Không có hóa đơn đã thanh toán nào trong khoảng ngày này.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($paidOrders as $o):
                            $deductTotal = (float)($o['discount_amount'] ?? 0) + (float)($o['deposit_deducted'] ?? 0);
                            $pmCode = $o['payment_method'] ?? 'Cash';
                            $searchStr = mb_strtolower(
                                ($o['order_code'] ?? '') . ' ' .
                                ($o['table_name'] ?? '') . ' ' .
                                ($o['area_name'] ?? '') . ' ' .
                                ($o['customer_name'] ?? '') . ' ' .
                                ($o['customer_phone'] ?? '') . ' ' .
                                ($o['items_summary'] ?? '') . ' ' .
                                ($o['cashier_name'] ?? '') . ' ' .
                                ($o['waiter_name'] ?? ''),
                                'UTF-8'
                            );
                        ?>
                            <tr class="paid-order-row"
                                data-method="<?= htmlspecialchars($pmCode) ?>"
                                data-search="<?= htmlspecialchars($searchStr) ?>">
                                <td class="fw-bold" style="color:#17243b;"><?= htmlspecialchars($o['order_code']) ?></td>
                                <td class="text-nowrap small"><?= htmlspecialchars($o['paid_at'] ?? '') ?></td>
                                <td>
                                    <span class="fw-bold"><?= htmlspecialchars($o['table_name'] ?? '') ?></span>
                                    <div class="small text-muted"><?= htmlspecialchars($o['area_name'] ?? '') ?></div>
                                </td>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars($o['customer_name'] ?? 'Khách lẻ') ?></div>
                                    <?php if (!empty($o['customer_phone'])): ?>
                                        <div class="small text-muted"><?= htmlspecialchars($o['customer_phone']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($o['cashier_name'] ?: ($o['waiter_name'] ?? '-')) ?>
                                    <?php if (!empty($o['cashier_name']) && !empty($o['waiter_name'])): ?>
                                        <div class="small text-muted">PV: <?= htmlspecialchars($o['waiter_name']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td style="max-width: 260px;" class="small text-secondary">
                                    <?= htmlspecialchars($o['items_summary'] ?: '-') ?>
                                </td>
                                <td class="text-end text-nowrap"><?= number_format((float)$o['subtotal'], 0, ',', '.') ?>đ</td>
                                <td class="text-end text-nowrap text-danger">
                                    <?= $deductTotal > 0 ? '-' . number_format($deductTotal, 0, ',', '.') . 'đ' : '0đ' ?>
                                    <?php if ((float)($o['deposit_deducted'] ?? 0) > 0): ?>
                                        <div class="small text-muted">Cọc: <?= number_format((float)$o['deposit_deducted'], 0, ',', '.') ?>đ</div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end fw-bold text-nowrap" style="color:#b48a3e;">
                                    <?= number_format((float)$o['total_amount'], 0, ',', '.') ?>đ
                                </td>
                                <td>
                                    <span class="badge-method badge-<?= htmlspecialchars($pmCode) ?>">
                                        <?= htmlspecialchars(posMethodLabel($pmCode)) ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn-reprint" onclick="reprintPosBill(<?= (int)$o['order_id'] ?>)">
                                        🖨️ In lại hóa đơn
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Bàn giao ca thu ngân -->
    <div class="pos-rep-card">
        <h5 class="pos-rep-title">Bàn giao ca thu ngân</h5>
        <div class="table-responsive">
            <table class="pos-table">
                <thead>
                    <tr>
                        <th>Ca</th>
                        <th>Thu ngân</th>
                        <th>Mở ca</th>
                        <th>Đóng ca</th>
                        <th>Tiền đầu ca</th>
                        <th>Doanh thu</th>
                        <th>Trong đó tiền mặt</th>
                        <th>Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($shifts)): ?>
                        <tr><td colspan="8" class="text-center text-muted py-3">Không có ca thu ngân nào trong khoảng ngày này.</td></tr>
                    <?php else: ?>
                        <?php foreach ($shifts as $s): ?>
                            <tr>
                                <td class="fw-bold">#<?= (int)$s['shift_id'] ?></td>
                                <td><?= htmlspecialchars($s['cashier_name'] ?? '') ?></td>
                                <td><?= htmlspecialchars($s['opened_at'] ?? '') ?></td>
                                <td><?= htmlspecialchars($s['closed_at'] ?: '-') ?></td>
                                <td><?= number_format((float)($s['opening_cash'] ?? 0), 0, ',', '.') ?>đ</td>
                                <td class="fw-semibold"><?= number_format((float)($s['total_revenue'] ?? 0), 0, ',', '.') ?>đ</td>
                                <td><?= number_format((float)($s['cash_revenue'] ?? 0), 0, ',', '.') ?>đ</td>
                                <td>
                                    <span class="badge <?= ($s['status'] ?? '') === 'Open' ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary' ?>">
                                        <?= ($s['status'] ?? '') === 'Open' ? 'Đang mở' : 'Đã đóng' ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Nhật ký hủy món -->
    <div class="pos-rep-card">
        <h5 class="pos-rep-title">Nhật ký hủy món</h5>
        <div class="table-responsive">
            <table class="pos-table">
                <thead>
                    <tr>
                        <th>Thời gian</th>
                        <th>Nhân viên</th>
                        <th>Bàn</th>
                        <th>Mã đơn</th>
                        <th>Chi tiết hủy & Lý do</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($cancelLogs)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-3">Không có món hủy trong khoảng ngày này.</td></tr>
                    <?php else: ?>
                        <?php foreach ($cancelLogs as $c): ?>
                            <tr>
                                <td class="text-nowrap"><?= htmlspecialchars($c['created_at'] ?? '') ?></td>
                                <td><?= htmlspecialchars($c['staff_name'] ?? '') ?></td>
                                <td class="fw-semibold"><?= htmlspecialchars($c['table_name'] ?: '-') ?></td>
                                <td><?= htmlspecialchars($c['order_code'] ?? '') ?></td>
                                <td><?= htmlspecialchars($c['description'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Nhật ký chuyển bàn / gộp bàn -->
    <div class="pos-rep-card">
        <h5 class="pos-rep-title">Nhật ký chuyển bàn / gộp bàn</h5>
        <div class="table-responsive">
            <table class="pos-table">
                <thead>
                    <tr>
                        <th>Thời gian</th>
                        <th>Nhân viên</th>
                        <th>Mã đơn</th>
                        <th>Nội dung</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($moveLogs)): ?>
                        <tr><td colspan="4" class="text-center text-muted py-3">Không có thao tác chuyển/gộp bàn trong khoảng ngày này.</td></tr>
                    <?php else: ?>
                        <?php foreach ($moveLogs as $m): ?>
                            <tr>
                                <td class="text-nowrap"><?= htmlspecialchars($m['created_at'] ?? '') ?></td>
                                <td><?= htmlspecialchars($m['staff_name'] ?? '') ?></td>
                                <td><?= htmlspecialchars($m['order_code'] ?? '') ?></td>
                                <td><?= htmlspecialchars($m['description'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
const paidOrdersData = <?= json_encode($paidOrders, JSON_UNESCAPED_UNICODE) ?>;

function reprintPosBill(orderId) {
    const url = 'index.php?c=pos&a=printBill&order_id=' + encodeURIComponent(orderId);
    const w = window.open(url, 'POS_PrintBill_' + orderId, 'width=420,height=680,scrollbars=yes');
    if (w) {
        w.focus();
    } else {
        window.location.href = url;
    }
}

function quickRange(mode) {
    const today = new Date();
    const toStr = today.toISOString().slice(0, 10);
    let fromStr = toStr;

    if (mode === 'month') {
        fromStr = toStr.slice(0, 8) + '01';
    } else if (typeof mode === 'number' && mode > 0) {
        const d = new Date(today);
        d.setDate(d.getDate() - mode);
        fromStr = d.toISOString().slice(0, 10);
    }

    document.getElementById('fromDate').value = fromStr;
    document.getElementById('toDate').value = toStr;
    document.getElementById('filterForm').submit();
}

function filterPaidOrdersTable() {
    const kw = (document.getElementById('orderSearchInput')?.value || '').trim().toLowerCase();
    const method = document.getElementById('orderMethodFilter')?.value || 'ALL';
    const rows = document.querySelectorAll('#paidOrdersTable tbody .paid-order-row');
    let visibleCount = 0;

    rows.forEach(row => {
        const rowMethod = row.getAttribute('data-method') || '';
        const rowSearch = row.getAttribute('data-search') || '';
        const matchMethod = (method === 'ALL') || (rowMethod === method);
        const matchKw = (kw === '') || rowSearch.includes(kw);

        if (matchMethod && matchKw) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    const countEl = document.getElementById('paidOrdersCount');
    if (countEl) countEl.textContent = visibleCount;
}

function exportPaidOrdersCsv() {
    if (!paidOrdersData || !paidOrdersData.length) {
        alert('Chưa có hóa đơn đã thanh toán để xuất file!');
        return;
    }
    const headers = ['Mã hóa đơn', 'Thời gian TT', 'Bàn', 'Khu vực', 'Khách hàng', 'SĐT', 'Thu ngân', 'Món đã gọi', 'Tạm tính', 'Giảm giá', 'Trừ cọc', 'VAT', 'Thực thu', 'Phương thức'];
    const rows = paidOrdersData.map(o => [
        o.order_code,
        o.paid_at,
        o.table_name,
        o.area_name,
        o.customer_name,
        o.customer_phone || '',
        o.cashier_name || o.waiter_name || '',
        o.items_summary || '',
        o.subtotal,
        o.discount_amount,
        o.deposit_deducted,
        o.vat_amount,
        o.total_amount,
        o.payment_method
    ]);

    const csvContent = '\uFEFF' + [headers, ...rows]
        .map(r => r.map(v => `"${String(v ?? '').replace(/"/g, '""')}"`).join(','))
        .join('\n');

    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `Bao_Cao_Hoa_Don_POS_${document.getElementById('fromDate').value}_${document.getElementById('toDate').value}.csv`;
    link.click();
}
</script>