<?php
$items = $items ?? [];
$categories = $categories ?? [];
$ingredients = $ingredients ?? [];
$bomByItem = $bomByItem ?? [];
$modByItem = $modByItem ?? [];
$catFilter = isset($catFilter) ? (int)$catFilter : 0;

// Bản đồ tra cứu nhanh nguyên liệu từ kho để tính Giá vốn (COGS) & kiểm tra tồn kho
$ingMap = [];
foreach ($ingredients as $ing) {
    $ingMap[(int)$ing['id']] = $ing;
}

// Phân loại 6 danh mục thống nhất: Tất cả | Khai vị | Món chính | Tráng miệng | Rượu & Cocktail | Khác
function resolveUnifiedGroup(string $catName, string $courseType = ''): string {
    $n = mb_strtolower(trim($catName), 'UTF-8');
    if (strpos($n, 'khai vị') !== false && strpos($n, 'yêu thương') === false) return 'appetizer';
    if (strpos($n, 'món chính') !== false || strpos($n, 'main') !== false) return 'main';
    if (strpos($n, 'tráng miệng') !== false || strpos($n, 'dessert') !== false) return 'dessert';
    if (strpos($n, 'rượu') !== false || strpos($n, 'cocktail') !== false || strpos($n, 'wine') !== false) return 'beverage';

    if ($courseType === 'Appetizer' || $courseType === 'Soup_Salad') return 'appetizer';
    if ($courseType === 'Main_Course' || $courseType === 'Set_Menu') return 'main';
    if ($courseType === 'Dessert') return 'dessert';
    if ($courseType === 'Beverage') return 'beverage';
    return 'other';
}

$catCourseMap = [];
foreach ($categories as $c) {
    $catCourseMap[(int)$c['id']] = resolveUnifiedGroup($c['name'] ?? '', $c['course_type'] ?? '');
}

// Tính toán thống kê tổng quan & làm giàu dữ liệu từng món
$totalItemsCount = count($items);
$activeItemsCount = 0;
$inactiveItemsCount = 0;
$bomConfiguredCount = 0;
$bomMissingCount = 0;
$modConfiguredCount = 0;
$totalSellingPrice = 0;
$totalCostConfigured = 0;

$groupCounts = [
    'all'       => $totalItemsCount,
    'appetizer' => 0,
    'main'      => 0,
    'dessert'   => 0,
    'beverage'  => 0,
    'other'     => 0
];

foreach ($items as &$item) {
    $itemId = (int)$item['id'];
    $catId = (int)$item['category_id'];
    $uGroup = $catCourseMap[$catId] ?? resolveUnifiedGroup($item['category_name'] ?? '');
    $item['unified_group'] = $uGroup;
    if (isset($groupCounts[$uGroup])) {
        $groupCounts[$uGroup]++;
    }

    $isAvailable = ($item['status'] === 'Available');
    if ($isAvailable) {
        $activeItemsCount++;
    } else {
        $inactiveItemsCount++;
    }

    $itemBoms = $bomByItem[$itemId] ?? [];
    $itemMods = $modByItem[$itemId] ?? [];
    $item['bom_items_count'] = count($itemBoms);
    $item['mod_items_count'] = count($itemMods);

    $itemCost = 0;
    $hasStockError = false;
    foreach ($itemBoms as $b) {
        $ingId = (int)$b['ingredient_id'];
        $qtyReq = (float)$b['quantity_required'];
        if (isset($ingMap[$ingId])) {
            $unitCost = (float)$ingMap[$ingId]['cost_price'];
            $itemCost += ($qtyReq * $unitCost);
            if ((float)$ingMap[$ingId]['stock_quantity'] < $qtyReq) {
                $hasStockError = true;
            }
        }
    }

    $item['calculated_cost'] = $itemCost;
    $item['has_stock_error'] = $hasStockError;

    if ($item['bom_items_count'] > 0) {
        $bomConfiguredCount++;
        $totalCostConfigured += $itemCost;
    } else {
        $bomMissingCount++;
    }

    if ($item['mod_items_count'] > 0) {
        $modConfiguredCount++;
    }

    $totalSellingPrice += (float)$item['price'];
}
unset($item);

$avgPrice = $totalItemsCount > 0 ? round($totalSellingPrice / $totalItemsCount) : 0;
$bomCompletionRate = $totalItemsCount > 0 ? round(($bomConfiguredCount / $totalItemsCount) * 100, 1) : 0;
$modCompletionRate = $totalItemsCount > 0 ? round(($modConfiguredCount / $totalItemsCount) * 100, 1) : 0;

$openBomId = isset($_GET['open_bom']) ? (int)$_GET['open_bom'] : 0;
$openModId = isset($_GET['open_mod']) ? (int)$_GET['open_mod'] : 0;
?>

<style>
:root {
    --rd-navy: #17243b;
    --rd-navy-dark: #0f172a;
    --rd-gold: #c69b45;
    --rd-gold-light: #fef9eb;
    --rd-bg: #f8fafc;
    --rd-card: #ffffff;
    --rd-border: #e2e8f0;
    --rd-text: #1e293b;
    --rd-muted: #64748b;
    --rd-green: #15803d;
    --rd-green-bg: #dcfce7;
    --rd-orange: #d97706;
    --rd-orange-bg: #fef3c7;
    --rd-red: #b91c1c;
    --rd-red-bg: #fee2e2;
}

.rd-menu-workspace {
    font-family: 'Be Vietnam Pro', sans-serif;
    background-color: var(--rd-bg);
    color: var(--rd-text);
    padding: 20px 24px 48px;
}

