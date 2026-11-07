<?php
$topNames = array_map(fn($d) => $d['name'], $topDishes);
$topRevs  = array_map(fn($d) => (float)$d['total_rev'], $topDishes);
$totalTopRev = array_sum($topRevs) ?: 1;
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-0">📊 Trung Tâm Điều Hành & Phân Tích RoyalDine 5S (Dashboard)</h3>
        <small class="text-muted">Cổng thông tin quản trị thời gian thực chuẩn MISA CUKCUK — Cập nhật lúc <?= date('H:i d/m/Y') ?></small>
    </div>
    <div class="d-flex gap-2">
        <a href="index.php?c=menu&a=index" class="btn btn-sm btn-outline-dark fw-semibold">🍽️ Quản lý Thực đơn & BOM</a>
        <a href="index.php?c=menu&a=users" class="btn btn-sm btn-warning fw-bold">👥 Quản trị Nhân sự & Ca</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card p-3 shadow-sm border-0 border-start border-warning border-4 bg-white h-100">
            <div class="d-flex justify-content-between align-items-center">
                <span class="text-muted small fw-semibold">DOANH THU HÔM NAY</span>
                <span class="badge bg-warning text-dark">Thực tế DB</span>
            </div>
            <h3 class="fw-bold text-dark my-2"><?= number_format($todayRevenue) ?> đ</h3>
            <small class="text-muted">Lũy kế 7 ngày: <b class="text-success"><?= number_format($weekRevenue) ?> đ</b></small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3 shadow-sm border-0 border-start border-success border-4 bg-white h-100">
            <div class="d-flex justify-content-between align-items-center">
                <span class="text-muted small fw-semibold">TỶ LỆ LẤP ĐẦY BÀN (VIP & SẢNH)</span>
                <span class="badge bg-success"><?= $totalTables ?> bàn</span>
            </div>
            <h3 class="fw-bold text-success my-2"><?= $occupancyRate ?>%</h3>
            <small class="text-muted">
                Đang phục vụ: <b><?= $tableStats['Occupied'] ?></b> | Đặt trước: <b><?= $tableStats['Reserved'] ?></b>
            </small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3 shadow-sm border-0 border-start border-primary border-4 bg-white h-100">
            <div class="d-flex justify-content-between align-items-center">
                <span class="text-muted small fw-semibold">THỰC ĐƠN 5 SAO & ĐỊNH LƯỢNG</span>
                <span class="badge bg-primary">BOM Auto</span>
            </div>
            <h3 class="fw-bold text-primary my-2"><?= $totalItems ?> món</h3>
            <small class="text-muted">Đã gắn công thức BOM: <b><?= $bomCount ?>/<?= $totalItems ?> món</b></small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3 shadow-sm border-0 border-start border-danger border-4 bg-white h-100">
            <div class="d-flex justify-content-between align-items-center">
                <span class="text-muted small fw-semibold">NHÂN SỰ & PHÂN QUYỀN RBAC</span>
                <span class="badge bg-dark">RBAC Active</span>
            </div>
            <h3 class="fw-bold text-danger my-2"><?= $totalUsers ?> nhân sự</h3>
            <small class="text-muted">Đang kích hoạt bảo mật & Audit Log</small>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-8">
        <div class="card shadow-sm border-0 p-3 bg-white h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h6 class="fw-bold mb-0" id="revChartTitle">📈 Biểu Đồ Doanh Thu 7 Ngày Gần Nhất (VNĐ)</h6>
                    <small class="text-muted">Tổng hợp trực tiếp từ các hóa đơn đã thanh toán (Paid) trong Database</small>
                </div>
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-dark text-warning fw-bold" id="btnRevDaily" onclick="switchRevenueMode('daily')">
                        Theo Ngày (7 ngày)
                    </button>
                    <button type="button" class="btn btn-outline-dark" id="btnRevMonthly" onclick="switchRevenueMode('monthly')">
                        Theo Tháng (6 tháng)
                    </button>
                </div>
            </div>
            <div style="height: 290px;">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm border-0 p-3 bg-white h-100">
            <h6 class="fw-bold mb-1">🪑 Tỷ Lệ Lấp Đầy & Trạng Thái Bàn</h6>
            <small class="text-muted mb-2">Đồng bộ thời gian thực từ Sơ đồ bàn & POS</small>
            <div style="height: 210px;" class="d-flex justify-content-center align-items-center">
                <canvas id="occupancyChart"></canvas>
            </div>
            <div class="row text-center mt-3 pt-2 border-top small">
                <div class="col-3">
                    <span class="badge bg-danger d-block mb-1">Có khách</span>
                    <b><?= $tableStats['Occupied'] ?> bàn</b>
                </div>
                <div class="col-3">
                    <span class="badge bg-warning text-dark d-block mb-1">Đặt VIP</span>
                    <b><?= $tableStats['Reserved'] ?> bàn</b>
                </div>
                <div class="col-3">
                    <span class="badge bg-success d-block mb-1">Bàn trống</span>
                    <b><?= $tableStats['Empty'] ?> bàn</b>
                </div>
                <div class="col-3">
                    <span class="badge bg-secondary d-block mb-1">Đang dọn</span>
                    <b><?= $tableStats['Cleaning'] ?> bàn</b>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-7">
        <div class="card shadow-sm border-0 p-3 bg-white h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div>
                    <h6 class="fw-bold mb-0">🏆 Top 5 Món Ăn 5 Sao Mang Lại Doanh Thu Cao Nhất</h6>
                    <small class="text-muted">Phân tích hiệu quả thực đơn (Menu Engineering) theo doanh số thực tế</small>
                </div>
                <span class="badge bg-dark text-warning">Fine Dining Best-Sellers</span>
            </div>
            <div style="height: 200px;" class="mb-3">
                <canvas id="topDishesChart"></canvas>
            </div>
            <table class="table table-sm table-bordered align-middle mb-0 small">
                <thead class="table-dark">
                    <tr>
                        <th>Hạng</th>
                        <th>Mã</th>
                        <th>Tên món ăn 5 sao</th>
                        <th class="text-center">SL Bán</th>
                        <th class="text-end">Doanh thu</th>
                        <th class="text-center">Tỷ trọng</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($topDishes as $idx => $dish): 
                        $share = $totalTopRev > 1 ? round(($dish['total_rev'] / $totalTopRev) * 100, 1) : 0;
                    ?>
                    <tr>
                        <td class="text-center fw-bold">
                            <?= $idx === 0 ? '🥇' : ($idx === 1 ? '🥈' : ($idx === 2 ? '🥉' : '#' . ($idx + 1))) ?>
                        </td>
                        <td><code><?= htmlspecialchars($dish['item_code']) ?></code></td>
                        <td class="fw-bold"><?= htmlspecialchars($dish['name']) ?></td>
                        <td class="text-center"><span class="badge bg-light text-dark border"><?= (int)$dish['total_qty'] ?> phần</span></td>
                        <td class="text-end fw-bold text-danger"><?= number_format($dish['total_rev']) ?> đ</td>
                        <td class="text-center">
                            <div class="progress" style="height: 16px;">
                                <div class="progress-bar bg-warning text-dark fw-bold" style="width: <?= $share ?>%;"><?= $share ?>%</div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="col-md-5">
        <div class="card shadow-sm border-0 p-3 bg-white h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h6 class="fw-bold mb-0">🛡️ Nhật Ký Kiểm Soát Hệ Thống Gần Nhất</h6>
                    <small class="text-muted">Theo dõi thao tác nhạy cảm (Audit Trail)</small>
                </div>
                <a href="index.php?c=menu&a=users&tab=logs" class="btn btn-sm btn-outline-dark py-0">Xem tất cả</a>
            </div>
            <ul class="list-group list-group-flush small mb-3">
                <?php if (empty($recentLogs)): ?>
                    <li class="list-group-item text-muted">Chưa có bản ghi nhật ký nào.</li>
                <?php else: foreach($recentLogs as $log): ?>
                    <li class="list-group-item px-0 py-2">
                        <div class="d-flex justify-content-between">
                            <span>
                                <span class="badge <?= $log['severity'] === 'CRITICAL' ? 'bg-danger' : ($log['severity'] === 'WARNING' ? 'bg-warning text-dark' : 'bg-info text-dark') ?>">
                                    <?= htmlspecialchars($log['severity']) ?>
                                </span>
                                <b class="ms-1"><?= htmlspecialchars($log['full_name']) ?></b> (<code><?= htmlspecialchars($log['role_code']) ?></code>)
                            </span>
                            <small class="text-muted"><?= htmlspecialchars($log['created_at']) ?></small>
                        </div>
                        <div class="text-muted mt-1"><?= htmlspecialchars($log['description']) ?></div>
                    </li>
                <?php endforeach; endif; ?>
            </ul>

            <h6 class="fw-bold mb-2 pt-2 border-top">📦 Theo Dõi Tồn Kho Nguyên Vật Liệu (BOM)</h6>
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0 small">
                    <thead class="table-secondary">
                        <tr><th>Nguyên vật liệu</th><th class="text-end">Tồn kho</th><th class="text-center">Trạng thái</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($lowStockItems as $ing):
                            $isLow = (float)$ing['stock_quantity'] <= (float)$ing['min_stock_level'];
                        ?>
                        <tr>
                            <td class="fw-semibold"><?= htmlspecialchars($ing['name']) ?></td>
                            <td class="text-end fw-bold <?= $isLow ? 'text-danger' : 'text-dark' ?>">
                                <?= number_format($ing['stock_quantity'], 2) ?> <?= htmlspecialchars($ing['unit']) ?>
                            </td>
                            <td class="text-center">
                                <?php if ($isLow): ?>
                                    <span class="badge bg-danger">Sắp hết</span>
                                <?php else: ?>
                                    <span class="badge bg-success">An toàn</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const dailyLabels    = <?= json_encode($dailyLabels) ?>;
