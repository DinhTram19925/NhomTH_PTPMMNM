<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="fw-bold mb-0">🍽️ Quản Lý Thực Đơn 5 Sao & Định Lượng (BOM)</h3>
    <span class="badge bg-dark p-2">Phân hệ TV1: Đinh Thị Mai Trâm</span>
</div>

<?php if (!empty($_SESSION['flash_msg'])): ?>
    <div class="alert alert-success py-2"><?= $_SESSION['flash_msg']; unset($_SESSION['flash_msg']); ?></div>
<?php endif; ?>
<?php if (!empty($_SESSION['flash_err'])): ?>
    <div class="alert alert-danger py-2"><?= $_SESSION['flash_err']; unset($_SESSION['flash_err']); ?></div>
<?php endif; ?>

<!-- Form Thêm Món Ăn Mới -->
<div class="card p-3 mb-4 shadow-sm border-0">
    <h6 class="fw-bold text-primary mb-3">➕ Thêm Món Ăn Mới Vào Thực Đơn</h6>
    <form method="POST" action="index.php?c=menu&a=save" class="row g-2">
        <div class="col-md-2">
            <input type="text" name="item_code" class="form-control" placeholder="Mã món (VD: RD-05)" required>
        </div>
        <div class="col-md-3">
            <input type="text" name="name" class="form-control" placeholder="Tên món ăn Fine Dining" required>
        </div>
        <div class="col-md-2">
            <select name="category_id" class="form-select">
                <?php foreach($categories as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-1">
            <input type="text" name="unit" class="form-control" value="Phần" placeholder="ĐVT">
        </div>
        <div class="col-md-2">
            <input type="number" name="price" class="form-control" placeholder="Giá bán (VNĐ)" min="0" required>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-warning w-100 fw-bold">➕ Thêm Món</button>
        </div>
    </form>
</div>

<!-- Bảng Danh sách Thực đơn -->
<div class="table-responsive">
<table class="table table-bordered table-hover bg-white shadow-sm align-middle">
    <thead class="table-dark">
        <tr>
            <th>Mã</th>
            <th>Tên món ăn</th>
            <th>Nhóm thực đơn</th>
            <th class="text-end">Giá bán</th>
            <th class="text-end">Giá vốn (BOM)</th>
            <th>Công thức Định lượng (BOM)</th>
            <th>Trạng thái</th>
            <th class="text-center" style="width: 220px;">Thao tác</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($items as $item): ?>
        <tr>
            <td><code><?= htmlspecialchars($item['item_code']) ?></code></td>
            <td>
                <div class="fw-bold"><?= htmlspecialchars($item['name']) ?></div>
                <small class="text-muted">ĐVT: <?= htmlspecialchars($item['unit']) ?></small>
            </td>
            <td><?= htmlspecialchars($item['category_name']) ?></td>
            <td class="text-end text-danger fw-bold"><?= number_format($item['price']) ?> đ</td>
            <td class="text-end text-secondary fw-semibold"><?= number_format($item['food_cost']) ?> đ</td>
            <td>
                <?php if ($item['bom_summary']): ?>
                    <small class="text-success fw-semibold"><?= htmlspecialchars($item['bom_summary']) ?></small>
                <?php else: ?>
                    <small class="text-muted fst-italic">Chưa thiết lập BOM</small>
                <?php endif; ?>
            </td>
            <td>
                <?php
                    $badge = 'bg-success';
                    if ($item['status'] === 'OutOfStock') $badge = 'bg-warning text-dark';
                    if ($item['status'] === 'Discontinued') $badge = 'bg-secondary';
                ?>
                <span class="badge <?= $badge ?>"><?= $item['status'] ?></span>
            </td>
            <td class="text-center">
                <a href="index.php?c=menu&a=bom&id=<?= $item['id'] ?>" class="btn btn-sm btn-outline-primary" title="Thiết lập Định lượng BOM">⚖️ BOM</a>
                <button type="button" class="btn btn-sm btn-outline-dark" 
                    onclick='openEditModal(<?= json_encode($item, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>✏️ Sửa</button>
                <a href="index.php?c=menu&a=delete&id=<?= $item['id'] ?>" 
                    class="btn btn-sm btn-outline-danger" 
                    onclick="return confirm('Bạn có chắc chắn muốn xóa món [<?= htmlspecialchars($item['name']) ?>] không?')">🗑️ Xóa</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>

<!-- Modal Sửa Món Ăn -->
<div class="modal fade" id="editItemModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="index.php?c=menu&a=save" class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title">✏️ Cập Nhật Món Ăn 5 Sao</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id" id="edit_id">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Mã món ăn</label>
                    <input type="text" name="item_code" id="edit_item_code" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Tên món ăn</label>
                    <input type="text" name="name" id="edit_name" class="form-control" required>
                </div>
                <div class="row mb-3">
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Nhóm thực đơn</label>
                        <select name="category_id" id="edit_category_id" class="form-select">
                            <?php foreach($categories as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Đơn vị tính</label>
                        <input type="text" name="unit" id="edit_unit" class="form-control" required>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Giá bán (VNĐ)</label>
                        <input type="number" name="price" id="edit_price" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Trạng thái phục vụ</label>
                        <select name="status" id="edit_status" class="form-select">
                            <option value="Available">Available (Đang phục vụ)</option>
                            <option value="OutOfStock">OutOfStock (Tạm hết món)</option>
                            <option value="Discontinued">Discontinued (Ngừng KD)</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-warning fw-bold">💾 Lưu Thay Đổi</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(item) {
    document.getElementById('edit_id').value = item.id;
    document.getElementById('edit_item_code').value = item.item_code;
    document.getElementById('edit_name').value = item.name;
    document.getElementById('edit_category_id').value = item.category_id;
    document.getElementById('edit_unit').value = item.unit;
    document.getElementById('edit_price').value = parseFloat(item.price);
    document.getElementById('edit_status').value = item.status;
    new bootstrap.Modal(document.getElementById('editItemModal')).show();
}
</script>