/* Header Bar */
.rd-top-bar {
    background: var(--rd-card);
    border: 1px solid var(--rd-border);
    border-left: 5px solid var(--rd-navy);
    border-radius: 12px;
    padding: 18px 24px;
    margin-bottom: 18px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
    box-shadow: 0 2px 10px rgba(15, 23, 42, 0.04);
}
.rd-top-title {
    font-size: 20px;
    font-weight: 800;
    color: var(--rd-navy);
    margin: 0 0 4px 0;
    letter-spacing: -0.2px;
}
.rd-top-desc {
    font-size: 13px;
    color: var(--rd-muted);
    margin: 0;
}
.rd-top-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}
.rd-btn {
    padding: 10px 16px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    border: 1px solid var(--rd-border);
    background: var(--rd-card);
    color: var(--rd-navy);
    transition: all 0.15s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.rd-btn:hover {
    border-color: var(--rd-gold);
    background: var(--rd-gold-light);
}
.rd-btn-gold {
    background: var(--rd-gold);
    border-color: #b38936;
    color: var(--rd-navy);
    font-weight: 800;
}
.rd-btn-gold:hover {
    background: var(--rd-navy);
    color: #e5bd69;
    border-color: var(--rd-navy);
}
.rd-btn-navy {
    background: var(--rd-navy);
    border-color: var(--rd-navy);
    color: #e5bd69;
}
.rd-btn-navy:hover {
    background: var(--rd-navy-dark);
    color: #ffffff;
}

/* Flash Alerts */
.rd-flash {
    padding: 12px 18px;
    border-radius: 9px;
    font-size: 13.5px;
    font-weight: 700;
    margin-bottom: 16px;
}
.rd-flash-ok { background: var(--rd-green-bg); color: var(--rd-green); border: 1px solid #86efac; }
.rd-flash-err { background: var(--rd-red-bg); color: var(--rd-red); border: 1px solid #fca5a5; }

/* Unified Category Bar */
.rd-unified-cat-bar {
    background: var(--rd-card);
    border: 1px solid var(--rd-border);
    border-radius: 12px;
    padding: 12px 16px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    box-shadow: 0 2px 6px rgba(15, 23, 42, 0.03);
}
.rd-cat-label-box {
    display: flex;
    align-items: center;
    gap: 10px;
}
.rd-cat-label-title {
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
    color: var(--rd-navy);
    letter-spacing: 0.4px;
}
.rd-cat-count-pill {
    background: #f1f5f9;
    color: var(--rd-muted);
    font-size: 11.5px;
    font-weight: 700;
    padding: 3px 9px;
    border-radius: 20px;
}
.rd-cat-tabs {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
    flex: 1;
}
.rd-cat-tab {
    padding: 8px 15px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 700;
    border: 1px solid transparent;
    background: #f8fafc;
    color: #475569;
    cursor: pointer;
    transition: all 0.15s ease;
    white-space: nowrap;
}
.rd-cat-tab:hover {
    border-color: var(--rd-gold);
    color: var(--rd-navy);
    background: var(--rd-gold-light);
}
.rd-cat-tab.active {
    background: var(--rd-navy);
    color: #e5bd69;
    border-color: var(--rd-navy);
    box-shadow: 0 2px 6px rgba(23, 36, 59, 0.18);
}

/* Filter Bar */
.rd-filter-toolbar {
    background: var(--rd-card);
    border: 1px solid var(--rd-border);
    border-radius: 12px;
    padding: 14px 16px;
    margin-bottom: 20px;
    display: grid;
    grid-template-columns: 1.5fr 1fr 1fr 1fr auto;
    gap: 12px;
    align-items: center;
    box-shadow: 0 2px 6px rgba(15, 23, 42, 0.03);
}
@media (max-width: 1100px) {
    .rd-filter-toolbar {
        grid-template-columns: 1fr 1fr;
    }
}
.rd-filter-input, .rd-filter-select {
    width: 100%;
    padding: 9px 12px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-size: 13px;
    font-family: 'Be Vietnam Pro', sans-serif;
    color: var(--rd-text);
    background: #ffffff;
}
.rd-filter-input:focus, .rd-filter-select:focus {
    outline: none;
    border-color: var(--rd-gold);
    box-shadow: 0 0 0 3px rgba(198, 155, 69, 0.15);
}
.rd-view-switch {
    display: flex;
    gap: 6px;
}
.rd-view-btn {
    padding: 8px 13px;
    border-radius: 8px;
    font-size: 12.5px;
    font-weight: 700;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    color: #475569;
    cursor: pointer;
}
.rd-view-btn.active {
    background: var(--rd-navy);
    color: #e5bd69;
    border-color: var(--rd-navy);
}

/* Main 2-Column Layout inspired by Idea Page */
.rd-content-layout {
    display: grid;
    grid-template-columns: 1fr 330px;
    gap: 20px;
    align-items: start;
}
@media (max-width: 1200px) {
    .rd-content-layout {
        grid-template-columns: 1fr;
    }
}

/* Card Grid Mode (Inspired by Dark Concept Page, styled in RoyalDine Navy/Gold/White) */
.rd-card-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 22px 18px;
    padding-top: 28px;
}
@media (max-width: 1400px) {
    .rd-card-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
@media (max-width: 768px) {
    .rd-card-grid {
        grid-template-columns: 1fr;
    }
}
.rd-dish-card {
    background: var(--rd-card);
    border: 1px solid var(--rd-border);
    border-radius: 16px;
    padding: 58px 18px 18px;
    position: relative;
    text-align: center;
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.05);
    transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
    margin-top: 26px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.rd-dish-card:hover {
    transform: translateY(-4px);
    border-color: var(--rd-gold);
    box-shadow: 0 10px 24px rgba(23, 36, 59, 0.10);
}
.rd-dish-plate {
    width: 88px;
    height: 88px;
    border-radius: 50%;
    background: var(--rd-navy);
    border: 4px solid #ffffff;
    box-shadow: 0 6px 16px rgba(23, 36, 59, 0.22), 0 0 0 2px var(--rd-gold);
    position: absolute;
    top: -44px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #e5bd69;
    overflow: hidden;
}
.rd-dish-plate img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.rd-dish-plate-code {
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.3px;
    color: #e5bd69;
}
.rd-dish-plate-unit {
    font-size: 10px;
    color: #cbd5e1;
    margin-top: 2px;
}
.rd-card-status-corner {
    position: absolute;
    top: 12px;
    right: 12px;
}
.rd-card-code-corner {
    position: absolute;
    top: 12px;
    left: 12px;
    font-size: 11px;
    font-weight: 800;
    color: var(--rd-muted);
    background: #f1f5f9;
    padding: 3px 8px;
    border-radius: 6px;
}
.rd-dish-title {
    font-size: 15.5px;
    font-weight: 800;
    color: var(--rd-navy);
    margin: 6px 0 4px;
    line-height: 1.35;
}
.rd-dish-cat {
    font-size: 12px;
    color: var(--rd-muted);
    margin-bottom: 10px;
    font-weight: 600;
}
.rd-price-strip {
    display: flex;
    justify-content: center;
    align-items: baseline;
    gap: 12px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 8px 12px;
    margin-bottom: 12px;
}
.rd-sell-price {
    font-size: 16px;
    font-weight: 800;
    color: var(--rd-navy);
}
.rd-cost-price {
    font-size: 11.5px;
    font-weight: 700;
    color: var(--rd-muted);
}

/* Status Pills (Green / Orange / Red) */
.rd-pill {
    display: inline-block;
    padding: 4px 9px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.2px;
}
.rd-pill-green {
    background: var(--rd-green-bg);
    color: var(--rd-green);
    border: 1px solid #86efac;
}
.rd-pill-orange {
    background: var(--rd-orange-bg);
    color: var(--rd-orange);
    border: 1px solid #fcd34d;
}
.rd-pill-red {
    background: var(--rd-red-bg);
    color: var(--rd-red);
    border: 1px solid #fca5a5;
}

.rd-config-summary {
    text-align: left;
    background: #f8fafc;
    border-radius: 9px;
    padding: 9px 11px;
    margin-bottom: 14px;
    font-size: 11.5px;
    border: 1px solid #e2e8f0;
}
.rd-config-line {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 5px;
}
.rd-config-line:last-child {
    margin-bottom: 0;
}
.rd-config-preview {
    font-size: 11px;
    color: var(--rd-muted);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-top: 4px;
}

/* 4 Action Buttons at bottom of Dish Card (Inspired by View/Edit/Delete/Duplicate) */
.rd-card-actions {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 6px;
    padding-top: 10px;
    border-top: 1px dashed #e2e8f0;
}
.rd-act-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    background: transparent;
    border: none;
    cursor: pointer;
    padding: 4px 2px;
    text-decoration: none;
}
.rd-act-circle {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: 800;
    transition: all 0.15s ease;
    border: 1px solid transparent;
}
.rd-act-circle.bom-ok { background: var(--rd-green-bg); color: var(--rd-green); border-color: #86efac; }
.rd-act-circle.bom-warn { background: var(--rd-orange-bg); color: var(--rd-orange); border-color: #fcd34d; }
.rd-act-circle.mod-btn { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; }
.rd-act-circle.edit-btn { background: var(--rd-gold-light); color: #92400e; border-color: #fde68a; }
.rd-act-circle.del-btn { background: var(--rd-red-bg); color: var(--rd-red); border-color: #fca5a5; }
.rd-act-item:hover .rd-act-circle {
    background: var(--rd-navy);
    color: #e5bd69;
    border-color: var(--rd-navy);
}
.rd-act-label {
    font-size: 10.5px;
    font-weight: 700;
    color: #475569;
}

/* Table View Mode */
.rd-table-container {
    background: var(--rd-card);
    border: 1px solid var(--rd-border);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(15, 23, 42, 0.04);
    display: none;
}
.rd-menu-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}
.rd-menu-table thead th {
    background: var(--rd-navy);
    color: #e5bd69;
    font-weight: 800;
    font-size: 11.5px;
    text-transform: uppercase;
    padding: 13px 14px;
    text-align: left;
}
.rd-menu-table tbody td {
    padding: 13px 14px;
    border-bottom: 1px solid var(--rd-border);
    vertical-align: middle;
}
.rd-menu-table tbody tr:hover {
    background: #f8fafc;
}

/* Right Sidebar KPI Panel (Inspired by Idea Page Right Column) */
.rd-side-panel {
    display: flex;
    flex-direction: column;
    gap: 16px;
}
.rd-kpi-box {
    background: var(--rd-card);
    border: 1px solid var(--rd-border);
    border-radius: 14px;
    padding: 18px;
    box-shadow: 0 2px 10px rgba(15, 23, 42, 0.04);
}
.rd-kpi-box.navy-theme {
    background: var(--rd-navy);
    color: #ffffff;
    border-color: var(--rd-navy);
}
.rd-kpi-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 12px;
}
.rd-kpi-label {
    font-size: 12px;
    font-weight: 700;
    color: var(--rd-muted);
    text-transform: uppercase;
}
.rd-kpi-box.navy-theme .rd-kpi-label {
    color: #cbd5e1;
}
.rd-kpi-big {
    font-size: 26px;
    font-weight: 800;
    color: var(--rd-navy);
    margin-top: 4px;
}
.rd-kpi-box.navy-theme .rd-kpi-big {
    color: #e5bd69;
}
.rd-progress-track {
    width: 100%;
    height: 8px;
    background: #e2e8f0;
    border-radius: 10px;
    overflow: hidden;
    margin: 10px 0;
}
.rd-progress-fill {
    height: 100%;
    background: var(--rd-green);
    border-radius: 10px;
}
.rd-stat-row {
    display: flex;
    justify-content: space-between;
    font-size: 12.5px;
    padding: 6px 0;
    border-bottom: 1px dashed #e2e8f0;
}
.rd-stat-row:last-child {
    border-bottom: none;
}

/* Modals (BOM, Modifiers, Dish CRUD, Category CRUD) */
.rd-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.6);
    z-index: 1050;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
}
.rd-modal-backdrop.show {
    display: flex;
}
.rd-modal-dialog {
    background: #ffffff;
    border-radius: 14px;
    width: 100%;
    max-width: 820px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 50px rgba(15, 23, 42, 0.3);
    border: 1px solid var(--rd-border);
}
.rd-modal-header {
    background: var(--rd-navy);
    color: #ffffff;
    padding: 16px 22px;
    border-bottom: 3px solid var(--rd-gold);
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.rd-modal-header h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 800;
    color: #e5bd69;
    text-transform: uppercase;
}
.rd-modal-close {
    background: rgba(255,255,255,0.12);
    color: #ffffff;
    border: none;
    padding: 5px 12px;
    border-radius: 6px;
    font-weight: 800;
    cursor: pointer;
}
.rd-modal-body {
    padding: 22px;
}
.rd-form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 14px;
}
.rd-form-grid-3 {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
}
@media (max-width: 768px) {
    .rd-form-grid, .rd-form-grid-3 {
        grid-template-columns: 1fr;
    }
}
.rd-field-label {
    display: block;
    font-size: 12px;
    font-weight: 800;
    color: var(--rd-navy);
    margin-bottom: 5px;
}
.rd-subtable {
    width: 100%;
    border-collapse: collapse;
    font-size: 12.5px;
    margin-top: 12px;
}
.rd-subtable th {
    background: #f1f5f9;
    color: var(--rd-navy);
    padding: 9px 10px;
    text-align: left;
    font-weight: 800;
    border-bottom: 2px solid #cbd5e1;
}
.rd-subtable td {
    padding: 9px 10px;
    border-bottom: 1px solid #e2e8f0;
}
</style>

<div class="rd-menu-workspace">

    <!-- Thanh tiêu đề chính -->
    <div class="rd-top-bar">
        <div>
            <h1 class="rd-top-title">QUẢN LÝ THỰC ĐƠN 5 SAO, ĐỊNH LƯỢNG BOM VÀ SỞ THÍCH PHỤC VỤ</h1>
            <p class="rd-top-desc">Thiết lập món ăn Fine Dining, tự động quy đổi định lượng, tính giá vốn nguyên liệu và đồng bộ POS / KDS</p>
        </div>
        <div class="rd-top-actions">
            <button type="button" class="rd-btn" onclick="openRdModal('modalCategory')">Quản lý Nhóm thực đơn</button>
            <button type="button" class="rd-btn rd-btn-gold" onclick="openDishModal()">Thêm Món / Set Menu 5 Sao</button>
        </div>
    </div>

    <?php if (!empty($_SESSION['flash_msg'])): ?>
        <div class="rd-flash rd-flash-ok">
            <?= htmlspecialchars($_SESSION['flash_msg']) ?>
            <?php unset($_SESSION['flash_msg']); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['flash_err'])): ?>
        <div class="rd-flash rd-flash-err">
            <?= htmlspecialchars($_SESSION['flash_err']) ?>
            <?php unset($_SESSION['flash_err']); ?>
        </div>
    <?php endif; ?>

    <!-- Thanh danh mục thực đơn thống nhất (6 nhóm chuẩn) -->
    <div class="rd-unified-cat-bar">
        <div class="rd-cat-label-box">
            <span class="rd-cat-label-title">Danh mục thực đơn</span>
            <span class="rd-cat-count-pill"><?= count($categories) ?> danh mục</span>
        </div>
        <div class="rd-cat-tabs" id="unifiedCatTabs">
            <button type="button" class="rd-cat-tab active" data-group="all">
                Tất cả (<?= $groupCounts['all'] ?>)
            </button>
            <button type="button" class="rd-cat-tab" data-group="appetizer">
                Khai vị (<?= $groupCounts['appetizer'] ?>)
            </button>
            <button type="button" class="rd-cat-tab" data-group="main">
                Món chính (<?= $groupCounts['main'] ?>)
            </button>
            <button type="button" class="rd-cat-tab" data-group="dessert">
                Tráng miệng (<?= $groupCounts['dessert'] ?>)
            </button>
            <button type="button" class="rd-cat-tab" data-group="beverage">
                Rượu & Cocktail (<?= $groupCounts['beverage'] ?>)
            </button>
            <button type="button" class="rd-cat-tab" data-group="other">
                Khác (<?= $groupCounts['other'] ?>)
            </button>
        </div>
    </div>

    <!-- Thanh bộ lọc đa chiều bên cạnh danh mục -->
    <div class="rd-filter-toolbar">
        <div>
            <input type="text" id="filterKeyword" class="rd-filter-input" placeholder="Tìm kiếm theo mã món, tên món hoặc nhóm thực đơn...">
        </div>
        <div>
            <select id="filterStatus" class="rd-filter-select">
                <option value="ALL">Trạng thái kinh doanh: Tất cả</option>
                <option value="Available">Đang kinh doanh (Xanh lá)</option>
                <option value="Stopped">Ngừng kinh doanh / Tạm hết (Đỏ)</option>
            </select>
        </div>
        <div>
            <select id="filterBom" class="rd-filter-select">
                <option value="ALL">Tình trạng cấu hình BOM: Tất cả</option>
                <option value="Configured">Đã cấu hình BOM (Xanh lá)</option>
                <option value="Missing">Cần bổ sung cấu hình BOM (Cam)</option>
            </select>
        </div>
        <div>
            <select id="filterMod" class="rd-filter-select">
                <option value="ALL">Tùy chọn Modifiers: Tất cả</option>
                <option value="HasMod">Món có Modifiers</option>
                <option value="NoMod">Chưa có Modifiers</option>
            </select>
        </div>
        <div class="rd-view-switch">
            <button type="button" class="rd-view-btn active" id="btnViewCard" onclick="switchMenuView('card')">Thẻ món ăn</button>
            <button type="button" class="rd-view-btn" id="btnViewTable" onclick="switchMenuView('table')">Bảng chi tiết</button>
        </div>
    </div>

    <!-- Khung làm việc 2 cột: Danh sách món ăn bên trái + Cột KPI Thực đơn/BOM bên phải -->
    <div class="rd-content-layout">

        <!-- CỘT TRÁI: HIỂN THỊ THẺ CARD GRID HOẶC BẢNG -->
        <div>
            <!-- Chế độ 1: Card Grid lấy cảm hứng từ trang mẫu -->
            <div class="rd-card-grid" id="menuCardView">
                <?php foreach ($items as $item): ?>
                    <?php
                    $id = (int)$item['id'];
                    $isAvail = ($item['status'] === 'Available');
                    $bomCount = (int)$item['bom_items_count'];
                    $modCount = (int)$item['mod_items_count'];
                    $costVal = (float)$item['calculated_cost'];
                    $sellVal = (float)$item['price'];
                    $costPercent = ($sellVal > 0 && $costVal > 0) ? round(($costVal / $sellVal) * 100, 1) : 0;
                    $searchBlob = mb_strtolower($item['item_code'] . ' ' . $item['name'] . ' ' . $item['category_name'], 'UTF-8');
                    ?>
                    <div class="rd-dish-card menu-filter-item"
                         data-group="<?= htmlspecialchars($item['unified_group']) ?>"
                         data-search="<?= htmlspecialchars($searchBlob) ?>"
                         data-status="<?= $isAvail ? 'Available' : 'Stopped' ?>"
                         data-bom="<?= $bomCount > 0 ? 'Configured' : 'Missing' ?>"
                         data-mod="<?= $modCount > 0 ? 'HasMod' : 'NoMod' ?>">

                        <span class="rd-card-code-corner"><?= htmlspecialchars($item['item_code']) ?></span>

                        <div class="rd-card-status-corner">
                            <?php if ($isAvail): ?>
                                <span class="rd-pill rd-pill-green">Đang kinh doanh</span>
                            <?php else: ?>
                                <span class="rd-pill rd-pill-red">Ngừng kinh doanh</span>
                            <?php endif; ?>
                        </div>

                        <div class="rd-dish-plate">
                            <?php if (!empty($item['image_url'])): ?>
                                <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                            <?php else: ?>
                                <span class="rd-dish-plate-code"><?= htmlspecialchars($item['item_code']) ?></span>
                                <span class="rd-dish-plate-unit"><?= htmlspecialchars($item['unit']) ?></span>
                            <?php endif; ?>
                        </div>

                        <div>
                            <h3 class="rd-dish-title"><?= htmlspecialchars($item['name']) ?></h3>
                            <div class="rd-dish-cat">
                                <?= htmlspecialchars($item['category_name']) ?> · ĐVT: <?= htmlspecialchars($item['unit']) ?>
                            </div>

                            <div class="rd-price-strip">
                                <div>
                                    <div style="font-size: 10.5px; color: var(--rd-muted); font-weight: 700;">GIÁ BÁN</div>
                                    <div class="rd-sell-price"><?= number_format($sellVal, 0, ',', '.') ?>đ</div>
                                </div>
                                <div style="border-left: 1px solid #cbd5e1; padding-left: 12px;">
                                    <div style="font-size: 10.5px; color: var(--rd-muted); font-weight: 700;">GIÁ VỐN BOM</div>
                                    <div class="rd-cost-price">
                                        <?= $bomCount > 0 ? (number_format($costVal, 0, ',', '.') . 'đ (' . $costPercent . '%)') : 'Chưa tính' ?>
                                    </div>
                                </div>
                            </div>

                            <div class="rd-config-summary">
                                <div class="rd-config-line">
                                    <span style="font-weight: 700; color: var(--rd-navy);">Định lượng BOM:</span>
                                    <?php if ($bomCount > 0 && !$item['has_stock_error']): ?>
                                        <span class="rd-pill rd-pill-green">Đã cấu hình (<?= $bomCount ?> NVL)</span>
                                    <?php elseif ($bomCount > 0 && $item['has_stock_error']): ?>
                                        <span class="rd-pill rd-pill-red">Thiếu tồn kho NVL</span>
                                    <?php else: ?>
                                        <span class="rd-pill rd-pill-orange">Cần bổ sung BOM</span>
                                    <?php endif; ?>
                                </div>
                                <div class="rd-config-preview">
                                    <?= !empty($item['bom_list']) ? htmlspecialchars($item['bom_list']) : 'Chưa thiết lập công thức xuất kho tự động' ?>
                                </div>

                                <div class="rd-config-line" style="margin-top: 8px;">
                                    <span style="font-weight: 700; color: var(--rd-navy);">Modifiers POS/KDS:</span>
                                    <?php if ($modCount > 0): ?>
                                        <span class="rd-pill rd-pill-green"><?= $modCount ?> tùy chọn</span>
                                    <?php else: ?>
                                        <span class="rd-pill rd-pill-orange">Chưa cấu hình</span>
                                    <?php endif; ?>
                                </div>
                                <div class="rd-config-preview">
                                    <?= !empty($item['modifier_list']) ? htmlspecialchars($item['modifier_list']) : 'Không có tùy chọn chế biến đi kèm' ?>
                                </div>
                            </div>
                        </div>

                        <div class="rd-card-actions">
                            <button type="button" class="rd-act-item" onclick="openBomModal(<?= $id ?>)">
                                <span class="rd-act-circle <?= $bomCount > 0 ? 'bom-ok' : 'bom-warn' ?>">BOM</span>
                                <span class="rd-act-label">Định lượng</span>
                            </button>

                            <button type="button" class="rd-act-item" onclick="openModModal(<?= $id ?>)">
                                <span class="rd-act-circle mod-btn">MOD</span>
                                <span class="rd-act-label">Sở thích</span>
                            </button>

                            <button type="button" class="rd-act-item"
                                    onclick='openDishModal(<?= json_encode([
                                        "id" => $id,
                                        "category_id" => (int)$item["category_id"],
                                        "item_code" => $item["item_code"],
                                        "name" => $item["name"],
                                        "unit" => $item["unit"],
                                        "price" => (float)$item["price"],
                                        "status" => $item["status"]
                                    ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                <span class="rd-act-circle edit-btn">SỬA</span>
                                <span class="rd-act-label">Cập nhật</span>
                            </button>

                            <a href="index.php?c=menu&a=deleteMenuItem&id=<?= $id ?>"
                               class="rd-act-item"
                               onclick="return confirm('Xác nhận xóa món <?= htmlspecialchars(addslashes($item['name'])) ?> khỏi thực đơn?');">
                                <span class="rd-act-circle del-btn">XÓA</span>
                                <span class="rd-act-label">Xóa món</span>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Chế độ 2: Bảng chi tiết (Table View) -->
            <div class="rd-table-container" id="menuTableView">
                <table class="rd-menu-table">
                    <thead>
                        <tr>
                            <th>Mã món</th>
                            <th>Tên món / Set Menu</th>
                            <th>Giá bán & Giá vốn</th>
                            <th>Định lượng nguyên liệu (BOM)</th>
                            <th>Sở thích phục vụ (Modifiers)</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <td style="display:flex; align-items:center; gap:10px;">
                        <?php if (!empty($item['image_url'])): ?>
                            <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="" style="width:42px; height:42px; border-radius:8px; object-fit:cover; border:1px solid #cbd5e1;">
                        <?php endif; ?>
                        <div>
                            <div style="font-weight: 800; color: var(--rd-navy);"><?= htmlspecialchars($item['name']) ?></div>
                            <div style="font-size: 12px; color: var(--rd-muted);">
                                <?= htmlspecialchars($item['category_name']) ?> · ĐVT: <?= htmlspecialchars($item['unit']) ?>
                            </div>
                        </div>
                    </td>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <?php
                            $id = (int)$item['id'];
                            $isAvail = ($item['status'] === 'Available');
                            $bomCount = (int)$item['bom_items_count'];
                            $modCount = (int)$item['mod_items_count'];
                            $costVal = (float)$item['calculated_cost'];
                            $sellVal = (float)$item['price'];
                            $searchBlob = mb_strtolower($item['item_code'] . ' ' . $item['name'] . ' ' . $item['category_name'], 'UTF-8');
                            ?>
                            <tr class="menu-filter-item"
                                data-group="<?= htmlspecialchars($item['unified_group']) ?>"
                                data-search="<?= htmlspecialchars($searchBlob) ?>"
                                data-status="<?= $isAvail ? 'Available' : 'Stopped' ?>"
                                data-bom="<?= $bomCount > 0 ? 'Configured' : 'Missing' ?>"
                                data-mod="<?= $modCount > 0 ? 'HasMod' : 'NoMod' ?>">
                                <td style="font-weight: 800; color: var(--rd-navy);"><?= htmlspecialchars($item['item_code']) ?></td>
                                <td>
                                    <div style="font-weight: 800; color: var(--rd-navy);"><?= htmlspecialchars($item['name']) ?></div>
                                    <div style="font-size: 12px; color: var(--rd-muted);">
                                        <?= htmlspecialchars($item['category_name']) ?> · ĐVT: <?= htmlspecialchars($item['unit']) ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 800; color: var(--rd-navy);"><?= number_format($sellVal, 0, ',', '.') ?>đ</div>
                                    <div style="font-size: 11.5px; color: var(--rd-muted);">
                                        Giá vốn: <?= $bomCount > 0 ? (number_format($costVal, 0, ',', '.') . 'đ') : 'Chưa tính' ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($bomCount > 0): ?>
                                        <div style="margin-bottom: 5px;">
                                            <span class="rd-pill rd-pill-green">Đã cấu hình (<?= $bomCount ?>)</span>
                                        </div>
                                        <div style="font-size: 11.5px; color: #334155; max-width: 240px;"><?= htmlspecialchars($item['bom_list']) ?></div>
                                    <?php else: ?>
                                        <div style="margin-bottom: 5px;">
                                            <span class="rd-pill rd-pill-orange">Cần bổ sung cấu hình</span>
                                        </div>
                                    <?php endif; ?>
                                    <button type="button" class="rd-btn" style="padding: 5px 10px; font-size: 11.5px; margin-top: 5px;" onclick="openBomModal(<?= $id ?>)">
                                        Cấu hình BOM (<?= $bomCount ?>)
                                    </button>
                                </td>
                                <td>
                                    <?php if ($modCount > 0): ?>
                                        <div style="margin-bottom: 5px;">
                                            <span class="rd-pill rd-pill-green">Đã đồng bộ POS/KDS (<?= $modCount ?>)</span>
                                        </div>
                                        <div style="font-size: 11.5px; color: #334155; max-width: 250px;"><?= htmlspecialchars($item['modifier_list']) ?></div>
                                    <?php else: ?>
                                        <div style="font-size: 11.5px; color: var(--rd-muted); margin-bottom: 5px;">Chưa có tùy chọn đi kèm</div>
                                    <?php endif; ?>
                                    <button type="button" class="rd-btn" style="padding: 5px 10px; font-size: 11.5px; margin-top: 5px;" onclick="openModModal(<?= $id ?>)">
                                        Modifiers (<?= $modCount ?>)
                                    </button>
                                </td>
                                <td>
                                    <a href="index.php?c=menu&a=toggleMenuStatus&id=<?= $id ?>" style="text-decoration: none;">
                                        <?php if ($isAvail): ?>
                                            <span class="rd-pill rd-pill-green">Đang kinh doanh</span>
                                        <?php else: ?>
                                            <span class="rd-pill rd-pill-red">Ngừng kinh doanh</span>
                                        <?php endif; ?>
                                    </a>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 6px;">
                                        <button type="button" class="rd-btn" style="padding: 6px 10px; font-size: 12px;"
                                                onclick='openDishModal(<?= json_encode([
                                                    "id" => $id,
                                                    "category_id" => (int)$item["category_id"],
                                                    "item_code" => $item["item_code"],
                                                    "name" => $item["name"],
                                                    "unit" => $item["unit"],
                                                    "price" => (float)$item["price"],
                                                    "image_url" => $item["image_url"] ?? "",
                                                    "status" => $item["status"]
                                                ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>Sửa</button>
                                        <a href="index.php?c=menu&a=deleteMenuItem&id=<?= $id ?>"
                                           class="rd-btn"
                                           style="padding: 6px 10px; font-size: 12px; color: var(--rd-red); border-color: #fca5a5;"
                                           onclick="return confirm('Xác nhận xóa món ăn này?');">Xóa</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- CỘT PHẢI: BẢNG CHỈ SỐ KPI & KIỂM SOÁT BOM / MODIFIERS (LẤY CẢM HỨNG TỪ TRANG MẪU) -->
        <div class="rd-side-panel">

            <!-- Thẻ 1: Tổng quan danh mục & Giá bán trung bình -->
            <div class="rd-kpi-box navy-theme">
                <div class="rd-kpi-head">
                    <div>
                        <div class="rd-kpi-label">Giá bán trung bình thực đơn</div>
                        <div class="rd-kpi-big"><?= number_format($avgPrice, 0, ',', '.') ?>đ</div>
                    </div>
                    <svg width="70" height="36" viewBox="0 0 70 36" fill="none">
                        <path d="M2 30L18 18L34 24L52 8L68 12" stroke="#c69b45" stroke-width="3" stroke-linecap="round"/>
                        <path d="M2 30L18 18L34 24L52 8L68 12V35H2V30Z" fill="rgba(198, 155, 69, 0.2)"/>
                    </svg>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 12px; color: #cbd5e1; border-top: 1px solid rgba(255,255,255,0.12); padding-top: 10px;">
                    <span>Tổng số món: <strong style="color:#fff;"><?= $totalItemsCount ?></strong></span>
                    <span>Đang bán: <strong style="color:#4ade80;"><?= $activeItemsCount ?></strong></span>
                    <span>Ngừng bán: <strong style="color:#f87171;"><?= $inactiveItemsCount ?></strong></span>
                </div>
            </div>

            <!-- Thẻ 2: Tình trạng cấu hình Định lượng BOM & Xuất kho tự động -->
            <div class="rd-kpi-box">
                <div class="rd-kpi-head">
                    <div>
                        <div class="rd-kpi-label">Độ phủ Định lượng BOM</div>
                        <div class="rd-kpi-big"><?= $bomCompletionRate ?>%</div>
                    </div>
                    <span class="rd-pill <?= $bomMissingCount === 0 ? 'rd-pill-green' : 'rd-pill-orange' ?>">
                        <?= $bomConfiguredCount ?> / <?= $totalItemsCount ?> món
                    </span>
                </div>
                <div class="rd-progress-track">
                    <div class="rd-progress-fill" style="width: <?= $bomCompletionRate ?>%;"></div>
                </div>
                <div class="rd-stat-row">
                    <span>Đã liên kết xuất kho tự động</span>
                    <strong style="color: var(--rd-green);"><?= $bomConfiguredCount ?> món</strong>
                </div>
                <div class="rd-stat-row">
                    <span>Cần bổ sung công thức BOM</span>
                    <strong style="color: var(--rd-orange);"><?= $bomMissingCount ?> món</strong>
                </div>
                <div class="rd-stat-row">
                    <span>Kho nguyên liệu sẵn sàng</span>
                    <strong style="color: var(--rd-navy);"><?= count($ingredients) ?> NVL</strong>
                </div>
            </div>

            <!-- Thẻ 3: Tình trạng Modifiers & Đồng bộ POS/KDS -->
            <div class="rd-kpi-box">
                <div class="rd-kpi-head">
                    <div>
                        <div class="rd-kpi-label">Sở thích phục vụ (Modifiers)</div>
                        <div class="rd-kpi-big"><?= $modCompletionRate ?>%</div>
                    </div>
                    <span class="rd-pill rd-pill-green">Đồng bộ POS/KDS</span>
                </div>
                <div class="rd-stat-row">
                    <span>Món đã gắn nhóm tùy chọn</span>
                    <strong style="color: var(--rd-navy);"><?= $modConfiguredCount ?> món</strong>
                </div>
                <div class="rd-stat-row">
                    <span>Hỗ trợ Bắt buộc / Chọn nhiều</span>
                    <strong style="color: var(--rd-green);">Đã kích hoạt</strong>
                </div>
                <div class="rd-stat-row">
                    <span>Tự động cộng giá phụ thu</span>
                    <strong style="color: var(--rd-green);">Đã kích hoạt</strong>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- ==================== MODAL 1: THÊM / SỬA MÓN ĂN & SET MENU ==================== -->
