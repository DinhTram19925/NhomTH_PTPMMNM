<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chi tiết Phiếu Nhập - <?= htmlspecialchars($receipt['receipt_code']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --gold-primary: #c9a050;
            --gold-dark: #a8823b;
            --gold-light: #fdf8ee;
            --charcoal: #1a1d20;
        }
        body { background-color: #f0f2f5; font-family: 'Segoe UI', sans-serif; color: #333; }
        
        /* Cấu trúc khổ giấy A4 */
        .a4-container {
            max-width: 900px;
            margin: 40px auto;
            background: #fff;
            padding: 50px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border-top: 8px solid var(--charcoal);
        }

        .receipt-header { border-bottom: 2px solid var(--gold-primary); padding-bottom: 20px; margin-bottom: 30px; }
        .receipt-title { color: var(--charcoal); font-weight: 800; letter-spacing: 2px; }
        
        .info-box { background: #f8f9fa; padding: 20px; border-radius: 8px; border: 1px solid #e9ecef; }
        .info-label { font-size: 0.85rem; color: #6c757d; text-transform: uppercase; font-weight: 700; }
        .info-value { font-size: 1.05rem; font-weight: 600; color: #212529; }

        .table th { background-color: var(--charcoal) !important; color: var(--gold-primary) !important; text-transform: uppercase; font-size: 0.9rem; }
        .table td { vertical-align: middle; font-weight: 500; }
        
        .total-row td { background-color: #fcfbf9 !important; font-size: 1.2rem; font-weight: 800; color: #a8823b; }

        /* Nút quay lại đồng bộ */
        .btn-return-lux { 
            background-color: #ffffff; 
            color: #495057; 
            border: 1.5px solid rgba(201, 160, 80, 0.4); 
            font-weight: 600; 
            transition: all 0.2s ease; 
        }
        .btn-return-lux:hover { 
            background-color: var(--gold-light); 
            color: var(--gold-dark); 
            border-color: var(--gold-primary); 
        }

        /* Chế độ in (Ẩn nút bấm khi in ra giấy) */
        @media print {
            body { background: #fff; }
            .a4-container { box-shadow: none; margin: 0; padding: 20px; border: none; max-width: 100%; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="a4-container">
    <!-- Nút công cụ (Sẽ ẩn khi in) -->
    <div class="d-flex justify-content-between mb-4 no-print">
        <button onclick="history.back()" class="btn btn-return-lux px-4 py-2 shadow-sm rounded-3">
             Quay lại 
        </button>
        <button onclick="window.print()" class="btn btn-dark fw-bold px-4 shadow-sm rounded-3" style="background: var(--charcoal); color: var(--gold-primary);">🖨️ In Phiếu Nhập</button>
    </div>

    <!-- Header Hóa Đơn -->
    <div class="receipt-header d-flex justify-content-between align-items-center">
        <div>
            <h2 class="receipt-title m-0">PHIẾU NHẬP KHO</h2>
            <div class="mt-2 text-muted fw-bold">Số phiếu: <span class="text-dark">#<?= htmlspecialchars($receipt['receipt_code']) ?></span></div>
        </div>
        <div class="text-end">
            <h4 class="fw-bold" style="color: var(--gold-primary);">ROYALDINE 5S</h4>
            <div class="text-muted" style="font-size: 0.9rem;">Chi nhánh Trung tâm<br>Hotline: 0909.888.999</div>
        </div>
    </div>

    <!-- Thông tin đối tác & Phiếu -->
    <div class="row g-4 mb-4">
        <div class="col-md-7">
            <div class="info-box h-100">
                <div class="info-label mb-2">Thông tin Nhà cung cấp</div>
                <div class="info-value text-primary"><?= htmlspecialchars($receipt['supplier_name'] ?? 'Khách lẻ / Khác') ?></div>
                <div class="mt-1" style="font-size: 0.95rem;">📞 SĐT: <?= htmlspecialchars($receipt['supplier_phone'] ?? '---') ?></div>
                <div class="mt-1" style="font-size: 0.95rem;">📍 Đ/c: <?= htmlspecialchars($receipt['supplier_address'] ?? '---') ?></div>
            </div>
        </div>
        <div class="col-md-5">
            <div class="info-box h-100">
                <div class="info-label mb-2">Thông tin Nhập kho</div>
                <div class="d-flex justify-content-between mt-1">
                    <span class="text-muted">Ngày lập:</span>
                    <span class="fw-bold"><?= date('d/m/Y H:i', strtotime($receipt['created_at'])) ?></span>
                </div>
                <div class="d-flex justify-content-between mt-1">
                    <span class="text-muted">Người lập:</span>
                    <span class="fw-bold"><?= htmlspecialchars($receipt['creator_name'] ?? 'Admin') ?></span>
                </div>
                <div class="d-flex justify-content-between mt-1">
                    <span class="text-muted">Loại phiếu:</span>
                    <span class="badge bg-success">Nhập hàng</span>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-4">
        <span class="fw-bold text-dark">Ghi chú:</span> <span class="text-muted fst-italic"><?= htmlspecialchars($receipt['note'] ?: 'Không có ghi chú.') ?></span>
    </div>

    <!-- Bảng chi tiết hàng hóa -->
    <table class="table table-bordered mb-5">
        <thead>
            <tr>
                <th class="text-center" width="5%">STT</th>
                <th width="15%">Mã NVL</th>
                <th width="35%">Tên hàng hóa</th>
                <th class="text-center" width="10%">ĐVT</th>
                <th class="text-end" width="10%">S.Lượng</th>
                <th class="text-end" width="15%">Đơn giá (đ)</th>
                <th class="text-end" width="15%">Thành tiền (đ)</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $stt = 1; 
            $totalAmount = 0;
            foreach ($details as $item): 
                $lineTotal = $item['quantity'] * $item['unit_cost'];
                $totalAmount += $lineTotal;
            ?>
            <tr>
                <td class="text-center text-muted"><?= $stt++ ?></td>
                <td class="font-monospace text-secondary">#<?= htmlspecialchars($item['ingredient_code']) ?></td>
                <td class="fw-bold text-dark"><?= htmlspecialchars($item['ingredient_name']) ?></td>
                <td class="text-center"><?= htmlspecialchars($item['unit']) ?></td>
                <td class="text-end text-primary fw-bold"><?= number_format($item['quantity'], 2) ?></td>
                <td class="text-end"><?= number_format($item['unit_cost'], 0, ',', '.') ?></td>
                <td class="text-end fw-bold"><?= number_format($lineTotal, 0, ',', '.') ?></td>
            </tr>
            <?php endforeach; ?>
            
            <tr class="total-row">
                <td colspan="6" class="text-end text-uppercase" style="color: var(--charcoal);">Tổng giá trị phiếu nhập:</td>
                <td class="text-end"><?= number_format($totalAmount, 0, ',', '.') ?></td>
            </tr>
        </tbody>
    </table>

    <!-- Chữ ký -->
    <div class="row text-center mt-5 pt-4">
        <div class="col-4">
            <div class="fw-bold text-dark">Người lập phiếu</div>
            <div class="text-muted small fst-italic">(Ký, ghi rõ họ tên)</div>
            <div class="mt-5 pt-4 fw-bold"><?= htmlspecialchars($receipt['creator_name'] ?? '') ?></div>
        </div>
        <div class="col-4">
            <div class="fw-bold text-dark">Người giao hàng</div>
            <div class="text-muted small fst-italic">(Ký, ghi rõ họ tên)</div>
        </div>
        <div class="col-4">
            <div class="fw-bold text-dark">Thủ kho / Kế toán trưởng</div>
            <div class="text-muted small fst-italic">(Ký, đóng dấu)</div>
        </div>
    </div>
</div>

</body>
</html>