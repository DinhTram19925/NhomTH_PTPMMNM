<?php
function rdCleanText($text) {
    $clean = str_replace(['(', ')'], ['', ''], (string)$text);
    return htmlspecialchars(trim($clean), ENT_QUOTES, 'UTF-8');
}

$selectedDate = $selectedDate ?? date('Y-m-d');
$yesterdayRevenue = (float)($yesterdayRevenue ?? 0);
$todayOrdersCount = (int)($todayOrdersCount ?? 0);
$todayPaidOrdersCount = (int)($todayPaidOrdersCount ?? 0);
$openOrdersCount = (int)($openOrdersCount ?? 0);
$todayBookingsCount = (int)($todayBookingsCount ?? 0);
$upcomingBookingsCount = (int)($upcomingBookingsCount ?? 0);
$avgOrderValue = (float)($avgOrderValue ?? 0);
$pendingKdsCount = (int)($pendingKdsCount ?? 0);
$paymentBreakdown = $paymentBreakdown ?? [];
$dailyOrders = $dailyOrders ?? array_fill(0, count($dailyLabels), 0);

$topNames = array_map(fn($d) => str_replace(['(', ')'], '', $d['name']), $topDishes);
$topRevs  = array_map(fn($d) => (float)$d['total_rev'], $topDishes);
$totalTopRev = array_sum($topRevs);
$hasRevenueData = (array_sum($dailyRevenue) > 0 || array_sum($monthlyRevenue) > 0);

$paymentLabels = [];
$paymentValues = [];
$methodNameMap = [
    'Cash'       => 'Tiền mặt',
    'VietQR'     => 'Chuyển khoản VietQR',
    'CreditCard' => 'Thẻ ngân hàng'
];
foreach ($paymentBreakdown as $pm) {
    $paymentLabels[] = $methodNameMap[$pm['payment_method']] ?? $pm['payment_method'];
    $paymentValues[] = (float)$pm['total_amount'];
}
$totalPaymentAmount = array_sum($paymentValues);
?>

<!-- 1. PAGE HEADER VÀ BỘ LỌC THỜI GIAN -->
<div class="rd-page-header">
    <div>
        <h2 class="rd-page-title">Tổng quan hoạt động</h2>
        <p class="rd-page-desc">Theo dõi tình hình kinh doanh và vận hành nhà hàng</p>
    </div>

    <form method="GET" action="index.php" class="d-flex flex-wrap align-items-center gap-2">
        <input type="hidden" name="c" value="menu">
        <input type="hidden" name="a" value="dashboard">
        <label for="dashboardDateFilter" class="text-secondary fw-semibold small mb-0">Ngày báo cáo</label>
        <input type="date" id="dashboardDateFilter" name="date" value="<?= rdCleanText($selectedDate) ?>" class="form-control" style="width: 155px;">
        <button type="submit" class="btn btn-primary-navy">Lọc dữ liệu</button>
        <?php if ($selectedDate !== date('Y-m-d')): ?>
            <a href="index.php?c=menu&a=dashboard" class="btn btn-outline-slate">Hôm nay</a>
        <?php endif; ?>
        <a href="index.php?c=pos&a=index" class="btn btn-accent-gold">Mở màn hình POS</a>
    </form>
</div>

