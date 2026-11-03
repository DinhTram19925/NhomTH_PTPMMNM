
<?php
$bookingStats = $bookingStats ?? [];
$topCustomers = $topCustomers ?? [];

$total = (int)($bookingStats['total_bookings'] ?? 0);
$pending = (int)($bookingStats['pending'] ?? 0);
$confirmed = (int)($bookingStats['confirmed'] ?? 0);
$checkedIn = (int)($bookingStats['checked_in'] ?? 0);
$cancelled = (int)($bookingStats['cancelled'] ?? 0);

$successRate = $total > 0
    ? round(($checkedIn / $total) * 100, 1)
    : 0;

$cancelRate = $total > 0
    ? round(($cancelled / $total) * 100, 1)
    : 0;

function reportEscape($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}
?>

<style>
/* ROYALDINE 5S - BOOKING & CRM REPORT */

.rd-report {
    --gold: #d8b66c;
    --gold-light: #f3dfaa;
    --bg: #111925;
    --panel: #1a2737;
    --panel2: #202e40;
    --border: #354458;
    --text: #f4f5f7;
    --muted: #a4b0c1;

    box-sizing: border-box;
    min-height: 700px;
    padding: 28px;
    color: var(--text);
    background:
        radial-gradient(ellipse at 90% 0%, #29374a 0%, transparent 38%),
        var(--bg);
    border-radius: 14px;
    font-family: "Segoe UI", Arial, sans-serif;
}

.rd-report *,
.rd-report *::before,
.rd-report *::after {
    box-sizing: border-box;
}

/* HEADER */

.rd-report .report-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    margin-bottom: 27px;
}

.rd-report h2 {
    margin: 0 0 7px;
    color: var(--gold-light);
    font: 500 clamp(25px, 3vw, 34px) Georgia, "Times New Roman", serif;
}

.rd-report .report-subtitle {
    margin: 0;
    color: var(--muted);
    font-size: 13px;
}

.rd-report h4 {
    margin: 0 0 15px;
    color: var(--gold-light);
    font: 500 21px Georgia, "Times New Roman", serif;
}

.rd-report h5 {
    margin: 0 0 12px;
    color: #e8d5a7;
    font-size: 15px;
    font-weight: 600;
}

/* PRINT BUTTON */

.rd-report .report-print {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 40px;
    padding: 10px 17px;
    border: 1px solid var(--gold);
    border-radius: 8px;
    color: var(--gold-light);
    background: #273448;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: .2s;
    white-space: nowrap;
}

.rd-report .report-print:hover {
    color: #171b21;
    background: var(--gold);
}

/* SECTION */

.rd-report .report-section {
    margin-bottom: 27px;
}

/* STAT CARDS */

.rd-report .report-grid {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: 17px;
}

