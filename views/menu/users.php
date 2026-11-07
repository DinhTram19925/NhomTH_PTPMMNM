<?php
$isAdmin = $isAdmin ?? false;
$currentUserId = $currentUserId ?? (int)($_SESSION['user']['id'] ?? 0);
$users = $users ?? [];
$roles = $roles ?? [];
$shifts = $shifts ?? [];
$schedules = $schedules ?? [];
$weekDays = $weekDays ?? [];
$weekStart = $weekStart ?? date('Y-m-d');
$weekEnd = $weekEnd ?? date('Y-m-d');
$weekGrid = $weekGrid ?? [];
$myTodayShifts = $myTodayShifts ?? [];
$myNextShift = $myNextShift ?? null;
$myWeekHours = $myWeekHours ?? 0;
$myWeekRegistered = $myWeekRegistered ?? 0;
$myWeekCompleted = $myWeekCompleted ?? 0;
$payrollSummary = $payrollSummary ?? ['total_amount' => 0, 'paid_amount' => 0, 'unpaid_amount' => 0, 'total_hours' => 0, 'staff_rows' => []];
$selectedMonth = $selectedMonth ?? date('Y-m');
$auditLogs = $auditLogs ?? [];
$filterDate = $filterDate ?? date('Y-m-d');
$severityFilter = $severityFilter ?? '';
$activeTab = $_GET['tab'] ?? ($isAdmin ? 'staff' : 'shifts');

function getInitials(string $fullName): string {
    $words = preg_split('/\s+/u', trim($fullName));
    if (!$words) return 'NV';
    if (count($words) === 1) return mb_strtoupper(mb_substr($words[0], 0, 2, 'UTF-8'), 'UTF-8');
    $first = mb_substr($words[0], 0, 1, 'UTF-8');
    $last = mb_substr($words[count($words) - 1], 0, 1, 'UTF-8');
    return mb_strtoupper($first . $last, 'UTF-8');
}

function getRoleBadgeStyle(string $roleCode): string {
    return match (strtoupper($roleCode)) {
        'ADMIN'   => 'background:#fef3c7; color:#92400e; border:1px solid #fde68a;',
        'HOSTESS' => 'background:#e0e7ff; color:#1e40af; border:1px solid #c7d2fe;',
        'WAITER'  => 'background:#dcfce7; color:#166534; border:1px solid #bbf7d0;',
        'CHEF'    => 'background:#fee2e2; color:#991b1b; border:1px solid #fecaca;',
        'STOCK'   => 'background:#f3e8ff; color:#6b21a8; border:1px solid #e9d5ff;',
        default   => 'background:#f1f5f9; color:#334155; border:1px solid #cbd5e1;'
    };
}

$totalStaffCount = count($users);
$activeStaffCount = 0;
$inactiveStaffCount = 0;
foreach ($users as $u) {
    if (!empty($u['is_active'])) $activeStaffCount++;
    else $inactiveStaffCount++;
}

$prevWeekDate = date('Y-m-d', strtotime($weekStart . ' -7 days'));
$nextWeekDate = date('Y-m-d', strtotime($weekStart . ' +7 days'));
?>

<style>
:root {
    --hr-navy: #17243b;
    --hr-navy-dark: #0f172a;
    --hr-gold: #c69b45;
    --hr-gold-soft: #fef9eb;
    --hr-bg: #f8fafc;
    --hr-card: #ffffff;
    --hr-border: #e2e8f0;
    --hr-text: #1e293b;
    --hr-muted: #64748b;
    --hr-green: #15803d;
    --hr-green-bg: #dcfce7;
    --hr-red: #b91c1c;
    --hr-red-bg: #fee2e2;
    --hr-amber: #b45309;
    --hr-amber-bg: #fef3c7;
}

.hr-workspace {
    font-family: 'Be Vietnam Pro', sans-serif;
    background: var(--hr-bg);
    color: var(--hr-text);
    padding: 16px 24px 48px;
}

.hr-flash {
    padding: 12px 16px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 700;
    margin-bottom: 16px;
}
.hr-flash-ok { background: var(--hr-green-bg); color: var(--hr-green); border: 1px solid #86efac; }
.hr-flash-err { background: var(--hr-red-bg); color: var(--hr-red); border: 1px solid #fca5a5; }

.hr-top-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr) 360px;
    gap: 16px;
    margin-bottom: 20px;
    align-items: stretch;
}
@media (max-width: 1280px) {
    .hr-top-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 768px) {
    .hr-top-grid { grid-template-columns: 1fr; }
}

.hr-stat-card {
    background: var(--hr-card);
    border: 1px solid var(--hr-border);
    border-radius: 14px;
    padding: 18px 20px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.03);
}
.hr-stat-label {
    font-size: 12.5px;
    font-weight: 700;
    color: var(--hr-muted);
    text-transform: uppercase;
    letter-spacing: 0.3px;
}
.hr-stat-value-row {
    display: flex;
    align-items: baseline;
    gap: 10px;
    margin: 10px 0;
}
.hr-stat-number {
    font-size: 30px;
    font-weight: 800;
    color: var(--hr-navy);
    line-height: 1;
}
.hr-stat-pill {
    font-size: 11px;
    font-weight: 700;
    padding: 3px 9px;
    border-radius: 20px;
}
.hr-stat-foot {
    border-top: 1px solid #f1f5f9;
    padding-top: 10px;
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    color: var(--hr-muted);
}

.hr-payroll-card {
    background: var(--hr-navy);
    color: #ffffff;
    border-radius: 14px;
    padding: 18px 20px;
    border: 1px solid var(--hr-navy-dark);
    box-shadow: 0 8px 22px rgba(23, 36, 59, 0.16);
}
.hr-payroll-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 6px;
}
.hr-payroll-title {
    font-size: 13px;
    font-weight: 800;
    color: #cbd5e1;
    text-transform: uppercase;
}
.hr-payroll-total {
    font-size: 26px;
    font-weight: 800;
    color: #e5bd69;
    margin: 4px 0 10px;
}
.hr-payroll-bar {
    height: 10px;
    background: rgba(255, 255, 255, 0.14);
    border-radius: 8px;
    overflow: hidden;
    margin-bottom: 10px;
}
.hr-payroll-bar-fill {
    height: 100%;
    background: var(--hr-gold);
    border-radius: 8px;
}
.hr-payroll-meta {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    color: #cbd5e1;
}

