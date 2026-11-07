<?php
function customerDetailEscape($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}
$tier = $customer['tier'] ?? 'Standard';
$tierBadges = [
    'Standard' => 'bg-secondary-subtle text-secondary border-secondary-subtle',
    'Silver'   => 'bg-info-subtle text-info-emphasis border-info-subtle',
    'Gold'     => 'bg-warning-subtle text-warning-emphasis border-warning-subtle',
    'Diamond'  => 'bg-primary-subtle text-primary border-primary-subtle'
];
?>

<div class="rd-page-header">
    <div>
        <h2 class="rd-page-title"><i class="bi bi-person-badge-fill text-warning me-2"></i>Hồ sơ chi tiết khách hàng thành viên</h2>
        <p class="rd-page-desc">Thông tin định danh, hạng thẻ ưu đãi và lịch sử tích lũy tại RoyalDine 5S</p>
    </div>
    <div class="d-flex gap-2">
        <a href="index.php?c=customer&a=edit&id=<?= (int)$customer['id'] ?>" class="btn btn-warning">
            <i class="bi bi-pencil-square me-1"></i> Chỉnh sửa hồ sơ
        </a>
        <a href="index.php?c=customer&a=index" class="btn btn-outline-dark bg-white">
            <i class="bi bi-arrow-left me-1"></i> Quay lại danh sách
        </a>
    </div>
</div>

<div class="card p-4 mb-4">
    <div class="row align-items-center">
        <div class="col-md-8">
            <div class="d-flex align-items-center gap-3 mb-2">
                <h4 class="fw-bold mb-0"><?= customerDetailEscape($customer['full_name']) ?></h4>
                <span class="badge border <?= $tierBadges[$tier] ?? $tierBadges['Standard'] ?> fs-6">
                    Hạng <?= customerDetailEscape($tier) ?>
                </span>
            </div>
            <div class="text-secondary small">
                <span class="me-3"><i class="bi bi-telephone me-1"></i> Số điện thoại: <b class="font-mono"><?= customerDetailEscape($customer['phone']) ?></b></span>
                <span><i class="bi bi-envelope me-1"></i> Email: <b><?= customerDetailEscape($customer['email'] ?: 'Chưa cập nhật') ?></b></span>
            </div>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0 small text-muted">
            Ngày đăng ký hội viên: <b class="font-mono"><?= customerDetailEscape($customer['created_at']) ?></b>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card p-4 border-start border-primary border-4 h-100">
            <div class="text-muted small fw-semibold">ĐIỂM THƯỞNG TÍCH LŨY</div>
            <h3 class="fw-bold text-primary my-2 font-mono"><?= number_format((int)$customer['points']) ?> điểm</h3>
            <small class="text-muted">Tỷ lệ quy đổi: 10.000đ thanh toán = 1 điểm</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-4 border-start border-success border-4 h-100">
            <div class="text-muted small fw-semibold">TỔNG CHI TIÊU LŨY KẾ</div>
            <h3 class="fw-bold text-success my-2 font-mono"><?= number_format((float)$customer['total_spent']) ?> đ</h3>
            <small class="text-muted">Tự động cộng dồn từ các hóa đơn đã thanh toán</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-4 border-start border-warning border-4 h-100">
            <div class="text-muted small fw-semibold">MỨC CHIẾT KHẤU HẠNG THẺ</div>
            <h3 class="fw-bold text-warning my-2">
                <?= $tier === 'Diamond' ? '10%' : ($tier === 'Gold' ? '5%' : ($tier === 'Silver' ? '3%' : '0%')) ?>
            </h3>
            <small class="text-muted">Áp dụng tự động khi nhập SĐT trên màn hình POS</small>
        </div>
    </div>
</div>

<div class="card p-4">
    <h5 class="fw-bold mb-2"><i class="bi bi-award text-warning me-2"></i>Chính sách xét nâng hạng tự động</h5>
    <p class="text-muted small mb-3">Hệ thống tự động nâng hạng thẻ ngay sau khi khách hàng hoàn tất thanh toán hóa đơn đạt mốc chi tiêu lũy kế:</p>
    <div class="table-responsive">
        <table class="table table-bordered align-middle mb-0">
            <thead>
                <tr>
                    <th>Hạng thành viên</th>
                    <th class="text-end">Mức chi tiêu tích lũy tối thiểu</th>
                    <th class="text-center">Ưu đãi giảm giá trực tiếp trên hóa đơn</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="badge border bg-secondary-subtle text-secondary">Standard</span></td>
                    <td class="text-end font-mono">Từ 0 đ</td>
                    <td class="text-center">Tích điểm tiêu chuẩn</td>
                </tr>
                <tr>
                    <td><span class="badge border bg-info-subtle text-info-emphasis">Silver (Bạc)</span></td>
                    <td class="text-end font-mono fw-semibold">5.000.000 đ</td>
                    <td class="text-center fw-semibold text-info">Giảm 3% tổng tiền món</td>
                </tr>
                <tr>
                    <td><span class="badge border bg-warning-subtle text-warning-emphasis">Gold (Vàng)</span></td>
                    <td class="text-end font-mono fw-semibold">20.000.000 đ</td>
                    <td class="text-center fw-semibold text-warning">Giảm 5% tổng tiền món</td>
                </tr>
                <tr>
                    <td><span class="badge border bg-primary-subtle text-primary">Diamond (Kim Cương)</span></td>
                    <td class="text-end font-mono fw-bold">50.000.000 đ</td>
                    <td class="text-center fw-bold text-primary">Giảm 10% tổng tiền món</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>