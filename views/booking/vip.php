<?php
$reservations = $reservations ?? [];
$tables = $tables ?? [];
$allTables = $allTables ?? [];
$floorsSummary = $floorsSummary ?? [];
$areas = $areas ?? [];
$statistics = $statistics ?? ['total' => 0, 'available' => 0, 'occupied' => 0, 'reserved' => 0, 'cleaning' => 0];
$reservedTableIds = $reservedTableIds ?? [];
$kdsMap = $kdsMap ?? [];
$error = $error ?? '';
$success = $success ?? '';

$cleanText = function (?string $text): string {
    $str = (string)$text;
    $str = preg_replace('/\s*\([^)]*\)/u', '', $str);
    $str = preg_replace('/\s*\[[^\]]*\]/u', '', $str);
    $str = str_replace(['/', '(', ')', '[', ']'], [' · ', '', '', '', ''], $str);
    return trim($str);
};

$tierLabels = [
    'Standard' => 'Hạng Standard · Tích điểm',
    'Silver'   => 'Hạng Silver · Giảm 3%',
    'Gold'     => 'Hạng Gold · Giảm 5%',
    'Diamond'  => 'Hạng Diamond · Giảm 10%'
];

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

$tablesByArea = [];
foreach ($allTables as $tb) {
    if (empty($tb['table_id'])) continue;
    $aId = (int)$tb['area_id'];
    $flNum = (int)($tb['floor_number'] ?? 1);
    if (!isset($tablesByArea[$aId])) {
        $tablesByArea[$aId] = [
            'area_id'      => $aId,
            'floor_number' => $flNum,
            'area_name'    => $cleanText($tb['area_name'] ?? ''),
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
    --vp-navy: #17243b;
    --vp-navy-dark: #0f172a;
    --vp-gold: #c69b45;
    --vp-gold-soft: #fffbeb;
    --vp-bg: #f8fafc;
    --vp-card: #ffffff;
    --vp-border: #e2e8f0;
    --vp-text: #1e293b;
    --vp-muted: #64748b;
    --vp-green: #15803d;
    --vp-green-bg: #dcfce7;
    --vp-blue: #1d4ed8;
    --vp-blue-bg: #dbeafe;
    --vp-amber: #b45309;
    --vp-amber-bg: #fef3c7;
    --vp-red: #b91c1c;
    --vp-red-bg: #fee2e2;
}

.vp-workspace {
    font-family: 'Be Vietnam Pro', sans-serif;
    background: var(--vp-bg);
    color: var(--vp-text);
    padding: 14px 20px 38px;
}

.vp-flash {
    padding: 10px 16px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 700;
    margin-bottom: 14px;
}
.vp-flash-ok { background: var(--vp-green-bg); color: var(--vp-green); border: 1px solid #86efac; }
.vp-flash-err { background: var(--vp-red-bg); color: var(--vp-red); border: 1px solid #fca5a5; }

/* Main Split Grid: Vertical Progress Left + Horizontal Map Right */
.vp-shell {
    display: grid;
    grid-template-columns: 345px 1fr;
    gap: 16px;
    align-items: start;
    margin-bottom: 18px;
}
@media (max-width: 1150px) {
    .vp-shell { grid-template-columns: 1fr; }
}

/* LEFT VERTICAL SIDEBAR */
.vp-vertical-panel {
    background: var(--vp-card);
    border: 1px solid var(--vp-border);
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.04);
    display: flex;
    flex-direction: column;
    max-height: calc(100vh - 110px);
    position: sticky;
    top: 14px;
}

.vp-v-tabs {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    border-bottom: 1px solid var(--vp-border);
    background: #ffffff;
}
.vp-v-tab {
    padding: 13px 6px;
    text-align: center;
    background: transparent;
    border: none;
    border-bottom: 3px solid transparent;
    cursor: pointer;
    transition: 0.15s;
}
.vp-v-tab:hover { background: #f8fafc; }
.vp-v-tab.active {
    border-bottom-color: var(--vp-navy);
    background: #f8fafc;
}
.vp-v-tab-label {
    display: block;
    font-size: 12px;
    font-weight: 700;
    color: var(--vp-muted);
    margin-bottom: 2px;
}
.vp-v-tab.active .vp-v-tab-label { color: var(--vp-navy); }
.vp-v-tab-num {
    font-size: 18px;
    font-weight: 800;
    color: var(--vp-navy);
}

.vp-v-search {
    padding: 10px 12px;
    border-bottom: 1px solid var(--vp-border);
    background: #f8fafc;
}
.vp-input {
    width: 100%;
    padding: 9px 12px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-size: 13px;
    font-family: inherit;
    color: var(--vp-text);
    background: #ffffff;
}
.vp-input:focus {
    outline: none;
    border-color: var(--vp-gold);
    box-shadow: 0 0 0 3px rgba(198, 155, 69, 0.15);
}

.vp-v-list {
    overflow-y: auto;
    padding: 12px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    flex: 1;
}

.vp-res-card {
    background: #ffffff;
    border: 1px solid var(--vp-border);
    border-radius: 10px;
    padding: 12px;
    cursor: pointer;
    transition: 0.15s;
}
.vp-res-card:hover,
.vp-res-card.active {
    border-color: var(--vp-gold);
    background: var(--vp-gold-soft);
    box-shadow: 0 4px 12px rgba(198, 155, 69, 0.12);
}
.vp-res-head {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    font-size: 13.5px;
    font-weight: 800;
    color: var(--vp-navy);
    margin-bottom: 4px;
}
.vp-res-sub {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    font-weight: 600;
    color: var(--vp-muted);
    margin-bottom: 8px;
}
.vp-prog-label {
    display: flex;
    justify-content: space-between;
    font-size: 11px;
    font-weight: 700;
    color: var(--vp-navy);
    margin-bottom: 4px;
}
.vp-prog-track {
    width: 100%;
    height: 6px;
    background: #e2e8f0;
    border-radius: 999px;
    overflow: hidden;
    margin-bottom: 9px;
}
.vp-prog-fill {
    height: 100%;
    border-radius: 999px;
    transition: width 0.25s ease;
}
.vp-prog-fill.green { background: var(--vp-green); }
.vp-prog-fill.blue  { background: var(--vp-blue); }
.vp-prog-fill.gold  { background: var(--vp-gold); }
.vp-prog-fill.amber { background: var(--vp-amber); }
.vp-prog-fill.red   { background: var(--vp-red); }

.vp-res-foot {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-top: 1px dashed #e2e8f0;
    padding-top: 8px;
    gap: 6px;
}
.vp-tier-tag {
    font-size: 10.5px;
    font-weight: 700;
    padding: 3px 7px;
    border-radius: 4px;
    background: #f1f5f9;
    color: #334155;
}
.vp-mini-btn {
    padding: 5px 10px;
    border-radius: 6px;
    font-size: 11.5px;
    font-weight: 800;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    color: var(--vp-navy);
    cursor: pointer;
    text-decoration: none;
}
.vp-mini-btn.navy { background: var(--vp-navy); color: #e5bd69; border-color: var(--vp-navy); }
.vp-mini-btn.gold { background: var(--vp-gold); color: var(--vp-navy); border-color: #b38936; }
.vp-mini-btn.green { background: var(--vp-green); color: #ffffff; border-color: var(--vp-green); }
.vp-mini-btn.danger { color: var(--vp-red); border-color: #fca5a5; background: #fff5f5; }

/* RIGHT STAGE: HORIZONTAL BARS & FLOOR PLAN */
.vp-right-stage {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.vp-hbar {
    background: var(--vp-card);
    border: 1px solid var(--vp-border);
    border-radius: 12px;
    padding: 10px 14px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.03);
}
.vp-hgroup {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    align-items: center;
}
.vp-btn {
    padding: 8px 15px;
    border-radius: 20px;
    font-size: 12.5px;
    font-weight: 800;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    color: var(--vp-navy);
    cursor: pointer;
    text-decoration: none;
    transition: 0.15s;
}
.vp-btn:hover {
    border-color: var(--vp-gold);
    background: var(--vp-gold-soft);
}
.vp-btn.navy { background: var(--vp-navy); color: #e5bd69; border-color: var(--vp-navy); }
.vp-btn.gold { background: var(--vp-gold); color: var(--vp-navy); border-color: #b38936; }

.vp-pill {
    padding: 7px 14px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 800;
    border: 1px solid transparent;
    background: #f1f5f9;
    color: #475569;
    cursor: pointer;
}
.vp-pill.active {
    background: var(--vp-blue-bg);
    color: var(--vp-blue);
    border-color: #93c5fd;
}

.vp-floor-canvas {
    background: #ffffff;
    border: 1px solid var(--vp-border);
    border-radius: 14px;
    padding: 18px;
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.03);
}
.vp-room-box {
    border: 5px solid #e2e8f0;
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 18px;
    background: #fcfcfd;
}
.vp-room-box:last-child { margin-bottom: 0; }
.vp-room-tag {
    display: inline-flex;
    gap: 8px;
    background: var(--vp-navy);
    color: #e5bd69;
    font-size: 11.5px;
    font-weight: 800;
    text-transform: uppercase;
    padding: 5px 12px;
    border-radius: 6px;
    margin-bottom: 14px;
}
.vp-table-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
    gap: 18px;
}
.vp-table-unit {
    display: flex;
    flex-direction: column;
    align-items: center;
    cursor: pointer;
    transition: transform 0.15s;
}
.vp-table-unit:hover { transform: translateY(-3px); }
.vp-table-unit.highlight-focus .vp-table-surface {
    outline: 3px solid var(--vp-gold);
    box-shadow: 0 0 0 5px rgba(198, 155, 69, 0.25);
}
.vp-chairs {
    display: flex;
    gap: 6px;
    margin: 3px 0;
}
.vp-chair {
    width: 22px;
    height: 6px;
    border-radius: 4px;
    background: #cbd5e1;
}
.vp-table-surface {
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
.vp-table-unit.st-Reserved .vp-table-surface { background: #fffbeb; border-color: #f59e0b; color: #92400e; }
.vp-table-unit.st-Reserved .vp-chair { background: #fcd34d; }
.vp-table-unit.st-Occupied .vp-table-surface { background: #eff6ff; border-color: #60a5fa; color: #1e3a8a; }
.vp-table-unit.st-Occupied .vp-chair { background: #93c5fd; }
.vp-table-unit.st-Cleaning .vp-table-surface { background: #fef2f2; border-color: #fca5a5; color: #991b1b; }

/* Modal */
.vp-modal-backdrop {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.58);
    z-index: 1060;
    align-items: center;
    justify-content: center;
    padding: 16px;
}
.vp-modal-backdrop.show { display: flex; }
.vp-modal-dialog {
    background: #ffffff;
    border-radius: 14px;
    width: min(680px, 100%);
    overflow: hidden;
    box-shadow: 0 20px 45px rgba(15, 23, 42, 0.25);
}
.vp-modal-header {
    background: var(--vp-navy);
    color: #e5bd69;
    padding: 14px 22px;
    font-size: 14px;
    font-weight: 800;
    text-transform: uppercase;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 2px solid var(--vp-gold);
}
.vp-modal-body { padding: 22px; }
.vp-form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
}
@media (max-width: 640px) {
    .vp-form-grid { grid-template-columns: 1fr; }
}
.vp-label {
    display: block;
    font-size: 12px;
    font-weight: 800;
    color: var(--vp-navy);
    margin-bottom: 5px;
}
</style>

<div class="vp-workspace">

    <?php if ($error !== ''): ?>
        <div class="vp-flash vp-flash-err"><?= htmlspecialchars($cleanText($error)) ?></div>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
        <div class="vp-flash vp-flash-ok"><?= htmlspecialchars($cleanText($success)) ?></div>
    <?php endif; ?>

    <div class="vp-shell">

        <!-- THANH DỌC BÊN TRÁI: TIẾN ĐỘ ĐẶT BÀN & PHỤC VỤ TỪ POS KDS -->
        <aside class="vp-vertical-panel">
            <div class="vp-v-tabs" id="vStatusTabs">
                <button type="button" class="vp-v-tab active" data-tab="ALL">
                    <span class="vp-v-tab-label">Tất cả</span>
                    <span class="vp-v-tab-num"><?= count($upcomingList) + count($pendingList) + count($seatedList) ?></span>
                </button>
                <button type="button" class="vp-v-tab" data-tab="UPCOMING">
                    <span class="vp-v-tab-label">Sắp đến</span>
                    <span class="vp-v-tab-num"><?= count($upcomingList) + count($pendingList) ?></span>
                </button>
                <button type="button" class="vp-v-tab" data-tab="SEATED">
                    <span class="vp-v-tab-label">Đang phục vụ</span>
                    <span class="vp-v-tab-num"><?= count($seatedList) ?></span>
                </button>
            </div>

            <div class="vp-v-search">
                <input type="text" id="vSearchInput" class="vp-input" placeholder="Tìm khách, số điện thoại, tên bàn...">
            </div>

            <div class="vp-v-list" id="vReservationList">
                <?php if (empty($reservations)): ?>
                    <div style="text-align:center; padding: 36px 14px; color: var(--vp-muted); font-size: 13px;">
                        Chưa có phiếu đặt bàn
                    </div>
                <?php else: ?>
                    <?php foreach ($reservations as $r): ?>
                        <?php
                        $st = $r['status'] ?? 'Pending';
                        if ($st === 'Cancelled') continue;
                        $tabGroup = ($st === 'CheckedIn') ? 'SEATED' : 'UPCOMING';
                        $tId = (int)($r['table_id'] ?? 0);
                        $tier = $r['tier'] ?? 'Standard';
                        $tierText = $tierLabels[$tier] ?? $tierLabels['Standard'];
                        $timeFormatted = date('H:i · d-m', strtotime($r['booking_time']));
                        $cleanArea = $cleanText($r['area_name'] ?? '');
                        $searchBlob = mb_strtolower(($r['full_name'] ?? '') . ' ' . ($r['phone'] ?? '') . ' ' . ($r['table_name'] ?? '') . ' ' . ($r['booking_code'] ?? ''), 'UTF-8');
                        ?>
                        <div class="vp-res-card"
                             data-tab="<?= $tabGroup ?>"
                             data-table-id="<?= $tId ?>"
                             data-search="<?= htmlspecialchars($searchBlob) ?>"
                             onclick="focusTableOnMap(<?= $tId ?>, this)">

                            <div class="vp-res-head">
                                <span><?= htmlspecialchars($r['full_name']) ?></span>
                                <span><?= $timeFormatted ?></span>
                            </div>

                            <div class="vp-res-sub">
                                <span><?= htmlspecialchars($r['table_name'] ?? 'Chưa xếp bàn') ?> · <?= htmlspecialchars($cleanArea) ?></span>
                                <span><?= (int)$r['guest_count'] ?> khách</span>
                            </div>

                            <div class="vp-prog-label">
                                <span><?= htmlspecialchars($r['progress_text']) ?></span>
                                <span><?= (int)$r['progress_percent'] ?>%</span>
                            </div>

                            <div class="vp-prog-track">
                                <div class="vp-prog-fill <?= htmlspecialchars($r['progress_color']) ?>"
                                     style="width: <?= (int)$r['progress_percent'] ?>%;"></div>
                            </div>

                            <div class="vp-res-foot" onclick="event.stopPropagation();">
                                <span class="vp-tier-tag"><?= htmlspecialchars($tierText) ?></span>

                                <div style="display:flex; gap:5px;">
                                    <?php if ($st === 'Pending'): ?>
                                        <form method="POST" action="index.php?c=booking&a=vip" style="display:inline;">
                                            <input type="hidden" name="return_page" value="vip">
                                            <input type="hidden" name="action" value="status">
                                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                            <input type="hidden" name="status" value="Confirmed">
                                            <button type="submit" class="vp-mini-btn green">Xác nhận</button>
                                        </form>
                                        <form method="POST" action="index.php?c=booking&a=vip" style="display:inline;">
                                            <input type="hidden" name="return_page" value="vip">
                                            <input type="hidden" name="action" value="status">
                                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                            <input type="hidden" name="status" value="Cancelled">
                                            <button type="submit" class="vp-mini-btn danger">Hủy</button>
                                        </form>
                                    <?php elseif ($st === 'Confirmed'): ?>
                                        <form method="POST" action="index.php?c=booking&a=vip" style="display:inline;">
                                            <input type="hidden" name="return_page" value="vip">
                                            <input type="hidden" name="action" value="status">
                                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                            <input type="hidden" name="status" value="CheckedIn">
                                            <button type="submit" class="vp-mini-btn gold">Check-in POS</button>
                                        </form>
                                        <form method="POST" action="index.php?c=booking&a=vip" style="display:inline;">
                                            <input type="hidden" name="return_page" value="vip">
                                            <input type="hidden" name="action" value="status">
                                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                            <input type="hidden" name="status" value="Cancelled">
                                            <button type="submit" class="vp-mini-btn danger">Hủy</button>
                                        </form>
                                    <?php elseif ($st === 'CheckedIn'): ?>
                                        <a href="index.php?c=pos&a=index" class="vp-mini-btn navy">Mở POS</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </aside>

        <!-- KHUNG BÊN PHẢI: THANH NGANG TẦNG, KHU VỰC & SƠ ĐỒ BÀN -->
        <section class="vp-right-stage">

            <!-- Thanh ngang 1: Nút chức năng + Lọc Tầng + Lọc Khu vực -->
            <div class="vp-hbar">
                <div class="vp-hgroup">
                    <button type="button" class="vp-btn gold" onclick="openBookingModal(0)">Tạo phiếu đặt bàn mới</button>
                    <a href="index.php?c=pos&a=index" class="vp-btn">Khách vãng lai</a>
                    <a href="index.php?c=booking&a=index" class="vp-btn">Sơ đồ bàn</a>
                    <a href="index.php?c=booking&a=report" class="vp-btn navy">Báo cáo đặt bàn</a>
                </div>

                <div class="vp-hgroup" id="hFloorTabs">
                    <button type="button" class="vp-pill active" data-floor="ALL">Tất cả tầng</button>
                    <?php foreach ($floorsSummary as $fl): ?>
                        <?php $flNum = (int)$fl['floor_number']; ?>
                        <button type="button" class="vp-pill" data-floor="<?= $flNum ?>">
                            Tầng <?= $flNum ?>
                        </button>
                    <?php endforeach; ?>
                </div>

                <div class="vp-hgroup" id="hAreaTabs">
                    <button type="button" class="vp-pill active" data-area="ALL">Tất cả khu vực</button>
                    <?php foreach ($tablesByArea as $aId => $areaInfo): ?>
                        <button type="button" class="vp-pill" data-area="<?= (int)$aId ?>">
                            <?= htmlspecialchars($areaInfo['area_name']) ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Sơ đồ bàn và phòng lấy từ Sơ đồ bàn -->
            <div class="vp-floor-canvas">
                <?php foreach ($tablesByArea as $aId => $areaInfo): ?>
                    <div class="vp-room-box area-room-section"
                         data-area-id="<?= (int)$aId ?>"
                         data-floor-num="<?= (int)$areaInfo['floor_number'] ?>">
                        <div class="vp-room-tag">
                            <span>Tầng <?= (int)$areaInfo['floor_number'] ?> · <?= htmlspecialchars($areaInfo['area_name']) ?></span>
                            <?php if ($areaInfo['surcharge'] > 0): ?>
                                <span>· Phụ thu <?= number_format($areaInfo['surcharge'], 0) ?>%</span>
                            <?php endif; ?>
                        </div>

                        <div class="vp-table-grid">
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
                                <div class="vp-table-unit st-<?= htmlspecialchars($rawStatus) ?> map-table-node"
                                     id="map_table_<?= $tId ?>"
                                     data-status="<?= htmlspecialchars($rawStatus) ?>"
                                     onclick="handleMapTableClick(<?= $tId ?>, '<?= htmlspecialchars($rawStatus) ?>')">

                                    <div class="vp-chairs">
                                        <?php for ($c = 0; $c < $topChairs; $c++): ?>
                                            <span class="vp-chair"></span>
                                        <?php endfor; ?>
                                    </div>

                                    <div class="vp-table-surface">
                                        <div style="font-size: 14.5px; font-weight: 800; color: var(--vp-navy);">
                                            <?= htmlspecialchars($tb['table_name']) ?>
                                        </div>
                                        <div style="font-size: 11px; font-weight: 700; color: var(--vp-muted);">
                                            <?= $cap ?> ghế · <?= htmlspecialchars($tb['table_type'] ?? 'Standard') ?>
                                        </div>
                                        <div style="width:100%; margin-top:5px; padding-top:5px; border-top:1px dashed rgba(15,23,42,0.12); font-size:11px; font-weight:800;">
                                            <?= htmlspecialchars($kdsTextLine) ?>
                                        </div>
                                    </div>

                                    <div class="vp-chairs">
                                        <?php for ($c = 0; $c < $botChairs; $c++): ?>
                                            <span class="vp-chair"></span>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Thanh ngang 2: Bộ lọc trạng thái bàn -->
            <div class="vp-hbar">
                <div class="vp-hgroup" id="hStatusFilter">
                    <button type="button" class="vp-pill active" data-st="ALL">
                        Tất cả bàn · <?= (int)($statistics['total'] ?? 0) ?>
                    </button>
                    <button type="button" class="vp-pill" data-st="Available">
                        Bàn trống · <?= (int)($statistics['available'] ?? 0) ?>
                    </button>
                    <button type="button" class="vp-pill" data-st="Occupied">
                        Đang phục vụ · <?= (int)($statistics['occupied'] ?? 0) ?>
                    </button>
                    <button type="button" class="vp-pill" data-st="Reserved">
                        Đã đặt trước · <?= (int)($statistics['reserved'] ?? 0) ?>
                    </button>
                    <button type="button" class="vp-pill" data-st="Cleaning">
                        Đang dọn · <?= (int)($statistics['cleaning'] ?? 0) ?>
                    </button>
                </div>
            </div>

        </section>
    </div>
</div>

<!-- POPUP TẠO PHIẾU ĐẶT BÀN MỚI -->
<div class="vp-modal-backdrop" id="modalCreateBooking">
    <div class="vp-modal-dialog">
        <div class="vp-modal-header">
            <span>Tạo phiếu đặt bàn mới</span>
            <button type="button" class="vp-mini-btn" onclick="closeBookingModal()">Đóng</button>
        </div>
        <div class="vp-modal-body">
            <form method="POST" action="index.php?c=booking&a=vip">
                <input type="hidden" name="return_page" value="vip">
                <input type="hidden" name="action" value="create">
                <div class="vp-form-grid">
                    <div>
                        <label class="vp-label">Họ và tên khách hàng *</label>
                        <input type="text" name="full_name" class="vp-input" placeholder="Nhập họ tên khách" required>
                    </div>
                    <div>
                        <label class="vp-label">Số điện thoại liên hệ *</label>
                        <input type="tel" name="phone" class="vp-input" pattern="0[0-9]{9,10}" maxlength="11" placeholder="0988888888" required>
                    </div>
                    <div>
                        <label class="vp-label">Địa chỉ Email</label>
                        <input type="email" name="email" class="vp-input" placeholder="khachhang@email.com">
                    </div>
                    <div>
                        <label class="vp-label">Chọn bàn phục vụ *</label>
                        <select name="table_id" id="modalTableSelect" class="vp-input" required>
                            <option value="">-- Chọn bàn phù hợp --</option>
                            <?php foreach ($tables as $t): ?>
                                <?php $tType = $t['table_type'] ?? 'Standard'; ?>
                                <option value="<?= (int)$t['id'] ?>" data-type="<?= htmlspecialchars($tType) ?>">
                                    <?= htmlspecialchars($t['table_name'] . ' · ' . $cleanText($t['area_name'] ?? '') . ' · ' . $t['capacity'] . ' ghế') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="vp-label">Thời gian khách đến *</label>
                        <input type="datetime-local" name="booking_time" class="vp-input" value="<?= htmlspecialchars($defaultBookingTime) ?>" required>
                    </div>
                    <div>
                        <label class="vp-label">Số lượng khách *</label>
                        <input type="number" name="guest_count" id="modalGuestCount" class="vp-input" min="1" max="100" value="4" required>
                    </div>
                    <div>
                        <label class="vp-label">Tiền đặt cọc trước</label>
                        <input type="number" name="deposit_amount" id="modalDepositAmount" class="vp-input" min="0" step="10000" value="50000" required>
                    </div>
                    <div>
                        <label class="vp-label">Ghi chú phục vụ</label>
                        <input type="text" name="special_request" class="vp-input" placeholder="Trang trí sinh nhật, ghế trẻ em...">
                    </div>
                </div>
                <div style="margin-top: 18px; display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" class="vp-btn" onclick="closeBookingModal()">Hủy</button>
                    <button type="submit" class="vp-btn gold">Lưu phiếu đặt bàn</button>
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
    document.querySelectorAll('#vReservationList .vp-res-card').forEach(function (item) {
        const itemTab = item.getAttribute('data-tab') || '';
        const itemSearch = item.getAttribute('data-search') || '';
        const matchTab = (currentVTab === 'ALL') || (itemTab === currentVTab);
        const matchKw = (kw === '') || (itemSearch.indexOf(kw) !== -1);
        item.style.display = (matchTab && matchKw) ? '' : 'none';
    });
}

document.querySelectorAll('#vStatusTabs .vp-v-tab').forEach(function (btn) {
    btn.addEventListener('click', function () {
        document.querySelectorAll('#vStatusTabs .vp-v-tab').forEach(b => b.classList.remove('active'));
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

document.querySelectorAll('#hFloorTabs .vp-pill').forEach(function (btn) {
    btn.addEventListener('click', function () {
        document.querySelectorAll('#hFloorTabs .vp-pill').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        currentFloorFilter = this.getAttribute('data-floor') || 'ALL';
        syncRoomVisibility();
    });
});

document.querySelectorAll('#hAreaTabs .vp-pill').forEach(function (btn) {
    btn.addEventListener('click', function () {
        document.querySelectorAll('#hAreaTabs .vp-pill').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        currentAreaFilter = this.getAttribute('data-area') || 'ALL';
        syncRoomVisibility();
    });
});

document.querySelectorAll('#hStatusFilter .vp-pill').forEach(function (btn) {
    btn.addEventListener('click', function () {
        document.querySelectorAll('#hStatusFilter .vp-pill').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        currentTableStFilter = this.getAttribute('data-st') || 'ALL';
        document.querySelectorAll('.map-table-node').forEach(function (node) {
            const st = node.getAttribute('data-status');
            node.style.display = (currentTableStFilter === 'ALL' || st === currentTableStFilter) ? '' : 'none';
        });
    });
});

function focusTableOnMap(tableId, itemEl) {
    document.querySelectorAll('.vp-res-card').forEach(el => el.classList.remove('active'));
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