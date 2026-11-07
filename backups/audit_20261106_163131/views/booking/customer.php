<?php
$groupedTables = [];

foreach ($tables as $table) {
    $groupedTables[$table['area_name']][] = $table;
}

$selectedTableId = (int)($_POST['table_id'] ?? 0);
$success = $success ?? '';
$error = $error ?? '';
$isTestPaid = isset($_GET['test_paid']) && $_GET['test_paid'] === '1';

$guestCount = (int)($guestCount ?? 1);

$depositAmount = $depositAmount ?? match (true) {
    $guestCount <= 4  => 50000,
    $guestCount <= 8  => 100000,
    $guestCount <= 12 => 150000,
    $guestCount <= 20 => 200000,
    default           => 300000
};
?>

<style>
.customer-booking {
    max-width: 1150px;
    margin: 35px auto;
    color: #263247;
}
.customer-booking .page-title {
    color: #17243b;
    font-weight: 800;
}
.customer-booking .booking-card {
    background: #fff;
    border: 1px solid #e5e8ef;
    border-radius: 14px;
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: 0 4px 16px rgba(20, 35, 60, .05);
}
.customer-booking .section-title {
    color: #17243b;
    font-weight: 700;
    margin-bottom: 18px;
}
.customer-booking .area-title {
    background: #17243b;
    color: #e5bd69;
    padding: 13px 18px;
    border-radius: 9px;
    font-weight: 700;
    margin-bottom: 16px;
}
.customer-booking .table-option {
    width: 100%;
    height: 100%;
    min-height: 145px;
    padding: 18px 12px;
    border: 2px solid #e3e7ed;
    border-radius: 12px;
    background: #fff;
    cursor: pointer;
    transition: .2s;
    color: #263247;
}
.customer-booking .table-option:hover {
    border-color: #c69b45;
    transform: translateY(-3px);
}
.customer-booking .table-option.selected {
    border-color: #c69b45;
    background: #fff9eb;
    box-shadow: 0 0 0 2px rgba(198, 155, 69, .15);
}
.customer-booking .table-option .table-name {
    font-size: 19px;
    font-weight: 800;
}
.customer-booking .table-option .capacity {
    color: #687386;
    margin: 8px 0;
}
.customer-booking .available-label {
    color: #198754;
    font-size: 13px;
    font-weight: 700;
}
.customer-booking .selected-label {
    color: #a77920;
    font-size: 13px;
    font-weight: 700;
}
.customer-booking .btn-gold {
    background: #c69b45;
    border: 1px solid #c69b45;
    color: #17243b;
    font-weight: 700;
    padding: 11px 22px;
    border-radius: 8px;
}
.customer-booking .btn-gold:hover {
    background: #b28735;
    color: #fff;
}
.customer-booking .form-control {
    border-radius: 8px;
    padding: 10px 12px;
}
.customer-booking .form-label {
    font-weight: 600;
}
</style>

