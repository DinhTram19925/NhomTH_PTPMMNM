<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi Tiết Phiếu Kiểm Kê - RoyalDine 5S</title>
    <!-- Thư viện Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f1f2f4; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        .receipt-container { 
            max-width: 1000px; 
            margin: 40px auto; 
            background: #fff; 
            padding: 40px; 
            border-radius: 4px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); 
            border-top: 10px solid #1a1d20; 
        }
        
        .receipt-divider { 
            border-bottom: 1px solid #c9a050; 
            margin-top: 25px;
            margin-bottom: 30px; 
        }
        
        .text-gold { color: #c9a050; }
        
        /* Nút In phiếu */
        .btn-print { 
            background-color: #1a1d20; 
            color: #c9a050; 
            border: none; 
            font-weight: 600; 
            transition: 0.3s; 
        }
        .btn-print:hover { 
            background-color: #2c3136; 
            color: #ffdf8a; 
        }

        /* Nút Quay lại */
        .btn-outline-gold {
            background-color: #ffffff;
            color: #495057;
            border: 1px solid #ced4da;
            font-weight: 500;
            transition: all 0.2s ease-in-out;
        }
        .btn-outline-gold:hover {
            color: #c9a050;
            border-color: #c9a050;
            background-color: #fffaf0;
        }

        /* Tùy chỉnh bảng */
        .table-custom th {
            background-color: #1a1d20 !important;
            color: #c9a050 !important;
            text-transform: uppercase;
            font-size: 0.85rem;
            padding: 12px;
            border: 1px solid #333;
        }
        .table-custom td {
            vertical-align: middle;
            border: 1px solid #dee2e6;
        }

        /* Chế độ in ấn */
        @media print {
            body { background-color: #fff; }
            .receipt-container { box-shadow: none; padding: 0; margin: 0; max-width: 100%; border-top: none; }
            .no-print { display: none !important; }
            .table-custom th { background-color: #1a1d20 !important; color: #c9a050 !important; -webkit-print-color-adjust: exact; border: 1px solid #000 !important; }
            .table-custom td { border: 1px solid #000 !important; }
        }
    </style>
</head>
<body>

<div class="container py-2">
    <!-- KHUNG GIẤY A4 BIÊN BẢN -->
    <div class="receipt-container">
        
        <!-- Nút bấm đặt bên TRONG khung giấy (Ẩn khi in) -->
        <div class="d-flex justify-content-between mb-4 no-print">
            <?php 
                $backUrl = (isset($_GET['from']) && $_GET['from'] === 'history') 
                           ? 'index.php?c=stock&a=index&tab=history' 
                           : 'index.php?c=stock&a=index&tab=dashboard';
            ?>
            <a href="<?= $backUrl ?>" class="btn btn-outline-gold rounded-3 px-4 shadow-sm">Quay lại</a>
            <button onclick="window.print()" class="btn btn-print rounded-3 px-4 shadow-sm d-flex align-items-center gap-2">
                🖨️ In Phiếu Kiểm Kê
            </button>
        </div>

        <!-- Header Phiếu -->
        <div class="d-flex justify-content-between align-items-end mt-2">
            <div>
                <h2 class="fw-bold mb-2 text-uppercase" style="font-size: 2.2rem; letter-spacing: 0.5px;">PHIẾU KIỂM KÊ KHO</h2>
                <p class="text-muted mb-0 fw-semibold" style="font-size: 1.05rem;">Số phiếu: <span class="fw-bold text-dark">#<?= htmlspecialchars($receipt['receipt_code'] ?? '') ?></span></p>
            </div>
            <div class="text-end">
                <h3 class="text-gold fw-bold mb-2">ROYALDINE 5S</h3>
                <p class="text-muted small mb-0">Chi nhánh Trung tâm<br>Hotline: 0909.888.999</p>
            </div>
        </div>
        
        <!-- Đường viền vàng phân cách -->
        <div class="receipt-divider"></div>

        <!-- Khối Thông tin 2 bên -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="p-3 bg-light rounded-3 h-100 border">
                    <h6 class="text-muted fw-bold small mb-3 text-uppercase">MỤC ĐÍCH / NỘI DUNG ĐIỀU CHỈNH</h6>
                    <p class="mb-1 fw-bold text-dark" style="font-size: 1rem;">
                        Kiểm kê & Cân bằng hệ thống
                    </p>
                    <p class="text-muted small mb-0">Điều chỉnh số lượng tồn kho trên phần mềm khớp với số liệu kiểm đếm thực tế.</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="p-3 bg-light rounded-3 h-100 border">
                    <h6 class="text-muted fw-bold small mb-3 text-uppercase">THÔNG TIN KIỂM KÊ</h6>
                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-muted">Ngày lập:</span>
                        <span class="fw-bold text-dark"><?= date('d/m/Y H:i', strtotime($receipt['created_at'])) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-muted">Người lập:</span>
                        <span class="fw-bold text-dark"><?= htmlspecialchars($receipt['creator_name'] ?? 'Không xác định') ?></span>
                    </div>
                    <div class="d-flex justify-content-between small align-items-center mt-2">
                        <span class="text-muted">Loại phiếu:</span>
                        <span class="badge bg-warning text-dark px-3 py-1 rounded-1">Kiểm kê</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Ghi chú chung -->
        <p class="mb-4 text-dark" style="font-size: 0.95rem;">
            <span class="fw-bold">Ghi chú chung:</span> <em class="text-muted"><?= htmlspecialchars($receipt['note'] ?? 'Không có') ?></em>
        </p>

        <!-- Bảng Chi Tiết Mặt Hàng Chênh Lệch & Lý Do -->
        <table class="table align-middle mb-5 table-custom">
            <thead class="text-center">
                <tr>
                    <th style="width: 50px;">STT</th>
                    <th style="width: 120px;">MÃ NVL</th>
                    <th class="text-start ps-3">TÊN HÀNG HÓA</th>
                    <th style="width: 80px;">ĐVT</th>
                    <th style="width: 130px;">ĐỘ LỆCH KHO</th>
                    <th class="text-start ps-3" style="width: 250px;">LÝ DO / GHI CHÚ CHÊNH LỆCH</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($details)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted fw-bold border">Không có mặt hàng nào bị chênh lệch. Kho khớp 100%.</td>
                    </tr>
                <?php else: ?>
                    <?php 
                        $stt = 1;
                        foreach ($details as $detail): 
                            $qty = (float)$detail['quantity'];
                    ?>
                    <tr>
                        <td class="text-center text-muted fw-bold"><?= $stt++ ?></td>
                        <td class="text-center text-muted fw-bold"><?= htmlspecialchars($detail['ingredient_code']) ?></td>
                        <td class="fw-bold text-dark ps-3"><?= htmlspecialchars($detail['ingredient_name']) ?></td>
                        <td class="text-center text-dark fw-bold"><?= htmlspecialchars($detail['unit']) ?></td>
                        
                        <!-- Độ lệch -->
                        <td class="text-center fw-bold <?= $qty > 0 ? 'text-success' : 'text-danger' ?>" style="font-size: 1.05rem;">
                            <?= $qty > 0 ? '+' : '' ?><?= number_format($qty, 2) ?>
                        </td>
                        
                        <!-- Cột Lý do chi tiết -->
                        <td class="text-start ps-3 text-muted small">
                            <?= htmlspecialchars($detail['reason'] ?? $detail['note'] ?? '-') ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <!-- Khu vực Chữ ký -->
        <div class="row mt-5 pt-3 text-center no-print">
            <div class="col-4">
                <p class="fw-bold mb-5">Người lập phiếu</p>
                <p class="text-muted small mt-5 pt-3"><em>(Ký, ghi rõ họ tên)</em></p>
            </div>
            <div class="col-4">
                <p class="fw-bold mb-5">Thủ kho</p>
                <p class="text-muted small mt-5 pt-3"><em>(Ký, đóng dấu)</em></p>
            </div>
            <div class="col-4">
                <p class="fw-bold mb-5">Quản lý / Kế toán</p>
                <p class="text-muted small mt-5 pt-3"><em>(Ký, đóng dấu)</em></p>
            </div>
        </div>
    </div>
</div>

</body>
</html>