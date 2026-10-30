
<?php
$statusLabels = [
    'Pending' => 'Chờ xác nhận',
    'Confirmed' => 'Đã xác nhận',
    'CheckedIn' => 'Đã check-in',
    'Cancelled' => 'Đã hủy'
];

$statusColors = [
    'Pending' => '#f59e0b',
    'Confirmed' => '#16a34a',
    'CheckedIn' => '#2563eb',
    'Cancelled' => '#dc2626'
];

function bookingEscape($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>

<style>
.booking-page {
    padding: 25px;
    color: #1f2937;
}
.booking-page h2 {
    margin-bottom: 5px;
}
.booking-subtitle {
    color: #64748b;
    margin-bottom: 25px;
}
.booking-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    padding: 22px;
    margin-bottom: 22px;
}
.booking-form {
    display: grid;
    grid-template-columns: repeat(auto-fit,minmax(210px,1fr));
    gap: 15px;
}
.booking-field {
    display: flex;
    flex-direction: column;
    gap: 7px;
}
.booking-field label {
    font-weight: 600;
    font-size: 14px;
}
.booking-field input,
.booking-field select,
.booking-field textarea {
    width: 100%;
    box-sizing: border-box;
    padding: 11px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font: inherit;
}
.booking-field textarea {
    min-height: 85px;
    resize: vertical;
}
.booking-submit {
    background: #b9974b;
    color: white;
    border: 0;
    border-radius: 8px;
    padding: 12px 20px;
    font-weight: bold;
    cursor: pointer;
}
.booking-submit:hover {
    background: #967735;
}
.booking-success {
    background: #dcfce7;
    color: #166534;
    padding: 13px;
    border-radius: 8px;
    margin-bottom: 15px;
}
.booking-error {
    background: #fee2e2;
    color: #991b1b;
    padding: 13px;
    border-radius: 8px;
    margin-bottom: 15px;
}
.booking-table-wrap {
    overflow-x: auto;
}
.booking-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 850px;
}
.booking-table th,
.booking-table td {
    padding: 12px;
    border-bottom: 1px solid #e5e7eb;
    text-align: left;
    font-size: 14px;
}
.booking-table th {
    background: #f8fafc;
}
.booking-status {
    display: inline-block;
    padding: 5px 9px;
    border-radius: 20px;
    color: white;
    font-size: 12px;
    white-space: nowrap;
}
.booking-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}
.booking-actions button {
    border: 0;
    border-radius: 6px;
    padding: 7px 9px;
    cursor: pointer;
    color: white;
}
.booking-confirm { background: #16a34a; }
.booking-checkin { background: #2563eb; }
.booking-cancel { background: #dc2626; }

@media(max-width:700px) {
    .booking-page { padding: 12px; }
    .booking-card { padding: 15px; }
}
</style>

<div class="booking-page">

    <h2>Đặt bàn VIP</h2>
    <p class="booking-subtitle">
        Quản lý thông tin khách hàng và lịch đặt bàn nhà hàng RoyalDine 5S.
    </p>

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

        <h3>Tạo đặt bàn mới</h3>

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
                        <option value="<?= (int)$table['id'] ?>">
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
                    + Tạo đặt bàn
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
                                <?= number_format((float)$reservation['deposit_amount'], 0, ',', '.') ?> đ
                            </td>

                            <td>
                                <span
                                    class="booking-status"
                                    style="background:<?= $color ?>">
                                    <?= bookingEscape($statusLabels[$status] ?? $status) ?>
                                </span>
                            </td>

                            <td>

                                <div class="booking-actions">

                                    <?php if ($status === 'Pending'): ?>

                                        <form method="POST">
                                            <input type="hidden" name="action" value="status">
                                            <input type="hidden" name="id" value="<?= (int)$reservation['id'] ?>">
                                            <input type="hidden" name="status" value="Confirmed">

                                            <button class="booking-confirm" type="submit">
                                                Xác nhận
                                            </button>
                                        </form>

                                    <?php endif; ?>

                                    <?php if ($status === 'Confirmed'): ?>

                                        <form method="POST">
                                            <input type="hidden" name="action" value="status">
                                            <input type="hidden" name="id" value="<?= (int)$reservation['id'] ?>">
                                            <input type="hidden" name="status" value="CheckedIn">

                                            <button class="booking-checkin" type="submit">
                                                Check-in
                                            </button>
                                        </form>

                                    <?php endif; ?>

                                    <?php if (in_array($status, ['Pending', 'Confirmed'], true)): ?>

                                        <form method="POST"
                                              onsubmit="return confirm('Bạn có chắc muốn hủy đặt bàn này?')">

                                            <input type="hidden" name="action" value="status">
                                            <input type="hidden" name="id" value="<?= (int)$reservation['id'] ?>">
                                            <input type="hidden" name="status" value="Cancelled">

                                            <button class="booking-cancel" type="submit">
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