<div class="rd-modal-backdrop" id="modalDish">
    <div class="rd-modal-dialog" style="max-width: 650px;">
        <div class="rd-modal-header">
            <h3 id="modalDishTitle">Thêm Món / Set Menu 5 Sao</h3>
            <button type="button" class="rd-modal-close" onclick="closeRdModal('modalDish')">Đóng</button>
        </div>
        <div class="rd-modal-body">
            <form method="POST" action="index.php?c=menu&a=saveMenuItem" enctype="multipart/form-data">
                <input type="hidden" name="item_id" id="dish_item_id" value="0">
                <div class="rd-form-grid">
                    <div>
                        <label class="rd-field-label">Mã món ăn / Set Menu *</label>
                        <input type="text" name="item_code" id="dish_item_code" class="rd-filter-input" placeholder="VD: RD-MAIN02" required>
                    </div>
                    <div>
                        <label class="rd-field-label">Nhóm thực đơn *</label>
                        <select name="category_id" id="dish_category_id" class="rd-filter-select" required>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="grid-column: span 2;">
                        <label class="rd-field-label">Tên món ăn / Set Menu *</label>
                        <input type="text" name="name" id="dish_name" class="rd-filter-input" placeholder="Nhập tên món ăn chuẩn thực đơn 5 sao" required>
                    </div>
                    <div>
                        <label class="rd-field-label">Đơn vị tính *</label>
                        <input type="text" name="unit" id="dish_unit" class="rd-filter-input" value="Phần" required>
                    </div>
                    <div>
                        <label class="rd-field-label">Giá bán niêm yết (VNĐ) *</label>
                        <input type="number" name="price" id="dish_price" class="rd-filter-input" min="0" step="1000" value="0" required>
                    </div>

                    <!-- Khu vực cập nhật hình ảnh món ăn -->
                    <div>
                        <label class="rd-field-label">Tải ảnh món ăn từ máy (JPG, PNG, WEBP)</label>
                        <input type="file" name="image_file" id="dish_image_file" class="rd-filter-input" accept="image/*" onchange="previewDishImage(this)">
                    </div>
                    <div>
                        <label class="rd-field-label">Hoặc dán đường dẫn ảnh (URL / Path)</label>
                        <input type="text" name="image_url" id="dish_image_url" class="rd-filter-input" placeholder="VD: public/uploads/menu/wagyu.jpg" oninput="previewDishUrl(this.value)">
                    </div>
                    <div style="grid-column: span 2; display: flex; align-items: center; gap: 14px; background: #f8fafc; padding: 10px 14px; border-radius: 10px; border: 1px dashed #cbd5e1;">
                        <img id="dish_img_preview" src="" alt="Preview" style="width: 64px; height: 64px; border-radius: 50%; object-fit: cover; border: 2px solid var(--rd-gold); display: none;">
                        <div id="dish_img_placeholder" style="width: 64px; height: 64px; border-radius: 50%; background: var(--rd-navy); color: #e5bd69; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 800;">Chưa có ảnh</div>
                        <div style="font-size: 12px; color: var(--rd-muted);">
                            <strong>Xem trước hình ảnh hiển thị:</strong><br>
                            Hình ảnh này sẽ hiển thị trên đĩa tròn ở Quản lý Thực đơn và trên thẻ chọn món tại màn hình Bán hàng POS.
                        </div>
                    </div>

                    <div style="grid-column: span 2;">
                        <label class="rd-field-label">Trạng thái kinh doanh</label>
                        <select name="status" id="dish_status" class="rd-filter-select">
                            <option value="Available">Đang kinh doanh (Hiển thị mở bán trên POS)</option>
                            <option value="Unavailable">Tạm hết / Ngừng kinh doanh (Khóa gọi món trên POS)</option>
                        </select>
                    </div>
                </div>
                <div style="margin-top: 18px; display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" class="rd-btn" onclick="closeRdModal('modalDish')">Hủy</button>
                    <button type="submit" class="rd-btn rd-btn-gold">Lưu thông tin món</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==================== MODAL 2: QUẢN LÝ NHÓM THỰC ĐƠN ==================== -->
