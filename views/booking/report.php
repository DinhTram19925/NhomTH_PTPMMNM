<?php
$reservations = $reservations ?? [];
$bookingStats = $bookingStats ?? [
    'total_bookings'     => count($reservations),
    'pending'            => 0,
    'confirmed'          => 0,
    'checked_in'         => 0,
    'cancelled'          => 0,
    'checked_in_deposit' => 0,
    'cancelled_deposit'  => 0
];
$tables = $tables ?? [];
$allTables = $allTables ?? [];
$floorsSummary = $floorsSummary ?? [];
$areas = $areas ?? [];
$statistics = $statistics ?? ['total' => 0, 'available' => 0, 'occupied' => 0, 'reserved' => 0, 'cleaning' => 0];
$reservedTableIds = $reservedTableIds ?? [];
$kdsMap = $kdsMap ?? [];
$error = $error ?? '';
$success = $success ?? '';

$upcomingList = [];
$pendingList = [];
$seatedList = [];
$activeReservationByTable = [];

foreach ($reservations as &$res) {
    $st = $res['status'] ?? 'Pending';
    $tId = (int)($res['table_id'] ?? 0);

    $kds = ($tId > 0 && isset($kdsMap[$tId])) ? $kdsMap[$tId] : null;
    $totalDishes   = (int)($kds['total_dishes'] ?? $res['total_dishes'] ?? 0);
    $waitingDishes = (int)($kds['waiting_dishes'] ?? $res['waiting_dishes'] ?? 0);
    $cookingDishes = (int)($kds['cooking_dishes'] ?? $res['cooking_dishes'] ?? 0);
    $readyDishes   = (int)($kds['ready_dishes'] ?? $res['ready_dishes'] ?? 0);
    $servedDishes  = (int)($kds['served_dishes'] ?? $res['served_dishes'] ?? 0);

    $progressPercent = 15;
    $progressText = 'Chờ xác nhận giữ bàn';
    $progressColor = 'amber';

    if ($st === 'Confirmed') {
        $progressPercent = 35;
        $progressText = 'Đã xếp bàn · Chờ khách đến';
        $progressColor = 'blue';
    } elseif ($st === 'CheckedIn') {
        if ($totalDishes <= 0) {
            $progressPercent = 45;
            $progressText = 'Đã mở POS · Chờ gọi món';
            $progressColor = 'gold';
        } elseif ($servedDishes >= $totalDishes) {
            $progressPercent = 100;
            $progressText = 'Đã lên đủ ' . $servedDishes . ' món';
            $progressColor = 'green';
        } elseif ($readyDishes > 0) {
            $progressPercent = 85;
            $progressText = 'Chờ lên ' . $readyDishes . ' món · Đã ra ' . $servedDishes . ' trên ' . $totalDishes . ' món';
            $progressColor = 'green';
        } elseif ($cookingDishes > 0) {
            $progressPercent = 68;
            $progressText = 'Bếp đang nấu ' . $cookingDishes . ' món · Đã ra ' . $servedDishes . ' trên ' . $totalDishes . ' món';
            $progressColor = 'gold';
        } else {
            $progressPercent = 55;
            $progressText = 'Chờ bếp nhận ' . $waitingDishes . ' món';
            $progressColor = 'amber';
        }
    } elseif ($st === 'Cancelled') {
        $progressPercent = 0;
        $progressText = 'Đã hủy đặt bàn';
        $progressColor = 'red';
    }

    $res['progress_percent'] = $progressPercent;
    $res['progress_text'] = $progressText;
    $res['progress_color'] = $progressColor;

    if ($st === 'Confirmed') {
        $upcomingList[] = $res;
        if ($tId > 0 && !isset($activeReservationByTable[$tId])) {
            $activeReservationByTable[$tId] = $res;
        }
    } elseif ($st === 'Pending') {
        $pendingList[] = $res;
        if ($tId > 0 && !isset($activeReservationByTable[$tId])) {
            $activeReservationByTable[$tId] = $res;
        }
    } elseif ($st === 'CheckedIn') {
        $seatedList[] = $res;
        if ($tId > 0) {
            $activeReservationByTable[$tId] = $res;
        }
    }
}
unset($res);

$cleanLabel = function (?string $text): string {
    $str = (string)$text;
    $str = preg_replace('/\s*\([^)]*\)/u', '', $str);
    $str = str_replace(['/', '(', ')'], [' · ', '', ''], $str);
    return trim($str);
};

$tablesByArea = [];
foreach ($allTables as $tb) {
    if (empty($tb['table_id'])) continue;
    $aId = (int)$tb['area_id'];
    $flNum = (int)($tb['floor_number'] ?? 1);
    if (!isset($tablesByArea[$aId])) {
        $tablesByArea[$aId] = [
            'area_id'      => $aId,
            'floor_number' => $flNum,
            'area_name'    => $cleanLabel($tb['area_name'] ?? ''),
            'surcharge'    => (float)($tb['surcharge_percent'] ?? 0),
            'tables'       => []
        ];
    }
    $tablesByArea[$aId]['tables'][] = $tb;
}

$defaultBookingTime = date('Y-m-d\TH:i', strtotime('+2 hours'));
?>

