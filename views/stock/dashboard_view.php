<!-- ================= TỔNG QUAN KHO ================= -->
<style>
    /* Thêm hiệu ứng hover nổi lên một chút để user biết có thể click vào */
    .kpi-card-link {
        text-decoration: none !important;
        transition: all 0.3s ease;
        cursor: pointer;
        color: inherit;
    }
    .kpi-card-link:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 25px rgba(201, 160, 80, 0.15) !important;
        border-color: rgba(201, 160, 80, 0.4) !important;
    }
</style>

<div class="row g-4 mb-4">
    <!-- 1. Click đưa về tab Nguyên liệu (Tất cả) -->
    <div class="col-md-3">
        <a href="index.php?c=stock&a=index&tab=ingredients" class="kpi-card kpi-card-link">
            <div class="kpi-icon bg-primary bg-opacity-10 text-primary">📦</div>
            <div>
                <div class="text-muted small fw-bold text-uppercase">Tổng loại nguyên liệu</div>
                <h4 class="mb-0 fw-bold text-dark"><?= isset($kpis['total_ingredients']) ? number_format($kpis['total_ingredients']) : 0 ?></h4>
            </div>
        </a>
    </div>
    
    <!-- 2. Click đưa về tab Nguyên liệu + Ép bộ lọc "Cảnh báo tồn thấp" -->
    <div class="col-md-3">
        <a href="index.php?c=stock&a=index&tab=ingredients&status_filter=low" class="kpi-card kpi-card-link">
            <div class="kpi-icon bg-warning bg-opacity-10 text-warning">⚠️</div>
            <div>
                <div class="text-muted small fw-bold text-uppercase">Tồn kho mức thấp</div>
                <h4 class="mb-0 fw-bold text-warning"><?= isset($kpis['low_stock']) ? number_format($kpis['low_stock']) : 0 ?></h4>
            </div>
        </a>
    </div>
    
    <div class="col-md-3">
    <!-- Đã bổ sung class "kpi-card-link" của bạn vào đây -->
    <a href="index.php?c=stock&a=index&tab=ingredients&status_filter=out_of_stock" class="kpi-card-link text-decoration-none d-block h-100" style="color: inherit;">
        
        <div class="lux-card p-3 d-flex align-items-center gap-3 h-100">
            <div class="kpi-icon bg-danger bg-opacity-10 text-danger d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; border-radius: 10px; font-size: 20px;">
                🛑
            </div>
            <div>
                <div class="text-muted small fw-bold text-uppercase mb-1">Đã hết hàng</div>
                <h4 class="mb-0 fw-bold text-danger"><?= isset($kpis['out_of_stock']) ? number_format($kpis['out_of_stock']) : 0 ?></h4>
            </div>
        </div>
        
    </a>
</div>
    
    <!-- 4. Click đưa về tab Lịch sử (Phiếu nhập/xuất) -->
    <!-- 4. Click đưa về tab Lịch sử + Ép bộ lọc "Hôm nay" -->
    <div class="col-md-3">
        <!-- Chú ý link href đã được thêm &date_filter=today -->
        <a href="index.php?c=stock&a=index&tab=history&date_filter=today" class="kpi-card kpi-card-link">
            <div class="kpi-icon bg-success bg-opacity-10 text-success">📄</div>
            <div>
                <div class="text-muted small fw-bold text-uppercase">Phiếu tạo trong ngày</div>
                <h4 class="mb-0 fw-bold text-success"><?= isset($kpis['today_receipts']) ? number_format($kpis['today_receipts']) : 0 ?></h4>
            </div>
        </a>
    </div>