<div class="rd-modal-backdrop" id="modalCategory">
    <div class="rd-modal-dialog" style="max-width: 560px;">
        <div class="rd-modal-header">
            <h3>Quản lý Nhóm Thực Đơn</h3>
            <button type="button" class="rd-modal-close" onclick="closeRdModal('modalCategory')">Đóng</button>
        </div>
        <div class="rd-modal-body">
            <form method="POST" action="index.php?c=menu&a=saveCategory" style="margin-bottom: 18px;">
                <div class="rd-form-grid">
                    <div>
                        <label class="rd-field-label">Tên nhóm thực đơn mới *</label>
                        <input type="text" name="category_name" class="rd-filter-input" placeholder="VD: Súp & Salad Hoàng Gia" required>
                    </div>
                    <div>
                        <label class="rd-field-label">Phân nhóm điều phối KDS *</label>
                        <select name="course_type" class="rd-filter-select">
                            <option value="Appetizer">Khai vị (Ưu tiên ra món 1)</option>
                            <option value="Soup_Salad">Súp & Salad (Ưu tiên ra món 2)</option>
                            <option value="Main_Course" selected>Món chính (Ưu tiên ra món 3)</option>
                            <option value="Set_Menu">Set Menu (Ưu tiên ra món 3)</option>
                            <option value="Dessert">Tráng miệng (Ưu tiên ra món 4)</option>
                            <option value="Beverage">Rượu & Cocktail (Chuyển quầy Bar)</option>
                        </select>
                    </div>
                </div>
                <div style="margin-top: 12px; text-align: right;">
                    <button type="submit" class="rd-btn rd-btn-gold">Thêm nhóm thực đơn</button>
                </div>
            </form>

            <table class="rd-subtable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tên nhóm thực đơn</th>
                        <th>Phân luồng POS / KDS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $c): ?>
                        <tr>
                            <td>#<?= (int)$c['id'] ?></td>
                            <td style="font-weight: 700; color: var(--rd-navy);"><?= htmlspecialchars($c['name']) ?></td>
                            <td><span class="rd-pill rd-pill-green"><?= htmlspecialchars($c['course_type'] ?? 'Main_Course') ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ==================== MODAL 3: CẤU HÌNH ĐỊNH LƯỢNG BOM & TỰ ĐỘNG QUY ĐỔI ĐƠN VỊ ==================== -->
