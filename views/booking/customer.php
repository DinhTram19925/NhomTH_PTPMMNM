<?php
$selectedFloor  = (int)($selectedFloor ?? 1);
$selectedAreaId = (int)($selectedAreaId ?? 0);
$selectedDate   = $selectedDate ?? date('Y-m-d');
$floorsSummary  = $floorsSummary ?? [];
$areas          = $areas ?? [];
$success        = $success ?? '';
$error          = $error ?? '';
$isTestPaid     = isset($_GET['test_paid']) && $_GET['test_paid'] === '1';


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

function renderCustChairsLight(int $capacity): array {
    $cap = in_array($capacity, [4, 8, 12], true) ? $capacity : 4;
    if ($cap === 4) return ['top' => 2, 'bottom' => 2, 'side' => 0];
    if ($cap === 8) return ['top' => 3, 'bottom' => 3, 'side' => 1];
    return ['top' => 5, 'bottom' => 5, 'side' => 1];
}
?>

<style>
.cust-light-wrap {
    max-width: 1150px;
    margin: 28px auto;
    background: #ffffff;
    color: #1e293b;
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    padding: 26px;
    box-shadow: 0 6px 22px rgba(15, 23, 42, 0.05);
}
.cust-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; margin-bottom: 18px; }
.cust-grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
@media (max-width: 768px) { .cust-grid-3, .cust-grid-2 { grid-template-columns: 1fr; } }