const dailyRevenue   = <?= json_encode($dailyRevenue) ?>;
const monthlyLabels  = <?= json_encode($monthlyLabels) ?>;
const monthlyRevenue = <?= json_encode($monthlyRevenue) ?>;

const ctxRev = document.getElementById('revenueChart').getContext('2d');
let revenueChart = new Chart(ctxRev, {
    type: 'bar',
    data: {
        labels: dailyLabels,
        datasets: [{
            label: 'Doanh thu (VNĐ)',
            data: dailyRevenue,
            backgroundColor: 'rgba(212, 175, 55, 0.75)',
            borderColor: '#b8860b',
            borderWidth: 2,
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(ctx) {
                        return ' Doanh thu: ' + Number(ctx.raw).toLocaleString('vi-VN') + ' đ';
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return Number(value).toLocaleString('vi-VN') + ' đ';
                    }
                }
            }
        }
    }
});

function switchRevenueMode(mode) {
    const btnDaily = document.getElementById('btnRevDaily');
    const btnMonthly = document.getElementById('btnRevMonthly');
    const title = document.getElementById('revChartTitle');

    if (mode === 'monthly') {
        revenueChart.config.type = 'line';
        revenueChart.data.labels = monthlyLabels;
        revenueChart.data.datasets[0].data = monthlyRevenue;
        revenueChart.data.datasets[0].fill = true;
        revenueChart.data.datasets[0].backgroundColor = 'rgba(212, 175, 55, 0.25)';
        title.textContent = '📈 Biểu Đồ Doanh Thu 6 Tháng Gần Nhất (VNĐ)';
        btnMonthly.className = 'btn btn-dark text-warning fw-bold';
        btnDaily.className = 'btn btn-outline-dark';
    } else {
        revenueChart.config.type = 'bar';
        revenueChart.data.labels = dailyLabels;
        revenueChart.data.datasets[0].data = dailyRevenue;
        revenueChart.data.datasets[0].fill = false;
        revenueChart.data.datasets[0].backgroundColor = 'rgba(212, 175, 55, 0.75)';
        title.textContent = '📈 Biểu Đồ Doanh Thu 7 Ngày Gần Nhất (VNĐ)';
        btnDaily.className = 'btn btn-dark text-warning fw-bold';
        btnMonthly.className = 'btn btn-outline-dark';
    }
    revenueChart.update();
}

new Chart(document.getElementById('occupancyChart').getContext('2d'), {
    type: 'doughnut',
    data: {
        labels: ['Có khách', 'Đặt VIP', 'Bàn trống', 'Đang dọn'],
        datasets: [{
            data: [
                <?= (int)$tableStats['Occupied'] ?>,
                <?= (int)$tableStats['Reserved'] ?>,
                <?= (int)$tableStats['Empty'] ?>,
                <?= (int)$tableStats['Cleaning'] ?>
            ],
            backgroundColor: ['#dc3545', '#ffc107', '#198754', '#6c757d'],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom' } }
    }
});

new Chart(document.getElementById('topDishesChart').getContext('2d'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($topNames) ?>,
        datasets: [{
            label: 'Doanh thu (VNĐ)',
            data: <?= json_encode($topRevs) ?>,
            backgroundColor: 'rgba(26, 29, 32, 0.85)',
            borderColor: '#d4af37',
            borderWidth: 1,
            borderRadius: 4
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } }
    }
});
</script>