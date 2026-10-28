<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng nhập - RoyalDine 5S</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark d-flex align-items-center justify-content-center" style="height: 100vh;">
    <div class="card shadow-lg p-4" style="width: 420px; border-top: 4px solid #d4af37;">
        <h3 class="text-center fw-bold mb-1" style="color: #b8860b;">🌟 RoyalDine 5S</h3>
        <p class="text-center text-muted small mb-4">Hệ Thống Quản Trị Nhà Hàng 5 Sao</p>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2"><?= $error ?></div>
        <?php endif; ?>
        <form method="POST" action="index.php?c=auth&a=login">
            <div class="mb-3">
                <label class="form-label fw-semibold">Tài khoản</label>
                <input type="text" name="username" class="form-control" value="admin" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Mật khẩu</label>
                <input type="password" name="password" class="form-control" value="123456" required>
            </div>
            <button type="submit" class="btn w-100 fw-bold text-dark" style="background-color: #d4af37;">ĐĂNG NHẬP HỆ THỐNG</button>
        </form>
        <div class="mt-3 small text-muted border-top pt-2">
            <b>Tài khoản test sẵn (MK: 123456):</b><br>
            • <code>admin</code> (Mai Trâm) | • <code>hostess</code> (Khánh Vy)<br>
            • <code>cashier</code> (Trà My) | • <code>stock</code> (Kim Hiền)
        </div>
    </div>
</body>
</html>