<div class="rd-modal-backdrop" id="modalBom">
    <div class="rd-modal-dialog">
        <div class="rd-modal-header">
            <h3 id="bomModalTitle">Cấu hình Định lượng BOM & Giá vốn nguyên liệu</h3>
            <button type="button" class="rd-modal-close" onclick="closeRdModal('modalBom')">Đóng</button>
        </div>
        <div class="rd-modal-body">
            <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 9px; padding: 12px 14px; margin-bottom: 16px; font-size: 12.5px;">
                <strong>Cơ chế liên kết Kho tự động:</strong> Chọn nguyên liệu từ kho, nhập định lượng và chọn đơn vị tính. Hệ thống tự động quy đổi về đơn vị gốc của kho, tính giá vốn nguyên liệu và tự động trừ kho khi thu ngân thanh toán hóa đơn trên POS.
            </div>

            <form method="POST" action="index.php?c=menu&a=addBom" id="bomForm" onsubmit="return syncConvertedBomQuantity();">
                <input type="hidden" name="menu_item_id" id="bom_menu_item_id" value="0">
                <input type="hidden" name="quantity_required" id="bom_quantity_required_final" value="0">

                <div class="rd-form-grid-3">
                    <div>
                        <label class="rd-field-label">1. Chọn nguyên liệu từ kho *</label>
                        <select name="ingredient_id" id="bom_ingredient_select" class="rd-filter-select" onchange="updateBomUnitOptions()" required>
                            <option value="">-- Chọn nguyên liệu --</option>
                            <?php foreach ($ingredients as $ing): ?>
                                <option value="<?= (int)$ing['id'] ?>"
                                        data-unit="<?= htmlspecialchars($ing['unit']) ?>"
                                        data-cost="<?= (float)$ing['cost_price'] ?>"
                                        data-stock="<?= (float)$ing['stock_quantity'] ?>">
                                    <?= htmlspecialchars($ing['ingredient_code'] . ' - ' . $ing['name'] . ' (Tồn: ' . $ing['stock_quantity'] . ' ' . $ing['unit'] . ' · Giá vốn: ' . number_format((float)$ing['cost_price']) . 'đ/' . $ing['unit'] . ')') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="rd-field-label">2. Nhập định lượng *</label>
                        <input type="number" id="bom_raw_qty" class="rd-filter-input" step="0.01" min="0.01" placeholder="VD: 200" oninput="recalcBomPreview()" required>
                    </div>
                    <div>
                        <label class="rd-field-label">3. Chọn đơn vị tính (Tự quy đổi)</label>
                        <select id="bom_input_unit" class="rd-filter-select" onchange="recalcBomPreview()">
                            <option value="1">Theo đơn vị gốc của kho</option>
                        </select>
                    </div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-top: 12px; padding: 10px 14px; background: var(--rd-gold-light); border: 1px solid #fde68a; border-radius: 8px; font-size: 12.5px;">
                    <div>
                        Định lượng quy đổi xuất kho: <strong id="bomPreviewConverted" style="color: var(--rd-navy);">0</strong>
                        <span style="margin: 0 10px;">|</span>
                        Giá vốn nguyên liệu dòng này: <strong id="bomPreviewCost" style="color: var(--rd-green);">0đ</strong>
                    </div>
                    <button type="submit" class="rd-btn rd-btn-gold">Lưu vào công thức BOM</button>
                </div>
            </form>

            <div id="bomTablesContainer" style="margin-top: 18px;">
                <?php foreach ($items as $item): ?>
                    <?php
                    $mId = (int)$item['id'];
                    $bList = $bomByItem[$mId] ?? [];
                    $totalDishCost = 0;
                    ?>
                    <div class="bom-item-panel" id="bom_panel_<?= $mId ?>" data-dish-name="<?= htmlspecialchars($item['name']) ?>" style="display: none;">
                        <table class="rd-subtable">
                            <thead>
                                <tr>
                                    <th>Nguyên liệu kho</th>
                                    <th>Định lượng chuẩn</th>
                                    <th>Đơn giá vốn</th>
                                    <th>Thành tiền giá vốn</th>
                                    <th>Trạng thái tồn kho</th>
                                    <th>Xóa</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($bList)): ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center; color: var(--rd-orange); font-weight: 700; padding: 18px;">
                                            Món ăn này chưa có định lượng BOM. Hãy chọn nguyên liệu phía trên để liên kết xuất kho tự động.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($bList as $row): ?>
                                        <?php
                                        $ingInfo = $ingMap[(int)$row['ingredient_id']] ?? null;
                                        $uCost = $ingInfo ? (float)$ingInfo['cost_price'] : 0;
                                        $lineCost = (float)$row['quantity_required'] * $uCost;
                                        $totalDishCost += $lineCost;
                                        $stockQty = $ingInfo ? (float)$ingInfo['stock_quantity'] : 0;
                                        $enough = $stockQty >= (float)$row['quantity_required'];
                                        ?>
                                        <tr>
                                            <td style="font-weight: 700; color: var(--rd-navy);"><?= htmlspecialchars($row['ingredient_name']) ?></td>
                                            <td style="font-weight: 800;"><?= number_format((float)$row['quantity_required'], 2) ?> <?= htmlspecialchars($row['unit']) ?></td>
                                            <td><?= number_format($uCost, 0, ',', '.') ?>đ / <?= htmlspecialchars($row['unit']) ?></td>
                                            <td style="font-weight: 800; color: var(--rd-navy);"><?= number_format($lineCost, 0, ',', '.') ?>đ</td>
                                            <td>
                                                <?php if ($enough): ?>
                                                    <span class="rd-pill rd-pill-green">Sẵn sàng xuất kho (Tồn: <?= number_format($stockQty, 1) ?>)</span>
                                                <?php else: ?>
                                                    <span class="rd-pill rd-pill-red">Thiếu tồn kho (Còn: <?= number_format($stockQty, 1) ?>)</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="index.php?c=menu&a=deleteBom&menu_item_id=<?= $mId ?>&ingredient_id=<?= (int)$row['ingredient_id'] ?>"
                                                   style="color: var(--rd-red); font-weight: 800; text-decoration: none;">Xóa</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr style="background: #f8fafc;">
                                        <td colspan="3" style="text-align: right; font-weight: 800; color: var(--rd-navy);">TỔNG GIÁ VỐN NGUYÊN LIỆU (COGS):</td>
                                        <td colspan="3" style="font-weight: 800; font-size: 14px; color: var(--rd-green);">
                                            <?= number_format($totalDishCost, 0, ',', '.') ?>đ
                                            (Giá bán: <?= number_format((float)$item['price'], 0, ',', '.') ?>đ)
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- ==================== MODAL 4: CẤU HÌNH MODIFIERS (SỞ THÍCH PHỤC VỤ ĐỒNG BỘ POS/KDS) ==================== -->
<div class="rd-modal-backdrop" id="modalMod">
    <div class="rd-modal-dialog">
        <div class="rd-modal-header">
            <h3 id="modModalTitle">Cấu hình Sở thích phục vụ (Modifiers) đồng bộ POS / KDS</h3>
            <button type="button" class="rd-modal-close" onclick="closeRdModal('modalMod')">Đóng</button>
        </div>
        <div class="rd-modal-body">
            <form method="POST" action="index.php?c=menu&a=addModifier" onsubmit="return composeModifierGroupBeforeSubmit();">
                <input type="hidden" name="menu_item_id" id="mod_menu_item_id" value="0">
                <input type="hidden" name="modifier_group" id="mod_group_final" value="">

                <div class="rd-form-grid-3">
                    <div>
                        <label class="rd-field-label">1. Tên nhóm tùy chọn *</label>
                        <input type="text" id="mod_group_base" class="rd-filter-input" placeholder="VD: Độ chín, Sốt kèm" value="Độ chín" required>
                    </div>
                    <div>
                        <label class="rd-field-label">2. Ràng buộc lựa chọn</label>
                        <select id="mod_req_rule" class="rd-filter-select">
                            <option value="Bắt buộc">Bắt buộc chọn</option>
                            <option value="Tùy chọn">Không bắt buộc</option>
                        </select>
                    </div>
                    <div>
                        <label class="rd-field-label">3. Số lượng & Giới hạn chọn</label>
                        <select id="mod_limit_rule" class="rd-filter-select">
                            <option value="Chọn 1">Chọn 1 tùy chọn</option>
                            <option value="Nhiều-Max 2">Chọn nhiều (Tối đa 2)</option>
                            <option value="Nhiều-Max 3">Chọn nhiều (Tối đa 3)</option>
                            <option value="Chọn nhiều">Chọn nhiều (Không giới hạn)</option>
                        </select>
                    </div>
                </div>

                <div class="rd-form-grid" style="margin-top: 12px;">
                    <div>
                        <label class="rd-field-label">4. Tên tùy chọn hiển thị trên POS / KDS *</label>
                        <input type="text" name="modifier_name" class="rd-filter-input" placeholder="VD: Medium Rare, Sốt nấm Truffle..." required>
                    </div>
                    <div>
                        <label class="rd-field-label">5. Giá cộng thêm (VNĐ)</label>
                        <input type="number" name="extra_price" class="rd-filter-input" min="0" step="5000" value="0">
                    </div>
                </div>

                <div style="margin-top: 12px; display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 12px; color: var(--rd-muted);">
                        Tự động đồng bộ sang màn hình gọi món POS và phiếu chế biến KDS Bếp/Bar.
                    </span>
                    <button type="submit" class="rd-btn rd-btn-gold">Thêm tùy chọn Modifier</button>
                </div>
            </form>

            <div id="modTablesContainer" style="margin-top: 18px;">
                <?php foreach ($items as $item): ?>
                    <?php
                    $mId = (int)$item['id'];
                    $mList = $modByItem[$mId] ?? [];
                    ?>
                    <div class="mod-item-panel" id="mod_panel_<?= $mId ?>" data-dish-name="<?= htmlspecialchars($item['name']) ?>" style="display: none;">
                        <table class="rd-subtable">
                            <thead>
                                <tr>
                                    <th>Nhóm tùy chọn & Quy tắc</th>
                                    <th>Tên tùy chọn chế biến</th>
                                    <th>Giá cộng thêm</th>
                                    <th>Đồng bộ POS/KDS</th>
                                    <th>Xóa</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($mList)): ?>
                                    <tr>
                                        <td colspan="5" style="text-align: center; color: var(--rd-muted); padding: 18px;">
                                            Món ăn này chưa có tùy chọn phục vụ đi kèm.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($mList as $mRow): ?>
                                        <tr>
                                            <td><span class="rd-pill rd-pill-green"><?= htmlspecialchars($mRow['modifier_group']) ?></span></td>
                                            <td style="font-weight: 700; color: var(--rd-navy);"><?= htmlspecialchars($mRow['modifier_name']) ?></td>
                                            <td style="font-weight: 800; color: var(--rd-orange);">
                                                <?= (float)$mRow['extra_price'] > 0 ? ('+' . number_format((float)$mRow['extra_price'], 0, ',', '.') . 'đ') : 'Miễn phí (+0đ)' ?>
                                            </td>
                                            <td><span class="rd-pill rd-pill-green">Đã đồng bộ</span></td>
                                            <td>
                                                <a href="index.php?c=menu&a=deleteModifier&id=<?= (int)$mRow['id'] ?>&menu_item_id=<?= $mId ?>"
                                                   style="color: var(--rd-red); font-weight: 800; text-decoration: none;">Xóa</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
