<?php
$selectedFloor  = (int)($selectedFloor ?? 1);
$selectedAreaId = (int)($selectedAreaId ?? 0);
$selectedDate   = $selectedDate ?? date('Y-m-d');
$isToday        = ($selectedDate === date('Y-m-d'));
$isAdmin        = !empty($isAdmin);
$floorsSummary  = $floorsSummary ?? [];
$areas          = $areas ?? [];
$flashSuccess   = $flashSuccess ?? '';
$flashError     = $flashError ?? '';


$standardTables = [];
$specialRooms   = [];

foreach ($tables as $row) {
    if (empty($row['table_id'])) continue;

    $tType = $row['table_type'] ?? 'Standard';

    if (in_array($tType, ['VIP', 'Premium'], true)) {
        $specialRooms[] = $row;
    } else {
        $standardTables[] = $row;
    }
}

function renderChairsAroundLight(int $capacity): array {
    $cap = in_array($capacity, [4, 8, 12], true) ? $capacity : 4;
    if ($cap === 4) return ['top' => 2, 'bottom' => 2, 'side' => 0];
    if ($cap === 8) return ['top' => 3, 'bottom' => 3, 'side' => 1];
    return ['top' => 5, 'bottom' => 5, 'side' => 1];
}
?>

<style>
.light-board-wrap {
    background: #ffffff;
    color: #1e293b;
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    padding: 26px 28px;
    box-shadow: 0 6px 22px rgba(15, 23, 42, 0.05);
}
.board-top-bar {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: center;
    gap: 14px;
    padding-bottom: 18px;
    margin-bottom: 18px;
    border-bottom: 1px solid #e2e8f0;
}
.board-title {
    font-size: 22px;
    font-weight: 800;
    color: #0f172a;
    margin: 0;
}
.light-filter-group {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px;
}
.light-input, .light-select {
    background: #f8fafc;
    border: 1px solid #cbd5e1;
    color: #0f172a;
    border-radius: 9px;
    padding: 7px 12px;
    font-size: 13.5px;
    font-weight: 600;
}
.status-legend-bar {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-bottom: 22px;
}
.legend-pill {
    display: inline-block;
    padding: 5px 16px;
    border-radius: 999px;
    font-size: 13px;
    font-weight: 700;
}
.legend-available { background: #dcfce7; color: #15803d; border: 1px solid #86efac; }
.legend-reserved  { background: #fef3c7; color: #b45309; border: 1px solid #fcd34d; }
.legend-occupied  { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }
.legend-vip       { background: #f3e8ff; color: #7e22ce; border: 1px solid #d8b4fe; }

/* Lưới Bàn Thường nền trắng */
.light-tables-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px;
    margin-bottom: 18px;
}
@media (max-width: 992px) { .light-tables-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 576px) { .light-tables-grid { grid-template-columns: 1fr; } }

.light-table-box {
    background: #ffffff;
    border: 2px solid #22c55e;
    border-radius: 16px;
    padding: 18px 14px;
    text-align: center;
    cursor: pointer;
    position: relative;
    transition: all 0.18s ease;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 156px;
    box-shadow: 0 3px 10px rgba(15, 23, 42, 0.04);
}
.light-table-box:hover {
    transform: translateY(-3px);
}
.light-table-box.st-available { border-color: #22c55e; background: #f0fdf4; }
.light-table-box.st-reserved  { border-color: #f59e0b; background: #fffbeb; }
.light-table-box.st-occupied  { border-color: #ef4444; background: #fef2f2; }

/* Ghế bao quanh bàn */
.chair-strip {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin: 4px 0;
}
.chair-bar {
    width: 22px;
    height: 6px;
    border-radius: 4px;
    background: #16a34a;
}
.st-reserved .chair-bar { background: #d97706; }
.st-occupied .chair-bar { background: #dc2626; }

.table-body-row {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    width: 100%;
    padding: 6px 0;
}
.chair-side-bar {
    width: 6px;
    height: 26px;
    border-radius: 4px;
    background: #16a34a;
}
.st-reserved .chair-side-bar { background: #d97706; }
.st-occupied .chair-side-bar { background: #dc2626; }

.sofa-icon svg {
    width: 28px;
    height: 28px;
    margin-bottom: 4px;
}
.st-available .sofa-icon svg { stroke: #16a34a; }
.st-reserved .sofa-icon svg  { stroke: #d97706; }
.st-occupied .sofa-icon svg  { stroke: #dc2626; }

.t-code-title {
    font-size: 19px;
    font-weight: 800;
    color: #0f172a;
}
.t-status-sub {
    font-size: 13px;
    font-weight: 600;
    color: #475569;
}
.t-cap-badge {
    position: absolute;
    top: 10px;
    right: 12px;
    font-size: 11px;
    font-weight: 700;
    color: #334155;
    background: rgba(255,255,255,0.85);
    border: 1px solid #cbd5e1;
    padding: 2px 8px;
    border-radius: 10px;
}
.t-area-badge {
    position: absolute;
    top: 10px;
    left: 12px;
    font-size: 11px;
    font-weight: 600;
    color: #64748b;
}

/* PHÒNG ĐẶC BIỆT (VIP & PRESIDENT) NỀN SÁNG RÕ NÉT */
.special-section-divider {
    border-top: 1px solid #e2e8f0;
    padding-top: 22px;
    margin-top: 16px;
}
.special-section-title {
    font-size: 19px;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 16px;
}
.special-rooms-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}
@media (max-width: 768px) { .special-rooms-grid { grid-template-columns: 1fr; } }

.special-room-card {
    border-radius: 16px;
    padding: 26px 20px;
    text-align: center;
    cursor: pointer;
    position: relative;
    transition: all 0.2s ease;
    border: 2px solid transparent;
}
.special-room-card:hover {
    transform: translateY(-3px);
}
.special-room-card.room-vip {
    background: #faf5ff;
    border-color: #a855f7;
    box-shadow: 0 6px 18px rgba(168, 85, 247, 0.08);
}
.room-vip .door-svg svg { stroke: #7e22ce; }
.room-vip .room-price   { color: #7e22ce; }

.special-room-card.room-president {
    background: #fffbeb;
    border-color: #d97706;
    box-shadow: 0 6px 18px rgba(217, 119, 6, 0.08);
}
.room-president .door-svg svg { stroke: #b45309; }
.room-president .room-price   { color: #b45309; }

.room-status-corner {
    position: absolute;
    top: 12px;
    right: 14px;
    font-size: 11.5px;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 999px;
}
.room-status-corner.st-available { background: #dcfce7; color: #15803d; }
.room-status-corner.st-reserved  { background: #fef3c7; color: #b45309; }
.room-status-corner.st-occupied  { background: #fee2e2; color: #b91c1c; }

.door-svg svg { width: 34px; height: 34px; margin-bottom: 8px; }
.room-name-title { font-size: 20px; font-weight: 800; color: #0f172a; text-transform: uppercase; margin-bottom: 6px; }
.room-price { font-size: 24px; font-weight: 800; margin-bottom: 4px; }
.room-sub-note { font-size: 13px; font-weight: 600; color: #64748b; }
</style>

<div class="light-board-wrap">
    <?php if ($flashSuccess !== ''): ?>
        <div class="alert alert-success py-2 fw-semibold"><?= htmlspecialchars($flashSuccess) ?></div>
    <?php endif; ?>
    <?php if ($flashError !== ''): ?>
        <div class="alert alert-danger py-2 fw-semibold"><?= htmlspecialchars($flashError) ?></div>
    <?php endif; ?>

    <!-- Thanh công cụ trên cùng -->
    <div class="board-top-bar">
        <div>
            <h2 class="board-title">
                Sơ đồ bàn – <?= $selectedFloor > 0 ? 'Floor ' . $selectedFloor : 'Tất cả tầng' ?>
            </h2>
            <div class="small text-muted mt-1">
                Ngày kiểm tra lịch: <strong class="text-primary"><?= date('d/m/Y', strtotime($selectedDate)) ?></strong>
                <?= $isToday ? '(Hôm nay - Đồng bộ trực tiếp với POS)' : '(Xem & Đặt lịch trước)' ?>
            </div>
        </div>

        <form method="GET" action="index.php" class="light-filter-group" id="boardFilterForm">
            <input type="hidden" name="c" value="booking">
            <input type="hidden" name="a" value="index">

            <div>
                <input type="date" name="date" class="light-input"
                       value="<?= htmlspecialchars($selectedDate) ?>"
                       onchange="document.getElementById('boardFilterForm').submit();">
            </div>

            <div>
                <select name="floor" class="light-select" onchange="document.getElementById('boardFilterForm').submit();">
                    <option value="0" <?= $selectedFloor === 0 ? 'selected' : '' ?>>Tất cả các tầng</option>
                    <?php foreach ($floorsSummary as $fl): ?>
                        <?php $fNum = (int)$fl['floor_number']; ?>
                        <option value="<?= $fNum ?>" <?= $selectedFloor === $fNum ? 'selected' : '' ?>>
                            Floor <?= $fNum ?> (<?= (int)$fl['standard_tables'] ?> bàn • <?= (int)$fl['vip_rooms'] + (int)$fl['premium_rooms'] ?> phòng)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <select name="area_id" class="light-select" onchange="document.getElementById('boardFilterForm').submit();">
                    <option value="0">-- Tất cả khu vực --</option>
                    <?php foreach ($areas as $ar): ?>
                        <?php if ($selectedFloor > 0 && (int)$ar['floor_number'] !== $selectedFloor) continue; ?>
                        <option value="<?= (int)$ar['id'] ?>" <?= $selectedAreaId === (int)$ar['id'] ? 'selected' : '' ?>>
                            [Tầng <?= (int)$ar['floor_number'] ?>] <?= htmlspecialchars($ar['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if ($isAdmin): ?>
                <button type="button" class="btn btn-warning btn-sm fw-bold px-3 py-2"
                        data-bs-toggle="modal" data-bs-target="#adminCreateModal">
                    + Thêm Bàn / Phòng (ADMIN)
                </button>
                <a href="index.php?c=booking&a=manage" class="btn btn-outline-dark btn-sm fw-bold px-3 py-2">
                    ⚙️ Setup Bàn & Phòng
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- 4 Badge Trạng thái -->
    <div class="status-legend-bar">
        <span class="legend-pill legend-available">Trống</span>
        <span class="legend-pill legend-reserved">Đã đặt</span>
        <span class="legend-pill legend-occupied">Đang dùng</span>
        <span class="legend-pill legend-vip">VIP</span>
    </div>

    <!-- PHẦN 1: BÀN TIÊU CHUẨN (CÓ GHẾ 4-8-12 BAO QUANH + PHÂN TRANG) -->
    <?php if (empty($standardTables)): ?>
        <div class="text-center text-muted py-4">Chưa có bàn tiêu chuẩn nào ở khu vực/tầng này.</div>
    <?php else: ?>
        <div class="light-tables-grid">
            <?php foreach ($standardTables as $t): ?>
                <?php
                $eff     = $t['effective_status'] ?? 'Available';
                $stClass = $eff === 'Occupied' ? 'st-occupied' : ($eff === 'Reserved' ? 'st-reserved' : 'st-available');
                $stText  = $eff === 'Occupied' ? 'Đang dùng' : ($eff === 'Reserved' ? 'Đã đặt' : 'Trống');
                $chairs  = renderChairsAroundLight((int)$t['capacity']);
                $dayBk   = $t['day_booking'] ?? null;

                $payload = [
                    'table_id'       => (int)$t['table_id'],
                    'table_name'     => $t['table_name'],
                    'table_type'     => 'Standard',
                    'capacity'       => (int)$t['capacity'],
                    'area_id'        => (int)$t['area_id'],
                    'area_name'      => $t['area_name'],
                    'floor_number'   => (int)$t['floor_number'],
                    'eff_status'     => $eff,
                    'status_text'    => $stText,
                    'deposit_default'=> TableModel::calculateDepositByTable($t, (int)$t['capacity']),
                    'active_order_id'=> (int)($t['active_order_id'] ?? 0),
                    'active_subtotal'=> (float)($t['active_subtotal'] ?? 0),
                    'booking_id'     => (int)($dayBk['id'] ?? 0),
                    'booking_code'   => $dayBk['booking_code'] ?? '',
                    'booking_status' => $dayBk['status'] ?? '',
                    'customer_name'  => $dayBk['customer_name'] ?? '',
                    'customer_phone' => $dayBk['customer_phone'] ?? '',
                    'booking_time'   => !empty($dayBk['booking_time']) ? date('H:i d/m/Y', strtotime($dayBk['booking_time'])) : '',
                    'guest_count'    => (int)($dayBk['guest_count'] ?? 0),
                    'deposit_amount' => (float)($dayBk['deposit_amount'] ?? 0),
                ];
                ?>
                <div class="light-table-box <?= $stClass ?>"
                     onclick='openTableActionModal(<?= htmlspecialchars(json_encode($payload, JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>)'>
                    <span class="t-area-badge"><?= htmlspecialchars($t['area_name']) ?></span>
                    <span class="t-cap-badge"><?= (int)$t['capacity'] ?> ghế</span>

                    <div class="chair-strip">
                        <?php for ($i = 0; $i < $chairs['top']; $i++): ?><span class="chair-bar"></span><?php endfor; ?>
                    </div>

                    <div class="table-body-row">
                        <?php if ($chairs['side'] > 0): ?><span class="chair-side-bar"></span><?php endif; ?>
                        <div>
                            <div class="sofa-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M4 11V8a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3v3"/>
                                    <path d="M2 11h20v5a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-5Z"/>
                                    <path d="M4 18v2"/><path d="M20 18v2"/>
                                </svg>
                            </div>
                            <div class="t-code-title"><?= htmlspecialchars($t['table_name']) ?></div>
                            <div class="t-status-sub">
                                <?= $stText ?>
                                <?php if ($eff === 'Reserved' && !empty($dayBk['booking_time'])): ?>
                                    (<?= date('H:i', strtotime($dayBk['booking_time'])) ?>)
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if ($chairs['side'] > 0): ?><span class="chair-side-bar"></span><?php endif; ?>
                    </div>

                    <div class="chair-strip">
                        <?php for ($i = 0; $i < $chairs['bottom']; $i++): ?><span class="chair-bar"></span><?php endfor; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="special-section-divider">
        <h3 class="special-section-title">Phòng đặc biệt</h3>
        <?php if (empty($specialRooms)): ?>
            <div class="text-muted small">Tầng/Khu vực này hiện chưa có Phòng VIP hoặc Phòng Thượng Hạng.</div>
        <?php else: ?>
            <div class="special-rooms-grid">
                <?php foreach ($specialRooms as $rm): ?>
                    <?php
                    $rType     = $rm['table_type'] ?? 'VIP';
                    $isPrem    = ($rType === 'Premium');
                    $roomClass = $isPrem ? 'room-president' : 'room-vip';
                    $minDep    = $isPrem ? TableModel::DEPOSIT_PREMIUM : TableModel::DEPOSIT_VIP;
                    $eff       = $rm['effective_status'] ?? 'Available';
                    $stClass   = $eff === 'Occupied' ? 'st-occupied' : ($eff === 'Reserved' ? 'st-reserved' : 'st-available');
                    $stText    = $eff === 'Occupied' ? 'Đang dùng' : ($eff === 'Reserved' ? 'Đã đặt' : 'Trống');
                    $dayBk     = $rm['day_booking'] ?? null;

                    $payload = [
                        'table_id'       => (int)$rm['table_id'],
                        'table_name'     => $rm['table_name'],
                        'table_type'     => $rType,
                        'capacity'       => (int)$rm['capacity'],
                        'area_id'        => (int)$rm['area_id'],
                        'area_name'      => $rm['area_name'],
                        'floor_number'   => (int)$rm['floor_number'],
                        'eff_status'     => $eff,
                        'status_text'    => $stText,
                        'deposit_default'=> $minDep,
                        'active_order_id'=> (int)($rm['active_order_id'] ?? 0),
                        'active_subtotal'=> (float)($rm['active_subtotal'] ?? 0),
                        'booking_id'     => (int)($dayBk['id'] ?? 0),
                        'booking_code'   => $dayBk['booking_code'] ?? '',
                        'booking_status' => $dayBk['status'] ?? '',
                        'customer_name'  => $dayBk['customer_name'] ?? '',
                        'customer_phone' => $dayBk['customer_phone'] ?? '',
                        'booking_time'   => !empty($dayBk['booking_time']) ? date('H:i d/m/Y', strtotime($dayBk['booking_time'])) : '',
                        'guest_count'    => (int)($dayBk['guest_count'] ?? 0),
                        'deposit_amount' => (float)($dayBk['deposit_amount'] ?? 0),
                    ];
                    ?>
                    <div class="special-room-card <?= $roomClass ?>"
                         onclick='openTableActionModal(<?= htmlspecialchars(json_encode($payload, JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>)'>
                        <span class="room-status-corner <?= $stClass ?>">
                            <?= $stText ?> • <?= (int)$rm['capacity'] ?> ghế
                        </span>

                        <div class="door-svg">
                            <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M13 4h3a2 2 0 0 1 2 2v14"/>
                                <path d="M2 20h3"/><path d="M13 20h9"/>
                                <path d="M10 12v.01"/>
                                <path d="M13 4.562v16.157a1 1 0 0 1-1.242.97L5 20V5.562a2 2 0 0 1 1.515-1.94l4-1A2 2 0 0 1 13 4.561Z"/>
                            </svg>
                        </div>

                        <div class="room-name-title"><?= htmlspecialchars($rm['table_name']) ?></div>
                        <div class="room-price"><?= number_format($minDep, 0, ',', '.') ?>đ</div>
                        <div class="room-sub-note">Tiền cọc tối thiểu</div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- POPUP ĐẶT BÀN / CHECK-IN CUKCUK / XÓA & SỬA NHANH (ADMIN) NỀN SÁNG -->
<div class="modal fade" id="tableActionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-white text-dark">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-bold" id="modalTableTitle">B01</h5>
                    <div class="small text-muted" id="modalTableSubtitle">Tầng 1 • Sức chứa 4 ghế</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <!-- 1. KHI BÀN TRỐNG: HIỆN FORM ĐẶT BÀN -->
                <div id="modalAvailableSection" style="display:none;">
                    <?php if ($isToday): ?>
                        <div class="d-flex justify-content-between align-items-center bg-light p-3 rounded-3 border mb-3">
                            <div>
                                <div class="fw-bold text-success">Khách vào ngồi dùng bữa ngay?</div>
                                <div class="small text-muted">Mở order trực tiếp trên màn hình POS</div>
                            </div>
                            <a href="#" id="modalOpenPosBtn" class="btn btn-success btn-sm fw-bold px-3">
                                🍽️ Mở POS gọi món
                            </a>
                        </div>
                    <?php endif; ?>

                    <h6 class="fw-bold text-primary mb-3">📅 Đặt lịch giữ bàn / phòng (Ngày <?= date('d/m/Y', strtotime($selectedDate)) ?>)</h6>
                    <form method="POST" action="index.php?c=booking&a=vip">
                        <input type="hidden" name="action" value="create">
                        <input type="hidden" name="redirect_to" value="index">
                        <input type="hidden" name="return_floor" value="<?= (int)$selectedFloor ?>">
                        <input type="hidden" name="return_date" value="<?= htmlspecialchars($selectedDate) ?>">
                        <input type="hidden" name="table_id" id="bookFormTableId">

                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Họ và tên khách hàng <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" class="form-control form-control-sm" maxlength="150" required>
                        </div>

                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Số điện thoại (10-11 số, bắt đầu 0) <span class="text-danger">*</span></label>
                                <input type="tel" name="phone" class="form-control form-control-sm"
                                       pattern="0[0-9]{9,10}" minlength="10" maxlength="11"
                                       placeholder="VD: 0901234567"
                                       title="Số điện thoại phải bắt đầu bằng số 0 và có 10-11 chữ số" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Số lượng khách <span class="text-danger">*</span></label>
                                <input type="number" name="guest_count" id="bookFormGuestCount" class="form-control form-control-sm" min="1" max="12" value="4" required>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Email</label>
                            <input type="email" name="email" class="form-control form-control-sm" placeholder="khachhang@email.com">
                        </div>

                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Giờ khách đến <span class="text-danger">*</span></label>
                                <input type="datetime-local" name="booking_time" id="bookFormTime" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Tiền đặt cọc (VNĐ) <span class="text-danger">*</span></label>
                                <input type="number" name="deposit_amount" id="bookFormDeposit" class="form-control form-control-sm text-danger fw-bold" min="0" step="10000" required>
                            </div>
                        </div>
                        <div class="small text-primary fw-semibold mb-2" id="bookFormRuleNote"></div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Yêu cầu đặc biệt</label>
                            <input type="text" name="special_request" class="form-control form-control-sm" placeholder="VD: Sinh nhật, ghế trẻ em...">
                        </div>

                        <button type="submit" class="btn btn-primary w-100 fw-bold">
                            Xác Nhận Đặt Bàn / Phòng
                        </button>
                    </form>
                </div>

                <!-- 2. KHI BÀN ĐÃ ĐẶT / ĐANG DÙNG -->
                <div id="modalBusySection" style="display:none;">
                    <div class="alert alert-warning mb-3">
                        <div class="fw-bold mb-1">⚠️ Bàn/Phòng này đã có lịch đặt hoặc đang phục vụ!</div>
                        <div class="small" id="busyAlertDetails"></div>
                    </div>
                    <div id="busyCukCukActions" class="d-flex flex-wrap gap-2"></div>
                </div>

                <!-- 3. QUẢN TRỊ SỬA / XÓA NHANH (CHỈ ADMIN) -->
                <?php if ($isAdmin): ?>
                    <hr class="my-3">
                    <div class="bg-light p-3 rounded-3 border">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small fw-bold text-dark">🛠️ Sửa / Xóa bàn này (ADMIN ONLY)</span>
                            <form method="POST" action="index.php?c=booking&a=manage"
                                  onsubmit="return confirm('Xác nhận xóa vĩnh viễn bàn/phòng này khỏi sơ đồ?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="redirect_to" value="index">
                                <input type="hidden" name="return_floor" value="<?= (int)$selectedFloor ?>">
                                <input type="hidden" name="return_date" value="<?= htmlspecialchars($selectedDate) ?>">
                                <input type="hidden" name="id" id="adminDeleteTableId">
                                <button type="submit" class="btn btn-outline-danger btn-sm py-0 px-2">🗑️ Xóa bàn/phòng</button>
                            </form>
                        </div>

                        <form method="POST" action="index.php?c=booking&a=manage" class="row g-2">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="redirect_to" value="index">
                            <input type="hidden" name="return_floor" value="<?= (int)$selectedFloor ?>">
                            <input type="hidden" name="return_date" value="<?= htmlspecialchars($selectedDate) ?>">
                            <input type="hidden" name="id" id="adminEditTableId">

                            <div class="col-4">
                                <label class="form-label small mb-1">Mã Label</label>
                                <input type="text" name="table_name" id="adminEditTableName" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-4">
                                <label class="form-label small mb-1">Loại</label>
                                <select name="table_type" id="adminEditTableType" class="form-select form-select-sm">
                                    <option value="Standard">Bàn thường</option>
                                    <option value="VIP">Phòng VIP (5Tr)</option>
                                    <option value="Premium">President (10Tr)</option>
                                </select>
                            </div>
                            <div class="col-4">
                                <label class="form-label small mb-1">Số ghế</label>
                                <select name="capacity" id="adminEditTableCap" class="form-select form-select-sm">
                                    <option value="4">4 ghế</option>
                                    <option value="8">8 ghế</option>
                                    <option value="12">12 ghế</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small mb-1">Thuộc Khu vực / Tầng</label>
                                <select name="area_id" id="adminEditAreaId" class="form-select form-select-sm">
                                    <?php foreach ($areas as $ar): ?>
                                        <option value="<?= (int)$ar['id'] ?>">
                                            [Tầng <?= (int)$ar['floor_number'] ?>] <?= htmlspecialchars($ar['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 mt-2">
                                <button type="submit" class="btn btn-sm btn-dark w-100 fw-bold">Lưu cấu hình bàn/phòng</button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- MODAL TẠO BÀN / PHÒNG MỚI (ADMIN) -->
<?php if ($isAdmin): ?>
<div class="modal fade" id="adminCreateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-white text-dark">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">+ Tạo Bàn hoặc Phòng Đặc Biệt (ADMIN)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="index.php?c=booking&a=manage">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="redirect_to" value="index">
                    <input type="hidden" name="return_floor" value="<?= (int)$selectedFloor ?>">
                    <input type="hidden" name="return_date" value="<?= htmlspecialchars($selectedDate) ?>">

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Chọn Tầng & Khu vực</label>
                        <select name="area_id" class="form-select" required>
                            <?php foreach ($areas as $ar): ?>
                                <option value="<?= (int)$ar['id'] ?>" <?= (int)$ar['floor_number'] === $selectedFloor ? 'selected' : '' ?>>
                                    [Tầng <?= (int)$ar['floor_number'] ?>] <?= htmlspecialchars($ar['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Tên Label (VD: B07, VIP 02, PRESIDENT 02)</label>
                        <input type="text" name="table_name" class="form-control" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-7">
                            <label class="form-label small fw-semibold">Phân loại</label>
                            <select name="table_type" class="form-select">
                                <option value="Standard">🪑 Bàn tiêu chuẩn</option>
                                <option value="VIP">🚪 Phòng VIP (Cọc 5.000.000đ)</option>
                                <option value="Premium">🚪 Phòng Thượng Hạng / President (Cọc 10.000.000đ)</option>
                            </select>
                        </div>
                        <div class="col-5">
                            <label class="form-label small fw-semibold">Sức chứa</label>
                            <select name="capacity" class="form-select">
                                <option value="4">4 ghế</option>
                                <option value="8">8 ghế</option>
                                <option value="12">12 ghế</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 fw-bold">Tạo Lên Sơ Đồ</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
const SELECTED_DATE = <?= json_encode($selectedDate) ?>;
const RETURN_FLOOR  = <?= (int)$selectedFloor ?>;

function openTableActionModal(data) {
    const modalEl = document.getElementById('tableActionModal');
    const modal = new bootstrap.Modal(modalEl);

    const typeText = data.table_type === 'Premium'
        ? 'Phòng Thượng Hạng (President)'
        : (data.table_type === 'VIP' ? 'Phòng VIP' : 'Bàn tiêu chuẩn');

    document.getElementById('modalTableTitle').textContent = data.table_name + ' — ' + data.status_text;
    document.getElementById('modalTableSubtitle').textContent =
        'Floor ' + data.floor_number + ' • ' + data.area_name + ' • ' + typeText + ' (' + data.capacity + ' ghế)';

    const availSec = document.getElementById('modalAvailableSection');
    const busySec  = document.getElementById('modalBusySection');

    if (data.eff_status === 'Available') {
        availSec.style.display = 'block';
        busySec.style.display  = 'none';

        document.getElementById('bookFormTableId').value = data.table_id;
        const guestInput = document.getElementById('bookFormGuestCount');
        guestInput.max   = data.capacity;
        guestInput.value = Math.min(4, data.capacity);

        document.getElementById('bookFormTime').value = SELECTED_DATE + 'T19:00';

        const depInput = document.getElementById('bookFormDeposit');
        const ruleNote = document.getElementById('bookFormRuleNote');

        if (data.table_type === 'VIP') {
            depInput.value = 5000000;
            depInput.readOnly = true;
            ruleNote.textContent = '🔒 Rule bắt buộc Phòng VIP: Tiền cọc cố định 5.000.000đ.';
        } else if (data.table_type === 'Premium') {
            depInput.value = 10000000;
            depInput.readOnly = true;
            ruleNote.textContent = '🔒 Rule bắt buộc Phòng Thượng Hạng / President: Tiền cọc cố định 10.000.000đ.';
        } else {
            depInput.value = data.deposit_default || 50000;
            depInput.readOnly = false;
            ruleNote.textContent = '🪑 Bàn tiêu chuẩn (' + data.capacity + ' ghế): Có thể điều chỉnh tiền cọc.';
        }

        const openPosBtn = document.getElementById('modalOpenPosBtn');
        if (openPosBtn) {
            openPosBtn.href = 'index.php?c=pos&a=index&table_id=' + data.table_id;
        }
    } else {
        availSec.style.display = 'none';
        busySec.style.display  = 'block';

        const detailsEl = document.getElementById('busyAlertDetails');
        const actionsEl = document.getElementById('busyCukCukActions');
        actionsEl.innerHTML = '';

        if (data.eff_status === 'Occupied') {
            detailsEl.innerHTML = `
                <div>Trạng thái: <strong class="text-danger">Đang dùng bữa tại nhà hàng</strong></div>
                <div>Tạm tính hiện tại: <strong>${Number(data.active_subtotal || 0).toLocaleString('vi-VN')}đ</strong></div>
            `;
            actionsEl.innerHTML = `
                <a href="index.php?c=pos&a=index&table_id=${data.table_id}" class="btn btn-danger fw-bold w-100">
                    🍽️ Mở Màn Hình POS Của Bàn Này
                </a>
            `;
        } else {
            detailsEl.innerHTML = `
                <div>Mã đặt chỗ: <strong>${data.booking_code || 'Giữ chỗ'}</strong> (${data.booking_status || 'Reserved'})</div>
                <div>Khách hàng: <strong>${data.customer_name || 'Khách đặt trước'}</strong> — SĐT: <strong>${data.customer_phone || '---'}</strong></div>
                <div>Giờ hẹn: <strong>${data.booking_time || SELECTED_DATE}</strong> • Số khách: <strong>${data.guest_count || data.capacity}</strong></div>
                <div>Tiền cọc: <strong class="text-danger">${Number(data.deposit_amount || 0).toLocaleString('vi-VN')}đ</strong></div>
            `;

            if (data.booking_id > 0) {
                let btns = '';
                if (data.booking_status === 'Pending') {
                    btns += `
                        <form method="POST" action="index.php?c=booking&a=vip" class="flex-fill">
                            <input type="hidden" name="action" value="status">
                            <input type="hidden" name="redirect_to" value="index">
                            <input type="hidden" name="return_floor" value="${RETURN_FLOOR}">
                            <input type="hidden" name="return_date" value="${SELECTED_DATE}">
                            <input type="hidden" name="id" value="${data.booking_id}">
                            <button type="submit" name="status" value="Confirmed" class="btn btn-primary btn-sm w-100 fw-bold">
                                ✔️ Xác nhận đã nhận cọc
                            </button>
                        </form>
                    `;
                }
                if (data.booking_status === 'Confirmed') {
                    btns += `
                        <form method="POST" action="index.php?c=booking&a=vip" class="flex-fill">
                            <input type="hidden" name="action" value="status">
                            <input type="hidden" name="redirect_to" value="index">
                            <input type="hidden" name="return_floor" value="${RETURN_FLOOR}">
                            <input type="hidden" name="return_date" value="${SELECTED_DATE}">
                            <input type="hidden" name="id" value="${data.booking_id}">
                            <input type="hidden" name="table_id" value="${data.table_id}">
                            <button type="submit" name="status" value="CheckedIn" class="btn btn-success btn-sm w-100 fw-bold">
                                🍽️ Khách nhận bàn (Check-in POS)
                            </button>
                        </form>
                    `;
                }
                btns += `
                    <form method="POST" action="index.php?c=booking&a=vip" onsubmit="return confirm('Hủy lịch đặt bàn này?');">
                        <input type="hidden" name="action" value="status">
                        <input type="hidden" name="redirect_to" value="index">
                        <input type="hidden" name="return_floor" value="${RETURN_FLOOR}">
                        <input type="hidden" name="return_date" value="${SELECTED_DATE}">
                        <input type="hidden" name="id" value="${data.booking_id}">
                        <button type="submit" name="status" value="Cancelled" class="btn btn-outline-danger btn-sm fw-bold">
                            Hủy lịch
                        </button>
                    </form>
                `;
                actionsEl.innerHTML = btns;
            }
        }
    }

    const delIdEl = document.getElementById('adminDeleteTableId');
    if (delIdEl) {
        delIdEl.value = data.table_id;
        document.getElementById('adminEditTableId').value   = data.table_id;
        document.getElementById('adminEditTableName').value = data.table_name;
        document.getElementById('adminEditTableType').value = data.table_type;
        document.getElementById('adminEditTableCap').value  = data.capacity;
        document.getElementById('adminEditAreaId').value    = data.area_id;
    }

    modal.show();
}
</script>