<style>
:root {
    --rp-navy: #17243b;
    --rp-navy-dark: #0f172a;
    --rp-gold: #c69b45;
    --rp-gold-soft: #fffbeb;
    --rp-bg: #f8fafc;
    --rp-card: #ffffff;
    --rp-border: #e2e8f0;
    --rp-text: #1e293b;
    --rp-muted: #64748b;
    --rp-green: #15803d;
    --rp-green-bg: #dcfce7;
    --rp-blue: #1d4ed8;
    --rp-blue-bg: #dbeafe;
    --rp-amber: #b45309;
    --rp-amber-bg: #fef3c7;
    --rp-red: #b91c1c;
    --rp-red-bg: #fee2e2;
}

.rp-page {
    font-family: 'Be Vietnam Pro', sans-serif;
    background: var(--rp-bg);
    color: var(--rp-text);
    padding: 14px 20px 40px;
}

.rp-flash {
    padding: 10px 16px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 700;
    margin-bottom: 14px;
}
.rp-flash-ok { background: var(--rp-green-bg); color: var(--rp-green); border: 1px solid #86efac; }
.rp-flash-err { background: var(--rp-red-bg); color: var(--rp-red); border: 1px solid #fca5a5; }

/* 4 KPI Cards Row */
.rp-kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
    margin-bottom: 16px;
}
@media (max-width: 992px) {
    .rp-kpi-grid { grid-template-columns: repeat(2, 1fr); }
}
.rp-kpi-card {
    background: var(--rp-card);
    border: 1px solid var(--rp-border);
    border-radius: 12px;
    padding: 16px 18px;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.03);
}
.rp-kpi-label {
    font-size: 12px;
    font-weight: 700;
    color: var(--rp-muted);
    margin-bottom: 6px;
}
.rp-kpi-val {
    font-size: 24px;
    font-weight: 800;
    line-height: 1.15;
    margin-bottom: 6px;
}
.rp-kpi-sub {
    font-size: 11.5px;
    font-weight: 600;
    color: var(--rp-muted);
}

/* Main 2-Column Dispatch Layout */
.rp-dispatch-shell {
    display: grid;
    grid-template-columns: 340px 1fr;
    gap: 16px;
    align-items: start;
    margin-bottom: 20px;
}
@media (max-width: 1150px) {
    .rp-dispatch-shell { grid-template-columns: 1fr; }
}

/* Left Vertical Progress Bar */
.rp-vertical-bar {
    background: var(--rp-card);
    border: 1px solid var(--rp-border);
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.04);
    display: flex;
    flex-direction: column;
    max-height: 740px;
}
.rp-v-tabs {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    border-bottom: 1px solid var(--rp-border);
}
.rp-v-tab {
    padding: 12px 6px;
    text-align: center;
    background: transparent;
    border: none;
    border-bottom: 3px solid transparent;
    cursor: pointer;
}
.rp-v-tab.active {
    border-bottom-color: var(--rp-navy);
    background: #f8fafc;
}
.rp-v-tab-name {
    display: block;
    font-size: 12px;
    font-weight: 700;
    color: var(--rp-muted);
}
.rp-v-tab.active .rp-v-tab-name {
    color: var(--rp-navy);
}
.rp-v-tab-count {
    font-size: 18px;
    font-weight: 800;
    color: var(--rp-navy);
}
.rp-v-search {
    padding: 10px 12px;
    border-bottom: 1px solid var(--rp-border);
    background: #f8fafc;
}
.rp-input {
    width: 100%;
    padding: 9px 12px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-size: 13px;
    font-family: inherit;
    background: #ffffff;
    color: var(--rp-text);
}
.rp-input:focus {
    outline: none;
    border-color: var(--rp-gold);
}
.rp-v-list {
    overflow-y: auto;
    padding: 12px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    flex: 1;
}
.rp-v-card {
    background: #ffffff;
    border: 1px solid var(--rp-border);
    border-radius: 10px;
    padding: 12px;
    cursor: pointer;
    transition: 0.15s;
}
.rp-v-card:hover,
.rp-v-card.active {
    border-color: var(--rp-gold);
    background: var(--rp-gold-soft);
}
.rp-v-row1 {
    display: flex;
    justify-content: space-between;
    font-size: 13.5px;
    font-weight: 800;
    color: var(--rp-navy);
    margin-bottom: 4px;
}
.rp-v-row2 {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    font-weight: 600;
    color: var(--rp-muted);
    margin-bottom: 8px;
}
.rp-v-prog-head {
    display: flex;
    justify-content: space-between;
    font-size: 11px;
    font-weight: 700;
    color: var(--rp-navy);
    margin-bottom: 4px;
}
.rp-prog-bar {
    height: 6px;
    background: #e2e8f0;
    border-radius: 999px;
    overflow: hidden;
    margin-bottom: 8px;
}
.rp-prog-fill {
    height: 100%;
    border-radius: 999px;
}
.rp-prog-fill.green { background: var(--rp-green); }
.rp-prog-fill.blue  { background: var(--rp-blue); }
.rp-prog-fill.gold  { background: var(--rp-gold); }
.rp-prog-fill.amber { background: var(--rp-amber); }
.rp-prog-fill.red   { background: var(--rp-red); }