.rd-report .report-grid.two-columns {
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.rd-report .report-card {
    min-width: 0;
    height: 100%;
    padding: 19px;
    border: 1px solid var(--border);
    border-radius: 12px;
    background: linear-gradient(145deg, #202e40, #192434);
    box-shadow: 0 8px 24px #00000018;
}

.rd-report .report-label {
    margin-bottom: 8px;
    color: var(--muted);
    font-size: 12px;
}

.rd-report .report-number {
    margin: 0;
    color: var(--gold-light);
    font-size: 27px;
    font-weight: 700;
    line-height: 1.3;
}

.rd-report .color-pending { color: #f5c76a; }
.rd-report .color-confirmed { color: #8dbbff; }
.rd-report .color-success { color: #6bd5a0; }
.rd-report .color-danger { color: #ff8994; }

/* RATE CARDS */

.rd-report .rate-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 15px;
}

.rd-report .rate-card {
    padding: 22px;
    border: 1px solid var(--border);
    border-radius: 12px;
    background: linear-gradient(145deg, #202e40, #192434);
}

.rd-report .rate-number {
    margin: 0 0 15px;
    font-size: 27px;
    font-weight: 700;
}

.rd-report .rate-bar {
    height: 11px;
    overflow: hidden;
    border-radius: 20px;
    background: #354458;
}

.rd-report .rate-fill {
    height: 100%;
    border-radius: 20px;
    transition: width .3s;
}

.rd-report .rate-success {
    background: linear-gradient(90deg, #238b60, #55c995);
}

.rd-report .rate-danger {
    background: linear-gradient(90deg, #c94350, #f07882);
}

.rd-report .rate-note {
    display: block;
    margin-top: 9px;
    color: var(--muted);
    font-size: 11px;
}

/* TABLE */

.rd-report .report-table-card {
    padding: 20px;
    border: 1px solid var(--border);
    border-radius: 12px;
    background: linear-gradient(145deg, #202e40, #192434);
    box-shadow: 0 8px 24px #00000018;
}

.rd-report .report-table-wrap {
    width: 100%;
    overflow-x: auto;
    border: 1px solid var(--border);
    border-radius: 9px;
}

.rd-report .report-table {
    width: 100%;
    min-width: 750px;
    margin: 0;
    border-collapse: collapse;
    background: #182434;
}

.rd-report .report-table th,
.rd-report .report-table td {
    padding: 13px 12px;
    border-bottom: 1px solid var(--border);
    color: #dce3ec;
    text-align: left;
    vertical-align: middle;
    font-size: 12px;
}

.rd-report .report-table th {
    color: var(--gold-light);
    background: var(--panel2);
    font-size: 11px;
    font-weight: 700;
    white-space: nowrap;
}

.rd-report .report-table tbody tr {
    transition: background .2s;
}

.rd-report .report-table tbody tr:hover {
    background: #ffffff08;
}

.rd-report .report-table tbody tr:last-child td {
    border-bottom: 0;
}

.rd-report .customer-name {
    color: #f3dfaa;
    font-weight: 700;
}

/* TIER */

.rd-report .tier-badge {
    display: inline-block;
    padding: 6px 10px;
    border: 1px solid transparent;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    white-space: nowrap;
}

.rd-report .tier-Standard {
    color: #d5dce5;
    background: #465365;
    border-color: #64748b;
}

.rd-report .tier-Silver {
    color: #e5e9ef;
    background: #536174;
    border-color: #8996a8;
}

.rd-report .tier-Gold {
    color: #f5dfa1;
    background: #66532b;
    border-color: #b58c3d;
}

.rd-report .tier-Diamond {
    color: #a9e9ff;
    background: #174b63;
    border-color: #3686a5;
}

.rd-report .points-value {
    color: #78aaff !important;
    font-weight: 700;
}

.rd-report .revenue-value {
    color: #6bd5a0 !important;
    font-weight: 700;
}

.rd-report .report-empty {
    padding: 35px 15px !important;
    color: var(--muted) !important;
    text-align: center !important;
}

/* RESPONSIVE */

@media (max-width: 1050px) {
    .rd-report .report-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}

@media (max-width: 700px) {
    .rd-report {
        padding: 16px;
    }

    .rd-report .report-header {
        align-items: flex-start;
        flex-direction: column;
    }

    .rd-report .report-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .rd-report .report-grid.two-columns,
    .rd-report .rate-grid {
        grid-template-columns: 1fr;
    }

    .rd-report .report-card,
    .rd-report .rate-card {
        padding: 15px;
    }
}

/* PRINT */

@media print {
    body {
        background: #fff !important;
    }

    .rd-report {
        padding: 10px;
        color: #111;
        background: #fff !important;
    }

    .rd-report .report-card,
    .rd-report .rate-card,
    .rd-report .report-table-card {
        color: #111;
        background: #fff !important;
        border: 1px solid #ccc;
        box-shadow: none;
        break-inside: avoid;
    }

    .rd-report h2,
    .rd-report h4,
    .rd-report h5,
    .rd-report .report-number,
    .rd-report .report-table th,
    .rd-report .report-table td,
    .rd-report .customer-name {
        color: #111 !important;
    }

    .rd-report .report-print {
        display: none !important;
    }

    .rd-report .report-table {
        background: #fff !important;
    }

    .rd-report .report-table th {
        background: #eee !important;
    }
}
</style>

<div class="rd-report">

    <!-- HEADER -->
    <div class="report-header">
        <div>
            <h2>📊 Báo cáo Booking & CRM</h2>
            <p class="report-subtitle">
                Thống kê đặt bàn và khách hàng thân thiết RoyalDine 5S.
            </p>
        </div>

        <button class="report-print" type="button"
                onclick="window.print()">
            🖨️ In báo cáo
        </button>
    </div>

    <!-- 1. THỐNG KÊ ĐẶT BÀN -->
    <section class="report-section">

        <h4>1. Thống kê đặt bàn</h4>

        <div class="report-grid">

            <div class="report-card">
                <div class="report-label">Tổng phiếu đặt</div>
                <p class="report-number">
                    <?= number_format($total) ?>
                </p>
            </div>

            <div class="report-card">
                <div class="report-label">Chờ xác nhận</div>
                <p class="report-number color-pending">
                    <?= number_format($pending) ?>
                </p>
            </div>

            <div class="report-card">
                <div class="report-label">Đã xác nhận</div>
                <p class="report-number color-confirmed">
                    <?= number_format($confirmed) ?>
                </p>
            </div>

            <div class="report-card">
                <div class="report-label">Đã Check-in</div>
                <p class="report-number color-success">
                    <?= number_format($checkedIn) ?>
                </p>
            </div>

            <div class="report-card">
                <div class="report-label">Đã hủy</div>
                <p class="report-number color-danger">
                    <?= number_format($cancelled) ?>
                </p>
            </div>

        </div>

        <!-- TỶ LỆ -->
        <div class="rate-grid">

            <div class="rate-card">

                <h5>Tỷ lệ Check-in</h5>

                <p class="rate-number color-success">
                    <?= $successRate ?>%
                </p>

                <div class="rate-bar">
                    <div class="rate-fill rate-success"
                         style="width:<?= min(100, max(0, $successRate)) ?>%">
                    </div>
                </div>

                <small class="rate-note">
                    Số phiếu đã Check-in / Tổng số phiếu
                </small>

            </div>

            <div class="rate-card">

                <h5>Tỷ lệ hủy đặt bàn</h5>

                <p class="rate-number color-danger">
                    <?= $cancelRate ?>%
                </p>

                <div class="rate-bar">
                    <div class="rate-fill rate-danger"
                         style="width:<?= min(100, max(0, $cancelRate)) ?>%">
                    </div>
                </div>

                <small class="rate-note">
                    Số phiếu đã hủy / Tổng số phiếu
                </small>

            </div>

        </div>

    </section>

    <!-- 2. THỐNG KÊ TIỀN ĐẶT CỌC -->
    <section class="report-section">

        <h4>2. Thống kê tiền đặt cọc</h4>

        <div class="report-grid two-columns">

            <div class="report-card">

                <div class="report-label">
                    Tiền cọc của phiếu đã Check-in
                </div>

                <p class="report-number color-success">
                    <?= number_format(
                        (float)($bookingStats['checked_in_deposit'] ?? 0),
                        0,
                        ',',
                        '.'
                    ) ?>đ
                </p>

            </div>

            <div class="report-card">

                <div class="report-label">
                    Tiền cọc của phiếu đã hủy
                </div>

                <p class="report-number color-danger">
                    <?= number_format(
                        (float)($bookingStats['cancelled_deposit'] ?? 0),
                        0,
                        ',',
                        '.'
                    ) ?>đ
                </p>

            </div>

        </div>

    </section>

    <!-- 3. TOP 10 KHÁCH HÀNG -->
    <section class="report-section">

        <h4>3. Top 10 khách hàng chi tiêu cao nhất</h4>

        <div class="report-table-card">

            <div class="report-table-wrap">

                <table class="report-table">

                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Khách hàng</th>
                            <th>Số điện thoại</th>
                            <th>Hạng thành viên</th>
                            <th>Điểm thưởng</th>
                            <th>Tổng chi tiêu</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if (empty($topCustomers)): ?>

                        <tr>
                            <td colspan="6" class="report-empty">
                                Chưa có dữ liệu khách hàng.
                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($topCustomers as $index => $customer): ?>

                            <?php
                            $tier = $customer['tier'] ?? 'Standard';

                            $allowedTiers = [
                                'Standard',
                                'Silver',
                                'Gold',
                                'Diamond'
                            ];

                            if (!in_array($tier, $allowedTiers, true)) {
                                $tier = 'Standard';
                            }
                            ?>

                            <tr>

                                <td>
                                    <?= $index + 1 ?>
                                </td>

                                <td>
                                    <span class="customer-name">
                                        <?= reportEscape($customer['full_name'] ?? '') ?>
                                    </span>
                                </td>

                                <td>
                                    <?= reportEscape($customer['phone'] ?? '') ?>
                                </td>

                                <td>
                                    <span class="tier-badge tier-<?= reportEscape($tier) ?>">
                                        <?= reportEscape($tier) ?>
                                    </span>
                                </td>

                                <td class="points-value">
                                    <?= number_format(
                                        (int)($customer['points'] ?? 0)
                                    ) ?>
                                </td>

                                <td class="revenue-value">
                                    <?= number_format(
                                        (float)($customer['total_spent'] ?? 0),
                                        0,
                                        ',',
                                        '.'
                                    ) ?>đ
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </section>

</div>