let currentCategoryGroup = 'all';

function applyMenuFilters() {
    const kw = (document.getElementById('filterKeyword').value || '').trim().toLowerCase();
    const st = document.getElementById('filterStatus').value;
    const bom = document.getElementById('filterBom').value;
    const mod = document.getElementById('filterMod').value;

    document.querySelectorAll('.menu-filter-item').forEach(function (el) {
        const matchGroup = (currentCategoryGroup === 'all') || (el.getAttribute('data-group') === currentCategoryGroup);
        const matchKw = (kw === '') || ((el.getAttribute('data-search') || '').indexOf(kw) !== -1);
        const matchSt = (st === 'ALL') || (el.getAttribute('data-status') === st);
        const matchBom = (bom === 'ALL') || (el.getAttribute('data-bom') === bom);
        const matchMod = (mod === 'ALL') || (el.getAttribute('data-mod') === mod);

        el.style.display = (matchGroup && matchKw && matchSt && matchBom && matchMod) ? '' : 'none';
    });
}

document.querySelectorAll('#unifiedCatTabs .rd-cat-tab').forEach(function (btn) {
    btn.addEventListener('click', function () {
        document.querySelectorAll('#unifiedCatTabs .rd-cat-tab').forEach(function (b) { b.classList.remove('active'); });
        this.classList.add('active');
        currentCategoryGroup = this.getAttribute('data-group') || 'all';
        applyMenuFilters();
    });
});