.cust-table-box {
    background: #f0fdf4; border: 2px solid #22c55e; border-radius: 16px;
    padding: 18px 14px; text-align: center; cursor: pointer; position: relative; min-height: 150px;
    display: flex; flex-direction: column; align-items: center; justify-content: center;
}
.cust-table-box.st-reserved { background: #fffbeb; border-color: #f59e0b; cursor: not-allowed; opacity: 0.75; }
.cust-table-box.st-occupied { background: #fef2f2; border-color: #ef4444; cursor: not-allowed; opacity: 0.75; }

.c-chair-row { display: flex; gap: 8px; margin: 4px 0; }
.c-chair-bar { width: 20px; height: 6px; border-radius: 4px; background: #16a34a; }
.st-reserved .c-chair-bar { background: #d97706; }
.st-occupied .c-chair-bar { background: #dc2626; }
.c-chair-side { width: 6px; height: 24px; border-radius: 4px; background: #16a34a; }
.st-reserved .c-chair-side { background: #d97706; }
.st-occupied .c-chair-side { background: #dc2626; }

.cust-room-box {
    border-radius: 16px; padding: 24px 20px; text-align: center; cursor: pointer; position: relative;
}
.cust-room-box.room-vip { background: #faf5ff; border: 2px solid #a855f7; }
.cust-room-box.room-president { background: #fffbeb; border: 2px solid #d97706; }
.cust-room-box.is-busy { opacity: 0.65; cursor: not-allowed; }
</style>

<div class="container">
    <div class="cust-light-wrap">
        <?php if ($success !== ''): ?>
            <div class="text-center py-4">
                <span class="badge bg-warning text-dark mb-2">TEST MODE — MÔ PHỎNG ĐẶT CỌC</span>
                <h3 class="text-success fw-bold">Đã Tạo Phiếu Đặt Bàn Thành Công!</h3>
                <p class="mb-1">Mã đặt bàn: <strong class="text-primary fs-5"><?= htmlspecialchars($success) ?></strong></p>
                <h3 class="text-danger fw-bold my-3">Tiền đặt cọc: <?= number_format((float)$depositAmount, 0, ',', '.') ?>đ</h3>

                <?php if (!$isTestPaid): ?>
                    <form method="POST" action="index.php?c=booking&a=customer">
                        <input type="hidden" name="action" value="confirm_test_deposit">
                        <input type="hidden" name="booking_code" value="<?= htmlspecialchars($success) ?>">
                        <button type="submit" class="btn btn-warning fw-bold px-4 py-2">
                            Xác nhận đặt cọc mô phỏng (TEST MODE)
                        </button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-success d-inline-block px-4">
                        Đã ghi nhận xác nhận đặt cọc! Lễ tân sẽ giữ bàn cho quý khách.
                    </div>
                <?php endif; ?>
                <div class="mt-3">
                    <a href="index.php?c=booking&a=customer" class="btn btn-outline-secondary btn-sm">← Quay lại trang đặt bàn</a>
                </div>
            </div>
        <?php else: ?>
            <?php if ($error !== ''): ?>
                <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="d-flex flex-wrap justify-content-between align-items-center pb-3 mb-3 border-bottom gap-2">
                <div>
                    <h3 class="fw-bold mb-1">Đặt Bàn Trực Quan – Floor <?= (int)$selectedFloor ?></h3>
                    <div class="small text-muted">Chọn ngày, tầng/khu vực và click vào bàn hoặc phòng đang <strong>Trống</strong> để đặt lịch.</div>
                </div>

                <form method="GET" action="index.php" class="d-flex flex-wrap gap-2" id="custFilterForm">
                    <input type="hidden" name="c" value="booking">
                    <input type="hidden" name="a" value="customer">
                    <input type="date" name="date" class="form-control form-control-sm" style="width:auto;"
                           min="<?= date('Y-m-d') ?>"
                           value="<?= htmlspecialchars($selectedDate) ?>"
                           onchange="document.getElementById('custFilterForm').submit();">

                    <select name="floor" class="form-select form-select-sm" style="width:auto;" onchange="document.getElementById('custFilterForm').submit();">
                        <?php foreach ($floorsSummary as $fl): ?>
                            <?php $fNum = (int)$fl['floor_number']; ?>
                            <option value="<?= $fNum ?>" <?= $selectedFloor === $fNum ? 'selected' : '' ?>>Floor <?= $fNum ?></option>
                        <?php endforeach; ?>
                    </select>

                    <select name="area_id" class="form-select form-select-sm" style="width:auto;" onchange="document.getElementById('custFilterForm').submit();">
                        <option value="0">-- Tất cả khu vực --</option>
                        <?php foreach ($areas as $ar): ?>
                            <?php if ($selectedFloor > 0 && (int)$ar['floor_number'] !== $selectedFloor) continue; ?>
                            <option value="<?= (int)$ar['id'] ?>" <?= $selectedAreaId === (int)$ar['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($ar['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>

            <!-- Danh sách Bàn tiêu chuẩn -->
            <div class="cust-grid-3">
                <?php foreach ($standardTables as $t): ?>
                    <?php
                    $eff     = $t['effective_status'] ?? 'Available';
                    $stClass = $eff === 'Occupied' ? 'st-occupied' : ($eff === 'Reserved' ? 'st-reserved' : 'st-available');
                    $stText  = $eff === 'Occupied' ? 'Đang dùng' : ($eff === 'Reserved' ? 'Đã đặt' : 'Trống');
                    $chairs  = renderCustChairsLight((int)$t['capacity']);
                    $dep     = TableModel::calculateDepositByTable($t, (int)$t['capacity']);
                    ?>
                    <div class="cust-table-box <?= $stClass ?>"
                         onclick="handleCustomerPick(<?= (int)$t['table_id'] ?>, '<?= htmlspecialchars($t['table_name'], ENT_QUOTES) ?>', 'Standard', <?= (int)$t['capacity'] ?>, <?= (float)$dep ?>, '<?= $eff ?>')">
                        <span class="badge bg-white text-dark border" style="position:absolute;top:10px;right:12px;"><?= (int)$t['capacity'] ?> ghế</span>

                        <div class="c-chair-row">
                            <?php for ($i = 0; $i < $chairs['top']; $i++): ?><span class="c-chair-bar"></span><?php endfor; ?>
                        </div>
                        <div class="d-flex align-items-center justify-content-center gap-3 my-1">
                            <?php if ($chairs['side'] > 0): ?><span class="c-chair-side"></span><?php endif; ?>
                            <div>
                                <div class="fw-bold fs-5 text-dark"><?= htmlspecialchars($t['table_name']) ?></div>
                                <div class="small text-secondary fw-semibold"><?= $stText ?></div>
                            </div>
                            <?php if ($chairs['side'] > 0): ?><span class="c-chair-side"></span><?php endif; ?>
                        </div>
                        <div class="c-chair-row">
                            <?php for ($i = 0; $i < $chairs['bottom']; $i++): ?><span class="c-chair-bar"></span><?php endfor; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <!-- Danh sách Phòng đặc biệt -->
            <h5 class="fw-bold mb-3 pt-3 border-top">Phòng đặc biệt</h5>
            <div class="cust-grid-2">
                <?php foreach ($specialRooms as $rm): ?>
                    <?php
                    $rType  = $rm['table_type'] ?? 'VIP';
                    $isPrem = ($rType === 'Premium');
                    $minDep = $isPrem ? TableModel::DEPOSIT_PREMIUM : TableModel::DEPOSIT_VIP;
                    $eff    = $rm['effective_status'] ?? 'Available';
                    $stText = $eff === 'Occupied' ? 'Đang dùng' : ($eff === 'Reserved' ? 'Đã đặt' : 'Trống');
                    ?>
                    <div class="cust-room-box <?= $isPrem ? 'room-president' : 'room-vip' ?> <?= $eff !== 'Available' ? 'is-busy' : '' ?>"
                         onclick="handleCustomerPick(<?= (int)$rm['table_id'] ?>, '<?= htmlspecialchars($rm['table_name'], ENT_QUOTES) ?>', '<?= $rType ?>', <?= (int)$rm['capacity'] ?>, <?= (float)$minDep ?>, '<?= $eff ?>')">
                        <span class="badge <?= $eff === 'Available' ? 'bg-success' : 'bg-warning text-dark' ?>" style="position:absolute;top:14px;right:16px;">
                            <?= $stText ?> • <?= (int)$rm['capacity'] ?> ghế
                        </span>
                        <div class="fw-bold fs-4 text-dark text-uppercase mb-1">🚪 <?= htmlspecialchars($rm['table_name']) ?></div>
                        <div class="fw-bold fs-3" style="color: <?= $isPrem ? '#b45309' : '#7e22ce' ?>;">
                            <?= number_format($minDep, 0, ',', '.') ?>đ
                        </div>
                        <div class="small text-muted fw-semibold">Tiền cọc tối thiểu</div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- POPUP ĐẶT BÀN CHO KHÁCH HÀNG -->
<div class="modal fade" id="customerBookingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-white text-dark">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="custModalTitle">Đặt bàn</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="index.php?c=booking&a=customer">
                    <input type="hidden" name="action" value="create">
                    <input type="hidden" name="booking_date" value="<?= htmlspecialchars($selectedDate) ?>">
                    <input type="hidden" name="table_id" id="custFormTableId">

                    <div class="alert alert-warning py-2 small">
                        <div>Vị trí: <strong id="custFormTableLabel"></strong></div>
                        <div>Tiền cọc bắt buộc: <strong id="custFormDepositLabel" class="text-danger fs-6"></strong></div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Họ và tên <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control" maxlength="150" required>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Số điện thoại (10-11 số) <span class="text-danger">*</span></label>
                            <input type="tel" name="phone" class="form-control"
                                   pattern="0[0-9]{9,10}" minlength="10" maxlength="11"
                                   placeholder="0901234567"
                                   title="Phải bắt đầu bằng số 0 và có từ 10-11 chữ số" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Số lượng khách <span class="text-danger">*</span></label>
                            <input type="number" name="guest_count" id="custFormGuestCount" class="form-control" min="1" max="12" value="4" required>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Email</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Thời gian đến <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="booking_time" id="custFormTime" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Yêu cầu đặc biệt</label>
                        <textarea name="special_request" class="form-control" rows="2"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 fw-bold">Gửi Yêu Cầu Đặt Bàn</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
const CUST_SELECTED_DATE = <?= json_encode($selectedDate) ?>;

function handleCustomerPick(tableId, tableName, tableType, capacity, deposit, effStatus) {
    if (effStatus !== 'Available') {
        alert('Bàn/Phòng [' + tableName + '] đã có khách đặt hoặc đang sử dụng trong ngày này. Quý khách vui lòng chọn bàn đang Trống!');
        return;
    }

    document.getElementById('custModalTitle').textContent = 'Đặt chỗ: ' + tableName;
    document.getElementById('custFormTableId').value = tableId;
    document.getElementById('custFormTableLabel').textContent = tableName + ' (Tối đa ' + capacity + ' ghế)';
    document.getElementById('custFormDepositLabel').textContent = Number(deposit).toLocaleString('vi-VN') + 'đ';

    const guestEl = document.getElementById('custFormGuestCount');
    guestEl.max = capacity;
    guestEl.value = Math.min(4, capacity);

    document.getElementById('custFormTime').value = CUST_SELECTED_DATE + 'T19:00';

    const modal = new bootstrap.Modal(document.getElementById('customerBookingModal'));
    modal.show();
}
</script>