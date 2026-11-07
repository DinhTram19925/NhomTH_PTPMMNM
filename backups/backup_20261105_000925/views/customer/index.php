
<?php
function h($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$stats = $statistics ?? [];
?>

<style>
/* ROYALDINE 5S - VIP & LOYALTY */

.rd-customer {
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

.rd-customer *,
.rd-customer *::before,
.rd-customer *::after {
    box-sizing: border-box;
}

/* HEADER */

.rd-customer .customer-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    margin-bottom: 25px;
}

.rd-customer h2 {
    margin: 0 0 7px;
    color: var(--gold-light);
    font: 500 clamp(25px, 3vw, 34px) Georgia, "Times New Roman", serif;
}

.rd-customer .customer-subtitle {
    margin: 0;
    color: var(--muted);
    font-size: 13px;
}

.rd-customer .btn-add-customer {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 42px;
    padding: 11px 18px;
    border: 1px solid #e7cd8c;
    border-radius: 8px;
    color: #171b21;
    background: linear-gradient(135deg, #f1dba3, #c8a458);
    font-size: 13px;
    font-weight: 700;
    text-decoration: none;
    white-space: nowrap;
    transition: .2s;
}

.rd-customer .btn-add-customer:hover {
    color: #171b21;
    filter: brightness(1.1);
    transform: translateY(-1px);
}

/* CARD */

.rd-customer .customer-card {
    min-width: 0;
    height: 100%;
    padding: 19px;
    border: 1px solid var(--border);
    border-radius: 12px;
    background: linear-gradient(145deg, #202e40, #192434);
    box-shadow: 0 8px 24px #00000018;
}

.rd-customer .stat-label {
    margin-bottom: 7px;
    color: var(--muted);
    font-size: 12px;
}

.rd-customer .stat-number {
    margin: 0;
    color: var(--gold-light);
    font-size: 27px;
    font-weight: 700;
}

.rd-customer .stat-total {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: 18px;
}

.rd-customer .stat-summary {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 15px;
    margin-bottom: 22px;
}

.rd-customer .stat-summary .customer-card {
    padding: 22px;
}

.rd-customer .points-value {
    margin: 0;
    color: #78aaff;
    font-size: 25px;
    font-weight: 700;
}

.rd-customer .revenue-value {
    margin: 0;
    color: #6bd5a0;
    font-size: 25px;
    font-weight: 700;
}

/* TIER COLORS */

.rd-customer .tier-badge {
    display: inline-block;
    padding: 6px 11px;
    border: 1px solid transparent;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    white-space: nowrap;
}

.rd-customer .tier-Standard {
    color: #d5dce5;
    background: #465365;
    border-color: #64748b;
}

.rd-customer .tier-Silver {
    color: #e5e9ef;
    background: #536174;
    border-color: #8996a8;
}

.rd-customer .tier-Gold {
    color: #f5dfa1;
    background: #66532b;
    border-color: #b58c3d;
}

.rd-customer .tier-Diamond {
    color: #a9e9ff;
    background: #174b63;
    border-color: #3686a5;
}

/* ALERT */

.rd-customer .customer-alert {
    margin-bottom: 16px;
    padding: 13px 15px;
    border-radius: 8px;
    font-size: 13px;
}

.rd-customer .customer-alert.success {
    border: 1px solid #287b61;
    color: #9ce8c7;
    background: #164333;
}

/* MAIN LIST */

.rd-customer .customer-list {
    padding: 20px;
    border: 1px solid var(--border);
    border-radius: 13px;
    background: linear-gradient(145deg, #202e40, #192434);
    box-shadow: 0 10px 28px #00000018;
}

/* SEARCH */

.rd-customer .customer-search {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 170px;
    gap: 10px;
    margin-bottom: 18px;
}

.rd-customer .customer-search input {
    width: 100%;
    min-width: 0;
    min-height: 42px;
    padding: 10px 13px;
    border: 1px solid #46566c;
    border-radius: 8px;
    outline: none;
    color: var(--text);
    background: #121d2b;
    font: 13px "Segoe UI", Arial, sans-serif;
    transition: .2s;
}

.rd-customer .customer-search input::placeholder {
    color: #8492a5;
}

.rd-customer .customer-search input:focus {
    border-color: var(--gold);
    box-shadow: 0 0 0 3px #d8b66c18;
}

.rd-customer .btn-search {
    min-height: 42px;
    padding: 10px 15px;
    border: 1px solid #e7cd8c;
    border-radius: 8px;
    color: #171b21;
    background: linear-gradient(135deg, #f1dba3, #c8a458);
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    transition: .2s;
}

.rd-customer .btn-search:hover {
    filter: brightness(1.1);
}

/* TABLE */

.rd-customer .customer-table-wrap {
    width: 100%;
    overflow-x: auto;
    border: 1px solid var(--border);
    border-radius: 9px;
}

.rd-customer .customer-table {
    width: 100%;
    min-width: 850px;
    margin: 0;
    border-collapse: collapse;
    background: #182434;
}

.rd-customer .customer-table th,
.rd-customer .customer-table td {
    padding: 13px 12px;
    border-bottom: 1px solid var(--border);
    color: #dce3ec;
    text-align: left;
    vertical-align: middle;
    font-size: 12px;
}

.rd-customer .customer-table th {
    color: var(--gold-light);
    background: var(--panel2);
    font-size: 11px;
    font-weight: 700;
    white-space: nowrap;
}

.rd-customer .customer-table tbody tr {
    transition: background .2s;
}

.rd-customer .customer-table tbody tr:hover {
    background: #ffffff08;
}

.rd-customer .customer-table tbody tr:last-child td {
    border-bottom: 0;
}

.rd-customer .customer-name {
    display: block;
    margin-bottom: 4px;
    color: #f3dfaa;
    font-size: 13px;
    font-weight: 700;
}

.rd-customer .customer-email {
    color: var(--muted);
    font-size: 11px;
}

.rd-customer .customer-points {
    color: #78aaff !important;
    font-weight: 700;
}

.rd-customer .customer-revenue {
    color: #6bd5a0 !important;
    font-weight: 700;
}

/* ACTION BUTTONS */

.rd-customer .customer-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 7px;
}

.rd-customer .customer-actions a {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 31px;
    padding: 7px 11px;
    border: 1px solid;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    text-decoration: none;
    transition: .2s;
}

.rd-customer .btn-detail {
    border-color: #4779bd !important;
    color: #a9ceff !important;
    background: #203b5e;
}

.rd-customer .btn-edit {
    border-color: #a88742 !important;
    color: #f3dfaa !important;
    background: #4c4028;
}

.rd-customer .customer-actions a:hover {
    filter: brightness(1.2);
    transform: translateY(-1px);
}

/* EMPTY */

.rd-customer .empty-data {
    padding: 35px 15px !important;
    color: var(--muted) !important;
    text-align: center !important;
}

/* RESPONSIVE */

@media (max-width: 1050px) {
    .rd-customer .stat-total {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}

@media (max-width: 700px) {
    .rd-customer {
        padding: 16px;
    }

    .rd-customer .customer-header {
        align-items: flex-start;
        flex-direction: column;
    }

    .rd-customer .stat-total {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .rd-customer .stat-summary {
        grid-template-columns: 1fr;
    }

    .rd-customer .customer-search {
        grid-template-columns: 1fr;
    }

    .rd-customer .customer-list {
        padding: 13px;
    }
}
</style>

<div class="rd-customer">

    <!-- HEADER -->
    <div class="customer-header">
        <div>
            <h2>👑 Hội viên VIP & Loyalty</h2>
            <p class="customer-subtitle">
                Quản lý khách hàng và chương trình thành viên RoyalDine 5S.
            </p>
        </div>

        <a href="index.php?c=customer&a=create"
           class="btn-add-customer">
            ＋ Thêm hội viên
        </a>
    </div>

    <!-- THÔNG BÁO -->
    <?php if (!empty($success)): ?>
        <div class="customer-alert success">
            ✓ <?= h($success) ?>
        </div>
    <?php endif; ?>

    <!-- THỐNG KÊ HẠNG HỘI VIÊN -->
    <div class="stat-total">

        <div class="customer-card">
            <div class="stat-label">Tổng hội viên</div>
            <h3 class="stat-number">
                <?= number_format((int)($stats['total_customers'] ?? 0)) ?>
            </h3>
        </div>

        <?php
        $tiers = [
            'standard_count' => 'Standard',
            'silver_count' => 'Silver',
            'gold_count' => 'Gold',
            'diamond_count' => 'Diamond'
        ];
        ?>

        <?php foreach ($tiers as $key => $tier): ?>

            <div class="customer-card">
                <div class="stat-label">
                    <?= h($tier) ?>
                </div>

                <h3 class="stat-number">
                    <?= number_format((int)($stats[$key] ?? 0)) ?>
                </h3>
            </div>

        <?php endforeach; ?>

    </div>

    <!-- THỐNG KÊ ĐIỂM VÀ CHI TIÊU -->
    <div class="stat-summary">

        <div class="customer-card">
            <div class="stat-label">Tổng điểm thưởng</div>

            <h3 class="points-value">
                <?= number_format((int)($stats['total_points'] ?? 0)) ?>
                điểm
            </h3>
        </div>

        <div class="customer-card">
            <div class="stat-label">Tổng chi tiêu hội viên</div>

            <h3 class="revenue-value">
                <?= number_format((float)($stats['total_revenue'] ?? 0)) ?>
                đ
            </h3>
        </div>

    </div>

    <!-- DANH SÁCH HỘI VIÊN -->
    <section class="customer-list">

        <!-- TÌM KIẾM -->
        <form method="GET" class="customer-search">

            <input type="hidden" name="c" value="customer">
            <input type="hidden" name="a" value="index">

            <input
                type="text"
                name="keyword"
                placeholder="Tìm theo tên, số điện thoại hoặc email..."
                value="<?= h($keyword ?? '') ?>"
            >

            <button type="submit" class="btn-search">
                Tìm kiếm
            </button>

        </form>

        <!-- BẢNG KHÁCH HÀNG -->
        <div class="customer-table-wrap">

            <table class="customer-table">

                <thead>
                    <tr>
                        <th>#</th>
                        <th>Khách hàng</th>
                        <th>Số điện thoại</th>
                        <th>Hạng thành viên</th>
                        <th>Điểm thưởng</th>
                        <th>Tổng chi tiêu</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (empty($customers)): ?>

                        <tr>
                            <td colspan="7" class="empty-data">
                                Chưa có dữ liệu hội viên.
                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($customers as $index => $customer): ?>

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
                                        <?= h($customer['full_name']) ?>
                                    </span>

                                    <span class="customer-email">
                                        <?= h($customer['email']) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= h($customer['phone']) ?>
                                </td>

                                <td>
                                    <span class="tier-badge tier-<?= h($tier) ?>">
                                        <?= h($tier) ?>
                                    </span>
                                </td>

                                <td class="customer-points">
                                    <?= number_format((int)$customer['points']) ?>
                                </td>

                                <td class="customer-revenue">
                                    <?= number_format((float)$customer['total_spent']) ?>
                                    đ
                                </td>

                                <td>
                                    <div class="customer-actions">

                                        <a
                                            class="btn-detail"
                                            href="index.php?c=customer&a=detail&id=<?= (int)$customer['id'] ?>"
                                        >
                                            Chi tiết
                                        </a>

                                        <a
                                            class="btn-edit"
                                            href="index.php?c=customer&a=edit&id=<?= (int)$customer['id'] ?>"
                                        >
                                            Sửa
                                        </a>

                                    </div>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

</div>
