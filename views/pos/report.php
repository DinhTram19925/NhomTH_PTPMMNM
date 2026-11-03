<?php

require __DIR__ . '/_style.php';
require_once __DIR__ . '/../../config/Database.php';

if (!function_exists('e')) {
    function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}
function rp_money($n) { return number_format((float)$n, 0, ',', '.') . '₫'; }
function rp_date($d) { return is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && strtotime($d) !== false; }

$from = rp_date($_GET['from_date'] ?? '') ? $_GET['from_date'] : date('Y-m-01');
$to   = rp_date($_GET['to_date'] ?? '')   ? $_GET['to_date']   : date('Y-m-d');
if ($from > $to) { $t = $from; $from = $to; $to = $t; }

$R = ['pay' => [], 'time' => [], 'shifts' => [], 'cancel' => [], 'moves' => []];
$error = null;
try {
    $db = Database::getConnection();
    $q = function ($sql, $p = []) use ($db) { $s = $db->prepare($sql); $s->execute($p); return $s->fetchAll(PDO::FETCH_ASSOC); };
    $rg = [$from, $to];

    $R['pay'] = $q("SELECT payment_method, COUNT(id) AS n, IFNULL(SUM(amount), 0) AS total
                    FROM payments WHERE paid_at >= ? AND paid_at < DATE_ADD(?, INTERVAL 1 DAY)
                    GROUP BY payment_method ORDER BY total DESC", $rg);

    $R['time'] = $q("SELECT CASE WHEN HOUR(paid_at) < 14 THEN 'Ca sáng (đến 14h)' WHEN HOUR(paid_at) < 18 THEN 'Ca chiều (14h–18h)' ELSE 'Ca tối (từ 18h)' END AS shift_name,
                            COUNT(id) AS n, IFNULL(SUM(amount), 0) AS total
                     FROM payments WHERE paid_at >= ? AND paid_at < DATE_ADD(?, INTERVAL 1 DAY)
                     GROUP BY shift_name ORDER BY MIN(HOUR(paid_at))", $rg);

    $R['shifts'] = $q("SELECT cs.id, u.full_name AS cashier, cs.opened_at, cs.closed_at, cs.opening_cash, cs.status,
                              IFNULL((SELECT SUM(x.amount) FROM payments x WHERE x.cashier_id = cs.cashier_id AND x.paid_at >= cs.opened_at AND x.paid_at <= IFNULL(cs.closed_at, NOW())), 0) AS revenue,
                              IFNULL((SELECT SUM(x.amount) FROM payments x WHERE x.cashier_id = cs.cashier_id AND x.payment_method = 'Cash' AND x.paid_at >= cs.opened_at AND x.paid_at <= IFNULL(cs.closed_at, NOW())), 0) AS cash
                       FROM cash_shifts cs JOIN users u ON cs.cashier_id = u.id
                       WHERE cs.opened_at >= ? AND cs.opened_at < DATE_ADD(?, INTERVAL 1 DAY) ORDER BY cs.id DESC", $rg);

    $logs = $q("SELECT ol.id, ol.description, ol.created_at, u.full_name AS staff, o.order_code, dt.table_name
                FROM order_logs ol
                LEFT JOIN users u ON ol.user_id = u.id
                LEFT JOIN orders o ON ol.order_id = o.id
                LEFT JOIN dining_tables dt ON o.table_id = dt.id
                WHERE ol.action_type = 'CancelItem' AND ol.created_at >= ? AND ol.created_at < DATE_ADD(?, INTERVAL 1 DAY)
                ORDER BY ol.id DESC", $rg);

    
    $ids = [];
    foreach ($logs as $l) if (preg_match('/Hủy món ID (\d+)/u', $l['description'], $m)) $ids[] = (int)$m[1];
    $names = [];
    if ($ids) {
        $in = implode(',', array_fill(0, count($ids), '?'));
        foreach ($q("SELECT od.id, od.quantity, mi.name FROM order_details od JOIN menu_items mi ON od.menu_item_id = mi.id WHERE od.id IN ($in)", $ids) as $r) {
            $names[(int)$r['id']] = $r['quantity'] . '× ' . $r['name'];
        }
    }
    foreach ($logs as $l) {
        $item = '-'; $reason = $l['description'];
        if (preg_match('/Hủy món ID (\d+)/u', $l['description'], $m)) $item = $names[(int)$m[1]] ?? ('Món #' . $m[1]);
        elseif (preg_match('/^Hủy (.+?) \(Bàn/u', $l['description'], $m)) $item = $m[1];
        if (preg_match('/Lý do:\s*(.*)$/us', $l['description'], $m)) $reason = trim($m[1]);
        $R['cancel'][] = ['time' => $l['created_at'], 'staff' => $l['staff'] ?? 'N/A', 'table' => $l['table_name'] ?? '-', 'order' => $l['order_code'] ?? '-', 'item' => $item, 'reason' => $reason];
    }

    $R['moves'] = $q("SELECT ol.created_at AS time, u.full_name AS staff, o.order_code AS `order`, ol.description
                      FROM order_logs ol LEFT JOIN users u ON ol.user_id = u.id LEFT JOIN orders o ON ol.order_id = o.id
                      WHERE ol.action_type IN ('TransferTable', 'MergeTable') AND ol.created_at >= ? AND ol.created_at < DATE_ADD(?, INTERVAL 1 DAY)
                      ORDER BY ol.id DESC", $rg);
} catch (Exception $ex) {
    error_log('[report] ' . $ex->getMessage());
    $error = 'Không đọc được dữ liệu báo cáo từ cơ sở dữ liệu.';
}

$totalRev = array_sum(array_map(function ($r) { return (float)$r['total']; }, $R['pay']));
$totalTx  = array_sum(array_map(function ($r) { return (int)$r['n']; }, $R['pay']));
$maxPay   = max(array_merge([1], array_map(function ($r) { return (float)$r['total']; }, $R['pay'])));
$maxTime  = max(array_merge([1], array_map(function ($r) { return (float)$r['total']; }, $R['time'])));
$methods  = ['Cash' => 'Tiền mặt', 'VietQR' => 'VietQR', 'CreditCard' => 'Thẻ'];
?>
<div class="rd">
    <div class="rd-head">
        <div>
            <h1>Báo cáo POS &amp; Thu ngân</h1>
            <small>Doanh thu theo ca, theo phương thức thanh toán và nhật ký hủy món · <?= e(date('d/m/Y', strtotime($from))) ?> – <?= e(date('d/m/Y', strtotime($to))) ?></small>
        </div>
        <div class="rd-nav"><a href="index.php?c=pos&a=index">← Quay lại POS</a></div>
    </div>

    <?php if ($error): ?><div class="rd-card" style="border-color:var(--bd);margin-bottom:16px"><?= e($error) ?></div><?php endif; ?>

    <form class="rd-card rd-filter" method="get" action="index.php">
        <input type="hidden" name="c" value="pos"><input type="hidden" name="a" value="report">
        <div><label>Từ ngày</label><input type="date" name="from_date" id="fd" value="<?= e($from) ?>"></div>
        <div><label>Đến ngày</label><input type="date" name="to_date" id="td" value="<?= e($to) ?>"></div>
        <div class="rd-row" style="margin:0">
            <button class="rd-btn rd-btn-gold" type="submit">Lọc dữ liệu</button>
            <button class="rd-btn rd-btn-line" type="button" onclick="quick(0)">Hôm nay</button>
            <button class="rd-btn rd-btn-line" type="button" onclick="quick(6)">7 ngày</button>
            <button class="rd-btn rd-btn-line" type="button" onclick="quick(-1)">Tháng này</button>
        </div>
        <div class="rd-row" style="margin:0">
            <button class="rd-btn rd-btn-line" type="button" onclick="window.print()">In</button>
            <button class="rd-btn rd-btn-line" type="button" onclick="exportCsv()">Xuất Excel (CSV)</button>
        </div>
    </form>

    <div class="rd-kpis">
        <div class="rd-card rd-kpi"><small>TỔNG DOANH THU</small><b><?= rp_money($totalRev) ?></b></div>
        <div class="rd-card rd-kpi"><small>SỐ GIAO DỊCH THANH TOÁN</small><b><?= (int)$totalTx ?></b></div>
        <div class="rd-card rd-kpi"><small>SỐ LẦN HỦY MÓN</small><b style="color:var(--bd)"><?= count($R['cancel']) ?></b></div>
    </div>

    <div class="rd-2col">
        <div class="rd-card">
            <h3>Doanh thu theo phương thức</h3>
            <?php foreach ($R['pay'] as $r): ?>
                <div class="rd-tot"><span><?= e($methods[$r['payment_method']] ?? $r['payment_method']) ?> <small style="color:var(--mu)">(<?= (int)$r['n'] ?> giao dịch)</small></span><b><?= rp_money($r['total']) ?></b></div>
                <div class="rd-bar" style="width:<?= round($r['total'] / $maxPay * 100) ?>%"></div>
            <?php endforeach; ?>
            <?php if (!$R['pay']): ?><div class="rd-empty">Chưa có giao dịch trong khoảng ngày này.</div><?php endif; ?>
        </div>
        <div class="rd-card">
            <h3>Doanh thu theo ca trong ngày</h3>
            <?php foreach ($R['time'] as $r): ?>
                <div class="rd-tot"><span><?= e($r['shift_name']) ?> <small style="color:var(--mu)">(<?= (int)$r['n'] ?> giao dịch)</small></span><b><?= rp_money($r['total']) ?></b></div>
                <div class="rd-bar" style="width:<?= round($r['total'] / $maxTime * 100) ?>%"></div>
            <?php endforeach; ?>
            <?php if (!$R['time']): ?><div class="rd-empty">Chưa có giao dịch trong khoảng ngày này.</div><?php endif; ?>
        </div>
    </div>

    <div class="rd-card" style="margin-bottom:16px">
        <h3>Bàn giao ca thu ngân</h3>
        <div class="rd-scroll"><table class="rd-table">
            <tr><th>Ca</th><th>Thu ngân</th><th>Mở ca</th><th>Đóng ca</th><th>Tiền đầu ca</th><th>Doanh thu</th><th>Trong đó tiền mặt</th><th>Trạng thái</th></tr>
            <?php foreach ($R['shifts'] as $s): ?>
                <tr><td>#<?= (int)$s['id'] ?></td><td><?= e($s['cashier']) ?></td><td><?= e($s['opened_at']) ?></td><td><?= e($s['closed_at'] ?: '-') ?></td>
                    <td><?= rp_money($s['opening_cash']) ?></td><td style="color:var(--g)"><?= rp_money($s['revenue']) ?></td><td><?= rp_money($s['cash']) ?></td>
                    <td><span class="rd-bg <?= $s['status'] === 'Open' ? 's-Ready' : 's-Served' ?>"><?= $s['status'] === 'Open' ? 'Đang mở' : 'Đã đóng' ?></span></td></tr>
            <?php endforeach; ?>
            <?php if (!$R['shifts']): ?><tr><td colspan="8" class="rd-empty">Không có ca nào được mở trong khoảng ngày này.</td></tr><?php endif; ?>
        </table></div>
    </div>

    <div class="rd-card" style="margin-bottom:16px">
        <h3>Nhật ký hủy món</h3>
        <div class="rd-scroll"><table class="rd-table">
            <tr><th>Thời gian</th><th>Nhân viên</th><th>Bàn</th><th>Mã đơn</th><th>Món bị hủy</th><th>Lý do</th></tr>
            <?php foreach ($R['cancel'] as $c): ?>
                <tr><td><?= e($c['time']) ?></td><td><?= e($c['staff']) ?></td><td><?= e($c['table']) ?></td><td><?= e($c['order']) ?></td><td><?= e($c['item']) ?></td><td style="color:var(--bd)"><?= e($c['reason']) ?></td></tr>
            <?php endforeach; ?>
            <?php if (!$R['cancel']): ?><tr><td colspan="6" class="rd-empty">Không có món nào bị hủy trong khoảng ngày này.</td></tr><?php endif; ?>
        </table></div>
    </div>

    <div class="rd-card">
        <h3>Nhật ký chuyển bàn / gộp bàn</h3>
        <div class="rd-scroll"><table class="rd-table">
            <tr><th>Thời gian</th><th>Nhân viên</th><th>Mã đơn</th><th>Nội dung</th></tr>
            <?php foreach ($R['moves'] as $m): ?>
                <tr><td><?= e($m['time']) ?></td><td><?= e($m['staff'] ?? 'N/A') ?></td><td><?= e($m['order'] ?? '-') ?></td><td><?= e($m['description']) ?></td></tr>
            <?php endforeach; ?>
            <?php if (!$R['moves']): ?><tr><td colspan="4" class="rd-empty">Không có thao tác chuyển/gộp bàn trong khoảng ngày này.</td></tr><?php endif; ?>
        </table></div>
    </div>
</div>

<script id="rpt" type="application/json"><?= json_encode(['from' => $from, 'to' => $to, 'pay' => $R['pay'], 'time' => $R['time'], 'cancel' => $R['cancel'], 'moves' => $R['moves']], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<script>
function quick(days) {
    const d = x => x.toISOString().slice(0, 10), now = new Date(), off = n => new Date(now.getTime() - now.getTimezoneOffset() * 6e4 - n * 864e5);
    document.getElementById('td').value = d(off(0));
    document.getElementById('fd').value = days < 0 ? d(off(0)).slice(0, 8) + '01' : d(off(days));
    document.getElementById('td').form.submit();
}
function exportCsv() {
    const D = JSON.parse(document.getElementById('rpt').textContent);
    const cell = v => { v = String(v ?? ''); if (/^[=+\-@]/.test(v)) v = "'" + v; return '"' + v.replace(/"/g, '""') + '"'; };
    const line = a => a.map(cell).join(',') + '\n';
    let c = '\uFEFF' + line(['BÁO CÁO DOANH THU VÀ NHẬT KÝ HỦY MÓN']) + line(['Từ ngày', D.from, 'Đến ngày', D.to]) + '\n';
    c += line(['DOANH THU THEO PHƯƠNG THỨC']) + line(['Phương thức', 'Số giao dịch', 'Doanh thu (VND)']);
    D.pay.forEach(r => c += line([r.payment_method, r.n, r.total]));
    c += '\n' + line(['DOANH THU THEO CA']) + line(['Ca', 'Số giao dịch', 'Doanh thu (VND)']);
    D.time.forEach(r => c += line([r.shift_name, r.n, r.total]));
    c += '\n' + line(['NHẬT KÝ HỦY MÓN']) + line(['Thời gian', 'Nhân viên', 'Bàn', 'Mã đơn', 'Món', 'Lý do']);
    D.cancel.forEach(r => c += line([r.time, r.staff, r.table, r.order, r.item, r.reason]));
    c += '\n' + line(['CHUYỂN BÀN / GỘP BÀN']) + line(['Thời gian', 'Nhân viên', 'Mã đơn', 'Nội dung']);
    D.moves.forEach(r => c += line([r.time, r.staff, r.order, r.description]));
    const a = document.createElement('a');
    a.href = URL.createObjectURL(new Blob([c], {type: 'text/csv;charset=utf-8;'}));
    a.download = `BaoCao_RoyalDine_${D.from}_${D.to}.csv`;
    document.body.appendChild(a); a.click(); a.remove();
}
</script>