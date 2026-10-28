<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="fw-bold">🍽️ Quản Lý Thực Đơn 5 Sao & Định Lượng (BOM)</h3>
</div>
<div class="card p-3 mb-4 shadow-sm">
    <h6 class="fw-bold text-primary">➕ Thêm món ăn mới vào Thực đơn</h6>
    <form method="POST" class="row g-2">
        <div class="col-md-2"><input type="text" name="item_code" class="form-control" placeholder="Mã món (VD: RD-05)" required></div>
        <div class="col-md-3"><input type="text" name="name" class="form-control" placeholder="Tên món ăn 5 sao" required></div>
        <div class="col-md-3">
            <select name="category_id" class="form-select">
                <?php foreach($categories as $c): ?><option value="<?= $c['id'] ?>"><?= $c['name'] ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-1"><input type="text" name="unit" class="form-control" value="Phần"></div>
        <div class="col-md-2"><input type="number" name="price" class="form-control" placeholder="Giá bán (VNĐ)" required></div>
        <div class="col-md-1"><button class="btn btn-warning w-100 fw-bold">Thêm</button></div>
    </form>
</div>
<table class="table table-bordered table-hover bg-white shadow-sm">
    <thead class="table-dark">
        <tr><th>Mã</th><th>Tên món ăn</th><th>Nhóm</th><th>Giá bán</th><th>Định lượng nguyên liệu (BOM)</th><th>Trạng thái</th></tr>
    </thead>
    <tbody>
        <?php foreach($items as $item): ?>
        <tr>
            <td><code><?= $item['item_code'] ?></code></td>
            <td class="fw-bold"><?= htmlspecialchars($item['name']) ?></td>
            <td><?= $item['category_name'] ?></td>
            <td class="text-danger fw-bold"><?= number_format($item['price']) ?> đ</td>
            <td><small class="text-muted"><?= $item['bom_list'] ?: 'Chưa thiết lập BOM' ?></small></td>
            <td><span class="badge bg-success"><?= $item['status'] ?></span></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