.rp-v-actions {
    display: flex;
    justify-content: flex-end;
    gap: 6px;
    padding-top: 8px;
    border-top: 1px dashed #e2e8f0;
}
.rp-mini-btn {
    padding: 5px 10px;
    border-radius: 6px;
    font-size: 11.5px;
    font-weight: 800;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    color: var(--rp-navy);
    cursor: pointer;
    text-decoration: none;
}
.rp-mini-btn.navy { background: var(--rp-navy); color: #e5bd69; border-color: var(--rp-navy); }
.rp-mini-btn.gold { background: var(--rp-gold); color: var(--rp-navy); border-color: #b38936; }
.rp-mini-btn.green { background: var(--rp-green); color: #ffffff; border-color: var(--rp-green); }
.rp-mini-btn.danger { color: var(--rp-red); border-color: #fca5a5; background: #fff5f5; }

/* Right Stage: Horizontal Bars + Floor Map */
.rp-right-stage {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.rp-hbar {
    background: var(--rp-card);
    border: 1px solid var(--rp-border);
    border-radius: 12px;
    padding: 10px 14px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
}
.rp-hgroup {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    align-items: center;
}
.rp-btn {
    padding: 8px 15px;
    border-radius: 20px;
    font-size: 12.5px;
    font-weight: 800;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    color: var(--rp-navy);
    cursor: pointer;
    text-decoration: none;
}
.rp-btn.navy { background: var(--rp-navy); color: #e5bd69; border-color: var(--rp-navy); }
.rp-btn.gold { background: var(--rp-gold); color: var(--rp-navy); border-color: #b38936; }

.rp-pill-btn {
    padding: 7px 14px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 800;
    border: 1px solid transparent;
    background: #f1f5f9;
    color: #475569;
    cursor: pointer;
}
.rp-pill-btn.active {
    background: var(--rp-blue-bg);
    color: var(--rp-blue);
    border-color: #93c5fd;
}

.rp-map-canvas {
    background: #ffffff;
    border: 1px solid var(--rp-border);
    border-radius: 14px;
    padding: 18px;
}
.rp-area-box {
    border: 5px solid #e2e8f0;
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 18px;
    background: #fcfcfd;
}
.rp-area-box:last-child { margin-bottom: 0; }
.rp-area-badge {
    display: inline-flex;
    gap: 8px;
    background: var(--rp-navy);
    color: #e5bd69;
    font-size: 11.5px;
    font-weight: 800;
    text-transform: uppercase;
    padding: 5px 12px;
    border-radius: 6px;
    margin-bottom: 14px;
}
.rp-table-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(168px, 1fr));
    gap: 18px;
}
.rp-table-node {
    display: flex;
    flex-direction: column;
    align-items: center;
    cursor: pointer;
    transition: transform 0.15s;
}
.rp-table-node:hover { transform: translateY(-3px); }
.rp-table-node.highlight-focus .rp-table-body {
    outline: 3px solid var(--rp-gold);
    box-shadow: 0 0 0 5px rgba(198, 155, 69, 0.25);
}
.rp-chairs {
    display: flex;
    gap: 6px;
    margin: 3px 0;
}
.rp-chair {
    width: 22px;
    height: 6px;
    border-radius: 4px;
    background: #cbd5e1;
}
.rp-table-body {
    width: 100%;
    min-height: 98px;
    border-radius: 10px;
    padding: 10px;
    border: 2px solid #cbd5e1;
    background: #f8fafc;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    align-items: center;
    text-align: center;
}
.rp-table-node.st-Reserved .rp-table-body { background: #fffbeb; border-color: #f59e0b; color: #92400e; }
.rp-table-node.st-Reserved .rp-chair { background: #fcd34d; }
.rp-table-node.st-Occupied .rp-table-body { background: #eff6ff; border-color: #60a5fa; color: #1e3a8a; }
.rp-table-node.st-Occupied .rp-chair { background: #93c5fd; }
.rp-table-node.st-Cleaning .rp-table-body { background: #fef2f2; border-color: #fca5a5; color: #991b1b; }

/* Full Reservation Table Card Below */
.rp-list-card {
    background: var(--rp-card);
    border: 1px solid var(--rp-border);
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(15, 23, 42, 0.03);
}
.rp-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}
.rp-table thead th {
    background: #f8fafc;
    color: var(--rp-navy);
    font-size: 11.5px;
    font-weight: 800;
    text-transform: uppercase;
    padding: 12px 14px;
    text-align: left;
    border-bottom: 1px solid var(--rp-border);
}
.rp-table tbody td {
    padding: 12px 14px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}

/* Modal */
.rp-modal-backdrop {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.58);
    z-index: 1060;
    align-items: center;
    justify-content: center;
    padding: 16px;
}
.rp-modal-backdrop.show { display: flex; }
.rp-modal-dialog {
    background: #ffffff;
    border-radius: 14px;
    width: min(680px, 100%);
    overflow: hidden;
    box-shadow: 0 20px 45px rgba(15, 23, 42, 0.25);
}
.rp-modal-header {
    background: var(--rp-navy);
    color: #e5bd69;
    padding: 14px 22px;
    font-size: 14px;
    font-weight: 800;
    text-transform: uppercase;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 2px solid var(--rp-gold);
}
.rp-modal-body { padding: 22px; }
.rp-form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
}
@media (max-width: 640px) {
    .rp-form-grid { grid-template-columns: 1fr; }
}
.rp-label {
    display: block;
    font-size: 12px;
    font-weight: 800;
    color: var(--rp-navy);
    margin-bottom: 5px;
}
</style>

<div class="rp-page">

    <?php if ($error !== ''): ?>
        <div class="rp-flash rp-flash-err"><?= htmlspecialchars($cleanLabel($error)) ?></div>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
        <div class="rp-flash rp-flash-ok"><?= htmlspecialchars($cleanLabel($success)) ?></div>
    <?php endif; ?>

    <!-- 4 THẺ BÁO CÁO ĐẶT BÀN & TIỀN ĐẶT CỌC -->
    <div class="rp-kpi-grid">
        <div class="rp-kpi-card">
            <div class="rp-kpi-label">Tổng phiếu đặt bàn</div>
            <div class="rp-kpi-val" style="color: var(--rp-navy);"><?= (int)($bookingStats['total_bookings'] ?? 0) ?></div>
            <div class="rp-kpi-sub">
                Chờ duyệt: <?= (int)($bookingStats['pending'] ?? 0) ?> · Đã xác nhận: <?= (int)($bookingStats['confirmed'] ?? 0) ?>
            </div>
        </div>
        <div class="rp-kpi-card">
            <div class="rp-kpi-label">Đã Check-in mở POS</div>
            <div class="rp-kpi-val" style="color: var(--rp-green);"><?= (int)($bookingStats['checked_in'] ?? 0) ?></div>
            <div class="rp-kpi-sub">Đã hủy: <?= (int)($bookingStats['cancelled'] ?? 0) ?></div>
        </div>
        <div class="rp-kpi-card">
            <div class="rp-kpi-label">Tiền cọc đã Check-in</div>
            <div class="rp-kpi-val" style="color: var(--rp-blue);">
                <?= number_format((float)($bookingStats['checked_in_deposit'] ?? 0), 0, ',', '.') ?>đ
            </div>
            <div class="rp-kpi-sub">Khấu trừ vào hóa đơn POS</div>
        </div>
        <div class="rp-kpi-card">
            <div class="rp-kpi-label">Tiền cọc phiếu đã hủy</div>
            <div class="rp-kpi-val" style="color: var(--rp-red);">
                <?= number_format((float)($bookingStats['cancelled_deposit'] ?? 0), 0, ',', '.') ?>đ
            </div>
            <div class="rp-kpi-sub">Tổng cọc các đơn đã hủy</div>
        </div>
    </div>

    <!-- KHUNG ĐIỀU PHỐI 2 CỘT: THANH DỌC TIẾN ĐỘ POS KDS + THANH NGANG SƠ ĐỒ BÀN -->
    <div class="rp-dispatch-shell">

        <!-- THANH NẰM DỌC: TIẾN ĐỘ POS & BẾP KDS -->
        <aside class="rp-vertical-bar">
            <div class="rp-v-tabs" id="vStatusTabs">
                <button type="button" class="rp-v-tab active" data-tab="ALL">
                    <span class="rp-v-tab-name">Tất cả</span>
                    <span class="rp-v-tab-count"><?= count($upcomingList) + count($pendingList) + count($seatedList) ?></span>
                </button>
                <button type="button" class="rp-v-tab" data-tab="UPCOMING">
                    <span class="rp-v-tab-name">Sắp đến</span>
                    <span class="rp-v-tab-count"><?= count($upcomingList) + count($pendingList) ?></span>
                </button>
                <button type="button" class="rp-v-tab" data-tab="SEATED">
                    <span class="rp-v-tab-name">Đang phục vụ</span>
                    <span class="rp-v-tab-count"><?= count($seatedList) ?></span>
                </button>
            </div>

            <div class="rp-v-search">
                <input type="text" id="vSearchInput" class="rp-input" placeholder="Tìm khách, số điện thoại, tên bàn...">
            </div>

            <div class="rp-v-list" id="vReservationList">
                <?php if (empty($reservations)): ?>
                    <div style="text-align:center; padding: 32px 12px; color: var(--rp-muted); font-size: 13px;">
                        Chưa có phiếu đặt bàn
                    </div>
                <?php else: ?>
                    <?php foreach ($reservations as $r): ?>
                        <?php
                        $st = $r['status'] ?? 'Pending';
                        if ($st === 'Cancelled') continue;
                        $tabGroup = ($st === 'CheckedIn') ? 'SEATED' : 'UPCOMING';
                        $tId = (int)($r['table_id'] ?? 0);
                        $timeFormatted = date('H:i · d-m', strtotime($r['booking_time']));
                        $cleanArea = $cleanLabel($r['area_name'] ?? '');
                        $searchBlob = mb_strtolower(($r['full_name'] ?? '') . ' ' . ($r['phone'] ?? '') . ' ' . ($r['table_name'] ?? '') . ' ' . ($r['booking_code'] ?? ''), 'UTF-8');
                        ?>
                        <div class="rp-v-card"
                             data-tab="<?= $tabGroup ?>"
                             data-table-id="<?= $tId ?>"
                             data-search="<?= htmlspecialchars($searchBlob) ?>"
                             onclick="focusTableOnMap(<?= $tId ?>, this)">

                            <div class="rp-v-row1">
                                <span><?= htmlspecialchars($r['full_name']) ?></span>
                                <span><?= $timeFormatted ?></span>
                            </div>

                            <div class="rp-v-row2">
                                <span><?= htmlspecialchars($r['table_name'] ?? 'Chưa xếp bàn') ?> · <?= htmlspecialchars($cleanArea) ?></span>
                                <span><?= (int)$r['guest_count'] ?> khách</span>
                            </div>

                            <div class="rp-v-prog-head">
                                <span><?= htmlspecialchars($r['progress_text']) ?></span>
                                <span><?= (int)$r['progress_percent'] ?>%</span>
                            </div>

                            <div class="rp-prog-bar">
                                <div class="rp-prog-fill <?= htmlspecialchars($r['progress_color']) ?>"
                                     style="width: <?= (int)$r['progress_percent'] ?>%;"></div>
                            </div>

                            <div class="rp-v-actions" onclick="event.stopPropagation();">
                                <?php if ($st === 'Pending'): ?>
                                    <form method="POST" action="index.php?c=booking&a=report" style="display:inline;">
                                        <input type="hidden" name="return_page" value="report">
                                        <input type="hidden" name="action" value="status">
                                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                        <input type="hidden" name="status" value="Confirmed">
                                        <button type="submit" class="rp-mini-btn green">Xác nhận</button>
                                    </form>
                                    <form method="POST" action="index.php?c=booking&a=report" style="display:inline;">
                                        <input type="hidden" name="return_page" value="report">
                                        <input type="hidden" name="action" value="status">
                                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                        <input type="hidden" name="status" value="Cancelled">
                                        <button type="submit" class="rp-mini-btn danger">Hủy đặt</button>
                                    </form>
                                <?php elseif ($st === 'Confirmed'): ?>
                                    <form method="POST" action="index.php?c=booking&a=report" style="display:inline;">
                                        <input type="hidden" name="return_page" value="report">
                                        <input type="hidden" name="action" value="status">
                                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                        <input type="hidden" name="status" value="CheckedIn">
                                        <button type="submit" class="rp-mini-btn gold">Check-in mở POS</button>
                                    </form>
                                    <form method="POST" action="index.php?c=booking&a=report" style="display:inline;">
                                        <input type="hidden" name="return_page" value="report">
                                        <input type="hidden" name="action" value="status">
                                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                        <input type="hidden" name="status" value="Cancelled">
                                        <button type="submit" class="rp-mini-btn danger">Hủy đặt</button>
                                    </form>
                                <?php elseif ($st === 'CheckedIn'): ?>
                                    <a href="index.php?c=pos&a=index" class="rp-mini-btn navy">Mở POS</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </aside>

        <!-- KHUNG BÊN PHẢI: THANH NGANG TẦNG & KHU VỰC TỪ SƠ ĐỒ BÀN -->
        <section class="rp-right-stage">

            <!-- Thanh ngang 1: Nút thao tác + Chọn Tầng + Chọn Khu vực -->
            <div class="rp-hbar">
                <div class="rp-hgroup">
                    <button type="button" class="rp-btn gold" onclick="openBookingModal(0)">Tạo phiếu đặt bàn mới</button>
                    <a href="index.php?c=booking&a=index" class="rp-btn">Xem sơ đồ bàn</a>
                    <a href="index.php?c=booking&a=vip" class="rp-btn navy">Quản lý Đặt bàn và Check-in</a>
                </div>

                <div class="rp-hgroup" id="hFloorTabs">
                    <button type="button" class="rp-pill-btn active" data-floor="ALL">Tất cả tầng</button>
                    <?php foreach ($floorsSummary as $fl): ?>
                        <?php $flNum = (int)$fl['floor_number']; ?>
                        <button type="button" class="rp-pill-btn" data-floor="<?= $flNum ?>">
                            Tầng <?= $flNum ?>
                        </button>
                    <?php endforeach; ?>
                </div>

                <div class="rp-hgroup" id="hAreaTabs">
                    <button type="button" class="rp-pill-btn active" data-area="ALL">Tất cả khu vực</button>
                    <?php foreach ($tablesByArea as $aId => $areaInfo): ?>
                        <button type="button" class="rp-pill-btn" data-area="<?= (int)$aId ?>">
                            <?= htmlspecialchars($areaInfo['area_name']) ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Sơ đồ bàn & phòng trực quan -->
            <div class="rp-map-canvas">
                <?php foreach ($tablesByArea as $aId => $areaInfo): ?>
                    <div class="rp-area-box area-room-section"
                         data-area-id="<?= (int)$aId ?>"
                         data-floor-num="<?= (int)$areaInfo['floor_number'] ?>">
                        <div class="rp-area-badge">
                            <span>Tầng <?= (int)$areaInfo['floor_number'] ?> · <?= htmlspecialchars($areaInfo['area_name']) ?></span>
                            <?php if ($areaInfo['surcharge'] > 0): ?>
                                <span>· Phụ thu <?= number_format($areaInfo['surcharge'], 0) ?>%</span>
                            <?php endif; ?>
                        </div>

                        <div class="rp-table-grid">
                            <?php foreach ($areaInfo['tables'] as $tb): ?>
                                <?php
                                $tId = (int)$tb['table_id'];
                                $rawStatus = $tb['status'] ?? 'Available';
                                if ($rawStatus === 'Available' && in_array($tId, $reservedTableIds, true)) {
                                    $rawStatus = 'Reserved';
                                }

                                $cap = max(2, (int)$tb['capacity']);
                                $topChairs = min(4, (int)ceil($cap / 2));
                                $botChairs = min(4, (int)floor($cap / 2));

                                $kdsInfo = $kdsMap[$tId] ?? null;
                                $linkedRes = $activeReservationByTable[$tId] ?? null;

                                $statusLabelVi = match ($rawStatus) {
                                    'Occupied' => 'Đang phục vụ',
                                    'Reserved' => 'Đã đặt trước',
                                    'Cleaning' => 'Đang dọn',
                                    default    => 'Bàn trống'
                                };

                                $kdsTextLine = $statusLabelVi;
                                if ($rawStatus === 'Occupied' && $kdsInfo) {
                                    $totD = (int)$kdsInfo['total_dishes'];
                                    $srvD = (int)$kdsInfo['served_dishes'];
                                    $ckD  = (int)$kdsInfo['cooking_dishes'];
                                    $rdD  = (int)$kdsInfo['ready_dishes'];
                                    if ($totD === 0) {
                                        $kdsTextLine = 'Chờ gọi món';
                                    } elseif ($srvD >= $totD) {
                                        $kdsTextLine = 'Đã lên đủ ' . $srvD . ' món';
                                    } elseif ($rdD > 0) {
                                        $kdsTextLine = 'Chờ lên ' . $rdD . ' món · Đã ra ' . $srvD . ' trên ' . $totD;
                                    } elseif ($ckD > 0) {
                                        $kdsTextLine = 'Đang nấu ' . $ckD . ' món · Đã ra ' . $srvD . ' trên ' . $totD;
                                    } else {
                                        $kdsTextLine = 'Đã ra ' . $srvD . ' trên ' . $totD . ' món';
                                    }
                                } elseif ($linkedRes && $rawStatus === 'Reserved') {
                                    $kdsTextLine = date('H:i', strtotime($linkedRes['booking_time'])) . ' · ' . $linkedRes['full_name'];
                                }
                                ?>
                                <div class="rp-table-node st-<?= htmlspecialchars($rawStatus) ?> map-table-node"
                                     id="map_table_<?= $tId ?>"
                                     data-status="<?= htmlspecialchars($rawStatus) ?>"
                                     onclick="handleMapTableClick(<?= $tId ?>, '<?= htmlspecialchars($rawStatus) ?>')">

                                    <div class="rp-chairs">
                                        <?php for ($c = 0; $c < $topChairs; $c++): ?>
                                            <span class="rp-chair"></span>
                                        <?php endfor; ?>
                                    </div>

                                    <div class="rp-table-body">
                                        <div style="font-size: 14.5px; font-weight: 800; color: var(--rp-navy);">
                                            <?= htmlspecialchars($tb['table_name']) ?>
                                        </div>
                                        <div style="font-size: 11px; font-weight: 700; color: var(--rp-muted);">
                                            <?= $cap ?> ghế · <?= htmlspecialchars($tb['table_type'] ?? 'Standard') ?>
                                        </div>
                                        <div style="width:100%; margin-top:5px; padding-top:5px; border-top:1px dashed rgba(15,23,42,0.12); font-size:11px; font-weight:800;">
                                            <?= htmlspecialchars($kdsTextLine) ?>
                                        </div>
                                    </div>

                                    <div class="rp-chairs">
                                        <?php for ($c = 0; $c < $botChairs; $c++): ?>
                                            <span class="rp-chair"></span>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Thanh ngang 2: Bộ lọc trạng thái bàn -->
            <div class="rp-hbar">
                <div class="rp-hgroup" id="hStatusFilter">
                    <button type="button" class="rp-pill-btn active" data-st="ALL">
                        Tất cả bàn · <?= (int)($statistics['total'] ?? 0) ?>
                    </button>
                    <button type="button" class="rp-pill-btn" data-st="Available">
                        Bàn trống · <?= (int)($statistics['available'] ?? 0) ?>
                    </button>
                    <button type="button" class="rp-pill-btn" data-st="Occupied">
                        Đang phục vụ · <?= (int)($statistics['occupied'] ?? 0) ?>
                    </button>
                    <button type="button" class="rp-pill-btn" data-st="Reserved">
                        Đã đặt trước · <?= (int)($statistics['reserved'] ?? 0) ?>
                    </button>
                    <button type="button" class="rp-pill-btn" data-st="Cleaning">
                        Đang dọn · <?= (int)($statistics['cleaning'] ?? 0) ?>
                    </button>
                </div>
            </div>

        </section>
    </div>

    <!-- BẢNG DANH SÁCH PHIẾU ĐẶT BÀN CHI TIẾT -->
    <div class="rp-list-card">
        <table class="rp-table">
            <thead>
                <tr>
                    <th>Mã đặt bàn</th>
                    <th>Khách hàng</th>
                    <th>Số điện thoại</th>
                    <th>Bàn và Khu vực</th>
                    <th>Thời gian hẹn</th>
                    <th>Số khách</th>
                    <th>Tiền cọc</th>
                    <th>Tiến độ POS và Bếp</th>
                    <th>Trạng thái</th>
                    <th>Thao tác nghiệp vụ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reservations)): ?>
                    <tr>
                        <td colspan="10" style="text-align:center; padding: 28px; color: var(--rp-muted);">
                            Chưa có dữ liệu đặt bàn
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($reservations as $r): ?>
                        <?php
                        $st = $r['status'] ?? 'Pending';
                        $statusBadge = match ($st) {
                            'Confirmed' => ['text' => 'Đã giữ bàn', 'bg' => '#dbeafe', 'color' => '#1d4ed8'],
                            'CheckedIn' => ['text' => 'Đang phục vụ', 'bg' => '#dcfce7', 'color' => '#15803d'],
                            'Cancelled' => ['text' => 'Đã hủy', 'bg' => '#fee2e2', 'color' => '#b91c1c'],
                            default     => ['text' => 'Chờ xác nhận', 'bg' => '#fef3c7', 'color' => '#b45309']
                        };
                        ?>
                        <tr>
                            <td style="font-weight:800; color:var(--rp-navy);"><?= htmlspecialchars($r['booking_code']) ?></td>
                            <td style="font-weight:800; color:var(--rp-navy);"><?= htmlspecialchars($r['full_name']) ?></td>
                            <td style="font-weight:700;"><?= htmlspecialchars($r['phone']) ?></td>
                            <td>
                                <div style="font-weight:800; color:var(--rp-navy);"><?= htmlspecialchars($r['table_name'] ?? 'Chưa xếp bàn') ?></div>
                                <div style="font-size:11.5px; color:var(--rp-muted);"><?= htmlspecialchars($cleanLabel($r['area_name'] ?? '')) ?></div>
                            </td>
                            <td style="font-weight:700;"><?= date('H:i - d-m-Y', strtotime($r['booking_time'])) ?></td>
                            <td style="font-weight:800;"><?= (int)$r['guest_count'] ?></td>
                            <td style="font-weight:800; color:var(--rp-green);"><?= number_format((float)$r['deposit_amount'], 0, ',', '.') ?>đ</td>
                            <td style="font-size:12px; font-weight:700; color:var(--rp-navy);"><?= htmlspecialchars($r['progress_text']) ?></td>
                            <td>
                                <span style="display:inline-block; padding:4px 10px; border-radius:6px; font-size:11.5px; font-weight:800; background:<?= $statusBadge['bg'] ?>; color:<?= $statusBadge['color'] ?>;">
                                    <?= $statusBadge['text'] ?>
                                </span>
                            </td>
                            <td>
                                <div style="display:flex; gap:6px;">
                                    <?php if ($st === 'Pending'): ?>
                                        <form method="POST" action="index.php?c=booking&a=report" style="display:inline;">
                                            <input type="hidden" name="return_page" value="report">
                                            <input type="hidden" name="action" value="status">
                                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                            <input type="hidden" name="status" value="Confirmed">
                                            <button type="submit" class="rp-mini-btn green">Xác nhận</button>
                                        </form>
                                        <form method="POST" action="index.php?c=booking&a=report" style="display:inline;">
                                            <input type="hidden" name="return_page" value="report">
                                            <input type="hidden" name="action" value="status">
                                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                            <input type="hidden" name="status" value="Cancelled">
                                            <button type="submit" class="rp-mini-btn danger">Hủy đặt</button>
                                        </form>
                                    <?php elseif ($st === 'Confirmed'): ?>
                                        <form method="POST" action="index.php?c=booking&a=report" style="display:inline;">
                                            <input type="hidden" name="return_page" value="report">
                                            <input type="hidden" name="action" value="status">
                                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                            <input type="hidden" name="status" value="CheckedIn">
                                            <button type="submit" class="rp-mini-btn gold">Check-in mở POS</button>
                                        </form>
                                        <form method="POST" action="index.php?c=booking&a=report" style="display:inline;">
                                            <input type="hidden" name="return_page" value="report">
                                            <input type="hidden" name="action" value="status">
                                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                            <input type="hidden" name="status" value="Cancelled">
                                            <button type="submit" class="rp-mini-btn danger">Hủy đặt</button>
                                        </form>
                                    <?php elseif ($st === 'CheckedIn'): ?>
                                        <a href="index.php?c=pos&a=index" class="rp-mini-btn">Mở POS</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<!-- POPUP TẠO PHIẾU ĐẶT BÀN MỚI -->
<div class="rp-modal-backdrop" id="modalCreateBooking">
    <div class="rp-modal-dialog">
        <div class="rp-modal-header">
            <span>Tạo phiếu đặt bàn mới</span>
            <button type="button" class="rp-mini-btn" onclick="closeBookingModal()">Đóng</button>
        </div>
        <div class="rp-modal-body">
            <form method="POST" action="index.php?c=booking&a=report">
                <input type="hidden" name="return_page" value="report">
                <input type="hidden" name="action" value="create">
                <div class="rp-form-grid">
                    <div>
                        <label class="rp-label">Họ và tên khách hàng *</label>
                        <input type="text" name="full_name" class="rp-input" placeholder="Nhập họ tên khách" required>
                    </div>
                    <div>
                        <label class="rp-label">Số điện thoại liên hệ *</label>
                        <input type="tel" name="phone" class="rp-input" pattern="0[0-9]{9,10}" maxlength="11" placeholder="0988888888" required>
                    </div>
                    <div>
                        <label class="rp-label">Địa chỉ Email</label>
                        <input type="email" name="email" class="rp-input" placeholder="khachhang@email.com">
                    </div>
                    <div>
                        <label class="rp-label">Chọn bàn phục vụ *</label>
                        <select name="table_id" id="modalTableSelect" class="rp-input" required>
                            <option value="">-- Chọn bàn phù hợp --</option>
                            <?php foreach ($tables as $t): ?>
                                <?php $tType = $t['table_type'] ?? 'Standard'; ?>
                                <option value="<?= (int)$t['id'] ?>" data-type="<?= htmlspecialchars($tType) ?>">
                                    <?= htmlspecialchars($t['table_name'] . ' · ' . $cleanLabel($t['area_name'] ?? '') . ' · ' . $t['capacity'] . ' ghế') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="rp-label">Thời gian khách đến *</label>
                        <input type="datetime-local" name="booking_time" class="rp-input" value="<?= htmlspecialchars($defaultBookingTime) ?>" required>
                    </div>
                    <div>
                        <label class="rp-label">Số lượng khách *</label>
                        <input type="number" name="guest_count" id="modalGuestCount" class="rp-input" min="1" max="100" value="4" required>
                    </div>
                    <div>
                        <label class="rp-label">Tiền đặt cọc trước</label>
                        <input type="number" name="deposit_amount" id="modalDepositAmount" class="rp-input" min="0" step="10000" value="50000" required>
                    </div>
                    <div>
                        <label class="rp-label">Ghi chú yêu cầu phục vụ đặc biệt</label>
                        <input type="text" name="special_request" class="rp-input" placeholder="Trang trí sinh nhật, ghế trẻ em, phòng yên tĩnh...">
                    </div>
                </div>
                <div style="margin-top: 18px; display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" class="rp-btn" onclick="closeBookingModal()">Hủy</button>
                    <button type="submit" class="rp-btn gold">Lưu phiếu đặt bàn</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let currentVTab = 'ALL';
let currentFloorFilter = 'ALL';
let currentAreaFilter = 'ALL';
let currentTableStFilter = 'ALL';

function filterVerticalList() {
    const kw = (document.getElementById('vSearchInput').value || '').trim().toLowerCase();
    document.querySelectorAll('#vReservationList .rp-v-card').forEach(function (item) {
        const itemTab = item.getAttribute('data-tab') || '';
        const itemSearch = item.getAttribute('data-search') || '';
        const matchTab = (currentVTab === 'ALL') || (itemTab === currentVTab);
        const matchKw = (kw === '') || (itemSearch.indexOf(kw) !== -1);
        item.style.display = (matchTab && matchKw) ? '' : 'none';
    });
}

document.querySelectorAll('#vStatusTabs .rp-v-tab').forEach(function (btn) {
    btn.addEventListener('click', function () {
        document.querySelectorAll('#vStatusTabs .rp-v-tab').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        currentVTab = this.getAttribute('data-tab') || 'ALL';
        filterVerticalList();
    });
});

document.getElementById('vSearchInput').addEventListener('input', filterVerticalList);

function syncRoomVisibility() {
    document.querySelectorAll('.area-room-section').forEach(function (sec) {
        const aId = sec.getAttribute('data-area-id');
        const flNum = sec.getAttribute('data-floor-num');
        const matchFloor = (currentFloorFilter === 'ALL' || flNum === currentFloorFilter);
        const matchArea = (currentAreaFilter === 'ALL' || aId === currentAreaFilter);
        sec.style.display = (matchFloor && matchArea) ? '' : 'none';
    });
}

document.querySelectorAll('#hFloorTabs .rp-pill-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
        document.querySelectorAll('#hFloorTabs .rp-pill-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        currentFloorFilter = this.getAttribute('data-floor') || 'ALL';
        syncRoomVisibility();
    });
});

document.querySelectorAll('#hAreaTabs .rp-pill-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
        document.querySelectorAll('#hAreaTabs .rp-pill-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        currentAreaFilter = this.getAttribute('data-area') || 'ALL';
        syncRoomVisibility();
    });
});

document.querySelectorAll('#hStatusFilter .rp-pill-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
        document.querySelectorAll('#hStatusFilter .rp-pill-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        currentTableStFilter = this.getAttribute('data-st') || 'ALL';
        document.querySelectorAll('.map-table-node').forEach(function (node) {
            const st = node.getAttribute('data-status');
            node.style.display = (currentTableStFilter === 'ALL' || st === currentTableStFilter) ? '' : 'none';
        });
    });
});

