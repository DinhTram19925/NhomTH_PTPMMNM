<?php
$isAdmin          = !empty($isAdmin);
$floorsSummary    = $floorsSummary ?? [];
$tables           = $tables ?? [];
$areas            = $areas ?? [];
$totalFloorsCount = count($floorsSummary) ?: 1;
$page_tbl         = (int)($page_tbl ?? 1);
$maxPage_tbl      = (int)($maxPage_tbl ?? 1);
$total_tbl        = (int)($total_tbl ?? count($tables));

$flashSuccess = $_SESSION['flash_msg'] ?? '';
unset($_SESSION['flash_msg']);

$queryParams = $_GET;
unset($queryParams['page_tbl'], $queryParams['success']);
$queryString = http_build_query($queryParams);

// Tính toán chỉ số tổng quan cho cột phải (giống thẻ KPI trang Thực đơn)
$totalCapacity = 0;
$countStandard = 0;
$countVip      = 0;
$countPremium  = 0;
$countEmpty    = 0;
$countBusy     = 0;

foreach ($tables as $t) {
    $cap = (int)($t['capacity'] ?? 4);
    $totalCapacity += $cap;
    $tp = $t['table_type'] ?? 'Standard';
    if ($tp === 'VIP') $countVip++;
    elseif ($tp === 'Premium') $countPremium++;
    else $countStandard++;

    if (($t['status'] ?? 'Available') === 'Available') $countEmpty++;
    else $countBusy++;
}
$avgCapacity = count($tables) > 0 ? round($totalCapacity / count($tables), 1) : 0;
$vipRatio    = count($tables) > 0 ? round((($countVip + $countPremium) / count($tables)) * 100, 1) : 0;
$readyRatio  = count($tables) > 0 ? round(($countEmpty / count($tables)) * 100, 1) : 100;
?>

<style>
:root {
    --rd-navy: #17243b;
    --rd-navy-dark: #0f172a;
    --rd-gold: #c69b45;
    --rd-gold-light: #e5bd69;
    --rd-bg: #f8fafc;
    --rd-card: #ffffff;
    --rd-border: #e2e8f0;
    --rd-text: #1e293b;
    --rd-muted: #64748b;
    --rd-green-bg: #dcfce7;
    --rd-green-text: #15803d;
    --rd-amber-bg: #fef3c7;
    --rd-amber-text: #b45309;
    --rd-blue-bg: #e0e7ff;
    --rd-blue-text: #1e40af;
    --rd-red-bg: #fee2e2;
    --rd-red-text: #b91c1c;
}

.rd-setup-page {
    font-family: 'Be Vietnam Pro', sans-serif;
    background: var(--rd-bg);
    color: var(--rd-text);
    padding: 16px 20px 48px;
}

/* 1. Thanh danh mục Pill Bar (Giống hệt DANH MỤC THỰC ĐƠN) */
.rd-pill-bar {
    background: var(--rd-card);
    border: 1px solid var(--rd-border);
    border-radius: 14px;
    padding: 12px 18px;
    margin-bottom: 14px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.03);
}
.rd-pill-group {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
}
.rd-bar-title {
    font-size: 12px;
    font-weight: 800;
    color: var(--rd-navy);
    text-transform: uppercase;
    letter-spacing: 0.4px;
    margin-right: 4px;
}
.rd-count-chip {
    background: #f1f5f9;
    color: var(--rd-muted);
    font-size: 11px;
    font-weight: 700;
    padding: 3px 9px;
    border-radius: 20px;
    margin-right: 6px;
}
.rd-cat-pill {
    padding: 7px 15px;
    border-radius: 20px;
    font-size: 12.5px;
    font-weight: 700;
    border: 1px solid var(--rd-border);
    background: #ffffff;
    color: #334155;
    cursor: pointer;
    transition: all 0.15s ease;
    text-decoration: none;
}
.rd-cat-pill:hover {
    border-color: var(--rd-gold);
    color: var(--rd-navy);
}
.rd-cat-pill.active {
    background: var(--rd-navy);
    color: #ffffff;
    border-color: var(--rd-navy);
}

