
<?php
$promotions = $promotions ?? [];
$keyword = $keyword ?? '';

$message = $_SESSION['promotion_message'] ?? '';
$error = $_SESSION['promotion_error'] ?? '';

unset($_SESSION['promotion_message'], $_SESSION['promotion_error']);

function promotionEscape($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function promotionMoney($value): string
{
    return number_format((float)$value, 0, ',', '.') . 'đ';
}

$testResult = $testResult ?? null;
$testInput = $testInput ?? [];
?>

<style>
/* ROYALDINE 5S - PROMOTION MANAGEMENT */

.rd-promotion {
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

.rd-promotion *,
.rd-promotion *::before,
.rd-promotion *::after {
    box-sizing: border-box;
}

/* HEADER */

.rd-promotion .promotion-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    margin-bottom: 24px;
}

.rd-promotion h2 {
    margin: 0 0 7px;
    color: var(--gold-light);
    font: 500 clamp(25px, 3vw, 34px) Georgia, "Times New Roman", serif;
}

.rd-promotion .promotion-subtitle {
    margin: 0;
    color: var(--muted);
    font-size: 13px;
}

/* CARD */

.rd-promotion .promotion-card {
    margin-bottom: 20px;
    padding: 22px;
    border: 1px solid var(--border);
    border-radius: 13px;
    background: linear-gradient(145deg, #202e40, #192434);
    box-shadow: 0 10px 28px #00000018;
}

.rd-promotion h3 {
    margin: 0 0 10px;
    color: var(--gold-light);
    font: 500 21px Georgia, "Times New Roman", serif;
}

.rd-promotion .promotion-description {
    margin: 0 0 20px;
    color: var(--muted);
    font-size: 13px;
}

/* BUTTONS */

.rd-promotion .promotion-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 36px;
    padding: 8px 13px;
    border: 1px solid transparent;
    border-radius: 7px;
    font: 600 12px "Segoe UI", Arial, sans-serif;
    text-decoration: none;
    white-space: nowrap;
    cursor: pointer;
    transition: .2s;
}

.rd-promotion .promotion-btn:hover {
    filter: brightness(1.12);
    transform: translateY(-1px);
}

.rd-promotion .promotion-add,
.rd-promotion .promotion-test-btn {
    color: #171b21;
    border-color: #e7cd8c;
    background: linear-gradient(135deg, #f1dba3, #c8a458);
}

.rd-promotion .promotion-edit {
    color: #f3dfaa;
    border-color: #a88742;
    background: #4c4028;
}

.rd-promotion .promotion-toggle {
    color: #9ce8c7;
    border-color: #287b61;
    background: #164333;
}

.rd-promotion .promotion-delete {
    color: #ffc1c6;
    border-color: #8e424b;
    background: #49252d;
}

/* FORM */

.rd-promotion .promotion-form {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
    align-items: end;
}

.rd-promotion .promotion-field {
    display: flex;
    min-width: 0;
    flex-direction: column;
    gap: 7px;
}

.rd-promotion .promotion-field label {
    color: #d8dfe8;
    font-size: 12px;
    font-weight: 600;
}

.rd-promotion input,
.rd-promotion select {
    width: 100%;
    min-width: 0;
    min-height: 42px;
    padding: 10px 12px;
    border: 1px solid #46566c;
    border-radius: 8px;
    outline: none;
    color: var(--text);
    background: #121d2b;
    font: 13px "Segoe UI", Arial, sans-serif;
    transition: .2s;
}

.rd-promotion input::placeholder {
    color: #8492a5;
}

.rd-promotion input:focus,
.rd-promotion select:focus {
    border-color: var(--gold);
    box-shadow: 0 0 0 3px #d8b66c18;
}

.rd-promotion .promotion-form button {
    min-height: 42px;
    width: 100%;
}

/* TEST RESULT */

.rd-promotion .test-result {
    margin-top: 18px;
    padding: 16px;
    border: 1px solid var(--border);
    border-radius: 9px;
    background: #121d2b;
}

.rd-promotion .test-result.valid {
    border-color: #287b61;
    color: #9ce8c7;
}

.rd-promotion .test-result.invalid {
    border-color: #8e424b;
    color: #ffc1c6;
}

.rd-promotion .test-result hr {
    border: 0;
    border-top: 1px solid var(--border);
    margin: 13px 0;
}

.rd-promotion .test-result strong {
    color: var(--gold-light);
}

.rd-promotion .test-total {
    margin-top: 10px;
    font-size: 19px;
}

/* SEARCH */

.rd-promotion .promotion-search {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 130px;
    gap: 10px;
    margin-bottom: 18px;
}

.rd-promotion .promotion-search button {
    min-height: 42px;
    color: #171b21;
    border: 1px solid #e7cd8c;
    background: linear-gradient(135deg, #f1dba3, #c8a458);
}

/* TABLE */

.rd-promotion .promotion-table-wrap {
    width: 100%;
    overflow-x: auto;
    border: 1px solid var(--border);
    border-radius: 9px;
}

.rd-promotion .promotion-table {
    width: 100%;
    min-width: 950px;
    border-collapse: collapse;
    background: #182434;
}

.rd-promotion .promotion-table th,
.rd-promotion .promotion-table td {
    padding: 13px 11px;
    border-bottom: 1px solid var(--border);
    color: #dce3ec;
    text-align: left;
    vertical-align: middle;
    font-size: 12px;
}

.rd-promotion .promotion-table th {
    color: var(--gold-light);
    background: var(--panel2);
    font-size: 11px;
    font-weight: 700;
    white-space: nowrap;
}

.rd-promotion .promotion-table tbody tr {
    transition: background .2s;
}

.rd-promotion .promotion-table tbody tr:hover {
    background: #ffffff08;
}

.rd-promotion .promotion-table tbody tr:last-child td {
    border-bottom: 0;
}

.rd-promotion .promotion-code {
    color: #f3dfaa !important;
    font-weight: 700;
}

.rd-promotion .promotion-table small {
    color: var(--muted);
    font-size: 11px;
}

/* STATUS */

.rd-promotion .promotion-status {
    display: inline-block;
    padding: 6px 10px;
    border: 1px solid transparent;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    white-space: nowrap;
}

.rd-promotion .promotion-active {
    color: #9ce8c7;
    border-color: #287b61;
    background: #164333;
}

.rd-promotion .promotion-inactive {
    color: #c4ccd7;
    border-color: #596579;
    background: #39475a;
}

/* ACTIONS */

.rd-promotion .promotion-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 6px;
}

.rd-promotion .promotion-actions form {
    display: inline;
    margin: 0;
}

.rd-promotion .promotion-empty {
    padding: 35px 15px !important;
    color: var(--muted) !important;
    text-align: center !important;
}

/* ALERTS */

.rd-promotion .promotion-alert {
    margin-bottom: 16px;
    padding: 13px 15px;
    border-radius: 8px;
    font-size: 13px;
}

.rd-promotion .promotion-success {
    color: #9ce8c7;
    border: 1px solid #287b61;
    background: #164333;
}

.rd-promotion .promotion-error {
    color: #ffc1c6;
    border: 1px solid #8e424b;
    background: #49252d;
}

/* RESPONSIVE */

@media (max-width: 950px) {
    .rd-promotion .promotion-form {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 700px) {
    .rd-promotion {
        padding: 16px;
    }

    .rd-promotion .promotion-card {
        padding: 15px;
    }

    .rd-promotion .promotion-header {
        align-items: flex-start;
        flex-direction: column;
    }

    .rd-promotion .promotion-form,
    .rd-promotion .promotion-search {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="rd-promotion">

    <!-- HEADER -->
    <div class="promotion-header">
        <div>
            <h2>🎟️ Quản lý Voucher</h2>
            <p class="promotion-subtitle">
                Quản lý mã giảm giá và chương trình khuyến mãi RoyalDine 5S.
            </p>
        </div>

        <a href="index.php?c=promotion&a=create"
           class="promotion-btn promotion-add">
            ＋ Thêm voucher
        </a>
    </div>

    <!-- THÔNG BÁO -->
    <?php if ($message): ?>
        <div class="promotion-alert promotion-success">
            ✓ <?= promotionEscape($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="promotion-alert promotion-error">
            <?= promotionEscape($error) ?>
        </div>
    <?php endif; ?>

    <!-- KIỂM THỬ VOUCHER -->
    <section class="promotion-card">

        <h3>🧪 Kiểm thử mã Voucher</h3>

        <p class="promotion-description">
            Kiểm tra điều kiện sử dụng và số tiền khách hàng được giảm.
        </p>

        <form method="POST"
              action="index.php?c=promotion&a=testVoucher"
              class="promotion-form">

            <div class="promotion-field">
                <label>Mã voucher</label>
                <input
                    type="text"
                    name="code"
                    required
                    placeholder="VD: SALE10"
                    value="<?= promotionEscape($testInput['code'] ?? '') ?>"
                >
            </div>

            <div class="promotion-field">
                <label>Giá trị đơn hàng (VNĐ)</label>
                <input
                    type="number"
                    name="order_value"
                    min="1"
                    required
                    placeholder="1000000"
                    value="<?= promotionEscape($testInput['order_value'] ?? '') ?>"
                >
            </div>

            <div class="promotion-field">
                <label>Hạng thành viên</label>
                <select name="customer_tier">

                    <?php foreach (['Standard', 'Silver', 'Gold', 'Diamond'] as $tier): ?>

                        <option
                            value="<?= promotionEscape($tier) ?>"
                            <?= ($testInput['customer_tier'] ?? 'Standard') === $tier ? 'selected' : '' ?>
                        >
                            <?= promotionEscape($tier) ?>
                        </option>

                    <?php endforeach; ?>

                </select>
            </div>

            <button type="submit" class="promotion-btn promotion-test-btn">
                Kiểm tra voucher
            </button>

        </form>

        <!-- KẾT QUẢ KIỂM THỬ -->
        <?php if ($testResult !== null): ?>

            <div class="test-result <?= !empty($testResult['valid']) ? 'valid' : 'invalid' ?>">

                <strong>
                    <?= promotionEscape($testResult['message']) ?>
                </strong>

                <?php if (!empty($testResult['valid'])): ?>

                    <hr>

                    <div>
                        Giá trị đơn hàng:
                        <strong>
                            <?= number_format((float)$testInput['order_value'], 0, ',', '.') ?>đ
                        </strong>
                    </div>

                    <div style="margin-top:8px">
                        Tiền được giảm:
                        <strong>
                            -<?= number_format((float)$testResult['discount'], 0, ',', '.') ?>đ
                        </strong>
                    </div>

                    <div class="test-total">
                        Còn phải thanh toán:
                        <strong>
                            <?= number_format(
                                max(
                                    0,
                                    (float)$testInput['order_value']
                                    - (float)$testResult['discount']
                                ),
                                0,
                                ',',
                                '.'
                            ) ?>đ
                        </strong>
                    </div>

                <?php endif; ?>

            </div>

        <?php endif; ?>

    </section>

    <!-- DANH SÁCH VOUCHER -->
    <section class="promotion-card">

        <!-- TÌM KIẾM -->
        <form method="GET"
              action="index.php"
              class="promotion-search">

            <input type="hidden" name="c" value="promotion">
            <input type="hidden" name="a" value="index">

            <input
                type="text"
                name="keyword"
                value="<?= promotionEscape($keyword) ?>"
                placeholder="Tìm theo mã voucher hoặc tên chương trình..."
            >

            <button type="submit" class="promotion-btn">
                Tìm kiếm
            </button>

        </form>

        <!-- BẢNG VOUCHER -->
        <div class="promotion-table-wrap">

            <table class="promotion-table">

                <thead>
                    <tr>
                        <th>#</th>
                        <th>Mã voucher</th>
                        <th>Tên chương trình</th>
                        <th>Giảm giá</th>
                        <th>Đơn tối thiểu</th>
                        <th>Hạng áp dụng</th>
                        <th>Thời hạn</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (empty($promotions)): ?>

                        <tr>
                            <td colspan="9" class="promotion-empty">
                                Chưa có voucher nào.
                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($promotions as $index => $promotion): ?>

                            <tr>

                                <td>
                                    <?= $index + 1 ?>
                                </td>

                                <td class="promotion-code">
                                    <?= promotionEscape($promotion['code']) ?>
                                </td>

                                <td>
                                    <?= promotionEscape($promotion['name']) ?>
                                </td>

                                <td>

                                    <?php if ($promotion['discount_type'] === 'Percent'): ?>

                                        <?= promotionEscape($promotion['discount_value']) ?>%

                                    <?php else: ?>

                                        <?= promotionMoney($promotion['discount_value']) ?>

                                    <?php endif; ?>

                                    <?php if ($promotion['max_discount'] !== null): ?>

                                        <br>
                                        <small>
                                            Tối đa <?= promotionMoney($promotion['max_discount']) ?>
                                        </small>

                                    <?php endif; ?>

                                </td>

                                <td>
                                    <?= promotionMoney($promotion['min_order_value']) ?>
                                </td>

                                <td>
                                    <?= promotionEscape($promotion['min_tier']) ?>
                                </td>

                                <td>
                                    <?= promotionEscape($promotion['start_date']) ?>
                                    <br>
                                    <small>đến</small>
                                    <br>
                                    <?= promotionEscape($promotion['end_date']) ?>
                                </td>

                                <td>

                                    <?php if ((int)$promotion['is_active'] === 1): ?>

                                        <span class="promotion-status promotion-active">
                                            Đang bật
                                        </span>

                                    <?php else: ?>

                                        <span class="promotion-status promotion-inactive">
                                            Đã tắt
                                        </span>

                                    <?php endif; ?>

                                </td>

                                <td>

                                    <div class="promotion-actions">

                                        <a
                                            class="promotion-btn promotion-edit"
                                            href="index.php?c=promotion&a=edit&id=<?= (int)$promotion['id'] ?>"
                                        >
                                            Sửa
                                        </a>

                                        <form
                                            method="POST"
                                            action="index.php?c=promotion&a=toggle"
                                            onsubmit="return confirm('Bạn có chắc muốn đổi trạng thái voucher?')"
                                        >

                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?= (int)$promotion['id'] ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="promotion-btn promotion-toggle"
                                            >
                                                <?= (int)$promotion['is_active'] === 1 ? 'Tắt' : 'Bật' ?>
                                            </button>

                                        </form>

                                        <form
                                            method="POST"
                                            action="index.php?c=promotion&a=delete"
                                            onsubmit="return confirm('Bạn có chắc muốn xóa voucher này?')"
                                        >

                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?= (int)$promotion['id'] ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="promotion-btn promotion-delete"
                                            >
                                                Xóa
                                            </button>

                                        </form>

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