['filterKeyword', 'filterStatus', 'filterBom', 'filterMod'].forEach(function (id) {
    const el = document.getElementById(id);
    if (el) {
        el.addEventListener('input', applyMenuFilters);
        el.addEventListener('change', applyMenuFilters);
    }
});

function switchMenuView(mode) {
    const cardView = document.getElementById('menuCardView');
    const tableView = document.getElementById('menuTableView');
    const btnCard = document.getElementById('btnViewCard');
    const btnTable = document.getElementById('btnViewTable');

    if (mode === 'table') {
        cardView.style.display = 'none';
        tableView.style.display = 'block';
        btnCard.classList.remove('active');
        btnTable.classList.add('active');
    } else {
        cardView.style.display = 'grid';
        tableView.style.display = 'none';
        btnCard.classList.add('active');
        btnTable.classList.remove('active');
    }
}

function openRdModal(id) {
    document.getElementById(id).classList.add('show');
}
function closeRdModal(id) {
    document.getElementById(id).classList.remove('show');
}

function setDishPreview(src) {
    const img = document.getElementById('dish_img_preview');
    const ph = document.getElementById('dish_img_placeholder');
    if (src && src.trim() !== '') {
        img.src = src;
        img.style.display = 'block';
        ph.style.display = 'none';
    } else {
        img.src = '';
        img.style.display = 'none';
        ph.style.display = 'flex';
    }
}

function previewDishImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
            setDishPreview(e.target.result);
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function previewDishUrl(url) {
    const fileInput = document.getElementById('dish_image_file');
    if (!fileInput.files || !fileInput.files.length) {
        setDishPreview(url);
    }
}

function openDishModal(dish) {
    const fileInput = document.getElementById('dish_image_file');
    if (fileInput) fileInput.value = '';

    if (dish && dish.id) {
        document.getElementById('modalDishTitle').textContent = 'Cập nhật Món / Set Menu: ' + dish.name;
        document.getElementById('dish_item_id').value = dish.id;
        document.getElementById('dish_category_id').value = dish.category_id;
        document.getElementById('dish_item_code').value = dish.item_code;
        document.getElementById('dish_name').value = dish.name;
        document.getElementById('dish_unit').value = dish.unit;
        document.getElementById('dish_price').value = dish.price;
        document.getElementById('dish_image_url').value = dish.image_url || '';
        document.getElementById('dish_status').value = dish.status === 'Available' ? 'Available' : 'Unavailable';
        setDishPreview(dish.image_url || '');
    } else {
        document.getElementById('modalDishTitle').textContent = 'Thêm Món / Set Menu 5 Sao';
        document.getElementById('dish_item_id').value = '0';
        document.getElementById('dish_item_code').value = '';
        document.getElementById('dish_name').value = '';
        document.getElementById('dish_unit').value = 'Phần';
        document.getElementById('dish_price').value = '0';
        document.getElementById('dish_image_url').value = '';
        document.getElementById('dish_status').value = 'Available';
        setDishPreview('');
    }
    openRdModal('modalDish');
}

function openBomModal(menuItemId) {
    document.getElementById('bom_menu_item_id').value = menuItemId;
    document.querySelectorAll('.bom-item-panel').forEach(function (p) { p.style.display = 'none'; });
    const activePanel = document.getElementById('bom_panel_' + menuItemId);
    if (activePanel) {
        activePanel.style.display = 'block';
        document.getElementById('bomModalTitle').textContent = 'Định lượng BOM & Giá vốn: ' + (activePanel.getAttribute('data-dish-name') || '');
    }
    openRdModal('modalBom');
}

function openModModal(menuItemId) {
    document.getElementById('mod_menu_item_id').value = menuItemId;
    document.querySelectorAll('.mod-item-panel').forEach(function (p) { p.style.display = 'none'; });
    const activePanel = document.getElementById('mod_panel_' + menuItemId);
    if (activePanel) {
        activePanel.style.display = 'block';
        document.getElementById('modModalTitle').textContent = 'Sở thích phục vụ (Modifiers): ' + (activePanel.getAttribute('data-dish-name') || '');
    }
    openRdModal('modalMod');
}

// Tự động nạp danh sách đơn vị quy đổi tương ứng với đơn vị gốc của kho
function updateBomUnitOptions() {
    const sel = document.getElementById('bom_ingredient_select');
    const unitSel = document.getElementById('bom_input_unit');
    unitSel.innerHTML = '';

    if (!sel.value) {
        unitSel.innerHTML = '<option value="1">Theo đơn vị gốc của kho</option>';
        recalcBomPreview();
        return;
    }

    const opt = sel.options[sel.selectedIndex];
    const baseUnit = (opt.getAttribute('data-unit') || '').trim();
    const uLower = baseUnit.toLowerCase();

    if (uLower === 'kg') {
        unitSel.innerHTML = '<option value="1">kg (Đơn vị kho)</option><option value="0.001">g (Tự động quy đổi 1000g = 1kg)</option>';
    } else if (uLower === 'g') {
        unitSel.innerHTML = '<option value="1">g (Đơn vị kho)</option><option value="1000">kg (Tự động quy đổi 1kg = 1000g)</option>';
    } else if (uLower === 'l' || uLower === 'lít') {
        unitSel.innerHTML = '<option value="1">' + baseUnit + ' (Đơn vị kho)</option><option value="0.001">ml (Tự động quy đổi 1000ml = 1L)</option>';
    } else if (uLower === 'ml') {
        unitSel.innerHTML = '<option value="1">ml (Đơn vị kho)</option><option value="1000">Lít (Tự động quy đổi 1L = 1000ml)</option>';
    } else {
        unitSel.innerHTML = '<option value="1">' + baseUnit + ' (Đơn vị gốc của kho)</option>';
    }
    recalcBomPreview();
}

function recalcBomPreview() {
    const sel = document.getElementById('bom_ingredient_select');
    const rawQty = parseFloat(document.getElementById('bom_raw_qty').value || '0');
    const factor = parseFloat(document.getElementById('bom_input_unit').value || '1');

    if (!sel.value || rawQty <= 0) {
        document.getElementById('bomPreviewConverted').textContent = '0';
        document.getElementById('bomPreviewCost').textContent = '0đ';
        document.getElementById('bom_quantity_required_final').value = '0';
        return;
    }

    const opt = sel.options[sel.selectedIndex];
    const baseUnit = opt.getAttribute('data-unit') || '';
    const unitCost = parseFloat(opt.getAttribute('data-cost') || '0');

    const convertedQty = Math.round(rawQty * factor * 1000) / 1000;
    const totalCost = Math.round(convertedQty * unitCost);

    document.getElementById('bom_quantity_required_final').value = convertedQty;
    document.getElementById('bomPreviewConverted').textContent = convertedQty + ' ' + baseUnit;
    document.getElementById('bomPreviewCost').textContent = totalCost.toLocaleString('vi-VN') + 'đ';
}

function syncConvertedBomQuantity() {
    recalcBomPreview();
    const finalQty = parseFloat(document.getElementById('bom_quantity_required_final').value || '0');
    if (finalQty <= 0) {
        alert('Vui lòng chọn nguyên liệu và nhập định lượng lớn hơn 0.');
        return false;
    }
    return true;
}

function composeModifierGroupBeforeSubmit() {
    const base = (document.getElementById('mod_group_base').value || 'Tùy chọn').trim();
    const reqRule = document.getElementById('mod_req_rule').value;
    const limitRule = document.getElementById('mod_limit_rule').value;
    const combined = (base + ' · ' + reqRule + ' · ' + limitRule).substring(0, 50);
    document.getElementById('mod_group_final').value = combined;
    return true;
}

document.addEventListener('DOMContentLoaded', function () {
    <?php if ($openBomId > 0): ?>
        openBomModal(<?= $openBomId ?>);
    <?php endif; ?>
    <?php if ($openModId > 0): ?>
        openModModal(<?= $openModId ?>);
    <?php endif; ?>
});
</script>