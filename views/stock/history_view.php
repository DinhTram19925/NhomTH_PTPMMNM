<!-- ================= LỊCH SỬ GIAO DỊCH KHO ================= -->
<style>
    /* CSS cho 2 nút thao tác */
    .btn-action-edit, .btn-action-cancel {
        color: #6c757d; 
        border: 1px solid #dee2e6; 
        background: #ffffff; 
        transition: all 0.2s ease-in-out;
        text-decoration: none;
    }
    /* Khi hover nút Sửa -> Nền Vàng kim, Icon Trắng */
    .btn-action-edit:hover {
        background-color: #c9a050; 
        border-color: #c9a050; 
        color: #ffffff;
    }
    /* Khi hover nút Hủy -> Nền Đỏ, Icon Trắng */
    .btn-action-cancel:hover {
        background-color: #dc3545; 
        border-color: #dc3545; 
        color: #ffffff;
    }
    
    /* CSS Phân trang */
    .pagination-custom .page-link { color: #000; border-color: #dee2e6; }
    .pagination-custom .page-item.active .page-link { background-color: #c9a050; border-color: #c9a050; color: white; font-weight: bold; }
    .pagination-custom .page-link:hover { background-color: #f1ebd8; color: #000; }
    .pagination-custom .page-item.disabled .page-link { background-color: #e9ecef; color: #6c757d; }
</style>

<div class="lux-card p-3 mb-4 d-flex justify-content-between align-items-center">
    <!-- Form Lọc -->
    <form method="GET" action="index.php" class="d-flex gap-2 w-75">
        <input type="hidden" name="c" value="stock">
        <input type="hidden" name="a" value="index">
        <input type="hidden" name="tab" value="history">
        
        <input class="form-control form-control-sm rounded-3 w-50" type="search" name="search_hist" 
       placeholder="Tìm theo Mã phiếu, Tên người lập..." value="<?= htmlspecialchars($_GET['search_hist'] ?? '') ?>">
        
        <select class="form-select form-select-sm rounded-3 w-25" name="type_hist">
            <option value="">- Tất cả loại phiếu -</option>
            <option value="import" <?= (($_GET['type_hist'] ?? '') === 'import') ? 'selected' : '' ?>>Phiếu Nhập kho</option>
            <option value="export" <?= (($_GET['type_hist'] ?? '') === 'export') ? 'selected' : '' ?>>Phiếu Xuất kho</option>
        </select>
        
        <div class="d-flex gap-1">
            <button class="btn btn-gold-lux btn-sm rounded-3 px-3" type="submit">Lọc</button>
            <?php if (!empty($_GET['search_hist']) || !empty($_GET['type_hist'])): ?>
                <a href="index.php?c=stock&a=index&tab=history" class="btn btn-light btn-sm rounded-3 px-3 border text-muted fw-bold">✕ Hủy</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- BẢNG LỊCH SỬ -->
<div class="lux-card mb-4">
    <div class="table-responsive m-0">
        <table class="table lux-table align-middle mb-0">
            <thead>
                <tr>
                    <th class="ps-4">STT</th>
                    <th>Mã Phiếu</th>
                    <th>Loại Phiếu</th>
                    <th>Người lập phiếu</th>
                    <th>Thời gian tạo</th>
                    <th class="text-center pe-4">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($history_list)): ?>
                    <tr><td colspan="6" class="text-center py-5 text-muted fw-bold">📭 Không tìm thấy lịch sử giao dịch.</td></tr>
                <?php else: 
                    $stt = ($page_hist - 1) * 10 + 1; 
                    foreach ($history_list as $item): 
                ?>
                <tr>
                    <td class="ps-4 text-muted fw-bold"><?= $stt++ ?></td>
                    <td class="fw-bold text-dark"><?= htmlspecialchars($item['receipt_code']) ?></td>
                    <td>
                        <?php 
                            $rtype = strtolower($item['receipt_type']);
                            if (strpos($rtype, 'nhập') !== false || $rtype == 'import'): 
                        ?>
                            <span class="badge bg-success bg-opacity-10 text-success px-3 py-1 border border-success rounded-pill">Nhập kho</span>
                        <?php else: ?>
                            <span class="badge bg-danger bg-opacity-10 text-danger px-3 py-1 border border-danger rounded-pill">Xuất kho</span>
                        <?php endif; ?>
                    </td>
                    
                    <!-- Cột người lập phiếu -->
                    <td class="text-muted fw-semibold">
                        <?= htmlspecialchars($item['creator_name'] ?? $item['created_by'] ?? $item['user_id'] ?? 'Không xác định') ?>
                    </td>
                    
                    <!-- Cột thời gian tạo -->
                    <td class="fw-bold text-dark"><?= date('H:i - d/m/Y', strtotime($item['created_at'])) ?></td>
                    
                    <!-- CỘT THAO TÁC: Chỉ 1 nút Xem chi tiết (Hover vàng) -->
                    <!-- CỘT THAO TÁC: Dùng chung link của Dashboard -->
                    <td class="text-center pe-4 d-flex justify-content-center">
                        <a href="index.php?c=stock_receipt&a=show&code=<?= htmlspecialchars($item['receipt_code']) ?>" class="btn-action-edit rounded-3 d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;" title="Xem chi tiết">
                            <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        </a>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- PHÂN TRANG 10 DÒNG -->
    <?php if(!empty($history_list) && isset($maxPage_hist) && $maxPage_hist > 1): 
        $queryParams = $_GET;
        unset($queryParams['page_hist']);
        $queryString = http_build_query($queryParams);
    ?>
    <div class="p-3 border-top d-flex justify-content-end bg-white" style="border-radius: 0 0 10px 10px;">
        <nav>
            <ul class="pagination pagination-sm m-0 shadow-sm pagination-custom">
                <li class="page-item <?= ($page_hist <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?<?= $queryString ?>&page_hist=<?= $page_hist - 1 ?>">Trước</a>
                </li>
                <?php for($i = 1; $i <= $maxPage_hist; $i++): ?>
                    <li class="page-item <?= ($i == $page_hist) ? 'active' : '' ?>">
                        <a class="page-link" href="?<?= $queryString ?>&page_hist=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= ($page_hist >= $maxPage_hist) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?<?= $queryString ?>&page_hist=<?= $page_hist + 1 ?>">Sau</a>
                </li>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>