<!-- 2. KHU VỰC CHỈ SỐ KINH DOANH KPI -->
<section class="rd-kpi-grid">
    <article class="rd-kpi-card">
        <div>
            <div class="rd-kpi-label">Doanh thu ngày <?= date('d/m/Y', strtotime($selectedDate)) ?></div>
            <div class="rd-kpi-value-row">
                <span class="rd-kpi-value"><?= number_format($todayRevenue, 0, ',', '.') ?></span>
                <span class="rd-kpi-unit">VNĐ</span>
            </div>
        </div>
        <div class="rd-kpi-meta">
            <?php if ($yesterdayRevenue > 0):
                $diffRev = $todayRevenue - $yesterdayRevenue;
                $diffPct = round(($diffRev / $yesterdayRevenue) * 100, 1);
            ?>
                So với hôm trước: <strong class="<?= $diffRev >= 0 ? 'text-success' : 'text-danger' ?>"><?= ($diffRev >= 0 ? 'Tăng ' : 'Giảm ') . abs($diffPct) ?>%</strong>
            <?php else: ?>
                Lũy kế 7 ngày: <strong><?= number_format($weekRevenue, 0, ',', '.') ?> VNĐ</strong>
            <?php endif; ?>
        </div>
    </article>

    <article class="rd-kpi-card">
        <div>
            <div class="rd-kpi-label">Tổng đơn hàng</div>
            <div class="rd-kpi-value-row">
                <span class="rd-kpi-value"><?= number_format($todayOrdersCount) ?></span>
                <span class="rd-kpi-unit">đơn hàng</span>
            </div>
        </div>
        <div class="rd-kpi-meta">
            Đã thanh toán: <strong><?= $todayPaidOrdersCount ?> đơn</strong> · Đang mở: <strong><?= $openOrdersCount ?> đơn</strong>
        </div>
    </article>

    <article class="rd-kpi-card">
        <div>
            <div class="rd-kpi-label">Bàn đang phục vụ</div>
            <div class="rd-kpi-value-row">
                <span class="rd-kpi-value"><?= (int)$tableStats['Occupied'] ?> / <?= (int)$totalTables ?></span>
                <span class="rd-kpi-unit">bàn</span>
            </div>
        </div>
        <div class="rd-kpi-meta">
            Công suất lấp đầy bàn: <strong><?= $occupancyRate ?>%</strong>
        </div>
    </article>

    <article class="rd-kpi-card">
        <div>
            <div class="rd-kpi-label">Số lịch đặt bàn</div>
            <div class="rd-kpi-value-row">
                <span class="rd-kpi-value"><?= number_format($todayBookingsCount) ?></span>
                <span class="rd-kpi-unit">lượt đặt</span>
            </div>
        </div>
        <div class="rd-kpi-meta">
            Chờ đón khách trong ngày: <strong><?= $upcomingBookingsCount ?> lượt</strong>
        </div>
    </article>

    <article class="rd-kpi-card">
        <div>
            <div class="rd-kpi-label">Giá trị đơn trung bình</div>
            <div class="rd-kpi-value-row">
                <span class="rd-kpi-value"><?= number_format($avgOrderValue, 0, ',', '.') ?></span>
                <span class="rd-kpi-unit">VNĐ</span>
            </div>
        </div>
        <div class="rd-kpi-meta">
            Tính trên các hóa đơn đã hoàn tất thanh toán
        </div>
    </article>
</section>

<!-- 3. KHU VỰC BIỂU ĐỒ KINH DOANH -->
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <section class="rd-panel h-100">
            <div class="rd-panel-header">
                <div>
                    <h3 class="rd-panel-title" id="revChartTitle">Biểu đồ doanh thu 7 ngày gần nhất</h3>
                    <p class="rd-panel-subtitle">Đơn vị tính bằng VNĐ, tổng hợp từ các hóa đơn đã thanh toán hợp lệ</p>
                </div>
                <div class="btn-group">
                    <button type="button" class="btn btn-primary-navy btn-sm" id="btnRevDaily" onclick="switchRevenueMode('daily')">
                        Theo 7 ngày
                    </button>
                    <button type="button" class="btn btn-outline-slate btn-sm" id="btnRevMonthly" onclick="switchRevenueMode('monthly')">
                        Theo 6 tháng
                    </button>
                </div>
            </div>
            <div class="rd-panel-body">
                <div style="height: 280px;">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
        </section>
    </div>

    <div class="col-lg-4">
        <section class="rd-panel h-100">
            <div class="rd-panel-header">
                <div>
                    <h3 class="rd-panel-title">Tỷ trọng phương thức thanh toán</h3>
                    <p class="rd-panel-subtitle">Đối chiếu doanh thu thực thu theo hình thức thanh toán</p>
                </div>
            </div>
            <div class="rd-panel-body d-flex flex-column justify-content-between">
                <?php if ($totalPaymentAmount > 0): ?>
                    <div style="height: 185px;">
                        <canvas id="paymentMethodChart"></canvas>
                    </div>
                    <div class="table-responsive mt-3">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Phương thức</th>
                                    <th class="text-center">Số giao dịch</th>
                                    <th class="text-end">Tổng thu bằng VNĐ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($paymentBreakdown as $pm):
                                    $pmName = $methodNameMap[$pm['payment_method']] ?? $pm['payment_method'];
                                ?>
                                <tr>
                                    <td class="fw-medium"><?= rdCleanText($pmName) ?></td>
                                    <td class="text-center"><?= (int)$pm['tx_count'] ?></td>
                                    <td class="text-end fw-semibold"><?= number_format((float)$pm['total_amount'], 0, ',', '.') ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="py-5 text-center">
                        <div class="fw-semibold text-secondary mb-1">Chưa phát sinh giao dịch thanh toán</div>
                        <p class="text-muted small mb-3">Biểu đồ tỷ trọng thanh toán sẽ hiển thị ngay khi có hóa đơn hoàn tất thu tiền trên POS.</p>
                        <a href="index.php?c=pos&a=index" class="btn btn-outline-slate btn-sm">Chuyển tới màn hình POS</a>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>

