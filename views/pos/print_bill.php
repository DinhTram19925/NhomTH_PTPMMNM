<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Hóa Đơn Thanh Toán - <?= $order['order_code'] ?></title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; width: 80mm; margin: 0 auto; padding: 10px; background: #fff; color: #000; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        .divider { border-top: 1px dashed #000; margin: 8px 0; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { padding: 4px 0; }
        .btn-print { background: #007bff; color: #fff; border: none; padding: 8px 16px; cursor: pointer; border-radius: 4px; margin-bottom: 10px; width: 100%; }
        @media print { .btn-print { display: none; } }
    </style>
</head>
<body>
    
    <div class="text-center">
        <h2 style="margin: 0;">ROYALDINE 5S</h2>
        <p style="margin: 2px 0; font-size: 12px;">Đẳng Cấp Ẩm Thực Hoàng Gia</p>
        <p style="margin: 2px 0; font-size: 11px;">Mã HD: <b><?= $order['order_code'] ?></b></p>
    </div>

    <div class="divider"></div>

    <div style="font-size: 12px;">
        <p style="margin: 2px 0;">Bàn: <b><?= $order['table_name'] ?></b> (<?= $order['area_name'] ?>)</p>
        <p style="margin: 2px 0;">Thu ngân: <?= $_SESSION['user']['full_name'] ?? 'POS Cashier' ?></p>
        <p style="margin: 2px 0;">Thời gian: <?= date('d/m/Y H:i', strtotime($order['closed_at'] ?? date('Y-m-d H:i:s'))) ?></p>
    </div>

    <div class="divider"></div>

    <table>
        <thead>
            <tr style="border-bottom: 1px solid #000;">
                <th style="text-align: left;">Món</th>
                <th class="text-center">SL</th>
                <th class="text-right">T.Tiền</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($details as $item): ?>
            <tr>
                <td style="text-align: left;"><?= $item['item_name'] ?></td>
                <td class="text-center"><?= $item['quantity'] ?></td>
                <td class="text-right"><?= number_format($item['quantity'] * $item['unit_price']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="divider"></div>

    <div style="font-size: 12px;">
        <div style="display:flex; justify-content:space-between;"><span>Tạm tính:</span><span><?= number_format($order['subtotal']) ?> đ</span></div>
        <?php if($order['area_surcharge_amount'] > 0): ?>
        <div style="display:flex; justify-content:space-between;"><span>Phụ thu VIP:</span><span><?= number_format($order['area_surcharge_amount']) ?> đ</span></div>
        <?php endif; ?>
        <div style="display:flex; justify-content:space-between;"><span>Phí dịch vụ (5%):</span><span><?= number_format($order['service_charge_amount']) ?> đ</span></div>
        <div style="display:flex; justify-content:space-between;"><span>Thuế VAT (8%):</span><span><?= number_format($order['vat_amount']) ?> đ</span></div>
        <div class="divider"></div>
        <div style="display:flex; justify-content:space-between; font-size: 14px;" class="bold">
            <span>TỔNG CỘNG:</span><span><?= number_format($order['total_amount']) ?> đ</span>
        </div>
    </div>

    <div class="divider"></div>
    <div class="text-center" style="font-size: 11px;">
        <p style="margin: 4px 0;">Cảm ơn Quý khách & Hẹn gặp lại!</p>
        <p style="margin: 2px 0;">RoyalDine - Phục vụ chuẩn 5 sao</p>
    </div>

    <button class="btn-print" onclick="window.print()">🖨️ In Hóa Đơn (Save PDF)</button>
</body>
</html>