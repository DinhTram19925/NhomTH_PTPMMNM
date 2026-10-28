<h3 class="fw-bold mb-4">📊 Tổng Quan Hệ Thống RoyalDine 5S</h3>
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card p-3 shadow-sm border-start border-warning border-4">
            <h6 class="text-muted">Món ăn đang phục vụ</h6>
            <h2 class="fw-bold mb-0"><?= $totalItems ?> món</h2>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3 shadow-sm border-start border-success border-4">
            <h6 class="text-muted">Tổng số bàn phục vụ</h6>
            <h2 class="fw-bold mb-0"><?= $totalTables ?> bàn</h2>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3 shadow-sm border-start border-primary border-4">
            <h6 class="text-muted">Nhân sự đang hoạt động</h6>
            <h2 class="fw-bold mb-0"><?= $totalUsers ?> tài khoản</h2>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3 shadow-sm border-start border-danger border-4">
            <h6 class="text-muted">NVL chạm mức tối thiểu</h6>
            <h2 class="fw-bold text-danger mb-0"><?= $lowStock ?> NVL</h2>
        </div>
    </div>
</div>

<div class="card p-3 shadow-sm border-0">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0">📈 Bảng Phân Tích Giá Vốn Định Lượng (Food Cost) & Lợi Nhuận Gộp Thực Đơn 5 Sao</h6>
        <a href="index.php?c=menu&a=index" class="btn btn-sm btn-warning fw-bold">Quản lý Thực đơn & BOM ➡️</a>
    </div>
    <table class="table table-bordered align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th>Mã món</th>
                <th>Tên món ăn</th>
                <th class="text-end">Giá bán</th>
                <th class="text-end">Giá vốn BOM</th>
                <th class="text-end">Lãi gộp / Phần</th>
                <th>Công thức định lượng</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($items as $it): 
                $profit = $it['price'] - $it['food_cost'];
            ?>
            <tr>
                <td><code><?= $it['item_code'] ?></code></td>
                <td class="fw-bold"><?= htmlspecialchars($it['name']) ?></td>
                <td class="text-end text-danger fw-bold"><?= number_format($it['price']) ?> đ</td>
                <td class="text-end"><?= number_format($it['food_cost']) ?> đ</td>
                <td class="text-end text-success fw-bold"><?= number_format($profit) ?> đ</td>
                <td><small><?= htmlspecialchars($it['bom_summary'] ?: 'Chưa thiết lập BOM') ?></small></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