<!-- 4. KHU VỰC TÌNH HÌNH BÀN & CẢNH BÁO VẬN HÀNH -->
<div class="row g-3 mb-4">
    <div class="col-lg-5">
        <section class="rd-panel h-100">
            <div class="rd-panel-header">
                <div>
                    <h3 class="rd-panel-title">Tình hình bàn</h3>
                    <p class="rd-panel-subtitle">Tổng số <?= (int)$totalTables ?> bàn trên toàn bộ khu vực phục vụ</p>
                </div>
                <a href="index.php?c=booking&a=index" class="btn btn-outline-slate btn-sm">Xem sơ đồ bàn</a>
            </div>
            <div class="rd-panel-body">
                <?php
                $tableRows = [
                    ['label' => 'Bàn trống',        'count' => (int)$tableStats['Empty'],    'tag' => 'rd-status-success', 'bar' => '#10b981'],
                    ['label' => 'Bàn đang phục vụ', 'count' => (int)$tableStats['Occupied'], 'tag' => 'rd-status-danger',  'bar' => '#ef4444'],
                    ['label' => 'Bàn đã đặt trước', 'count' => (int)$tableStats['Reserved'], 'tag' => 'rd-status-warning', 'bar' => '#f59e0b'],
                    ['label' => 'Bàn đang dọn',     'count' => (int)$tableStats['Cleaning'], 'tag' => 'rd-status-neutral', 'bar' => '#64748b']
                ];
                foreach ($tableRows as $tr):
                    $pct = $totalTables > 0 ? round(($tr['count'] / $totalTables) * 100, 1) : 0;
                ?>
                <div class="mb-3 pb-2 border-bottom last-border-0">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="rd-status-tag <?= $tr['tag'] ?>"><?= $tr['label'] ?></span>
                        <div class="small">
                            <strong class="text-dark"><?= $tr['count'] ?> bàn</strong>
                            <span class="text-muted ms-2">Chiếm <?= $pct ?>%</span>
                        </div>
                    </div>
                    <div class="progress" style="height: 6px; background-color: #f1f5f9;">
                        <div class="progress-bar" style="width: <?= $pct ?>%; background-color: <?= $tr['bar'] ?>;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>

    <div class="col-lg-7">
        <section class="rd-panel h-100">
            <div class="rd-panel-header">
                <div>
                    <h3 class="rd-panel-title">Cảnh báo vận hành</h3>
                    <p class="rd-panel-subtitle">Tổng hợp trạng thái ca thu ngân, bếp KDS, lịch đặt bàn và nguyên vật liệu tồn kho</p>
                </div>
                <a href="index.php?c=stock&a=index&tab=ingredients" class="btn btn-outline-slate btn-sm">Quản lý tồn kho</a>
            </div>
            <div class="rd-panel-body">
                <div class="row g-2 mb-3">
                    <div class="col-md-4">
                        <div class="p-2 border rounded bg-light">
                            <div class="text-muted small">Trạng thái ca thu ngân</div>
                            <?php if (!empty($openShift)): ?>
                                <div class="fw-bold text-success mt-1">Đang mở ca số <?= (int)$openShift['id'] ?></div>
                                <div class="small text-secondary">Thu ngân: <?= rdCleanText($openShift['cashier_name'] ?? 'Hệ thống') ?></div>
                            <?php else: ?>
                                <div class="fw-bold text-danger mt-1">Chưa mở ca làm việc</div>
                                <div class="small text-secondary">Cần mở ca trước khi thu tiền mặt</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-2 border rounded bg-light">
                            <div class="text-muted small">Món chờ chế biến tại bếp</div>
                            <div class="fw-bold <?= $pendingKdsCount > 0 ? 'text-warning' : 'text-dark' ?> mt-1"><?= $pendingKdsCount ?> món đang xử lý</div>
                            <div class="small text-secondary">Thuộc <?= $openOrdersCount ?> đơn hàng đang mở</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-2 border rounded bg-light">
                            <div class="text-muted small">Đặt bàn chờ đón khách</div>
                            <div class="fw-bold <?= $upcomingBookingsCount > 0 ? 'text-primary' : 'text-dark' ?> mt-1"><?= $upcomingBookingsCount ?> lịch hẹn</div>
                            <div class="small text-secondary">Trong ngày <?= date('d/m/Y', strtotime($selectedDate)) ?></div>
                        </div>
                    </div>
                </div>

                <div class="fw-semibold text-secondary small mb-2">Nguyên vật liệu dưới hoặc chạm mức tồn tối thiểu</div>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Mã nguyên liệu</th>
                                <th>Tên nguyên vật liệu</th>
                                <th class="text-end">Tồn hiện tại</th>
                                <th class="text-end">Mức tối thiểu</th>
                                <th class="text-end">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($lowStockItems)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">Tất cả nguyên vật liệu trong kho đều đảm bảo trên định mức tối thiểu.</td>
                                </tr>
                            <?php else: foreach ($lowStockItems as $ing):
                                $isOut = (float)$ing['stock_quantity'] <= 0;
                            ?>
                                <tr>
                                    <td class="fw-semibold"><?= rdCleanText($ing['ingredient_code']) ?></td>
                                    <td><?= rdCleanText($ing['name']) ?></td>
                                    <td class="text-end fw-bold text-danger"><?= number_format((float)$ing['stock_quantity'], 2, ',', '.') ?> <?= rdCleanText($ing['unit']) ?></td>
                                    <td class="text-end text-secondary"><?= number_format((float)$ing['min_stock_level'], 2, ',', '.') ?> <?= rdCleanText($ing['unit']) ?></td>
                                    <td class="text-end">
                                        <span class="rd-status-tag <?= $isOut ? 'rd-status-danger' : 'rd-status-warning' ?>">
                                            <?= $isOut ? 'Hết hàng' : 'Cần nhập thêm' ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- 5. HIỆU QUẢ THỰC ĐƠN & HOẠT ĐỘNG GẦN ĐÂY -->
