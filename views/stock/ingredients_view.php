<!-- ================= NGUYÊN VẬT LIỆU ================= -->
<div class="lux-card p-3 mb-4 d-flex justify-content-between align-items-center">
    <!-- Ràng buộc pattern và Lọc -->
    <!-- Ràng buộc pattern và Lọc -->
    <form method="GET" action="index.php" class="d-flex gap-2 w-75" onsubmit="return validateSearch(this);">
        <input type="hidden" name="c" value="stock">
        <input type="hidden" name="a" value="index">
        <input type="hidden" name="tab" value="ingredients">
        
        <input class="form-control form-control-sm rounded-3 w-25" type="search" name="search" id="searchInput" 
               pattern="^[a-zA-Z0-9\sÀ-ỹ-]+$" title="Chỉ nhập chữ, số và dấu gạch ngang" 
               placeholder="Mã hoặc Tên NVL..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
        
        <!-- Dropdown Nhóm Nguyên Liệu -->
        <select class="form-select form-select-sm rounded-3 w-25" name="group_filter">
            <option value="">- Tất cả nhóm NVL -</option>
            <?php 
            $groups = ['Thịt Cao Cấp', 'Hải Sản Tươi', 'Bơ Sữa & Gia Vị', 'Nấm & Rau củ', 'Đồ Uống Có Cồn', 'Đồ Uống Không Cồn'];
            foreach($groups as $grp): 
            ?>
                <option value="<?= $grp ?>" <?= (isset($_GET['group_filter']) && $_GET['group_filter'] == $grp) ? 'selected' : '' ?>>
                    <?= $grp ?>
                </option>
            <?php endforeach; ?>
        </select>

        <!-- Dropdown Đơn Vị Tính -->
        <select class="form-select form-select-sm rounded-3 w-25" name="unit_filter">
            <option value="">- Tất cả đơn vị tính -</option>
            <?php 
            $units = ['g', 'kg', 'ml', 'Lá'];
            foreach($units as $u): 
            ?>
                <option value="<?= $u ?>" <?= (isset($_GET['unit_filter']) && $_GET['unit_filter'] == $u) ? 'selected' : '' ?>>
                    <?= $u ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select class="form-select form-select-sm rounded-3 w-25"
        name="status_filter">

    <option value="">- Trạng thái tồn -</option>

    <option value="low"
        <?= (($_GET['status_filter'] ?? '') === 'low') ? 'selected' : '' ?>>
        Cảnh báo tồn thấp
    </option>

    <option value="out_of_stock"
        <?= (($_GET['status_filter'] ?? '') === 'out_of_stock') ? 'selected' : '' ?>>
        Đã hết hàng
    </option>

    <option value="safe"
        <?= (($_GET['status_filter'] ?? '') === 'safe') ? 'selected' : '' ?>>
        An toàn
    </option>

</select>
        
        <div class="d-flex gap-1">
            <button class="btn btn-gold-lux btn-sm rounded-3 px-3" type="submit">Lọc</button>
            
            <!-- NÚT XÓA LỌC HIỂN THỊ ĐỘNG -->
            <?php if (!empty($_GET['search']) || !empty($_GET['group_filter']) || !empty($_GET['unit_filter']) || !empty($_GET['status_filter'])): ?>
                <a href="index.php?c=stock&a=index&tab=ingredients" class="btn btn-light btn-sm rounded-3 px-3 border text-muted fw-bold" title="Hủy bỏ bộ lọc">
                    ✕ Hủy
                </a>
            <?php endif; ?>
        </div>
    </form>
    <div class="d-flex gap-2">
        <a href="index.php?c=stock&a=form_ingredient" class="btn btn-gold-lux btn-sm px-4 rounded-3">+ Thêm nguyên liệu</a>
        <button class="btn btn-outline-excel btn-sm px-3 rounded-3" onclick="exportToExcel()">
            <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" class="me-1"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="8" y1="13" x2="16" y2="13"></line><line x1="8" y1="17" x2="16" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
            Xuất Excel
        </button>
    </div>
</div>

