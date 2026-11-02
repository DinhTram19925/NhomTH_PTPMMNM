<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>RoyalDine 5S - Quản Trị Nhà Hàng 5 Sao</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        .sidebar { min-height: 100vh; background: #1a1d20; color: #fff; }
        .sidebar .brand { color: #d4af37; font-weight: bold; font-size: 1.3rem; padding: 20px; border-bottom: 1px solid #333; }
        .sidebar a { color: #adb5bd; text-decoration: none; display: block; padding: 12px 20px; border-left: 3px solid transparent; }
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
                Xin chào, <b><?= $_SESSION['user']['full_name'] ?? 'Khách' ?></b><br>
                <span class="badge badge-gold mt-1"><?= $_SESSION['user']['role_code'] ?? '' ?></span>
            </div>
            <a href="index.php?c=menu&a=dashboard">📊 Tổng quan (Dashboard)</a>
            <a href="index.php?c=menu&a=index">🍽️ Thực đơn & Định lượng BOM (TV1)</a>
            <a href="index.php?c=menu&a=users">👥 Nhân sự & Phân quyền (TV1)</a>
            <a href="index.php?c=booking&a=index">📅 Sơ đồ bàn & Đặt bàn VIP (TV2)</a>
            <a href="index.php?c=customer&a=index">👑 Hội viên VIP & Loyalty (TV2)</a>
            <a href="index.php?c=promotion&a=index">   🎟️ Quản lý Voucher</a>
            <a href="index.php?c=booking&a=report">📊 Báo cáo Booking & CRM</a>
            <a href="index.php?c=pos&a=index">💻 Bán hàng POS & Bếp KDS (TV3)</a>
            <a href="index.php?c=stock&a=index">📦 Kho & Bàn giao ca (TV4)</a>
            <a href="index.php?c=auth&a=logout" class="text-danger mt-4">🚪 Đăng xuất</a>
        </div>
        <div class="col-md-10 p-4">
