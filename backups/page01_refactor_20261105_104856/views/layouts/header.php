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
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RoyalDine 5S - Hệ Thống Quản Trị Nhà Hàng Chuẩn CUKCUK</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="public/assets/css/royaldine-design-system.css?v=<?= time() ?>" rel="stylesheet">
    <style>
        :root { --rd-navy: #0f172a; --rd-navy-light: #1e293b; --rd-gold: #d4af37; --rd-bg: #f8fafc; }
        body { background-color: var(--rd-bg); font-family: 'Be Vietnam Pro', 'Segoe UI', system-ui, sans-serif; color: #0f172a; }
        .sidebar { min-height: 100vh; background: var(--rd-navy); color: #fff; box-shadow: 2px 0 12px rgba(0,0,0,0.12); }
        .sidebar .brand { color: var(--rd-gold); font-weight: 800; font-size: 1.2rem; padding: 16px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar .nav-section { font-size: 0.68rem; text-transform: uppercase; letter-spacing: 1.2px; color: #94a3b8; padding: 14px 18px 4px; font-weight: 700; }
        .sidebar a { color: #cbd5e1; text-decoration: none; display: flex; align-items: center; gap: 8px; padding: 8px 18px; border-left: 3px solid transparent; font-size: 0.86rem; transition: all 0.15s; }
        .sidebar a:hover, .sidebar a.active { background: var(--rd-navy-light); color: var(--rd-gold); border-left-color: var(--rd-gold); font-weight: 600; }
        .badge-gold { background-color: var(--rd-gold); color: #0f172a; font-weight: 700; }
        .rd-top-header { background: #ffffff; border-bottom: 1px solid #e2e8f0; padding: 10px 24px; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 100; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-2 p-0 sidebar">
            <div class="brand">👑 RoyalDine 5S</div>
            <div class="nav-section">I. Điều hành & Sảnh</div>
            <a href="index.php?c=menu&a=dashboard" class="<?= ($currC=='menu' && $currA=='dashboard')?'active':'' ?>"><i class="bi bi-speedometer2"></i> 1. Tổng quan (Dashboard)</a>
            <a href="index.php?c=booking&a=index" class="<?= ($currC=='booking' && $currA=='index')?'active':'' ?>"><i class="bi bi-grid-3x3-gap"></i> 2. Sơ đồ bàn</a>
            <a href="index.php?c=booking&a=vip" class="<?= ($currC=='booking' && $currA=='vip')?'active':'' ?>"><i class="bi bi-calendar2-check"></i> 3. Đặt bàn & Check-in</a>
            <a href="index.php?c=booking&a=manage" class="<?= ($currC=='booking' && $currA=='manage')?'active':'' ?>"><i class="bi bi-sliders"></i> 4. Thiết lập Khu vực/Bàn</a>

            <div class="nav-section">II. Bán hàng & Bếp</div>
            <a href="index.php?c=pos&a=index" class="<?= ($currC=='pos' && $currA=='index')?'active':'' ?>"><i class="bi bi-pc-display-horizontal"></i> 5. Bán hàng POS & KDS</a>
            <a href="index.php?c=menu&a=index" class="<?= ($currC=='menu' && $currA=='index')?'active':'' ?>"><i class="bi bi-journal-richtext"></i> 6. Thực đơn & BOM</a>

            <div class="nav-section">III. Kho & Mua hàng</div>
            <a href="index.php?c=stock&a=index&tab=dashboard" class="<?= ($currC=='stock' && in_array($currTab,['','dashboard','ingredients','history']))?'active':'' ?>"><i class="bi bi-box-seam"></i> 7. Quản lý Kho & NVL</a>
            <a href="index.php?c=stock_receipt&a=create&type=import" class="<?= ($currC=='stock_receipt')?'active':'' ?>"><i class="bi bi-truck"></i> 8. Nhập/Xuất & Kiểm kê</a>
            <a href="index.php?c=stock&a=index&tab=suppliers" class="<?= ($currC=='stock' && $currTab=='suppliers')?'active':'' ?>"><i class="bi bi-building"></i> 9. Nhà cung cấp & Công nợ</a>

            <div class="nav-section">IV. CRM, Quỹ ca & Báo cáo</div>
            <a href="index.php?c=customer&a=index" class="<?= ($currC=='customer')?'active':'' ?>"><i class="bi bi-gem"></i> 10. Khách hàng VIP (CRM)</a>
            <a href="index.php?c=promotion&a=index" class="<?= ($currC=='promotion')?'active':'' ?>"><i class="bi bi-ticket-perforated"></i> 11. Khuyến mãi / Voucher</a>
            <a href="index.php?c=stock&a=index&tab=cash_shift" class="<?= ($currC=='stock' && $currTab=='cash_shift')?'active':'' ?>"><i class="bi bi-cash-coin"></i> 12. Quỹ tiền & Giao ca</a>
            <a href="index.php?c=pos&a=report" class="<?= ($currC=='pos' && $currA=='report')?'active':'' ?>"><i class="bi bi-bar-chart-line"></i> 13. Báo cáo Doanh thu POS</a>
            <a href="index.php?c=booking&a=report" class="<?= ($currC=='booking' && $currA=='report')?'active':'' ?>"><i class="bi bi-pie-chart"></i> 14. Báo cáo Booking & CRM</a>
            <a href="index.php?c=menu&a=users" class="<?= ($currC=='menu' && $currA=='users')?'active':'' ?>"><i class="bi bi-people"></i> 15. Nhân viên & Phân ca</a>
            <a href="index.php?c=booking&a=customer" target="_blank"><i class="bi bi-globe"></i> 16. Cổng Khách Đặt Bàn Online</a>
        </div>

        <div class="col-md-10 p-0 d-flex flex-column min-vh-100">
            <header class="rd-top-header">
                <div class="d-flex align-items-center gap-3">
                    <span class="badge bg-dark text-warning px-3 py-2"><i class="bi bi-geo-alt-fill"></i> Chi nhánh: RoyalDine 5S Flagship</span>
                    <span class="text-muted small fw-semibold"><i class="bi bi-clock"></i> <span id="rdLiveClock"><?= date('H:i:s d/m/Y') ?></span></span>
                    <?php if ($headerOpenShift): ?>
                        <a href="index.php?c=stock&a=index&tab=cash_shift" class="badge bg-success text-decoration-none px-2 py-2"><i class="bi bi-unlock-fill"></i> Ca thu ngân #<?= (int)$headerOpenShift['id'] ?> đang mở</a>
                    <?php else: ?>
                        <a href="index.php?c=stock&a=index&tab=cash_shift" class="badge bg-danger text-decoration-none px-2 py-2"><i class="bi bi-lock-fill"></i> Chưa mở ca thu ngân</a>
                    <?php endif; ?>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <a href="index.php?c=stock&a=index&tab=ingredients&status_filter=low" class="btn btn-sm btn-outline-warning text-dark position-relative">
                        <i class="bi bi-bell-fill"></i> Cảnh báo kho
                        <?php if ($headerLowStockCount > 0): ?><span class="badge bg-danger ms-1"><?= $headerLowStockCount ?></span><?php endif; ?>
                    </a>
                    <div class="vr"></div>
                    <div class="small text-end">
                        <div class="fw-bold"><?= htmlspecialchars($_SESSION['user']['full_name'] ?? 'Khách') ?></div>
                        <span class="badge badge-gold"><?= htmlspecialchars($_SESSION['user']['role_code'] ?? '') ?></span>
                    </div>
                    <a href="index.php?c=auth&a=logout" class="btn btn-sm btn-outline-danger" title="Đăng xuất"><i class="bi bi-box-arrow-right"></i></a>
                </div>
            </header>
            <script>
            setInterval(function() {
                const el = document.getElementById('rdLiveClock');
                if (el) { const now = new Date(); el.textContent = now.toLocaleTimeString('vi-VN') + ' ' + now.toLocaleDateString('vi-VN'); }
            }, 1000);
            </script>
            <main class="p-4 flex-grow-1">