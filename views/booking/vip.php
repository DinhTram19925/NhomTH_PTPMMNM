<?php
$statusLabels = [
    'Pending' => 'Chờ xác nhận',
    'Confirmed' => 'Đã xác nhận',
    'CheckedIn' => 'Đã check-in',
    'Cancelled' => 'Đã hủy'
];

$statusColors = [
    'Pending' => '#d99b32',
    'Confirmed' => '#168653',
    'CheckedIn' => '#285fc4',
    'Cancelled' => '#bd343e'
];

function bookingEscape($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>

<style>
/* ROYALDINE 5S - VIP BOOKING */

.rd-booking {
    --gold: #d8b66c;
    --gold-light: #f3dfaa;
    --bg: #111925;
    --panel: #1a2737;
    --panel2: #202e40;
    --border: #354458;
    --text: #f4f5f7;
    --muted: #a4b0c1;

    box-sizing: border-box;
    margin: 0;
    padding: 28px;
    min-height: 700px;
    color: var(--text);
    background:
        radial-gradient(
            ellipse at 90% 0%,
            #29374a 0%,
            transparent 38%
        ),
        var(--bg);
    border-radius: 14px;
    font-family: "Segoe UI", Arial, sans-serif;
}

.rd-booking *,
.rd-booking *::before,
.rd-booking *::after {
    box-sizing: border-box;
}

.rd-booking h2 {
    margin: 0 0 8px;
    color: var(--gold-light);
    font-family: Georgia, "Times New Roman", serif;
    font-size: clamp(27px, 3vw, 36px);
    font-weight: 500;
}

.rd-booking h3 {
    margin: 0 0 18px;
    color: var(--gold-light);
    font-family: Georgia, "Times New Roman", serif;
    font-size: 21px;
    font-weight: 500;
}

.rd-booking .booking-subtitle {
    margin: 0 0 25px;
    color: var(--muted);
    font-size: 13px;
    line-height: 1.7;
}

/* CARD */

.rd-booking .booking-card {
    margin-bottom: 22px;
    padding: 22px;
    border: 1px solid var(--border);
    border-radius: 13px;
    background: linear-gradient(145deg, #202e40, #192434);
    box-shadow: 0 10px 28px #00000018;
}

/* FORM */

.rd-booking .booking-form {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 16px;
}

.rd-booking .booking-field {
    display: flex;
    min-width: 0;
    flex-direction: column;
    gap: 8px;
}

.rd-booking .booking-field label {
    color: #d8dfe8;
    font-size: 12px;
    font-weight: 600;
}

.rd-booking .booking-field input,
.rd-booking .booking-field select,
.rd-booking .booking-field textarea {
    width: 100%;
    min-width: 0;
    padding: 11px 12px;
    border: 1px solid #46566c;
    border-radius: 8px;
    outline: none;
    color: var(--text);
    background: #121d2b;
    font: 13px "Segoe UI", Arial, sans-serif;
    color-scheme: dark;
    transition: .2s;
}

.rd-booking .booking-field input:focus,
.rd-booking .booking-field select:focus,
.rd-booking .booking-field textarea:focus {
    border-color: var(--gold);
    box-shadow: 0 0 0 3px #d8b66c18;
}

.rd-booking .booking-field input::placeholder,
.rd-booking .booking-field textarea::placeholder {
    color: #8492a5;
}

.rd-booking .booking-field textarea {
    min-height: 88px;
    resize: vertical;
}

/* BUTTON */

.rd-booking .booking-submit {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 42px;
    padding: 11px 20px;
    border: 1px solid #e7cd8c;
    border-radius: 8px;
    color: #171b21;
    background: linear-gradient(135deg, #f1dba3, #c8a458);
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    transition: .2s;
}

.rd-booking .booking-submit:hover {
    transform: translateY(-1px);
    filter: brightness(1.08);
}

/* NOTIFICATION */

.rd-booking .booking-success,
.rd-booking .booking-error {
    margin-bottom: 16px;
    padding: 13px 15px;
    border-radius: 8px;
    font-size: 13px;
}

.rd-booking .booking-success {
    border: 1px solid #287b61;
    color: #9ce8c7;
    background: #164333;
}

.rd-booking .booking-error {
    border: 1px solid #8e424b;
    color: #ffc1c6;
    background: #49252d;
}

/* BOOKING TABLE */

.rd-booking .booking-table-wrap {
    overflow-x: auto;
    border: 1px solid var(--border);
    border-radius: 9px;
}

.rd-booking .booking-table {
    width: 100%;
    min-width: 900px;
    border-collapse: collapse;
    background: #182434;
}

.rd-booking .booking-table th,
.rd-booking .booking-table td {
    padding: 13px 12px;
    border-bottom: 1px solid var(--border);
    color: #dce3ec;
    text-align: left;
    font-size: 12px;
    white-space: nowrap;
}

.rd-booking .booking-table th {
    color: var(--gold-light);
    background: #202e40;
    font-size: 11px;
    font-weight: 700;
}

.rd-booking .booking-table tbody tr {
    transition: background .2s;
}

.rd-booking .booking-table tbody tr:hover {
    background: #ffffff08;
}

.rd-booking .booking-table tbody tr:last-child td {
    border-bottom: 0;
}

.rd-booking .booking-table strong {
    color: var(--gold-light);
}

/* STATUS */

.rd-booking .booking-status {
    display: inline-block;
    padding: 6px 10px;
    border: 1px solid #ffffff20;
    border-radius: 20px;
    color: #fff;
    font-size: 11px;
    font-weight: 600;
    white-space: nowrap;
}

/* ACTIONS */

.rd-booking .booking-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.rd-booking .booking-actions form {
    margin: 0;
}

.rd-booking .booking-actions button {
    min-height: 30px;
    padding: 7px 10px;
    border: 1px solid transparent;
    border-radius: 6px;
    color: #fff;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
    transition: .2s;
}

.rd-booking .booking-actions button:hover {
    filter: brightness(1.12);
    transform: translateY(-1px);
}

.rd-booking .booking-confirm {
    background: #168653;
}

.rd-booking .booking-checkin {
    background: #285fc4;
}

.rd-booking .booking-cancel {
    background: #bd343e;
}

/* BACK LINK */

.rd-booking > a {
    display: inline-flex;
    margin-top: 2px;
    color: var(--gold);
    font-size: 13px;
    text-decoration: none;
}

.rd-booking > a:hover {
    color: var(--gold-light);
}

/* RESPONSIVE */

@media (max-width: 1050px) {
    .rd-booking .booking-form {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 700px) {
    .rd-booking {
        padding: 16px;
    }

    .rd-booking .booking-card {
        padding: 15px;
    }

    .rd-booking .booking-form {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="rd-booking">

    <!-- TIÊU ĐỀ -->
    <h2>Đặt bàn VIP</h2>

    <p class="booking-subtitle">
        Quản lý thông tin khách hàng và lịch đặt bàn nhà hàng RoyalDine 5S.
    </p>

    <!-- THÔNG BÁO -->
    <?php if (!empty($error)): ?>
        <div class="booking-error">
            <?= bookingEscape($error) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="booking-success">
            <?= bookingEscape($success) ?>
        </div>
    <?php endif; ?>

    <!-- FORM TẠO ĐẶT BÀN -->
    <section class="booking-card">

        <h3>＋ Tạo đặt bàn mới</h3>

        <form method="POST" class="booking-form">

            <input type="hidden" name="action" value="create">

            <div class="booking-field">
                <label>Họ tên khách hàng *</label>
                <input
                    type="text"
                    name="full_name"
                    maxlength="100"
                    required
                    placeholder="Nhập họ tên khách">
            </div>

            <div class="booking-field">
                <label>Số điện thoại *</label>
                <input
                    type="tel"
                    name="phone"
                    required
                    pattern="[0-9]{9,11}"
                    placeholder="Nhập số điện thoại">
            </div>

            <div class="booking-field">
                <label>Email</label>
                <input
                    type="email"
                    name="email"
                    maxlength="100"
                    placeholder="Email khách hàng">
            </div>

            <div class="booking-field">
                <label>Chọn bàn *</label>

                <select name="table_id" required>
                    <option value="">-- Chọn bàn --</option>

                    <?php foreach ($tables as $table): ?>
                        <option
                            value="<?= (int)$table['id'] ?>"
                            <?= (
                                isset($_GET['table_id'])
                                && (int)$_GET['table_id'] === (int)$table['id']
                            ) ? 'selected' : '' ?>>

                            <?= bookingEscape($table['table_name']) ?>
                            -
                            <?= bookingEscape($table['area_name']) ?>
                            (<?= (int)$table['capacity'] ?> khách)

                        </option>
                    <?php endforeach; ?>

                </select>
            </div>

            <div class="booking-field">
                <label>Ngày giờ đặt *</label>

                <input
                    type="datetime-local"
                    name="booking_time"
                    min="<?= date('Y-m-d\TH:i') ?>"
                    required>
            </div>

            <div class="booking-field">
                <label>Số lượng khách *</label>

                <input
                    type="number"
                    name="guest_count"
                    min="1"
                    max="100"
                    value="2"
                    required>
            </div>

            <div class="booking-field">
                <label>Tiền đặt cọc (VNĐ)</label>

                <input
                    type="number"
                    name="deposit_amount"
                    min="0"
                    step="1000"
                    value="0">
            </div>

            <div class="booking-field">
                <label>Yêu cầu đặc biệt</label>

                <textarea
                    name="special_request"
                    placeholder="Sinh nhật, trang trí bàn, yêu cầu riêng..."></textarea>
            </div>

            <div style="grid-column:1/-1">
                <button class="booking-submit" type="submit">
                    ＋ Tạo đặt bàn
                </button>
            </div>

        </form>
    </section>

    <!-- DANH SÁCH ĐẶT BÀN -->
    <section class="booking-card">

        <h3>Danh sách đặt bàn</h3>

        <div class="booking-table-wrap">

            <table class="booking-table">

                <thead>
                    <tr>
                        <th>Mã đặt bàn</th>
                        <th>Khách hàng</th>
                        <th>Điện thoại</th>
                        <th>Bàn</th>
                        <th>Thời gian</th>
                        <th>Số khách</th>
                        <th>Tiền cọc</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (empty($reservations)): ?>

                        <tr>
                            <td colspan="9" style="text-align:center">
                                Chưa có dữ liệu đặt bàn.
                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($reservations as $reservation): ?>

                            <?php
                            $status = $reservation['status'];
                            $color = $statusColors[$status] ?? '#64748b';
                            ?>

                            <tr>

                                <td>
                                    <strong>
                                        <?= bookingEscape($reservation['booking_code']) ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= bookingEscape($reservation['full_name']) ?>
                                </td>

                                <td>
                                    <?= bookingEscape($reservation['phone']) ?>
                                </td>

                                <td>
                                    <?= bookingEscape($reservation['table_name'] ?? 'Chưa chọn') ?>
                                </td>

                                <td>
                                    <?= bookingEscape($reservation['booking_time']) ?>
                                </td>

                                <td>
                                    <?= (int)$reservation['guest_count'] ?>
                                </td>

                                <td>
                                    <?= number_format(
                                        (float)$reservation['deposit_amount'],
                                        0,
                                        ',',
                                        '.'
                                    ) ?> đ
                                </td>

                                <td>
                                    <span
                                        class="booking-status"
                                        style="background:<?= $color ?>">

                                        <?= bookingEscape(
                                            $statusLabels[$status] ?? $status
                                        ) ?>

                                    </span>
                                </td>

                                <td>

                                    <div class="booking-actions">

                                        <?php if ($status === 'Pending'): ?>

                                            <form method="POST">

                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="status">

                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?= (int)$reservation['id'] ?>">

                                                <input
                                                    type="hidden"
                                                    name="status"
                                                    value="Confirmed">

                                                <button
                                                    class="booking-confirm"
                                                    type="submit">
                                                    Xác nhận
                                                </button>

                                            </form>

                                        <?php endif; ?>

                                        <?php if ($status === 'Confirmed'): ?>

                                            <form method="POST">

                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="status">

                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?= (int)$reservation['id'] ?>">

                                                <input
                                                    type="hidden"
                                                    name="status"
                                                    value="CheckedIn">

                                                <button
                                                    class="booking-checkin"
                                                    type="submit">
                                                    Check-in
                                                </button>

                                            </form>

                                        <?php endif; ?>

                                        <?php if (in_array(
                                            $status,
                                            ['Pending', 'Confirmed'],
                                            true
                                        )): ?>

                                            <form
                                                method="POST"
                                                onsubmit="return confirm('Bạn có chắc muốn hủy đặt bàn này?')">

                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="status">

                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?= (int)$reservation['id'] ?>">

                                                <input
                                                    type="hidden"
                                                    name="status"
                                                    value="Cancelled">

                                                <button
                                                    class="booking-cancel"
                                                    type="submit">
                                                    Hủy
                                                </button>

                                            </form>

                                        <?php endif; ?>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>
    </section>

    <a href="index.php?c=booking&a=index">
        ← Quay lại sơ đồ bàn
    </a>

</div>