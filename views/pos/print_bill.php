<?php

if (!function_exists('e')) {
    function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}
function bill_money($n) { return number_format((float)$n, 0, ',', '.') . 'đ'; }

$isPaid = (($order['status'] ?? '') === 'Paid');
$pay = null;
try {
    require_once __DIR__ . '/../../config/Database.php';
    $st = Database::getConnection()->prepare(
        "SELECT p.payment_method, p.transaction_ref, u.full_name AS cashier_name
         FROM payments p LEFT JOIN users u ON p.cashier_id = u.id
         WHERE p.order_id = ? ORDER BY p.id DESC LIMIT 1");
    $st->execute([(int)$order['id']]);
    $pay = $st->fetch(PDO::FETCH_ASSOC);
} catch (Exception $ex) { error_log('[print_bill] ' . $ex->getMessage()); }

$methods = ['Cash' => 'Tiền mặt', 'VietQR' => 'Chuyển khoản VietQR', 'CreditCard' => 'Thẻ'];
$itemsTotal = 0;
foreach ($details as $d) $itemsTotal += $d['quantity'] * $d['unit_price'];

$subtotal  = $isPaid ? (float)$order['subtotal'] : $itemsTotal;
$surcharge = (float)($order['area_surcharge_amount'] ?? 0);
$service   = (float)($order['service_charge_amount'] ?? 0);
$discount  = (float)($order['discount_amount'] ?? 0);
$deposit   = (float)($order['deposit_deducted'] ?? 0);
$vat       = (float)($order['vat_amount'] ?? 0);
$total     = (float)($order['total_amount'] ?? 0);
$cashier   = $pay['cashier_name'] ?? ($_SESSION['user']['full_name'] ?? 'POS Cashier');
$time      = $order['closed_at'] ?? null;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hóa đơn <?= e($order['order_code']) ?></title>
    <style>
@page {
    size: 80mm auto;
    margin: 3mm;
}

* {
    box-sizing: border-box;
}

body {
    font-family: 'Courier New', Courier, monospace;
    width: 80mm;
    margin: 0 auto;
    padding: 10px;
    background: #fff;
    color: #000;
    font-size: 12px;
    line-height: 1.45;
}

.c {
    text-align: center;
}

.b {
    font-weight: bold;
}

.brand {
    font: 700 22px Georgia, 'Times New Roman', serif;
    letter-spacing: 1px;
    margin: 0;
}

.orn {
    letter-spacing: 6px;
    font-size: 11px;
    margin: 2px 0;
}

.hr {
    border-top: 1px dashed #000;
    margin: 8px 0;
}

.hr2 {
    border-top: 2px solid #000;
    margin: 8px 0;
}

.row {
    display: flex;
    justify-content: space-between;
    gap: 8px;
}

.row span:last-child {
    text-align: right;
    white-space: nowrap;
}

.it {
    margin-bottom: 5px;
}

.it .note {
    font-size: 11px;
    padding-left: 10px;
}

.tot {
    font-size: 15px;
    font-weight: bold;
}

.tag {
    border: 1px solid #000;
    display: inline-block;
    padding: 1px 8px;
    margin: 4px 0;
    font-weight: bold;
}

.btn {
    width: 100%;
    padding: 9px;
    margin-top: 12px;
    border: 0;
    background: #b89628;
    color: #000;
    font: bold 13px Arial, sans-serif;
    cursor: pointer;
    border-radius: 4px;
}

@media print {
    .btn {
        display: none;
    }
    body {
        padding: 0;
    }
}
</style>
</head>
<body>
    <div class="c">
        <p class="brand">ROYALDINE 5S</p>
        <div class="orn">✦ ✦ ✦</div>
        <div>Đẳng Cấp Ẩm Thực Hoàng Gia</div>
        <div class="tag"><?= $isPaid ? 'HÓA ĐƠN THANH TOÁN' : 'TẠM TÍNH - CHƯA THANH TOÁN' ?></div>
        <div>Mã HD: <b><?= e($order['order_code']) ?></b></div>
    </div>

    <div class="hr"></div>
    <div class="row"><span>Bàn:</span><span class="b"><?= e($order['table_name']) ?></span></div>
    <div class="row"><span>Khu vực:</span><span><?= e($order['area_name']) ?></span></div>
    <?php if (!empty($order['customer_name'])): ?><div class="row"><span>Khách hàng:</span><span><?= e($order['customer_name']) ?></span></div><?php endif; ?>
    <div class="row"><span>Phục vụ:</span><span><?= e($order['waiter_name'] ?? '-') ?></span></div>
    <div class="row"><span>Thu ngân:</span><span><?= e($cashier) ?></span></div>
    <div class="row"><span>Thời gian:</span><span><?= e(date('d/m/Y H:i', strtotime($time ?: 'now'))) ?></span></div>
    <div class="hr2"></div>

    <?php foreach ($details as $item): ?>
        <div class="it">
            <div class="b"><?= e($item['item_name']) ?></div>
            <div class="row"><span><?= (int)$item['quantity'] ?> x <?= number_format((float)$item['unit_price'], 0, ',', '.') ?></span><span><?= number_format($item['quantity'] * $item['unit_price'], 0, ',', '.') ?>đ</span></div>
            <?php if (!empty($item['modifier_notes'])): ?><div class="note">» <?= e($item['modifier_notes']) ?></div><?php endif; ?>
        </div>
    <?php endforeach; ?>

    <div class="hr"></div>
    <div class="row"><span>Tổng tiền món</span><span><?= bill_money($subtotal) ?></span></div>
    <?php if ($isPaid): ?>
        <?php if ($surcharge > 0): ?><div class="row"><span>Phụ thu <?= e($order['area_name']) ?></span><span><?= bill_money($surcharge) ?></span></div><?php endif; ?>
        <div class="row"><span>Phí phục vụ (5%)</span><span><?= bill_money($service) ?></span></div>
        <?php if ($discount > 0): ?><div class="row"><span>Giảm giá</span><span>- <?= bill_money($discount) ?></span></div><?php endif; ?>
        <div class="row"><span>Thuế VAT (8%)</span><span><?= bill_money($vat) ?></span></div>
        <?php if ($deposit > 0): ?>
            <div class="row b"><span>Tổng cộng</span><span><?= bill_money($total + $deposit) ?></span></div>
            <div class="row"><span>Đã đặt cọc</span><span>- <?= bill_money($deposit) ?></span></div>
        <?php endif; ?>
        <div class="hr2"></div>
        <div class="row tot"><span>KHÁCH THANH TOÁN</span><span><?= bill_money($total) ?></span></div>
        <?php if (!empty($pay['payment_method'])): ?>
            <div class="row" style="margin-top:4px"><span>Hình thức:</span><span><?= e($methods[$pay['payment_method']] ?? $pay['payment_method']) ?></span></div>
            <?php if (!empty($pay['transaction_ref'])): ?><div class="row"><span>Mã GD:</span><span><?= e($pay['transaction_ref']) ?></span></div><?php endif; ?>
        <?php endif; ?>
    <?php else: ?>
        <div class="c" style="margin-top:6px;font-size:11px">Chưa gồm phụ thu, phí phục vụ, giảm giá và VAT.<br>Số tiền cuối cùng hiển thị khi thanh toán.</div>
    <?php endif; ?>

    <div class="hr"></div>
    <div class="c" style="font-size:11px">
        <div>Giá đã bao gồm thuế VAT theo quy định.</div>
        <div class="b" style="margin-top:4px">Cảm ơn Quý khách &amp; Hẹn gặp lại!</div>
        <div>RoyalDine - Phục vụ chuẩn 5 sao</div>
    </div>

    <button class="btn" onclick="window.print()">In hóa đơn / Lưu PDF</button>
</body>
</html>