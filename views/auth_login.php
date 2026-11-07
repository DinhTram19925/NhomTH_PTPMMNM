<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập hệ thống — RoyalDine 5S</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="public/assets/css/royaldine-design-system.css" rel="stylesheet">
    <style>
        body.login-screen {
            background: linear-gradient(135deg, #0b1120 0%, #1e293b 100%) !important;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-card {
            width: 100%;
            max-width: 440px;
            background: #ffffff;
            border-radius: 16px;
            border-top: 5px solid #d4af37 !important;
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.35) !important;
            padding: 32px;
        }
        .role-pill {
            cursor: pointer;
            font-size: 0.76rem;
            padding: 5px 10px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #334155;
            transition: all 0.15s;
        }
        .role-pill:hover {
            border-color: #c59b27;
            background: #fffbeb;
            color: #0f172a;
        }
    </style>
</head>
<body class="login-screen">
    <div class="login-card">
        <div class="text-center mb-4">
            <div class="d-inline-flex align-items-center justify-content-center rounded-3 mb-2" style="width:48px;height:48px;background:#0f172a;color:#d4af37;font-size:1.5rem;">
                <i class="bi bi-award-fill"></i>
            </div>
            <h3 class="fw-bold mb-1" style="color:#0f172a;">RoyalDine 5S</h3>
            <p class="text-muted small mb-0">Hệ thống quản lý và vận hành nhà hàng chuyên nghiệp</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2 small d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="index.php?c=auth&a=login">
            <div class="mb-3">
                <label class="form-label">Tên đăng nhập</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                    <input type="text" name="username" id="loginUser" class="form-control" value="admin" placeholder="Nhập tài khoản nhân viên" required>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label">Mật khẩu</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" id="loginPass" class="form-control" value="123456" placeholder="Nhập mật khẩu" required>
                </div>
            </div>
            <button type="submit" class="btn btn-warning w-100 py-2">
                <i class="bi bi-box-arrow-in-right me-1"></i> Đăng nhập hệ thống
            </button>
        </form>

        <div class="mt-4 pt-3 border-top">
            <div class="small fw-semibold text-secondary mb-2">Chọn nhanh tài khoản theo bộ phận (Mật khẩu: <code>123456</code>):</div>
            <div class="d-flex flex-wrap gap-1">
                <button type="button" class="role-pill" onclick="fillLogin('admin')">Quản trị (admin)</button>
                <button type="button" class="role-pill" onclick="fillLogin('hostess')">Lễ tân (hostess)</button>
                <button type="button" class="role-pill" onclick="fillLogin('cashier')">Thu ngân POS (cashier)</button>
                <button type="button" class="role-pill" onclick="fillLogin('chef')">Bếp KDS (chef)</button>
                <button type="button" class="role-pill" onclick="fillLogin('stock')">Thủ kho (stock)</button>
            </div>
        </div>
    </div>
    <script>
    function fillLogin(user) {
        document.getElementById('loginUser').value = user;
        document.getElementById('loginPass').value = '123456';
    }
    </script>
</body>
</html>