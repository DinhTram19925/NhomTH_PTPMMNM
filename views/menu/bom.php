<?php
$totalCost = 0;
foreach ($bomList as $b) {
    $totalCost += $b['line_cost'];
}
$margin = $item['price'] > 0 ? round((($item['price'] - $totalCost) / $item['price']) * 100, 1) : 0;
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="fw-bold mb-1">⚖️ Thiết Lập Định Lượng Nguyên Vật Liệu (BOM)</h3>
        <p class="text-muted mb-0">Món ăn: <b class="text-dark"><?= htmlspecialchars($item['name']) ?></b> (<code><?= $item['item_code'] ?></code>)</p>
    </div>
    <a href="index.php?c=menu&a=index" class="btn btn-outline-dark">⬅️ Quay lại Thực đơn</a>
</div>

<?php if (!empty($_SESSION['flash_msg'])): ?>
    <div class="alert alert-success py-2"><?= $_SESSION['flash_msg']; unset($_SESSION['flash_msg']); ?></div>
<?php endif; ?>

<!-- Thẻ phân tích Food Cost chuẩn Nhà hàng 5 sao -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card p-3 shadow-sm border-start border-danger border-4">
            <small class="text-muted">Giá bán niêm yết (1 <?= htmlspecialchars($item['unit']) ?>)</small>
            <h3 class="fw-bold text-danger mb-0"><?= number_format($item['price']) ?> đ</h3>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3 shadow-sm border-start border-primary border-4">
            <small class="text-muted">Tổng giá vốn nguyên liệu (Food Cost)</small>
            <h3 class="fw-bold text-primary mb-0"><?= number_format($totalCost) ?> đ</h3>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3 shadow-sm border-start border-success border-4">
            <small class="text-muted">Tỷ suất lợi nhuận gộp (Gross Margin)</small>
            <h3 class="fw-bold text-success mb-0"><?= $margin ?>%</h3>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Cột trái: Form thêm/cập nhật nguyên liệu vào BOM -->
    <div class="col-md-4">
        <div class="card p-3 shadow-sm border-0">
            <h6 class="fw-bold text-primary mb-3">➕ Thêm / Cập Nhật Nguyên Liệu Định Lượng</h6>
            <form method="POST" action="index.php?c=menu&a=bom&id=<?= $item['id'] ?>">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Chọn nguyên vật liệu trong kho</label>
                    <select name="ingredient_id" class="form-select" required>
                        <?php foreach($ingredients as $ing): ?>
                            <option value="<?= $ing['id'] ?>">
                                <?= htmlspecialchars($ing['name']) ?> (ĐVT: <?= $ing['unit'] ?> - Giá vốn: <?= number_format($ing['cost_price']) ?>đ/<?= $ing['unit'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Định lượng tiêu hao cho 1 <?= htmlspecialchars($item['unit']) ?></label>
                    <input type="number" step="0.01" min="0.01" name="quantity_required" class="form-control" placeholder="VD: 200 (g hoặc ml)" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Tỷ lệ hao hụt sơ chế cho phép (%)</label>
                    <input type="number" step="0.1" min="0" max="100" name="wastage_percent" class="form-control" value="0">
                    <small class="text-muted">VD: Bò Wagyu cắt lọc hao hụt 5%</small>
                </div>
                <button type="submit" class="btn btn-warning w-100 fw-bold">⚖️ Lưu Vào Công Thức BOM</button>
            </form>
        </div>
    </div>

    <!-- Cột phải: Bảng chi tiết công thức BOM -->
    <div class="col-md-8">
        <div class="card p-3 shadow-sm border-0">
            <h6 class="fw-bold mb-3">📋 Bảng Định Lượng Chi Tiết (Tự động trừ kho khi bán)</h6>
            <table class="table table-bordered align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Mã NVL</th>
                        <th>Tên nguyên vật liệu</th>
                        <th class="text-end">Định lượng</th>
                        <th class="text-end">Hao hụt (%)</th>
                        <th class="text-end">Đơn giá vốn</th>
                        <th class="text-end">Thành tiền vốn</th>
                        <th class="text-center">Xóa</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($bomList)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">Chưa có nguyên vật liệu nào trong công thức. Hãy thêm ở khung bên trái!</td></tr>
                    <?php else: ?>
                        <?php foreach($bomList as $b): ?>
                        <tr>
                            <td><code><?= $b['ingredient_code'] ?></code></td>
                            <td class="fw-semibold"><?= htmlspecialchars($b['ingredient_name']) ?></td>
                            <td class="text-end fw-bold"><?= number_format($b['quantity_required'], 2) ?> <?= $b['unit'] ?></td>
                            <td class="text-end"><?= $b['wastage_percent'] ?>%</td>
                            <td class="text-end"><?= number_format($b['cost_price']) ?> đ/<?= $b['unit'] ?></td>
                            <td class="text-end text-primary fw-bold"><?= number_format($b['line_cost']) ?> đ</td>
                            <td class="text-center">
                                <a href="index.php?c=menu&a=deleteBom&bom_id=<?= $b['id'] ?>&item_id=<?= $item['id'] ?>" 
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Gỡ nguyên liệu này khỏi công thức?')">✖</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
