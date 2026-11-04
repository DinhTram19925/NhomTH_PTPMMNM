
<?php
function customerFormEscape($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$isEdit = !empty($customer);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold">
            <?= $isEdit ? '✏️ Chỉnh sửa hội viên' : '👑 Thêm hội viên mới' ?>
        </h3>
        <p class="text-muted mb-0">Thông tin khách hàng RoyalDine 5S</p>
    </div>

    <a href="index.php?c=customer&a=index" class="btn btn-outline-secondary">
        ← Quay lại
    </a>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger">
        <?= customerFormEscape($error) ?>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm p-4" style="border-radius:15px">
    <form method="POST">
        <div class="row g-3">

            <div class="col-md-6">
                <label class="form-label fw-bold">Họ và tên *</label>
                <input
                    type="text"
                    name="full_name"
                    class="form-control"
                    maxlength="100"
                    required
                    value="<?= customerFormEscape(
                        $_POST['full_name'] ?? $customer['full_name'] ?? ''
                    ) ?>"
                    placeholder="Nhập họ tên khách hàng"
                >
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold">Số điện thoại *</label>
                <input
                    type="tel"
                    name="phone"
                    class="form-control"
                    pattern="[0-9]{9,11}"
                    maxlength="11"
                    required
                    value="<?= customerFormEscape(
                        $_POST['phone'] ?? $customer['phone'] ?? ''
                    ) ?>"
                    placeholder="Nhập số điện thoại"
                >
            </div>

            <div class="col-md-12">
                <label class="form-label fw-bold">Email</label>
                <input
                    type="email"
                    name="email"
                    class="form-control"
                    maxlength="100"
                    value="<?= customerFormEscape(
                        $_POST['email'] ?? $customer['email'] ?? ''
                    ) ?>"
                    placeholder="example@gmail.com"
                >
            </div>

            <?php if ($isEdit): ?>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Hạng thành viên</label>
                    <input
                        type="text"
                        class="form-control"
                        value="<?= customerFormEscape($customer['tier']) ?>"
                        readonly
                    >
                    <small class="text-muted">
                        Hạng được xác định theo tổng chi tiêu tích lũy.
                    </small>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">Điểm thưởng</label>
                    <input
                        type="text"
                        class="form-control"
                        value="<?= number_format((int)$customer['points']) ?> điểm"
                        readonly
                    >
                </div>
            <?php endif; ?>

        </div>

        <hr class="my-4">

        <div class="d-flex justify-content-end gap-2">
            <a href="index.php?c=customer&a=index"
               class="btn btn-light border">
                Hủy
            </a>

            <button type="submit" class="btn btn-warning fw-bold px-4">
                <?= $isEdit ? 'Lưu thay đổi' : 'Tạo hội viên' ?>
            </button>
        </div>
    </form>
</div>