/* 2. Thanh Tìm kiếm, Bộ lọc & Chuyển chế độ xem */
.rd-filter-bar {
    background: var(--rd-card);
    border: 1px solid var(--rd-border);
    border-radius: 14px;
    padding: 12px 16px;
    margin-bottom: 20px;
    display: grid;
    grid-template-columns: 1.5fr 1fr 1fr 1fr auto;
    gap: 10px;
    align-items: center;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.03);
}
@media (max-width: 1100px) {
    .rd-filter-bar { grid-template-columns: 1fr 1fr; }
}
.rd-input, .rd-select {
    width: 100%;
    padding: 8px 12px;
    font-size: 13px;
    font-family: inherit;
    color: var(--rd-text);
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 9px;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
.rd-input:focus, .rd-select:focus {
    outline: none;
    border-color: var(--rd-gold);
    box-shadow: 0 0 0 3px rgba(198, 155, 69, 0.15);
}
.rd-view-toggle {
    display: flex;
    gap: 6px;
}
.rd-toggle-btn {
    padding: 8px 14px;
    border-radius: 9px;
    font-size: 12.5px;
    font-weight: 700;
    border: 1px solid var(--rd-border);
    background: #ffffff;
    color: #475569;
    cursor: pointer;
    white-space: nowrap;
}
.rd-toggle-btn.active {
    background: var(--rd-navy);
    color: #ffffff;
    border-color: var(--rd-navy);
}

/* 3. Khung 3 Form Thiết lập (Admin) đồng bộ màu Navy & Gold */
.rd-setup-forms-grid {
    display: grid;
    grid-template-columns: 280px 1fr 1.3fr;
    gap: 16px;
    margin-bottom: 22px;
}
@media (max-width: 1150px) {
    .rd-setup-forms-grid { grid-template-columns: 1fr; }
}
.rd-box-card {
    background: var(--rd-card);
    border: 1px solid var(--rd-border);
    border-radius: 16px;
    padding: 18px 20px;
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.03);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.rd-box-head {
    font-size: 13.5px;
    font-weight: 800;
    color: var(--rd-navy);
    padding-bottom: 11px;
    margin-bottom: 14px;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.rd-label {
    display: block;
    font-size: 11.5px;
    font-weight: 700;
    color: #475569;
    margin-bottom: 5px;
}
.rd-btn-navy {
    width: 100%;
    padding: 9px 14px;
    border-radius: 9px;
    background: var(--rd-navy);
    color: var(--rd-gold-light);
    font-size: 12.5px;
    font-weight: 800;
    border: 1px solid var(--rd-navy);
    cursor: pointer;
    transition: all 0.15s ease;
}
.rd-btn-navy:hover {
    background: var(--rd-navy-dark);
    color: #ffffff;
}
.rd-btn-outline-gold {
    width: 100%;
    padding: 9px 14px;
    border-radius: 9px;
    background: #fffbeb;
    color: var(--rd-amber-text);
    font-size: 12.5px;
    font-weight: 800;
    border: 1px solid #fde68a;
    cursor: pointer;
    transition: all 0.15s ease;
}
.rd-btn-outline-gold:hover {
    background: var(--rd-gold);
    color: var(--rd-navy);
    border-color: var(--rd-gold);
}
.rd-btn-emerald {
    width: 100%;
    padding: 9px 14px;
    border-radius: 9px;
    background: var(--rd-green-bg);
    color: var(--rd-green-text);
    font-size: 12.5px;
    font-weight: 800;
    border: 1px solid #86efac;
    cursor: pointer;
    transition: all 0.15s ease;
}
.rd-btn-emerald:hover {
    background: var(--rd-green-text);
    color: #ffffff;
}

/* 4. Bố cục chính 2 cột: Trái (Card Grid / Bảng chi tiết) - Phải (KPI Sidebar giống Thực đơn) */
.rd-main-layout {
    display: grid;
    grid-template-columns: 1fr 310px;
    gap: 18px;
    align-items: start;
}
@media (max-width: 1200px) {
    .rd-main-layout { grid-template-columns: 1fr; }
}

/* Chế độ 1: Lưới Thẻ Bàn & Phòng (Giống hệt Thẻ Món Ăn) */
.rd-cards-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 28px 16px;
    padding-top: 22px;
}
@media (max-width: 992px) {
    .rd-cards-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 640px) {
    .rd-cards-grid { grid-template-columns: 1fr; }
}
.rd-item-card {
    background: var(--rd-card);
    border: 1px solid var(--rd-border);
    border-radius: 16px;
    padding: 42px 18px 18px;
    position: relative;
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
    transition: transform 0.18s ease, box-shadow 0.18s ease;
}
.rd-item-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
    border-color: #cbd5e1;
}
.rd-circle-badge {
    width: 68px;
    height: 68px;
    border-radius: 50%;
    background: var(--rd-navy);
    border: 3px solid #ffffff;
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.18);
    position: absolute;
    top: -24px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 4px;
}
.rd-circle-badge .code {
    font-size: 11px;
    font-weight: 800;
    color: var(--rd-gold-light);
    line-height: 1.1;
}
.rd-circle-badge .unit {
    font-size: 10px;
    color: #cbd5e1;
    margin-top: 2px;
}
.rd-card-top-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: absolute;
    top: 12px;
    left: 16px;
    right: 16px;
}
.rd-meta-code {
    font-size: 11px;
    font-weight: 700;
    color: #94a3b8;
}
.rd-status-pill {
    font-size: 10.5px;
    font-weight: 800;
    padding: 3px 10px;
    border-radius: 20px;
}
.pill-available { background: var(--rd-green-bg); color: var(--rd-green-text); border: 1px solid #bbf7d0; }
.pill-occupied  { background: var(--rd-amber-bg); color: var(--rd-amber-text); border: 1px solid #fde68a; }
.pill-reserved  { background: var(--rd-blue-bg);  color: var(--rd-blue-text);  border: 1px solid #c7d2fe; }
.pill-cleaning  { background: var(--rd-red-bg);   color: var(--rd-red-text);   border: 1px solid #fecaca; }

.rd-card-title {
    text-align: center;
    font-size: 15.5px;
    font-weight: 800;
    color: var(--rd-navy);
    margin: 12px 0 3px;
}
.rd-card-sub {
    text-align: center;
    font-size: 12px;
    color: var(--rd-muted);
    margin-bottom: 12px;
}
.rd-card-metrics {
    background: #f8fafc;
    border: 1px solid #f1f5f9;
    border-radius: 12px;
    padding: 10px 12px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    text-align: center;
    margin-bottom: 12px;
}
.rd-card-metrics .m-label {
    font-size: 10px;
    font-weight: 700;
    color: #94a3b8;
    text-transform: uppercase;
}
.rd-card-metrics .m-val {
    font-size: 14px;
    font-weight: 800;
    color: var(--rd-navy);
    margin-top: 2px;
}
.rd-card-info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 11.5px;
    margin-bottom: 8px;
}
.rd-round-actions {
    display: flex;
    justify-content: space-around;
    align-items: center;
    padding-top: 12px;
    margin-top: 10px;
    border-top: 1px solid #f1f5f9;
}
.rd-act-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    background: none;
    border: none;
    cursor: pointer;
    padding: 0;
}
.rd-act-circle {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    font-size: 10px;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.15s ease;
}
.rd-act-item:hover .rd-act-circle {
    transform: scale(1.08);
}
.rd-act-label {
    font-size: 10.5px;
    color: var(--rd-muted);
    font-weight: 600;
}

/* Chế độ 2: Bảng chi tiết (Table View) đồng bộ màu Thực đơn */
.rd-table-card {
    background: var(--rd-card);
    border: 1px solid var(--rd-border);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
}
.rd-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}
.rd-table thead th {
    background: #f8fafc;
    color: #64748b;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    padding: 13px 14px;
    border-bottom: 1px solid var(--rd-border);
    white-space: nowrap;
}
.rd-table tbody td {
    padding: 12px 14px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}
.rd-table tbody tr:hover {
    background: #fcfcfd;
}
.rd-floor-badge {
    display: inline-block;
    background: var(--rd-navy);
    color: var(--rd-gold-light);
    font-size: 10.5px;
    font-weight: 800;
    padding: 2px 8px;
    border-radius: 6px;
}
.rd-mini-input, .rd-mini-select {
    padding: 5px 8px;
    font-size: 12px;
    border: 1px solid #cbd5e1;
    border-radius: 7px;
    background: #ffffff;
    color: var(--rd-text);
}
.rd-btn-sm-save {
    padding: 5px 12px;
    border-radius: 7px;
    font-size: 12px;
    font-weight: 800;
    background: var(--rd-navy);
    color: var(--rd-gold-light);
    border: 1px solid var(--rd-navy);
    cursor: pointer;
}
.rd-btn-sm-save:hover {
    background: var(--rd-gold);
    color: var(--rd-navy);
    border-color: var(--rd-gold);
}
.rd-btn-sm-del {
    padding: 5px 10px;
    border-radius: 7px;
    font-size: 12px;
    font-weight: 700;
    background: var(--rd-red-bg);
    color: var(--rd-red-text);
    border: 1px solid #fecaca;
    cursor: pointer;
}
.rd-btn-sm-del:hover {
    background: var(--rd-red-text);
    color: #ffffff;
}

/* Cột phải: Thẻ KPI Navy & Thống kê độ phủ (Giống hệt cột phải trang Thực đơn) */
.rd-side-stack {
    display: flex;
    flex-direction: column;
    gap: 16px;
}
.rd-navy-kpi {
    background: var(--rd-navy);
    color: #ffffff;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 8px 24px rgba(23, 36, 59, 0.18);
}
.rd-navy-kpi .kpi-label {
    font-size: 11px;
    font-weight: 800;
    color: #cbd5e1;
    text-transform: uppercase;
    letter-spacing: 0.4px;
}
.rd-navy-kpi .kpi-main {
    font-size: 28px;
    font-weight: 800;
    color: var(--rd-gold-light);
    margin: 6px 0 12px;
}
.rd-navy-kpi .kpi-foot {
    border-top: 1px solid rgba(255, 255, 255, 0.12);
    padding-top: 10px;
    display: flex;
    justify-content: space-between;
    font-size: 11.5px;
    color: #cbd5e1;
}
.rd-side-white {
    background: var(--rd-card);
    border: 1px solid var(--rd-border);
    border-radius: 16px;
    padding: 18px 20px;
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.03);
}
.rd-progress-track {
    height: 8px;
    background: #f1f5f9;
    border-radius: 99px;
    overflow: hidden;
    margin: 10px 0 14px;
}
.rd-progress-fill {
    height: 100%;
    background: var(--rd-green-text);
    border-radius: 99px;
}
.rd-stat-line {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    color: var(--rd-muted);
    margin-bottom: 8px;
}
.rd-stat-line strong {
    color: var(--rd-navy);
}