.hr-toolbar {
    background: var(--hr-card);
    border: 1px solid var(--hr-border);
    border-radius: 12px;
    padding: 10px 14px;
    margin-bottom: 18px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
}
.hr-tabs {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}
.hr-tab-btn {
    padding: 9px 16px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 700;
    border: 1px solid transparent;
    background: transparent;
    color: #475569;
    cursor: pointer;
    transition: all 0.15s ease;
}
.hr-tab-btn:hover {
    background: #f1f5f9;
    color: var(--hr-navy);
}
.hr-tab-btn.active {
    background: var(--hr-navy);
    color: #e5bd69;
}

.hr-btn {
    padding: 9px 16px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 700;
    border: 1px solid var(--hr-border);
    background: #ffffff;
    color: var(--hr-navy);
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.15s ease;
}
.hr-btn:hover {
    border-color: var(--hr-gold);
    background: var(--hr-gold-soft);
}
.hr-btn-gold {
    background: var(--hr-gold);
    border-color: #b38936;
    color: var(--hr-navy);
    font-weight: 800;
}
.hr-btn-gold:hover {
    background: var(--hr-navy);
    color: #e5bd69;
    border-color: var(--hr-navy);
}
.hr-btn-danger {
    color: var(--hr-red);
    border-color: #fca5a5;
    background: #fff5f5;
}

.hr-pane { display: none; }
.hr-pane.active { display: block; }

.hr-card {
    background: var(--hr-card);
    border: 1px solid var(--hr-border);
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(15, 23, 42, 0.03);
    margin-bottom: 20px;
}
.hr-card-bar {
    padding: 12px 16px;
    border-bottom: 1px solid var(--hr-border);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
    background: #ffffff;
}

.hr-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}
.hr-table thead th {
    background: #f8fafc;
    color: #475569;
    font-weight: 800;
    font-size: 11.5px;
    text-transform: uppercase;
    padding: 13px 16px;
    text-align: left;
    border-bottom: 1px solid var(--hr-border);
}
.hr-table tbody td {
    padding: 13px 16px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}
.hr-table tbody tr:hover {
    background: #f8fafc;
}

.hr-avatar {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: var(--hr-navy);
    color: #e5bd69;
    font-size: 12px;
    font-weight: 800;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    border: 2px solid #ffffff;
    box-shadow: 0 2px 5px rgba(15, 23, 42, 0.12);
}
.hr-avatar.me {
    background: var(--hr-gold);
    color: var(--hr-navy);
    border-color: var(--hr-navy);
}

.hr-week-grid {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}
.hr-week-grid th {
    background: var(--hr-navy);
    color: #ffffff;
    padding: 12px 10px;
    text-align: center;
    font-size: 12px;
    font-weight: 700;
    border-right: 1px solid rgba(255,255,255,0.1);
}
.hr-week-grid th.today-col {
    background: #1e3a5f;
    color: #e5bd69;
    border-bottom: 3px solid var(--hr-gold);
}
.hr-week-grid td {
    border: 1px solid var(--hr-border);
    vertical-align: top;
    padding: 10px;
    min-height: 110px;
    background: #ffffff;
}
.hr-week-grid td.today-cell {
    background: #fffdf7;
}
.hr-shift-slot {
    display: flex;
    flex-direction: column;
    gap: 8px;
    min-height: 90px;
    justify-content: space-between;
}
.hr-shift-avatars {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}
.hr-staff-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 8px 4px 4px;
    border-radius: 20px;
    background: #f8fafc;
    border: 1px solid #cbd5e1;
    font-size: 11.5px;
    font-weight: 700;
    color: var(--hr-navy);
}
.hr-staff-chip.is-me {
    background: var(--hr-gold-soft);
    border-color: var(--hr-gold);
}
.hr-staff-chip .hr-avatar {
    width: 24px;
    height: 24px;
    font-size: 10px;
}
.hr-slot-add-btn {
    width: 100%;
    padding: 5px;
    border: 1px dashed #cbd5e1;
    border-radius: 6px;
    background: transparent;
    color: var(--hr-muted);
    font-size: 11px;
    font-weight: 700;
    cursor: pointer;
}
.hr-slot-add-btn:hover {
    border-color: var(--hr-gold);
    color: var(--hr-navy);
    background: var(--hr-gold-soft);
}

.hr-personal-shell {
    background: #0b0d13;
    color: #f8fafc;
    border-radius: 18px;
    padding: 26px;
    border: 1px solid #1e293b;
    box-shadow: 0 14px 34px rgba(15, 23, 42, 0.25);
    margin-bottom: 24px;
}
.hr-personal-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 18px;
    border-bottom: 1px solid #1e293b;
    margin-bottom: 20px;
}
.hr-personal-user {
    display: flex;
    align-items: center;
    gap: 14px;
}
.hr-personal-avatar {
    width: 54px;
    height: 54px;
    border-radius: 50%;
    background: #e2e8f0;
    color: #0f172a;
    font-size: 18px;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
}
.hr-personal-status {
    background: rgba(21, 128, 61, 0.25);
    color: #4ade80;
    border: 1px solid rgba(74, 222, 128, 0.35);
    padding: 5px 14px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
}
.hr-today-box {
    background: #111622;
    border: 1px solid #1e293b;
    border-radius: 14px;
    padding: 20px;
    margin-bottom: 16px;
}
.hr-shift-BigTime {
    font-size: 32px;
    font-weight: 800;
    color: #38bdf8;
    margin: 8px 0 4px;
}
.hr-checkin-cta {
    display: block;
    width: 100%;
    background: #f8fafc;
    color: #0f172a;
    text-align: center;
    padding: 14px;
    border-radius: 30px;
    font-size: 14.5px;
    font-weight: 800;
    text-decoration: none;
    margin-top: 16px;
}
.hr-checkin-cta:hover {
    background: var(--hr-gold);
    color: #0f172a;
}
.hr-next-box {
    background: #111622;
    border: 1px solid #1e293b;
    border-radius: 14px;
    padding: 16px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}
.hr-personal-metrics {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    padding-top: 16px;
    border-top: 1px solid #1e293b;
}
.hr-personal-metrics strong {
    display: block;
    font-size: 22px;
    color: #ffffff;
    margin-top: 4px;
}