<div class="container customer-booking">

    <div class="text-center mb-4">
        <h2 class="page-title">ĐẶT BÀN NHÀ HÀNG</h2>
        <p class="text-muted">
            Chọn thời gian, số lượng khách và bàn phù hợp với bạn.
        </p>
    </div>

    <?php if ($success !== ''): ?>
        <div class="booking-card text-center">
            <span class="badge bg-warning text-dark mb-2">TEST MODE — MÔ PHỎNG ĐẶT CỌC</span>
            <h4 class="text-success">Thanh toán đặt cọc (Chế độ kiểm thử)</h4>
            <p>Mã đặt bàn:</p>
            <h5><?= htmlspecialchars($success) ?></h5>
            <h3 class="text-danger my-3">
                <?= number_format($depositAmount, 0, ',', '.') ?>đ
            </h3>

            <?php if (!$isTestPaid): ?>
                <div class="alert alert-info mt-3">
                    Phiếu đặt bàn đã được ghi nhận ở trạng thái <strong>Pending</strong>. Nhấn nút bên dưới để gửi xác nhận thanh toán đặt cọc mô phỏng (TEST MODE) về máy chủ:
                </div>
                <form method="POST" action="index.php?c=booking&a=customer" class="mt-3">
                    <input type="hidden" name="action" value="confirm_test_deposit">
                    <input type="hidden" name="booking_code" value="<?= htmlspecialchars($success) ?>">
                    <input type="hidden" name="guest_count" value="<?= (int)$guestCount ?>">
                    <button type="submit" class="btn-gold">
                        Xác nhận đặt cọc mô phỏng (TEST MODE)
                    </button>
                </form>
            <?php else: ?>
                <div class="alert alert-success mt-3">
                    <h4>Đã ghi nhận xác nhận đặt cọc (TEST MODE)!</h4>
                    <p class="mb-0">Lễ tân nhà hàng sẽ kiểm tra và xác nhận giữ bàn cho quý khách.</p>
                </div>
            <?php endif; ?>

            <a href="index.php?c=booking&a=customer" class="btn btn-outline-secondary d-inline-block mt-3">
                Quay lại trang đặt bàn
            </a>
        </div>
    <?php endif; ?>

    <?php if ($success === ''): ?>
        <?php if ($error !== ''): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="booking-card">
            <h5 class="section-title">1. Thông tin đặt bàn</h5>
            <form method="GET" action="index.php" class="row g-3">
                <input type="hidden" name="c" value="booking">
                <input type="hidden" name="a" value="customer">
                <div class="col-md-5">
                    <label class="form-label">Ngày và giờ đặt</label>
                    <input type="datetime-local" name="booking_time" class="form-control" value="<?= htmlspecialchars($bookingTime) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Số lượng khách</label>
                    <input type="number" name="guest_count" class="form-control" min="1" max="100" value="<?= $guestCount ?>" required>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn-gold w-100">Xem bàn trống</button>
                </div>
            </form>
        </div>

        <?php if ($bookingTime !== '' && $tables): ?>
            <div class="booking-card">
                <h5 class="section-title">2. Chọn bàn của bạn</h5>
                <?php foreach ($groupedTables as $areaName => $areaTables): ?>
                    <div class="mb-4">
                        <div class="area-title"><?= htmlspecialchars($areaName) ?></div>
                        <div class="row g-3">
                            <?php foreach ($areaTables as $table): ?>
                                <?php $tableId = (int)$table['id']; ?>
                                <div class="col-6 col-md-3">
                                    <button
                                        type="button"
                                        class="table-option <?= $selectedTableId === $tableId ? 'selected' : '' ?>"
                                        data-id="<?= $tableId ?>"
                                        data-name="<?= htmlspecialchars($table['table_name'], ENT_QUOTES) ?>"
                                        data-capacity="<?= (int)$table['capacity'] ?>"
                                    >
                                        <div class="table-name"><?= htmlspecialchars($table['table_name']) ?></div>
                                        <div class="capacity"><?= (int)$table['capacity'] ?> khách</div>
                                        <div class="available-label">Bàn còn trống</div>
                                        <div class="selected-label mt-1" style="<?= $selectedTableId === $tableId ? '' : 'display:none' ?>">Đang chọn</div>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php elseif ($bookingTime !== '' && !$error): ?>
            <div class="alert alert-warning">
                Không có bàn phù hợp trong thời gian này. Vui lòng chọn thời gian hoặc số lượng khách khác.
            </div>
        <?php endif; ?>

        <div class="booking-card" id="customerForm" style="<?= $selectedTableId > 0 ? '' : 'display:none' ?>">
            <h5 class="section-title">3. Thông tin khách hàng</h5>
            <div class="alert alert-warning">
                <strong>Tiền đặt cọc dự kiến:</strong>
                <span id="depositAmount"><?= number_format($depositAmount, 0, ',', '.') ?>đ</span>
                <p class="mb-0 mt-1">Tiền cọc được khấu trừ vào hóa đơn khi khách sử dụng dịch vụ.</p>
            </div>
            <div class="alert alert-info">
                Bàn đang chọn:
                <strong id="selectedTableName">
                    <?php
                    foreach ($tables as $table) {
                        if ((int)$table['id'] === $selectedTableId) {
                            echo htmlspecialchars($table['table_name']);
                            break;
                        }
                    }
                    ?>
                </strong>
                <span id="selectedTableCapacity"></span>
            </div>

            <form method="POST" action="index.php?c=booking&a=customer">
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="booking_time" value="<?= htmlspecialchars($bookingTime) ?>">
                <input type="hidden" name="guest_count" value="<?= $guestCount ?>">
                <input type="hidden" name="table_id" id="selectedTableId" value="<?= $selectedTableId ?>">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Họ và tên <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" maxlength="150" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                        <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" pattern="[0-9]{9,11}" maxlength="11" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Yêu cầu đặc biệt</label>
                        <textarea name="special_request" class="form-control" rows="3"><?= htmlspecialchars($_POST['special_request'] ?? '') ?></textarea>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn-gold w-100">Gửi yêu cầu đặt bàn</button>
                    </div>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const guestCount = <?= $guestCount ?>;
    const depositElement = document.getElementById('depositAmount');

    function calculateDeposit(count) {
        if (count <= 4) return 50000;
        if (count <= 8) return 100000;
        if (count <= 12) return 150000;
        if (count <= 20) return 200000;
        return 300000;
    }

    if (depositElement) {
        depositElement.textContent = calculateDeposit(guestCount).toLocaleString('vi-VN') + 'đ';
    }

    const tableButtons = document.querySelectorAll('.table-option');
    const customerForm = document.getElementById('customerForm');
    const selectedTableId = document.getElementById('selectedTableId');
    const selectedTableName = document.getElementById('selectedTableName');
    const selectedTableCapacity = document.getElementById('selectedTableCapacity');

    tableButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            tableButtons.forEach(function (item) {
                item.classList.remove('selected');
                item.querySelector('.selected-label').style.display = 'none';
            });
            this.classList.add('selected');
            this.querySelector('.selected-label').style.display = 'block';
            selectedTableId.value = this.dataset.id;
            selectedTableName.textContent = this.dataset.name;
            selectedTableCapacity.textContent = ' - Sức chứa: ' + this.dataset.capacity + ' khách';
            customerForm.style.display = 'block';
            customerForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });
});
</script>