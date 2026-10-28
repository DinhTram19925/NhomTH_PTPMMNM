<?php
$bomConfiguredCount = 0;
foreach ($items as $it) {
    if ($it['bom_count'] > 0) $bomConfiguredCount++;
}
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="fw-bold mb-0">🍽️ Quản Lý Thực Đơn 5 Sao, Định Lượng (BOM) & Sở Thích Phục Vụ</h3>
        <small class="text-muted">Thiết lập món ăn Fine Dining, công thức trừ kho tự động (TV1 → TV4) và tùy chọn chế biến cho POS/KDS (TV1 → TV3)</small>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-dark fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#categoryModal">
            📁 + Nhóm Thực Đơn
        </button>
        <button class="btn btn-warning fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#menuItemModal" onclick="openAddMenuItem()">
            ➕ Thêm Món / Set Menu 5 Sao
        </button>
    </div>
</div>

<?php if (!empty($_SESSION['flash_msg'])): ?>
    <div class="alert alert-success py-2 alert-dismissible fade show">
        ✅ <?= $_SESSION['flash_msg']; unset($_SESSION['flash_msg']); ?>
    </div>
<?php endif; ?>
<?php if (!empty($_SESSION['flash_err'])): ?>
    <div class="alert alert-danger py-2 alert-dismissible fade show">
        ⚠️ <?= $_SESSION['flash_err']; unset($_SESSION['flash_err']); ?>
    </div>
<?php endif; ?>