/* Phân trang đồng bộ Navy & Gold */
.rd-pagination {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 6px;
    padding: 14px 18px;
    background: #ffffff;
    border-top: 1px solid var(--rd-border);
}
.rd-page-btn {
    padding: 5px 11px;
    border-radius: 7px;
    font-size: 12px;
    font-weight: 700;
    border: 1px solid var(--rd-border);
    background: #ffffff;
    color: var(--rd-text);
    text-decoration: none;
}
.rd-page-btn.active {
    background: var(--rd-navy);
    color: var(--rd-gold-light);
    border-color: var(--rd-navy);
}
.rd-page-btn.disabled {
    opacity: 0.45;
    pointer-events: none;
}
</style>

<div class="rd-setup-page">

    <!-- 1. THANH DANH MỤC TẦNG & KHU VỰC (ĐỒNG BỘ VỚI DANH MỤC THỰC ĐƠN) -->
    <div class="rd-pill-bar">
        <div class="rd-pill-group" id="floorPillGroup">
            <span class="rd-bar-title">DANH MỤC KHU VỰC & BÀN</span>
            <span class="rd-count-chip"><?= count($areas) ?> khu vực</span>
            <button type="button" class="rd-cat-pill active" data-floor="ALL" onclick="filterByFloorPill('ALL', this)">Tất cả</button>
            <?php foreach ($floorsSummary as $fl): ?>
                <?php $fNum = (int)$fl['floor_number']; ?>
                <button type="button" class="rd-cat-pill" data-floor="<?= $fNum ?>" onclick="filterByFloorPill('<?= $fNum ?>', this)">
                    Tầng <?= $fNum ?>
                </button>
            <?php endforeach; ?>
        </div>
        <div class="rd-pill-group">
            <a href="index.php?c=booking&a=index" class="rd-cat-pill">← Quay lại Sơ đồ bàn</a>
            <a href="index.php?c=booking&a=vip" class="rd-cat-pill active">Quản lý Đặt bàn VIP</a>
        </div>
    </div>

    <!-- 2. THANH TÌM KIẾM, BỘ LỌC & CHUYỂN ĐỔI THẺ / BẢNG CHI TIẾT -->
    <div class="rd-filter-bar">
        <input type="text" id="setupSearchInput" class="rd-input" placeholder="Tìm theo mã bàn, tên bàn hoặc khu vực..." oninput="applySetupFilters()">

        <select id="filterSpaceType" class="rd-select" onchange="applySetupFilters()">
            <option value="ALL">Loại không gian: Tất cả</option>
            <option value="Standard">Bàn đặt tiêu chuẩn</option>
            <option value="VIP">Phòng VIP (Cọc 5Tr)</option>
            <option value="Premium">Phòng Thượng Hạng (Cọc 10Tr)</option>
        </select>

        <select id="filterStatus" class="rd-select" onchange="applySetupFilters()">
            <option value="ALL">Trạng thái vận hành: Tất cả</option>
            <option value="Available">Bàn trống (Sẵn sàng)</option>
            <option value="Occupied">Đang phục vụ</option>
            <option value="Reserved">Đã đặt trước</option>
            <option value="Cleaning">Đang dọn bàn</option>
        </select>

        <select id="filterCapacity" class="rd-select" onchange="applySetupFilters()">
            <option value="ALL">Sức chứa: Tất cả</option>
            <option value="4">Tiêu chuẩn 4 ghế</option>
            <option value="8">Bàn lớn 8 ghế</option>
            <option value="12">Đại tiệc 12 ghế</option>
        </select>

        <div class="rd-view-toggle">
            <button type="button" id="btnViewCards" class="rd-toggle-btn" onclick="switchSetupView('cards')">Thẻ bàn & phòng</button>
            <button type="button" id="btnViewTable" class="rd-toggle-btn active" onclick="switchSetupView('table')">Bảng chi tiết</button>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger fw-semibold rounded-3 mb-3"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($flashSuccess !== '' || isset($_GET['success'])): ?>
        <div class="alert alert-success fw-semibold rounded-3 mb-3"><?= htmlspecialchars($flashSuccess ?: 'Đã lưu thay đổi thành công!') ?></div>
    <?php endif; ?>

    <!-- 3. KHỐI 3 FORM THIẾT LẬP NHANH (ADMIN) ĐỒNG BỘ MÀU NAVY - GOLD - EMERALD -->
    <?php if ($isAdmin): ?>
        <div class="rd-setup-forms-grid">
            <div class="rd-box-card">
                <div>
                    <div class="rd-box-head">
                        <span>1. Quản lý Số Tầng (ADMIN)</span>
                        <span class="rd-status-pill pill-reserved"><?= (int)$totalFloorsCount ?> tầng</span>
                    </div>
                    <form method="POST" action="index.php?c=booking&a=manage">
                        <input type="hidden" name="action" value="floor_sync">
                        <label class="rd-label">Tổng số tầng nhà hàng</label>
                        <input type="number" name="total_floors" class="rd-input mb-3"
                               min="1" max="20" value="<?= (int)$totalFloorsCount ?>" required>
                        <button type="submit" class="rd-btn-navy">Lưu Số Tầng</button>
                    </form>
                </div>
            </div>

            <div class="rd-box-card">
                <div>
                    <div class="rd-box-head">
                        <span>2. Thêm Khu Vực / Sảnh Mới</span>
                        <span class="rd-status-pill pill-occupied">Phụ thu VIP</span>
                    </div>
                    <form method="POST" action="index.php?c=booking&a=manage" class="row g-2">
                        <input type="hidden" name="action" value="area_add">
                        <div class="col-4">
                            <label class="rd-label">Tầng</label>
                            <input type="number" name="floor_number" class="rd-input" min="1" max="20" value="1" required>
                        </div>
                        <div class="col-8">
                            <label class="rd-label">Phụ thu (%)</label>
                            <input type="number" step="0.5" min="0" max="100" name="surcharge_percent" class="rd-input" value="0" required>
                        </div>
                        <div class="col-12">
                            <label class="rd-label">Tên Khu Vực / Sảnh</label>
                            <input type="text" name="name" class="rd-input" placeholder="VD: Sảnh Giao Hưởng" required>
                        </div>
                        <div class="col-12 mt-2">
                            <button type="submit" class="rd-btn-outline-gold">+ Thêm Khu Vực</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="rd-box-card">
                <div>
                    <div class="rd-box-head">
                        <span>3. Tạo Label Bàn Đặt / Phòng Riêng</span>
                        <span class="rd-status-pill pill-available">Đồng bộ POS/QR</span>
                    </div>
                    <form method="POST" action="index.php?c=booking&a=manage" class="row g-2">
                        <input type="hidden" name="action" value="add">
                        <div class="col-md-6">
                            <label class="rd-label">Khu vực (Tầng)</label>
                            <select name="area_id" class="rd-select" required>
                                <?php foreach ($areas as $a): ?>
                                    <option value="<?= (int)$a['id'] ?>">
                                        [Tầng <?= (int)$a['floor_number'] ?>] <?= htmlspecialchars($a['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="rd-label">Tên Label</label>
                            <input type="text" name="table_name" class="rd-input" placeholder="VD: B01, VIP 01..." required>
                        </div>
                        <div class="col-md-7">
                            <label class="rd-label">Loại không gian</label>
                            <select name="table_type" class="rd-select" required>
                                <option value="Standard">Bàn Đặt Tiêu Chuẩn</option>
                                <option value="VIP">Phòng VIP (Cọc 5.000.000đ)</option>
                                <option value="Premium">Phòng Thượng Hạng (Cọc 10.000.000đ)</option>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="rd-label">Số ghế chuẩn</label>
                            <select name="capacity" class="rd-select" required>
                                <option value="4">4 ghế</option>
                                <option value="8">8 ghế</option>
                                <option value="12">12 ghế</option>
                            </select>
                        </div>
                        <div class="col-12 mt-2">
                            <button type="submit" class="rd-btn-emerald">+ Tạo Label Bàn / Phòng</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- 4. BỐ CỤC CHÍNH: DANH SÁCH (THẺ / BẢNG) BÊN TRÁI + SIDEBAR KPI BÊN PHẢI -->
    <div class="rd-main-layout">

        <!-- CỘT TRÁI: CHỨA CẢ CHẾ ĐỘ THẺ CARD VÀ CHẾ ĐỘ BẢNG CHI TIẾT -->
        <div>
            <!-- CHẾ ĐỘ A: THẺ BÀN & PHÒNG (GIỐNG HỆT CARD THỰC ĐƠN) -->
            <div id="setupCardsContainer" class="rd-cards-grid" style="display: none;">
                <?php foreach ($tables as $row): ?>
                    <?php
                    $tId     = (int)$row['table_id'];
                    $tType   = $row['table_type'] ?? 'Standard';
                    $tStatus = $row['status'] ?? 'Available';
                    $fNum    = (int)($row['floor_number'] ?? 1);
                    $cap     = (int)($row['capacity'] ?? 4);

                    $stPillClass = match ($tStatus) {
                        'Occupied' => 'pill-occupied',
                        'Reserved' => 'pill-reserved',
                        'Cleaning' => 'pill-cleaning',
                        default    => 'pill-available'
                    };
                    $stLabel = match ($tStatus) {
                        'Occupied' => 'Đang phục vụ',
                        'Reserved' => 'Đã đặt trước',
                        'Cleaning' => 'Đang dọn bàn',
                        default    => 'Bàn trống'
                    };
                    $depositRuleText = match ($tType) {
                        'Premium' => '10.000.000đ',
                        'VIP'     => '5.000.000đ',
                        default   => '50.000đ - 200.000đ'
                    };
                    $typeLabel = match ($tType) {
                        'Premium' => 'Phòng Thượng Hạng',
                        'VIP'     => 'Phòng VIP riêng tư',
                        default   => 'Bàn đặt tiêu chuẩn'
                    };
                    $searchText = mb_strtolower($row['table_name'] . ' ' . $row['area_name'] . ' ' . $typeLabel, 'UTF-8');
                    ?>
                    <div class="rd-item-card setup-filter-item"
                         data-floor="<?= $fNum ?>"
                         data-type="<?= htmlspecialchars($tType) ?>"
                         data-status="<?= htmlspecialchars($tStatus) ?>"
                         data-capacity="<?= $cap ?>"
                         data-search="<?= htmlspecialchars($searchText) ?>">

                        <div class="rd-circle-badge">
                            <span class="code"><?= htmlspecialchars($row['table_name']) ?></span>
                            <span class="unit"><?= $cap ?> ghế</span>
                        </div>

                        <div class="rd-card-top-meta">
                            <span class="rd-meta-code">#<?= $tId ?> · Tầng <?= $fNum ?></span>
                            <span class="rd-status-pill <?= $stPillClass ?>"><?= $stLabel ?></span>
                        </div>

                        <div class="rd-card-title"><?= htmlspecialchars($row['table_name']) ?></div>
                        <div class="rd-card-sub"><?= htmlspecialchars($row['area_name']) ?> · ĐVT: <?= $cap ?> khách</div>

                        <div class="rd-card-metrics">
                            <div style="border-right: 1px solid #e2e8f0;">
                                <div class="m-label">Sức chứa chuẩn</div>
                                <div class="m-val"><?= $cap ?> ghế</div>
                            </div>
                            <div>
                                <div class="m-label">Định mức cọc</div>
                                <div class="m-val" style="font-size: 12.5px; color: var(--rd-amber-text);"><?= $depositRuleText ?></div>
                            </div>
                        </div>

                        <div class="rd-card-info-row">
                            <span style="font-weight: 700; color: #475569;">Phân hạng không gian:</span>
                            <span class="rd-status-pill <?= $tType === 'Standard' ? 'pill-available' : 'pill-occupied' ?>">
                                <?= $typeLabel ?>
                            </span>
                        </div>

                        <!-- Form đổi nhanh trạng thái ngay trên Card -->
                        <form method="POST" action="index.php?c=booking&a=manage" class="mt-2">
                            <input type="hidden" name="action" value="status">
                            <input type="hidden" name="id" value="<?= $tId ?>">
                            <div class="d-flex gap-1">
                                <select name="status" class="rd-mini-select w-100">
                                    <?php foreach (['Available' => 'Bàn trống', 'Reserved' => 'Đã đặt trước', 'Occupied' => 'Đang phục vụ', 'Cleaning' => 'Đang dọn bàn'] as $stKey => $stVal): ?>
                                        <option value="<?= $stKey ?>" <?= $tStatus === $stKey ? 'selected' : '' ?>><?= $stVal ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="rd-btn-sm-save">Đổi</button>
                            </div>
                        </form>

                        <?php if ($isAdmin): ?>
                            <div class="rd-round-actions">
                                <button type="button" class="rd-act-item" onclick="switchSetupView('table')">
                                    <span class="rd-act-circle" style="background: var(--rd-amber-bg); color: var(--rd-amber-text);">SỬA</span>
                                    <span class="rd-act-label">Cập nhật</span>
                                </button>
                                <form method="POST" action="index.php?c=booking&a=manage" class="m-0" onsubmit="return confirm('Xóa bàn/phòng này?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $tId ?>">
                                    <button type="submit" class="rd-act-item">
                                        <span class="rd-act-circle" style="background: var(--rd-red-bg); color: var(--rd-red-text);">XÓA</span>
                                        <span class="rd-act-label">Xóa bàn</span>
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- CHẾ ĐỘ B: BẢNG CHI TIẾT (MẶC ĐỊNH) ĐỒNG BỘ MÀU THỰC ĐƠN -->
            <div id="setupTableContainer" class="rd-table-card">
                <div class="table-responsive">
                    <table class="rd-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tầng & Khu vực</th>
                                <th>Tên Label</th>
                                <th>Loại không gian & Rule Cọc</th>
                                <th>Số ghế</th>
                                <th>Trạng thái vận hành</th>
                                <?php if ($isAdmin): ?>
                                    <th class="text-end">Thao tác</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tables as $row): ?>
                                <?php
                                $tId     = (int)$row['table_id'];
                                $tType   = $row['table_type'] ?? 'Standard';
                                $tStatus = $row['status'] ?? 'Available';
                                $fNum    = (int)($row['floor_number'] ?? 1);
                                $cap     = (int)($row['capacity'] ?? 4);
                                $searchText = mb_strtolower($row['table_name'] . ' ' . $row['area_name'] . ' ' . $tType, 'UTF-8');
                                ?>
                                <tr class="setup-filter-item"
                                    data-floor="<?= $fNum ?>"
                                    data-type="<?= htmlspecialchars($tType) ?>"
                                    data-status="<?= htmlspecialchars($tStatus) ?>"
                                    data-capacity="<?= $cap ?>"
                                    data-search="<?= htmlspecialchars($searchText) ?>">
                                    <td class="fw-bold" style="color: #64748b;">#<?= $tId ?></td>
                                    <td>
                                        <span class="rd-floor-badge">Tầng <?= $fNum ?></span>
                                        <div class="small fw-bold mt-1" style="color: var(--rd-navy);"><?= htmlspecialchars($row['area_name']) ?></div>
                                    </td>
                                    <td class="fw-bold" style="color: var(--rd-navy); font-size: 14px;"><?= htmlspecialchars($row['table_name']) ?></td>
                                    <td>
                                        <?php if ($tType === 'Premium'): ?>
                                            <span class="rd-status-pill pill-occupied">Phòng Thượng Hạng (Cọc 10Tr)</span>
                                        <?php elseif ($tType === 'VIP'): ?>
                                            <span class="rd-status-pill pill-reserved">Phòng VIP (Cọc 5Tr)</span>
                                        <?php else: ?>
                                            <span class="rd-status-pill pill-available">Bàn đặt tiêu chuẩn</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong style="color: var(--rd-navy);"><?= $cap ?> ghế</strong></td>
                                    <td>
                                        <form method="POST" action="index.php?c=booking&a=manage" class="d-flex gap-1 align-items-center m-0">
                                            <input type="hidden" name="action" value="status">
                                            <input type="hidden" name="id" value="<?= $tId ?>">
                                            <select name="status" class="rd-mini-select" style="width: 128px;">
                                                <?php foreach (['Available' => 'Bàn trống', 'Reserved' => 'Đã đặt trước', 'Occupied' => 'Đang phục vụ', 'Cleaning' => 'Đang dọn bàn'] as $stKey => $stLabel): ?>
                                                    <option value="<?= $stKey ?>" <?= $tStatus === $stKey ? 'selected' : '' ?>>
                                                        <?= $stLabel ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="rd-cat-pill" style="padding: 4px 10px; font-size: 11.5px;">Đổi</button>
                                        </form>
                                    </td>
                                    <?php if ($isAdmin): ?>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-1 align-items-center flex-wrap justify-content-end">
                                                <form method="POST" action="index.php?c=booking&a=manage" class="d-inline-flex gap-1 align-items-center m-0">
                                                    <input type="hidden" name="action" value="update">
                                                    <input type="hidden" name="id" value="<?= $tId ?>">
                                                    <select name="area_id" class="rd-mini-select" style="width: 135px;">
                                                        <?php foreach ($areas as $a): ?>
                                                            <option value="<?= (int)$a['id'] ?>" <?= (int)$a['id'] === (int)$row['area_id'] ? 'selected' : '' ?>>
                                                                T<?= (int)$a['floor_number'] ?> - <?= htmlspecialchars($a['name']) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <input type="text" name="table_name" class="rd-mini-input"
                                                           style="width: 82px;" value="<?= htmlspecialchars($row['table_name']) ?>" required>
                                                    <select name="table_type" class="rd-mini-select" style="width: 112px;">
                                                        <option value="Standard" <?= $tType === 'Standard' ? 'selected' : '' ?>>Bàn thường</option>
                                                        <option value="VIP" <?= $tType === 'VIP' ? 'selected' : '' ?>>VIP (5Tr)</option>
                                                        <option value="Premium" <?= $tType === 'Premium' ? 'selected' : '' ?>>President (10Tr)</option>
                                                    </select>
                                                    <select name="capacity" class="rd-mini-select" style="width: 74px;">
                                                        <?php foreach ([4, 8, 12] as $capOpt): ?>
                                                            <option value="<?= $capOpt ?>" <?= $cap === $capOpt ? 'selected' : '' ?>>
                                                                <?= $capOpt ?> ghế
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <button type="submit" class="rd-btn-sm-save">Lưu</button>
                                                </form>
                                                <form method="POST" action="index.php?c=booking&a=manage" class="d-inline m-0"
                                                      onsubmit="return confirm('Xóa bàn/phòng này?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= $tId ?>">
                                                    <button type="submit" class="rd-btn-sm-del">Xóa</button>
                                                </form>
                                            </div>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($maxPage_tbl >= 1): ?>
                    <div class="rd-pagination">
                        <a class="rd-page-btn <?= ($page_tbl <= 1) ? 'disabled' : '' ?>"
                           href="?<?= $queryString ?>&page_tbl=<?= $page_tbl - 1 ?>">Trước</a>
                        <?php for ($i = 1; $i <= $maxPage_tbl; $i++): ?>
                            <a class="rd-page-btn <?= ($i == $page_tbl) ? 'active' : '' ?>"
                               href="?<?= $queryString ?>&page_tbl=<?= $i ?>"><?= $i ?></a>
                        <?php endfor; ?>
                        <a class="rd-page-btn <?= ($page_tbl >= $maxPage_tbl) ? 'disabled' : '' ?>"
                           href="?<?= $queryString ?>&page_tbl=<?= $page_tbl + 1 ?>">Sau</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- CỘT PHẢI: SIDEBAR KPI NAVY & ĐỘ PHỦ KHÔNG GIAN (GIỐNG HỆT CỘT PHẢI TRANG THỰC ĐƠN) -->
        <div class="rd-side-stack">
            <div class="rd-navy-kpi">
                <div class="kpi-label">TỔNG SỨC CHỨA TRANG HIỆN TẠI</div>
                <div class="kpi-main"><?= $totalCapacity ?> ghế</div>
                <div class="kpi-foot">
                    <span>Tổng số bàn: <strong><?= $total_tbl ?></strong></span>
                    <span>Đang trống: <strong><?= $countEmpty ?></strong></span>
                    <span>Đang bận: <strong><?= $countBusy ?></strong></span>
                </div>
            </div>

            <div class="rd-side-white">
                <div class="d-flex justify-content-between align-items-center">
                    <span style="font-size: 11.5px; font-weight: 800; color: #64748b; text-transform: uppercase;">TỶ LỆ BÀN SẴN SÀNG ĐÓN KHÁCH</span>
                    <span class="rd-status-pill pill-available"><?= $countEmpty ?> / <?= count($tables) ?> bàn</span>
                </div>
                <div style="font-size: 24px; font-weight: 800; color: var(--rd-navy); margin-top: 6px;">
                    <?= $readyRatio ?>%
                </div>
                <div class="rd-progress-track">
                    <div class="rd-progress-fill" style="width: <?= $readyRatio ?>%;"></div>
                </div>
                <div class="rd-stat-line">
                    <span>Bàn đặt tiêu chuẩn</span>
                    <strong style="color: var(--rd-green-text);"><?= $countStandard ?> bàn</strong>
                </div>
                <div class="rd-stat-line">
                    <span>Phòng VIP (Cọc 5.000.000đ)</span>
                    <strong style="color: var(--rd-amber-text);"><?= $countVip ?> phòng</strong>
                </div>
                <div class="rd-stat-line mb-0">
                    <span>Phòng Thượng Hạng (Cọc 10.000.000đ)</span>
                    <strong><?= $countPremium ?> phòng</strong>
                </div>
            </div>

            <div class="rd-side-white">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span style="font-size: 11.5px; font-weight: 800; color: #64748b; text-transform: uppercase;">CẤU HÌNH PHÒNG VIP & QR POS</span>
                    <span class="rd-status-pill pill-available">Đồng bộ POS/KDS</span>
                </div>
                <div style="font-size: 22px; font-weight: 800; color: var(--rd-navy); margin-bottom: 10px;">
                    <?= $vipRatio ?>%
                </div>
                <div class="rd-stat-line">
                    <span>Trung bình ghế / bàn</span>
                    <strong><?= $avgCapacity ?> ghế</strong>
                </div>
                <div class="rd-stat-line">
                    <span>Tự động áp phụ thu khu vực</span>
                    <strong>Đã kích hoạt</strong>
                </div>
                <div class="rd-stat-line mb-0">
                    <span>Khấu trừ cọc vào bill POS</span>
                    <strong>Đã kích hoạt</strong>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
let currentFloorPill = 'ALL';

function switchSetupView(mode) {
    const cardsEl = document.getElementById('setupCardsContainer');
    const tableEl = document.getElementById('setupTableContainer');
    const btnCards = document.getElementById('btnViewCards');
    const btnTable = document.getElementById('btnViewTable');

    if (mode === 'cards') {
        cardsEl.style.display = 'grid';
        tableEl.style.display = 'none';
        btnCards.classList.add('active');
        btnTable.classList.remove('active');
    } else {
        cardsEl.style.display = 'none';
        tableEl.style.display = 'block';
        btnTable.classList.add('active');
        btnCards.classList.remove('active');
    }
}

function filterByFloorPill(floorVal, btnEl) {
    currentFloorPill = String(floorVal);
    document.querySelectorAll('#floorPillGroup .rd-cat-pill').forEach(b => b.classList.remove('active'));
    if (btnEl) btnEl.classList.add('active');
    applySetupFilters();
}

function applySetupFilters() {
    const kw     = (document.getElementById('setupSearchInput')?.value || '').trim().toLowerCase();
    const type   = document.getElementById('filterSpaceType')?.value || 'ALL';
    const status = document.getElementById('filterStatus')?.value || 'ALL';
    const cap    = document.getElementById('filterCapacity')?.value || 'ALL';

    document.querySelectorAll('.setup-filter-item').forEach(el => {
        const elFloor  = el.getAttribute('data-floor') || '';
        const elType   = el.getAttribute('data-type') || '';
        const elStatus = el.getAttribute('data-status') || '';
        const elCap    = el.getAttribute('data-capacity') || '';
        const elSearch = el.getAttribute('data-search') || '';

        const matchFloor  = (currentFloorPill === 'ALL') || (elFloor === currentFloorPill);
        const matchType   = (type === 'ALL') || (elType === type);
        const matchStatus = (status === 'ALL') || (elStatus === status);
        const matchCap    = (cap === 'ALL') || (elCap === cap);
        const matchKw     = (kw === '') || elSearch.includes(kw);

        el.style.display = (matchFloor && matchType && matchStatus && matchCap && matchKw) ? '' : 'none';
    });
}
</script>