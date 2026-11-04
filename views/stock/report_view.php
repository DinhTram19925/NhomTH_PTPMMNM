<?php
    $startDate     = $_GET['start_date'] ?? date('Y-m-01 00:00:00');
    $endDate       = $_GET['end_date'] ?? date('Y-m-t 23:59:59');
    
    if (strtotime($startDate) > strtotime($endDate)) {
        $temp = $startDate;
        $startDate = $endDate;
        $endDate = $temp;
    }

    $filterIng     = $_GET['ingredient_id'] ?? '';
    $filterSup     = $_GET['supplier_id'] ?? '';
    
    $limit         = 10;
    $page          = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($page < 1) $page = 1;
    $offset        = ($page - 1) * $limit;

    require_once __DIR__ . '/../../models/StockModel.php';
    $stockModel = new StockModel();

    $report = $stockModel->getInventoryAndProfitReport($startDate, $endDate);
    $summary = $report['summary'];
    $allItems = $report['items'];

    $allIngredients = $stockModel->getIngredients('', '', '', '', 1000, 0);
    $allSuppliers   = $stockModel->getSuppliers('', 'active', 1000, 0);

    $filteredItems = $allItems;
    if (!empty($filterIng)) {
        $filteredItems = array_filter($filteredItems, function($it) use ($filterIng) {
            return $it['id'] == $filterIng;
        });
    }
    if (!empty($filterSup)) {
        $filteredItems = array_filter($filteredItems, function($it) use ($filterSup, $stockModel) {
            $ingDetail = $stockModel->getIngredientById($it['id']);
            return $ingDetail && $ingDetail['supplier_id'] == $filterSup;
        });
    }

    $totalRows = count($filteredItems);
    $totalPages = ceil($totalRows / $limit);
    $pagedItems = array_slice(array_values($filteredItems), $offset, $limit);

    $chartLabels = [];
    $chartImport = [];
    $chartExport = [];
    $chartConsumptionCost = [];

    $chartSourceItems = !empty($filterIng) ? $filteredItems : array_slice($filteredItems, 0, 15);
    foreach ($chartSourceItems as $it) {
        $chartLabels[] = $it['name'];
        $chartImport[] = (float)$it['total_imported'];
        $chartExport[] = (float)$it['total_exported'];
        $chartConsumptionCost[] = (float)($it['total_exported'] * $it['cost_price']);
    }

    $totalFilteredCogs = 0;
    foreach ($filteredItems as $it) {
        $totalFilteredCogs += ($it['total_exported'] * $it['cost_price']);
    }
    $displayCogs = $totalFilteredCogs > 0 ? $totalFilteredCogs : (float)$summary['total_cogs'];
    $displayRev = (float)$summary['total_revenue'] > 0 ? (float)$summary['total_revenue'] : 1;
    $displayGrossProfit = $displayRev - $displayCogs;
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    .report-wrapper { background-color: #f8fafc; padding: 24px; border-radius: 12px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #1e293b; }
    .report-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 24px; }
    .filter-section { background: #ffffff; padding: 18px 20px; border-radius: 10px; border: 1px solid #e2e8f0; margin-bottom: 24px; display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
    .filter-input { background: #ffffff; border: 1px solid #cbd5e1; color: #1e293b; padding: 8px 12px; border-radius: 6px; width: 100%; outline: none; font-size: 14px; }
    .filter-input:focus { border-color: #d4af37; box-shadow: 0 0 0 2px rgba(212,175,55,0.1); }
    
    .table-light-custom { width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; }
    .table-light-custom th { background-color: #f1f5f9; color: #0f172a; padding: 14px 16px; font-weight: 700; text-transform: uppercase; font-size: 12px; border-bottom: 2px solid #cbd5e1; }
    .table-light-custom td { padding: 14px 16px; border-bottom: 1px solid #e2e8f0; color: #334155; }
    .table-light-custom tr:hover { background-color: #f8fafc; }

    .btn-gold { background-color: #d4af37; color: #ffffff; border: none; padding: 9px 18px; border-radius: 6px; font-weight: 700; cursor: pointer; transition: all 0.2s; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; font-size: 14px; }
    .btn-gold:hover { background-color: #c59b27; color: #fff; }
    .btn-secondary-light { background-color: #64748b; color: #fff; border: none; padding: 9px 14px; border-radius: 6px; font-weight: 600; text-decoration: none; font-size: 14px; display: inline-flex; align-items: center; }
    
    .btn-excel { background-color: transparent; color: #d4af37; border: 1px solid #d4af37; padding: 6px 14px; border-radius: 6px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; font-size: 13px; transition: all 0.2s; }
    .btn-excel:hover { background-color: #d4af37; color: #1b1f23; }

    .pagination-container { display: flex; justify-content: center; padding: 16px 0; background: #ffffff; border-radius: 0 0 10px 10px; border-top: 1px solid #e2e8f0; }
    .pagination-custom { display: flex; list-style: none; padding: 0; margin: 0; border: 1px solid #cbd5e1; border-radius: 6px; overflow: hidden; }
    .pagination-custom li { border-right: 1px solid #cbd5e1; }
    .pagination-custom li:last-child { border-right: none; }
    .pagination-custom li a, .pagination-custom li span { display: block; padding: 7px 14px; text-decoration: none; color: #334155; font-weight: 600; background: #ffffff; transition: background-color 0.2s; font-size: 13px; }
    .pagination-custom li a:hover { background: #f1f5f9; }
    .pagination-custom li.active a { background: #d4af37; color: #fff; border-color: #d4af37; }
    .pagination-custom li.disabled span { color: #94a3b8; background: #f8fafc; cursor: not-allowed; }
</style>

<div class="report-wrapper">
    
    <div style="background-color: #1b1f23; border-bottom: 2px solid #d4af37; padding: 18px 24px; border-radius: 8px 8px 0 0; display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <h2 style="margin: 0; color: #d4af37; font-size: 18px; font-weight: 800; letter-spacing: 0.5px; text-transform: uppercase;">📈 TRUNG TÂM BÁO CÁO TÀI CHÍNH & KHO F&B</h2>
        <span style="color: #94a3b8; font-size: 13px; font-weight: 600;">Hệ thống quản lý 5S</span>
    </div>

    <div class="filter-section">
        <form id="reportFilterForm" method="GET" action="index.php" style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap; width: 100%; margin: 0;">
            <input type="hidden" name="c" value="stock">
            <input type="hidden" name="tab" value="report">
            
            <div style="flex: 1; min-width: 170px;">
                <label style="display: block; font-size: 11px; font-weight: bold; color: #64748b; margin-bottom: 5px; text-transform: uppercase;">Từ ngày:</label>
                <input type="datetime-local" id="startDate" name="start_date" value="<?= date('Y-m-d\TH:i', strtotime($startDate)) ?>" class="filter-input">
            </div>

            <div style="flex: 1; min-width: 170px;">
                <label style="display: block; font-size: 11px; font-weight: bold; color: #64748b; margin-bottom: 5px; text-transform: uppercase;">Đến ngày:</label>
                <input type="datetime-local" id="endDate" name="end_date" value="<?= date('Y-m-d\TH:i', strtotime($endDate)) ?>" class="filter-input">
            </div>

            <div style="flex: 1; min-width: 190px;">
                <label style="display: block; font-size: 11px; font-weight: bold; color: #64748b; margin-bottom: 5px; text-transform: uppercase;">Nguyên vật liệu:</label>
                <select name="ingredient_id" class="filter-input">
                    <option value="">-- Tất cả nguyên liệu --</option>
                    <?php foreach ($allIngredients as $ing): ?>
                        <option value="<?= $ing['id'] ?>" <?= $filterIng == $ing['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($ing['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="flex: 1; min-width: 190px;">
                <label style="display: block; font-size: 11px; font-weight: bold; color: #64748b; margin-bottom: 5px; text-transform: uppercase;">Nhà cung cấp:</label>
                <select name="supplier_id" class="filter-input">
                    <option value="">-- Tất cả nhà cung cấp --</option>
                    <?php foreach ($allSuppliers as $sup): ?>
                        <option value="<?= $sup['id'] ?>" <?= $filterSup == $sup['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($sup['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display: flex; gap: 8px;">
                <button type="submit" class="btn-gold">🔍 Lọc</button>
                <a href="index.php?c=stock&tab=report" class="btn-secondary-light">Đặt lại</a>
            </div>
        </form>
    </div>

    <script>
        document.getElementById('reportFilterForm').addEventListener('submit', function(e) {
            const start = new Date(document.getElementById('startDate').value);
            const end = new Date(document.getElementById('endDate').value);
            if (start > end) {
                e.preventDefault();
                alert('❌ Lỗi logic ngày: Thời gian bắt đầu ("Từ ngày") không được lớn hơn thời gian kết thúc ("Đến ngày")!');
            }
        });
    </script>

    
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 20px;">
        <div class="report-card" style="margin-bottom: 0;">
            <h4 style="margin: 0 0 15px 0; color: #1e293b; font-size: 14px; font-weight: 700;">📊 Nhập – Xuất kho</h4>
            <div style="position: relative; height: 220px;"><canvas id="barStockChart"></canvas></div>
        </div>
        <div class="report-card" style="margin-bottom: 0;">
            <h4 style="margin: 0 0 15px 0; color: #1e293b; font-size: 14px; font-weight: 700;">📈 Biến động tồn kho</h4>
            <div style="position: relative; height: 220px;"><canvas id="lineTrendChart"></canvas></div>
        </div>
        <div class="report-card" style="margin-bottom: 0;">
            <h4 style="margin: 0 0 15px 0; color: #1e293b; font-size: 14px; font-weight: 700;">🍩 Tỷ lệ Food Cost</h4>
            <div style="position: relative; height: 220px;"><canvas id="doughnutFoodCostChart"></canvas></div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 24px;">
        <div class="report-card" style="margin-bottom: 0;">
            <h4 style="margin: 0 0 15px 0; color: #1e293b; font-size: 14px; font-weight: 700;">⭐ Top Nguyên Liệu Tiêu Hao (BOM)</h4>
            <div style="position: relative; height: 230px;"><canvas id="horizontalTopChart"></canvas></div>
        </div>
        <div class="report-card" style="margin-bottom: 0;">
            <h4 style="margin: 0 0 15px 0; color: #1e293b; font-size: 14px; font-weight: 700;">📉 Doanh thu – Giá vốn – Lãi gộp</h4>
            <div style="position: relative; height: 230px;"><canvas id="barProfitChart"></canvas></div>
        </div>
    </div>

    <div class="report-card" style="padding: 0; overflow: hidden;">
        
        <div style="padding: 16px 20px; background: #1b1f23; border-bottom: 2px solid #d4af37; display: flex; justify-content: space-between; align-items: center; border-radius: 8px 8px 0 0;">
            <span style="font-weight: 800; color: #d4af37; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">📋 Bảng Xuất – Nhập – Tồn kho & Tài chính</span>
            <a href="index.php?c=stock&a=export_excel&start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>" class="btn-excel">
                📄 Xuất Excel
            </a>
        </div>

        <div style="overflow-x: auto;">
            <table class="table-light-custom">
                <thead>
                    <tr>
                        <th style="text-align: center; width: 60px;">STT</th>
                        <th>Nguyên vật liệu</th>
                        <th style="text-align: center;">Đơn vị</th>
                        <th style="text-align: right;">Nhập trong kỳ</th>
                        <th style="text-align: right;">Xuất trong kỳ</th>
                        <th style="text-align: right;">Tồn cuối kỳ</th>
                        <th style="text-align: center; width: 120px;">Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pagedItems)): ?>
                        <tr><td colspan="7" style="text-align: center; padding: 40px; color: #64748b;">Không tìm thấy dữ liệu phù hợp.</td></tr>
                    <?php else: 
                        $stt = $offset + 1;
                        foreach ($pagedItems as $it): 
                            $endingStock = $it['total_imported'] - $it['total_exported'];
                            $isLow = $endingStock <= 5;
                    ?>
                        <tr>
                            <td style="text-align: center; font-weight: bold; color: #d4af37;"><?= $stt++ ?></td>
                            <td style="font-weight: 700; color: #0f172a;"><?= htmlspecialchars($it['name']) ?></td>
                            <td style="text-align: center;"><?= htmlspecialchars($it['unit']) ?></td>
                            <td style="text-align: right; font-weight: 600; color: #0284c7;"><?= number_format($it['total_imported'], 2) ?></td>
                            <td style="text-align: right; font-weight: 600; color: #dc2626;"><?= number_format($it['total_exported'], 2) ?></td>
                            <td style="text-align: right; font-weight: 700; color: #16a34a;"><?= number_format($endingStock, 2) ?></td>
                            <td style="text-align: center;">
                                <span style="background: <?= $isLow ? '#fee2e2' : '#dcfce7' ?>; color: <?= $isLow ? '#991b1b' : '#166534' ?>; padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: 700;">
                                    <?= $isLow ? 'Sắp hết' : 'Bình thường' ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="pagination-container">
                <ul class="pagination-custom">
                    <li class="<?= ($page <= 1) ? 'disabled' : '' ?>">
                        <a href="<?= ($page <= 1) ? '#' : 'index.php?c=stock&tab=report&page='.($page - 1).'&start_date='.urlencode($startDate).'&end_date='.urlencode($endDate).'&ingredient_id='.$filterIng.'&supplier_id='.$filterSup ?>">Trước</a>
                    </li>
                    
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="<?= ($page == $i) ? 'active' : '' ?>">
                            <a href="index.php?c=stock&tab=report&page=<?= $i ?>&start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>&ingredient_id=<?= $filterIng ?>&supplier_id=<?= $filterSup ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <li class="<?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                        <a href="<?= ($page >= $totalPages) ? '#' : 'index.php?c=stock&tab=report&page='.($page + 1).'&start_date='.urlencode($startDate).'&end_date='.urlencode($endDate).'&ingredient_id='.$filterIng.'&supplier_id='.$filterSup ?>">Sau</a>
                    </li>
                </ul>
            </div>
        <?php endif; ?>

    </div>
</div>

<script>
    const labels = <?= json_encode($chartLabels) ?>;
    const dataImport = <?= json_encode($chartImport) ?>;
    const dataExport = <?= json_encode($chartExport) ?>;
    const dataCost = <?= json_encode($chartConsumptionCost) ?>;

    Chart.defaults.color = '#475569';
    Chart.defaults.borderColor = '#e2e8f0';

    new Chart(document.getElementById('barStockChart'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                { label: 'Nhập kho', data: dataImport, backgroundColor: '#d4af37', borderRadius: 4 },
                { label: 'Xuất kho', data: dataExport, backgroundColor: '#0284c7', borderRadius: 4 }
            ]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });

    new Chart(document.getElementById('lineTrendChart'), {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Tồn cuối kỳ',
                data: dataImport.map((imp, idx) => imp - dataExport[idx]),
                borderColor: '#d4af37',
                backgroundColor: 'rgba(212, 175, 55, 0.1)',
                fill: true,
                tension: 0.2
            }]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });

    const totalCogs = <?= (float)$displayCogs ?>;
    const totalRev = <?= (float)$displayRev ?>;
    new Chart(document.getElementById('doughnutFoodCostChart'), {
        type: 'doughnut',
        data: {
            labels: ['Giá vốn NVL', 'Doanh thu còn lại'],
            datasets: [{
                data: [totalCogs, Math.max(totalRev - totalCogs, 0)],
                backgroundColor: ['#d4af37', '#0284c7'],
                borderWidth: 0
            }]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });

    new Chart(document.getElementById('horizontalTopChart'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Chi phí (VNĐ)',
                data: dataCost,
                backgroundColor: '#d4af37',
                borderRadius: 4
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false
        }
    });

    const grossProfit = <?= (float)$displayGrossProfit ?>;
    new Chart(document.getElementById('barProfitChart'), {
        type: 'bar',
        data: {
            labels: ['Kỳ hiện tại'],
            datasets: [
                { label: 'Doanh thu', data: [totalRev], backgroundColor: '#d4af37', borderRadius: 4 },
                { label: 'Giá vốn', data: [totalCogs], backgroundColor: '#0284c7', borderRadius: 4 },
                { label: 'Lãi gộp', data: [grossProfit], backgroundColor: '#16a34a', borderRadius: 4 }
            ]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });
</script>