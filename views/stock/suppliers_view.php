<!-- ================= NHÀ CUNG CẤP ================= -->
<div class="lux-card p-3 mb-4 d-flex justify-content-between align-items-center">
    <!-- Form Lọc Nhà Cung Cấp -->
    <form method="GET" action="index.php" class="d-flex gap-2 w-75">
        <input type="hidden" name="c" value="stock">
        <input type="hidden" name="a" value="index">
        <input type="hidden" name="tab" value="suppliers">
        
        <!-- Tìm kiếm theo Tên hoặc Số điện thoại -->
        <input class="form-control form-control-sm rounded-3 w-50" type="search" name="search" 
               placeholder="Tìm theo Tên hoặc Số điện thoại NCC..." 
               value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
        
        <!-- Lọc trạng thái -->
        <select class="form-select form-select-sm rounded-3 w-25" name="status_filter">
            <option value="">- Tất cả trạng thái -</option>
            <option value="active" <?= (($_GET['status_filter'] ?? '') === 'active') ? 'selected' : '' ?>>Đang hợp tác</option>
            <option value="inactive" <?= (($_GET['status_filter'] ?? '') === 'inactive') ? 'selected' : '' ?>>Ngừng hợp tác</option>
        </select>
        
        <div class="d-flex gap-1">
            <button class="btn btn-gold-lux btn-sm rounded-3 px-3" type="submit">Lọc</button>
            <?php if (!empty($_GET['search']) || !empty($_GET['status_filter'])): ?>
                <a href="index.php?c=stock&a=index&tab=suppliers" class="btn btn-light btn-sm rounded-3 px-3 border text-muted fw-bold" title="Hủy bỏ bộ lọc">✕ Hủy</a>
            <?php endif; ?>
        </div>
    </form>

    <div class="d-flex gap-2">
        <a href="index.php?c=stock&a=form_supplier" class="btn btn-gold-lux btn-sm px-4 rounded-3">+ Thêm NCC</a>
        <button class="btn btn-outline-excel btn-sm px-3 rounded-3" onclick="exportSuppliersToExcel()">
            <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" class="me-1"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="8" y1="13" x2="16" y2="13"></line><line x1="8" y1="17" x2="16" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
            Xuất Excel
        </button>
    </div>
</div>

<!-- BẢNG DỮ LIỆU NHÀ CUNG CẤP -->
<div class="lux-card mb-4">
    <div class="table-responsive m-0">
        <table class="table lux-table align-middle mb-0">
            <thead>
                <tr>
                    <th class="ps-4">STT</th>
                    <th>Tên Nhà Cung Cấp</th>
                    <th>Người liên hệ</th>
                    <th class="text-center">Số điện thoại</th>
                    <th>Địa chỉ</th>
                    <th class="text-end">Công nợ (VNĐ)</th>
                    <th class="text-center">Trạng thái</th>
                    <th class="text-center pe-4">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($suppliers)): ?>
                    <tr><td colspan="8" class="text-center py-5 text-muted fw-bold">📭 Chưa có dữ liệu nhà cung cấp.</td></tr>
                <?php else: 
                    // Tính STT dựa trên trang hiện tại
                    $page_sup = isset($_GET['page_sup']) ? (int)$_GET['page_sup'] : 1;
                    $stt = ($page_sup - 1) * 10 + 1; // 10 là số limit dòng trên trang
                    foreach ($suppliers as $sup): 
                ?>
                <tr>
                    <td class="ps-4 text-muted fw-bold"><?= $stt++ ?></td>
                    <td class="fw-bold text-dark"><?= htmlspecialchars($sup['name']) ?></td>
                    <td><?= htmlspecialchars($sup['contact_person'] ?? '---') ?></td>
                    <td class="text-center fw-semibold"><?= htmlspecialchars($sup['phone'] ?? '---') ?></td>
                    <td><span class="text-truncate d-inline-block" style="max-width: 250px;" title="<?= htmlspecialchars($sup['address'] ?? '') ?>"><?= htmlspecialchars($sup['address'] ?? '---') ?></span></td>
                    
                    <!-- Cột Công Nợ in đậm đỏ nếu có nợ, xanh nếu đã thanh toán -->
                    <td class="text-end fw-bold <?= $sup['debt_balance'] > 0 ? 'text-danger' : 'text-success' ?>">
                        <?= number_format($sup['debt_balance'], 0, ',', '.') ?> đ
                    </td>
                    
                    <td class="text-center">
                        <?php if($sup['status'] === 'active'): ?>
                            <span class="badge bg-success bg-opacity-10 text-success px-3 py-1 rounded-pill border border-success">Đang hợp tác</span>
                        <?php else: ?>
                            <span class="badge bg-secondary bg-opacity-10 text-secondary px-3 py-1 rounded-pill border border-secondary">Ngừng hợp tác</span>
                        <?php endif; ?>
                    </td>
                    
                    <!-- Icon thao tác -->
                    <td class="text-center pe-4 d-flex justify-content-center gap-1">
                        <a href="index.php?c=stock&a=form_supplier&id=<?= $sup['id'] ?>" class="btn-icon btn-edit" title="Sửa">
                            <svg class="icon-action" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                        </a>
                        <button class="btn-icon btn-delete" title="Xóa" onclick="confirmDeleteSupplier(<?= $sup['id'] ?>, '<?= htmlspecialchars($sup['name'], ENT_QUOTES) ?>')">
                            <svg class="icon-action" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line></svg>
                        </button>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- PHÂN TRANG -->
    <?php if(!empty($suppliers) && isset($maxPage_sup) && $maxPage_sup > 1): 
        $queryParams = $_GET;
        unset($queryParams['page_sup']);
        $queryString = http_build_query($queryParams);
    ?>
    <style>
        .pagination-custom .page-link { color: #000; border-color: #dee2e6; }
        .pagination-custom .page-item.active .page-link { background-color: #c9a050; border-color: #c9a050; color: white; font-weight: bold; }
        .pagination-custom .page-link:hover { background-color: #f1ebd8; color: #000; }
        .pagination-custom .page-item.disabled .page-link { background-color: #e9ecef; color: #6c757d; }
    </style>
    
    <div class="p-3 border-top d-flex justify-content-end bg-white" style="border-radius: 0 0 10px 10px;">
        <nav>
            <ul class="pagination pagination-sm m-0 shadow-sm pagination-custom">
                <li class="page-item <?= ($page_sup <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?<?= $queryString ?>&page_sup=<?= $page_sup - 1 ?>">Trước</a>
                </li>
                <?php for($i = 1; $i <= $maxPage_sup; $i++): ?>
                    <li class="page-item <?= ($i == $page_sup) ? 'active' : '' ?>">
                        <a class="page-link" href="?<?= $queryString ?>&page_sup=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= ($page_sup >= $maxPage_sup) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?<?= $queryString ?>&page_sup=<?= $page_sup + 1 ?>">Sau</a>
                </li>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<script>
function confirmDeleteSupplier(id, name) {
    var msg = '⚠️ Bạn có chắc chắn muốn xóa nhà cung cấp: "' + name + '"?\n\n' +
              'Lưu ý: Chỉ có thể xóa nếu NCC này chưa phát sinh giao dịch nhập kho.';
              
    if(confirm(msg)) {
        window.location.href = 'index.php?c=stock&a=delete_supplier&id=' + id;
    }
}

function exportSuppliersToExcel() {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('a', 'export_suppliers_excel'); // Sử dụng action xuất excel riêng cho NCC
    window.location.href = 'index.php?' + urlParams.toString();
}
</script>