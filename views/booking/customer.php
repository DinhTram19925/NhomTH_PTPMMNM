<?php
$groupedTables = [];

foreach ($tables as $table) {
    $groupedTables[$table['area_name']][] = $table;
}

$selectedTableId = (int)($_POST['table_id'] ?? 0);
$success = $success ?? '';
$error = $error ?? '';

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

    <!-- MÀN HÌNH THANH TOÁN MÔ PHỎNG -->
    <?php if ($success !== ''): ?>

        <div class="booking-card text-center">

            <h4 class="text-success">Thanh toán đặt cọc</h4>

            <p>Mã đặt bàn:</p>

            <h5><?= htmlspecialchars($success) ?></h5>

            <h3 class="text-danger my-3">
                <?= number_format($depositAmount, 0, ',', '.') ?>đ
            </h3>

           <!-- QR TRANG TRÍ ROYALDINE 5S -->
<div class="royaldine-qr-card mx-auto my-4">

    <div class="royaldine-qr-header">
        <div class="royaldine-crown">♛</div>
        <div>
            <h4>RoyalDine 5S</h4>
            <p>THANH TOÁN ĐẶT CỌC</p>
        </div>
    </div>

    <div class="royaldine-qr-body">

        <div class="royaldine-qr-frame">

            <div class="qr-corner top-left"></div>
            <div class="qr-corner top-right"></div>
            <div class="qr-corner bottom-left"></div>
            <div class="qr-corner bottom-right"></div>

            <div class="royaldine-fake-qr">
                <?php
                $qrSeed = crc32($success);

                for ($i = 0; $i < 441; $i++):
                    $row = intdiv($i, 21);
                    $col = $i % 21;

                    $isFinder =
                        ($row < 7 && $col < 7) ||
                        ($row < 7 && $col >= 14) ||
                        ($row >= 14 && $col < 7);

                    $dark = (($qrSeed >> ($i % 32)) ^ ($i * 17 + $row * 7 + $col * 13)) % 3 !== 0;
                ?>
                    <span class="<?= $dark || $isFinder ? 'dark' : '' ?>"></span>
                <?php endfor; ?>
            </div>

            <div class="royaldine-qr-logo">♛</div>

        </div>

        <div class="royaldine-transfer">

            <small>NỘI DUNG CHUYỂN KHOẢN</small>

            <strong><?= htmlspecialchars($success) ?></strong>

        </div>

        <div class="royaldine-qr-note">
            <i class="bi bi-info-circle"></i>
        </div>

    </div>
</div>

<style>
.royaldine-qr-card {
    max-width: 340px;
    overflow: hidden;
    border: 2px solid #c69b45;
    border-radius: 18px;
    background: #fff;
    box-shadow: 0 8px 25px rgba(0,0,0,.12);
}