<div class="row g-3">
    <div class="col-lg-6">
        <section class="rd-panel h-100">
            <div class="rd-panel-header">
                <div>
                    <h3 class="rd-panel-title">Món ăn mang lại doanh thu cao nhất</h3>
                    <p class="rd-panel-subtitle">Tổng hợp từ các đơn hàng đã thanh toán</p>
                </div>
                <a href="index.php?c=menu&a=index" class="btn btn-outline-slate btn-sm">Quản lý thực đơn</a>
            </div>
            <div class="rd-panel-body p-0">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Thứ hạng</th>
                                <th>Mã món</th>
                                <th>Tên món ăn</th>
                                <th class="text-center">Số lượng bán</th>
                                <th class="text-end">Doanh thu bằng VNĐ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($topDishes)): ?>
                                <tr><td colspan="5" class="text-center text-muted py-4">Chưa có dữ liệu món ăn.</td></tr>
                            <?php else: foreach ($topDishes as $idx => $dish): ?>
                                <tr>
                                    <td class="fw-semibold text-secondary">Hạng <?= $idx + 1 ?></td>
                                    <td class="fw-medium"><?= rdCleanText($dish['item_code']) ?></td>
                                    <td class="fw-semibold"><?= rdCleanText($dish['name']) ?></td>
                                    <td class="text-center"><?= (int)$dish['total_qty'] ?> <?= rdCleanText($dish['unit'] ?? 'Phần') ?></td>
                                    <td class="text-end fw-bold"><?= number_format((float)$dish['total_rev'], 0, ',', '.') ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>

    <div class="col-lg-6">
        <section class="rd-panel h-100">
            <div class="rd-panel-header">
                <div>
                    <h3 class="rd-panel-title">Hoạt động gần đây</h3>
                    <p class="rd-panel-subtitle">Nhật ký vận hành và thao tác hệ thống mới nhất</p>
                </div>
                <a href="index.php?c=menu&a=users&tab=logs" class="btn btn-outline-slate btn-sm">Xem toàn bộ nhật ký</a>
            </div>
            <div class="rd-panel-body p-0">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Thời gian</th>
                                <th>Phân hệ</th>
                                <th>Nội dung hoạt động</th>
                                <th>Nhân viên</th>
                                <th class="text-end">Mức độ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentLogs)): ?>
                                <tr><td colspan="5" class="text-center text-muted py-4">Chưa có hoạt động nào được ghi nhận.</td></tr>
                            <?php else: foreach ($recentLogs as $log):
                                $sev = $log['severity'] ?? 'INFO';
                                $sevLabel = $sev === 'CRITICAL' ? 'Quan trọng' : ($sev === 'WARNING' ? 'Cảnh báo' : 'Thông tin');
                                $sevClass = $sev === 'CRITICAL' ? 'rd-status-danger' : ($sev === 'WARNING' ? 'rd-status-warning' : 'rd-status-info');
                            ?>
                                <tr>
                                    <td class="text-secondary small whitespace-nowrap"><?= date('H:i d/m', strtotime($log['created_at'])) ?></td>
                                    <td class="fw-medium"><?= rdCleanText($log['module_name']) ?></td>
                                    <td><?= rdCleanText($log['description']) ?></td>
                                    <td><?= rdCleanText($log['full_name']) ?></td>
                                    <td class="text-end">
                                        <span class="rd-status-tag <?= $sevClass ?>"><?= $sevLabel ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
