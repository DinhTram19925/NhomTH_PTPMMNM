<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>In Báo Cáo Ca - #CA-<?= $shiftData['id'] ?></title>
    <style>
        @page { margin: 0; }
        body { font-family: 'Courier New', Courier, monospace; font-size: 13px; color: #000; margin: 0; padding: 10px; width: 72mm; /* Trừ lề 2 bên để vừa K80 */ line-height: 1.4; background: #fff; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .fw-bold { font-weight: bold; }
        .divider { border-top: 1px dashed #000; margin: 8px 0; }
        .divider-solid { border-top: 1px solid #000; margin: 8px 0; }
        .flex-row { display: flex; justify-content: space-between; }
        h3 { font-size: 18px; margin: 5px 0; }
        h4 { font-size: 15px; margin: 5px 0; }
        
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print(); window.onafterprint = function(){ window.close(); }">

    <div class="text-center fw-bold">
        <h3>TRUNG TÂM ĐIỀU HÀNH KHO</h3>
        <p style="margin: 0;">CN: Chi Nhánh Trung Tâm</p>
        <p style="margin: 0; font-size: 11px;">(Hệ thống quản lý RoyalDine 5S)</p>
    </div>

    <div class="divider-solid"></div>
    <div class="text-center fw-bold">
        <h4>BÁO CÁO KẾT THÚC CA (Z-REPORT)</h4>
    </div>
    
    <div style="margin-top: 10px;">
        <div class="flex-row"><span>Mã Ca:</span> <span class="fw-bold">#CA-<?= $shiftData['id'] ?></span></div>
        <div class="flex-row"><span>Thu Ngân:</span> <span><?= htmlspecialchars($shiftData['cashier_name'] ?? 'Nhân viên') ?></span></div>
        <div class="flex-row"><span>Giờ Mở:</span> <span><?= date('d/m/Y H:i', strtotime($shiftData['opened_at'])) ?></span></div>
        <div class="flex-row"><span>Giờ Đóng:</span> <span><?= date('d/m/Y H:i', strtotime($shiftData['closed_at'])) ?></span></div>
    </div>

    <div class="divider"></div>
    
    <div class="fw-bold" style="margin-bottom: 5px;">THỐNG KÊ DÒNG TIỀN:</div>
    <div class="flex-row">
        <span>Tiền Lẻ Ban Đầu:</span> 
        <span><?= number_format($shiftData['opening_cash']) ?></span>
    </div>
    <div class="flex-row">
        <span>(+) Tổng Bán Hàng:</span> 
        <span><?= number_format($shiftData['total_cash_sales']) ?></span>
    </div>
    <div class="flex-row">
        <span>(+) Thu Khác:</span> 
        <span><?= number_format($shiftData['total_cash_in']) ?></span>
    </div>
    <div class="flex-row">
        <span>(-) Chi Khác:</span> 
        <span><?= number_format($shiftData['total_cash_out']) ?></span>
    </div>

    <div class="divider-solid"></div>

    <div class="flex-row fw-bold" style="font-size: 14px;">
        <span>TIỀN PHẢI CÓ (Lý thuyết):</span> 
        <span><?= number_format($shiftData['expected_closing_cash']) ?></span>
    </div>
    <div class="flex-row fw-bold" style="font-size: 14px;">
        <span>TIỀN THỰC ĐẾM (Thực tế):</span> 
        <span><?= number_format($shiftData['actual_closing_cash']) ?></span>
    </div>

    <div class="divider"></div>

    <div class="flex-row fw-bold" style="font-size: 15px; margin-top: 10px;">
        <span>CHÊNH LỆCH:</span> 
        <span><?= ($shiftData['discrepancy'] > 0 ? '+' : '') . number_format($shiftData['discrepancy']) ?></span>
    </div>

    <?php if (!empty($shiftData['note'])): ?>
    <div style="margin-top: 10px; font-size: 12px; font-style: italic;">
        Ghi chú/Bàn giao: <?= nl2br(htmlspecialchars($shiftData['note'])) ?>
    </div>
    <?php endif; ?>

    <div class="divider-solid" style="margin-top: 20px;"></div>
    <div class="text-center" style="font-size: 12px; margin-top: 10px;">
        <div>Chữ ký Bàn giao</div>
        <div style="margin-top: 40px;">...................................</div>
        <div class="fw-bold"><?= htmlspecialchars($shiftData['cashier_name'] ?? '') ?></div>
        <div style="margin-top: 15px;">Ngày in: <?= date('d/m/Y H:i') ?></div>
    </div>

</body>
</html>