<!-- Thanh Thống kê nhanh & Bộ lọc Danh mục Thực đơn -->
<div class="row g-3 mb-3 align-items-center">
    <div class="col-md-8">
        <div class="btn-group flex-wrap shadow-sm">
            <a href="index.php?c=menu&a=index" class="btn btn-sm <?= $catFilter === 0 ? 'btn-dark text-warning fw-bold' : 'btn-outline-dark bg-white' ?>">
                Tất cả (<?= count($items) ?>)
            </a>
            <?php foreach($categories as $c): ?>
                <a href="index.php?c=menu&a=index&cat_id=<?= $c['id'] ?>" 
                   class="btn btn-sm <?= $catFilter === (int)$c['id'] ? 'btn-dark text-warning fw-bold' : 'btn-outline-dark bg-white' ?>">
                    <?= htmlspecialchars($c['name']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="col-md-4 text-end">
        <span class="badge bg-dark p-2">
            🧪 Đã thiết lập BOM: <b class="text-warning"><?= $bomConfiguredCount ?> / <?= count($items) ?></b> món
        </span>
    </div>
</div>

<!-- Bảng Danh sách Thực đơn 5 sao -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <table class="table table-bordered table-hover align-middle mb-0 bg-white">
            <thead class="table-dark">
                <tr>
                    <th style="width: 8%;">Mã món</th>
                    <th style="width: 20%;">Tên món / Set Menu</th>
                    <th style="width: 11%;">Giá bán</th>
                    <th style="width: 24%;">Định lượng nguyên liệu (BOM)</th>
                    <th style="width: 20%;">Sở thích phục vụ (Modifiers)</th>
                    <th style="width: 7%;" class="text-center">Trạng thái</th>
                    <th style="width: 10%;" class="text-center">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Chưa có món ăn nào trong nhóm thực đơn này.</td></tr>
                <?php else: foreach($items as $item): ?>
                <tr>
                    <td><code><?= htmlspecialchars($item['item_code']) ?></code></td>
                    <td>
                        <div class="fw-bold text-dark"><?= htmlspecialchars($item['name']) ?></div>
                        <small class="badge bg-light text-secondary border"><?= htmlspecialchars($item['category_name']) ?> • ĐVT: <?= htmlspecialchars($item['unit']) ?></small>
                    </td>
                    <td class="text-danger fw-bold"><?= number_format($item['price']) ?> đ</td>
                    <td>
                        <?php if (!empty($item['bom_list'])): ?>
                            <div class="small text-dark mb-1"><?= htmlspecialchars($item['bom_list']) ?></div>
                        <?php else: ?>
                            <div class="small text-danger fst-italic mb-1">⚠️ Chưa thiết lập công thức trừ kho</div>
                        <?php endif; ?>
                        <button class="btn btn-sm btn-outline-success py-0 px-2" 
                                onclick="openBomModal(<?= $item['id'] ?>, '<?= htmlspecialchars($item['name'], ENT_QUOTES) ?>')">
                            🧪 Cấu hình BOM (<?= (int)$item['bom_count'] ?>)
                        </button>
                    </td>
                    <td>
                        <?php if (!empty($item['modifier_list'])): ?>
                            <div class="small text-muted mb-1"><?= htmlspecialchars($item['modifier_list']) ?></div>
                        <?php else: ?>
                            <div class="small text-muted fst-italic mb-1">Không có tùy chọn đi kèm</div>
                        <?php endif; ?>
                        <button class="btn btn-sm btn-outline-primary py-0 px-2" 
                                onclick="openModModal(<?= $item['id'] ?>, '<?= htmlspecialchars($item['name'], ENT_QUOTES) ?>')">
                            🌶️ Sở thích (<?= isset($modByItem[$item['id']]) ? count($modByItem[$item['id']]) : 0 ?>)
                        </button>
                    </td>
                    <td class="text-center">
                        <a href="index.php?c=menu&a=toggleMenuStatus&id=<?= $item['id'] ?>" 
                           class="text-decoration-none"
                           title="Bấm để đổi nhanh trạng thái Đang bán / Tạm hết">
                            <?php if ($item['status'] === 'Available'): ?>
                                <span class="badge bg-success">Đang bán</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Tạm hết</span>
                            <?php endif; ?>
                        </a>
                    </td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-dark" title="Sửa món ăn"
                                    onclick='openEditMenuItem(<?= json_encode($item, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                ✏️
                            </button>
                            <a href="index.php?c=menu&a=deleteMenuItem&id=<?= $item['id'] ?>" 
                               class="btn btn-outline-danger" title="Xóa món ăn"
                               onclick="return confirm('Xác nhận xóa món <?= htmlspecialchars($item['name'], ENT_QUOTES) ?> cùng toàn bộ công thức BOM?')">
                                🗑️
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ==================== MODAL 1: THÊM / SỬA MÓN ĂN & SET MENU ==================== -->
<div class="modal fade" id="menuItemModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="index.php?c=menu&a=saveMenuItem" class="modal-content">
            <div class="modal-header bg-dark text-warning">
                <h5 class="modal-title fw-bold" id="menuItemModalTitle">➕ Thêm Món Ăn 5 Sao Mới</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="item_id" id="form_item_id" value="0">
                <div class="row mb-3">
                    <div class="col-md-5">
                        <label class="form-label fw-semibold">Mã món ăn</label>
                        <input type="text" name="item_code" id="form_item_code" class="form-control" placeholder="VD: RD-06" required>
                    </div>
                    <div class="col-md-7">
                        <label class="form-label fw-semibold">Nhóm thực đơn (Course)</label>
                        <select name="category_id" id="form_category_id" class="form-select" required>
                            <?php foreach($categories as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Tên món ăn / Set Menu Fine Dining</label>
                    <input type="text" name="name" id="form_name" class="form-control" placeholder="VD: Gan ngỗng Pháp áp chảo sốt táo" required>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Đơn vị tính</label>
                        <input type="text" name="unit" id="form_unit" class="form-control" value="Phần" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Giá bán (VNĐ)</label>
                        <input type="number" name="price" id="form_price" class="form-control" placeholder="850000" min="0" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Trạng thái</label>
                        <select name="status" id="form_status" class="form-select">
                            <option value="Available">Đang bán (Available)</option>
                            <option value="Unavailable">Tạm hết (Out of stock)</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="submit" class="btn btn-warning fw-bold">💾 Lưu Món Ăn</button>
            </div>
        </form>
    </div>
</div>

<!-- ==================== MODAL 2: THÊM NHÓM THỰC ĐƠN MỚI ==================== -->
<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <form method="POST" action="index.php?c=menu&a=saveCategory" class="modal-content">
            <div class="modal-header bg-dark text-warning">
                <h6 class="modal-title fw-bold">📁 Thêm Nhóm Thực Đơn</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label small fw-semibold">Tên nhóm thực đơn</label>
                <input type="text" name="category_name" class="form-control" placeholder="VD: Set Menu VIP 5 Sao" required>
            </div>
            <div class="modal-footer py-1">
                <button type="submit" class="btn btn-sm btn-warning fw-bold w-100">Thêm Nhóm</button>
            </div>
        </form>
    </div>
</div>

<!-- ==================== MODAL 3: THIẾT LẬP CÔNG THỨC ĐỊNH LƯỢNG (BOM) ==================== -->
<div class="modal fade" id="bomModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-dark text-warning">
                <h5 class="modal-title fw-bold">🧪 Định Lượng Nguyên Vật Liệu (BOM): <span id="bom_item_title" class="text-white"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-secondary py-2 small">
                    💡 <b>Điểm chạm liên kết (TV1 → TV3 → TV4):</b> Khi Thu ngân/Bếp hoàn tất đơn hàng trên POS, hệ thống tự động nhân số lượng món bán ra với định lượng bên dưới để trừ tồn kho nguyên vật liệu.
                </div>
                <form method="POST" action="index.php?c=menu&a=addBom" class="row g-2 align-items-end mb-3 p-3 bg-light rounded border">
                    <input type="hidden" name="menu_item_id" id="bom_menu_item_id" value="0">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Chọn nguyên vật liệu từ Kho (Bảng ingredients)</label>
                        <select name="ingredient_id" class="form-select" required>
                            <?php foreach($ingredients as $ing): ?>
                                <option value="<?= $ing['id'] ?>">
                                    <?= htmlspecialchars($ing['name']) ?> (ĐVT chuẩn: <?= htmlspecialchars($ing['unit']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Định lượng / 1 phần</label>
                        <input type="number" step="0.01" min="0.01" name="quantity_required" class="form-control" placeholder="VD: 200" required>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-success w-100 fw-bold">➕ Lưu vào BOM</button>
                    </div>
                </form>

                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-secondary">
                        <tr>
                            <th>Nguyên vật liệu cấu thành</th>
                            <th class="text-center">Định lượng tiêu hao / 1 phần</th>
                            <th class="text-center">Đơn vị</th>
                            <th class="text-center">Xóa</th>
                        </tr>
                    </thead>
                    <tbody id="bom_table_body"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ==================== MODAL 4: THIẾT LẬP SỞ THÍCH PHỤC VỤ (MODIFIERS) ==================== -->
<div class="modal fade" id="modModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-dark text-warning">
                <h5 class="modal-title fw-bold">🌶️ Sở Thích Phục Vụ (Modifiers): <span id="mod_item_title" class="text-white"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="index.php?c=menu&a=addModifier" class="row g-2 align-items-end mb-3 p-3 bg-light rounded border">
                    <input type="hidden" name="menu_item_id" id="mod_menu_item_id" value="0">
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Nhóm tùy chọn</label>
                        <select name="modifier_group" class="form-select">
                            <option value="Độ chín (Doneness)">Độ chín (Doneness)</option>
                            <option value="Sốt dùng kèm">Sốt dùng kèm</option>
                            <option value="Lưu ý dị ứng">Lưu ý dị ứng</option>
                            <option value="Yêu cầu thêm">Yêu cầu thêm (Topping)</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Tên tùy chọn hiển thị trên POS/KDS</label>
                        <input type="text" name="modifier_name" class="form-control" placeholder="VD: Medium Rare / Không hành..." required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Phụ thu thêm (VNĐ)</label>
                        <input type="number" name="extra_price" class="form-control" value="0" min="0" step="1000">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100 fw-bold">➕ Thêm</button>
                    </div>
                </form>

                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-secondary">
                        <tr>
                            <th>Nhóm tùy chọn</th>
                            <th>Chi tiết sở thích phục vụ</th>
                            <th class="text-end">Phụ thu</th>
                            <th class="text-center">Xóa</th>
                        </tr>
                    </thead>
                    <tbody id="mod_table_body"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const bomData = <?= json_encode($bomByItem, JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const modData = <?= json_encode($modByItem, JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

function openAddMenuItem() {
    document.getElementById('menuItemModalTitle').innerText = '➕ Thêm Món Ăn / Set Menu 5 Sao';
    document.getElementById('form_item_id').value = '0';
    document.getElementById('form_item_code').value = '';
    document.getElementById('form_name').value = '';
    document.getElementById('form_unit').value = 'Phần';
    document.getElementById('form_price').value = '';
    document.getElementById('form_status').value = 'Available';
}

function openEditMenuItem(item) {
    document.getElementById('menuItemModalTitle').innerText = '✏️ Cập Nhật Món Ăn: ' + item.name;
    document.getElementById('form_item_id').value = item.id;
    document.getElementById('form_item_code').value = item.item_code;
    document.getElementById('form_category_id').value = item.category_id;
    document.getElementById('form_name').value = item.name;
    document.getElementById('form_unit').value = item.unit;
    document.getElementById('form_price').value = parseFloat(item.price);
    document.getElementById('form_status').value = item.status;
    new bootstrap.Modal(document.getElementById('menuItemModal')).show();
}

function openBomModal(itemId, itemName) {
    document.getElementById('bom_menu_item_id').value = itemId;
    document.getElementById('bom_item_title').innerText = itemName;
    const tbody = document.getElementById('bom_table_body');
    tbody.innerHTML = '';
    const list = bomData[itemId] || [];

    if (list.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">Chưa có nguyên liệu nào trong công thức của món này.</td></tr>';
    } else {
        list.forEach(row => {
            tbody.innerHTML += `
                <tr>
                    <td class="fw-semibold">${row.ingredient_name}</td>
                    <td class="text-center fw-bold text-success">${row.quantity_required}</td>
                    <td class="text-center"><code>${row.unit}</code></td>
                    <td class="text-center">
                        <a href="index.php?c=menu&a=deleteBom&menu_item_id=${itemId}&ingredient_id=${row.ingredient_id}" 
                           class="btn btn-sm btn-outline-danger py-0">✖</a>
                    </td>
                </tr>`;
        });
    }
    new bootstrap.Modal(document.getElementById('bomModal')).show();
}

function openModModal(itemId, itemName) {
    document.getElementById('mod_menu_item_id').value = itemId;
    document.getElementById('mod_item_title').innerText = itemName;
    const tbody = document.getElementById('mod_table_body');
    tbody.innerHTML = '';
    const list = modData[itemId] || [];

    if (list.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">Chưa có sở thích phục vụ nào cho món này.</td></tr>';
    } else {
        list.forEach(row => {
            const priceText = parseFloat(row.extra_price) > 0 
                ? '+' + Number(row.extra_price).toLocaleString('vi-VN') + ' đ' 
                : '<span class="text-muted">Miễn phí</span>';
            tbody.innerHTML += `
                <tr>
                    <td><span class="badge bg-dark">${row.modifier_group}</span></td>
                    <td class="fw-semibold">${row.modifier_name}</td>
                    <td class="text-end fw-bold text-danger">${priceText}</td>
                    <td class="text-center">
                        <a href="index.php?c=menu&a=deleteModifier&id=${row.id}&menu_item_id=${itemId}" 
                           class="btn btn-sm btn-outline-danger py-0">✖</a>
                    </td>
                </tr>`;
        });
    }
    new bootstrap.Modal(document.getElementById('modModal')).show();
}

// Tự động mở lại Modal BOM hoặc Modifiers sau khi vừa Thêm/Xóa xong để thao tác liên tục
window.addEventListener('DOMContentLoaded', () => {
    const params = new URLSearchParams(window.location.search);
    if (params.has('open_bom')) {
        const id = params.get('open_bom');
        openBomModal(id, 'Món #' + id);
    } else if (params.has('open_mod')) {
        const id = params.get('open_mod');
        openModModal(id, 'Món #' + id);
    }
});
</script>