var dailyLabels    = <?= json_encode($dailyLabels, JSON_UNESCAPED_UNICODE) ?>;
var dailyRevenue   = <?= json_encode($dailyRevenue) ?>;
var monthlyLabels  = <?= json_encode($monthlyLabels, JSON_UNESCAPED_UNICODE) ?>;
var monthlyRevenue = <?= json_encode($monthlyRevenue) ?>;

var ctxRev = document.getElementById('revenueChart').getContext('2d');
var revenueChart = new Chart(ctxRev, {
    type: 'bar',
    data: {
        labels: dailyLabels,
        datasets: [{
            label: 'Doanh thu bằng VNĐ',
            data: dailyRevenue,
            backgroundColor: '#111c33',
            borderColor: '#111c33',
            borderWidth: 1,
            borderRadius: 4,
            maxBarThickness: 42
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
                        return ' Doanh thu: ' + Number(ctx.raw).toLocaleString('vi-VN') + ' VNĐ';
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return Number(value).toLocaleString('vi-VN') + ' VNĐ';
                    }
                }
            }
        }
    }
});

function switchRevenueMode(mode) {
    var btnDaily = document.getElementById('btnRevDaily');
    var btnMonthly = document.getElementById('btnRevMonthly');
    var title = document.getElementById('revChartTitle');

    if (mode === 'monthly') {
        revenueChart.config.type = 'line';
        revenueChart.data.labels = monthlyLabels;
        revenueChart.data.datasets[0].data = monthlyRevenue;
        revenueChart.data.datasets[0].fill = true;
        revenueChart.data.datasets[0].backgroundColor = 'rgba(201, 158, 50, 0.16)';
        revenueChart.data.datasets[0].borderColor = '#b88b22';
        revenueChart.data.datasets[0].borderWidth = 2;
        title.textContent = 'Biểu đồ doanh thu 6 tháng gần nhất';
        btnMonthly.className = 'btn btn-primary-navy btn-sm';
        btnDaily.className = 'btn btn-outline-slate btn-sm';
    } else {
        revenueChart.config.type = 'bar';
        revenueChart.data.labels = dailyLabels;
        revenueChart.data.datasets[0].data = dailyRevenue;
        revenueChart.data.datasets[0].fill = false;
        revenueChart.data.datasets[0].backgroundColor = '#111c33';
        revenueChart.data.datasets[0].borderColor = '#111c33';
        revenueChart.data.datasets[0].borderWidth = 1;
        title.textContent = 'Biểu đồ doanh thu 7 ngày gần nhất';
        btnDaily.className = 'btn btn-primary-navy btn-sm';
        btnMonthly.className = 'btn btn-outline-slate btn-sm';
    }
    revenueChart.update();
}

<?php if ($totalPaymentAmount > 0): ?>
var ctxPm = document.getElementById('paymentMethodChart').getContext('2d');
new Chart(ctxPm, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($paymentLabels, JSON_UNESCAPED_UNICODE) ?>,
        datasets: [{
            data: <?= json_encode($paymentValues) ?>,
            backgroundColor: ['#111c33', '#c99e32', '#475569'],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom' },
            tooltip: {
                callbacks: {
                    label: function(ctx) {
                        return ' ' + ctx.label + ': ' + Number(ctx.raw).toLocaleString('vi-VN') + ' VNĐ';
                    }
                }
            }
        }
    }
});
<?php endif; ?>
</script>