<!-- BẢNG DỮ LIỆU -->
<div class="lux-card mb-4">
    <div class="table-responsive m-0">
        <table class="table lux-table align-middle mb-0">
            <thead>
                <tr>
                    <th class="ps-4">Mã NVL</th>
                    <th>Tên nguyên liệu</th>
                    <th class="text-center">Nhóm</th>
                    <th class="text-center">ĐVT</th>
                    <th class="text-end">Tồn hiện tại</th>
                    <th class="text-end">Tồn tối thiểu</th>
                    <th class="text-end">Giá vốn</th>
                    <th>Nhà cung cấp</th>
                    <th class="text-center">Trạng thái</th>
                    <th class="text-center pe-4">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($ingredients)): ?>
                    <tr><td colspan="10" class="text-center py-5 text-muted fw-bold">Chưa có dữ liệu nguyên vật liệu.</td></tr>
                <?php else: foreach ($ingredients as $item): 
                    $isLowStock = $item['stock_quantity'] <= $item['min_stock_level'];
                    
                    // Xác định màu sắc (class) dựa trên Tên Nhóm
                    $groupClass = 'badge-default';
                    $groupName = $item['group_name'] ?? 'Chưa phân nhóm';
                    switch ($groupName) {
                        case 'Thịt Cao Cấp': $groupClass = 'badge-meat'; break;
                        case 'Hải Sản Tươi': $groupClass = 'badge-seafood'; break;
                        case 'Bơ Sữa & Gia Vị': $groupClass = 'badge-dairy'; break;
                        case 'Nấm & Rau củ': $groupClass = 'badge-veg'; break;
                        case 'Đồ Uống Có Cồn': $groupClass = 'badge-alcohol'; break;
                        case 'Đồ Uống Không Cồn': $groupClass = 'badge-drink'; break;
                    }
                ?>
                <tr>
                    <td class="ps-4"><span class="badge-code">#<?= htmlspecialchars($item['ingredient_code']) ?></span></td>
                    <td class="item-name"><?= htmlspecialchars($item['name']) ?></td>
                    
                    <!-- Cột Nhóm được chia màu -->
                    <td class="text-center">
                        <span class="badge <?= $groupClass ?> px-3 py-2 rounded-pill fw-bold shadow-sm" style="font-size: 0.85rem;">
                            <?= htmlspecialchars($groupName) ?>
                        </span>
                    </td>
                    
                    <td class="text-center fw-semibold text-muted"><?= htmlspecialchars($item['unit']) ?></td>
                    
                    <!-- Số lượng tồn kho -->
                    <td class="text-end <?= $isLowStock ? 'stock-danger' : 'stock-safe' ?>"><?= number_format($item['stock_quantity'], 2) ?></td>
                    
                    <!-- Cột Tồn tối thiểu: In đậm và màu xám đậm dễ nhìn -->
                    <td class="text-end fw-bold text-secondary" style="font-size: 1.05rem;">
                        <?= number_format($item['min_stock_level'], 2) ?>
                    </td>
                    
                    <!-- Giá vốn Vàng Đồng -->
                    <td class="text-end highlight-price"><?= number_format($item['cost_price'], 0, ',', '.') ?> đ</td>
                    <td class="text-muted" style="font-size: 0.85rem;"><?= htmlspecialchars($item['supplier_name'] ?? 'Chưa xác định') ?></td>
                    
                    <td class="text-center">
                        <?php 
                            $stock = (float)$item['stock_quantity'];
                            $min = (float)$item['min_stock_level'];
                            
                            if ($stock <= 0): 
                        ?>
                            <span class="badge bg-danger px-3 py-1 rounded-pill shadow-sm">Đã hết hàng</span>
                        <?php elseif ($stock <= $min): ?>
                            <span class="badge badge-danger-lux px-3 py-1 rounded-pill">Tồn thấp</span>
                        <?php else: ?>
                            <span class="badge badge-safe px-3 py-1 rounded-pill">An toàn</span>
                        <?php endif; ?>
                    </td>
                    
                    <!-- Icon thao tác -->
                    <td class="text-center pe-4 d-flex justify-content-center gap-1">
                        <a href="index.php?c=stock&a=form_ingredient&id=<?= $item['id'] ?>" class="btn-icon btn-edit" title="Sửa">
                            <svg class="icon-action" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                        </a>
                        <button class="btn-icon btn-delete" title="Ngừng sử dụng" onclick="confirmDelete(<?= $item['id'] ?>, '<?= htmlspecialchars($item['name'], ENT_QUOTES) ?>')">
                            <svg class="icon-action" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line></svg>
                        </button>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- PHÂN TRANG (TỰ ĐỘNG THEO PHP) -->
    <?php if(!empty($ingredients) && isset($maxPage_ing) && $maxPage_ing > 1): 
        $page_ing = isset($_GET['page_ing']) ? (int)$_GET['page_ing'] : 1;
        $queryParams = $_GET;
        unset($queryParams['page_ing']);
        $queryString = http_build_query($queryParams);
    ?>
    <div class="p-3 border-top d-flex justify-content-end bg-white">
        <nav>
            <ul class="pagination pagination-sm m-0">
                <li class="page-item <?= ($page_ing <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?<?= $queryString ?>&page_ing=<?= $page_ing - 1 ?>">Trước</a>
                </li>
                <?php for($i = 1; $i <= $maxPage_ing; $i++): ?>
                    <li class="page-item <?= ($i == $page_ing) ? 'active' : '' ?>">
                        <a class="page-link" href="?<?= $queryString ?>&page_ing=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= ($page_ing >= $maxPage_ing) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?<?= $queryString ?>&page_ing=<?= $page_ing + 1 ?>">Sau</a>
                </li>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<script>
function confirmDelete(id, name) {
    var msg = '⚠️ Bạn có chắc chắn muốn xóa nguyên liệu: "' + name + '"?\n\n' +
              'Lưu ý: Hệ thống chỉ cho phép xóa những nguyên liệu chưa phát sinh giao dịch nhập kho và chưa được ghép vào công thức món ăn.';
              
    if(confirm(msg)) {
        window.location.href = 'index.php?c=stock&a=delete_ingredient&id=' + id;
    }
}
function exportToExcel() {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('a', 'export_excel'); // Đổi action thành export_excel
    window.location.href = 'index.php?' + urlParams.toString();
}
</script>