.hr-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.6);
    z-index: 1050;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 16px;
}
.hr-modal-backdrop.show { display: flex; }
.hr-modal-dialog {
    background: #ffffff;
    border-radius: 14px;
    width: 100%;
    max-width: 560px;
    overflow: hidden;
    box-shadow: 0 20px 50px rgba(15, 23, 42, 0.3);
}
.hr-modal-header {
    background: var(--hr-navy);
    color: #e5bd69;
    padding: 15px 20px;
    font-weight: 800;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.hr-modal-body { padding: 20px; }
.hr-input, .hr-select {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-size: 13px;
    font-family: inherit;
}
.hr-label {
    display: block;
    font-size: 12px;
    font-weight: 800;
    color: var(--hr-navy);
    margin-bottom: 5px;
}
.hr-time-inline {
    padding: 5px 8px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 12.5px;
    font-family: inherit;
    font-weight: 700;
    color: var(--hr-navy);
    width: 96px;
}
</style>

<div class="hr-workspace">

    <?php if (!empty($_SESSION['flash_msg'])): ?>
        <div class="hr-flash hr-flash-ok">
            <?= htmlspecialchars($_SESSION['flash_msg']) ?>
            <?php unset($_SESSION['flash_msg']); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['flash_err'])): ?>
        <div class="hr-flash hr-flash-err">
            <?= htmlspecialchars($_SESSION['flash_err']) ?>
            <?php unset($_SESSION['flash_err']); ?>
        </div>
    <?php endif; ?>

    <?php if (!$isAdmin): ?>
        <div class="hr-personal-shell">
            <div class="hr-personal-top">
                <div class="hr-personal-user">
                    <div class="hr-personal-avatar">
                        <?= htmlspecialchars(getInitials($_SESSION['user']['full_name'] ?? 'NV')) ?>
                    </div>
                    <div>
                        <div style="font-size: 20px; font-weight: 800;">
                            <?= htmlspecialchars($_SESSION['user']['full_name'] ?? 'Nhân viên') ?>
                        </div>
                        <div style="font-size: 13px; color: #94a3b8; margin-top: 2px;">
                            <?= htmlspecialchars($_SESSION['user']['role_code'] ?? '') ?>
                        </div>
                    </div>
                </div>
                <span class="hr-personal-status">Đang hoạt động</span>
            </div>

            <?php if (empty($myTodayShifts)): ?>
                <div class="hr-today-box" style="text-align: center; color: #94a3b8; padding: 28px;">
                    Hôm nay không có ca làm việc.
                </div>
            <?php else: ?>
                <?php foreach ($myTodayShifts as $myShift): ?>
                    <?php
                    $sTime = substr($myShift['start_time'], 0, 5);
                    $eTime = substr($myShift['end_time'], 0, 5);
                    ?>
                    <div class="hr-today-box">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div style="font-size: 16px; font-weight: 800;"><?= htmlspecialchars($myShift['shift_name']) ?></div>
                            <span class="hr-personal-status">
                                <?php
                                if (!empty($myShift['check_out'])) echo 'Đã hoàn thành ca';
                                elseif (!empty($myShift['check_in'])) echo 'Vào ca lúc ' . substr($myShift['check_in'], 0, 5);
                                else echo 'Đã phân công';
                                ?>
                            </span>
                        </div>
                        <div class="hr-shift-BigTime"><?= $sTime ?> – <?= $eTime ?></div>

                        <?php if (empty($myShift['check_in'])): ?>
                            <a href="index.php?c=menu&a=attendance&id=<?= (int)$myShift['id'] ?>&type=in&date=<?= date('Y-m-d') ?>"
                               class="hr-checkin-cta">
                                Chấm công vào ca
                            </a>
                        <?php elseif (empty($myShift['check_out'])): ?>
                            <a href="index.php?c=menu&a=attendance&id=<?= (int)$myShift['id'] ?>&type=out&date=<?= date('Y-m-d') ?>"
                               class="hr-checkin-cta" style="background: var(--hr-gold);">
                                Chấm công tan ca
                            </a>
                        <?php else: ?>
                            <div style="margin-top: 14px; text-align: center; padding: 10px; border-radius: 20px; background: rgba(74, 222, 128, 0.12); color: #4ade80; font-weight: 700; font-size: 13px;">
                                Đã hoàn thành: <?= substr($myShift['check_in'], 0, 5) ?> - <?= substr($myShift['check_out'], 0, 5) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <div class="hr-personal-metrics">
                <div>
                    <span style="font-size: 12px; color: #94a3b8;">Tổng giờ thực tế tuần</span>
                    <strong><?= number_format($myWeekHours, 1) ?> giờ</strong>
                </div>
                <div>
                    <span style="font-size: 12px; color: #94a3b8;">Số ca trong tuần</span>
                    <strong><?= str_pad((string)$myWeekRegistered, 2, '0', STR_PAD_LEFT) ?> ca</strong>
                </div>
                <div style="text-align: right;">
                    <span style="font-size: 12px; color: #94a3b8;">Đã hoàn thành</span>
                    <strong><?= str_pad((string)$myWeekCompleted, 2, '0', STR_PAD_LEFT) ?> ca</strong>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($isAdmin): ?>
        <div class="hr-top-grid">
            <div class="hr-stat-card">
                <div class="hr-stat-label">Tổng nhân sự hệ thống</div>
                <div class="hr-stat-value-row">
                    <span class="hr-stat-number"><?= $totalStaffCount ?></span>
                    <span class="hr-stat-pill" style="background: var(--hr-green-bg); color: var(--hr-green);">
                        <?= count($roles) ?> vai trò
                    </span>
                </div>
                <div class="hr-stat-foot">
                    <span>Đang làm việc: <strong><?= $activeStaffCount ?></strong></span>
                    <span>Tạm khóa: <strong><?= $inactiveStaffCount ?></strong></span>
                </div>
            </div>

            <div class="hr-stat-card">
                <div class="hr-stat-label">Ca làm việc ngày <?= date('d/m/Y', strtotime($filterDate)) ?></div>
                <div class="hr-stat-value-row">
                    <span class="hr-stat-number"><?= count($schedules) ?></span>
                    <span class="hr-stat-pill" style="background: var(--hr-amber-bg); color: var(--hr-amber);">
                        <?= count($shifts) ?> khung ca
                    </span>
                </div>
                <div class="hr-stat-foot">
                    <span>Đã chấm công: <strong><?= count(array_filter($schedules, fn($s) => !empty($s['check_in']) && !empty($s['check_out']))) ?></strong></span>
                    <span>Chờ chấm công: <strong><?= count(array_filter($schedules, fn($s) => empty($s['check_in']) || empty($s['check_out']))) ?></strong></span>
                </div>
            </div>

            <div class="hr-stat-card">
                <div class="hr-stat-label">Tổng giờ chấm công tháng <?= htmlspecialchars($selectedMonth) ?></div>
                <?php
                $totalMonthHours = $payrollSummary['total_hours'] ?? 0;
                $totalWorkedShiftsAll = array_sum(array_column($payrollSummary['staff_rows'], 'worked_shifts'));
                $totalSchedShiftsAll  = array_sum(array_column($payrollSummary['staff_rows'], 'total_shifts'));
                $paidRatio = $totalSchedShiftsAll > 0
                    ? round(($totalWorkedShiftsAll / $totalSchedShiftsAll) * 100)
                    : 0;
                ?>
                <div class="hr-stat-value-row">
                    <span class="hr-stat-number"><?= $totalMonthHours ?>h</span>
                    <span class="hr-stat-pill" style="background: #e0e7ff; color: #1e40af;">
                        <?= $paidRatio ?>% ca hoàn tất
                    </span>
                </div>
                <div class="hr-stat-foot">
                    <span>Đã chấm công: <strong><?= $totalWorkedShiftsAll ?> ca</strong></span>
                    <span>Tổng lịch: <strong><?= $totalSchedShiftsAll ?> ca</strong></span>
                </div>
            </div>

            <div class="hr-payroll-card">
                <div class="hr-payroll-head">
                    <span class="hr-payroll-title">Tổng quỹ lương tháng</span>
                    <form method="GET" action="index.php" style="margin:0;">
                        <input type="hidden" name="c" value="menu">
                        <input type="hidden" name="a" value="users">
                        <input type="hidden" name="tab" value="payroll">
                        <input type="month" name="month" value="<?= htmlspecialchars($selectedMonth) ?>"
                               onchange="this.form.submit()"
                               style="background:rgba(255,255,255,0.12); color:#fff; border:1px solid rgba(255,255,255,0.2); border-radius:6px; padding:3px 8px; font-size:11.5px;">
                    </form>
                </div>
                <div class="hr-payroll-total">
                    <?= number_format($payrollSummary['total_amount'], 0, ',', '.') ?>đ
                </div>
                <div class="hr-payroll-bar">
                    <div class="hr-payroll-bar-fill" style="width: <?= min(100, max(10, $paidRatio)) ?>%;"></div>
                </div>
                <div class="hr-payroll-meta">
                    <span>Đã chấm công: <strong style="color:#4ade80;"><?= number_format($payrollSummary['paid_amount'], 0, ',', '.') ?>đ</strong></span>
                    <span>Chờ chấm công: <strong style="color:#f8fafc;"><?= number_format($payrollSummary['unpaid_amount'], 0, ',', '.') ?>đ</strong></span>
                </div>
            </div>
        </div>

        <div class="hr-toolbar">
            <div class="hr-tabs">
                <button type="button" class="hr-tab-btn <?= $activeTab === 'staff' ? 'active' : '' ?>" onclick="switchHrTab('staff')">
                    Hồ sơ và Tài khoản
                </button>
                <button type="button" class="hr-tab-btn <?= $activeTab === 'shifts' ? 'active' : '' ?>" onclick="switchHrTab('shifts')">
                    Lịch phân ca tuần và Chấm công
                </button>
                <button type="button" class="hr-tab-btn <?= $activeTab === 'payroll' ? 'active' : '' ?>" onclick="switchHrTab('payroll')">
                    Bảng lương tháng và Đơn giá Role
                </button>
                <button type="button" class="hr-tab-btn <?= $activeTab === 'rbac' ? 'active' : '' ?>" onclick="switchHrTab('rbac')">
                    Nhật ký hệ thống
                </button>
            </div>
            <div style="display: flex; gap: 8px;">
                <button type="button" class="hr-btn" onclick="openHrModal('modalShiftConfig')">Thêm khung ca</button>
                <button type="button" class="hr-btn hr-btn-gold" onclick="openStaffModal()">Thêm Nhân Viên Mới</button>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($isAdmin): ?>
        <div class="hr-pane <?= $activeTab === 'staff' ? 'active' : '' ?>" id="pane_staff">
            <div class="hr-card">
                <div class="hr-card-bar">
                    <input type="text" id="staffSearchInput" class="hr-input" style="max-width: 320px;" placeholder="Tìm kiếm nhân viên..." oninput="filterStaffTable(this.value)">
                </div>
                <table class="hr-table" id="staffMainTable">
                    <thead>
                        <tr>
                            <th>Mã NV</th>
                            <th>Nhân viên</th>
                            <th>Tài khoản</th>
                            <th>Mã quyền</th>
                            <th>Vai trò đảm nhiệm</th>
                            <th>Lương / Giờ</th>
                            <th>Số điện thoại</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <?php
                            $uid = (int)$u['id'];
                            $isProtectedAdmin = ($uid === 1 || strtolower($u['username']) === 'admin' || strtoupper($u['role_code']) === 'ADMIN');
                            ?>
                            <tr class="staff-row-item">
                                <td style="font-weight: 800; color: var(--hr-navy);">#NV<?= str_pad((string)$uid, 2, '0', STR_PAD_LEFT) ?></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <span class="hr-avatar <?= $uid === $currentUserId ? 'me' : '' ?>">
                                            <?= htmlspecialchars(getInitials($u['full_name'])) ?>
                                        </span>
                                        <span style="font-weight: 800; color: var(--hr-navy);"><?= htmlspecialchars($u['full_name']) ?></span>
                                    </div>
                                </td>
                                <td style="color: #be185d; font-weight: 700;"><?= htmlspecialchars($u['username']) ?></td>
                                <td>
                                    <span class="hr-stat-pill" style="<?= getRoleBadgeStyle($u['role_code']) ?>">
                                        <?= htmlspecialchars($u['role_code']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($u['role_name']) ?></td>
                                <td style="font-weight: 800; color: var(--hr-navy);">
                                    <?= number_format((float)$u['effective_hourly_rate'], 0, ',', '.') ?>đ/h
                                </td>
                                <td><?= htmlspecialchars($u['phone'] ?? '') ?></td>
                                <td>
                                    <?php if (!empty($u['is_active'])): ?>
                                        <span class="hr-stat-pill" style="background: var(--hr-green-bg); color: var(--hr-green);">Đang làm việc</span>
                                    <?php else: ?>
                                        <span class="hr-stat-pill" style="background: var(--hr-red-bg); color: var(--hr-red);">Đã khóa</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                        <button type="button" class="hr-btn" style="padding: 5px 10px; font-size: 12px;"
                                                onclick='openStaffModal(<?= json_encode([
                                                    "id" => $uid,
                                                    "full_name" => $u["full_name"],
                                                    "username" => $u["username"],
                                                    "phone" => $u["phone"] ?? "",
                                                    "role_id" => (int)$u["role_id"],
                                                    "hourly_rate" => (float)$u["effective_hourly_rate"],
                                                    "is_protected" => $isProtectedAdmin
                                                ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                            Sửa
                                        </button>
                                        <a href="index.php?c=menu&a=resetPassword&id=<?= $uid ?>"
                                           class="hr-btn" style="padding: 5px 10px; font-size: 12px;"
                                           onclick="return confirm('Đặt lại mật khẩu về 123456?');">
                                            Reset MK
                                        </a>
                                        <?php if (!$isProtectedAdmin): ?>
                                            <a href="index.php?c=menu&a=toggleUserStatus&id=<?= $uid ?>"
                                               class="hr-btn <?= !empty($u['is_active']) ? 'hr-btn-danger' : '' ?>"
                                               style="padding: 5px 10px; font-size: 12px;">
                                                <?= !empty($u['is_active']) ? 'Khóa' : 'Mở khóa' ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <div class="hr-pane <?= (!$isAdmin || $activeTab === 'shifts') ? 'active' : '' ?>" id="pane_shifts">
        <div class="hr-card">
            <div class="hr-card-bar">
                <div style="font-weight: 800; color: var(--hr-navy); font-size: 13.5px;">
                    <?= date('d/m/Y', strtotime($weekStart)) ?> – <?= date('d/m/Y', strtotime($weekEnd)) ?>
                </div>
                <div style="display: flex; gap: 8px; align-items: center;">
                    <a href="index.php?c=menu&a=users&tab=shifts&date=<?= $prevWeekDate ?>" class="hr-btn">Tuần trước</a>
                    <a href="index.php?c=menu&a=users&tab=shifts&date=<?= date('Y-m-d') ?>" class="hr-btn">Hôm nay</a>
                    <a href="index.php?c=menu&a=users&tab=shifts&date=<?= $nextWeekDate ?>" class="hr-btn">Tuần sau</a>
                    <?php if ($isAdmin): ?>
                        <button type="button" class="hr-btn hr-btn-gold" onclick="openAssignModal('<?= htmlspecialchars($filterDate) ?>', 0)">
                            Phân ca nhân viên
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <div style="overflow-x: auto;">
                <table class="hr-week-grid">
                    <thead>
                        <tr>
                            <th style="width: 175px; text-align: left;">Khung ca</th>
                            <?php foreach ($weekDays as $wd): ?>
                                <th class="<?= $wd['is_today'] ? 'today-col' : '' ?>">
                                    <div><?= htmlspecialchars($wd['label']) ?></div>
                                    <div style="font-size: 13px; font-weight: 800; margin-top: 2px;"><?= htmlspecialchars($wd['day_num']) ?></div>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($shifts as $sh): ?>
                            <?php
                            $shId = (int)$sh['id'];
                            $shStart = substr($sh['start_time'], 0, 5);
                            $shEnd = substr($sh['end_time'], 0, 5);
                            ?>
                            <tr>
                                <td style="background: #f8fafc;">
                                    <div style="font-weight: 800; color: var(--hr-navy); font-size: 13px;">
                                        <?= htmlspecialchars($sh['shift_name']) ?>
                                    </div>
                                    <div style="font-size: 12px; color: var(--hr-muted); font-weight: 700; margin-top: 3px;">
                                        <?= $shStart ?> – <?= $shEnd ?>
                                    </div>
                                    <div style="font-size: 11px; color: var(--hr-amber); font-weight: 700; margin-top: 4px;">
                                        +<?= number_format((float)$sh['allowance'], 0, ',', '.') ?>đ
                                    </div>
                                </td>

                                <?php foreach ($weekDays as $wd): ?>
                                    <?php
                                    $dStr = $wd['date'];
                                    $cellUsers = $weekGrid[$shId][$dStr] ?? [];
                                    ?>
                                    <td class="<?= $wd['is_today'] ? 'today-cell' : '' ?>">
                                        <div class="hr-shift-slot">
                                            <div class="hr-shift-avatars">
                                                <?php if (empty($cellUsers)): ?>
                                                    <span style="font-size: 11px; color: #94a3b8;">Trống</span>
                                                <?php else: ?>
                                                    <?php foreach ($cellUsers as $cu): ?>
                                                        <?php
                                                        $isMe = ((int)$cu['user_id'] === $currentUserId);
                                                        $shortName = explode(' ', trim($cu['full_name']));
                                                        $displayShort = end($shortName);
                                                        ?>
                                                        <div class="hr-staff-chip <?= $isMe ? 'is-me' : '' ?>">
                                                            <span class="hr-avatar <?= $isMe ? 'me' : '' ?>">
                                                                <?= htmlspecialchars(getInitials($cu['full_name'])) ?>
                                                            </span>
                                                            <span><?= htmlspecialchars($displayShort) ?></span>
                                                            <?php if ($isAdmin): ?>
                                                                <a href="index.php?c=menu&a=deleteSchedule&id=<?= (int)$cu['id'] ?>&date=<?= $dStr ?>"
                                                                   style="color:#94a3b8; text-decoration:none; font-size:11px; margin-left:2px;"
                                                                   onclick="return confirm('Gỡ phân ca này?');">×</a>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </div>

                                            <?php if ($isAdmin): ?>
                                                <button type="button" class="hr-slot-add-btn" onclick="openAssignModal('<?= $dStr ?>', <?= $shId ?>)">
                                                    + Xếp người
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="hr-card">
            <div class="hr-card-bar">
                <form method="GET" action="index.php" style="display:flex; gap:8px; align-items:center; margin:0;">
                    <input type="hidden" name="c" value="menu">
                    <input type="hidden" name="a" value="users">
                    <input type="hidden" name="tab" value="shifts">
                    <input type="date" name="date" class="hr-input" value="<?= htmlspecialchars($filterDate) ?>" onchange="this.form.submit()">
                </form>
            </div>
            <table class="hr-table">
                <thead>
                    <tr>
                        <th>Nhân viên</th>
                        <th>Vai trò</th>
                        <th>Ca làm việc</th>
                        <th>Giờ vào ca</th>
                        <th>Giờ tan ca</th>
                        <th>Giờ thực tế</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($schedules)): ?>
                        <tr>
                            <td colspan="8" style="text-align:center; color:var(--hr-muted); padding:24px;">
                                Không có ca làm việc trong ngày <?= date('d/m/Y', strtotime($filterDate)) ?>.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($schedules as $sc): ?>
                            <?php
                            $canAct = $isAdmin || ((int)$sc['user_id'] === $currentUserId);
                            $inStr = !empty($sc['check_in']) ? substr($sc['check_in'], 0, 5) : '';
                            $outStr = !empty($sc['check_out']) ? substr($sc['check_out'], 0, 5) : '';
                            $workedRowHrs = 0.0;
                            if ($inStr !== '' && $outStr !== '' && $sc['attendance_status'] !== 'Absent') {
                                $t1 = strtotime($sc['check_in']);
                                $t2 = strtotime($sc['check_out']);
                                if ($t2 < $t1) $t2 += 86400;
                                $workedRowHrs = round(max(0, ($t2 - $t1) / 3600), 2);
                            }
                            ?>
                            <tr>
                                <td>
                                    <div style="display:flex; align-items:center; gap:10px;">
                                        <span class="hr-avatar <?= (int)$sc['user_id'] === $currentUserId ? 'me' : '' ?>">
                                            <?= htmlspecialchars(getInitials($sc['full_name'])) ?>
                                        </span>
                                        <span style="font-weight:800; color:var(--hr-navy);"><?= htmlspecialchars($sc['full_name']) ?></span>
                                    </div>
                                </td>
                                <td><span class="hr-stat-pill" style="<?= getRoleBadgeStyle($sc['role_code']) ?>"><?= htmlspecialchars($sc['role_code']) ?></span></td>
                                <td>
                                    <strong><?= htmlspecialchars($sc['shift_name']) ?></strong>
                                    <div style="font-size:11.5px; color:var(--hr-muted);">
                                        <?= substr($sc['start_time'], 0, 5) ?> – <?= substr($sc['end_time'], 0, 5) ?>
                                    </div>
                                </td>
                                <td colspan="2">
                                    <?php if ($isAdmin): ?>
                                        <form method="POST" action="index.php?c=menu&a=attendance" style="display:flex; gap:6px; align-items:center; margin:0;">
                                            <input type="hidden" name="id" value="<?= (int)$sc['id'] ?>">
                                            <input type="hidden" name="type" value="save_time">
                                            <input type="hidden" name="date" value="<?= htmlspecialchars($filterDate) ?>">
                                            <input type="time" name="check_in" class="hr-time-inline" value="<?= htmlspecialchars($inStr) ?>">
                                            <span>–</span>
                                            <input type="time" name="check_out" class="hr-time-inline" value="<?= htmlspecialchars($outStr) ?>">
                                            <button type="submit" class="hr-btn" style="padding:5px 10px; font-size:11.5px;">Lưu giờ</button>
                                        </form>
                                    <?php else: ?>
                                        <strong><?= $inStr !== '' ? $inStr : '—' ?></strong> – <strong><?= $outStr !== '' ? $outStr : '—' ?></strong>
                                    <?php endif; ?>
                                </td>
                                <td style="font-weight:800; color:var(--hr-navy);">
                                    <?= $workedRowHrs > 0 ? ($workedRowHrs . ' giờ') : '0 giờ' ?>
                                </td>
                                <td>
                                    <?php if ($sc['attendance_status'] === 'OnTime'): ?>
                                        <span class="hr-stat-pill" style="background:var(--hr-green-bg); color:var(--hr-green);">Đúng giờ</span>
                                    <?php elseif ($sc['attendance_status'] === 'Late'): ?>
                                        <span class="hr-stat-pill" style="background:var(--hr-amber-bg); color:var(--hr-amber);">Đi trễ</span>
                                    <?php elseif ($sc['attendance_status'] === 'Absent'): ?>
                                        <span class="hr-stat-pill" style="background:var(--hr-red-bg); color:var(--hr-red);">Vắng mặt</span>
                                    <?php else: ?>
                                        <span class="hr-stat-pill" style="background:#f1f5f9; color:#475569;">Đã xếp lịch</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($canAct): ?>
                                        <div style="display:flex; gap:6px;">
                                            <?php if (empty($sc['check_in'])): ?>
                                                <a href="index.php?c=menu&a=attendance&id=<?= (int)$sc['id'] ?>&type=in&date=<?= $filterDate ?>"
                                                   class="hr-btn hr-btn-gold" style="padding:5px 10px; font-size:12px;">Vào ca</a>
                                            <?php elseif (empty($sc['check_out'])): ?>
                                                <a href="index.php?c=menu&a=attendance&id=<?= (int)$sc['id'] ?>&type=out&date=<?= $filterDate ?>"
                                                   class="hr-btn" style="padding:5px 10px; font-size:12px;">Tan ca</a>
                                            <?php endif; ?>
                                            <?php if ($isAdmin && $sc['attendance_status'] !== 'Absent'): ?>
                                                <a href="index.php?c=menu&a=attendance&id=<?= (int)$sc['id'] ?>&type=absent&date=<?= $filterDate ?>"
                                                   class="hr-btn hr-btn-danger" style="padding:5px 10px; font-size:12px;">Vắng mặt</a>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($isAdmin): ?>
        <div class="hr-pane <?= $activeTab === 'payroll' ? 'active' : '' ?>" id="pane_payroll">
            <div class="hr-card">
                <table class="hr-table">
                    <thead>
                        <tr>
                            <th>Nhân viên</th>
                            <th>Vai trò</th>
                            <th>Đơn giá / Giờ</th>
                            <th>Số ca trong tháng</th>
                            <th>Tổng giờ lịch / Thực tế</th>
                            <th>Phụ cấp ca</th>
                            <th>Lương đã chấm công</th>
                            <th>Tổng lương tháng</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payrollSummary['staff_rows'] as $pr): ?>
                            <tr>
                                <td>
                                    <div style="display:flex; align-items:center; gap:10px;">
                                        <span class="hr-avatar"><?= htmlspecialchars(getInitials($pr['full_name'])) ?></span>
                                        <div>
                                            <div style="font-weight:800; color:var(--hr-navy);"><?= htmlspecialchars($pr['full_name']) ?></div>
                                            <div style="font-size:11.5px; color:var(--hr-muted);"><?= htmlspecialchars($pr['username']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="hr-stat-pill" style="<?= getRoleBadgeStyle($pr['role_code']) ?>"><?= htmlspecialchars($pr['role_code']) ?></span></td>
                                <td style="font-weight:700;"><?= number_format($pr['hourly_rate'], 0, ',', '.') ?>đ/h</td>
                                <td><?= $pr['worked_shifts'] ?> / <?= $pr['total_shifts'] ?> ca</td>
                                <td style="font-weight:700;"><?= $pr['worked_hours'] ?> giờ</td>
                                <td><?= number_format($pr['allowance_total'], 0, ',', '.') ?>đ</td>
                                <td style="color:var(--hr-green); font-weight:700;"><?= number_format($pr['earned_pay'], 0, ',', '.') ?>đ</td>
                                <td style="font-weight:800; font-size:14px; color:var(--hr-navy);"><?= number_format($pr['total_month_pay'], 0, ',', '.') ?>đ</td>
                            </tr>
                        <?php endforeach; ?>
                        <tr style="background:#f8fafc;">
                            <td colspan="7" style="text-align:right; font-weight:800; color:var(--hr-navy);">TỔNG QUỸ LƯƠNG THÁNG PHẢI TRẢ:</td>
                            <td style="font-weight:800; font-size:16px; color:var(--hr-red);">
                                <?= number_format($payrollSummary['total_amount'], 0, ',', '.') ?>đ
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="hr-card">
                <table class="hr-table">
                    <thead>
                        <tr>
                            <th>Mã Role</th>
                            <th>Tên vai trò đảm nhiệm</th>
                            <th>Mức lương chuẩn / Giờ</th>
                            <th>Đồng bộ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($roles as $r): ?>
                            <tr>
                                <td><span class="hr-stat-pill" style="<?= getRoleBadgeStyle($r['role_code']) ?>"><?= htmlspecialchars($r['role_code']) ?></span></td>
                                <td style="font-weight:700; color:var(--hr-navy);"><?= htmlspecialchars($r['role_name']) ?></td>
                                <td colspan="2">
                                    <form method="POST" action="index.php?c=menu&a=saveRoleRate" style="display:flex; gap:10px; align-items:center; margin:0;">
                                        <input type="hidden" name="role_id" value="<?= (int)$r['id'] ?>">
                                        <input type="number" name="hourly_rate" class="hr-input" style="max-width:160px;" min="10000" step="1000" value="<?= (float)($r['hourly_rate'] ?? 35000) ?>" required>
                                        <label style="font-size:12px; display:flex; align-items:center; gap:5px; cursor:pointer;">
                                            <input type="checkbox" name="sync_users" value="1" checked> Áp dụng cho nhân viên thuộc Role này
                                        </label>
                                        <button type="submit" class="hr-btn hr-btn-gold" style="padding:6px 12px; font-size:12px;">Lưu đơn giá</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="hr-pane <?= $activeTab === 'rbac' ? 'active' : '' ?>" id="pane_rbac">
            <div class="hr-card">
                <table class="hr-table">
                    <thead>
                        <tr>
                            <th>Thời gian</th>
                            <th>Nhân viên</th>
                            <th>Vai trò</th>
                            <th>Phân hệ</th>
                            <th>Nội dung thao tác</th>
                            <th>Mức độ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($auditLogs as $log): ?>
                            <tr>
                                <td style="font-size:12px; color:var(--hr-muted);"><?= date('H:i d/m/Y', strtotime($log['created_at'])) ?></td>
                                <td style="font-weight:700; color:var(--hr-navy);"><?= htmlspecialchars($log['full_name']) ?></td>
                                <td><span class="hr-stat-pill" style="<?= getRoleBadgeStyle($log['role_code']) ?>"><?= htmlspecialchars($log['role_code']) ?></span></td>
                                <td><?= htmlspecialchars($log['module_name']) ?></td>
                                <td><?= htmlspecialchars($log['description']) ?></td>
                                <td>
                                    <span class="hr-stat-pill" style="<?= $log['severity'] === 'CRITICAL' ? 'background:var(--hr-red-bg);color:var(--hr-red);' : ($log['severity'] === 'WARNING' ? 'background:var(--hr-amber-bg);color:var(--hr-amber);' : 'background:#f1f5f9;color:#475569;') ?>">
                                        <?= htmlspecialchars($log['severity']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php if ($isAdmin): ?>
<div class="hr-modal-backdrop" id="modalStaff">
    <div class="hr-modal-dialog">
        <div class="hr-modal-header">
            <span id="modalStaffTitle">Thêm Nhân Viên Mới</span>
            <button type="button" class="hr-btn" style="padding:3px 10px; font-size:12px;" onclick="closeHrModal('modalStaff')">Đóng</button>
        </div>
        <div class="hr-modal-body">
            <form method="POST" action="index.php?c=menu&a=saveUser">
                <input type="hidden" name="user_id" id="st_user_id" value="0">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                    <div style="grid-column: span 2;">
                        <label class="hr-label">Họ và tên nhân viên *</label>
                        <input type="text" name="full_name" id="st_full_name" class="hr-input" required>
                    </div>
                    <div>
                        <label class="hr-label">Tên tài khoản đăng nhập *</label>
                        <input type="text" name="username" id="st_username" class="hr-input" required>
                    </div>
                    <div>
                        <label class="hr-label">Số điện thoại</label>
                        <input type="text" name="phone" id="st_phone" class="hr-input">
                    </div>
                    <div>
                        <label class="hr-label">Vai trò đảm nhiệm *</label>
                        <select name="role_id" id="st_role_id" class="hr-select" required>
                            <?php foreach ($roles as $r): ?>
                                <option value="<?= (int)$r['id'] ?>" data-rate="<?= (float)($r['hourly_rate'] ?? 35000) ?>">
                                    <?= htmlspecialchars($r['role_code'] . ' - ' . $r['role_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="hr-label">Lương theo giờ *</label>
                        <input type="number" name="hourly_rate" id="st_hourly_rate" class="hr-input" min="10000" step="1000" value="35000">
                    </div>
                    <div style="grid-column: span 2;" id="st_password_box">
                        <label class="hr-label">Mật khẩu khởi tạo</label>
                        <input type="text" name="password" class="hr-input" value="123456">
                    </div>
                </div>
                <div style="margin-top:18px; display:flex; justify-content:flex-end; gap:8px;">
                    <button type="button" class="hr-btn" onclick="closeHrModal('modalStaff')">Hủy</button>
                    <button type="submit" class="hr-btn hr-btn-gold">Lưu hồ sơ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="hr-modal-backdrop" id="modalAssignShift">
    <div class="hr-modal-dialog" style="max-width:460px;">
        <div class="hr-modal-header">
            <span>Phân Ca Làm Việc</span>
            <button type="button" class="hr-btn" style="padding:3px 10px; font-size:12px;" onclick="closeHrModal('modalAssignShift')">Đóng</button>
        </div>
        <div class="hr-modal-body">
            <form method="POST" action="index.php?c=menu&a=assignShift">
                <div style="display:grid; gap:14px;">
                    <div>
                        <label class="hr-label">Ngày làm việc *</label>
                        <input type="date" name="work_date" id="as_work_date" class="hr-input" value="<?= htmlspecialchars($filterDate) ?>" required>
                    </div>
                    <div>
                        <label class="hr-label">Chọn ca làm việc *</label>
                        <select name="shift_id" id="as_shift_id" class="hr-select" required>
                            <?php foreach ($shifts as $sh): ?>
                                <option value="<?= (int)$sh['id'] ?>">
                                    <?= htmlspecialchars($sh['shift_name'] . ' · ' . substr($sh['start_time'], 0, 5) . ' - ' . substr($sh['end_time'], 0, 5)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="hr-label">Chọn nhân viên *</label>
                        <select name="user_id" class="hr-select" required>
                            <?php foreach ($users as $u): ?>
                                <?php if (!empty($u['is_active'])): ?>
                                    <option value="<?= (int)$u['id'] ?>">
                                        <?= htmlspecialchars($u['full_name'] . ' · ' . $u['role_code']) ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="hr-label">Ghi chú phân công</label>
                        <input type="text" name="note" class="hr-input" placeholder="Trực sảnh VIP tầng 2">
                    </div>
                </div>
                <div style="margin-top:18px; display:flex; justify-content:flex-end; gap:8px;">
                    <button type="button" class="hr-btn" onclick="closeHrModal('modalAssignShift')">Hủy</button>
                    <button type="submit" class="hr-btn hr-btn-gold">Xác nhận xếp ca</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="hr-modal-backdrop" id="modalShiftConfig">
    <div class="hr-modal-dialog" style="max-width:480px;">
        <div class="hr-modal-header">
            <span>Thêm Khung Ca Làm Việc</span>
            <button type="button" class="hr-btn" style="padding:3px 10px; font-size:12px;" onclick="closeHrModal('modalShiftConfig')">Đóng</button>
        </div>
        <div class="hr-modal-body">
            <form method="POST" action="index.php?c=menu&a=saveShift">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                    <div>
                        <label class="hr-label">Mã ca *</label>
                        <input type="text" name="shift_code" class="hr-input" placeholder="SHIFT_NIGHT" required>
                    </div>
                    <div>
                        <label class="hr-label">Phụ cấp ca</label>
                        <input type="number" name="allowance" class="hr-input" value="50000" step="5000">
                    </div>
                    <div style="grid-column: span 2;">
                        <label class="hr-label">Tên ca làm việc *</label>
                        <input type="text" name="shift_name" class="hr-input" placeholder="Ca Đêm Đặc Biệt" required>
                    </div>
                    <div>
                        <label class="hr-label">Giờ bắt đầu *</label>
                        <input type="time" name="start_time" class="hr-input" value="08:00" required>
                    </div>
                    <div>
                        <label class="hr-label">Giờ kết thúc *</label>
                        <input type="time" name="end_time" class="hr-input" value="14:00" required>
                    </div>
                </div>
                <div style="margin-top:18px; display:flex; justify-content:flex-end; gap:8px;">
                    <button type="button" class="hr-btn" onclick="closeHrModal('modalShiftConfig')">Hủy</button>
                    <button type="submit" class="hr-btn hr-btn-gold">Lưu khung ca</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function switchHrTab(tabName) {
    document.querySelectorAll('.hr-pane').forEach(function (p) { p.classList.remove('active'); });
    document.querySelectorAll('.hr-tab-btn').forEach(function (b) { b.classList.remove('active'); });

    const target = document.getElementById('pane_' + tabName);
    if (target) target.classList.add('active');

    const buttons = document.querySelectorAll('.hr-tab-btn');
    buttons.forEach(function (btn) {
        if ((btn.getAttribute('onclick') || '').indexOf("'" + tabName + "'") !== -1) {
            btn.classList.add('active');
        }
    });
}

function openHrModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.add('show');
}

function closeHrModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.remove('show');
}

function openStaffModal(staff) {
    const passBox = document.getElementById('st_password_box');
    const userInp = document.getElementById('st_username');
    const roleSel = document.getElementById('st_role_id');

    if (staff && staff.id) {
        document.getElementById('modalStaffTitle').textContent = 'Cập nhật hồ sơ: ' + staff.full_name;
        document.getElementById('st_user_id').value = staff.id;
        document.getElementById('st_full_name').value = staff.full_name;
        userInp.value = staff.username;
        userInp.readOnly = true;
        document.getElementById('st_phone').value = staff.phone || '';
        roleSel.value = staff.role_id;
        document.getElementById('st_hourly_rate').value = staff.hourly_rate || 35000;
        if (passBox) passBox.style.display = 'none';
    } else {
        document.getElementById('modalStaffTitle').textContent = 'Thêm Nhân Viên Mới';
        document.getElementById('st_user_id').value = '0';
        document.getElementById('st_full_name').value = '';
        userInp.value = '';
        userInp.readOnly = false;
        document.getElementById('st_phone').value = '';
        document.getElementById('st_hourly_rate').value = '35000';
        if (passBox) passBox.style.display = 'block';
    }
    openHrModal('modalStaff');
}

function openAssignModal(dateStr, shiftId) {
    const dateInp = document.getElementById('as_work_date');
    const shiftSel = document.getElementById('as_shift_id');
    if (dateInp && dateStr) dateInp.value = dateStr;
    if (shiftSel && shiftId > 0) shiftSel.value = shiftId;
    openHrModal('modalAssignShift');
}

function filterStaffTable(keyword) {
    const kw = (keyword || '').toLowerCase().trim();
    document.querySelectorAll('#staffMainTable .staff-row-item').forEach(function (row) {
        const text = (row.textContent || '').toLowerCase();
        row.style.display = (kw === '' || text.indexOf(kw) !== -1) ? '' : 'none';
    });
}
</script>