function focusTableOnMap(tableId, itemEl) {
    document.querySelectorAll('.rp-v-card').forEach(el => el.classList.remove('active'));
    if (itemEl) itemEl.classList.add('active');

    document.querySelectorAll('.map-table-node').forEach(el => el.classList.remove('highlight-focus'));
    if (tableId > 0) {
        const target = document.getElementById('map_table_' + tableId);
        if (target) {
            target.classList.add('highlight-focus');
            target.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
}

function handleMapTableClick(tableId, status) {
    if (status === 'Occupied') {
        window.location.href = 'index.php?c=pos&a=index';
        return;
    }
    openBookingModal(tableId);
}

function openBookingModal(tableId) {
    const modal = document.getElementById('modalCreateBooking');
    const select = document.getElementById('modalTableSelect');
    if (select && tableId > 0) {
        select.value = String(tableId);
        updateSuggestedDeposit();
    }
    modal.classList.add('show');
}

function closeBookingModal() {
    document.getElementById('modalCreateBooking').classList.remove('show');
}

function updateSuggestedDeposit() {
    const select = document.getElementById('modalTableSelect');
    const count = parseInt(document.getElementById('modalGuestCount').value || '1', 10);
    const opt = select.options[select.selectedIndex];
    const tType = opt ? (opt.getAttribute('data-type') || 'Standard') : 'Standard';

    let dep = 50000;
    if (tType === 'Premium') dep = 10000000;
    else if (tType === 'VIP') dep = 5000000;
    else if (count > 20) dep = 300000;
    else if (count > 12) dep = 200000;
    else if (count > 8) dep = 150000;
    else if (count > 4) dep = 100000;

    document.getElementById('modalDepositAmount').value = dep;
}

document.getElementById('modalTableSelect').addEventListener('change', updateSuggestedDeposit);
document.getElementById('modalGuestCount').addEventListener('change', updateSuggestedDeposit);
</script>