<?php
if (isset($_SESSION['user']['id']) && !isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = $_SESSION['user']['id'];
}
$currC = strtolower($_GET['c'] ?? 'menu');
$currA = strtolower($_GET['a'] ?? 'index');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>RoyalDine 5S - Quản Trị Nhà Hàng 5 Sao</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        .sidebar { min-height: 100vh; background: #1a1d20; color: #fff; }
        .sidebar .brand { color: #d4af37; font-weight: bold; font-size: 1.25rem; padding: 18px 20px; border-bottom: 1px solid #333; }
        .sidebar .nav-section { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px; color: #6c757d; padding: 12px 20px 4px; font-weight: 700; }
        .sidebar a { color: #adb5bd; text-decoration: none; display: block; padding: 9px 20px; border-left: 3px solid transparent; font-size: 0.92rem; }
        .sidebar a:hover, .sidebar a.active { background: #2c3034; color: #d4af37; border-left-color: #d4af37; }
        .badge-gold { background-color: #d4af37; color: #000; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-2 p-0 sidebar">
            <div class="brand">🌟 RoyalDine 5S</div>
            <div class="p-3 border-bottom border-secondary small">
                Xin chào, <b><?= htmlspecialchars($_SESSION['user']['full_name'] ?? 'Khách') ?></b><br>
                <span class="badge badge-gold mt-1"><?= htmlspecialchars($_SESSION['user']['role_code'] ?? '') ?></span>
            </div>

            <div class="nav-section">TV1 · Quản trị & Menu</div>
            <a href="index.php?c=menu&a=dashboard" class="<?= ($currC=='menu' && $currA=='dashboard')?'active':'' ?>">📊 Tổng quan (Dashboard)</a>
            <a href="index.php?c=menu&a=index" class="<?= ($currC=='menu' && in_array($currA,['index','bom']))?'active':'' ?>">🍽️ Thực đơn & BOM</a>
            <a href="index.php?c=menu&a=users" class="<?= ($currC=='menu' && $currA=='users')?'active':'' ?>">👥 Nhân sự & Phân quyền</a>

            <div class="nav-section">TV2 · Bàn, Booking & CRM</div>
            <a href="index.php?c=booking&a=index" class="<?= ($currC=='booking' && $currA=='index')?'active':'' ?>">🗺️ Sơ đồ bàn nhà hàng</a>
            <a href="index.php?c=booking&a=vip" class="<?= ($currC=='booking' && $currA=='vip')?'active':'' ?>">📅 Đặt bàn VIP & Check-in</a>
            <a href="index.php?c=booking&a=manage" class="<?= ($currC=='booking' && $currA=='manage')?'active':'' ?>">⚙️ Quản lý Khu vực & Bàn</a>
            <a href="index.php?c=customer&a=index" class="<?= ($currC=='customer')?'active':'' ?>">💎 Hội viên VIP (CRM)</a>
            <a href="index.php?c=promotion&a=index" class="<?= ($currC=='promotion')?'active':'' ?>">🎟️ Khuyến mãi / Voucher</a>
            <a href="index.php?c=booking&a=report" class="<?= ($currC=='booking' && $currA=='report')?'active':'' ?>">📈 Báo cáo Đặt bàn & VIP</a>

            <div class="nav-section">TV3 · Bán hàng & Bếp</div>
            <a href="index.php?c=pos&a=index" class="<?= ($currC=='pos' && $currA=='index')?'active':'' ?>">💻 Bán hàng POS & KDS</a>
            <a href="index.php?c=pos&a=report" class="<?= ($currC=='pos' && $currA=='report')?'active':'' ?>">🧾 Báo cáo Doanh thu POS</a>

            <div class="nav-section">TV4 · Kho & Quỹ ca</div>
            <a href="index.php?c=stock&a=index&tab=dashboard" class="<?= ($currC=='stock' && ($_GET['tab']??'dashboard')!='cash_shift')?'active':'' ?>">📦 Quản lý Kho & NVL</a>
            <a href="index.php?c=stock&a=index&tab=cash_shift" class="<?= ($currC=='stock' && ($_GET['tab']??'')=='cash_shift')?'active':'' ?>">💰 Quỹ tiền & Bàn giao ca</a>

            <a href="index.php?c=auth&a=logout" class="text-danger mt-3 border-top border-secondary pt-3">🚪 Đăng xuất</a>
        </div>
        <div class="col-md-10 p-4">
