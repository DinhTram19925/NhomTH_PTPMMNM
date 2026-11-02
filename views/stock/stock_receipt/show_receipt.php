<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chi tiết Phiếu Xuất - <?= htmlspecialchars($receipt['receipt_code'] ?? '') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --gold-primary: #c9a050;
            --gold-dark: #a8823b;
            --gold-light: #fdf8ee;
            --charcoal: #1a1d20;
        }
        body { background-color: #f0f2f5; font-family: 'Segoe UI', sans-serif; color: #333; }
        
        /* Cấu trúc khổ giấy A4 đồng bộ màu vàng đồng */
        .a4-container {
            max-width: 900px;
            margin: 30px auto;
            background: #fff;
            padding: 50px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border-top: 8px solid var(--charcoal);
            border-radius: 8px;
        }
        .receipt-header { border-bottom: 2px solid var(--gold-primary); padding-bottom: 20px; margin-bottom: 30px; }
        .receipt-title { color: var(--charcoal); font-weight: 800; letter-spacing: 2px; }
        
        .info-box { background: #f8f9fa; padding: 20px; border-radius: 8px; border: 1px solid #e9ecef; }
        .info-label { font-size: 0.85rem; color: #6c757d; text-transform: uppercase; font-weight: 700; }
        .info-value { font-size: 1.05rem; font-weight: 600; color: #212529; }
        
        .table-invoice th { background-color: var(--charcoal) !important; color: var(--gold-primary) !important; text-transform: uppercase; font-size: 0.9rem; text-align: center; }
        .table-invoice td { vertical-align: middle; font-weight: 500; border: 1px solid #dee2e6; }
        
        /* Nút quay lại đồng bộ */
        .btn-return-lux { 
            background-color: #ffffff; color: #495057; border: 1.5px solid rgba(201, 160, 80, 0.4); font-weight: 600; transition: all 0.2s ease; 
        }
        .btn-return-lux:hover { 
            background-color: var(--gold-light); color: var(--gold-dark); border-color: var(--gold-primary); 
        }
        
        @media print {
            body { background: #fff !important; }
            .a4-container { box-shadow: none; margin: 0; padding: 0; border: none; max-width: 100%; }
            .no-print { display: none !important; }
            .table-invoice th { -webkit-print-color-adjust: exact; color-adjust: exact; background-color: var(--charcoal) !important; color: var(--gold-primary) !important; }
        }
    </style>
</head>
<body>
<div class="a4-container">
    <!-- Nút công cụ -->
    <div class="d-flex justify-content-between mb-4 no-print">
        <a href="index.php?c=stock&a=index&tab=history" class="btn btn-return-lux px-4 py-2 shadow-sm rounded-3 text-decoration-none d-flex align-items-center gap-2">
             Quay lại 
        </a>
        <button onclick="window.print()" class="btn btn-dark fw-bold px-4 shadow-sm rounded-3 d-flex align-items-center gap-2" style="background: var(--charcoal); color: var(--gold-primary);">
            🖨️ In Phiếu Xuất
        </button>
    </div>

    <!-- Header Hóa Đơn -->
    <div class="receipt-header d-flex justify-content-between align-items-center">
        <div>
            <h2 class="receipt-title m-0">PHIẾU XUẤT KHO</h2>
            <div class="mt-2 text-muted fw-bold">Số phiếu: <span class="text-dark">#<?= htmlspecialchars($receipt['receipt_code'] ?? '') ?></span></div>
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
                <div class="info-label mb-2">Thông tin Bộ phận / Bếp nhận</div>
                <div class="info-value text-dark fw-bold">Xuất kho nội bộ / Chế biến bếp</div>
                <div class="mt-2 text-muted" style="font-size: 0.95rem;">Mục đích: Cấp phát nguyên liệu phục vụ kinh doanh hằng ngày.</div>
            </div>
        </div>
        <div class="col-md-5">
            <div class="info-box h-100">
                <div class="info-label mb-2">Thông tin Xuất kho</div>
                <div class="d-flex justify-content-between mt-1">
                    <span class="text-muted">Ngày lập:</span>
                    <span class="fw-bold"><?= isset($receipt['created_at']) ? date('d/m/Y H:i', strtotime($receipt['created_at'])) : 'N/A' ?></span>
                </div>
                <div class="d-flex justify-content-between mt-1">
                    <span class="text-muted">Người lập:</span>
                    <span class="fw-bold"><?= htmlspecialchars($receipt['creator_name'] ?? 'Không xác định') ?></span>
                </div>
                <div class="d-flex justify-content-between mt-2 align-items-center">
                    <span class="text-muted">Loại phiếu:</span>
                    <span class="badge bg-danger px-3 py-2">Xuất kho</span>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-4">
        <span class="fw-bold text-dark">Ghi chú:</span> <span class="text-muted fst-italic"><?= htmlspecialchars($receipt['note'] ?? 'Không có ghi chú.') ?></span>
    </div>

    <!-- Bảng chi tiết hàng hóa xuất -->
    <div class="table-responsive">
        <table class="table table-bordered table-invoice mb-5">
            <thead>
                <tr>
                    <th width="8%">STT</th>
                    <th width="18%">Mã NVL</th>
                    <th class="text-start ps-3" width="44%">Tên hàng hóa</th>
                    <th width="15%">ĐVT</th>
                    <th class="text-end pe-3" width="15%">S.Lượng xuất</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($details)): ?>
                    <tr><td colspan="5" class="text-center py-4 text-muted">Không có chi tiết mặt hàng xuất.</td></tr>
                <?php else: 
                    $stt = 1; 
                    foreach ($details as $item): 
                ?>
                <tr>
                    <td class="text-center text-muted"><?= $stt++ ?></td>
                    <td class="font-monospace text-secondary text-center">#<?= htmlspecialchars($item['ingredient_code'] ?? '') ?></td>
                    <td class="fw-bold text-dark ps-3"><?= htmlspecialchars($item['ingredient_name'] ?? 'NVL Không rõ') ?></td>
                    <td class="text-center"><?= htmlspecialchars($item['unit'] ?? '-') ?></td>
                    <td class="text-end text-dark fw-bold pe-3"><?= number_format($item['quantity'], 2) ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Chữ ký -->
    <div class="row text-center mt-4 pt-4 pb-5">
        <div class="col-4">
            <div class="fw-bold text-dark fs-6">Người lập phiếu</div>
            <div class="text-muted small fst-italic">(Ký, ghi rõ họ tên)</div>
            <div class="mt-5 pt-4 fw-bold fs-6"><?= htmlspecialchars($receipt['creator_name'] ?? '') ?></div>
        </div>
        <div class="col-4">
            <div class="fw-bold text-dark fs-6">Người nhận hàng</div>
            <div class="text-muted small fst-italic">(Ký, ghi rõ họ tên)</div>
        </div>
        <div class="col-4">
            <div class="fw-bold text-dark fs-6">Thủ kho</div>
            <div class="text-muted small fst-italic">(Ký, đóng dấu)</div>
        </div>
    </div>
</div>
</body>
</html>