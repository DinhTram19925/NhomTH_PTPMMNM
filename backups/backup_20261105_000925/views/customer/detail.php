
<?php
function customerDetailEscape($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$tier = $customer['tier'] ?? 'Standard';
$tierColors = [
    'Standard' => 'secondary',
    'Silver' => 'secondary',
    'Gold' => 'warning',
    'Diamond' => 'info'
];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold">👑 Chi tiết hội viên</h3>
        <p class="text-muted mb-0">Thông tin và quyền lợi thành viên</p>
    </div>

    <a href="index.php?c=customer&a=index"
       class="btn btn-outline-secondary">
        ← Quay lại
    </a>
</div>

<div class="card border-0 shadow-sm p-4 mb-4" style="border-radius:15px">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h4 class="fw-bold">
                <?= customerDetailEscape($customer['full_name']) ?>
            </h4>

            <p class="text-muted mb-1">
                SĐT: <?= customerDetailEscape($customer['phone']) ?>
            </p>

            <p class="text-muted mb-2">
                Email: <?= customerDetailEscape($customer['email'] ?: 'Chưa cập nhật') ?>
            </p>

            <span class="badge bg-<?= $tierColors[$tier] ?? 'secondary' ?> fs-6">
                <?= customerDetailEscape($tier) ?>
            </span>
        </div>

        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <a
                href="index.php?c=customer&a=edit&id=<?= (int)$customer['id'] ?>"
                class="btn btn-warning"
            >
                Chỉnh sửa thông tin
            </a>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm p-4">
            <div class="text-muted">Điểm thưởng</div>
            <h3 class="fw-bold text-primary">
                <?= number_format((int)$customer['points']) ?>
            </h3>
            <small class="text-muted">Điểm tích lũy</small>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm p-4">
            <div class="text-muted">Tổng chi tiêu</div>
            <h3 class="fw-bold text-success">
                <?= number_format((float)$customer['total_spent']) ?> đ
            </h3>
            <small class="text-muted">Chi tiêu tích lũy</small>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm p-4">
            <div class="text-muted">Ngày tham gia</div>
            <h5 class="fw-bold mt-2">
                <?= customerDetailEscape($customer['created_at']) ?>
            </h5>
            <small class="text-muted">Ngày tạo tài khoản hội viên</small>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm p-4 mt-4">
    <h5 class="fw-bold">Điều kiện nâng hạng</h5>
    <p class="text-muted">
        Hạng thành viên được xác định dựa trên tổng chi tiêu tích lũy.
    </p>

    <table class="table">
        <tr><td>Silver</td><td class="text-end">5.000.000đ</td></tr>
        <tr><td>Gold</td><td class="text-end">20.000.000đ</td></tr>
        <tr><td>Diamond</td><td class="text-end">50.000.000đ</td></tr>
    </table>
</div>