</div>
<!-- Bảng Cảnh báo & Hoạt động -->
<div class="row g-4">
    <div class="col-md-8">
        <div class="lux-card h-100">
            <div class="p-3 border-bottom bg-light d-flex justify-content-between align-items-center">
                <h6 class="m-0 fw-bold text-danger">🚨 CẢNH BÁO TỒN KHO THẤP</h6>
                <a href="index.php?c=stock_receipt&a=create&type=import" class="btn btn-sm btn-outline-danger px-3">Tạo phiếu nhập</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle m-0 text-sm">
                    <thead class="table-light text-muted">
                        <tr>
                            <th class="ps-4">Mã NVL</th>
                            <th>Tên Nguyên Liệu</th>
                            <th class="text-end">Tồn hiện tại</th>
                            <th class="text-end pe-4">Mức tối thiểu</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- ĐÃ SỬA THÔNG BÁO TẠI ĐÂY -->
                        <?php if (empty($alerts)): ?>
                            <tr><td colspan="4" class="text-center py-4 text-muted" style="font-size: 0.9rem;">Chưa có dữ liệu cảnh báo.</td></tr>
                        <?php else: foreach ($alerts as $al): ?>
                            <tr>
                                <td class="ps-4 fw-bold font-monospace text-secondary">#<?= htmlspecialchars($al['ingredient_code']) ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($al['name']) ?></td>
                                <td class="text-end fw-bold <?= $al['stock_quantity'] <= 0 ? 'text-danger' : 'text-warning' ?>"><?= number_format($al['stock_quantity'], 2) ?></td>
                                <td class="text-end pe-4 text-muted"><?= number_format($al['min_stock_level'], 2) ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- CỘT GIAO DỊCH GẦN ĐÂY -->
    <div class="col-md-4">
        <div class="lux-card h-100">
            <div class="p-3 border-bottom bg-light">
                <h6 class="m-0 fw-bold text-dark">🕒 GIAO DỊCH GẦN ĐÂY</h6>
            </div>
            <div class="p-3">
                <style>
                    /* CSS tạo hiệu ứng mượt mà khi hover vào giao dịch */
                    .act-link { transition: all 0.2s ease; border-radius: 8px; padding: 8px; margin-left: -8px; margin-right: -8px; }
                    .act-link:hover { background-color: #fdf8ee; }
                    .act-link:hover .act-title { color: #a8823b !important; }
                    .act-arrow { opacity: 0; transition: opacity 0.2s, transform 0.2s; transform: translateX(-5px); }
                    .act-link:hover .act-arrow { opacity: 1; transform: translateX(0); }
                </style>
                <?php if (empty($activities)): ?>
                    <div class="text-center text-muted py-3" style="font-size: 0.9rem;">Chưa có giao dịch nhập/xuất nào.</div>
                <?php else: foreach ($activities as $act): 
                    $isImport = ($act['receipt_type'] == 'Import');
                    // Tạo đường dẫn truyền mã phiếu để sau này làm trang xem chi tiết
                    $detailUrl = "index.php?c=stock_receipt&a=show&code=" . htmlspecialchars($act['receipt_code']);
                ?>
                    <a href="<?= $detailUrl ?>" class="d-flex gap-3 mb-2 border-bottom pb-2 text-decoration-none act-link">
                        <div class="<?= $isImport ? 'text-success' : 'text-danger' ?> fw-bold text-center" style="font-size: 0.9rem; min-width: 45px;">
                            <?= date('H:i', strtotime($act['created_at'])) ?><br>
                            <small class="text-muted" style="font-size: 0.75rem;"><?= date('d/m', strtotime($act['created_at'])) ?></small>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-bold text-dark act-title" style="font-size: 0.95rem;">
                                <?= $isImport ? 'Nhập kho' : 'Xuất kho' ?> (<?= htmlspecialchars($act['receipt_code']) ?>)
                            </div>
                            <small class="text-muted">Người lập ID: <?= $act['user_id'] ?></small>
                        </div>
                        <!-- Icon mũi tên nhỏ hiện ra khi hover -->
                        <div class="align-self-center text-muted act-arrow">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
                        </div>
                    </a>
                <?php endforeach; endif; ?>
            </div>
            <!-- Thanh Phân trang Mini cho Giao dịch -->
        <?php if(isset($maxPage_trans) && $maxPage_trans > 1): ?>
        <style>
            /* Tùy chỉnh màu sắc riêng cho thanh phân trang này */
            .pagination-custom .page-link {
                color: #000; /* Màu chữ mặc định */
                border-color: #dee2e6; /* Màu viền */
            }
            .pagination-custom .page-item.active .page-link {
                background-color: #c9a050; /* Màu nền vàng đồng */
                border-color: #c9a050;     /* Màu viền vàng đồng */
                color: white;              /* Màu chữ trắng */
                font-weight: bold;
            }
            .pagination-custom .page-link:hover {
                background-color: #f1ebd8; /* Màu nền khi hover */
                color: #000;
            }
            .pagination-custom .page-item.disabled .page-link {
                background-color: #e9ecef;
                color: #6c757d;
            }
        </style>
        
        <div class="d-flex justify-content-center mt-3 pt-3 border-top">
            <nav>
                <ul class="pagination pagination-sm mb-0 shadow-sm pagination-custom">
                    <!-- Nút Trước -->
                    <li class="page-item <?= ($page_trans <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?c=stock&a=index&tab=dashboard&page_trans=<?= $page_trans - 1 ?>">Trước</a>
                    </li>
                    
                    <!-- Hiển thị các số trang (1, 2, 3...) -->
                    <?php for($i = 1; $i <= $maxPage_trans; $i++): ?>
                        <li class="page-item <?= ($i == $page_trans) ? 'active' : '' ?>">
                            <a class="page-link" href="?c=stock&a=index&tab=dashboard&page_trans=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <!-- Nút Sau -->
                    <li class="page-item <?= ($page_trans >= $maxPage_trans) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?c=stock&a=index&tab=dashboard&page_trans=<?= $page_trans + 1 ?>">Sau</a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
        </div>
    </div>
</div>