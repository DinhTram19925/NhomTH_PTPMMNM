<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Hóa đơn <?= htmlspecialchars($order['order_code'] ?? '') ?> - RoyalDine 5S</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; width: 340px; margin: 0 auto; padding: 16px; color: #111; }
        .head { text-align: center; border-bottom: 2px dashed #333; padding-bottom: 10px; margin-bottom: 10px; }
        .head h2 { margin: 0; color: #8a651b; letter-spacing: 1px; }
        .meta { font-size: 12px; line-height: 1.6; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { padding: 6px 0; border-bottom: 1px dotted #ccc; }
        .r { text-align: right; }
        .sum { margin-top: 10px; font-size: 13px; }
        .sum div { display: flex; justify-content: space-between; padding: 3px 0; }
        .sum .big { font-size: 16px; font-weight: bold; border-top: 2px solid #000; padding-top: 8px; margin-top: 6px; }
        .foot { text-align: center; margin-top: 16px; font-size: 12px; font-style: italic; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body onload="window.print()">
    <div class="head">
        <h2>🌟 ROYALDINE 5S</h2>
        <div>Fine Dining & Royal Wine Cellar</div>
        <small>HÓA ĐƠN THANH TOÁN</small>
    </div>
    <div class="meta">
        <div><b>Mã HĐ:</b> <?= htmlspecialchars($order['order_code'] ?? '') ?></div>
        <div><b>Bàn:</b> <?= htmlspecialchars($order['table_name'] ?? '') ?> (<?= htmlspecialchars($order['area_name'] ?? '') ?>)</div>
        <div><b>Thu ngân:</b> <?= htmlspecialchars($order['cashier_name'] ?? ($_SESSION['user']['full_name'] ?? '')) ?></div>
        <div><b>Giờ thanh toán:</b> <?= htmlspecialchars($order['closed_at'] ?? date('Y-m-d H:i:s')) ?></div>
    </div>
    <table>
        <thead>
            <tr><th align="left">Món ăn</th><th class="r">SL</th><th class="r">Thành tiền</th></tr>
        </thead>
        <tbody>
            <?php foreach (($details ?? []) as $d): ?>
            <tr>
                <td>
                    <b><?= htmlspecialchars($d['item_name'] ?? ($d['name'] ?? '')) ?></b>
                    <?php if (!empty($d['modifier_notes'])): ?><br><small>(<?= htmlspecialchars($d['modifier_notes']) ?>)</small><?php endif; ?>
                </td>
                <td class="r"><?= (int)$d['quantity'] ?></td>
                <td class="r"><?= number_format($d['quantity'] * $d['unit_price']) ?>đ</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div class="sum">
        <div><span>Tổng tiền món:</span><span><?= number_format($order['subtotal'] ?? 0) ?>đ</span></div>
        <?php if (($order['area_surcharge_amount'] ?? 0) > 0): ?>
            <div><span>Phụ thu khu vực VIP:</span><span>+<?= number_format($order['area_surcharge_amount']) ?>đ</span></div>
        <?php endif; ?>
        <div><span>Phí phục vụ (5%):</span><span>+<?= number_format($order['service_charge_amount'] ?? 0) ?>đ</span></div>
        <?php if (($order['discount_amount'] ?? 0) > 0): ?>
            <div><span>Giảm giá (Hạng thẻ / Voucher):</span><span>-<?= number_format($order['discount_amount']) ?>đ</span></div>
        <?php endif; ?>
        <?php if (($order['deposit_deducted'] ?? 0) > 0): ?>
            <div><span>Khấu trừ tiền đặt cọc:</span><span>-<?= number_format($order['deposit_deducted']) ?>đ</span></div>
        <?php endif; ?>
        <div><span>Thuế VAT (8%):</span><span>+<?= number_format($order['vat_amount'] ?? 0) ?>đ</span></div>
        <div class="big"><span>TỔNG THANH TOÁN:</span><span><?= number_format($order['total_amount'] ?? 0) ?>đ</span></div>
    </div>
    <div class="foot">
        Cảm ơn Quý khách và hẹn gặp lại tại RoyalDine 5S!<br>
        <button class="no-print" onclick="window.print()" style="margin-top:10px;padding:6px 14px;cursor:pointer;">🖨️ In hóa đơn</button>
    </div>
</body>
</html>