.royaldine-qr-header {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 16px;
    color: #f5d78d;
    background: linear-gradient(135deg,#101b30,#263a58);
}

.royaldine-crown {
    font-size: 43px;
    color: #e5bd69;
}

.royaldine-qr-header h4 {
    margin: 0;
    font-weight: 800;
    color: #e5bd69;
}

.royaldine-qr-header p {
    margin: 3px 0 0;
    font-size: 11px;
    letter-spacing: 1px;
}

.royaldine-qr-body {
    padding: 22px;
    text-align: center;
}

.royaldine-qr-frame {
    width: 240px;
    height: 240px;
    max-width: 100%;
    position: relative;
    margin: auto;
    padding: 13px;
    background: white;
}

.royaldine-fake-qr {
    width: 100%;
    height: 100%;
    display: grid;
    grid-template-columns: repeat(21,1fr);
    gap: 1px;
    background: white;
    padding: 3px;
}

.royaldine-fake-qr span.dark {
    background: #101827;
}

.royaldine-qr-logo {
    position: absolute;
    width: 48px;
    height: 48px;
    top: 50%;
    left: 50%;
    transform: translate(-50%,-50%);
    display: flex;
    align-items: center;
    justify-content: center;
    border: 4px solid white;
    border-radius: 50%;
    background: #17243b;
    color: #e5bd69;
    font-size: 30px;
}

.qr-corner {
    position: absolute;
    width: 28px;
    height: 28px;
    border-color: #c69b45;
    border-style: solid;
    z-index: 2;
}

.qr-corner.top-left {
    top: 0;
    left: 0;
    border-width: 4px 0 0 4px;
}

.qr-corner.top-right {
    top: 0;
    right: 0;
    border-width: 4px 4px 0 0;
}

.qr-corner.bottom-left {
    bottom: 0;
    left: 0;
    border-width: 0 0 4px 4px;
}

.qr-corner.bottom-right {
    bottom: 0;
    right: 0;
    border-width: 0 4px 4px 0;
}

.royaldine-transfer {
    margin-top: 20px;
    padding: 12px;
    border: 1px solid #e5bd69;
    border-radius: 10px;
    background: #fff9eb;
}

.royaldine-transfer small {
    display: block;
    color: #8c6824;
    font-weight: 700;
    margin-bottom: 6px;
}

.royaldine-transfer strong {
    color: #17243b;
    font-size: 16px;
    overflow-wrap: anywhere;
}

.royaldine-qr-note {
    margin-top: 12px;
    font-size: 12px;
    color: #9a752d;
}
</style>

            <p>Nội dung chuyển khoản:</p>

            <strong><?= htmlspecialchars($success) ?></strong>

            <div class="alert alert-info mt-3" id="paymentWaiting">
                Đang xác nhận thanh toán mô phỏng...
                <h3 id="countdown">5</h3>
            </div>

            <div class="alert alert-success mt-3"
                 id="paymentSuccess"
                 style="display:none">

                <h4>
                    <i class="bi bi-check-circle-fill"></i>
                    Thanh toán thành công!
                </h4>

                <p class="mb-0">
                   
                </p>

            </div>

            <a href="index.php?c=booking&a=customer"
               class="btn-gold d-inline-block mt-3">

                Quay lại trang đặt bàn

            </a>

        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function () {

            let seconds = 5;

            const countdown = document.getElementById('countdown');
            const waiting = document.getElementById('paymentWaiting');
            const success = document.getElementById('paymentSuccess');

            const timer = setInterval(function () {

                seconds--;

                countdown.textContent = seconds;

                if (seconds <= 0) {

                    clearInterval(timer);

                    waiting.style.display = 'none';

                    success.style.display = 'block';
                }

            }, 1000);

        });
        </script>

    <?php endif; ?>


    <!-- CHỈ HIỂN THỊ FORM KHI CHƯA TẠO ĐẶT BÀN -->
    <?php if ($success === ''): ?>

        <?php if ($error !== ''): ?>

            <div class="alert alert-danger">
                <?= htmlspecialchars($error) ?>
            </div>

        <?php endif; ?>


        <!-- 1. CHỌN THỜI GIAN VÀ SỐ LƯỢNG KHÁCH -->
        <div class="booking-card">

            <h5 class="section-title">1. Thông tin đặt bàn</h5>

            <form method="GET" action="index.php" class="row g-3">

                <input type="hidden" name="c" value="booking">
                <input type="hidden" name="a" value="customer">

                <div class="col-md-5">

                    <label class="form-label">
                        Ngày và giờ đặt
                    </label>

                    <input
                        type="datetime-local"
                        name="booking_time"
                        class="form-control"
                        value="<?= htmlspecialchars($bookingTime) ?>"
                        required
                    >

                </div>

                <div class="col-md-3">

                    <label class="form-label">
                        Số lượng khách
                    </label>

                    <input
                        type="number"
                        name="guest_count"
                        class="form-control"
                        min="1"
                        max="100"
                        value="<?= $guestCount ?>"
                        required
                    >

                </div>

                <div class="col-md-4 d-flex align-items-end">

                    <button type="submit" class="btn-gold w-100">

                        <i class="bi bi-search"></i>
                        Xem bàn trống

                    </button>

                </div>

            </form>

        </div>


        <!-- 2. DANH SÁCH BÀN -->
        <?php if ($bookingTime !== '' && $tables): ?>

            <div class="booking-card">

                <h5 class="section-title">
                    2. Chọn bàn của bạn
                </h5>

                <?php foreach ($groupedTables as $areaName => $areaTables): ?>

                    <div class="mb-4">

                        <div class="area-title">
                            <?= htmlspecialchars($areaName) ?>
                        </div>

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

                                        <div class="table-name">
                                            <?= htmlspecialchars($table['table_name']) ?>
                                        </div>

                                        <div class="capacity">
                                            <?= (int)$table['capacity'] ?> khách
                                        </div>

                                        <div class="available-label">

                                            <i class="bi bi-check-circle"></i>
                                            Bàn còn trống

                                        </div>

                                        <div class="selected-label mt-1"
                                             style="<?= $selectedTableId === $tableId ? '' : 'display:none' ?>">

                                            Đang chọn

                                        </div>

                                    </button>

                                </div>

                            <?php endforeach; ?>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php elseif ($bookingTime !== '' && !$error): ?>

            <div class="alert alert-warning">

                Không có bàn phù hợp trong thời gian này.
                Vui lòng chọn thời gian hoặc số lượng khách khác.

            </div>

        <?php endif; ?>


        <!-- 3. FORM THÔNG TIN KHÁCH HÀNG -->
        <div class="booking-card"
             id="customerForm"
             style="<?= $selectedTableId > 0 ? '' : 'display:none' ?>">

            <h5 class="section-title">
                3. Thông tin khách hàng
            </h5>

            <div class="alert alert-warning">

                <strong>Tiền đặt cọc dự kiến:</strong>

                <span id="depositAmount">
                    <?= number_format($depositAmount, 0, ',', '.') ?>đ
                </span>

                <p class="mb-0 mt-1">

                    Tiền cọc được khấu trừ vào hóa đơn khi khách sử dụng dịch vụ.
                    Phụ phí khu vực (nếu có) sẽ được tính vào hóa đơn sau.

                </p>

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


                <input
                    type="hidden"
                    name="booking_time"
                    value="<?= htmlspecialchars($bookingTime) ?>"
                >


                <input
                    type="hidden"
                    name="guest_count"
                    value="<?= $guestCount ?>"
                >


                <input
                    type="hidden"
                    name="table_id"
                    id="selectedTableId"
                    value="<?= $selectedTableId ?>"
                >


                <div class="row g-3">

                    <div class="col-md-6">

                        <label class="form-label">

                            Họ và tên
                            <span class="text-danger">*</span>

                        </label>

                        <input
                            type="text"
                            name="full_name"
                            class="form-control"
                            value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>"
                            maxlength="150"
                            required
                        >

                    </div>


                    <div class="col-md-6">

                        <label class="form-label">

                            Số điện thoại
                            <span class="text-danger">*</span>

                        </label>

                        <input
                            type="tel"
                            name="phone"
                            class="form-control"
                            value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                            pattern="[0-9]{9,11}"
                            maxlength="11"
                            required
                        >

                    </div>


                    <div class="col-md-6">

                        <label class="form-label">
                            Email
                        </label>

                        <input
                            type="email"
                            name="email"
                            class="form-control"
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        >

                    </div>


                    <div class="col-12">

                        <label class="form-label">
                            Yêu cầu đặc biệt
                        </label>

                        <textarea
                            name="special_request"
                            class="form-control"
                            rows="3"
                            placeholder="Ví dụ: Cần ghế trẻ em, bàn gần cửa sổ..."
                        ><?= htmlspecialchars($_POST['special_request'] ?? '') ?></textarea>

                    </div>


                    <div class="col-12">

                        <button type="submit" class="btn-gold w-100">

                            <i class="bi bi-calendar-check"></i>
                            Gửi yêu cầu đặt bàn

                        </button>

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


    // TÍNH TIỀN ĐẶT CỌC THEO SỐ LƯỢNG KHÁCH
    function calculateDeposit(count) {

        if (count <= 4) return 50000;

        if (count <= 8) return 100000;

        if (count <= 12) return 150000;

        if (count <= 20) return 200000;

        return 300000;
    }


    if (depositElement) {

        depositElement.textContent =
            calculateDeposit(guestCount).toLocaleString('vi-VN') + 'đ';

    }


    // CHỌN BÀN
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

            selectedTableCapacity.textContent =
                ' - Sức chứa: ' + this.dataset.capacity + ' khách';


            customerForm.style.display = 'block';


            customerForm.scrollIntoView({

                behavior: 'smooth',

                block: 'start'

            });

        });

    });

});
</script>