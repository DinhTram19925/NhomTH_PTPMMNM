<?php
if (isset($_SESSION['user']['id']) && !isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = $_SESSION['user']['id'];
}
$currC   = strtolower($_GET['c'] ?? 'menu');
$currA   = strtolower($_GET['a'] ?? 'index');
$currTab = strtolower($_GET['tab'] ?? '');

$headerOpenShift = null;
$headerLowStockCount = 0;
try {
    $dbHeader = Database::getConnection();
    $headerOpenShift = $dbHeader->query("SELECT id, opened_at FROM cash_shifts WHERE status = 'Open' ORDER BY id DESC LIMIT 1")->fetch();
    $headerLowStockCount = (int)$dbHeader->query("SELECT COUNT(*) FROM ingredients WHERE stock_quantity <= min_stock_level")->fetchColumn();
} catch (Throwable $e) {}

$roleLabels = [
    'ADMIN'   => 'Quản trị hệ thống',
    'MANAGER' => 'Quản lý vận hành',
    'HOSTESS' => 'Lễ tân và đặt bàn',
    'WAITER'  => 'Phục vụ và thu ngân',
    'CASHIER' => 'Thu ngân POS',
    'CHEF'    => 'Bếp trưởng KDS',
    'BAR'     => 'Pha chế Bar',
    'STOCK'   => 'Thủ kho và kế toán'
];
$userRoleCode = strtoupper($_SESSION['user']['role_code'] ?? 'ADMIN');
$userRoleName = $roleLabels[$userRoleCode] ?? $userRoleCode;
$userFullName = str_replace(['(', ')'], '', $_SESSION['user']['full_name'] ?? 'Nhân viên');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RoyalDine 5S — Hệ thống quản lý và vận hành nhà hàng</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="public/assets/css/royaldine-design-system.css?v=<?= time() ?>" rel="stylesheet">
</head>
<body>
<div class="rd-layout-shell">
    <aside class="rd-sidebar">
        <div class="rd-brand-header">
            <h1 class="rd-brand-name">RoyalDine 5S</h1>
            <div class="rd-brand-tagline">Quản trị nhà hàng</div>
        </div>

        <div class="rd-nav-section">Điều hành và sảnh</div>
        <a href="index.php?c=menu&a=dashboard" class="rd-nav-link <?= ($currC=='menu' && $currA=='dashboard')?'active':'' ?>">Tổng quan hoạt động</a>
        <a href="index.php?c=booking&a=index" class="rd-nav-link <?= ($currC=='booking' && $currA=='index')?'active':'' ?>">Sơ đồ bàn</a>
        <a href="index.php?c=booking&a=vip" class="rd-nav-link <?= ($currC=='booking' && $currA=='vip')?'active':'' ?>">Lịch đặt bàn và nhận bàn</a>
        <a href="index.php?c=booking&a=manage" class="rd-nav-link <?= ($currC=='booking' && $currA=='manage')?'active':'' ?>">Thiết lập khu vực và bàn</a>

        <div class="rd-nav-section">Bán hàng và bếp</div>
        <a href="index.php?c=pos&a=index" class="rd-nav-link <?= ($currC=='pos' && $currA=='index')?'active':'' ?>">Bán hàng POS và bếp KDS</a>
        <a href="index.php?c=menu&a=index" class="rd-nav-link <?= ($currC=='menu' && in_array($currA,['index','bom']))?'active':'' ?>">Thực đơn và định lượng</a>

        <div class="rd-nav-section">Kho và mua hàng</div>
        <a href="index.php?c=stock&a=index&tab=dashboard" class="rd-nav-link <?= ($currC=='stock' && in_array($currTab,['','dashboard','ingredients','history']))?'active':'' ?>">Quản lý kho và nguyên liệu</a>
        <a href="index.php?c=stock_receipt&a=create&type=import" class="rd-nav-link <?= ($currC=='stock_receipt')?'active':'' ?>">Nhập xuất và kiểm kê</a>
        <a href="index.php?c=stock&a=index&tab=suppliers" class="rd-nav-link <?= ($currC=='stock' && $currTab=='suppliers')?'active':'' ?>">Nhà cung cấp và công nợ</a>

        <div class="rd-nav-section">Khách hàng và báo cáo</div>
        <a href="index.php?c=customer&a=index" class="rd-nav-link <?= ($currC=='customer')?'active':'' ?>">Khách hàng thành viên</a>
        <a href="index.php?c=promotion&a=index" class="rd-nav-link <?= ($currC=='promotion')?'active':'' ?>">Chương trình khuyến mãi</a>
        <a href="index.php?c=stock&a=index&tab=cash_shift" class="rd-nav-link <?= ($currC=='stock' && $currTab=='cash_shift')?'active':'' ?>">Ca thu ngân và thu chi</a>
        <a href="index.php?c=pos&a=report" class="rd-nav-link <?= ($currC=='pos' && $currA=='report')?'active':'' ?>">Báo cáo doanh thu POS</a>
        <a href="index.php?c=booking&a=report" class="rd-nav-link <?= ($currC=='booking' && $currA=='report')?'active':'' ?>">Báo cáo đặt bàn và khách hàng</a>
        <a href="index.php?c=menu&a=users" class="rd-nav-link <?= ($currC=='menu' && $currA=='users')?'active':'' ?>">Nhân sự và phân ca</a>
        <a href="index.php?c=booking&a=customer" target="_blank" class="rd-nav-link mt-2 border-top border-secondary pt-2">Cổng đặt bàn trực tuyến</a>
    </aside>

    <div class="rd-workspace">
        <header class="rd-topbar">
            <div class="rd-topbar-left">
                <span class="rd-branch-label">Chi nhánh RoyalDine Flagship</span>
                <span class="rd-clock-label" id="rdLiveClock"><?= date('H:i:s - d/m/Y') ?></span>
                <?php if ($headerOpenShift): ?>
                    <a href="index.php?c=stock&a=index&tab=cash_shift" class="rd-status-tag rd-status-success">
                        Ca thu ngân số <?= (int)$headerOpenShift['id'] ?> đang mở
                    </a>
                <?php else: ?>
                    <a href="index.php?c=stock&a=index&tab=cash_shift" class="rd-status-tag rd-status-danger">
                        Chưa mở ca thu ngân
                    </a>
                <?php endif; ?>
            </div>

            <div class="rd-topbar-right">
                <a href="index.php?c=stock&a=index&tab=ingredients&status_filter=low" class="btn btn-outline-slate btn-sm">
                    Cảnh báo tồn kho: <?= $headerLowStockCount ?>
                </a>
                <div class="vr"></div>
                <div class="text-end lh-sm">
                    <div class="fw-bold text-dark" style="font-size:13px;"><?= htmlspecialchars($userFullName) ?></div>
                    <div class="text-muted" style="font-size:11.5px;"><?= htmlspecialchars($userRoleName) ?></div>
                </div>
                <a href="index.php?c=auth&a=logout" class="btn btn-outline-danger btn-sm">Đăng xuất</a>
            </div>
        </header>

        <script>
        setInterval(function() {
            var el = document.getElementById('rdLiveClock');
            if (el) {
                var now = new Date();
                el.textContent = now.toLocaleTimeString('vi-VN') + ' - ' + now.toLocaleDateString('vi-VN');
            }
        }, 1000);
        </script>

        <main class="rd-main-container">