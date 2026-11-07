<?php
function customerFormEscape($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}
$isEdit = !empty($customer);
?>

<div class="rd-page-header">
    <div>
        <h2 class="rd-page-title">
            <i class="bi <?= $isEdit ? 'bi-pencil-square' : 'bi-person-plus-fill' ?> text-warning me-2"></i>
            <?= $isEdit ? 'Cập nhật thông tin khách hàng' : 'Thêm khách hàng thành viên mới' ?>
        </h2>
        <p class="rd-page-desc">Quản lý thông tin liên hệ và định danh tích điểm khách hàng tại RoyalDine 5S</p>
    </div>
    <div>
        <a href="index.php?c=customer&a=index" class="btn btn-outline-dark bg-white">
            <i class="bi bi-arrow-left me-1"></i> Quay lại danh sách
        </a>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger py-2 d-flex align-items-center gap-2">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <span><?= customerFormEscape($error) ?></span>
    </div>
<?php endif; ?>

<div class="card p-4">
    <form method="POST">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Họ và tên khách hàng *</label>
                <input type="text" name="full_name" class="form-control" maxlength="100" required
                       value="<?= customerFormEscape($_POST['full_name'] ?? $customer['full_name'] ?? '') ?>"
                       placeholder="Nhập đầy đủ họ và tên khách hàng">
            </div>
            <div class="col-md-6">
                <label class="form-label">Số điện thoại di động *</label>
                <input type="tel" name="phone" class="form-control font-mono" pattern="[0-9]{9,11}" maxlength="11" required
                       value="<?= customerFormEscape($_POST['phone'] ?? $customer['phone'] ?? '') ?>"
                       placeholder="Nhập số điện thoại 9-11 chữ số">
            </div>
            <div class="col-md-12">
                <label class="form-label">Địa chỉ Email</label>
                <input type="email" name="email" class="form-control" maxlength="100"
                       value="<?= customerFormEscape($_POST['email'] ?? $customer['email'] ?? '') ?>"
                       placeholder="vidu@email.com">
            </div>

            <?php if ($isEdit): ?>
                <div class="col-md-6">
                    <label class="form-label">Hạng thẻ hiện tại</label>
                    <input type="text" class="form-control bg-light fw-bold" value="<?= customerFormEscape($customer['tier']) ?>" readonly>
                    <small class="text-muted">Hạng thẻ được nâng tự động dựa trên tổng chi tiêu tích lũy.</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Điểm thưởng tích lũy</label>
                    <input type="text" class="form-control bg-light font-mono" value="<?= number_format((int)$customer['points']) ?> điểm" readonly>
                </div>
            <?php endif; ?>
        </div>

        <hr class="my-4">
        <div class="d-flex justify-content-end gap-2">
            <a href="index.php?c=customer&a=index" class="btn btn-outline-secondary">Hủy bỏ</a>
            <button type="submit" class="btn btn-warning px-4">
                <i class="bi bi-check2-circle me-1"></i> <?= $isEdit ? 'Lưu thông tin khách hàng' : 'Tạo hồ sơ khách hàng' ?>
            </button>
        </div>
    </form>
</div>